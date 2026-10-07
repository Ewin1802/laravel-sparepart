<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberBarcode;
use App\Models\MysteryBoxReward;
use App\Models\Product;
use App\Models\StampTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberStampController extends Controller
{
    /**
     * ============================================================
     * SYARAT MINIMAL BELANJA UNTUK DAPAT STAMP
     * ============================================================
     *
     * Disamakan persis dengan Api\OrderController — selain aturan
     * "1 kunjungan/hari = maksimal 1 stamp", member juga cuma dapat
     * stamp kalau nilai transaksinya (order->total) MINIMAL
     * Rp 22.000.
     */
    private const MINIMUM_ORDER_FOR_STAMP = 50000;

    // ============================================================
    // SHOW STAMP
    // ============================================================

    public function show(Request $request)
    {
        $user = $request->user();

        $barcode = MemberBarcode::with('user')
            ->where('user_id', $user->id)
            ->first();

        // Target stamp Marimoi Cafe: 10 stamp.
        // Normalisasi member lama yang masih menggunakan target 5.
        if ($barcode && (int) $barcode->stamp_target !== 10) {
            $barcode->stamp_target = 10;
            $barcode->save();
        }

        if (!$barcode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data member tidak ditemukan.',
            ], 404);
        }

        // ============================================================
        // TIDAK LAGI MEMBLOKIR RESPONSE KETIKA MEMBER NON-AKTIF.
        //
        // Data tetap dikirim dengan is_active yang sebenarnya,
        // supaya aplikasi Flutter bisa memperbarui status di cache
        // dan menampilkan "Non Aktif" dengan benar.
        // ============================================================

        return response()->json([
            'status' => 'success',

            'data' => [

                // =====================================================
                // MEMBER
                // =====================================================

                'id' => $barcode->id,

                'user_id' => $barcode->user_id,

                'name' => $barcode->user->name,

                'email' => $barcode->user->email,

                'phone_number' => $barcode->phone_number ?? $barcode->user->phone_number ?? '',

                // =====================================================
                // BARCODE
                // =====================================================

                'code' => $barcode->code,

                'birth_date' => $barcode->birth_date,

                // =====================================================
                // DISCOUNT
                // =====================================================

                'discount_type' => $barcode->discount_type,

                'discount_value' => $barcode->discount_value,

                // =====================================================
                // STAMP
                // =====================================================

                'stamp_count' => $barcode->stamp_count,

                'stamp_target' => $barcode->stamp_target,

                // =====================================================
                // STATUS
                // =====================================================

                'is_active' => (bool) $barcode->is_active,

                // =====================================================
                // VALIDITY
                // =====================================================

                'valid_from' => $barcode->valid_from,

                'valid_until' => $barcode->valid_until,
            ],
        ], 200);
    }

    // ============================================================
    // HISTORY
    // ============================================================

    public function history(Request $request)
    {
        $user = $request->user();

        $member = MemberBarcode::where(
            'user_id',
            $user->id
        )->first();

        if (!$member) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data member tidak ditemukan.',
            ], 404);
        }

        $history = StampTransaction::where(
            'member_barcode_id',
            $member->id
        )
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }

    /**
     * ==========================================================
     * EARN STAMP
     * ==========================================================
     *
     * Menambahkan 1 stamp setelah transaksi member berhasil.
     *
     * ATURAN (2 SYARAT SEKALIGUS):
     *
     * 1. 1 KUNJUNGAN (HARI) = MAKSIMAL 1 STAMP.
     *    Sebelumnya dicek per order_id, sehingga belanja 3x dalam
     *    sehari menghasilkan 3 stamp. Sekarang dicek per TANGGAL —
     *    belanja berapa kali pun dalam hari yang sama tetap cuma
     *    dapat 1 stamp.
     *
     * 2. NILAI ORDER MINIMAL Rp 22.000.
     *    Order di bawah itu TIDAK dapat stamp sama sekali.
     *
     * Logika ini disamakan persis dengan Api\OrderController@saveOrder
     * supaya konsisten kalau endpoint ini dipanggil dari tempat lain
     * juga.
     */
    public function earn(Request $request)
    {
        // ============================================================
        // VALIDASI REQUEST
        // ============================================================

        $request->validate([
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
            ],

            'member_code' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        // ============================================================
        // TRANSACTION
        // ============================================================

        return DB::transaction(function () use ($request) {

            // ========================================================
            // BERSIHKAN MEMBER CODE
            // ========================================================

            $memberCode = trim(
                $request->member_code
            );

            // ========================================================
            // CARI MEMBER + LOCK
            // ========================================================

            $member = MemberBarcode::where(
                'code',
                $memberCode
            )
                ->lockForUpdate()
                ->first();

            // Target stamp Marimoi Cafe: 10 stamp.
            // Normalisasi member lama yang masih menggunakan target 5.
            if ($member && (int) $member->stamp_target !== 10) {
                $member->stamp_target = 10;
                $member->save();
            }

            // ========================================================
            // MEMBER TIDAK DITEMUKAN
            // ========================================================

            if (!$member) {
                return response()->json([
                    'status' => 'error',

                    'message' =>
                        'Member tidak ditemukan.',
                ], 404);
            }

            // ========================================================
            // CEK VALIDITAS MEMBER
            // ========================================================

            if (!$member->isValid()) {
                return response()->json([
                    'status' => 'error',

                    'message' =>
                        'Member tidak aktif atau barcode sudah tidak berlaku.',
                ], 422);
            }

            // ========================================================
            // CEK ORDER
            // ========================================================

            $order = DB::table('orders')
                ->where(
                    'id',
                    $request->order_id
                )
                ->first();

            if (!$order) {
                return response()->json([
                    'status' => 'error',

                    'message' =>
                        'Order tidak ditemukan.',
                ], 404);
            }

            // ========================================================
            // CEK ORDER SUDAH PERNAH DIPROSES ATAU BELUM
            // ========================================================
            //
            // Tetap dicek per order_id di sini — bukan buat nentuin
            // dapat stamp atau nggak, tapi buat mencegah SATU order
            // yang sama diproses dua kali (misal request dikirim
            // ulang karena timeout).
            //
            // ========================================================

            $alreadyProcessedThisOrder = StampTransaction::where(
                'member_barcode_id',
                $member->id
            )
                ->where(
                    'order_id',
                    $order->id
                )
                ->where(
                    'type',
                    'earn'
                )
                ->exists();

            if ($alreadyProcessedThisOrder) {

                return response()->json([
                    'status' => 'exists',

                    'message' =>
                        'Order ini sudah pernah diproses.',

                    'data' => [
                        'stamp_count' =>
                            $member->stamp_count,

                        'stamp_target' =>
                            $member->stamp_target,

                        'mystery_box_ready' =>
                            $member->stamp_count >=
                            $member->stamp_target,
                    ],
                ], 200);
            }

            // ========================================================
            // SYARAT #1: NILAI ORDER MINIMAL Rp 22.000
            // ========================================================

            $orderTotal = (float) $order->total;

            if ($orderTotal < self::MINIMUM_ORDER_FOR_STAMP) {

                StampTransaction::create([
                    'member_barcode_id' =>
                        $member->id,

                    'order_id' =>
                        $order->id,

                    'type' =>
                        'earn',

                    'amount' =>
                        0,

                    'note' =>
                        'Order #' .
                        $order->id .
                        ' - total belanja Rp ' .
                        number_format($orderTotal, 0, ',', '.') .
                        ' belum memenuhi syarat minimal Rp ' .
                        number_format(self::MINIMUM_ORDER_FOR_STAMP, 0, ',', '.') .
                        ' untuk mendapatkan stamp.',
                ]);

                return response()->json([
                    'status' => 'below_minimum',

                    'message' =>
                        'Total belanja belum memenuhi syarat minimal Rp ' .
                        number_format(self::MINIMUM_ORDER_FOR_STAMP, 0, ',', '.') .
                        ' untuk mendapatkan stamp.',

                    'data' => [
                        'stamp_count' =>
                            $member->stamp_count,

                        'stamp_target' =>
                            $member->stamp_target,

                        'mystery_box_ready' =>
                            $member->stamp_count >=
                            $member->stamp_target,
                    ],
                ], 200);
            }

            // ========================================================
            // SYARAT #2: CEK APAKAH MEMBER SUDAH DAPAT STAMP HARI INI
            // ========================================================
            //
            // PENTING: dibandingkan berdasarkan transaction_time
            // (waktu transaksi ASLI dari HP kasir), BUKAN jam server.
            // Ini krusial buat kasir yang sempat offline — lihat
            // catatan yang sama di Api\OrderController@saveOrder.
            //
            // ========================================================

            $referenceDate = \Carbon\Carbon::parse(
                $order->transaction_time
            )->toDateString();

            $alreadyStampedToday = StampTransaction::query()
                ->join(
                    'orders',
                    'orders.id',
                    '=',
                    'stamp_transactions.order_id'
                )
                ->where(
                    'stamp_transactions.member_barcode_id',
                    $member->id
                )
                ->where(
                    'stamp_transactions.type',
                    'earn'
                )
                ->where(
                    'stamp_transactions.amount',
                    '>',
                    0
                )
                ->whereDate(
                    'orders.transaction_time',
                    $referenceDate
                )
                ->exists();

            if ($alreadyStampedToday) {

                StampTransaction::create([
                    'member_barcode_id' =>
                        $member->id,

                    'order_id' =>
                        $order->id,

                    'type' =>
                        'earn',

                    'amount' =>
                        0,

                    'note' =>
                        'Order #' .
                        $order->id .
                        ' - stamp hari ini sudah didapat dari transaksi lain.',
                ]);

                return response()->json([
                    'status' => 'already_stamped_today',

                    'message' =>
                        'Stamp hari ini sudah didapat dari transaksi lain.',

                    'data' => [
                        'stamp_count' =>
                            $member->stamp_count,

                        'stamp_target' =>
                            $member->stamp_target,

                        'mystery_box_ready' =>
                            $member->stamp_count >=
                            $member->stamp_target,
                    ],
                ], 200);
            }

            // ========================================================
            // CEK STAMP SUDAH PENUH
            // ========================================================

            if (
                $member->stamp_count >=
                $member->stamp_target
            ) {

                StampTransaction::create([
                    'member_barcode_id' =>
                        $member->id,

                    'order_id' =>
                        $order->id,

                    'type' =>
                        'earn',

                    'amount' =>
                        0,

                    'note' =>
                        'Order #' .
                        $order->id .
                        ' - stamp sudah penuh. Menunggu redeem Mystery Box.',
                ]);

                return response()->json([
                    'status' => 'full',

                    'message' =>
                        'Stamp sudah penuh. Silakan redeem Mystery Box terlebih dahulu.',

                    'data' => [
                        'stamp_count' =>
                            $member->stamp_count,

                        'stamp_target' =>
                            $member->stamp_target,

                        'mystery_box_ready' =>
                            true,
                    ],
                ], 200);
            }

            // ========================================================
            // TAMBAH 1 STAMP (UNTUK KUNJUNGAN HARI INI)
            // ========================================================

            $newStampCount = min(
                $member->stamp_count + 1,
                $member->stamp_target
            );

            $member->stamp_count =
                $newStampCount;

            $member->save();

            $member->refresh();

            StampTransaction::create([
                'member_barcode_id' =>
                    $member->id,

                'order_id' =>
                    $order->id,

                'type' =>
                    'earn',

                'amount' =>
                    1,

                'note' =>
                    'Stamp dari kunjungan hari ini (order #' .
                    $order->id .
                    ').',
            ]);

            $mysteryBoxReady =
                $member->stamp_count >=
                $member->stamp_target;

            $message = $mysteryBoxReady
                ? 'Stamp berhasil ditambahkan. Mystery Box sudah tersedia!'
                : 'Stamp berhasil ditambahkan.';

            return response()->json([
                'status' => 'success',

                'message' =>
                    $message,

                'data' => [
                    'stamp_count' =>
                        $member->stamp_count,

                    'stamp_target' =>
                        $member->stamp_target,

                    'mystery_box_ready' =>
                        $mysteryBoxReady,
                ],
            ], 200);
        });
    }

    // ============================================================
    // REDEEM MYSTERY BOX
    // ============================================================
    //
    // TIDAK DIUBAH — redeem gak ada hubungannya sama "per hari"
    // atau "minimal belanja", cuma berlaku sekali pas stamp udah
    // penuh.
    // ============================================================

    public function redeem(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $user = $request->user();

            $member = MemberBarcode::where(
                'user_id',
                $user->id
            )
                ->lockForUpdate()
                ->first();

            if ($member && (int) $member->stamp_target !== 10) {
                $member->stamp_target = 10;
                $member->save();
            }

            if (!$member) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data member tidak ditemukan.',
                ], 404);
            }

            if (!$member->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Member tidak aktif atau barcode sudah tidak berlaku.',
                ], 422);
            }

            if ($member->stamp_count < $member->stamp_target) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Stamp belum mencukupi.',
                    'data' => [
                        'stamp_count' => $member->stamp_count,
                        'stamp_target' => $member->stamp_target,
                        'mystery_box_ready' => false,
                    ],
                ], 422);
            }

            $existingReward = MysteryBoxReward::where(
                'member_barcode_id',
                $member->id
            )
                ->where(
                    'status',
                    'available'
                )
                ->lockForUpdate()
                ->first();

            if ($existingReward) {

                $usedStamp = $member->stamp_target;

                $member->decrement(
                    'stamp_count',
                    $usedStamp
                );

                $member->refresh();

                StampTransaction::create([
                    'member_barcode_id' => $member->id,
                    'order_id' => null,
                    'type' => 'redeem',
                    'amount' => $usedStamp,
                    'note' =>
                        'Redeem Mystery Box #' .
                        $existingReward->id .
                        ' - ' .
                        $existingReward->product_name,
                ]);

                $existingReward->update([
                    'status' => 'redeemed',
                ]);

                return response()->json([
                    'status' => 'success',

                    'message' =>
                        'Mystery Box berhasil diredeem!',

                    'data' => [
                        'reward_id' => $existingReward->id,

                        'product' => [
                            'id' => $existingReward->product_id,
                            'name' => $existingReward->product_name,
                            'price' => $existingReward->product_price,
                        ],

                        'stamp_count' =>
                            $member->stamp_count,

                        'stamp_target' =>
                            $member->stamp_target,

                        'mystery_box_ready' =>
                            $member->stamp_count >=
                            $member->stamp_target,
                    ],
                ]);
            }

            $product = Product::where('status', 1)
                ->where('stock', '>', 0)
                ->whereIn('category_id', [1, 2]) // Bonus Mystery Box hanya dari kategori Es Kopi Series & Non Kopi Series
                ->inRandomOrder()
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' =>
                        'Saat ini belum ada produk yang tersedia untuk Mystery Box.',
                ], 422);
            }

            $reward = MysteryBoxReward::create([
                'member_barcode_id' => $member->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_price' => $product->price ?? 0,
                'status' => 'redeemed',
            ]);

            $product->decrement('stock', 1);

            $usedStamp = $member->stamp_target;
            $member->decrement('stamp_count', $usedStamp);
            $member->refresh();

            StampTransaction::create([
                'member_barcode_id' => $member->id,
                'order_id' => null,
                'type' => 'redeem',
                'amount' => $usedStamp,
                'note' =>
                    'Redeem Mystery Box #' .
                    $reward->id .
                    ' - ' .
                    $product->name,
            ]);

            return response()->json([
                'status' => 'success',
                'message' =>
                    'Mystery Box berhasil dibuka!',
                'data' => [
                    'reward_id' => $reward->id,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'image' => $product->image,
                    ],
                    'stamp_count' =>
                        $member->stamp_count,
                    'stamp_target' =>
                        $member->stamp_target,
                    'mystery_box_ready' =>
                        $member->stamp_count >=
                        $member->stamp_target,
                ],
            ]);
        });
    }
}

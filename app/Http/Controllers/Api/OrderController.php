<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberBarcode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product; // 🔥 tambahkan di atas
use App\Models\StampTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * ============================================================
     * SYARAT MINIMAL BELANJA UNTUK DAPAT STAMP
     * ============================================================
     *
     * Selain aturan "1 kunjungan/hari = maksimal 1 stamp", member
     * juga cuma dapat stamp kalau nilai transaksinya (order->total)
     * MINIMAL Rp 22.000. Di bawah itu, order tetap tersimpan normal
     * — cuma gak dapat stamp.
     */
    private const MINIMUM_ORDER_FOR_STAMP = 50000;

    private function getMemberStampData(
        ?string $memberCode
    ): ?array {

        if (!$memberCode) {
            return null;
        }

        $member = MemberBarcode::where(
            'code',
            $memberCode
        )->first();

        if (!$member) {
            return null;
        }

        return [
            'id' =>
                $member->id,

            'code' =>
                $member->code,

            'stamp_count' =>
                $member->stamp_count,

            'stamp_target' =>
                $member->stamp_target,

            'mystery_box_available' =>
                $member->hasMysteryReward(),
        ];
    }

    public function saveOrder(Request $request)
    {
        // ============================================================
        // VALIDATION
        // ============================================================

        $request->validate([

            // --------------------------------------------------------
            // IDEMPOTENCY
            // --------------------------------------------------------

            'client_order_id' => [
                'required',
                'uuid',
            ],

            // --------------------------------------------------------
            // PAYMENT
            // --------------------------------------------------------

            'payment_amount' => 'required',
            'sub_total' => 'required',
            'tax' => 'required',
            'discount' => 'required',
            'discount_amount' => 'required',
            'service_charge' => 'required',
            'total' => 'required',

            'payment_method' => [
                'required',
                'string',
            ],

            'total_item' => 'required',

            // --------------------------------------------------------
            // CASHIER
            // --------------------------------------------------------

            'id_kasir' => 'required',
            'nama_kasir' => 'required|string',

            // --------------------------------------------------------
            // TRANSACTION
            // --------------------------------------------------------

            'transaction_time' => [
                'required',
                'string',
            ],

            // --------------------------------------------------------
            // MEMBER
            // --------------------------------------------------------

            'member_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            // --------------------------------------------------------
            // TABLE
            // --------------------------------------------------------

            'table_number' => [
                'nullable',
                'integer',
            ],

            // --------------------------------------------------------
            // CUSTOMER
            // --------------------------------------------------------

            'customer_name' => [
                'nullable',
                'string',
            ],

            // --------------------------------------------------------
            // STATUS
            // --------------------------------------------------------

            'status' => [
                'nullable',
                'string',
            ],

            // --------------------------------------------------------
            // ORDER ITEMS
            // --------------------------------------------------------

            'order_items' => [
                'required',
                'array',
                'min:1',
            ],

            'order_items.*.id_product' => [
                'required',
                'integer',
            ],

            'order_items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'order_items.*.price' => [
                'required',
                'numeric',
                'gte:0',
            ],
        ]);

        // ============================================================
        // CLIENT ORDER ID
        // ============================================================

        $clientOrderId =
            $request->client_order_id;

        // ============================================================
        // 1. CEK ORDER YANG SUDAH ADA
        // ============================================================
        //
        // Ini adalah fast-path.
        //
        // Kalau Flutter mengirim request yang sama lagi,
        // kita langsung mengembalikan order sebelumnya.
        //
        // ============================================================

        $existingOrder = Order::with('orderItems')
            ->where(
                'client_order_id',
                $clientOrderId
            )
            ->first();

        if ($existingOrder) {

            return response()->json([
                'status' => 'exists',

                'message' =>
                    'Order sudah pernah disimpan.',

                'data' => [
                    'order' => $existingOrder,

                    'member' => $this->getMemberStampData(
                        $existingOrder->member_code
                    ),
                ],
            ], 200);
        }

        // ============================================================
        // 2. TRANSACTION
        // ============================================================

        try {

            return DB::transaction(function () use ($request) {

                // ====================================================
                // MEMBER
                // ====================================================

                $member = null;

                if ($request->filled('member_code')) {

                    $member = MemberBarcode::where(
                        'code',
                        $request->member_code
                    )
                        ->where(
                            'is_active',
                            true
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$member) {

                        throw new \Exception(
                            'Member tidak ditemukan atau tidak aktif.'
                        );
                    }

                    // ------------------------------------------------
                    // VALID FROM
                    // ------------------------------------------------

                    if (
                        $member->valid_from &&
                        now()->lt(
                            $member->valid_from
                        )
                    ) {

                        throw new \Exception(
                            'Member belum aktif.'
                        );
                    }

                    // ------------------------------------------------
                    // VALID UNTIL
                    // ------------------------------------------------

                    if (
                        $member->valid_until &&
                        now()->gt(
                            $member->valid_until
                        )
                    ) {

                        throw new \Exception(
                            'Masa berlaku member telah berakhir.'
                        );
                    }
                }

                // ====================================================
                // CREATE ORDER
                // ====================================================

                $order = Order::create([

                    // ------------------------------------------------
                    // IDEMPOTENCY
                    // ------------------------------------------------

                    'client_order_id' =>
                        $request->client_order_id,

                    // ------------------------------------------------
                    // MEMBER
                    // ------------------------------------------------

                    'member_code' =>
                        $member?->code,

                    // ------------------------------------------------
                    // PAYMENT
                    // ------------------------------------------------

                    'payment_amount' =>
                        $request->payment_amount,

                    'sub_total' =>
                        $request->sub_total,

                    'tax' =>
                        $request->tax,

                    'discount' =>
                        $request->discount,

                    'discount_amount' =>
                        $request->discount_amount,

                    'service_charge' =>
                        $request->service_charge,

                    'total' =>
                        $request->total,

                    'payment_method' =>
                        $request->payment_method,

                    'total_item' =>
                        $request->total_item,

                    // ------------------------------------------------
                    // TABLE
                    // ------------------------------------------------

                    'table_number' =>
                        $request->table_number,

                    // ------------------------------------------------
                    // CUSTOMER
                    // ------------------------------------------------

                    'customer_name' =>
                        $request->customer_name,

                    // ------------------------------------------------
                    // STATUS
                    // ------------------------------------------------

                    'status' =>
                        $request->status,

                    // ------------------------------------------------
                    // CASHIER
                    // ------------------------------------------------

                    'id_kasir' =>
                        $request->id_kasir,

                    'nama_kasir' =>
                        $request->nama_kasir,

                    // ------------------------------------------------
                    // TRANSACTION TIME
                    // ------------------------------------------------

                    'transaction_time' =>
                        $request->transaction_time,
                ]);

                // ====================================================
                // ORDER ITEMS
                // ====================================================

                foreach (
                    $request->order_items
                    as $item
                ) {

                    // ------------------------------------------------
                    // PRODUCT LOCK
                    // ------------------------------------------------

                    $product = Product::lockForUpdate()
                        ->find(
                            $item['id_product']
                        );

                    if (!$product) {

                        throw new \Exception(
                            'Produk ID '
                            . $item['id_product']
                            . ' tidak ditemukan.'
                        );
                    }

                    // ------------------------------------------------
                    // QUANTITY
                    // ------------------------------------------------

                    $quantity =
                        (float) $item['quantity'];

                    if ($quantity <= 0) {

                        throw new \Exception(
                            "Quantity {$product->name} tidak valid."
                        );
                    }

                    // ------------------------------------------------
                    // STOCK
                    // ------------------------------------------------

                    if (
                        $product->stock <
                        $quantity
                    ) {

                        throw new \Exception(
                            "Stok {$product->name} tidak mencukupi. "
                            . "Stok tersedia: "
                            . $product->stock
                        );
                    }

                    // ------------------------------------------------
                    // CREATE ITEM
                    // ------------------------------------------------

                    OrderItem::create([

                        'order_id' =>
                            $order->id,

                        'product_id' =>
                            $product->id,

                        'product_name' =>
                            $product->name,

                        'quantity' =>
                            $quantity,

                        'price' =>
                            $item['price'],
                    ]);

                    // ------------------------------------------------
                    // DECREMENT STOCK
                    // ------------------------------------------------

                    $product->decrement(
                        'stock',
                        $quantity
                    );
                }

                // ====================================================
                // MEMBER STAMP
                // ====================================================
                //
                // ATURAN (2 SYARAT SEKALIGUS):
                //
                // 1. 1 KUNJUNGAN (HARI) = MAKSIMAL 1 STAMP.
                //    Belanja berapa kali pun dalam hari yang sama
                //    tetap cuma dapat 1 stamp.
                //
                // 2. NILAI ORDER MINIMAL Rp 22.000.
                //    Order di bawah itu TIDAK dapat stamp sama
                //    sekali, terlepas dari sudah/belum dapat stamp
                //    hari ini.
                //
                // ====================================================

                $stampAdded = false;
                $stampFull = false;

                if ($member) {

                    $orderTotal = (float) $order->total;

                    // ------------------------------------------------
                    // SYARAT #1: NILAI ORDER MINIMAL
                    // ------------------------------------------------

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

                    } else {

                        // ----------------------------------------------
                        // SYARAT #2: CEK APAKAH SUDAH DAPAT STAMP HARI INI
                        // ----------------------------------------------
                        //
                        // PENTING: dibandingkan berdasarkan transaction_time
                        // (waktu transaksi ASLI yang dikirim dari HP kasir),
                        // BUKAN jam server / waktu sync. Ini krusial buat
                        // kasir yang sempat offline — order yang terjadi
                        // tanggal 14 tapi baru ke-sync tanggal 15 (setelah
                        // internet nyala lagi) tetap dihitung sebagai
                        // kunjungan tanggal 14, bukan tanggal 15.
                        //
                        // ----------------------------------------------

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

                        if (!$alreadyStampedToday) {

                            // ==================================================
                            // MEMBER SUDAH PENUH
                            // ==================================================

                            if (
                                $member->stamp_count >=
                                $member->stamp_target
                            ) {

                                $stampFull = true;

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

                            } else {

                                // ==================================================
                                // TAMBAH 1 STAMP (UNTUK KUNJUNGAN HARI INI)
                                // ==================================================

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

                                $stampAdded = true;
                            }
                        } else {

                            // ==================================================
                            // SUDAH DAPAT STAMP HARI INI DARI ORDER LAIN
                            // ==================================================

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
                        }
                    }
                }

                // ====================================================
                // REFRESH MEMBER
                // ====================================================

                if ($member) {
                    $member->refresh();
                }

                // ====================================================
                // CEK MYSTERY BOX
                // ====================================================

                $mysteryBoxAvailable =
                    $member &&
                    $member->stamp_count >=
                    $member->stamp_target;

                // ====================================================
                // REFRESH
                // ====================================================

                $order->load('orderItems');

                if ($member) {
                    $member->refresh();
                }

                // ====================================================
                // RESPONSE
                // ====================================================

                return response()->json([
                    'status' => 'success',

                    'message' =>
                        'Order berhasil disimpan.',

                    'data' => [

                        'order' =>
                            $order,

                        'member' =>
                            $member
                                ? [
                                    'id' =>
                                        $member->id,

                                    'code' =>
                                        $member->code,

                                    'stamp_count' =>
                                        $member->stamp_count,

                                    'stamp_target' =>
                                        $member->stamp_target,

                                    'stamp_added' =>
                                        $stampAdded,

                                    'mystery_box_available' =>
                                        $member->hasMysteryReward(),
                                ]
                                : null,
                    ],
                ], 200);
            });

        } catch (QueryException $e) {

            // ========================================================
            // UNIQUE CLIENT ORDER ID
            // ========================================================
            //
            // Kondisi ini sangat penting.
            //
            // Misalnya:
            //
            // Request A ─┐
            // Request B ─┘
            //
            // keduanya masuk bersamaan.
            //
            // Database UNIQUE hanya mengizinkan satu.
            //
            // Request kedua akan masuk ke sini.
            //
            // ========================================================

            $duplicateOrder = Order::with('orderItems')
                ->where(
                    'client_order_id',
                    $clientOrderId
                )
                ->first();

            if ($duplicateOrder) {

                return response()->json([
                    'status' => 'exists',

                    'message' =>
                        'Order sudah pernah disimpan.',

                    'data' => [
                        'order' =>
                            $duplicateOrder,

                        'member' =>
                            $this->getMemberStampData(
                                $duplicateOrder->member_code
                            ),
                    ],
                ], 200);
            }

            // --------------------------------------------------------
            // BUKAN DUPLICATE
            // --------------------------------------------------------

            return response()->json([
                'status' => 'error',

                'message' =>
                    'Gagal menyimpan order.',

                'error' =>
                    $e->getMessage(),
            ], 500);

        } catch (\Throwable $e) {

            // ========================================================
            // GENERAL ERROR
            // ========================================================

            return response()->json([
                'status' => 'error',

                'message' =>
                    'Terjadi kesalahan saat menyimpan order.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }


    public function index(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        if ($start_date && $end_date) {

            $start = substr($start_date, 0, 10);
            $end = substr($end_date, 0, 10);

            $orders = Order::with('orderItems')->whereRaw("
                DATE(SUBSTRING_INDEX(transaction_time, 'T', 1)) BETWEEN ? AND ?
            ", [$start, $end])->get();
        } else {
            $orders = Order::all();
        }

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ], 200);
    }

    public function summary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Order::query();

        if ($startDate && $endDate) {

            $start = substr($startDate, 0, 10);
            $end = substr($endDate, 0, 10);

            $query->whereRaw(
                "DATE(SUBSTRING_INDEX(transaction_time, 'T', 1)) BETWEEN ? AND ?",
                [$start, $end]
            );
        }

        $totalRevenue = $query->sum('total');

        $totalDiscount = $query->sum('discount_amount');

        $totalTax = $query->sum('tax');

        $totalServiceCharge = $query->sum('service_charge');

        $totalSubtotal = $query->sum('sub_total');

        // Total penjualan mengikuti nilai total transaksi
        $total = $totalRevenue;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_revenue' => (int) $totalRevenue,
                'total_discount' => (float) $totalDiscount,
                'total_tax' => (int) $totalTax,
                'total_subtotal' => (int) $totalSubtotal,
                'total_service_charge' => (int) $totalServiceCharge,
                'total' => (int) $total,
            ]
        ], 200);
    }
}

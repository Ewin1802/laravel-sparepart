<?php

namespace App\Http\Controllers;

use App\Models\MemberBarcode;
use App\Models\MysteryBoxReward;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StampTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberPortalController extends Controller
{
    /**
     * Halaman "Kartu Member Saya" — QR code, progress stamp, riwayat
     * transaksi terakhir, dan tombol redeem Mystery Box. Bisa dibuka
     * ulang kapan aja selama login, gak cuma sekali pas baru daftar
     * kayak halaman sukses registrasi.
     *
     * Khusus untuk user dengan role 'user' (member biasa). Kalau
     * yang login adalah admin, lempar ke dashboard admin — halaman
     * ini gak relevan buat mereka.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->route('dashboard');
        }

        $memberBarcode = MemberBarcode::where('user_id', $user->id)->first();

        if (!$memberBarcode) {
            abort(404, 'Data member tidak ditemukan.');
        }

        // =========================================================
        // 5 TRANSAKSI TERAKHIR (buat preview di halaman utama)
        // =========================================================

        $recentOrders = Order::where('member_code', $memberBarcode->code)
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $mysteryReady = $memberBarcode->stamp_count >= $memberBarcode->stamp_target;

        $setting = Setting::first();

        return view('pages.member-portal.show', [
            'user'         => $user,
            'member'       => $memberBarcode,
            'setting'      => $setting,
            'recentOrders' => $recentOrders,
            'mysteryReady' => $mysteryReady,
        ]);
    }

    /**
     * Halaman riwayat transaksi LENGKAP (paginated) — link "Lihat
     * Semua" dari halaman utama portal mengarah ke sini.
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $memberBarcode = MemberBarcode::where('user_id', $user->id)->first();

        if (!$memberBarcode) {
            abort(404, 'Data member tidak ditemukan.');
        }

        $orders = Order::where('member_code', $memberBarcode->code)
            ->orderByDesc('id')
            ->paginate(10);

        $setting = Setting::first();

        return view('pages.member-portal.history', [
            'member'  => $memberBarcode,
            'orders'  => $orders,
            'setting' => $setting,
        ]);
    }

    /**
     * Redeem Mystery Box lewat web — logikanya disalin & disamakan
     * persis dengan Api\MemberStampController@redeem, supaya member
     * yang redeem lewat web dapat perlakuan yang identik dengan
     * yang redeem lewat aplikasi Android (produk random dari stok
     * yang tersedia, stamp dipotong stamp_target, dicatat di
     * StampTransaction).
     */
    public function redeem(Request $request)
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($user) {

            $member = MemberBarcode::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$member) {
                return ['error' => 'Data member tidak ditemukan.'];
            }

            if (!$member->isValid()) {
                return ['error' => 'Member tidak aktif atau barcode sudah tidak berlaku.'];
            }

            if ($member->stamp_count < $member->stamp_target) {
                return ['error' => 'Stamp belum mencukupi.'];
            }

            // =====================================================
            // CEK REWARD YANG MASIH AVAILABLE (belum sempat diambil)
            // =====================================================

            $existingReward = MysteryBoxReward::where('member_barcode_id', $member->id)
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            if ($existingReward) {
                $usedStamp = $member->stamp_target;

                $member->decrement('stamp_count', $usedStamp);
                $member->refresh();

                StampTransaction::create([
                    'member_barcode_id' => $member->id,
                    'order_id'          => null,
                    'type'              => 'redeem',
                    'amount'            => $usedStamp,
                    'note'              => 'Redeem Mystery Box #' . $existingReward->id . ' - ' . $existingReward->product_name,
                ]);

                $existingReward->update(['status' => 'redeemed']);

                return ['product_name' => $existingReward->product_name];
            }

            // =====================================================
            // PILIH PRODUK BONUS SECARA RANDOM
            // =====================================================

            $product = Product::where('status', 1)
                ->where('stock', '>', 0)
                ->whereIn('category_id', [1, 2]) // Bonus Mystery Box hanya dari kategori Es Kopi Series & Non Kopi Series
                ->inRandomOrder()
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return ['error' => 'Saat ini belum ada produk yang tersedia untuk Mystery Box.'];
            }

            $reward = MysteryBoxReward::create([
                'member_barcode_id' => $member->id,
                'product_id'        => $product->id,
                'product_name'      => $product->name,
                'product_price'     => $product->price ?? 0,
                'status'            => 'redeemed',
            ]);

            $product->decrement('stock', 1);

            $usedStamp = $member->stamp_target;
            $member->decrement('stamp_count', $usedStamp);
            $member->refresh();

            StampTransaction::create([
                'member_barcode_id' => $member->id,
                'order_id'          => null,
                'type'              => 'redeem',
                'amount'            => $usedStamp,
                'note'              => 'Redeem Mystery Box #' . $reward->id . ' - ' . $product->name,
            ]);

            return ['product_name' => $product->name];
        });

        if (isset($result['error'])) {
            return redirect()
                ->route('member.portal')
                ->with('error', $result['error']);
        }

        return redirect()
            ->route('member.portal')
            ->with('success', 'Selamat! Kamu dapat: ' . $result['product_name'] . '. Tunjukkan halaman ini ke kasir untuk mengambil hadiahmu.');
    }
}

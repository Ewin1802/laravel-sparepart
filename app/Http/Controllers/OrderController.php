<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\MemberBarcode;
use App\Models\Order;
use App\Models\OrderDeletion;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StampTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * ============================================================
     * SYARAT MINIMAL BELANJA UNTUK DAPAT STAMP
     * ============================================================
     *
     * Disamakan persis dengan Api\OrderController /
     * Api\MemberStampController.
     */
    private const MINIMUM_ORDER_FOR_STAMP = 50000;

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    |
    | Menampilkan laporan transaksi.
    |
    | Default:
    | - range = 30 hari
    | - end_date = hari ini
    | - start_date = 29 hari sebelum hari ini
    |
    */

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | RANGE
        |--------------------------------------------------------------------------
        */

        $range = (int) $request->input('range', 30);

        if ($range < 1) {
            $range = 30;
        }


        /*
        |--------------------------------------------------------------------------
        | DATE FILTER
        |--------------------------------------------------------------------------
        */

        $end_date = $request->input(
            'end_date',
            now()->toDateString()
        );

        $start_date = $request->input(
            'start_date',
            now()
                ->subDays($range - 1)
                ->toDateString()
        );


        /*
        |--------------------------------------------------------------------------
        | VALIDASI TANGGAL
        |--------------------------------------------------------------------------
        */

        try {

            $startCarbon = Carbon::parse($start_date);
            $endCarbon = Carbon::parse($end_date);

        } catch (\Throwable $e) {

            return redirect()
                ->route('orders.index')
                ->with(
                    'error',
                    'Format tanggal tidak valid.'
                );
        }


        if ($startCarbon->greaterThan($endCarbon)) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | FORMAT TRANSACTION TIME
        |--------------------------------------------------------------------------
        */

        $start = $startCarbon
            ->startOfDay()
            ->format('Y-m-d\TH:i:s');

        $end = $endCarbon
            ->endOfDay()
            ->format('Y-m-d\TH:i:s');


        /*
        |--------------------------------------------------------------------------
        | BASE ORDER QUERY
        |--------------------------------------------------------------------------
        */

        $query = Order::query()
            ->whereRaw(
                "STR_TO_DATE(
                    transaction_time,
                    '%Y-%m-%dT%H:%i:%s'
                ) BETWEEN ? AND ?",
                [
                    $start,
                    $end,
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | ORDER LIST
        |--------------------------------------------------------------------------
        */

        $orders = (clone $query)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | ORDER SUMMARY
        |--------------------------------------------------------------------------
        */

        // ================================================================
        // PENTING — SOAL "total_revenue" (Total Pendapatan):
        //
        // Dulu dihitung dari SUM(payment_amount), padahal payment_amount
        // adalah UANG TUNAI YANG DISERAHKAN KONSUMEN (termasuk uang yang
        // nanti dikembalikan sebagai kembalian). Contoh: tagihan Rp50.000
        // dibayar pakai Rp100.000 → payment_amount = 100.000, padahal
        // yang benar-benar jadi pendapatan cafe cuma Rp50.000.
        //
        // Sekarang dihitung dari SUM(total) — nilai TAGIHAN YANG SEHARUSNYA
        // DIBAYAR, yang sudah otomatis bersih dari diskon (karena kolom
        // `total` dihitung sebagai: sub_total - discount_amount + tax +
        // service_charge). Ini juga sekaligus memperbaiki laporan saat
        // diskon member diterapkan — sebelumnya seolah uang penuh (sebelum
        // diskon) ikut tercatat sebagai pemasukan.
        // ================================================================

        $totalRevenue = (clone $query)->sum('total');
        $totalTax = (clone $query)->sum('tax');

        $summary = [

            'total_revenue' => $totalRevenue,

            'total_discount' => (clone $query)
                ->sum('discount_amount'),

            'total_tax' => $totalTax,

            // Pendapatan bersih cafe SETELAH pajak dipisahkan — pajak
            // ini bukan hak cafe, jadi wajib disetorkan ke pemda dan
            // tidak dihitung sebagai pendapatan riil.
            'net_revenue' => $totalRevenue - $totalTax,

            'total_service_charge' => (clone $query)
                ->sum('service_charge'),

            'total_cash' => (clone $query)
                ->whereRaw(
                    'LOWER(payment_method) = ?',
                    ['cash']
                )
                ->sum('total'),

            'total_transfer' => (clone $query)
                ->whereRaw(
                    'LOWER(payment_method) = ?',
                    ['transfer']
                )
                ->sum('total'),

            'total_order' => (clone $query)
                ->count(),

            'total_item' => (clone $query)
                ->sum('total_item'),

            // Rata-rata dihitung dari `total` (tagihan bersih), bukan
            // dari `payment_amount` (uang tunai yang diserahkan).
            'average_order' => (clone $query)
                ->avg('total') ?? 0,
        ];


        /*
        |--------------------------------------------------------------------------
        | EXPENSE QUERY
        |--------------------------------------------------------------------------
        */

        $expenseQuery = Expense::query()
            ->whereBetween(
                'expense_date',
                [
                    $startCarbon->toDateString(),
                    $endCarbon->toDateString(),
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | TOTAL EXPENSE
        |--------------------------------------------------------------------------
        */

        $totalExpense = (clone $expenseQuery)
            ->sum('amount');


        /*
        |--------------------------------------------------------------------------
        | EXPENSE BY CATEGORY
        |--------------------------------------------------------------------------
        */

        $expenseByCategory = (clone $expenseQuery)
            ->selectRaw(
                'category, SUM(amount) as total'
            )
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | NET INCOME
        |--------------------------------------------------------------------------
        */

        $netIncome =
            ($summary['total_revenue'] ?? 0)
            -
            ($totalExpense ?? 0);


        /*
        |--------------------------------------------------------------------------
        | CHART DATA
        |--------------------------------------------------------------------------
        */

        $chartData = (clone $query)
            ->selectRaw("
                DATE(
                    STR_TO_DATE(
                        transaction_time,
                        '%Y-%m-%dT%H:%i:%s'
                    )
                ) AS trx_date,

                SUM(total) AS total
            ")
            ->groupBy('trx_date')
            ->orderBy('trx_date')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'pages.order_reports.index',
            compact(
                'orders',
                'summary',
                'chartData',
                'start_date',
                'end_date',
                'range',
                'totalExpense',
                'expenseByCategory',
                'netIncome'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SUMMARY API
    |--------------------------------------------------------------------------
    */

    public function summary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Order::query();

        if ($startDate && $endDate) {

            try {

                $start = Carbon::parse($startDate)
                    ->startOfDay();

                $end = Carbon::parse($endDate)
                    ->endOfDay();

                $query->whereBetween(
                    'created_at',
                    [
                        $start,
                        $end,
                    ]
                );

            } catch (\Throwable $e) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Format tanggal tidak valid.',
                ], 422);

            }
        }

        $totalRevenue = (clone $query)->sum('total');
        $totalDiscount = (clone $query)->sum('discount_amount');
        $totalTax = (clone $query)->sum('tax');
        $totalServiceCharge = (clone $query)->sum('service_charge');
        $totalSubtotal = (clone $query)->sum('sub_total');

        $total =
            $totalSubtotal
            - $totalDiscount
            - $totalTax
            + $totalServiceCharge;

        return response()->json([

            'status' => 'success',

            'data' => [
                'total_revenue' => $totalRevenue,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'total_subtotal' => $totalSubtotal,
                'total_service_charge' => $totalServiceCharge,
                'total' => $total,
            ],

        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    |
    | Mengambil detail satu transaksi (dipakai modal detail).
    |
    */

    public function show(int $id)
    {
        $order = Order::with([
            'orderItems.product'
        ])->findOrFail($id);

        $orderData = [
            'id' => $order->id,
            'payment_amount' => $order->payment_amount,
            'sub_total' => $order->sub_total,
            'tax' => $order->tax,
            'discount' => $order->discount,
            'discount_amount' => $order->discount_amount,
            'service_charge' => $order->service_charge,
            'total' => $order->total,
            'payment_method' => $order->payment_method,
            'total_item' => $order->total_item,
            'table_number' => $order->table_number,
            'customer_name' => $order->customer_name,
            'status' => $order->status,
            'id_kasir' => $order->id_kasir,
            'nama_kasir' => $order->nama_kasir,
            'transaction_time' => $order->transaction_time,
            'created_at' => $order->created_at,
        ];

        $items = $order->orderItems
            ->map(function ($item) {

                $productName =
                    $item->product_name
                    ?? optional($item->product)->name
                    ?? 'Produk';

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $productName,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'total' => (float) $item->quantity * (float) $item->price,
                ];

            })
            ->values();

        return response()->json([
            'status' => 'success',
            'order' => $orderData,
            'items' => $items,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DESTROY — HAPUS ORDER (misal: kasir double-input)
    |--------------------------------------------------------------------------
    |
    | DELETE /orders/{id}
    |
    | Dipakai kalau ada transaksi ganda/keliru yang perlu dihapus total.
    | OTOMATIS MENGOREKSI, sama seperti update():
    |
    | 1. Stok produk dikembalikan untuk setiap item order ini.
    | 2. Stamp member ditarik balik kalau order ini pernah kasih stamp
    |    (supaya member tidak "untung" stamp dari order yang dibatalkan).
    | 3. Semua order_items & stamp_transactions milik order ini ikut
    |    dihapus, baru order-nya sendiri dihapus.
    |
    | CATATAN SOAL KASIR: penghapusan ini hanya berlaku di server/admin
    | panel. Aplikasi kasir (Flutter) mengirim order sekali (fire-and-
    | forget) dan tidak menarik ulang daftar order dari server, jadi
    | riwayat order tersebut kemungkinan MASIH tampil di HP kasir yang
    | membuatnya — hanya sudah tidak lagi terhitung di laporan pusat.
    |
    */

    public function destroy(int $id)
    {
        try {

            DB::transaction(function () use ($id) {

                $order = Order::with('orderItems')
                    ->lockForUpdate()
                    ->findOrFail($id);

                // ============================================================
                // STEP 1: BALIKIN STOK
                // ============================================================

                foreach ($order->orderItems as $item) {

                    if (!$item->product_id) {
                        continue;
                    }

                    $product = Product::lockForUpdate()->find($item->product_id);

                    if ($product) {
                        $product->increment('stock', $item->quantity);
                    }
                }

                // ============================================================
                // STEP 2: TARIK STAMP (KALAU ORDER INI PERNAH KASIH STAMP)
                // ============================================================

                $stampTransaction = StampTransaction::where('order_id', $order->id)
                    ->where('type', 'earn')
                    ->where('amount', '>', 0)
                    ->first();

                if ($stampTransaction) {

                    $member = MemberBarcode::lockForUpdate()
                        ->find($stampTransaction->member_barcode_id);

                    if ($member) {

                        $member->stamp_count = max(
                            0,
                            $member->stamp_count - $stampTransaction->amount
                        );

                        $member->save();
                    }
                }

                // Hapus SEMUA jejak stamp_transactions punya order ini
                // (termasuk yang amount=0, biar bersih).

                StampTransaction::where('order_id', $order->id)->delete();

                // ============================================================
                // STEP 3: CATAT KE LOG PENGHAPUSAN (UNTUK SYNC KE KASIR)
                // ============================================================
                //
                // WAJIB dicatat SEBELUM order-nya dihapus — begitu
                // $order->delete() jalan, tidak ada lagi cara untuk tahu
                // order ini pernah ada. Endpoint Api\OrderSyncController
                // nanti baca tabel ini untuk kasih tahu kasir order mana
                // yang perlu dibuang dari SQLite lokalnya.
                //
                // ============================================================

                OrderDeletion::create([
                    'order_id' => $order->id,
                    'client_order_id' => $order->client_order_id,
                    'deleted_at' => now(),
                ]);

                // ============================================================
                // STEP 4: HAPUS ITEM, LALU ORDER-NYA SENDIRI
                // ============================================================

                $order->orderItems()->delete();

                $order->delete();
            });

        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus order: ' . $e->getMessage());
        }

        return redirect()
            ->route('orders.index')
            ->with('success', 'Order berhasil dihapus. Stok dan stamp member sudah otomatis dikoreksi.');
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT — TAMPILKAN FORM EDIT ORDER
    |--------------------------------------------------------------------------
    |
    | GET /orders/{id}/edit
    |
    */

    public function edit(int $id)
    {
        $order = Order::with('orderItems.product')->findOrFail($id);

        $products = Product::orderBy('name')->get();

        return view(
            'pages.order_reports.edit',
            compact('order', 'products')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE — SIMPAN HASIL EDIT ORDER
    |--------------------------------------------------------------------------
    |
    | PUT /orders/{id}
    |
    | Full edit: item, qty, harga, metode bayar, member, meja,
    | customer, diskon, pajak, service charge, waktu transaksi.
    |
    | OTOMATIS MENGOREKSI:
    | 1. Stok produk (kembalikan stok lama, potong stok baru)
    | 2. Stamp member (tarik stamp lama kalau ada, evaluasi ulang
    |    dari nol apakah order hasil edit ini berhak dapat stamp
    |    baru — pakai aturan yang SAMA PERSIS dengan
    |    Api\OrderController@saveOrder: 1 kunjungan/hari + minimal
    |    Rp 22.000)
    |
    */

    public function update(Request $request, int $id)
    {
        $request->validate([

            'member_code' => ['nullable', 'string', 'max:100'],

            'payment_method' => ['required', 'string'],

            'table_number' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'transaction_time' => ['required', 'string'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax' => ['required', 'numeric', 'min:0'],
            'service_charge' => ['required', 'numeric', 'min:0'],

            'payment_amount' => ['nullable', 'numeric', 'min:0'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],

            'items.*.quantity' => ['required', 'numeric', 'gt:0'],

            'items.*.price' => ['required', 'numeric', 'gte:0'],
        ]);

        try {

            DB::transaction(function () use ($request, $id) {

                $order = Order::with('orderItems')
                    ->lockForUpdate()
                    ->findOrFail($id);

                // ============================================================
                // STEP 1: BALIKIN STOK LAMA
                // ============================================================
                //
                // Setiap item order LAMA, stoknya dikembalikan dulu ke
                // produk masing-masing, sebelum item baru diproses.
                //
                // ============================================================

                foreach ($order->orderItems as $oldItem) {

                    if (!$oldItem->product_id) {
                        continue;
                    }

                    $product = Product::lockForUpdate()->find($oldItem->product_id);

                    if ($product) {
                        $product->increment('stock', $oldItem->quantity);
                    }
                }

                // ============================================================
                // STEP 2: TARIK STAMP LAMA (KALAU ORDER INI PERNAH KASIH STAMP)
                // ============================================================

                $oldStampTransaction = StampTransaction::where('order_id', $order->id)
                    ->where('type', 'earn')
                    ->where('amount', '>', 0)
                    ->first();

                if ($oldStampTransaction) {

                    $oldMember = MemberBarcode::lockForUpdate()
                        ->find($oldStampTransaction->member_barcode_id);

                    if ($oldMember) {

                        $oldMember->stamp_count = max(
                            0,
                            $oldMember->stamp_count - $oldStampTransaction->amount
                        );

                        $oldMember->save();
                    }
                }

                // Hapus SEMUA jejak stamp_transactions punya order ini
                // (termasuk yang amount=0, biar bersih dan gak ganggu
                // perhitungan "sudah dapat stamp hari ini" di STEP 6
                // nanti — kalau gak dihapus, order ini bisa "nabrak
                // dirinya sendiri" pas dicek ulang).

                StampTransaction::where('order_id', $order->id)->delete();

                // ============================================================
                // STEP 3: HAPUS ITEM LAMA
                // ============================================================

                $order->orderItems()->delete();

                // ============================================================
                // STEP 4: INSERT ITEM BARU + POTONG STOK BARU
                // ============================================================

                $subTotal = 0;
                $totalItem = 0;

                foreach ($request->items as $itemInput) {

                    $product = Product::lockForUpdate()->find($itemInput['product_id']);

                    if (!$product) {
                        throw new \Exception(
                            'Produk ID ' . $itemInput['product_id'] . ' tidak ditemukan.'
                        );
                    }

                    $quantity = (float) $itemInput['quantity'];

                    if ($product->stock < $quantity) {
                        throw new \Exception(
                            "Stok {$product->name} tidak mencukupi untuk perubahan ini. "
                            . "Stok tersedia: {$product->stock}"
                        );
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $quantity,
                        'price' => $itemInput['price'],
                    ]);

                    $product->decrement('stock', $quantity);

                    $subTotal += $quantity * (float) $itemInput['price'];
                    $totalItem += $quantity;
                }

                // ============================================================
                // STEP 5: HITUNG ULANG TOTAL & SIMPAN DATA ORDER
                // ============================================================

                $tax = (float) $request->tax;
                $discountAmount = (float) $request->discount_amount;
                $serviceCharge = (float) $request->service_charge;

                $total = $subTotal - $discountAmount + $tax + $serviceCharge;

                $paymentAmount = $request->filled('payment_amount')
                    ? (float) $request->payment_amount
                    : $total;

                $order->update([
                    'member_code' => $request->member_code ?: null,
                    'payment_method' => $request->payment_method,
                    'table_number' => $request->table_number,
                    'customer_name' => $request->customer_name,
                    'transaction_time' => $request->transaction_time,
                    'sub_total' => $subTotal,
                    'tax' => $tax,
                    'discount_amount' => $discountAmount,
                    'service_charge' => $serviceCharge,
                    'total' => $total,
                    'payment_amount' => $paymentAmount,
                    'total_item' => $totalItem,
                ]);

                $order->refresh();

                // ============================================================
                // STEP 6: EVALUASI ULANG STAMP (dari nol, pakai data BARU)
                // ============================================================
                //
                // Aturan disamakan PERSIS dengan Api\OrderController@saveOrder:
                // minimal Rp 22.000 + belum dapat stamp di hari yang sama.
                //
                // ============================================================

                if ($order->member_code) {

                    $member = MemberBarcode::where('code', $order->member_code)
                        ->lockForUpdate()
                        ->first();

                    if ($member && $member->isValid()) {

                        if ($total >= self::MINIMUM_ORDER_FOR_STAMP) {

                            $referenceDate = Carbon::parse(
                                $order->transaction_time
                            )->toDateString();

                            $alreadyStampedToday = StampTransaction::query()
                                ->join('orders', 'orders.id', '=', 'stamp_transactions.order_id')
                                ->where('stamp_transactions.member_barcode_id', $member->id)
                                ->where('stamp_transactions.type', 'earn')
                                ->where('stamp_transactions.amount', '>', 0)
                                ->whereDate('orders.transaction_time', $referenceDate)
                                ->exists();

                            if (!$alreadyStampedToday) {

                                if ($member->stamp_count < $member->stamp_target) {

                                    $member->stamp_count = min(
                                        $member->stamp_count + 1,
                                        $member->stamp_target
                                    );

                                    $member->save();

                                    StampTransaction::create([
                                        'member_barcode_id' => $member->id,
                                        'order_id' => $order->id,
                                        'type' => 'earn',
                                        'amount' => 1,
                                        'note' => 'Stamp dari koreksi/edit order #' . $order->id . ' oleh admin.',
                                    ]);

                                } else {

                                    StampTransaction::create([
                                        'member_barcode_id' => $member->id,
                                        'order_id' => $order->id,
                                        'type' => 'earn',
                                        'amount' => 0,
                                        'note' => 'Order #' . $order->id . ' (hasil edit) - stamp sudah penuh.',
                                    ]);
                                }

                            } else {

                                StampTransaction::create([
                                    'member_barcode_id' => $member->id,
                                    'order_id' => $order->id,
                                    'type' => 'earn',
                                    'amount' => 0,
                                    'note' => 'Order #' . $order->id . ' (hasil edit) - stamp hari ini sudah didapat dari transaksi lain.',
                                ]);
                            }

                        } else {

                            StampTransaction::create([
                                'member_barcode_id' => $member->id,
                                'order_id' => $order->id,
                                'type' => 'earn',
                                'amount' => 0,
                                'note' => 'Order #' . $order->id . ' (hasil edit) - total belanja di bawah syarat minimal stamp.',
                            ]);
                        }
                    }
                }
            });

        } catch (\Throwable $e) {

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan perubahan: ' . $e->getMessage());
        }

        return redirect()
            ->route('orders.index')
            ->with('success', 'Order berhasil diperbarui. Stok dan stamp member sudah otomatis dikoreksi.');
    }
}

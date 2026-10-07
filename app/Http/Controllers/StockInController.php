<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockIn;
use App\Support\SupplierPrices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockInController extends Controller
{
    public function index(Request $request)
    {
        $start_date = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end_date = $request->input('end_date', now()->toDateString());
        $supplier_id = $request->input('supplier_id');
        $payment = in_array($request->input('payment'), ['paid', 'unpaid'], true) ? $request->input('payment') : null;
        $today = now()->toDateString();

        $bySupplier = fn ($q) => $q->when($supplier_id, fn ($q) => $q->where('stock_ins.supplier_id', $supplier_id));

        $base = $bySupplier(DB::table('stock_ins'))
            ->whereDate('stock_ins.received_at', '>=', $start_date)
            ->whereDate('stock_ins.received_at', '<=', $end_date)
            ->when($payment, fn ($q) => $q->where('stock_ins.payment_status', $payment));

        $summary = (clone $base)
            ->selectRaw('COUNT(*) as notes, COALESCE(SUM(total), 0) as total, COUNT(DISTINCT supplier_id) as suppliers')
            ->first();

        // UANG KELUAR = nota yang DIBAYAR pada periode ini (dilihat dari tanggal bayar,
        // bukan tanggal barang datang) → nota tempo baru dihitung saat dilunasi.
        $summary->cash_out = (float) $bySupplier(DB::table('stock_ins'))
            ->where('payment_status', 'paid')
            ->whereDate('paid_at', '>=', $start_date)
            ->whereDate('paid_at', '<=', $end_date)
            ->sum('total');

        // HUTANG = semua nota yang belum lunas, kapan pun barangnya datang
        $debt = $bySupplier(DB::table('stock_ins'))
            ->where('payment_status', 'unpaid')
            ->selectRaw(
                'COUNT(*) as notes, COALESCE(SUM(total), 0) as total, '
                . 'COALESCE(SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? THEN total ELSE 0 END), 0) as overdue_total, '
                . 'COALESCE(SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END), 0) as overdue_notes',
                [$today, $today]
            )
            ->first();

        $stockIns = (clone $base)
            ->join('suppliers', 'suppliers.id', '=', 'stock_ins.supplier_id')
            ->leftJoin('users', 'users.id', '=', 'stock_ins.user_id')
            ->select(
                'stock_ins.*',
                'suppliers.name as supplier_name',
                'users.name as user_name',
                DB::raw('(SELECT COUNT(*) FROM stock_in_items WHERE stock_in_items.stock_in_id = stock_ins.id) as items_count')
            )
            ->orderByDesc('stock_ins.received_at')
            ->orderByDesc('stock_ins.id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = DB::table('suppliers')->orderBy('name')->get(['id', 'name']);

        return view('pages.stock-ins.index', compact(
            'stockIns', 'suppliers', 'summary', 'debt', 'start_date', 'end_date', 'supplier_id', 'payment'
        ));
    }

    public function create()
    {
        return view('pages.stock-ins.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $stockIn = StockIn::create([
                'supplier_id' => $data['supplier_id'],
                'user_id' => Auth::id(),
                'invoice_number' => $data['invoice_number'] ?? null,
                'received_at' => $data['received_at'],
                'notes' => $data['notes'] ?? null,
                'total' => 0,
            ] + $this->paymentFields($data));

            $this->applyItems($stockIn, $data['items']);

            return $stockIn;
        });

        return redirect()
            ->route('stock-ins.index')
            ->with('success', 'Stok masuk tersimpan. Stok produk sudah bertambah.');
    }

    public function show(Request $request, $id)
    {
        /** @var StockIn $stockIn */
        $stockIn = StockIn::with(['supplier', 'user', 'items.product'])->findOrFail($id);

        // Dipanggil dari modal di halaman daftar (fetch) → kirim JSON saja
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'stock_in' => [
                    'id' => $stockIn->id,
                    'title' => $stockIn->invoice_number ?: 'Nota #' . $stockIn->id,
                    'supplier' => $stockIn->supplier->name ?? '-',
                    'received_at' => $stockIn->received_at->translatedFormat('l, d F Y'),
                    'invoice_number' => $stockIn->invoice_number,
                    'user' => $stockIn->user->name ?? '-',
                    'notes' => $stockIn->notes,
                    'total' => (float) $stockIn->total,
                    'is_paid' => $stockIn->isPaid(),
                    'paid_at' => $stockIn->paid_at?->translatedFormat('d M Y'),
                    'due_date' => $stockIn->due_date?->translatedFormat('d M Y'),
                    'is_overdue' => ! $stockIn->isPaid() && $stockIn->due_date && $stockIn->due_date->lt(today()),
                ],
                'items' => $stockIn->items->map(function ($item) {
                    $product = $item->product;

                    return [
                        'name' => $product->name ?? 'Produk dihapus',
                        'unit' => $product->base_unit ?? 'PCS',
                        'quantity' => (float) $item->quantity,
                        'cost_price' => (float) $item->cost_price,
                        'subtotal' => round($item->quantity * $item->cost_price, 2),
                        'sell_price' => $product ? (float) $product->price : null,
                    ];
                })->values(),
            ]);
        }

        return view('pages.stock-ins.show', compact('stockIn'));
    }

    public function edit($id)
    {
        /** @var StockIn $stockIn */
        $stockIn = StockIn::with('items')->findOrFail($id);

        return view('pages.stock-ins.edit', $this->formData() + compact('stockIn'));
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $id) {
            /** @var StockIn $stockIn */
            $stockIn = StockIn::with('items')->lockForUpdate()->findOrFail($id);

            // 1) batalkan isi lama (stok dikembalikan) → 2) terapkan isi baru
            $affected = $this->revertItems($stockIn);

            $stockIn->update([
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'received_at' => $data['received_at'],
                'notes' => $data['notes'] ?? null,
            ] + $this->paymentFields($data, $stockIn));

            $this->applyItems($stockIn, $data['items']);
            $this->refreshCostPrice($affected);
        });

        return redirect()
            ->route('stock-ins.index')
            ->with('success', 'Stok masuk diperbarui. Stok produk sudah disesuaikan.');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            /** @var StockIn $stockIn */
            $stockIn = StockIn::with('items')->lockForUpdate()->findOrFail($id);

            $affected = $this->revertItems($stockIn);
            $stockIn->delete();

            $this->refreshCostPrice($affected);
        });

        return redirect()
            ->route('stock-ins.index')
            ->with('success', 'Stok masuk dihapus. Stok produk sudah dikurangi kembali.');
    }

    /** Tandai nota tempo sebagai LUNAS (uang keluar pada tanggal bayar) */
    public function pay(Request $request, $id)
    {
        $data = $request->validate([
            'paid_at' => 'nullable|date|before_or_equal:today',
        ]);

        /** @var StockIn $stockIn */
        $stockIn = StockIn::findOrFail($id);

        if (! $stockIn->isPaid()) {
            $stockIn->update([
                'payment_status' => 'paid',
                'paid_at' => $data['paid_at'] ?? now()->toDateString(),
            ]);
        }

        return back()->with('success', 'Nota ditandai lunas.');
    }

    // ============================================================
    // HELPER
    // ============================================================

    /**
     * Kolom pembayaran dari isian form.
     * - Lunas : paid_at = tanggal bayar lama (kalau sudah pernah lunas) atau tanggal terima.
     * - Tempo : paid_at dikosongkan, due_date diisi.
     */
    private function paymentFields(array $data, ?StockIn $existing = null): array
    {
        if ($data['payment_status'] === 'paid') {
            return [
                'payment_status' => 'paid',
                'due_date' => null,
                'paid_at' => $existing?->isPaid() && $existing->paid_at
                    ? $existing->paid_at->toDateString()
                    : $data['received_at'],
            ];
        }

        return [
            'payment_status' => 'unpaid',
            'due_date' => $data['due_date'] ?? null,
            'paid_at' => null,
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'received_at' => 'required|date|before_or_equal:today',
            'invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'payment_status' => 'required|in:paid,unpaid',
            'due_date' => 'nullable|date|after_or_equal:received_at',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|distinct|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.cost_price' => 'required|numeric|min:0',
        ], [
            'items.required' => 'Tambahkan minimal satu barang.',
            'items.*.product_id.distinct' => 'Ada barang yang dimasukkan dua kali. Gabungkan jumlahnya.',
            'items.*.quantity.gt' => 'Jumlah barang harus lebih dari 0.',
            'received_at.before_or_equal' => 'Tanggal terima tidak boleh di masa depan.',
            'due_date.after_or_equal' => 'Jatuh tempo tidak boleh sebelum tanggal terima.',
        ]);
    }

    /** Data untuk form tambah / edit */
    private function formData(): array
    {
        $suppliers = DB::table('suppliers')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $products = DB::table('products')
            ->orderBy('name')
            ->get(['id', 'name', 'base_unit', 'stock', 'price', 'cost_price']);

        // harga terakhir tiap supplier per produk → isi otomatis & peringatan "ada yang lebih murah"
        $priceMap = SupplierPrices::compare()->map(fn ($p) => $p->suppliers->map(fn ($s) => [
            'id' => $s->supplier_id,
            'name' => $s->supplier_name,
            'price' => $s->last_price,
        ])->all());

        return compact('suppliers', 'products', 'priceMap');
    }

    /** Simpan item, tambah stok, perbarui harga beli terakhir & total nota */
    private function applyItems(StockIn $stockIn, array $items): void
    {
        $total = 0;
        $now = now();
        $rows = [];

        foreach ($items as $item) {
            $qty = round((float) $item['quantity'], 2);
            $cost = round((float) $item['cost_price'], 2);

            $rows[] = [
                'stock_in_id' => $stockIn->id,
                'product_id' => $item['product_id'],
                'quantity' => $qty,
                'cost_price' => $cost,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            Product::whereKey($item['product_id'])->lockForUpdate()->first()?->increment('stock', $qty);

            $total += $qty * $cost;
        }

        DB::table('stock_in_items')->insert($rows);
        $stockIn->update(['total' => round($total, 2)]);

        $this->refreshCostPrice(array_column($rows, 'product_id'));
    }

    /** Kembalikan stok dari item lama lalu hapus itemnya. Mengembalikan id produk yang terdampak. */
    private function revertItems(StockIn $stockIn): array
    {
        $ids = [];

        foreach ($stockIn->items as $item) {
            $product = Product::whereKey($item->product_id)->lockForUpdate()->first();

            if ($product) {
                // kalau sebagian sudah terjual, stok tidak dibuat minus
                $product->stock = max(0, (float) $product->stock - (float) $item->quantity);
                $product->save();
            }

            $ids[] = $item->product_id;
        }

        $stockIn->items()->delete();

        return $ids;
    }

    /** cost_price produk = harga beli pada stok masuk paling akhir (null kalau belum pernah) */
    private function refreshCostPrice(array $productIds): void
    {
        foreach (array_unique($productIds) as $productId) {
            $latest = DB::table('stock_in_items as i')
                ->join('stock_ins as s', 's.id', '=', 'i.stock_in_id')
                ->where('i.product_id', $productId)
                ->orderByDesc('s.received_at')
                ->orderByDesc('i.id')
                ->value('i.cost_price');

            DB::table('products')->where('id', $productId)->update(['cost_price' => $latest]);
        }
    }
}

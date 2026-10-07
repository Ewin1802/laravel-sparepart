<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat stok masuk CONTOH — disesuaikan dengan CategorySeeder,
 * ProductSeeder, dan OrderSeeder (September – sekarang).
 *
 * Urutan di DatabaseSeeder:
 *   CategorySeeder → ProductSeeder → SupplierSeeder → OrderSeeder → StockInSeeder
 *
 * Yang dibuat masuk akal dengan data lain:
 * - Supplier memasok kategori/merek yang sesuai (dealer CAT hanya part CAT, dst.).
 * - Jumlah yang dibeli tiap produk = STOK SEKARANG + YANG SUDAH TERJUAL (order_items),
 *   jadi angkanya klop: total stok masuk − total terjual = stok di ProductSeeder.
 * - Kiriman pertama tanggal 1 September (stok awal), sisanya kiriman mingguan.
 * - Tiap supplier punya tingkat harga sendiri, harga naik-turun tipis antar kiriman.
 * - products.cost_price diisi harga beli terakhir.
 * - Supplier bertempo (14 / 30 hari): nota baru masih hutang, nota lama sudah lunas.
 *
 * Stok produk TIDAK diubah. Hasil acak tetap (seed 2026).
 * Kalau tabel stock_ins sudah ada isinya, seeder ini dilewati (tidak dobel).
 */
class StockInSeeder extends Seeder
{
    /**
     * Siapa memasok apa. Cocokkan dengan nama di SupplierSeeder.
     *   level      : harga beli ≈ harga jual × level (makin kecil makin murah)
     *   categories : kategori yang dijual ('*' = semua)
     *   brands     : merek khusus (dicari di deskripsi produk: "Merek: ...")
     *   weekday    : hari kiriman rutin (1 = Senin … 6 = Sabtu)
     *   term       : tempo pembayaran dalam hari (tanpa ini = selalu bayar lunas)
     */
    private const SUPPLIERS = [
        'PT Mitra Alat Berat Sulut' => [
            'level' => 0.780, 'weekday' => 2, 'term' => 30,
            'categories' => [],
            'brands' => ['CAT'],
        ],
        'PT Karya Traktor Nusantara' => [
            'level' => 0.770, 'weekday' => 3, 'term' => 30,
            'categories' => [],
            'brands' => ['Komatsu'],
        ],
        'CV Sumber Alat Berat' => [
            'level' => 0.715, 'weekday' => 4,
            'categories' => ['Filter', 'Bucket & Kuku', 'Undercarriage', 'Mesin'],
            'brands' => ['ITR'],
        ],
        'UD Maju Jaya Hidrolik' => [
            'level' => 0.730, 'weekday' => 1, 'term' => 14,
            'categories' => ['Hidrolik', 'Seal & O-Ring', 'Oli & Pelumas'],
            'brands' => ['Gates', 'NOK'],
        ],
        'Toko Sinar Abadi Teknik' => [
            'level' => 0.790, 'weekday' => 6,
            'categories' => ['*'],
            'brands' => [],
        ],
        'Online / Marketplace' => [
            'level' => 0.750, 'weekday' => 5,
            'categories' => ['Kelistrikan & Aki', 'Filter'],
            'brands' => ['Donaldson', 'GS Astra'],
        ],
    ];

    public function run(): void
    {
        if (DB::table('stock_ins')->exists()) {
            $this->command?->warn('StockInSeeder dilewati: tabel stock_ins sudah ada isinya.');
            return;
        }

        mt_srand(2026);

        $supplierIds = DB::table('suppliers')->pluck('id', 'name')->all();
        $suppliers = array_intersect_key(self::SUPPLIERS, $supplierIds);

        $products = DB::table('products')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->orderBy('products.id')
            ->get(['products.id', 'products.name', 'products.description', 'products.price', 'products.stock', 'categories.name as category']);

        if ($products->isEmpty() || count($suppliers) < 2) {
            $this->command?->warn('StockInSeeder dilewati: jalankan ProductSeeder dan SupplierSeeder dulu.');
            return;
        }

        $sold = DB::table('order_items')
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $userId = DB::table('users')->orderBy('id')->value('id');
        $now = Carbon::now();
        $start = Carbon::create($now->month >= 9 ? $now->year : $now->year - 1, 9, 1)->startOfDay();

        // ---------- 1) jadwal kiriman tiap supplier ----------
        $schedule = [];
        foreach ($suppliers as $name => $cfg) {
            $dates = [];
            for ($day = $start->copy()->addDay(); $day->lte($now); $day->addDay()) {
                if ($day->dayOfWeekIso === $cfg['weekday']) {
                    $dates[] = $day->toDateString();
                }
            }
            $schedule[$name] = $dates;
        }

        // ---------- 2) pecah kebutuhan tiap produk jadi beberapa kiriman ----------
        $batches = []; // "tanggal|supplier" => [ [product_id, qty, cost], ... ]

        foreach ($products as $product) {
            $names = $this->suppliersFor($product, $suppliers);
            if (empty($names)) {
                continue;
            }

            // harga dasar tiap supplier untuk produk ini (selisih khas per produk)
            $base = [];
            foreach ($names as $name) {
                $factor = $suppliers[$name]['level'] + mt_rand(-25, 35) / 1000;
                $base[$name] = $this->roundPrice((float) $product->price * $factor);
            }

            $need = (float) $product->stock + (float) ($sold[$product->id] ?? 0);
            if ($need <= 0) {
                continue;
            }

            // stok awal (1 Sept) ± 45% kebutuhan, sisanya dibagi ke 1–4 kiriman
            $restock = $need >= 40 ? 4 : ($need >= 20 ? 3 : ($need >= 8 ? 2 : 1));
            $opening = max(1, (int) round($need * 0.45));
            $parts = $this->split($need - $opening, $restock);

            // stok awal dari supplier pertama (pemasok utama)
            $main = $names[0];
            $batches[$start->toDateString() . '|' . $main][] = [$product->id, $opening, $base[$main]];

            foreach ($parts as $i => $qty) {
                if ($qty <= 0) {
                    continue;
                }

                // bergantian antar supplier supaya harganya bisa dibandingkan
                $name = $names[($i + 1) % count($names)];
                $dates = $schedule[$name];
                if (empty($dates)) {
                    $name = $main;
                    $date = $start->toDateString();
                } else {
                    // kiriman ke-i jatuh di bagian ke-i dari periode
                    $slot = (int) floor(($i + mt_rand(20, 95) / 100) * count($dates) / count($parts));
                    $date = $dates[min($slot, count($dates) - 1)];
                }

                $cost = $this->roundPrice($base[$name] * (1 + mt_rand(-20, 25) / 1000));
                $batches[$date . '|' . $name][] = [$product->id, $qty, $cost];
            }
        }

        ksort($batches);

        // ---------- 3) simpan: satu nota per supplier per tanggal ----------
        $notes = 0;
        $lines = 0;

        $today = Carbon::today();

        DB::transaction(function () use ($batches, $suppliers, $supplierIds, $userId, $today, &$notes, &$lines) {
            foreach ($batches as $key => $items) {
                [$date, $name] = explode('|', $key, 2);

                // produk yang sama di nota yang sama digabung
                $merged = [];
                foreach ($items as [$productId, $qty, $cost]) {
                    if (isset($merged[$productId])) {
                        $merged[$productId][0] += $qty;
                    } else {
                        $merged[$productId] = [$qty, $cost];
                    }
                }

                $stamp = Carbon::parse($date)->setTime(mt_rand(9, 16), mt_rand(0, 59))->format('Y-m-d H:i:s');

                // pembayaran: supplier bertempo → nota baru masih hutang, nota lama sudah dilunasi
                $term = $suppliers[$name]['term'] ?? null;
                $payment = ['payment_status' => 'paid', 'due_date' => null, 'paid_at' => $date];

                if ($term) {
                    $due = Carbon::parse($date)->addDays($term);
                    $recentlyDue = $due->lt($today) && $due->diffInDays($today) <= 10;

                    if ($due->gte($today) || ($recentlyDue && mt_rand(1, 100) <= 50)) {
                        // belum jatuh tempo, atau baru lewat dan belum sempat dibayar
                        $payment = ['payment_status' => 'unpaid', 'due_date' => $due->toDateString(), 'paid_at' => null];
                    } else {
                        // dilunasi beberapa hari sebelum jatuh tempo
                        $payment['paid_at'] = $due->copy()->subDays(mt_rand(0, 3))->toDateString();
                    }
                }

                $stockInId = DB::table('stock_ins')->insertGetId($payment + [
                    'supplier_id' => $supplierIds[$name],
                    'user_id' => $userId,
                    'invoice_number' => 'INV/' . Carbon::parse($date)->format('ymd') . '/' . str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'received_at' => $date,
                    'notes' => Carbon::parse($date)->day === 1 && Carbon::parse($date)->month === 9 ? 'Stok awal.' : null,
                    'total' => 0,
                    'created_at' => $stamp,
                    'updated_at' => $stamp,
                ]);

                $rows = [];
                $total = 0;

                foreach ($merged as $productId => [$qty, $cost]) {
                    $rows[] = [
                        'stock_in_id' => $stockInId,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'cost_price' => $cost,
                        'created_at' => $stamp,
                        'updated_at' => $stamp,
                    ];
                    $total += $qty * $cost;
                }

                DB::table('stock_in_items')->insert($rows);
                DB::table('stock_ins')->where('id', $stockInId)->update(['total' => $total]);

                $notes++;
                $lines += count($rows);
            }

            // harga beli terakhir tiap produk
            $latest = DB::table('stock_in_items as i')
                ->join('stock_ins as s', 's.id', '=', 'i.stock_in_id')
                ->orderBy('s.received_at')
                ->orderBy('i.id')
                ->get(['i.product_id', 'i.cost_price'])
                ->keyBy('product_id'); // yang paling akhir menimpa yang lama

            foreach ($latest as $productId => $row) {
                DB::table('products')->where('id', $productId)->update(['cost_price' => $row->cost_price]);
            }
        });

        $this->command?->info("StockInSeeder: {$notes} nota, {$lines} baris barang dibuat.");
    }

    /**
     * Supplier yang cocok untuk produk ini (maksimal 3), pemasok utama di urutan pertama.
     * Cocok = menjual kategorinya, atau memegang mereknya ("Merek: ..." di deskripsi).
     */
    private function suppliersFor(object $product, array $suppliers): array
    {
        $brandMatch = [];
        $categoryMatch = [];
        $general = [];

        foreach ($suppliers as $name => $cfg) {
            $hasBrand = false;
            foreach ($cfg['brands'] as $brand) {
                if (stripos((string) $product->description, 'Merek: ' . $brand) !== false) {
                    $hasBrand = true;
                    break;
                }
            }

            if ($hasBrand) {
                $brandMatch[] = $name;
            } elseif (in_array($product->category, $cfg['categories'], true)) {
                $categoryMatch[] = $name;
            } elseif (in_array('*', $cfg['categories'], true)) {
                $general[] = $name;
            }
        }

        // pemegang merek dulu, lalu spesialis kategori, terakhir toko umum
        return array_slice(array_merge($brandMatch, $categoryMatch, $general), 0, 3);
    }

    /** Bagi jumlah jadi beberapa bagian bulat yang kurang-lebih rata */
    private function split(float $total, int $parts): array
    {
        $total = (int) round($total);
        if ($total <= 0 || $parts <= 0) {
            return [];
        }

        $parts = min($parts, $total);
        $base = intdiv($total, $parts);
        $result = array_fill(0, $parts, $base);

        for ($i = 0; $i < $total - $base * $parts; $i++) {
            $result[$i]++;
        }

        return $result;
    }

    /** Bulatkan seperti harga grosir: kelipatan 100 (barang murah) atau 500 */
    private function roundPrice(float $price): float
    {
        $step = $price < 20000 ? 100 : 500;

        return max($step, round($price / $step) * $step);
    }
}

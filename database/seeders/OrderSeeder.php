<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Data transaksi CONTOH untuk Garasi Part — bulan SEPTEMBER & OKTOBER.
 *
 * Disesuaikan dengan migration:
 *   orders      : client_order_id, member_code, payment_amount, sub_total, tax, discount,
 *                 discount_amount, service_charge, total, payment_method, total_item,
 *                 table_number, customer_name, status, id_kasir, nama_kasir, transaction_time
 *   order_items : order_id, product_id, product_name, quantity, price
 *
 * Rumus sama dengan halaman Edit Order:
 *   total = (sub_total − discount_amount) + tax + service_charge
 *
 * Jalankan SETELAH UserSeeder, CategorySeeder, ProductSeeder (& MemberSeeder kalau ada):
 *   php artisan db:seed --class=OrderSeeder
 *
 * Catatan:
 * - Stok produk TIDAK dikurangi (supaya data contoh produk tetap utuh).
 * - Hasil acak dibuat tetap (seed 2026), jadi tiap dijalankan datanya sama.
 *   Menjalankan dua kali = data dobel → pakai `php artisan migrate:fresh --seed`.
 */
class OrderSeeder extends Seeder
{
    /** Rentang tanggal transaksi (tahun berjalan) */
    private const FROM = '09-01';   // 1 September
    private const UNTIL = '10-31';  // 31 Oktober

    /**
     * false = berhenti di waktu sekarang (tidak ada transaksi di masa depan).
     * true  = isi penuh sampai 31 Oktober, walau tanggalnya belum lewat.
     */
    private const FILL_FUTURE = false;

    /** Status yang disimpan (samakan dengan yang dikirim aplikasi kasir) */
    private const STATUS = 'Selesai';

    /** Pajak & service charge. Toko sparepart umumnya 0 — ubah kalau toko memungut PPN. */
    private const TAX_RATE = 0.0;
    private const SERVICE_RATE = 0.0;

    /** Satuan yang boleh desimal (mis. 1,5 liter / 2,5 meter) */
    private const DECIMAL_UNITS = ['LITER', 'METER'];

    private const CUSTOMERS = [
        'Bengkel Jaya Motor', 'Bengkel Sinar Abadi', 'Bengkel Maju Jaya', 'Rizky Pratama',
        'Andre Wowor', 'Steven Lumowa', 'Budi Santoso', 'Hendra Gunawan', 'Michael Rumengan',
        'Fadli Ramadhan', 'Yohanes Kalalo', 'Dimas Saputra', 'Kevin Tumbel', 'Agus Salim',
        'Ricky Mamahit', 'Fikri Hidayat', 'Christian Sondakh', 'Bayu Nugroho', 'Ojek Online',
        'Rental Motor Bunaken',
    ];

    public function run(): void
    {
        mt_srand(2026);

        $products = DB::table('products')
            ->where('status', 1)
            ->get(['id', 'name', 'price', 'base_unit', 'is_favorite'])
            ->all();

        if (empty($products)) {
            $this->command?->warn('OrderSeeder dilewati: tabel products masih kosong. Jalankan ProductSeeder dulu.');
            return;
        }

        $cashiers = $this->cashiers();
        $memberCodes = Schema::hasTable('members') ? DB::table('members')->pluck('code')->filter()->values()->all() : [];
        $discounts = Schema::hasTable('discounts')
            ? DB::table('discounts')->pluck('value')->map(fn($v) => (float) $v)->filter(fn($v) => $v > 0 && $v <= 30)->values()->all()
            : [];
        if (empty($discounts)) {
            $discounts = [5, 10];
        }

        // bobot: produk terlaris & murah (oli, busi, kampas) lebih sering terjual
        $weights = array_map(function ($p) {
            $w = $p->is_favorite ? 3 : 1;
            $w *= $p->price < 100000 ? 3 : ($p->price < 300000 ? 2 : 1);
            return $w;
        }, $products);
        $weightTotal = array_sum($weights);

        $now = Carbon::now();
        $orderCount = 0;
        $itemCount = 0;

        DB::transaction(function () use (
            $products, $weights, $weightTotal, $cashiers, $memberCodes, $discounts, $now, &$orderCount, &$itemCount
        ) {
            $start = Carbon::parse($now->year . '-' . self::FROM)->startOfDay();
            $end = Carbon::parse($now->year . '-' . self::UNTIL)->endOfDay();
            $limit = self::FILL_FUTURE ? $end : $now->copy()->min($end);
            $totalDays = $start->diffInDays($end) + 1;

            for ($day = $start->copy(); $day->lte($limit); $day->addDay()) {
                $dayIndex = $start->diffInDays($day);

                // Minggu buka setengah hari, Sabtu paling ramai
                $perDay = match ($day->dayOfWeekIso) {
                    7 => mt_rand(2, 5),
                    6 => mt_rand(8, 14),
                    default => mt_rand(4, 10),
                };
                // awal bulan ramai (gajian), tren naik pelan
                if ($day->day <= 5) {
                    $perDay += mt_rand(1, 3);
                }
                $perDay += (int) floor($dayIndex / max(1, $totalDays / 2));

                $closeHour = $day->dayOfWeekIso === 7 ? 13 : 20;

                $times = [];
                for ($i = 0; $i < $perDay; $i++) {
                    $t = $day->copy()->setTime(mt_rand(8, $closeHour - 1), mt_rand(0, 59), mt_rand(0, 59));
                    if (!self::FILL_FUTURE && $t->greaterThan($now)) {
                        continue; // jangan buat transaksi di masa depan
                    }
                    $times[] = $t;
                }
                usort($times, fn($a, $b) => $a <=> $b);

                foreach ($times as $time) {
                    [$order, $items] = $this->makeOrder(
                        $time, $products, $weights, $weightTotal, $cashiers, $memberCodes, $discounts
                    );

                    $orderId = DB::table('orders')->insertGetId($order);

                    foreach ($items as &$item) {
                        $item['order_id'] = $orderId;
                    }
                    unset($item);

                    DB::table('order_items')->insert($items);

                    $orderCount++;
                    $itemCount += count($items);
                }
            }
        });

        $this->command?->info("OrderSeeder: {$orderCount} transaksi, {$itemCount} item dibuat (September–Oktober {$now->year}).");
    }

    /**
     * Satu transaksi lengkap + item-itemnya.
     */
    private function makeOrder(
        Carbon $time, array $products, array $weights, int $weightTotal,
        array $cashiers, array $memberCodes, array $discounts
    ): array {
        // jumlah jenis barang: 1 (45%) · 2 (30%) · 3 (15%) · 4–5 (10%)
        $roll = mt_rand(1, 100);
        $lines = $roll <= 45 ? 1 : ($roll <= 75 ? 2 : ($roll <= 90 ? 3 : mt_rand(4, 5)));

        $picked = [];
        $guard = 0;
        while (count($picked) < min($lines, count($products)) && $guard++ < 50) {
            $idx = $this->weightedIndex($weights, $weightTotal);
            $picked[$idx] = $products[$idx];
        }

        // created_at / updated_at memakai format tanggal MySQL biasa…
        $stamp = $time->format('Y-m-d H:i:s');
        // …tetapi transaction_time HARUS berformat sama dengan yang dikirim aplikasi kasir
        // (2026-08-17T13:25:00, pakai huruf T). Dashboard & laporan membacanya dengan
        // STR_TO_DATE(..., '%Y-%m-%dT%H:%i:%s'); format lain dianggap kosong.
        $trxTime = $time->format('Y-m-d\\TH:i:s');
        $items = [];
        $subTotal = 0;
        $totalQty = 0.0;

        foreach ($picked as $p) {
            $qty = $this->quantity($p->base_unit);
            $price = (int) $p->price;

            $items[] = [
                'product_id' => $p->id,
                'product_name' => $p->name,
                'quantity' => $qty,
                'price' => $price,
                'created_at' => $stamp,
                'updated_at' => $stamp,
            ];

            $subTotal += (int) round($qty * $price);
            $totalQty += $qty;
        }

        // member & diskon
        $memberCode = (!empty($memberCodes) && mt_rand(1, 100) <= 30)
            ? $memberCodes[mt_rand(0, count($memberCodes) - 1)]
            : null;

        $discountPct = 0;
        if ($memberCode && mt_rand(1, 100) <= 50) {
            $discountPct = $discounts[mt_rand(0, count($discounts) - 1)];
        } elseif (!$memberCode && $subTotal >= 1000000 && mt_rand(1, 100) <= 25) {
            $discountPct = 5; // potongan belanja besar (bengkel)
        }

        $discountAmount = (int) round($subTotal * $discountPct / 100);
        $taxable = max(0, $subTotal - $discountAmount);
        $tax = (int) round($taxable * self::TAX_RATE);
        $service = (int) round($subTotal * self::SERVICE_RATE);
        $total = $taxable + $tax + $service;

        // pembayaran: tunai dibulatkan ke pecahan uang, transfer/QRIS pas
        $method = $this->paymentMethod($total);
        $paid = $method === 'Cash' ? $this->cashPaid($total) : $total;

        $cashier = $cashiers[mt_rand(0, count($cashiers) - 1)];

        $order = [
            'client_order_id' => (string) Str::uuid(),
            'member_code' => $memberCode,
            'payment_amount' => $paid,
            'sub_total' => $subTotal,
            'tax' => $tax,
            'discount' => (int) round($discountPct),
            'discount_amount' => $discountAmount,
            'service_charge' => $service,
            'total' => $total,
            'payment_method' => $method,
            'total_item' => round($totalQty, 2),
            'table_number' => null, // tidak dipakai di toko sparepart
            'customer_name' => mt_rand(1, 100) <= 55 ? self::CUSTOMERS[mt_rand(0, count(self::CUSTOMERS) - 1)] : null,
            'status' => self::STATUS,
            'id_kasir' => $cashier['id'],
            'nama_kasir' => $cashier['name'],
            'transaction_time' => $trxTime,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ];

        return [$order, $items];
    }

    /** Kasir = user admin/staff. Kalau belum ada user, pakai nama default. */
    private function cashiers(): array
    {
        $query = DB::table('users')->select('id', 'name');

        if (Schema::hasColumn('users', 'role')) {
            $staff = (clone $query)->whereIn('role', ['admin', 'staff', 'kasir'])->get();
            if ($staff->isNotEmpty()) {
                return $staff->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->all();
            }
        }

        $any = $query->limit(3)->get();
        if ($any->isNotEmpty()) {
            return $any->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->all();
        }

        return [['id' => 1, 'name' => 'Kasir']];
    }

    private function quantity(?string $unit): float
    {
        $unit = strtoupper((string) $unit);

        if (in_array($unit, self::DECIMAL_UNITS, true)) {
            return [0.5, 1, 1, 1.5, 2, 2.5, 3, 4][mt_rand(0, 7)];
        }

        if ($unit === 'PASANG' || $unit === 'SET') {
            return mt_rand(1, 100) <= 85 ? 1 : 2;
        }

        $r = mt_rand(1, 100);
        return $r <= 60 ? 1 : ($r <= 85 ? 2 : ($r <= 95 ? mt_rand(3, 4) : mt_rand(5, 10)));
    }

    private function paymentMethod(int $total): string
    {
        // belanja besar lebih sering transfer
        $transferChance = $total >= 1000000 ? 60 : ($total >= 300000 ? 35 : 15);
        return mt_rand(1, 100) <= $transferChance ? 'Transfer' : 'Cash';
    }

    private function cashPaid(int $total): int
    {
        $r = mt_rand(1, 100);
        $step = $r <= 25 ? 1 : ($r <= 55 ? 5000 : ($r <= 85 ? 10000 : 50000));
        $paid = (int) (ceil($total / $step) * $step);

        // kadang bayar pakai pecahan 100 ribu
        if ($total < 100000 && mt_rand(1, 100) <= 20) {
            $paid = 100000;
        }

        return max($paid, $total);
    }

    private function weightedIndex(array $weights, int $total): int
    {
        $r = mt_rand(1, $total);
        foreach ($weights as $i => $w) {
            $r -= $w;
            if ($r <= 0) {
                return $i;
            }
        }
        return array_key_last($weights);
    }
}

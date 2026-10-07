<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Supplier CONTOH untuk toko sparepart excavator.
 * Nama-nama ini karangan — ganti dengan supplier asli toko sebelum dipakai live.
 * Kalau namanya diubah, samakan juga di konstanta SUPPLIERS pada StockInSeeder.
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['PT Mitra Alat Berat Sulut', '0431-861234', 'Jl. Raya Manado–Bitung Km 12', 'Part genuine CAT. Tempo 30 hari, minimal order Rp5 juta.'],
            ['PT Karya Traktor Nusantara', '0431-872210', 'Kawasan Industri Bitung', 'Part genuine Komatsu. Tempo 30 hari.'],
            ['CV Sumber Alat Berat', '0812-4400-1122', 'Jl. Piere Tendean, Manado', 'Part aftermarket: filter, undercarriage, kuku bucket. Bayar tunai, harga paling miring.'],
            ['UD Maju Jaya Hidrolik', '0813-5566-7788', 'Jl. Martadinata, Manado', 'Selang & seal hidrolik, oli. Tempo 14 hari. Bisa press selang di tempat.'],
            ['Toko Sinar Abadi Teknik', '0852-9900-3344', 'Pasar 45, Manado', 'Serba ada, stok cepat tapi harga lebih tinggi.'],
            ['Online / Marketplace', null, null, 'Pembelian lewat marketplace kalau stok lokal kosong.'],
        ];

        $now = now();

        foreach ($suppliers as [$name, $phone, $address, $notes]) {
            DB::table('suppliers')->updateOrInsert(
                ['name' => $name],
                ['phone' => $phone, 'address' => $address, 'notes' => $notes, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}

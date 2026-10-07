<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        /*
        | 10 produk contoh sparepart EXCAVATOR (kelas 20 ton: PC200 / CAT 320).
        |
        | Disesuaikan dengan migration products:
        | category_id, code, name, description, image, price, stock,
        | base_unit, status, is_favorite
        |
        | Kode part disimpan di kolom code (unik). Merek, kode part, dan
        | kecocokan unit juga tetap ada di description supaya bisa dicari. Teks "Merek: ..." juga dipakai
        | StockInSeeder untuk memilih supplier yang cocok.
        |
        | CATATAN: harga, stok, dan KODE PART adalah DATA CONTOH — bukan
        | referensi resmi pabrikan. Cocokkan dengan katalog part asli
        | sebelum dipakai untuk jual-beli sungguhan.
        */
        $products = [

            // ============================================================
            // FILTER
            // ============================================================

            [
                'code' => '1R-0739',
                'name' => 'Filter Oli Mesin CAT 320D',
                'category' => 'Filter',
                'description' => 'Filter oli mesin untuk servis berkala tiap 250 jam. Cocok untuk: CAT 320D, 320D2, 323D. Merek: CAT. Kode part: 1R-0739.',
                'price' => 385000,
                'stock' => 24,
                'base_unit' => 'PCS',
                'is_favorite' => 1,
            ],
            [
                'code' => '207-60-71182',
                'name' => 'Filter Hidrolik PC200-8',
                'category' => 'Filter',
                'description' => 'Elemen filter hidrolik return, menjaga oli hidrolik bersih dari serpihan logam. Cocok untuk: Komatsu PC200-7, PC200-8, PC210-8. Merek: Komatsu. Kode part: 207-60-71182.',
                'price' => 690000,
                'stock' => 12,
                'base_unit' => 'PCS',
                'is_favorite' => 0,
            ],
            [
                'code' => 'P551329',
                'name' => 'Filter Solar + Water Separator',
                'category' => 'Filter',
                'description' => 'Filter solar dengan pemisah air, melindungi injector dari solar kotor. Cocok untuk: Excavator kelas 20 ton, universal ulir 1"-14. Merek: Donaldson. Kode part: P551329.',
                'price' => 325000,
                'stock' => 30,
                'base_unit' => 'PCS',
                'is_favorite' => 1,
            ],

            // ============================================================
            // OLI & PELUMAS
            // ============================================================

            [
                'code' => 'MEDITRAN-SX-1540-20',
                'name' => 'Oli Mesin Diesel 15W-40 CI-4 20L',
                'category' => 'Oli & Pelumas',
                'description' => 'Oli mesin diesel tugas berat, kemasan pail 20 liter. Cocok untuk: Mesin diesel alat berat & truk. Merek: Pertamina. Kode part: MEDITRAN-SX-1540-20.',
                'price' => 1150000,
                'stock' => 15,
                'base_unit' => 'PAIL',
                'is_favorite' => 1,
            ],
            [
                'code' => 'TELLUS-S2-M46-20',
                'name' => 'Oli Hidrolik ISO VG 46 20L',
                'category' => 'Oli & Pelumas',
                'description' => 'Oli hidrolik anti aus untuk sistem hidrolik excavator, kemasan pail 20 liter. Cocok untuk: Semua excavator & alat berat. Merek: Shell. Kode part: TELLUS-S2-M46-20.',
                'price' => 985000,
                'stock' => 18,
                'base_unit' => 'PAIL',
                'is_favorite' => 1,
            ],

            // ============================================================
            // BUCKET & KUKU
            // ============================================================

            [
                'code' => '205-70-19570',
                'name' => 'Kuku Bucket PC200',
                'category' => 'Bucket & Kuku',
                'description' => 'Kuku bucket (tooth) tipe standar, baja tempa tahan aus. Dijual per buah, belum termasuk pin. Cocok untuk: Komatsu PC200-6/-7/-8. Merek: Komatsu. Kode part: 205-70-19570.',
                'price' => 295000,
                'stock' => 40,
                'base_unit' => 'PCS',
                'is_favorite' => 1,
            ],

            // ============================================================
            // UNDERCARRIAGE
            // ============================================================

            [
                'code' => '20Y-30-00016',
                'name' => 'Track Roller PC200',
                'category' => 'Undercarriage',
                'description' => 'Roller bawah (bottom roller) single flange, sudah terisi oli. Cocok untuk: Komatsu PC200-6/-7/-8. Merek: ITR. Kode part: 20Y-30-00016.',
                'price' => 1650000,
                'stock' => 16,
                'base_unit' => 'PCS',
                'is_favorite' => 0,
            ],

            // ============================================================
            // HIDROLIK
            // ============================================================

            [
                'code' => '8M2T',
                'name' => 'Selang Hidrolik 1/2" R2 (2 Wire)',
                'category' => 'Hidrolik',
                'description' => 'Selang hidrolik 2 lapis kawat, tekanan kerja 275 bar. Dijual per meter, belum termasuk fitting & press. Cocok untuk: Universal. Merek: Gates. Kode part: 8M2T.',
                'price' => 145000,
                'stock' => 60,
                'base_unit' => 'METER',
                'is_favorite' => 0,
            ],

            // ============================================================
            // SEAL & O-RING
            // ============================================================

            [
                'code' => 'SK-BOOM-PC200-8',
                'name' => 'Seal Kit Silinder Boom PC200-8',
                'category' => 'Seal & O-Ring',
                'description' => 'Satu set seal untuk rekondisi silinder boom yang bocor. Cocok untuk: Komatsu PC200-8. Merek: NOK. Kode part: SK-BOOM-PC200-8.',
                'price' => 780000,
                'stock' => 8,
                'base_unit' => 'SET',
                'is_favorite' => 0,
            ],

            // ============================================================
            // KELISTRIKAN & AKI
            // ============================================================

            [
                'code' => 'N120',
                'name' => 'Aki N120 12V 120Ah',
                'category' => 'Kelistrikan & Aki',
                'description' => 'Aki basah 12V 120Ah. Excavator 24V memakai 2 buah dipasang seri. Cocok untuk: Excavator kelas 20 ton, truk, genset. Merek: GS Astra. Kode part: N120.',
                'price' => 2150000,
                'stock' => 6,
                'base_unit' => 'PCS',
                'is_favorite' => 0,
            ],

        ];

        foreach ($products as $product) {

            // Kategori dibuat otomatis kalau belum ada di database
            $category = Category::firstOrCreate(
                ['name' => $product['category']],
                ['description' => null, 'image' => null]
            );

            // Hanya kolom yang ada di migration products
            Product::updateOrCreate(
                ['name' => $product['name']],
                [
                    'category_id' => $category->id,
                    'code' => $product['code'],
                    'description' => $product['description'],
                    'image' => null,
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'base_unit' => $product['base_unit'] ?? 'PCS',
                    'status' => 1,
                    'is_favorite' => $product['is_favorite'],
                ]
            );
        }
    }
}

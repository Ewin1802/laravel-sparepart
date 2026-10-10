<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        /*
        | Kategori sparepart EXCAVATOR / alat berat.
        |
        | Beberapa nama sengaja memuat kata kunci (oli, filter, aki, mesin)
        | supaya landing page otomatis memberi ikon yang pas.
        */
        $categories = [
            [
                'name' => 'Filter',
                'description' => 'Filter oli mesin, filter solar, filter hidrolik, filter udara, dan water separator.',
                'image' => null,
            ],
            [
                'name' => 'Oli & Pelumas',
                'description' => 'Oli mesin diesel, oli hidrolik, oli final drive / swing, dan grease.',
                'image' => null,
            ],
            [
                'name' => 'Bucket & Kuku',
                'description' => 'Kuku bucket (tooth), pin & lock, bushing, shim, side cutter, dan cutting edge.',
                'image' => null,
            ],
            [
                'name' => 'Undercarriage',
                'description' => 'Track roller, carrier roller, idler, sprocket, track link, dan track shoe.',
                'image' => null,
            ],
            [
                'name' => 'Hidrolik',
                'description' => 'Selang hidrolik, fitting, pompa, control valve, dan komponen silinder.',
                'image' => null,
            ],
            [
                'name' => 'Seal & O-Ring',
                'description' => 'Seal kit silinder boom / arm / bucket, O-ring kit, dan floating seal.',
                'image' => null,
            ],
            [
                'name' => 'Mesin',
                'description' => 'Fan belt, water pump, turbocharger, injector, gasket, dan komponen mesin diesel.',
                'image' => null,
            ],
            [
                'name' => 'Kelistrikan & Aki',
                'description' => 'Aki, alternator, starter, switch kontak, relay, lampu kerja, rotari, dan kabel.',
                'image' => null,
            ],

            // --- tambahan sesuai daftar barang toko (import Excel) ---
            [
                'name' => 'Perkakas',
                'description' => 'Kunci & mata shock, kunci filter, pompa gemuk, nepel grease, perlengkapan las, dan obeng.',
                'image' => null,
            ],
            [
                'name' => 'Ban Mobil',
                'description' => 'Ban mobil & truk ringan merek Accelera, Forceum, GT Radial, dan Hankook.',
                'image' => null,
            ],
            [
                'name' => 'Cairan & Kimia',
                'description' => 'Air radiator / coolant, minyak rem, lem kaca, dan carbu cleaner.',
                'image' => null,
            ],
            [
                'name' => 'Sparepart Truk & Mobil',
                'description' => 'Stik as roda dan komponen kaki-kaki truk & mobil (Hino, Isuzu, Toyota).',
                'image' => null,
            ],
            [
                'name' => 'Perlengkapan',
                'description' => 'Kain majun, stiker keselamatan, dan perlengkapan bengkel lainnya.',
                'image' => null,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}

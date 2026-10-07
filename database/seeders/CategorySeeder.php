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
                'description' => 'Kuku bucket (tooth), adapter, pin & lock, side cutter, dan cutting edge.',
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
                'description' => 'Aki, alternator, starter, sensor, lampu kerja, dan kabel.',
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

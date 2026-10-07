<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [

                /*
                |--------------------------------------------------------------------------
                | IDENTITAS TOKO
                |--------------------------------------------------------------------------
                */

                'store_name' =>
                'Toko Utama Caterpillar',

                'store_tagline' =>
                'Sparepart Alat Berat • Original • Bergaransi',

                'store_description' =>
                'Toko Utama Caterpillar menyediakan sparepart alat berat original maupun aftermarket berkualitas: filter, oli, kuku bucket, undercarriage, selang hidrolik, seal kit, hingga aki. Kami bantu cek kecocokan part dengan unit Anda sebelum membeli.',

                /*
                |--------------------------------------------------------------------------
                | HERO
                | (boleh pakai <span class="hl">...</span> untuk kata berwarna oranye)
                |--------------------------------------------------------------------------
                */

                'hero_title' =>
                'Part yang <span class="hl">Tepat</span>, Alat Berat Tetap Kerja',

                'hero_subtitle' =>
                'Filter, oli, kuku bucket, track roller, selang hidrolik, seal kit, sampai aki — untuk excavator dan alat berat lainnya. Cari berdasarkan nama part, kode part, atau tipe unit, dan kami pastikan cocok sebelum Anda beli.',

                'hero_button' =>
                'Lihat Katalog',


                /*
                |--------------------------------------------------------------------------
                | KONTAK
                |--------------------------------------------------------------------------
                */

                'phone' =>
                '081327201592',

                'whatsapp' =>
                '6281327201592',

                'email' =>
                'info@tokoutamacaterpillar.com',

                'address' =>
                'Kota Marisa',


                /*
                |--------------------------------------------------------------------------
                | SOCIAL MEDIA
                |--------------------------------------------------------------------------
                */

                'facebook' =>
                'https://www.facebook.com',

                'instagram' =>
                'https://www.instagram.com',

                'youtube' =>
                'https://www.youtube.com',

                'tiktok' =>
                'https://www.tiktok.com',


                /*
                |--------------------------------------------------------------------------
                | SEO
                |--------------------------------------------------------------------------
                */

                'meta_title' =>
                'Toko Utama Caterpillar | Sparepart Alat Berat di Marisa',

                'meta_description' =>
                'Toko sparepart alat berat di Marisa. Filter, oli, kuku bucket, undercarriage, selang hidrolik, seal kit, dan aki untuk excavator — original dan aftermarket, bergaransi.',

                'meta_keywords' =>
                'sparepart alat berat,sparepart excavator,toko sparepart alat berat Marisa,filter excavator,oli hidrolik,kuku bucket,track roller,undercarriage,selang hidrolik,seal kit,aki alat berat,part Caterpillar,part Komatsu',


                /*
                |--------------------------------------------------------------------------
                | COPYRIGHT
                |--------------------------------------------------------------------------
                */

                'copyright' =>
                '© ' . date('Y') . ' Toko Utama Caterpillar. All rights reserved.',

            ]
        );
    }
}

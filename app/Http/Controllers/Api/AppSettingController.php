<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

/**
 * GET /api/app-settings
 *
 * Penanda fitur untuk aplikasi kasir. Aplikasi membacanya saat dibuka,
 * saat koneksi kembali, dan saat tombol sinkron produk ditekan, lalu
 * menyimpannya di HP supaya tetap berlaku saat offline.
 */
class AppSettingController extends Controller
{
    public function show()
    {
        $setting = Setting::first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'store_name' => $setting?->store_name,
                'features' => [
                    'draft_order' => (bool) ($setting?->feature_draft_order ?? false),
                ],
            ],
        ]);
    }
}

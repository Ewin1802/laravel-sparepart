<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberDeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * POST /api/member/device-token
     *
     * Dipanggil aplikasi Flutter setiap kali:
     * - Berhasil login
     * - Token FCM di-refresh oleh Firebase (jarang, tapi bisa terjadi)
     *
     * updateOrCreate berdasarkan token supaya satu device (token)
     * gak kedaftar dobel, dan kalau device yang sama dipakai login
     * ulang dengan akun berbeda, kepemilikan token otomatis pindah
     * ke user yang login terakhir.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:20'],
        ]);

        MemberDeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->platform,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device token tersimpan.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberBarcode;
use Illuminate\Http\Request;

class MemberBarcodeController extends Controller
{
    /**
     * ==========================================================
     * SCAN MEMBER
     * ==========================================================
     *
     * Digunakan oleh aplikasi kasir.
     *
     * POST /api/member/scan
     *
     * Body:
     * {
     *     "code": "MM-XXXXXXXXXX"
     * }
     */
    public function scan(Request $request)
    {
        // ======================================================
        // VALIDASI
        // ======================================================

        $request->validate([
            'code' => 'required|string|max:100',
        ]);

        $code = trim($request->code);

        // ======================================================
        // CARI MEMBER
        // ======================================================

        $barcode = MemberBarcode::with('user')
            ->where('code', $code)
            ->first();

        // ======================================================
        // MEMBER TIDAK DITEMUKAN
        // ======================================================

        if (!$barcode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Barcode member tidak ditemukan.',
            ], 404);
        }

        // ======================================================
        // CEK USER
        // ======================================================

        if (!$barcode->user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data user member tidak ditemukan.',
            ], 404);
        }

        // ======================================================
        // CEK VALIDITAS
        // ======================================================

        if (!$barcode->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Member tidak aktif atau barcode sudah tidak berlaku.',
            ], 422);
        }

        // ======================================================
        // RESPONSE
        // ======================================================

        return response()->json([
            'status' => 'success',

            'message' => 'Member ditemukan.',

            'data' => $this->formatMember($barcode),

        ], 200);
    }

    /**
     * ==========================================================
     * SYNC MEMBER
     * ==========================================================
     *
     * Mengambil seluruh member dari server.
     *
     * Digunakan oleh aplikasi kasir untuk menyimpan
     * cache member ke SQLite.
     *
     * GET /api/member/sync
     *
     * PENTING:
     *
     * Jangan menggunakan:
     *
     * ->where('is_active', true)
     *
     * karena member yang sudah dinonaktifkan juga harus
     * dikirim ke kasir agar cache SQLite ikut berubah.
     */
    public function sync()
    {
        // ======================================================
        // AMBIL SEMUA MEMBER
        // ======================================================

        $members = MemberBarcode::with('user')
            ->orderBy('id')
            ->get();

        // ======================================================
        // FORMAT RESPONSE
        // ======================================================

        return response()->json([
            'status' => 'success',

            'data' => $members
                ->map(function ($barcode) {
                    return $this->formatMember($barcode);
                })
                ->values(),

            'meta' => [
                'total' => $members->count(),
                'synced_at' => now()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * ==========================================================
     * FORMAT MEMBER
     * ==========================================================
     *
     * Supaya response scan dan sync mempunyai struktur
     * yang sama.
     */
    private function formatMember(MemberBarcode $barcode): array
    {
        return [
            // ==================================================
            // MEMBER
            // ==================================================
            'id' => $barcode->id,
            'user_id' => $barcode->user_id,
            'name' => $barcode->user?->name ?? '',
            'email' => $barcode->user?->email ?? '',
            'phone_number' => $barcode->user?->phone_number ?? '', // <-- tambahan
            // ==================================================
            // BARCODE
            // ==================================================
            'code' => $barcode->code,
            // ==================================================
            // BIRTH DATE
            // ==================================================
            'birth_date' => $barcode->birth_date,
            // ==================================================
            // DISCOUNT
            // ==================================================
            'discount_type' => $barcode->discount_type,
            'discount_value' => $barcode->discount_value,
            // ==================================================
            // STAMP
            // ==================================================
            'stamp_count' => $barcode->stamp_count,
            'stamp_target' => $barcode->stamp_target,
            // ==================================================
            // STATUS
            // ==================================================
            'is_active' => (bool) $barcode->is_active,
            // ==================================================
            // VALIDITY
            // ==================================================
            'valid_from' => $barcode->valid_from,
            'valid_until' => $barcode->valid_until,
        ];
    }
}

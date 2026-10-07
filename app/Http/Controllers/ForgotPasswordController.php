<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    /**
     * ============================================================
     * TAMPILKAN FORM LUPA PASSWORD
     * ============================================================
     */
    public function show()
    {
        return view('pages.auth.forgot-password');
    }

    /**
     * ============================================================
     * RESET PASSWORD (USER PILIH PASSWORD BARU SENDIRI)
     * ============================================================
     *
     * CATATAN KEAMANAN:
     *
     * Ini BUKAN flow reset password standar (kirim link ke email).
     * User cuma perlu tau email + nomor HP yang terdaftar buat
     * langsung ganti password ke password baru pilihannya sendiri,
     * tanpa verifikasi lebih lanjut. Dua data ini dijadikan syarat
     * SEKALIGUS (bukan salah satu) supaya gak semudah itu ditebak/
     * disalahgunakan orang lain, tapi tetap TIDAK SEAMAN flow reset
     * password via email asli.
     *
     * ============================================================
     */
    public function reset(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'phone_number' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        // ============================================================
        // CARI USER — EMAIL DAN NOMOR HP HARUS COCOK DUA-DUANYA
        // ============================================================

        $user = User::where('email', $validated['email'])
            ->where('phone_number', $validated['phone_number'])
            ->first();

        if (!$user) {
            return back()
                ->withInput($request->only('email', 'phone_number'))
                ->withErrors([
                    'email' => 'Email atau nomor HP tidak cocok dengan data yang terdaftar.',
                ]);
        }

        // ============================================================
        // SIMPAN PASSWORD BARU PILIHAN USER
        // ============================================================

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Password berhasil diperbarui. Silakan login dengan password baru Anda.'
            );
    }
}

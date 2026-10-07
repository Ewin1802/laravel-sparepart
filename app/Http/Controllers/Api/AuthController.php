<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\MemberBarcode;

class AuthController extends Controller
{
    /**
     * ============================================================
     * REGISTER MEMBER
     * ============================================================
     *
     * CATATAN PENTING BUAT SISI FLUTTER:
     *
     * Karena sekarang endpoint ini bisa nerima FILE (foto profil),
     * request dari app HARUS dikirim sebagai multipart/form-data
     * (pakai http.MultipartRequest), BUKAN JSON body / body: {...}
     * biasa seperti sebelumnya. Field lain (name, email, dst) tetap
     * dikirim sebagai field text biasa di dalam multipart itu, cuma
     * "photo" yang dikirim sebagai file — kalau user gak pilih foto,
     * field "photo" boleh gak usah disertakan sama sekali di request.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'phone_number' => ['required','string','max:20','unique:users,phone_number'],
            'birth_date' => ['required','date','before:today',],
            'password' => ['required','string','min:6','confirmed',],

            // =====================================================
            // FOTO PROFIL — OPSIONAL
            // =====================================================
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        try {
            $result = DB::transaction(function () use ($request) {

                // =====================================================
                // CREATE USER
                // =====================================================

                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone_number' => $request->phone_number,
                    'date_of_birth' => $request->birth_date,
                    'password' => Hash::make($request->password),
                    'role' => 'user',
                ]);

                // =====================================================
                // UPLOAD FOTO PROFIL (KALAU ADA)
                // =====================================================
                //
                // Sengaja dilakukan SETELAH $user->id ada, supaya nama
                // file bisa dikaitkan dengan ID user (gampang ditelusuri
                // dan gak akan tabrakan nama antar member).
                //
                // =====================================================

                if ($request->hasFile('photo')) {

                    $photo = $request->file('photo');

                    $filename = 'member-' . $user->id . '-' . time()
                        . '.' . $photo->getClientOriginalExtension();

                    $photo->storeAs('members', $filename, 'public');

                    $user->photo = 'members/' . $filename;
                    $user->save();
                }

                // =====================================================
                // CREATE MEMBER BARCODE
                // =====================================================

                $memberBarcode = MemberBarcode::create([
                    'user_id' => $user->id,
                    'birth_date' => $request->birth_date,
                    'phone_number' => $request->phone_number,
                    'code' => $this->generateMemberCode(),
                    'discount_type' => 'percentage',
                    'discount_value' => 0,
                    'stamp_count' => 0,
                    'stamp_target' => 10,
                    'is_active' => true,
                    'valid_from' => now(),
                    'valid_until' => null,
                ]);

                // =====================================================
                // TOKEN
                // =====================================================

                $token = $user
                    ->createToken('android-member')
                    ->plainTextToken;

                return [
                    'user' => $user,
                    'member' => $memberBarcode,
                    'token' => $token,
                ];
            });

            // =========================================================
            // RESPONSE
            // =========================================================

            return response()->json([
                'status' => 'success',

                'message' => 'Registrasi member berhasil.',

                'data' => [
                    'token' => $result['token'],

                    'user' => [
                        'id' => $result['user']->id,
                        'name' => $result['user']->name,
                        'email' => $result['user']->email,
                        'phone_number' => $result['user']->phone_number,
                        'role' => $result['user']->role,

                        // photo_url sekarang otomatis dari accessor
                        // di model User — null kalau member gak
                        // upload foto pas register.
                        'photo_url' => $result['user']->photo_url,
                    ],

                    'member' => [
                        'id' =>
                            $result['member']->id,

                        'user_id' =>
                            $result['member']->user_id,
                        'phone_number' => $result['member']->phone_number,
                        'birth_date' =>
                            $result['member']
                                ->birth_date
                                ?->format('Y-m-d'),

                        'code' =>
                            $result['member']->code,

                        'discount_type' =>
                            $result['member']
                                ->discount_type,

                        'discount_value' =>
                            $result['member']
                                ->discount_value,

                        'stamp_count' =>
                            $result['member']
                                ->stamp_count,

                        'stamp_target' =>
                            $result['member']
                                ->stamp_target,

                        'is_active' =>
                            $result['member']
                                ->is_active,

                        'valid_from' =>
                            $result['member']
                                ->valid_from,

                        'valid_until' =>
                            $result['member']
                                ->valid_until,
                    ],
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Registrasi member gagal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * ============================================================
     * GENERATE MEMBER CODE
     * ============================================================
     */
    private function generateMemberCode(): string
    {
        do {
            $code = 'MM-' . strtoupper(
                Str::random(10)
            );
        } while (
            MemberBarcode::where('code', $code)->exists()
        );

        return $code;
    }


    /**
     * ============================================================
     * LOGIN
     * ============================================================
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Ambil user sekaligus barcode member
        $user = User::with('memberBarcode')
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        // Cek password
        if (!Hash::check(
            $request->password,
            $user->password
        )) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Generate token
        $token = $user
            ->createToken('auth-token')
            ->plainTextToken;

        return response()->json([
            'status' => 'success',

            'data' => [

                'token' => $token,

                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,

                    // Disamakan dengan response register — accessor
                    // photo_url di model User yang ngurus semuanya.
                    'photo_url' => $user->photo_url,
                ],

                'member' => $user->memberBarcode
                    ? [
                        'id' =>
                            $user->memberBarcode->id,

                        'user_id' =>
                            $user->memberBarcode->user_id,

                        'birth_date' =>
                            $user->memberBarcode->birth_date
                                ?->format('Y-m-d'),

                        'code' =>
                            $user->memberBarcode->code,

                        'discount_type' =>
                            $user->memberBarcode->discount_type,

                        'discount_value' =>
                            $user->memberBarcode->discount_value,

                        'stamp_count' =>
                            $user->memberBarcode->stamp_count,

                        'stamp_target' =>
                            $user->memberBarcode->stamp_target,

                        'is_active' =>
                            $user->memberBarcode->is_active,

                        'valid_from' =>
                            $user->memberBarcode->valid_from,

                        'valid_until' =>
                            $user->memberBarcode->valid_until,
                    ]
                    : null,
            ],
        ], 200);
    }


    /**
     * ============================================================
     * LOGOUT
     * ============================================================
     */
    public function logout(Request $request)
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out',
        ], 200);
    }
}

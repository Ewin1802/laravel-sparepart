<?php

namespace App\Http\Controllers;

use App\Models\MemberBarcode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    /**
     * Tampilkan daftar member.
     */
    public function index(Request $request)
    {
        $members = MemberBarcode::query()
            ->with('user')
            ->when($request->filled('name'), function ($query) use ($request) {
                $keyword = $request->name;

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($sub) use ($keyword) {
                        $sub->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    })->orWhere('code', 'like', "%{$keyword}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('pages.member.index', compact('members'));
    }

    /**
     * Tampilkan form tambah member.
     */
    public function create()
    {
        $users = User::whereDoesntHave('memberBarcode')
            ->orderBy('name')
            ->get();

        return view('pages.member.create', compact('users'));
    }

    /**
     * Simpan member baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => ['required', 'exists:users,id', 'unique:member_barcodes,user_id'],
            'phone_number'   => ['required', 'string', 'max:20', 'unique:users,phone_number'], // <-- tambahan
            'birth_date'     => ['nullable', 'date'],
            'code'           => ['required', 'string', 'max:100', 'unique:member_barcodes,code'],
            'discount_type'  => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'stamp_target'   => ['required', 'integer', 'min:1'],
            'valid_from'     => ['nullable', 'date'],
            'valid_until'    => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Sinkronkan phone_number ke tabel users juga
        User::whereKey($validated['user_id'])
            ->update(['phone_number' => $validated['phone_number']]);

        MemberBarcode::create($validated);

        return redirect()
            ->route('members.index')
            ->with('success', 'Member berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit member.
     */
    public function edit(MemberBarcode $member)
    {
        $users = User::where(function ($query) use ($member) {
                $query->whereDoesntHave('memberBarcode')
                    ->orWhere('id', $member->user_id);
            })
            ->orderBy('name')
            ->get();

        return view('pages.member.edit', compact('member', 'users'));
    }

    /**
     * Perbarui data member.
     */
   public function update(Request $request, MemberBarcode $member)
    {
        $userId = $request->input('user_id', $member->user_id);

        $validated = $request->validate([
            'user_id'        => ['required', 'exists:users,id', Rule::unique('member_barcodes', 'user_id')->ignore($member->id)],
            'phone_number'   => ['required', 'string', 'max:20', Rule::unique('users', 'phone_number')->ignore($userId)],
            'birth_date'     => ['nullable', 'date'],
            'code'           => ['required', 'string', 'max:100', Rule::unique('member_barcodes', 'code')->ignore($member->id)],
            'discount_type'  => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'stamp_count'    => ['required', 'integer', 'min:0'],
            'stamp_target'   => ['required', 'integer', 'min:1'],
            'valid_from'     => ['nullable', 'date'],
            'valid_until'    => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Sinkronkan phone_number ke tabel users juga
        User::whereKey($validated['user_id'])
            ->update(['phone_number' => $validated['phone_number']]);

        $member->update($validated);

        return redirect()
            ->route('members.index')
            ->with('success', 'Member berhasil diperbarui.');
    }

    /**
     * Hapus member.
     */
    public function destroy(MemberBarcode $member)
    {
        $member->delete();

        return redirect()
            ->route('members.index')
            ->with('success', 'Member berhasil dihapus.');
    }

    /**
     * Generate kode barcode unik (dipakai tombol "Generate" di form).
     */
    public function generateCode()
    {
        do {
            $code = 'MRM-' . strtoupper(Str::random(8));
        } while (MemberBarcode::where('code', $code)->exists());

        return response()->json(['code' => $code]);
    }
}

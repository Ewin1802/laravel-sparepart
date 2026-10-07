@php
    $isEdit = isset($user);
    $currentRole = old('role', $user->role ?? '');
    $roles = [
        'admin' => ['Admin', 'Akses penuh', 'shield-check'],
        'staff' => ['Kasir', 'Transaksi & stok', 'scan-line'],
        'user' => ['User', 'Member / pelanggan', 'user-round'],
    ];
@endphp

@if ($errors->any())
    <div class="p-notice is-danger" role="alert">
        <i data-lucide="circle-alert"></i>
        <div>
            <strong>Periksa kembali isian yang ditandai.</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    </div>
@endif

<div class="p-form-layout">

    {{-- ===================== KOLOM UTAMA ===================== --}}
    <div class="p-form-main">

        {{-- PROFIL --}}
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="user-round"></i></span>
                <div>
                    <h3>Profil</h3>
                    <p>Nama dan kontak pengguna.</p>
                </div>
            </div>

            <div class="p-grid">
                <div class="p-field p-span-2">
                    <label for="name">Nama Lengkap <span class="req">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}"
                        placeholder="Masukkan nama lengkap" required maxlength="255" autocomplete="name"
                        class="@error('name') is-invalid @enderror">
                    @error('name')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}"
                        placeholder="nama@email.com" required autocomplete="off" inputmode="email"
                        class="@error('email') is-invalid @enderror">
                    @error('email')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field">
                    <label for="phone_number">Nomor HP <span class="req">*</span></label>
                    <input type="tel" id="phone_number" name="phone_number" inputmode="tel" autocomplete="off"
                        value="{{ old('phone_number', $user->phone_number ?? '') }}" placeholder="081234567890"
                        required class="@error('phone_number') is-invalid @enderror">
                    <small class="p-hint">Dipakai untuk reset password.</small>
                    @error('phone_number')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </section>

        {{-- PERAN --}}
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="key-round"></i></span>
                <div>
                    <h3>Peran</h3>
                    <p>Menentukan menu yang bisa diakses.</p>
                </div>
            </div>

            <div class="p-segmented is-cards" role="radiogroup" aria-label="Peran user">
                @foreach ($roles as $value => [$label, $desc, $icon])
                    <label>
                        <input type="radio" name="role" value="{{ $value }}" required @checked($currentRole === $value)>
                        <span>
                            <i data-lucide="{{ $icon }}"></i>
                            {{ $label }}
                            <small>{{ $desc }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('role')
                <small class="p-error">{{ $message }}</small>
            @enderror
        </section>

    </div>

    {{-- ===================== KOLOM SAMPING ===================== --}}
    <aside class="p-form-side">
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="lock"></i></span>
                <div>
                    <h3>Password</h3>
                    <p>{{ $isEdit ? 'Kosongkan jika tidak diubah.' : 'Wajib untuk akun baru.' }}</p>
                </div>
            </div>

            <div class="p-grid">
                <div class="p-field p-span-2">
                    <label for="password">Password @unless ($isEdit)<span class="req">*</span>@endunless</label>
                    <div class="p-password">
                        <input type="password" id="password" name="password" autocomplete="new-password"
                            placeholder="{{ $isEdit ? 'Biarkan kosong' : 'Buat password' }}"
                            @unless ($isEdit) required @endunless
                            class="@error('password') is-invalid @enderror">
                        <button type="button" data-toggle-password="password" aria-label="Tampilkan password">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field p-span-2">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <div class="p-password">
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            autocomplete="new-password" placeholder="Ulangi password" data-match="#password">
                        <button type="button" data-toggle-password="password_confirmation"
                            aria-label="Tampilkan password">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                    <small class="p-match-msg">Konfirmasi password tidak sama.</small>
                </div>
            </div>

            <div class="p-form-actions">
                <a href="{{ route('users.index') }}" class="p-btn p-btn-ghost">Batal</a>
                <button type="submit" class="p-btn p-btn-primary">
                    <i data-lucide="save"></i>
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan User' }}
                </button>
            </div>
        </section>
    </aside>
</div>

{{-- Bar simpan yang selalu terlihat di HP / tablet --}}
<div class="p-savebar">
    <a href="{{ route('users.index') }}" class="p-btn p-btn-ghost">Batal</a>
    <button type="submit" class="p-btn p-btn-primary">
        <i data-lucide="save"></i>
        {{ $isEdit ? 'Simpan Perubahan' : 'Simpan User' }}
    </button>
</div>

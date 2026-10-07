{{--
    Partial form untuk create.blade.php & edit.blade.php.
    $member = null saat create, berisi MemberBarcode saat edit.
    Nama field sama persis dengan versi lama → controller tidak perlu diubah.
--}}
@php
    $isEdit = filled($member);
    $discountType = old('discount_type', $member->discount_type ?? 'percentage');
    $isActive = (bool) old('is_active', $member->is_active ?? true);
    // kalau validasi gagal & checkbox tidak dicentang, old('is_active') kosong → ikuti input terakhir
    if ($errors->any()) {
        $isActive = (bool) old('is_active');
    }
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

        {{-- DATA PELANGGAN --}}
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="user-round"></i></span>
                <div>
                    <h3>Data Pelanggan</h3>
                    <p>Akun pengguna yang dijadikan member.</p>
                </div>
            </div>

            <div class="p-grid">
                <div class="p-field p-span-2">
                    <label for="user_id">Pengguna <span class="req">*</span></label>
                    <select id="user_id" name="user_id" required class="@error('user_id') is-invalid @enderror">
                        <option value="">Pilih pengguna</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id', $member->user_id ?? null) == $user->id)>
                                {{ $user->name }} — {{ $user->email }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field">
                    <label for="phone_number">No. HP</label>
                    <input type="tel" id="phone_number" name="phone_number" inputmode="tel" autocomplete="tel"
                        value="{{ old('phone_number', $member->user->phone_number ?? '') }}"
                        placeholder="081234567890" class="@error('phone_number') is-invalid @enderror">
                    @error('phone_number')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field">
                    <label for="birth_date">Tanggal Lahir</label>
                    <input type="date" id="birth_date" name="birth_date"
                        value="{{ old('birth_date', optional($member?->birth_date)->format('Y-m-d')) }}"
                        class="@error('birth_date') is-invalid @enderror">
                    @error('birth_date')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </section>

        {{-- KARTU & DISKON --}}
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="scan-barcode"></i></span>
                <div>
                    <h3>Kartu & Diskon</h3>
                    <p>Kode dipindai kasir; diskon berlaku otomatis saat bayar.</p>
                </div>
            </div>

            <div class="p-grid">
                <div class="p-field p-span-2">
                    <label for="code">Kode Barcode</label>
                    <div class="p-input-group">
                        <input type="text" id="code" name="code" value="{{ old('code', $member->code ?? '') }}"
                            placeholder="Klik Generate" autocomplete="off"
                            class="p-mono @error('code') is-invalid @enderror">
                        <button type="button" class="p-btn p-btn-ghost" id="generateCodeBtn"
                            data-url="{{ route('members.generate-code') }}">
                            <i data-lucide="refresh-cw"></i>
                            <span>Generate</span>
                        </button>
                    </div>
                    @error('code')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field p-span-2">
                    <span class="p-field-label">Tipe Diskon</span>
                    <div class="p-segmented" role="radiogroup" aria-label="Tipe diskon">
                        <label>
                            <input type="radio" name="discount_type" value="percentage" @checked($discountType === 'percentage')>
                            <span><i data-lucide="percent"></i> Persentase</span>
                        </label>
                        <label>
                            <input type="radio" name="discount_type" value="fixed" @checked($discountType === 'fixed')>
                            <span><i data-lucide="banknote"></i> Nominal (Rp)</span>
                        </label>
                    </div>
                    @error('discount_type')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field p-span-2">
                    <label for="discount_value">Nilai Diskon</label>
                    <div class="p-affix">
                        <span class="is-prefix" data-affix-prefix @if ($discountType !== 'fixed') hidden @endif>Rp</span>
                        <input type="number" id="discount_value" name="discount_value" step="0.01" min="0"
                            inputmode="decimal" value="{{ old('discount_value', $member->discount_value ?? 0) }}"
                            class="has-prefix has-suffix @error('discount_value') is-invalid @enderror">
                        <span class="is-suffix" data-affix-suffix @if ($discountType === 'fixed') hidden @endif>%</span>
                    </div>
                    <small class="p-hint" id="discountHint">
                        {{ $discountType === 'fixed' ? 'Potongan rupiah per transaksi.' : 'Persen dari total belanja, mis. 5 = 5%.' }}
                    </small>
                    @error('discount_value')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </section>

    </div>

    {{-- ===================== KOLOM SAMPING ===================== --}}
    <aside class="p-form-side">

        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="stamp"></i></span>
                <div>
                    <h3>Stamp & Masa Berlaku</h3>
                    <p>Program loyalitas member.</p>
                </div>
            </div>

            <div class="p-grid">
                @if ($isEdit)
                    <div class="p-field">
                        <label for="stamp_count">Stamp terkumpul</label>
                        <input type="number" id="stamp_count" name="stamp_count" min="0" inputmode="numeric"
                            value="{{ old('stamp_count', $member->stamp_count) }}"
                            class="@error('stamp_count') is-invalid @enderror">
                        @error('stamp_count')
                            <small class="p-error">{{ $message }}</small>
                        @enderror
                    </div>
                @endif

                <div class="p-field {{ $isEdit ? '' : 'p-span-2' }}">
                    <label for="stamp_target">Target stamp</label>
                    <input type="number" id="stamp_target" name="stamp_target" min="1" inputmode="numeric"
                        value="{{ old('stamp_target', $member->stamp_target ?? 10) }}"
                        class="@error('stamp_target') is-invalid @enderror">
                    @error('stamp_target')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field p-span-2">
                    <label for="valid_from">Berlaku dari</label>
                    <input type="datetime-local" id="valid_from" name="valid_from"
                        value="{{ old('valid_from', optional($member?->valid_from)->format('Y-m-d\TH:i')) }}"
                        class="@error('valid_from') is-invalid @enderror">
                    @error('valid_from')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field p-span-2">
                    <label for="valid_until">Berlaku sampai <small class="p-hint">kosongkan = tanpa batas</small></label>
                    <input type="datetime-local" id="valid_until" name="valid_until"
                        value="{{ old('valid_until', optional($member?->valid_until)->format('Y-m-d\TH:i')) }}"
                        class="@error('valid_until') is-invalid @enderror">
                    @error('valid_until')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <div class="p-toggle-row is-separated">
                <div>
                    <strong>Barcode aktif</strong>
                    <small>Kartu nonaktif tidak bisa dipakai di kasir.</small>
                </div>
                <label class="p-switch">
                    {{-- checkbox saja (tanpa input hidden) → sama seperti versi lama --}}
                    <input type="checkbox" name="is_active" value="1" @checked($isActive)>
                    <span class="p-switch-track"></span>
                    <span class="sr-only">Barcode aktif</span>
                </label>
            </div>

            <div class="p-form-actions">
                <a href="{{ route('members.index') }}" class="p-btn p-btn-ghost">Batal</a>
                <button type="submit" class="p-btn p-btn-primary">
                    <i data-lucide="save"></i>
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Member' }}
                </button>
            </div>
        </section>

    </aside>
</div>

{{-- Bar simpan yang selalu terlihat di HP / tablet --}}
<div class="p-savebar">
    <a href="{{ route('members.index') }}" class="p-btn p-btn-ghost">Batal</a>
    <button type="submit" class="p-btn p-btn-primary">
        <i data-lucide="save"></i>
        {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Member' }}
    </button>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                /* ---------- Generate kode barcode ---------- */
                const btn = document.getElementById('generateCodeBtn');
                const codeInput = document.getElementById('code');

                btn?.addEventListener('click', async () => {
                    btn.disabled = true;
                    btn.classList.add('is-loading');

                    try {
                        const res = await fetch(btn.dataset.url, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) throw new Error();

                        const data = await res.json();
                        if (!data.code) throw new Error();

                        codeInput.value = data.code;
                        codeInput.dispatchEvent(new Event('input', { bubbles: true }));
                        codeInput.focus();
                    } catch {
                        window.showToast?.('danger', 'Gagal', 'Kode barcode tidak bisa dibuat. Coba lagi.');
                    } finally {
                        btn.disabled = false;
                        btn.classList.remove('is-loading');
                    }
                });

                /* ---------- Rp / % mengikuti tipe diskon ---------- */
                const prefix = document.querySelector('[data-affix-prefix]');
                const suffix = document.querySelector('[data-affix-suffix]');
                const hint = document.getElementById('discountHint');
                const value = document.getElementById('discount_value');

                const syncDiscount = () => {
                    const fixed = document.querySelector('input[name="discount_type"]:checked')?.value === 'fixed';
                    prefix.hidden = !fixed;
                    suffix.hidden = fixed;
                    value.max = fixed ? '' : '100';
                    value.step = fixed ? '1' : '0.01';
                    hint.textContent = fixed
                        ? 'Potongan rupiah per transaksi.'
                        : 'Persen dari total belanja, mis. 5 = 5%.';
                };

                document.querySelectorAll('input[name="discount_type"]').forEach((r) =>
                    r.addEventListener('change', syncDiscount));
                syncDiscount();
            })();
        </script>
    @endpush
@endonce

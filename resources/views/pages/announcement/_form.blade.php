@php
    $isEdit = isset($announcement);
    $a = $announcement ?? null;
    $activeOn = (bool) old('is_active', $a->is_active ?? true);
    $wasActive = (bool) ($a->is_active ?? false);
    $appName = ($appSetting ?? \App\Models\Setting::first())?->store_name ?? 'Garasi Part';
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
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="megaphone"></i></span>
                <div>
                    <h3>Isi Informasi</h3>
                    <p>Info atau promo yang tampil di aplikasi member.</p>
                </div>
            </div>

            <div class="p-grid">
                <div class="p-field p-span-2">
                    <label for="title">Judul <span class="req">*</span></label>
                    <input type="text" id="title" name="title" maxlength="255" required autocomplete="off"
                        value="{{ old('title', $a->title ?? '') }}" placeholder="Contoh: Diskon Oli 15% Akhir Pekan!"
                        class="@error('title') is-invalid @enderror">
                    @error('title')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field p-span-2">
                    <label for="message">
                        Pesan <span class="req">*</span>
                        <span class="p-counter" data-counter-for="message" data-limit="120"></span>
                    </label>
                    <textarea id="message" name="message" rows="6" required
                        placeholder="Contoh: Ganti oli di Garasi Part Sabtu–Minggu ini dapat potongan 15% untuk semua merek. Tunjukkan kartu member di kasir."
                        class="@error('message') is-invalid @enderror">{{ old('message', $a->message ?? '') }}</textarea>
                    <small class="p-hint">
                        120 karakter pertama muncul di notifikasi push. Pesan lengkap tetap tampil di aplikasi.
                    </small>
                    @error('message')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </section>

        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="image"></i></span>
                <div>
                    <h3>Gambar</h3>
                    <p>Opsional. Banner promo, foto produk, dsb.</p>
                </div>
            </div>

            <label class="p-upload is-wide @error('image') is-invalid @enderror" data-upload data-max-mb="2">
                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
                    aria-label="Pilih gambar">
                <img alt="Pratinjau gambar" width="640" height="360"
                    @if ($isEdit && $a->image) src="{{ $a->image_url }}" @else hidden @endif>
                <span class="p-upload-empty" @if ($isEdit && $a->image) hidden @endif>
                    <i data-lucide="image-plus"></i>
                    <strong>Pilih atau seret gambar</strong>
                    <small>PNG/JPG, maks. 2 MB · rasio 16:9 paling pas</small>
                </span>
            </label>
            <div class="p-upload-meta" hidden>
                <span data-upload-name></span>
                <button type="button" class="p-link" data-upload-reset>Batal</button>
            </div>
            @error('image')
                <small class="p-error">{{ $message }}</small>
            @enderror
        </section>
    </div>

    {{-- ===================== KOLOM SAMPING ===================== --}}
    <aside class="p-form-side">
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="smartphone"></i></span>
                <div>
                    <h3>Pratinjau</h3>
                    <p>Tampilan notifikasi di HP member.</p>
                </div>
            </div>

            <div class="p-push" aria-hidden="true">
                <div class="p-push-head">
                    <span class="p-push-app">{{ mb_substr($appName, 0, 1) }}</span>
                    <span>{{ $appName }}</span>
                    <span class="p-push-time">sekarang</span>
                </div>
                <strong class="p-push-title" id="pushTitle"></strong>
                <p class="p-push-body" id="pushBody"></p>
            </div>
        </section>

        <section class="p-card p-section">
            <div class="p-toggle-row">
                <div>
                    <strong>Aktifkan & kirim notifikasi</strong>
                    <small>
                        @if ($isEdit && $wasActive)
                            Sudah aktif. Menyimpan ulang tidak mengirim notifikasi lagi.
                        @elseif ($isEdit)
                            Saat ini nonaktif. Jika diaktifkan, notifikasi <b>baru</b> dikirim ke semua member.
                        @else
                            Notifikasi langsung terkirim ke semua member begitu disimpan.
                        @endif
                    </small>
                </div>
                <label class="p-switch">
                    <input type="checkbox" name="is_active" value="1" id="is_active" @checked($activeOn)>
                    <span class="p-switch-track"></span>
                    <span class="sr-only">Aktifkan informasi</span>
                </label>
            </div>

            <p class="p-send-note" id="sendNote" data-was-active="{{ $wasActive ? '1' : '0' }}" aria-live="polite"></p>

            <div class="p-form-actions">
                <a href="{{ route('announcements.index') }}" class="p-btn p-btn-ghost">Batal</a>
                <button type="submit" class="p-btn p-btn-primary">
                    <i data-lucide="send"></i>
                    <span data-submit-label>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</span>
                </button>
            </div>
        </section>
    </aside>
</div>

<div class="p-savebar">
    <a href="{{ route('announcements.index') }}" class="p-btn p-btn-ghost">Batal</a>
    <button type="submit" class="p-btn p-btn-primary">
        <i data-lucide="send"></i>
        <span data-submit-label>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}</span>
    </button>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const $ = (id) => document.getElementById(id);
                const title = $('title');
                const message = $('message');
                const active = $('is_active');
                const note = $('sendNote');
                const counter = document.querySelector('[data-counter-for="message"]');
                const wasActive = note.dataset.wasActive === '1';
                const isEdit = @json($isEdit);

                const render = () => {
                    const msg = message.value.trim().replace(/\s+/g, ' ');
                    $('pushTitle').textContent = title.value.trim() || 'Judul informasi';
                    $('pushBody').textContent = msg
                        ? (msg.length > 120 ? msg.slice(0, 120).trimEnd() + '…' : msg)
                        : 'Isi pesan akan tampil di sini.';

                    counter.textContent = `${msg.length}/120`;
                    counter.classList.toggle('is-over', msg.length > 120);
                };

                const status = () => {
                    const willSend = active.checked && !wasActive;
                    note.className = 'p-send-note ' + (willSend ? 'is-send' : active.checked ? 'is-on' : 'is-off');
                    note.textContent = willSend
                        ? 'Notifikasi akan dikirim ke semua member saat disimpan.'
                        : active.checked ? 'Tampil di aplikasi, tanpa notifikasi baru.' : 'Disimpan sebagai draf, tidak tampil di aplikasi.';

                    const label = willSend ? 'Simpan & Kirim' : (isEdit ? 'Simpan Perubahan' : 'Simpan Draf');
                    document.querySelectorAll('[data-submit-label]').forEach((el) => { el.textContent = label; });
                };

                title.addEventListener('input', render);
                message.addEventListener('input', render);
                active.addEventListener('change', status);
                render();
                status();
            })();
        </script>
    @endpush
@endonce

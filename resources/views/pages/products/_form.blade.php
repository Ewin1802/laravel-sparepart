@php
    $isEdit = isset($product);
    $p = $product ?? null;

    // Satuan untuk toko sparepart. Kalau produk lama memakai satuan lain,
    // satuan itu tetap ditambahkan supaya tidak berubah tanpa sengaja.
    $units = [
        'PCS' => 'PCS — per buah',
        'SET' => 'SET — satu set / paket',
        'PASANG' => 'PASANG — sepasang',
        'BOTOL' => 'BOTOL',
        'LITER' => 'LITER',
        'GALON' => 'GALON',
        'PAK' => 'PAK — isi beberapa',
        'METER' => 'METER — kabel, selang',
    ];
    $currentUnit = old('base_unit', $p->base_unit ?? 'PCS');
    if ($currentUnit && !array_key_exists($currentUnit, $units)) {
        $units = [$currentUnit => $currentUnit] + $units;
    }

    $stockValue = old('stock', $p ? rtrim(rtrim(number_format((float) $p->stock, 2, '.', ''), '0'), '.') : 0);
    $statusOn = (bool) old('status', $p->status ?? 1);
    $favoriteOn = (bool) old('is_favorite', $p->is_favorite ?? 0);
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

        {{-- INFORMASI --}}
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="package"></i></span>
                <div>
                    <h3>Informasi Produk</h3>
                    <p>Nama, kategori, dan keterangan part.</p>
                </div>
            </div>

            <div class="p-grid">
                <div class="p-field p-span-2">
                    <label for="name">Nama Produk <span class="req">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $p->name ?? '') }}"
                        placeholder="Contoh: Kampas Rem Depan Beat / Vario" required maxlength="255"
                        class="@error('name') is-invalid @enderror" autocomplete="off">
                    @error('name')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field p-span-2">
                    <label for="code">Kode Produk</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $p->code ?? '') }}"
                        placeholder="Contoh: part number atau kode internal toko" maxlength="50"
                        class="@error('code') is-invalid @enderror" autocomplete="off">
                    <small class="p-hint">Opsional. Tidak boleh sama dengan produk lain.</small>
                    @error('code')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field p-span-2">
                    <label for="category_id">Kategori <span class="req">*</span></label>
                    <select id="category_id" name="category_id" required
                        class="@error('category_id') is-invalid @enderror">
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $p->category_id ?? '') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field p-span-2">
                    <label for="description">Deskripsi <span class="req">*</span></label>
                    <textarea id="description" name="description" rows="5" required class="@error('description') is-invalid @enderror"
                        placeholder="Merek, kode part, dan cocok untuk kendaraan apa. Contoh: Merek AHM, kode 06455-KVB-901, cocok untuk Beat, Vario 125, Scoopy.">{{ old('description', $p->description ?? '') }}</textarea>
                    <small class="p-hint">Tulis merek, kode part, dan tipe kendaraan — pembeli bisa mencarinya di
                        website.</small>
                    @error('description')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </section>

        {{-- HARGA & STOK --}}
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="tags"></i></span>
                <div>
                    <h3>Harga & Stok</h3>
                    <p>Stok boleh desimal untuk satuan liter/meter.</p>
                </div>
            </div>

            <div class="p-grid">
                <div class="p-field p-span-2">
                    <label for="price_display">Harga Jual <span class="req">*</span></label>
                    <div class="p-money">
                        <span>Rp</span>
                        <input type="text" id="price_display" inputmode="numeric" autocomplete="off"
                            data-money="#price" required
                            value="{{ old('price', isset($p->price) ? (int) $p->price : '') }}" placeholder="0"
                            class="@error('price') is-invalid @enderror" aria-describedby="price-help">
                    </div>
                    {{-- Yang dikirim ke server: angka mentah tanpa titik --}}
                    <input type="hidden" name="price" id="price"
                        value="{{ old('price', isset($p->price) ? (int) $p->price : '') }}">
                    @error('price')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field">
                    <label for="stock">{{ $isEdit ? 'Stok' : 'Stok Awal' }} <span class="req">*</span></label>
                    <input type="number" id="stock" name="stock" value="{{ $stockValue }}" min="0"
                        step="any" inputmode="decimal" required class="@error('stock') is-invalid @enderror">
                    @error('stock')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field">
                    <label for="base_unit">Satuan <span class="req">*</span></label>
                    <select id="base_unit" name="base_unit" required class="@error('base_unit') is-invalid @enderror">
                        @foreach ($units as $value => $label)
                            <option value="{{ $value }}" @selected($currentUnit === $value)>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('base_unit')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </section>

    </div>

    {{-- ===================== KOLOM SAMPING ===================== --}}
    <aside class="p-form-side">

        {{-- FOTO --}}
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="image"></i></span>
                <div>
                    <h3>Foto Produk</h3>
                    <p>{{ $isEdit ? 'Kosongkan jika tidak diganti.' : 'Opsional, disarankan.' }}</p>
                </div>
            </div>

            <label class="p-upload @error('image') is-invalid @enderror" data-upload data-max-mb="2">
                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp">

                <img alt="Pratinjau foto produk"
                    @if ($isEdit && $p->image) src="{{ asset($p->image) }}" @else hidden @endif>

                <span class="p-upload-empty" @if ($isEdit && $p->image) hidden @endif>
                    <i data-lucide="image-plus"></i>
                    <strong>Pilih atau seret foto</strong>
                    <small>JPG, PNG, atau WebP</small>
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

        {{-- PENGATURAN --}}
        <section class="p-card p-section">
            <div class="p-toggle-row">
                <div>
                    <strong>Tampilkan di toko</strong>
                    <small>Produk nonaktif tidak muncul di website & kasir.</small>
                </div>
                <label class="p-switch">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" @checked($statusOn)>
                    <span class="p-switch-track"></span>
                    <span class="sr-only">Status aktif</span>
                </label>
            </div>

            <div class="p-toggle-row">
                <div>
                    <strong>Tandai terlaris</strong>
                    <small>Muncul dengan label "Terlaris" di website.</small>
                </div>
                <label class="p-switch">
                    <input type="hidden" name="is_favorite" value="0">
                    <input type="checkbox" name="is_favorite" value="1" @checked($favoriteOn)>
                    <span class="p-switch-track"></span>
                    <span class="sr-only">Tandai terlaris</span>
                </label>
            </div>

            <div class="p-form-actions">
                <a href="{{ route('products.index') }}" class="p-btn p-btn-ghost">Batal</a>
                <button type="submit" class="p-btn p-btn-primary">
                    <i data-lucide="save"></i>
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' }}
                </button>
            </div>
        </section>

    </aside>
</div>

{{-- Bar simpan yang selalu terlihat di HP / tablet --}}
<div class="p-savebar">
    <a href="{{ route('products.index') }}" class="p-btn p-btn-ghost">Batal</a>
    <button type="submit" class="p-btn p-btn-primary">
        <i data-lucide="save"></i>
        {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' }}
    </button>
</div>

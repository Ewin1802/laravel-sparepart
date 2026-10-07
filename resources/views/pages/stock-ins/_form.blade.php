@php
    $isEdit = isset($stockIn);
    $si = $stockIn ?? null;

    // baris awal: isian lama (validasi gagal) → isi nota (edit) → produk dari ?product_id= (tambah)
    $initialItems = old('items');
    if ($initialItems === null) {
        if ($isEdit) {
            $initialItems = $si->items->map(fn($i) => [
                'product_id' => $i->product_id,
                'quantity' => $i->quantity,
                'cost_price' => $i->cost_price,
            ])->all();
        } elseif (request()->filled('product_id')) {
            $initialItems = [['product_id' => (int) request('product_id'), 'quantity' => '', 'cost_price' => '']];
        } else {
            $initialItems = [];
        }
    }

    $selectedSupplier = old('supplier_id', $si->supplier_id ?? request('supplier_id'));
    $receivedAt = old('received_at', $isEdit ? $si->received_at->format('Y-m-d') : now()->toDateString());
    $paymentStatus = old('payment_status', $si->payment_status ?? 'paid');
    $dueDate = old('due_date', $isEdit && $si->due_date ? $si->due_date->format('Y-m-d') : '');
@endphp

@if ($errors->any())
    <div class="p-notice is-danger" role="alert">
        <i data-lucide="circle-alert"></i>
        <div>
            <strong>Stok masuk belum tersimpan.</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    </div>
@endif

@if ($suppliers->isEmpty())
    <div class="p-notice is-warning" role="status">
        <i data-lucide="truck"></i>
        <div>
            <strong>Belum ada supplier.</strong>
            <span>Tambahkan dulu di menu <a href="{{ route('suppliers.create') }}" class="p-link">Supplier</a>.</span>
        </div>
    </div>
@endif

<div class="p-form-layout">

    {{-- ===================== KOLOM UTAMA ===================== --}}
    <div class="p-form-main">

        {{-- NOTA --}}
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="file-text"></i></span>
                <div>
                    <h3>Nota Pembelian</h3>
                    <p>Dari siapa dan kapan barang diterima.</p>
                </div>
            </div>

            <div class="p-grid">
                <div class="p-field p-span-2">
                    <label for="supplier_id">Supplier <span class="req">*</span></label>
                    <select id="supplier_id" name="supplier_id" required
                        class="@error('supplier_id') is-invalid @enderror">
                        <option value="">Pilih supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected($selectedSupplier == $supplier->id)>
                                {{ $supplier->name }}{{ $supplier->is_active ? '' : ' (nonaktif)' }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field">
                    <label for="received_at">Tanggal Terima <span class="req">*</span></label>
                    <input type="date" id="received_at" name="received_at" required value="{{ $receivedAt }}"
                        max="{{ now()->toDateString() }}" class="@error('received_at') is-invalid @enderror">
                    @error('received_at')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="p-field">
                    <label for="invoice_number">No. Nota / Faktur</label>
                    <input type="text" id="invoice_number" name="invoice_number" maxlength="100" autocomplete="off"
                        value="{{ old('invoice_number', $si->invoice_number ?? '') }}" placeholder="Opsional"
                        class="p-mono @error('invoice_number') is-invalid @enderror">
                    @error('invoice_number')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>

                {{-- PEMBAYARAN --}}
                <fieldset class="p-field p-fieldset p-span-2">
                    <legend class="p-field-label">Pembayaran <span class="req">*</span></legend>
                    <div class="p-segmented is-tones">
                        <label class="tone-green">
                            <input type="radio" name="payment_status" value="paid" required
                                @checked($paymentStatus === 'paid')>
                            <span><i data-lucide="circle-check"></i> Lunas</span>
                        </label>
                        <label class="tone-amber">
                            <input type="radio" name="payment_status" value="unpaid"
                                @checked($paymentStatus === 'unpaid')>
                            <span><i data-lucide="clock"></i> Tempo (bayar nanti)</span>
                        </label>
                    </div>
                    <small class="p-hint" id="paymentHint"></small>
                    @error('payment_status')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </fieldset>

                <div class="p-field p-span-2" id="dueDateField" @if ($paymentStatus !== 'unpaid') hidden @endif>
                    <label for="due_date">Jatuh Tempo</label>
                    <input type="date" id="due_date" name="due_date" value="{{ $dueDate }}" min="{{ $receivedAt }}"
                        class="@error('due_date') is-invalid @enderror">
                    <div class="p-chips p-chips-inline" aria-label="Isi cepat jatuh tempo">
                        @foreach ([7, 14, 30] as $days)
                            <button type="button" class="p-chip" data-due-days="{{ $days }}">{{ $days }} hari</button>
                        @endforeach
                    </div>
                    @error('due_date')
                        <small class="p-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </section>

        {{-- BARANG --}}
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="package-plus"></i></span>
                <div>
                    <h3>Barang Masuk</h3>
                    <p>Cari produk yang sudah ada, lalu isi jumlah dan harga belinya.</p>
                </div>
            </div>

            {{-- pencarian produk --}}
            <div class="p-picker">
                <label class="p-search">
                    <i data-lucide="search"></i>
                    <input type="search" id="productSearch" placeholder="Ketik nama produk, lalu Enter..."
                        autocomplete="off" role="combobox" aria-expanded="false" aria-controls="productResults"
                        aria-label="Cari produk untuk ditambahkan">
                </label>
                <ul class="p-picker-list" id="productResults" role="listbox" hidden></ul>
            </div>

            @error('items')
                <small class="p-error">{{ $message }}</small>
            @enderror

            <div class="p-lines" id="lines"></div>

            <div class="p-empty p-lines-empty" id="linesEmpty">
                <span class="p-empty-icon"><i data-lucide="package-search"></i></span>
                <h4>Belum ada barang</h4>
                <p>Cari produk di kotak di atas untuk menambahkannya ke nota ini.</p>
            </div>

            <p class="p-hint p-lines-note">
                Produk belum terdaftar?
                <a href="{{ route('products.create') }}" class="p-link" target="_blank" rel="noopener">Tambah produk baru</a>
                dulu, lalu muat ulang halaman ini.
            </p>
        </section>

        <section class="p-card p-section">
            <div class="p-field">
                <label for="notes">Catatan</label>
                <textarea id="notes" name="notes" rows="2" maxlength="1000"
                    placeholder="Opsional, mis. bayar tempo 14 hari, 1 dus penyok."
                    class="@error('notes') is-invalid @enderror">{{ old('notes', $si->notes ?? '') }}</textarea>
                @error('notes')
                    <small class="p-error">{{ $message }}</small>
                @enderror
            </div>
        </section>
    </div>

    {{-- ===================== KOLOM SAMPING ===================== --}}
    <aside class="p-form-side">
        <section class="p-card p-section">
            <div class="p-section-head">
                <span class="p-section-icon"><i data-lucide="calculator"></i></span>
                <div>
                    <h3>Ringkasan</h3>
                    <p>Dihitung otomatis.</p>
                </div>
            </div>

            <dl class="p-sim" aria-live="polite">
                <div><dt>Jenis barang</dt><dd id="sumLines">0</dd></div>
                <div><dt>Total jumlah</dt><dd id="sumQty">0</dd></div>
                <div class="is-total"><dt>Total pembelian</dt><dd id="sumTotal">Rp 0</dd></div>
            </dl>

            @if ($isEdit)
                <p class="p-send-note is-off">Saat disimpan, stok lama dikembalikan dulu lalu dihitung ulang dari isi nota ini.</p>
            @else
                <p class="p-send-note is-on">Saat disimpan, stok tiap produk langsung bertambah.</p>
            @endif

            <div class="p-form-actions">
                <a href="{{ route('stock-ins.index') }}" class="p-btn p-btn-ghost">Batal</a>
                <button type="submit" class="p-btn p-btn-primary">
                    <i data-lucide="save"></i>
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Stok Masuk' }}
                </button>
            </div>
        </section>
    </aside>
</div>

<div class="p-savebar">
    <span class="p-savebar-total">
        <small>Total</small>
        <strong id="sumTotalMobile">Rp 0</strong>
    </span>
    <button type="submit" class="p-btn p-btn-primary">
        <i data-lucide="save"></i>
        Simpan
    </button>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const PRODUCTS = @json($products);
                const PRICES = @json($priceMap); // { productId: [{id, name, price}] } urut termurah dulu
                const INITIAL = @json(array_values($initialItems));

                const $ = (id) => document.getElementById(id);
                const lines = $('lines');
                const search = $('productSearch');
                const results = $('productResults');
                const supplier = $('supplier_id');

                const byId = new Map(PRODUCTS.map((p) => [Number(p.id), p]));
                PRODUCTS.forEach((p) => { p._text = p.name.toLowerCase(); });

                const rp = (n) => 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');
                const num = (n) => (Number(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
                const digits = (s) => String(s).replace(/\D/g, '').replace(/^0+(?=\d)/, '');
                const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

                const supplierPrice = (pid) => (PRICES[pid] || []).find((s) => Number(s.id) === Number(supplier.value));

                /* ---------- baris barang ---------- */
                const addLine = (pid, qty = '', cost = null) => {
                    pid = Number(pid);
                    const p = byId.get(pid);
                    if (!p) return;

                    const existing = lines.querySelector(`[data-line="${pid}"]`);
                    if (existing) {
                        existing.querySelector('[data-qty]').focus();
                        existing.classList.add('is-flash');
                        setTimeout(() => existing.classList.remove('is-flash'), 700);
                        return;
                    }

                    // harga awal: harga terakhir supplier ini → harga beli terakhir produk
                    const auto = cost === null || cost === '';
                    if (auto) cost = supplierPrice(pid)?.price ?? p.cost_price ?? '';
                    const raw = cost === '' || cost === null ? '' : String(Math.round(Number(cost)));

                    const unit = esc(p.base_unit || 'PCS');
                    const row = document.createElement('div');
                    row.className = 'p-line';
                    row.dataset.line = pid;
                    row.innerHTML = `
                        <input type="hidden" name="items[${pid}][product_id]" value="${pid}">
                        <div class="p-line-main">
                            <strong>${esc(p.name)}</strong>
                            <small>Stok sekarang ${num(p.stock)} ${unit} · Harga jual ${rp(p.price)}</small>
                            <small class="p-line-hint" data-hint></small>
                        </div>
                        <label class="p-line-field">
                            <span>Jumlah (${unit})</span>
                            <input type="number" name="items[${pid}][quantity]" data-qty min="0.01" step="0.01"
                                inputmode="decimal" required value="${esc(qty)}" placeholder="0">
                        </label>
                        <label class="p-line-field">
                            <span>Harga beli / ${unit}</span>
                            <span class="p-affix">
                                <span class="is-prefix">Rp</span>
                                <input type="text" class="has-prefix" data-cost data-money="#cost-${pid}" inputmode="numeric"
                                    autocomplete="off" required placeholder="0"
                                    value="${raw ? Number(raw).toLocaleString('id-ID') : ''}">
                            </span>
                            <input type="hidden" name="items[${pid}][cost_price]" id="cost-${pid}" value="${raw}">
                        </label>
                        <div class="p-line-total">
                            <span>Subtotal</span>
                            <strong data-sub>Rp 0</strong>
                        </div>
                        <button type="button" class="p-icon-btn is-danger" data-remove title="Hapus baris"
                            aria-label="Hapus ${esc(p.name)}">
                            <i data-lucide="x"></i>
                        </button>`;

                    row.dataset.auto = auto ? '1' : '0';
                    lines.appendChild(row);
                    window.refreshIcons?.();
                    updateRow(row);
                    return row;
                };

                const updateRow = (row) => {
                    const pid = Number(row.dataset.line);
                    const p = byId.get(pid);
                    const qty = Number(row.querySelector('[data-qty]').value) || 0;
                    const rawCost = row.querySelector(`#cost-${pid}`).value;
                    const cost = Number(rawCost) || 0;

                    row.querySelector('[data-sub]').textContent = rp(qty * cost);

                    // petunjuk harga: bandingkan dengan supplier lain
                    const hint = row.querySelector('[data-hint]');
                    const list = PRICES[pid] || [];
                    const cheapest = list[0];
                    let cls = '';
                    let text = 'Belum ada riwayat harga beli.';

                    if (cheapest) {
                        const other = Number(cheapest.id) !== Number(supplier.value);
                        text = `Termurah: ${cheapest.name} · ${rp(cheapest.price)}`;

                        if (rawCost !== '' && other && cost > cheapest.price) {
                            cls = 'is-warn';
                            text = `Lebih mahal ${rp(cost - cheapest.price)} dari ${cheapest.name} (${rp(cheapest.price)})`;
                        } else if (rawCost !== '' && cost <= cheapest.price) {
                            cls = 'is-ok';
                            text = cost < cheapest.price
                                ? `Lebih murah ${rp(cheapest.price - cost)} dari harga termurah sebelumnya`
                                : 'Sama dengan harga termurah';
                        }
                    }

                    if (rawCost !== '' && cost >= Number(p.price)) {
                        cls = 'is-warn';
                        text = `Harga beli sama / di atas harga jual (${rp(p.price)})`;
                    }

                    hint.className = 'p-line-hint ' + cls;
                    hint.textContent = text;
                };

                const updateTotals = () => {
                    const rows = [...lines.children];
                    let qty = 0;
                    let total = 0;

                    rows.forEach((row) => {
                        const q = Number(row.querySelector('[data-qty]').value) || 0;
                        const c = Number(row.querySelector('input[type=hidden][id^="cost-"]').value) || 0;
                        qty += q;
                        total += q * c;
                    });

                    $('sumLines').textContent = rows.length;
                    $('sumQty').textContent = num(qty);
                    $('sumTotal').textContent = rp(total);
                    $('sumTotalMobile').textContent = rp(total);
                    $('linesEmpty').hidden = rows.length > 0;

                    // dipakai form guard (app.js): nota tanpa barang tidak bisa disimpan
                    search.setCustomValidity(rows.length ? '' : 'Tambahkan minimal satu barang.');
                };

                lines.addEventListener('input', (e) => {
                    const row = e.target.closest('.p-line');
                    if (!row) return;

                    if (e.target.matches('[data-cost]')) {
                        const input = e.target;
                        const fromEnd = input.value.length - input.selectionStart;
                        const raw = digits(input.value);
                        row.querySelector('input[type=hidden][id^="cost-"]').value = raw;
                        input.value = raw ? Number(raw).toLocaleString('id-ID') : '';
                        const pos = Math.max(0, input.value.length - fromEnd);
                        input.setSelectionRange(pos, pos);
                        input.setCustomValidity('');
                        row.dataset.auto = '0';
                    }

                    updateRow(row);
                    updateTotals();
                });

                lines.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-remove]');
                    if (!btn) return;
                    btn.closest('.p-line').remove();
                    updateTotals();
                    search.focus();
                });

                // ganti supplier → harga yang belum diubah manual ikut harga terakhir supplier itu
                supplier.addEventListener('change', () => {
                    [...lines.children].forEach((row) => {
                        const pid = Number(row.dataset.line);
                        const mine = supplierPrice(pid);

                        if (row.dataset.auto === '1' && mine) {
                            const raw = String(Math.round(mine.price));
                            row.querySelector(`#cost-${pid}`).value = raw;
                            row.querySelector('[data-cost]').value = Number(raw).toLocaleString('id-ID');
                        }
                        updateRow(row);
                    });
                    updateTotals();
                });

                /* ---------- pencarian produk ---------- */
                let active = -1;
                let found = [];

                const closeResults = () => {
                    results.hidden = true;
                    search.setAttribute('aria-expanded', 'false');
                    active = -1;
                };

                const renderResults = () => {
                    const terms = search.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
                    if (!terms.length) return closeResults();

                    found = PRODUCTS.filter((p) => terms.every((t) => p._text.includes(t))).slice(0, 8);
                    active = found.length ? 0 : -1;

                    results.innerHTML = found.length
                        ? found.map((p, i) => {
                            const added = lines.querySelector(`[data-line="${p.id}"]`);
                            const cheapest = (PRICES[p.id] || [])[0];
                            return `<li role="option" data-pick="${p.id}" class="${i === 0 ? 'is-active' : ''}" aria-selected="${i === 0}">
                                <span><strong>${esc(p.name)}</strong>
                                <small>Stok ${num(p.stock)} ${esc(p.base_unit || 'PCS')}${cheapest ? ` · termurah ${rp(cheapest.price)} (${esc(cheapest.name)})` : ''}</small></span>
                                <em>${added ? 'Sudah ada' : 'Tambah'}</em></li>`;
                        }).join('')
                        : '<li class="is-none">Produk tidak ditemukan.</li>';

                    results.hidden = false;
                    search.setAttribute('aria-expanded', 'true');
                };

                const pick = (pid) => {
                    const row = addLine(pid);
                    search.value = '';
                    closeResults();
                    updateTotals();
                    row?.querySelector('[data-qty]').focus();
                };

                search.addEventListener('input', renderResults);
                search.addEventListener('focus', renderResults);

                search.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault(); // jangan submit form
                        if (found[active]) pick(found[active].id);
                        return;
                    }
                    if (e.key === 'Escape') return closeResults();
                    if (results.hidden || !found.length) return;

                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        active = (active + (e.key === 'ArrowDown' ? 1 : -1) + found.length) % found.length;
                        [...results.children].forEach((li, i) => {
                            li.classList.toggle('is-active', i === active);
                            li.setAttribute('aria-selected', i === active);
                        });
                    }
                });

                results.addEventListener('mousedown', (e) => {
                    const li = e.target.closest('[data-pick]');
                    if (!li) return;
                    e.preventDefault(); // fokus tidak lepas dari kotak cari
                    pick(li.dataset.pick);
                });

                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.p-picker')) closeResults();
                });

                /* ---------- pembayaran: lunas / tempo ---------- */
                const dueField = $('dueDateField');
                const due = $('due_date');
                const received = $('received_at');

                const addDays = (dateStr, days) => {
                    const d = new Date(dateStr + 'T00:00:00');
                    d.setDate(d.getDate() + days);
                    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
                };

                const syncPayment = () => {
                    const unpaid = document.querySelector('input[name="payment_status"]:checked')?.value === 'unpaid';
                    dueField.hidden = !unpaid;
                    due.min = received.value;

                    if (unpaid && !due.value && received.value) due.value = addDays(received.value, 14);
                    if (!unpaid) due.value = '';

                    $('paymentHint').textContent = unpaid
                        ? 'Dicatat sebagai hutang ke supplier. Uang baru dihitung keluar saat nota ditandai lunas.'
                        : 'Uang dihitung keluar pada tanggal terima.';
                };

                document.querySelectorAll('input[name="payment_status"]').forEach((r) => r.addEventListener('change', syncPayment));
                received.addEventListener('change', () => { due.min = received.value; });

                dueField.addEventListener('click', (e) => {
                    const chip = e.target.closest('[data-due-days]');
                    if (!chip || !received.value) return;
                    due.value = addDays(received.value, Number(chip.dataset.dueDays));
                    due.dispatchEvent(new Event('input', { bubbles: true }));
                });

                syncPayment();

                /* ---------- mulai ---------- */
                INITIAL.forEach((item) => addLine(item.product_id, item.quantity ?? '', item.cost_price ?? ''));
                updateTotals();
            })();
        </script>
    @endpush
@endonce

@extends('layouts.app')

@section('title', 'Buka Kemasan')

@section('content')
    @php
        $rp = fn($n) => "Rp\u{00A0}" . number_format((float) $n, 0, ',', '.');
        $qty = function ($v) {
            $v = (float) $v;
            return fmod($v, 1.0) == 0.0
                ? number_format($v, 0, ',', '.')
                : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
        };
        $productData = $products->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'unit' => $p->base_unit ?: 'PCS',
            'stock' => (float) $p->stock,
            'cost' => $p->cost_price !== null ? (float) $p->cost_price : null,
            'active' => (bool) $p->status,
        ])->values();
        $old = [
            'source' => old('source_product_id', request('source')),
            'target' => old('target_product_id'),
            'qty' => old('source_qty', 1),
            'ratio' => old('ratio'),
            'note' => old('note'),
        ];
    @endphp

    <div class="crud-page">

        {{-- ===================== HEADER ===================== --}}
        <div class="p-header">
            <div>
                <span class="p-eyebrow">Stok &amp; Pembelian</span>
                <h1>Buka Kemasan</h1>
                <p>Pecah stok kemasan (galon, pail, rol) menjadi stok eceran (liter, kg, meter) — stok kemasan berkurang,
                    stok eceran bertambah, harga beli per eceran dihitung otomatis.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="p-notice is-danger" role="alert">
                <i data-lucide="circle-alert"></i>
                <div>
                    <strong>Belum tersimpan.</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        {{-- ===================== RINGKASAN ===================== --}}
        <section class="p-stats" aria-label="Ringkasan buka kemasan">
            <div class="p-stat is-half">
                <span class="p-stat-label"><i data-lucide="package-open"></i> Dibuka bulan ini</span>
                <strong class="p-stat-value">{{ number_format($summary->month_count, 0, ',', '.') }}×</strong>
                <span class="p-stat-sub">nilai modal dipindah {{ $rp($summary->month_value) }}</span>
            </div>
            <div class="p-stat is-half">
                <span class="p-stat-label"><i data-lucide="link-2"></i> Pasangan tersimpan</span>
                <strong class="p-stat-value">{{ number_format($summary->pairs, 0, ',', '.') }}</strong>
                <span class="p-stat-sub">kemasan → eceran terisi otomatis berikutnya</span>
            </div>
        </section>

        {{-- ===================== FORM ===================== --}}
        <form method="POST" action="{{ route('stock-conversions.store') }}" id="convForm" autocomplete="off">
            @csrf
            <div class="p-form-layout">
                <div class="p-form-main">
                    <section class="p-card p-section">
                        <div class="p-section-head">
                            <span class="p-section-icon"><i data-lucide="package"></i></span>
                            <div>
                                <h3>1. Kemasan yang dibuka</h3>
                                <p>Produk yang stoknya berkurang, mis. Oli Jumbo Hydro 68 Galon 20 ltr.</p>
                            </div>
                        </div>

                        <div class="p-grid">
                            <div class="p-field p-span-2">
                                <label for="source_product_id">Produk kemasan <span class="req">*</span></label>
                                <input type="search" class="cv-filter" data-for="source_product_id"
                                    placeholder="Cari nama / kode produk..." aria-label="Cari produk kemasan">
                                <select id="source_product_id" name="source_product_id" required
                                    class="@error('source_product_id') is-invalid @enderror">
                                    <option value="">— pilih produk —</option>
                                </select>
                                <small class="p-hint" id="sourceInfo"></small>
                            </div>

                            <div class="p-field">
                                <label for="source_qty">Jumlah dibuka <span class="req">*</span></label>
                                <div class="cv-unit-input">
                                    <input type="number" id="source_qty" name="source_qty" min="0.01" step="any"
                                        value="{{ $old['qty'] }}" required
                                        class="@error('source_qty') is-invalid @enderror">
                                    <span id="sourceUnit">PCS</span>
                                </div>
                            </div>

                            <div class="p-field">
                                <label for="ratio">Isi per kemasan <span class="req">*</span></label>
                                <div class="cv-unit-input">
                                    <input type="number" id="ratio" name="ratio" min="0.001" step="any"
                                        value="{{ $old['ratio'] }}" placeholder="mis. 20" required
                                        class="@error('ratio') is-invalid @enderror">
                                    <span id="ratioUnit">eceran</span>
                                </div>
                                <small class="p-hint">Berapa satuan eceran dalam 1 kemasan.</small>
                            </div>
                        </div>
                    </section>

                    <section class="p-card p-section">
                        <div class="p-section-head">
                            <span class="p-section-icon"><i data-lucide="droplet"></i></span>
                            <div>
                                <h3>2. Produk eceran</h3>
                                <p>Produk yang stoknya bertambah, satuannya LITER / KG / METER.</p>
                            </div>
                        </div>

                        <div class="p-grid">
                            <div class="p-field p-span-2">
                                <label for="target_product_id">Produk eceran <span class="req">*</span></label>
                                <input type="search" class="cv-filter" data-for="target_product_id"
                                    placeholder="Cari nama / kode produk..." aria-label="Cari produk eceran">
                                <select id="target_product_id" name="target_product_id" required
                                    class="@error('target_product_id') is-invalid @enderror">
                                    <option value="">— pilih produk —</option>
                                </select>
                                <small class="p-hint" id="targetInfo">
                                    Belum ada produk ecerannya? <a href="{{ route('products.create') }}" class="p-link">Tambah produk</a>
                                    dengan satuan LITER / KG / METER dan stok awal 0.
                                </small>
                            </div>

                            <div class="p-field p-span-2">
                                <label for="note">Catatan</label>
                                <input type="text" id="note" name="note" maxlength="255" value="{{ $old['note'] }}"
                                    placeholder="Opsional, mis. galon dibuka untuk eceran bengkel">
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="p-form-side">
                    <section class="p-card p-section">
                        <div class="p-section-head">
                            <span class="p-section-icon"><i data-lucide="calculator"></i></span>
                            <div>
                                <h3>Hasil</h3>
                                <p>Dihitung otomatis.</p>
                            </div>
                        </div>

                        <dl class="p-sim" aria-live="polite">
                            <div><dt>Stok kemasan</dt><dd id="simSource">–</dd></div>
                            <div><dt>Stok eceran</dt><dd id="simTarget">–</dd></div>
                            <div><dt>Harga beli eceran</dt><dd id="simCost">–</dd></div>
                            <div class="is-total"><dt>Nilai modal dipindah</dt><dd id="simValue">Rp 0</dd></div>
                        </dl>

                        <p class="p-send-note is-on" id="simNote">Pilih produk kemasan dan eceran.</p>

                        <div class="p-form-actions">
                            <button type="submit" class="p-btn p-btn-primary" id="convSubmit">
                                <i data-lucide="package-open"></i>
                                Buka Kemasan
                            </button>
                        </div>
                    </section>
                </aside>
            </div>
        </form>

        {{-- ===================== RIWAYAT ===================== --}}
        <section class="p-card" style="margin-top: 18px">
            <div class="p-section-head" style="padding: 18px 20px 0">
                <span class="p-section-icon"><i data-lucide="history"></i></span>
                <div>
                    <h3>Riwayat</h3>
                    <p>Batalkan jika salah pilih — stok dikembalikan selama eceran belum terjual.</p>
                </div>
            </div>
            <div class="p-table-wrap">
                <table class="p-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Kemasan dibuka</th>
                            <th>Menjadi eceran</th>
                            <th class="text-end">Harga beli / eceran</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($conversions as $c)
                            <tr @class(['cv-reverted' => $c->isReverted()])>
                                <td data-label="Waktu">
                                    {{ $c->created_at->translatedFormat('d M Y H:i') }}
                                    <small class="p-sub">{{ $c->user->name ?? '-' }}</small>
                                </td>
                                <td data-label="Kemasan">
                                    <strong>{{ $qty($c->source_qty) }} {{ $c->source->base_unit ?? 'PCS' }}</strong>
                                    <small class="p-sub">{{ $c->source->name ?? 'Produk dihapus' }}</small>
                                </td>
                                <td data-label="Eceran">
                                    <strong>+{{ $qty($c->target_qty) }} {{ $c->target->base_unit ?? '' }}</strong>
                                    <small class="p-sub">{{ $c->target->name ?? 'Produk dihapus' }} · isi {{ $qty($c->ratio) }}</small>
                                    @if ($c->note)
                                        <small class="p-sub">{{ $c->note }}</small>
                                    @endif
                                </td>
                                <td class="text-end" data-label="Harga beli">
                                    {{ $c->unit_cost !== null ? $rp($c->unit_cost) : '–' }}
                                </td>
                                <td class="cell-actions">
                                    @if ($c->isReverted())
                                        <span class="p-stock is-mid">Dibatalkan</span>
                                    @else
                                        <form method="POST" action="{{ route('stock-conversions.destroy', $c->id) }}"
                                            onsubmit="return confirm('Batalkan buka kemasan ini? Stok kedua produk dikembalikan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-icon-btn is-danger" title="Batalkan">
                                                <i data-lucide="undo-2"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="p-empty-row">
                                <td colspan="5">
                                    <div class="p-empty">
                                        <span class="p-empty-icon"><i data-lucide="package-open"></i></span>
                                        <h4>Belum ada kemasan yang dibuka</h4>
                                        <p>Contoh: 1 GALON oli isi 20 → 20 LITER oli eceran.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($conversions->hasPages())
                <div style="padding: 0 20px 18px">{{ $conversions->links() }}</div>
            @endif
        </section>
    </div>

    <style>
        .cv-filter { margin-bottom: 8px; }
        .cv-unit-input { display: flex; align-items: stretch; }
        .cv-unit-input input { flex: 1; min-width: 0; border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; }
        .cv-unit-input span {
            display: inline-flex; align-items: center; padding: 0 12px; font-size: 12px; font-weight: 700;
            border: 1px solid var(--p-stroke, #e5e7eb); border-left: 0; border-radius: 0 12px 12px 0;
            background: var(--p-soft, #f4f5f7); color: #4b5563; white-space: nowrap;
        }
        tr.cv-reverted td { opacity: .5; }
        tr.cv-reverted td.cell-actions { opacity: 1; }
        #simNote.is-warn { color: #b45309; }
    </style>
@endsection

@push('scripts')
    <script>
        (() => {
            const PRODUCTS = @json($productData);
            const PAIRS = @json($lastPairs);
            const OLD = @json($old);
            const byId = new Map(PRODUCTS.map((p) => [String(p.id), p]));

            const $ = (id) => document.getElementById(id);
            const src = $('source_product_id'), tgt = $('target_product_id');
            const qty = $('source_qty'), ratio = $('ratio');

            const MEASURE = ['LITER', 'LTR', 'L', 'ML', 'METER', 'M', 'CM', 'KG', 'KILO', 'GR', 'GRAM', 'ONS'];
            const num = (n) => (Number(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
            const rp = (n) => 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');

            const fill = (select, filter = '', keep = select.value, preferMeasure = false) => {
                const f = filter.trim().toLowerCase();
                const list = PRODUCTS.filter((p) => !f || p.name.toLowerCase().includes(f) || (p.code || '').toLowerCase().includes(f));
                // produk eceran: satuan ukuran ditaruh di atas
                if (preferMeasure) list.sort((a, b) => (MEASURE.includes(b.unit) - MEASURE.includes(a.unit)) || a.name.localeCompare(b.name));
                select.innerHTML = '<option value="">— pilih produk —</option>' + list.map((p) =>
                    `<option value="${p.id}">${p.name.replace(/</g, '&lt;')} · ${num(p.stock)} ${p.unit}${p.active ? '' : ' (nonaktif)'}</option>`
                ).join('');
                if (keep && list.some((p) => String(p.id) === String(keep))) select.value = keep;
            };

            document.querySelectorAll('.cv-filter').forEach((input) => {
                input.addEventListener('input', () => {
                    const sel = $(input.dataset.for);
                    fill(sel, input.value, sel.value, sel === tgt);
                    update();
                });
            });

            const update = () => {
                const s = byId.get(src.value), t = byId.get(tgt.value);
                const q = Number(qty.value) || 0, r = Number(ratio.value) || 0;
                $('sourceUnit').textContent = s ? s.unit : 'PCS';
                $('ratioUnit').textContent = t ? t.unit : 'eceran';
                $('sourceInfo').textContent = s
                    ? `Stok ${num(s.stock)} ${s.unit} · harga beli ${s.cost ? rp(s.cost) : 'belum ada'}`
                    : '';

                const note = $('simNote');
                note.classList.remove('is-warn');
                if (!s || !t) {
                    ['simSource', 'simTarget', 'simCost'].forEach((id) => $(id).textContent = '–');
                    $('simValue').textContent = 'Rp 0';
                    note.textContent = 'Pilih produk kemasan dan eceran.';
                    return;
                }
                const add = q * r;
                const unitCost = s.cost && r > 0 ? s.cost / r : null;
                $('simSource').textContent = `${num(s.stock)} → ${num(s.stock - q)} ${s.unit}`;
                $('simTarget').textContent = `${num(t.stock)} → ${num(t.stock + add)} ${t.unit}`;
                $('simCost').textContent = unitCost ? `${rp(unitCost)} / ${t.unit}` : 'tidak berubah';
                $('simValue').textContent = rp((s.cost || 0) * q);

                if (s.id === t.id) {
                    note.textContent = 'Produk eceran harus berbeda dari produk kemasan.'; note.classList.add('is-warn');
                } else if (q > s.stock) {
                    note.textContent = `Stok ${s.name} hanya ${num(s.stock)} ${s.unit}.`; note.classList.add('is-warn');
                } else if (!MEASURE.includes(t.unit)) {
                    note.textContent = `Perhatian: satuan produk eceran ${t.unit}. Biasanya LITER / KG / METER.`; note.classList.add('is-warn');
                } else if (!s.cost) {
                    note.textContent = 'Produk kemasan belum punya harga beli — harga beli eceran tidak diubah.'; note.classList.add('is-warn');
                } else {
                    note.textContent = `${num(q)} ${s.unit} dibuka → +${num(add)} ${t.unit} ${t.name}.`;
                }
            };

            src.addEventListener('change', () => {
                const pair = PAIRS[src.value];
                if (pair) {
                    if (!tgt.value) { fill(tgt, '', String(pair.target), true); tgt.value = String(pair.target); }
                    if (!ratio.value) ratio.value = pair.ratio;
                }
                update();
            });
            [tgt, qty, ratio].forEach((el) => el.addEventListener('input', update));
            tgt.addEventListener('change', update);

            fill(src, '', OLD.source ? String(OLD.source) : '');
            fill(tgt, '', OLD.target ? String(OLD.target) : '', true);
            if (src.value && !OLD.ratio) src.dispatchEvent(new Event('change'));
            update();
        })();
    </script>
@endpush

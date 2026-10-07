@extends('layouts.app')

@section('title', 'Stok Masuk')

@section('content')
    @php
        $rp = fn($n) => "Rp\u{00A0}" . number_format((float) ($n ?? 0), 0, ',', '.');
        $count = $stockIns->total();
        $from = \Carbon\Carbon::parse($start_date);
        $to = \Carbon\Carbon::parse($end_date);
        $isFiltered = filled($supplier_id) || filled($payment) || request()->filled('start_date') || request()->filled('end_date');
        $today = now()->startOfDay();
        $payUrl = fn($value) => route('stock-ins.index', array_filter([
            'start_date' => $start_date, 'end_date' => $end_date, 'supplier_id' => $supplier_id, 'payment' => $value,
        ]));
        $debtUrl = route('stock-ins.index', array_filter([
            'start_date' => '2000-01-01', 'end_date' => now()->toDateString(), 'supplier_id' => $supplier_id, 'payment' => 'unpaid',
        ]));
    @endphp

    <div class="crud-page">

        <div class="p-header">
            <div>
                <span class="p-eyebrow">Stok & Pembelian</span>
                <h1>Stok Masuk</h1>
                <p>Riwayat barang datang dari supplier, lengkap dengan harga belinya.</p>
            </div>

            <a href="{{ route('stock-ins.create') }}" class="p-btn p-btn-primary">
                <i data-lucide="plus"></i>
                Catat Stok Masuk
            </a>
        </div>

        {{-- FILTER --}}
        <section class="p-card p-filter-card" aria-label="Filter stok masuk">
            <form method="GET" action="{{ route('stock-ins.index') }}" class="p-filter">
                @if ($payment)
                    <input type="hidden" name="payment" value="{{ $payment }}">
                @endif
                <div class="p-field">
                    <label for="start_date">Dari</label>
                    <input type="date" id="start_date" name="start_date" value="{{ $start_date }}">
                </div>

                <div class="p-field">
                    <label for="end_date">Sampai</label>
                    <input type="date" id="end_date" name="end_date" value="{{ $end_date }}">
                </div>

                <div class="p-field">
                    <label for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id">
                        <option value="">Semua supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected($supplier_id == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-filter-actions">
                    <button type="submit" class="p-btn p-btn-dark">
                        <i data-lucide="filter"></i>
                        Terapkan
                    </button>
                    <a href="{{ route('stock-ins.index') }}" class="p-icon-btn p-filter-reset" title="Reset filter"
                        aria-label="Reset filter">
                        <i data-lucide="rotate-ccw"></i>
                    </a>
                </div>
            </form>

            <nav class="p-chips" aria-label="Status pembayaran">
                <a href="{{ $payUrl(null) }}" class="p-chip {{ $payment === null ? 'is-active' : '' }}">Semua</a>
                <a href="{{ $payUrl('paid') }}" class="p-chip {{ $payment === 'paid' ? 'is-active' : '' }}">Lunas</a>
                <a href="{{ $payUrl('unpaid') }}" class="p-chip {{ $payment === 'unpaid' ? 'is-active' : '' }}">Tempo / belum lunas</a>
            </nav>
        </section>

        {{-- RINGKASAN --}}
        <section class="p-stats" aria-label="Ringkasan pembelian">
            <div class="p-stat is-total">
                <div class="p-stat-main">
                    <span class="p-stat-label"><i data-lucide="package-plus"></i> Total pembelian</span>
                    <strong class="p-stat-value">{{ $rp($summary->total) }}</strong>
                    <span class="p-stat-sub">
                        <i data-lucide="calendar-days"></i>
                        {{ $from->translatedFormat('d M Y') }} – {{ $to->translatedFormat('d M Y') }}
                        · {{ $summary->notes }} nota · {{ $summary->suppliers }} supplier
                    </span>
                </div>

                @if (Route::has('reports.supplier-prices'))
                    <a href="{{ route('reports.supplier-prices') }}" class="p-btn p-btn-primary">
                        <i data-lucide="scale"></i>
                        Supplier Termurah
                    </a>
                @endif
            </div>

            {{-- uang yang benar-benar keluar pada periode ini --}}
            <div class="p-stat tone-green is-half">
                <span class="p-stat-icon"><i data-lucide="banknote"></i></span>
                <span class="p-stat-label">Uang keluar (dibayar periode ini)</span>
                <strong class="p-stat-value">{{ $rp($summary->cash_out) }}</strong>
                <span class="p-stat-sub">Dihitung dari tanggal bayar, termasuk pelunasan nota lama</span>
            </div>

            {{-- hutang ke supplier (semua nota belum lunas) --}}
            <a href="{{ $debtUrl }}" class="p-stat is-half {{ $debt->overdue_notes > 0 ? 'tone-red' : 'tone-amber' }}"
                title="Lihat semua nota belum lunas">
                <span class="p-stat-icon"><i data-lucide="clock"></i></span>
                <span class="p-stat-label">Hutang ke supplier</span>
                <strong class="p-stat-value">{{ $rp($debt->total) }}</strong>
                <span class="p-stat-sub">
                    @if ($debt->notes == 0)
                        Tidak ada nota tempo
                    @elseif ($debt->overdue_notes > 0)
                        {{ $debt->notes }} nota · {{ $debt->overdue_notes }} lewat jatuh tempo ({{ $rp($debt->overdue_total) }})
                    @else
                        {{ $debt->notes }} nota belum lunas
                    @endif
                </span>
            </a>
        </section>

        {{-- DAFTAR --}}
        <div class="p-card">
            <div class="p-table-wrap">
                <table class="p-table p-table-expense">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Pembayaran</th>
                            <th class="cell-hide-sm">Dicatat oleh</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($stockIns as $row)
                            <tr>
                                <td class="cell-date">
                                    <span class="p-date">
                                        <strong>{{ \Carbon\Carbon::parse($row->received_at)->translatedFormat('d M Y') }}</strong>
                                    </span>
                                </td>

                                <td class="cell-main">
                                    <div class="p-item">
                                        <div class="p-thumb has-tone tone-orange"><i data-lucide="truck"></i></div>
                                        <div class="p-item-text">
                                            <a href="{{ route('stock-ins.show', $row->id) }}" class="p-name p-name-link"
                                                data-stockin-view="{{ route('stock-ins.show', $row->id) }}"
                                                data-edit-url="{{ route('stock-ins.edit', $row->id) }}">{{ $row->supplier_name }}</a>
                                            <small class="p-desc">{{ $row->invoice_number ?: 'Tanpa nomor nota' }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td class="cell-category">
                                    @php
                                        $isPaid = $row->payment_status === 'paid';
                                        $dueAt = $row->due_date ? \Carbon\Carbon::parse($row->due_date)->startOfDay() : null;
                                        $lateDays = ! $isPaid && $dueAt && $dueAt->lt($today) ? (int) $dueAt->diffInDays($today) : 0;
                                    @endphp
                                    @if ($isPaid)
                                        <span class="p-status is-on">Lunas</span>
                                    @elseif ($lateDays > 0)
                                        <span class="p-stock is-empty" title="Jatuh tempo {{ $dueAt->translatedFormat('d M Y') }}">Telat {{ $lateDays }} hari</span>
                                    @else
                                        <span class="p-stock is-low">
                                            Tempo{{ $dueAt ? ' · ' . $dueAt->translatedFormat('d M') : '' }}
                                        </span>
                                    @endif
                                    <small class="p-sub p-items-count">{{ $row->items_count }} jenis barang</small>
                                </td>

                                <td class="cell-hide-sm">{{ $row->user_name ?? '-' }}</td>

                                <td class="cell-price text-end">
                                    <span class="p-price is-out">{{ $rp($row->total) }}</span>
                                </td>

                                <td class="cell-actions">
                                    <div class="p-actions">
                                        @if (! $isPaid)
                                            <form method="POST" action="{{ route('stock-ins.pay', $row->id) }}"
                                                data-confirm="Tandai nota {{ $row->supplier_name }} ({{ $rp($row->total) }}) sudah LUNAS hari ini?">
                                                @csrf
                                                <button type="submit" class="p-icon-btn is-success" title="Tandai lunas"
                                                    aria-label="Tandai lunas">
                                                    <i data-lucide="circle-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <button type="button" class="p-icon-btn" title="Detail"
                                            aria-label="Detail nota {{ $row->supplier_name }}"
                                            data-stockin-view="{{ route('stock-ins.show', $row->id) }}"
                                            data-edit-url="{{ route('stock-ins.edit', $row->id) }}">
                                            <i data-lucide="eye"></i>
                                        </button>

                                        <a href="{{ route('stock-ins.edit', $row->id) }}" class="p-icon-btn"
                                            title="Edit" aria-label="Edit nota">
                                            <i data-lucide="pencil"></i>
                                        </a>

                                        <form method="POST" action="{{ route('stock-ins.destroy', $row->id) }}"
                                            data-confirm="Hapus stok masuk dari {{ $row->supplier_name }} ({{ $rp($row->total) }})? Stok produk akan dikurangi kembali.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-icon-btn is-danger" title="Hapus"
                                                aria-label="Hapus nota">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="p-empty-row">
                                <td colspan="6">
                                    <div class="p-empty">
                                        <span class="p-empty-icon"><i data-lucide="package-plus"></i></span>
                                        <h4>{{ $isFiltered ? 'Tidak ada stok masuk' : 'Belum ada stok masuk bulan ini' }}</h4>
                                        <p>
                                            {{ $isFiltered
                                                ? 'Tidak ada data pada periode / supplier yang dipilih.'
                                                : 'Catat setiap barang datang supaya stok dan harga beli selalu benar.' }}
                                        </p>
                                        <div class="p-empty-actions">
                                            @if ($isFiltered)
                                                <a href="{{ route('stock-ins.index') }}" class="p-btn p-btn-ghost">
                                                    <i data-lucide="rotate-ccw"></i> Reset filter
                                                </a>
                                            @endif
                                            <a href="{{ route('stock-ins.create') }}" class="p-btn p-btn-primary">
                                                <i data-lucide="plus"></i> Catat Stok Masuk
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($stockIns->hasPages())
                <div class="p-footer">
                    <span class="p-page-info">
                        Menampilkan <strong>{{ $stockIns->firstItem() }}</strong>–<strong>{{ $stockIns->lastItem() }}</strong>
                        dari <strong>{{ $count }}</strong>
                    </span>
                    {{ $stockIns->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>

    </div>

    {{-- ===================== MODAL DETAIL =====================
         Detail nota dibuka di sini (tanpa pindah halaman).
         Di HP tampil sebagai bottom sheet. --}}
    <div class="p-modal" id="stockInModal">
        <div class="p-modal-backdrop" data-stockin-close></div>

        <div class="p-modal-dialog is-wide crud-page" role="dialog" aria-modal="true" aria-labelledby="siTitle">
            <div class="p-modal-head">
                <span class="p-section-icon"><i data-lucide="package-plus"></i></span>
                <div>
                    <h2 id="siTitle">Detail Stok Masuk</h2>
                    <p id="siSubtitle">&nbsp;</p>
                </div>
                <button type="button" class="p-icon-btn p-modal-close" data-stockin-close aria-label="Tutup">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="p-modal-body">
                {{-- memuat --}}
                <div class="p-modal-state" id="siLoading">
                    <span class="p-spinner"></span>
                    Memuat detail...
                </div>

                {{-- gagal --}}
                <div class="p-modal-state" id="siError" hidden>
                    <span class="p-empty-icon is-danger"><i data-lucide="circle-alert"></i></span>
                    <strong>Gagal memuat detail</strong>
                    <span id="siErrorText"></span>
                </div>

                {{-- isi --}}
                <div id="siContent" hidden>
                    <dl class="p-meta">
                        <div><dt>Supplier</dt><dd id="siSupplier">-</dd></div>
                        <div><dt>Tanggal terima</dt><dd id="siDate">-</dd></div>
                        <div><dt>No. nota</dt><dd id="siInvoice">-</dd></div>
                        <div><dt>Dicatat oleh</dt><dd id="siUser">-</dd></div>
                        <div class="p-meta-wide" id="siPayBox"><dt>Pembayaran</dt><dd id="siPay">-</dd></div>
                    </dl>

                    <div class="p-modal-items">
                        <table class="p-table p-table-plain">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th class="text-end">Jumlah</th>
                                    <th class="text-end">Harga beli</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="siItems"></tbody>
                        </table>
                    </div>

                    <p class="p-send-note is-off" id="siNotes" hidden></p>

                    <div class="p-modal-total">
                        <span><b id="siCount">0</b> jenis barang</span>
                        <span>Total pembelian <strong id="siTotal">Rp 0</strong></span>
                    </div>
                </div>
            </div>

            <div class="p-modal-foot">
                <button type="button" class="p-btn p-btn-ghost" data-stockin-close>Tutup</button>
                <a href="#" class="p-btn p-btn-primary" id="siEdit">
                    <i data-lucide="pencil"></i>
                    Edit
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('stockInModal');
            if (!modal) return;

            const $ = (id) => document.getElementById(id);
            const rp = (n) => 'Rp\u00A0' + Math.round(Number(n) || 0).toLocaleString('id-ID');
            const num = (n) => (Number(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

            const cache = new Map(); // nota yang sudah dibuka tidak diambil ulang
            let controller = null;   // batalkan permintaan lama kalau klik cepat berganti
            let opener = null;

            const show = (state) => {
                $('siLoading').hidden = state !== 'loading';
                $('siError').hidden = state !== 'error';
                $('siContent').hidden = state !== 'content';
            };

            const close = () => {
                controller?.abort();
                modal.classList.remove('is-open');
                opener?.focus?.();
            };

            const cell = (tr, text, cls) => {
                const td = tr.insertCell();
                if (cls) td.className = cls;
                td.textContent = text;
                return td;
            };

            const render = ({ stock_in: s, items = [] }) => {
                $('siTitle').textContent = s.title;
                $('siSubtitle').textContent = s.supplier + ' · ' + s.received_at;
                $('siSupplier').textContent = s.supplier;
                $('siDate').textContent = s.received_at;
                $('siInvoice').textContent = s.invoice_number || '-';
                $('siUser').textContent = s.user;

                $('siPay').textContent = s.is_paid
                    ? 'Lunas' + (s.paid_at ? ' · dibayar ' + s.paid_at : '')
                    : (s.is_overdue ? 'Belum lunas · LEWAT jatuh tempo ' : 'Tempo · jatuh tempo ') + (s.due_date || 'belum ditentukan');
                $('siPayBox').className = 'p-meta-wide ' + (s.is_paid ? 'is-paid' : s.is_overdue ? 'is-late' : 'is-due');

                // baris dibuat lewat textContent → aman dari XSS
                const body = $('siItems');
                body.replaceChildren();

                items.forEach((item) => {
                    const tr = body.insertRow();

                    const main = tr.insertCell();
                    main.className = 'cell-main';
                    const name = document.createElement('strong');
                    name.textContent = item.name;
                    main.append(name);

                    if (item.sell_price !== null) {
                        const margin = item.sell_price - item.cost_price;
                        const small = document.createElement('small');
                        small.className = 'p-sub';
                        small.textContent = 'Jual ' + rp(item.sell_price) + ' · margin ' + rp(margin);
                        if (margin <= 0) small.classList.add('is-minus');
                        main.append(small);
                    }

                    cell(tr, num(item.quantity) + ' ' + item.unit, 'text-end cell-qty');
                    cell(tr, rp(item.cost_price), 'text-end cell-cost');
                    cell(tr, rp(item.subtotal), 'text-end cell-sub');
                });

                $('siNotes').hidden = !s.notes;
                $('siNotes').textContent = s.notes || '';
                $('siCount').textContent = items.length;
                $('siTotal').textContent = rp(s.total);

                show('content');
            };

            const open = async (trigger) => {
                const url = trigger.dataset.stockinView;
                opener = trigger;

                $('siEdit').href = trigger.dataset.editUrl || '#';
                $('siTitle').textContent = 'Detail Stok Masuk';
                $('siSubtitle').textContent = '\u00A0';
                modal.classList.add('is-open');

                if (cache.has(url)) return render(cache.get(url));

                show('loading');
                controller?.abort();
                controller = new AbortController();

                try {
                    const res = await fetch(url, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: controller.signal,
                    });

                    if (!res.ok) throw new Error('Server menjawab ' + res.status + '.');

                    const data = await res.json();
                    if (data.status !== 'success') throw new Error('Data tidak valid.');

                    cache.set(url, data);
                    render(data);
                } catch (err) {
                    if (err.name === 'AbortError') return;
                    $('siErrorText').textContent = err.message + ' Coba lagi, atau muat ulang halaman.';
                    show('error');
                }
            };

            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-stockin-view]');
                if (trigger) {
                    // Ctrl/Cmd+klik pada nama supplier tetap membuka halaman detail di tab baru
                    if (e.ctrlKey || e.metaKey || e.shiftKey) return;
                    e.preventDefault();
                    open(trigger);
                    return;
                }

                if (e.target.closest('[data-stockin-close]')) close();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
            });
        })();
    </script>
@endpush

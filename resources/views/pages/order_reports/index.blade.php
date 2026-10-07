@extends('layouts.app')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/order.css') }}">
@endpush

@section('title', 'Transaksi')

@section('content')
    @php
        $rp = fn ($n) => "Rp\u{00A0}" . number_format((float) ($n ?? 0), 0, ',', '.');
        $num = fn ($n) => number_format((float) ($n ?? 0), 0, ',', '.');
        $invoice = fn ($id) => 'INV' . str_pad($id, 6, '0', STR_PAD_LEFT);

        $ranges = [1 => 'Hari ini', 2 => '2 hari', 7 => '7 hari', 14 => '14 hari', 30 => '30 hari', 90 => '90 hari'];

        $cashTotal = (float) ($summary['total_cash'] ?? 0);
        $transferTotal = (float) ($summary['total_transfer'] ?? 0);
        $paymentSum = $cashTotal + $transferTotal;
        $cashPct = $paymentSum > 0 ? round($cashTotal / $paymentSum * 100) : 0;

        $chartLabels = $chartData->pluck('trx_date')->values();
        $chartValues = $chartData->pluck('total')->map(fn ($v) => (float) $v)->values();
    @endphp

    <div class="order-page">

        {{-- =========================================================
             HEADER
        ========================================================== --}}
        <div class="order-header">
            <div>
                <span class="order-eyebrow">Penjualan</span>
                <h1>Transaksi</h1>
                <p>Kelola dan pantau seluruh transaksi penjualan sparepart.</p>
            </div>
        </div>

        {{-- =========================================================
             FILTER
        ========================================================== --}}
        <form method="GET" class="filter-card" id="filterForm">
            <div class="filter-grid">
                <div class="form-group">
                    <label for="range">Periode</label>
                    <select id="range" name="range">
                        @foreach ($ranges as $value => $label)
                            <option value="{{ $value }}" @selected($range == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_date">Dari tanggal</label>
                    <input type="date" id="start_date" name="start_date" value="{{ $start_date }}">
                </div>

                <div class="form-group">
                    <label for="end_date">Sampai tanggal</label>
                    <input type="date" id="end_date" name="end_date" value="{{ $end_date }}">
                </div>

                <button type="submit" class="btn-primary filter-submit">
                    <i data-lucide="filter"></i>
                    Terapkan
                </button>
            </div>
        </form>

        {{-- =========================================================
             RINGKASAN
        ========================================================== --}}
        <div class="summary-grid">
            <div class="summary-card is-highlight">
                <span class="summary-icon orange"><i data-lucide="wallet"></i></span>
                <div class="summary-content">
                    <small>Total Pendapatan</small>
                    <h3>{{ $rp($summary['total_revenue']) }}</h3>
                </div>
            </div>

            <div class="summary-card">
                <span class="summary-icon slate"><i data-lucide="landmark"></i></span>
                <div class="summary-content">
                    <small>Pajak</small>
                    <h3>{{ $rp($summary['total_tax']) }}</h3>
                </div>
            </div>

            <div class="summary-card">
                <span class="summary-icon green"><i data-lucide="piggy-bank"></i></span>
                <div class="summary-content">
                    <small>Pendapatan Bersih</small>
                    <h3>{{ $rp($summary['net_revenue']) }}</h3>
                </div>
            </div>

            <div class="summary-card">
                <span class="summary-icon yellow"><i data-lucide="chart-column"></i></span>
                <div class="summary-content">
                    <small>Rata-rata Transaksi</small>
                    <h3>{{ $rp($summary['average_order']) }}</h3>
                </div>
            </div>

            <div class="summary-card">
                <span class="summary-icon orange"><i data-lucide="banknote"></i></span>
                <div class="summary-content">
                    <small>Cash</small>
                    <h3>{{ $rp($cashTotal) }}</h3>
                </div>
            </div>

            <div class="summary-card">
                <span class="summary-icon slate"><i data-lucide="credit-card"></i></span>
                <div class="summary-content">
                    <small>Transfer</small>
                    <h3>{{ $rp($transferTotal) }}</h3>
                </div>
            </div>

            <div class="summary-card">
                <span class="summary-icon green"><i data-lucide="receipt"></i></span>
                <div class="summary-content">
                    <small>Jumlah Transaksi</small>
                    <h3>{{ $num($summary['total_order']) }}</h3>
                </div>
            </div>

            <div class="summary-card">
                <span class="summary-icon yellow"><i data-lucide="package"></i></span>
                <div class="summary-content">
                    <small>Part Terjual</small>
                    <h3>{{ $num($summary['total_item']) }}</h3>
                </div>
            </div>
        </div>

        {{-- =========================================================
             GRAFIK
        ========================================================== --}}
        <div class="report-grid">
            <div class="report-card">
                <div class="card-header">
                    <div>
                        <h3>Grafik Pendapatan</h3>
                        <small>{{ $ranges[$range] ?? $range . ' hari' }} terakhir</small>
                    </div>
                    <span class="card-header-icon"><i data-lucide="chart-no-axes-column"></i></span>
                </div>

                @if ($chartValues->sum() > 0)
                    {{-- tinggi dikunci → halaman tidak "loncat" saat grafik dimuat --}}
                    <div class="chart-container" data-order-chart="sales">
                        <canvas id="salesChart" aria-label="Grafik pendapatan harian" role="img"></canvas>
                    </div>
                @else
                    <div class="chart-empty">
                        <i data-lucide="chart-no-axes-column"></i>
                        Belum ada penjualan pada periode ini.
                    </div>
                @endif
            </div>

            <div class="report-card payment-report">
                <div class="card-header">
                    <div>
                        <h3>Metode Pembayaran</h3>
                        <small>Cash vs Transfer</small>
                    </div>
                    <span class="card-header-icon"><i data-lucide="wallet-cards"></i></span>
                </div>

                @if ($paymentSum > 0)
                    <div class="payment-chart-container" data-order-chart="payment">
                        <canvas id="paymentChart" aria-label="Grafik metode pembayaran" role="img"></canvas>
                        <div class="donut-center">
                            <strong>{{ $cashPct }}%</strong>
                            <span>Cash</span>
                        </div>
                    </div>
                @else
                    <div class="chart-empty">
                        <i data-lucide="wallet-cards"></i>
                        Belum ada pembayaran.
                    </div>
                @endif

                <div class="payment-legend">
                    <div><span class="dot cash"></span> Cash <strong>{{ $rp($cashTotal) }}</strong></div>
                    <div><span class="dot transfer"></span> Transfer <strong>{{ $rp($transferTotal) }}</strong></div>
                </div>
            </div>
        </div>

        {{-- =========================================================
             RIWAYAT TRANSAKSI
             Laptop/tablet: tabel · HP: otomatis jadi kartu per transaksi
        ========================================================== --}}
        <div class="table-card">
            <div class="table-header">
                <h3>Riwayat Transaksi</h3>
                <div class="transaction-count">
                    <strong>{{ $num($orders->total()) }}</strong> transaksi
                </div>
            </div>

            <div class="table-responsive">
                <table class="table order-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th>Pelanggan</th>
                            <th>Pembayaran</th>
                            <th>Item</th>
                            <th>Total</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $time = \Carbon\Carbon::parse($order->transaction_time);
                                $isCash = strtolower($order->payment_method) === 'cash';
                                $inv = $invoice($order->id);
                            @endphp
                            <tr>
                                <td class="row-number" data-label="#">{{ $orders->firstItem() + $loop->index }}</td>

                                <td class="cell-invoice" data-label="Invoice">
                                    <span class="invoice-number">{{ $inv }}</span>
                                </td>

                                <td class="cell-date" data-label="Tanggal">
                                    <div class="date-cell">
                                        <strong>{{ $time->translatedFormat('d M Y') }}</strong>
                                        <small>{{ $time->format('H:i') }}</small>
                                    </div>
                                </td>

                                <td data-label="Kasir">{{ $order->nama_kasir ?: '-' }}</td>

                                <td data-label="Pelanggan">
                                    <span class="customer-name">{{ $order->customer_name ?: 'Umum' }}</span>
                                </td>

                                <td data-label="Pembayaran">
                                    <span class="badge {{ $isCash ? 'badge-cash' : 'badge-transfer' }}">
                                        <i data-lucide="{{ $isCash ? 'banknote' : 'credit-card' }}"></i>
                                        {{ $isCash ? 'Cash' : 'Transfer' }}
                                    </span>
                                </td>

                                <td data-label="Item">
                                    <span class="item-count">{{ $order->total_item }}</span>
                                </td>

                                <td class="cell-total" data-label="Total">
                                    <strong class="table-total">{{ $rp($order->total) }}</strong>
                                </td>

                                <td class="cell-actions" data-label="Aksi">
                                    <div class="row-actions">
                                        <button type="button" class="btn-icon btn-view-order"
                                            data-id="{{ $order->id }}" title="Lihat detail"
                                            aria-label="Lihat detail {{ $inv }}">
                                            <i data-lucide="eye"></i>
                                        </button>

                                        <a href="{{ route('orders.edit', $order->id) }}" class="btn-icon"
                                            title="Edit transaksi" aria-label="Edit {{ $inv }}">
                                            <i data-lucide="pencil"></i>
                                        </a>

                                        <form method="POST" action="{{ route('orders.destroy', $order->id) }}"
                                            class="delete-order-form" data-invoice="{{ $inv }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-delete-order"
                                                title="Hapus transaksi" aria-label="Hapus {{ $inv }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="9">
                                    <div class="order-empty">
                                        <span class="empty-icon"><i data-lucide="receipt"></i></span>
                                        <h4>Belum ada transaksi</h4>
                                        <p>Tidak ada transaksi pada periode yang dipilih.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="table-footer">
                    <div class="pagination-info">
                        Menampilkan <strong>{{ $orders->firstItem() }}</strong>–<strong>{{ $orders->lastItem() }}</strong>
                        dari <strong>{{ $num($orders->total()) }}</strong> transaksi
                    </div>
                    <div class="pagination-wrapper">
                        {{ $orders->links('vendor.pagination.custom') }}
                    </div>
                </div>
            @endif
        </div>

    </div>

    {{-- =============================================================
         MODAL DETAIL
    ============================================================== --}}
    <div class="order-modal" id="orderDetailModal" aria-hidden="true">
        <div class="order-modal-backdrop" data-close-modal></div>

        <div class="order-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle">

            <div class="order-modal-header">
                <div>
                    <span class="order-eyebrow">Detail transaksi</span>
                    <h2 id="orderModalTitle">Detail Pesanan</h2>
                    <p id="modalInvoice">-</p>
                </div>
                <button type="button" class="order-modal-close" data-close-modal aria-label="Tutup">
                    <i data-lucide="x"></i>
                </button>
            </div>

            {{-- loading --}}
            <div class="modal-state" id="modalLoading">
                <div class="modal-spinner"></div>
                <span>Memuat detail pesanan...</span>
            </div>

            {{-- error (terpisah dari loading supaya bisa dipakai ulang) --}}
            <div class="modal-state modal-error" id="modalError" hidden>
                <span class="modal-error-icon"><i data-lucide="circle-alert"></i></span>
                <h3>Gagal memuat transaksi</h3>
                <p id="modalErrorText">-</p>
                <button type="button" class="btn-primary" data-close-modal>Tutup</button>
            </div>

            <div class="order-modal-content" id="modalContent" hidden>
                <div class="order-info-grid">
                    <div class="order-info-item">
                        <span>Pelanggan</span>
                        <strong id="modalCustomer">-</strong>
                    </div>
                    <div class="order-info-item">
                        <span>Kasir</span>
                        <strong id="modalCashier">-</strong>
                    </div>
                    <div class="order-info-item">
                        <span>Waktu</span>
                        <strong id="modalDate">-</strong>
                    </div>
                    {{-- hanya tampil kalau ada isinya --}}
                    <div class="order-info-item" id="modalTableWrap" hidden>
                        <span>Keterangan</span>
                        <strong id="modalTable">-</strong>
                    </div>
                </div>

                <div class="modal-payment-box">
                    <div>
                        <span>Metode Pembayaran</span>
                        <strong id="modalPayment">-</strong>
                    </div>
                    <div class="modal-status">
                        <span>Status</span>
                        <strong id="modalStatus">Selesai</strong>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-header">
                        <h3>Rincian Part</h3>
                        <span id="modalTotalItem">0 item</span>
                    </div>

                    <div class="modal-items">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Qty</th>
                                    <th>Harga</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody id="modalItemsBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-total-box">
                    <div class="total-row"><span>Subtotal</span><strong id="modalSubtotal">Rp 0</strong></div>
                    <div class="total-row"><span>Pajak</span><strong id="modalTax">Rp 0</strong></div>
                    <div class="total-row" id="modalServiceRow"><span>Biaya Jasa</span><strong id="modalService">Rp 0</strong></div>
                    <div class="total-row" id="modalDiscountRow"><span>Diskon</span><strong id="modalDiscount">Rp 0</strong></div>
                    <div class="total-divider"></div>
                    <div class="total-row total-final"><span>Total</span><strong id="modalTotal">Rp 0</strong></div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const $ = (id) => document.getElementById(id);
                const rupiah = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');
                const icons = () => window.lucide?.createIcons();

                /* ======================================================
                   FILTER: pilih periode → kosongkan tanggal manual,
                   isi tanggal manual → periode diabaikan server
                ====================================================== */
                $('range')?.addEventListener('change', () => {
                    $('start_date').value = '';
                    $('end_date').value = '';
                });

                /* ======================================================
                   KONFIRMASI HAPUS (event delegation, satu listener)
                ====================================================== */
                document.addEventListener('submit', (e) => {
                    const form = e.target.closest('.delete-order-form');
                    if (!form) return;

                    const ok = confirm(
                        'Hapus ' + (form.dataset.invoice || 'transaksi ini') + '?\n\n' +
                        'Stok produk & data member akan dikoreksi otomatis. ' +
                        'Tindakan ini TIDAK BISA dibatalkan.'
                    );

                    if (!ok) e.preventDefault();
                    else window.showLoading?.();
                });

                /* ======================================================
                   MODAL DETAIL
                ====================================================== */
                const modal = $('orderDetailModal');
                let controller = null;   // batalkan request lama kalau klik cepat berkali-kali
                const cache = new Map(); // detail yang sudah dibuka tidak diambil ulang

                const show = (state) => {
                    $('modalLoading').hidden = state !== 'loading';
                    $('modalError').hidden = state !== 'error';
                    $('modalContent').hidden = state !== 'content';
                };

                const openModal = () => {
                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                };

                const closeModal = () => {
                    controller?.abort();
                    modal.classList.remove('show');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                };

                window.closeOrderModal = closeModal;

                const formatDate = (value) => {
                    if (!value) return '-';
                    const d = new Date(String(value).replace(' ', 'T'));
                    return isNaN(d) ? value : d.toLocaleString('id-ID', {
                        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                    });
                };

                const render = ({ order, items = [] }) => {
                    $('modalInvoice').textContent = order.invoice || '-';
                    $('modalCustomer').textContent = order.customer_name || 'Umum';
                    $('modalCashier').textContent = order.nama_kasir || '-';
                    $('modalDate').textContent = formatDate(order.transaction_time);
                    $('modalPayment').textContent = order.payment_method || '-';
                    $('modalStatus').textContent = order.status || 'Selesai';

                    $('modalTableWrap').hidden = !order.table_number;
                    $('modalTable').textContent = order.table_number || '-';

                    // baris item dibuat lewat DOM (textContent) → aman dari XSS
                    const body = $('modalItemsBody');
                    body.replaceChildren();

                    if (!items.length) {
                        const tr = body.insertRow();
                        const td = tr.insertCell();
                        td.colSpan = 4;
                        td.className = 'modal-empty-items';
                        td.textContent = 'Tidak ada rincian item.';
                    }

                    items.forEach((item) => {
                        const tr = body.insertRow();
                        const name = document.createElement('strong');
                        name.textContent = item.product_name ?? '-';
                        tr.insertCell().append(name);
                        tr.insertCell().textContent = item.quantity;
                        tr.insertCell().textContent = rupiah(item.price);
                        const total = tr.insertCell();
                        total.className = 'text-right';
                        total.innerHTML = '<strong></strong>';
                        total.firstChild.textContent = rupiah(item.total);
                    });

                    const qty = items.reduce((t, i) => t + Number(i.quantity || 0), 0);
                    $('modalTotalItem').textContent = qty + ' item';

                    $('modalSubtotal').textContent = rupiah(order.sub_total);
                    $('modalTax').textContent = rupiah(order.tax);
                    $('modalService').textContent = rupiah(order.service_charge);
                    $('modalDiscount').textContent = '−' + rupiah(order.discount_amount);
                    $('modalTotal').textContent = rupiah(order.total);

                    // sembunyikan baris bernilai 0 supaya ringkas
                    $('modalServiceRow').hidden = !Number(order.service_charge);
                    $('modalDiscountRow').hidden = !Number(order.discount_amount);

                    show('content');
                };

                const loadOrder = async (id) => {
                    openModal();

                    if (cache.has(id)) {
                        render(cache.get(id));
                        return;
                    }

                    show('loading');
                    $('modalInvoice').textContent = '-';
                    controller?.abort();
                    controller = new AbortController();

                    try {
                        const res = await fetch(@json(url('/orders')) + '/' + encodeURIComponent(id), {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            signal: controller.signal
                        });

                        if (!res.ok) throw new Error('Gagal mengambil detail transaksi.');

                        const result = await res.json();
                        if (result.status !== 'success') throw new Error('Data transaksi tidak valid.');

                        cache.set(id, result);
                        render(result);
                    } catch (err) {
                        if (err.name === 'AbortError') return;
                        $('modalErrorText').textContent = err.message;
                        show('error');
                    }
                };

                document.addEventListener('click', (e) => {
                    const view = e.target.closest('.btn-view-order');
                    if (view?.dataset.id) {
                        loadOrder(view.dataset.id);
                        return;
                    }
                    if (e.target.closest('[data-close-modal]')) closeModal();
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
                });

                /* ======================================================
                   GRAFIK — Chart.js baru diunduh saat grafik hampir
                   terlihat (lazy load), animasi dimatikan.
                ====================================================== */
                const CHART_SRC = 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js';
                const COLORS = { accent: '#FF6A1A', ink: '#2A2F37', grid: 'rgba(20,23,28,.06)', muted: '#8A93A0' };

                const shortRp = (v) => {
                    const n = Math.abs(v);
                    if (n >= 1e9) return (v / 1e9).toFixed(1).replace('.', ',') + ' M';
                    if (n >= 1e6) return (v / 1e6).toFixed(1).replace('.', ',') + ' jt';
                    if (n >= 1e3) return Math.round(v / 1e3) + ' rb';
                    return v;
                };

                const charts = {
                    sales: () => new Chart($('salesChart'), {
                        type: 'bar',
                        data: {
                            labels: @json($chartLabels),
                            datasets: [{
                                data: @json($chartValues),
                                backgroundColor: COLORS.accent,
                                hoverBackgroundColor: '#E2550A',
                                borderRadius: 6,
                                borderSkipped: false,
                                maxBarThickness: 36
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: (c) => rupiah(c.parsed.y) } }
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { color: COLORS.muted, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
                                y: { beginAtZero: true, grid: { color: COLORS.grid }, border: { display: false }, ticks: { color: COLORS.muted, callback: shortRp } }
                            }
                        }
                    }),

                    payment: () => new Chart($('paymentChart'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Cash', 'Transfer'],
                            datasets: [{
                                data: [{{ $cashTotal }}, {{ $transferTotal }}],
                                backgroundColor: [COLORS.accent, COLORS.ink],
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            cutout: '74%',
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: (c) => c.label + ': ' + rupiah(c.parsed) } }
                            }
                        }
                    })
                };

                let chartLoader;
                const loadChartJs = () => window.Chart
                    ? Promise.resolve()
                    : (chartLoader ??= new Promise((resolve, reject) => {
                        const s = document.createElement('script');
                        s.src = CHART_SRC;
                        s.async = true;
                        s.onload = resolve;
                        s.onerror = reject;
                        document.head.appendChild(s);
                    }));

                const boxes = document.querySelectorAll('[data-order-chart]');
                const draw = (box) => loadChartJs()
                    .then(() => charts[box.dataset.orderChart]())
                    .catch(() => box.insertAdjacentHTML('beforeend', '<p class="chart-error">Grafik gagal dimuat.</p>'));

                if (boxes.length) {
                    if ('IntersectionObserver' in window) {
                        const io = new IntersectionObserver((entries) => {
                            entries.forEach((en) => {
                                if (!en.isIntersecting) return;
                                io.unobserve(en.target);
                                draw(en.target);
                            });
                        }, { rootMargin: '250px 0px' });
                        boxes.forEach((b) => io.observe(b));
                    } else {
                        boxes.forEach(draw);
                    }
                }
            })();
        </script>
    @endpush
@endsection

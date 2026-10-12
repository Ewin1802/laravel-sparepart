@php
    $hour = now()->hour;
    $greeting = match (true) {
        $hour < 11 => 'Selamat pagi',
        $hour < 15 => 'Selamat siang',
        $hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $firstName = \Illuminate\Support\Str::before(auth()->user()?->name ?? 'Admin', ' ');
@endphp

{{-- =========================================================
     JUDUL + TANGGAL
========================================================= --}}
<div class="dashboard-header">
    <div>
        <span class="dash-eyebrow">{{ $greeting }}, {{ $firstName }}</span>
        <h1>Dashboard</h1>
        <p>Ringkasan penjualan, stok, dan keuangan toko.</p>
    </div>

    <div class="dashboard-date-card">
        <span class="date-icon"><i data-lucide="calendar-days"></i></span>
        <div>
            <div class="date-title">{{ now()->translatedFormat('l') }}</div>
            <div class="date-subtitle">{{ now()->translatedFormat('d F Y') }}</div>
        </div>
    </div>
</div>

{{-- =========================================================
     RINGKASAN BULAN INI (panel gelap)
========================================================= --}}
<section class="month-panel" aria-label="Ringkasan bulan ini">
    <div class="month-panel-head">
        <div>
            <span class="dash-eyebrow">Bulan ini</span>
            <h2>{{ now()->translatedFormat('F Y') }}</h2>
        </div>
        <span class="month-chip">
            <i data-lucide="receipt-text"></i>
            {{ $num($monthOrders ?? 0) }} transaksi
        </span>
    </div>

    <div class="month-metrics">
        <div class="metric">
            <span class="metric-label">Penjualan</span>
            <strong class="metric-value">{{ $rp($monthRevenue ?? 0) }}</strong>
            <span class="metric-foot">
                <i data-lucide="trending-up"></i>
                @if (($monthTempo ?? 0) > 0)
                    Termasuk tempo {{ $rp($monthTempo) }}
                @else
                    Total nota penjualan
                @endif
            </span>
        </div>

        <div class="metric">
            <span class="metric-label">Uang Masuk</span>
            <strong class="metric-value">{{ $rp($monthCashIn ?? 0) }}</strong>
            <span class="metric-foot">
                <i data-lucide="banknote"></i>
                Cash · Transfer{{ ($monthTempoReceived ?? 0) > 0 ? ' · cicilan ' . $rp($monthTempoReceived) : '' }}
            </span>
        </div>

        <div class="metric">
            <span class="metric-label">Piutang Belum Dibayar</span>
            <strong class="metric-value">{{ $rp($receivableOutstanding ?? 0) }}</strong>
            <span class="metric-foot">
                <i data-lucide="hand-coins"></i>
                @if (Route::has('receivables.index'))
                    <a href="{{ route('receivables.index') }}" style="color: inherit">{{ $num($receivableNotes ?? 0) }} nota tempo →</a>
                @else
                    {{ $num($receivableNotes ?? 0) }} nota tempo
                @endif
            </span>
        </div>

        <div class="metric">
            <span class="metric-label">Modal &amp; Pengeluaran</span>
            <strong class="metric-value">{{ $rp(($monthCogs ?? 0) + ($monthExpense ?? 0) + ($monthTax ?? 0)) }}</strong>
            <span class="metric-foot">
                <i data-lucide="trending-down"></i>
                Modal {{ $rp($monthCogs ?? 0) }} · biaya {{ $rp($monthExpense ?? 0) }}{{ ($monthTax ?? 0) > 0 ? ' · pajak ' . $rp($monthTax) : '' }}
            </span>
        </div>

        <div class="metric is-accent">
            <span class="metric-label">Laba Bersih</span>
            <strong class="metric-value">{{ $rp($monthNetIncome ?? 0) }}</strong>
            <span class="metric-foot">
                <i data-lucide="badge-dollar-sign"></i>
                @if (($monthNoCost ?? 0) > 0)
                    {{ $num($monthNoCost) }} item belum ada harga beli
                @else
                    Margin {{ number_format($netIncomePercent ?? 0, 1, ',', '.') }}%
                @endif
            </span>
        </div>
    </div>
</section>

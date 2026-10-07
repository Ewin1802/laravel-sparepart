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
            <span class="metric-label">Pendapatan</span>
            <strong class="metric-value">{{ $rp($monthRevenue ?? 0) }}</strong>
            <span class="metric-foot"><i data-lucide="trending-up"></i> Total penjualan</span>
        </div>

        <div class="metric">
            <span class="metric-label">Pengeluaran</span>
            <strong class="metric-value">{{ $rp($monthExpense ?? 0) }}</strong>
            <span class="metric-foot">
                <i data-lucide="trending-down"></i>
                {{ number_format($expensePercent ?? 0, 1, ',', '.') }}% dari pendapatan
            </span>
        </div>

        <div class="metric">
            <span class="metric-label">Pajak</span>
            <strong class="metric-value">{{ $rp($monthTax ?? 0) }}</strong>
            <span class="metric-foot"><i data-lucide="landmark"></i> Kewajiban pajak</span>
        </div>

        <div class="metric is-accent">
            <span class="metric-label">Laba Bersih</span>
            <strong class="metric-value">{{ $rp($monthNetIncome ?? 0) }}</strong>
            <span class="metric-foot">
                <i data-lucide="badge-dollar-sign"></i>
                Margin {{ number_format($netIncomePercent ?? 0, 1, ',', '.') }}%
            </span>
        </div>

        <div class="metric">
            <span class="metric-label">Rata-rata Transaksi</span>
            <strong class="metric-value">{{ $rp($averageOrder ?? 0) }}</strong>
            <span class="metric-foot"><i data-lucide="wallet"></i> Per nota</span>
        </div>
    </div>
</section>

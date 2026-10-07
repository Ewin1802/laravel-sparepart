@php
    // Pakai setting yang sudah diambil layout (tanpa query ulang)
    $sidebarSetting = $appSetting ?? \App\Models\Setting::first();
    $sidebarName = $sidebarSetting?->store_name ?? 'Garasi Part';
    $sidebarLogo = $sidebarSetting?->logo ? $sidebarSetting->logo_url : null;

    // Pengingat hutang supplier: nota tempo yang lewat / segera jatuh tempo
    $payables = \App\Support\PayableAlerts::summary();

    $menuGroups = [
        'Utama' => [
            ['route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
            ['route' => 'orders.index', 'active' => 'orders.*', 'icon' => 'receipt-text', 'label' => 'Transaksi'],
        ],
        'Katalog' => [
            ['route' => 'products.index', 'active' => 'products.*', 'icon' => 'package', 'label' => 'Produk'],
            ['route' => 'categories.index', 'active' => 'categories.*', 'icon' => 'layers-3', 'label' => 'Kategori'],
            // ['route' => 'discounts.index', 'active' => 'discounts.*', 'icon' => 'badge-percent', 'label' => 'Diskon'],
        ],
        'Stok & Pembelian' => [
            [
                'route' => 'stock-ins.index', 'active' => 'stock-ins.*', 'icon' => 'package-plus', 'label' => 'Stok Masuk',
                'badge' => $payables->count,
                'badgeClass' => $payables->overdue_count > 0 ? 'is-danger' : 'is-warning',
                'badgeTitle' => $payables->overdue_count > 0
                    ? $payables->overdue_count . ' nota lewat jatuh tempo'
                    : $payables->soon_count . ' nota segera jatuh tempo',
                // klik menu saat ada pengingat → langsung ke daftar nota belum lunas
                'params' => $payables->count > 0
                    ? ['payment' => 'unpaid', 'start_date' => '2000-01-01', 'end_date' => now()->toDateString()]
                    : [],
            ],
            ['route' => 'suppliers.index', 'active' => 'suppliers.*', 'icon' => 'truck', 'label' => 'Supplier'],
            ['route' => 'reports.supplier-prices', 'active' => 'reports.supplier-prices', 'icon' => 'scale', 'label' => 'Supplier Termurah'],
        ],
        'Pelanggan & Tim' => [
            ['route' => 'members.index', 'active' => 'members.*', 'icon' => 'id-card', 'label' => 'Member', 'badge' => $totalMembers ?? 0],
            ['route' => 'users.index', 'active' => 'users.*', 'icon' => 'users', 'label' => 'User'],
        ],
        'Keuangan' => [
            ['route' => 'expenses.index', 'active' => 'expenses.*', 'icon' => 'wallet', 'label' => 'Pengeluaran'],
        ],
        'Sistem' => [
            ['route' => 'announcements.index', 'active' => 'announcements.*', 'icon' => 'megaphone', 'label' => 'Pengumuman'],
            ['route' => 'settings.edit', 'active' => 'settings.*', 'icon' => 'settings', 'label' => 'Pengaturan'],
        ],
    ];
@endphp

<aside class="sidebar" id="sidebar">

    {{-- Brand --}}
    <a href="{{ route('dashboard') }}" class="sidebar-brand" title="{{ $sidebarName }}">
        <span class="brand-mark">
            @if ($sidebarLogo)
                <img src="{{ $sidebarLogo }}" alt="{{ $sidebarName }}">
            @else
                <i data-lucide="cog"></i>
            @endif
        </span>
        <span class="brand-copy">
            <strong>{{ $sidebarName }}</strong>
            <small>Panel Manajemen</small>
        </span>
    </a>

    {{-- Menu --}}
    <nav class="sidebar-menu" aria-label="Menu admin">
        @foreach ($menuGroups as $group => $items)
            <span class="menu-group">{{ $group }}</span>

            @foreach ($items as $item)
                @continue(! Route::has($item['route']))

                <a href="{{ route($item['route'], $item['params'] ?? []) }}" title="{{ $item['badgeTitle'] ?? $item['label'] }}"
                    class="{{ request()->routeIs($item['active']) ? 'active' : '' }}"
                    @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                    <span class="menu-icon"><i data-lucide="{{ $item['icon'] }}"></i></span>
                    <span class="menu-title">{{ $item['label'] }}</span>

                    @if (($item['badge'] ?? 0) > 0)
                        <span class="menu-badge {{ $item['badgeClass'] ?? '' }}">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    {{-- Footer --}}
    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="sidebar-logout" title="Logout">
                <span class="menu-icon"><i data-lucide="log-out"></i></span>
                <span class="menu-title">Logout</span>
            </button>
        </form>
    </div>

</aside>

{{-- Latar gelap di HP; klik untuk menutup sidebar --}}
<div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

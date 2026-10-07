@php
    $topProducts = $topProducts ?? collect();
    $lowStockProducts = $lowStockProducts ?? collect();
    $maxQty = max(1, (float) ($topProducts->max('total_qty') ?? 1));
@endphp

<div class="product-panel">

    {{-- ==========================
         PRODUK TERLARIS
    =========================== --}}
    <div class="panel-card">
        <div class="panel-header">
            <h3>Produk Terlaris</h3>
            <span>Top {{ $topProducts->count() ?: 10 }}</span>
        </div>

        @forelse ($topProducts as $index => $product)
            <div class="product-item">
                <div class="product-left">
                    <span class="product-rank {{ $index < 3 ? 'is-top' : '' }}">{{ $index + 1 }}</span>
                    <div class="product-info">
                        <div class="product-name" title="{{ $product->name }}">{{ $product->name }}</div>
                        <small>{{ $qty($product->total_qty) }} terjual</small>
                        {{-- bar relatif terhadap produk terlaris #1 --}}
                        <span class="rank-bar"><span style="width: {{ round(($product->total_qty / $maxQty) * 100) }}%"></span></span>
                    </div>
                </div>
                <div class="product-right">{{ $rp($product->omzet ?? 0) }}</div>
            </div>
        @empty
            <div class="empty-state">
                <i data-lucide="chart-bar"></i>
                Belum ada transaksi.
            </div>
        @endforelse
    </div>

    {{-- ==========================
         STOK MENIPIS
    =========================== --}}
    <div class="panel-card">
        <div class="panel-header">
            <h3>Stok Menipis</h3>
            <span class="is-warning">≤ 5</span>
        </div>

        @forelse ($lowStockProducts as $product)
            @php $stock = (float) $product->stock; @endphp
            <div class="stock-item">
                <div class="product-info">
                    <div class="product-name" title="{{ $product->name }}">{{ $product->name }}</div>
                    <small>{{ $product->category->name ?? '-' }}</small>
                </div>
                <span class="stock-badge {{ $stock <= 0 ? 'is-empty' : '' }}">
                    {{ $stock <= 0 ? 'Habis' : $qty($stock) . ' ' . ($product->base_unit ?? 'PCS') }}
                </span>
            </div>
        @empty
            <div class="empty-state is-ok">
                <i data-lucide="circle-check"></i>
                Semua stok masih aman.
            </div>
        @endforelse

        @if ($lowStockProducts->isNotEmpty() && Route::has('products.index'))
            <a href="{{ route('products.index') }}" class="panel-link">
                Kelola stok <i data-lucide="arrow-right"></i>
            </a>
        @endif
    </div>

</div>

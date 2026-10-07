@php
    /*
    |--------------------------------------------------------------------------
    | Disesuaikan dengan LandingController:
    | $setting, $menuProducts, $categories, $topProducts, $topProductIds, $range
    |--------------------------------------------------------------------------
    */
    $setting = $setting ?? new \App\Models\Setting();
    $products = $menuProducts ?? collect();
    $categories = $categories ?? collect();
    $topProducts = $topProducts ?? collect();
    $topProductIds = $topProductIds ?? [];
    $range = $range ?? 30;

    $storeName = $setting->store_name ?? 'Toko Utama Caterpillar';
    $waNumber = $setting->whatsapp ?? null;
    $waLink = $waNumber
        ? 'https://wa.me/' .
            $waNumber .
            '?text=' .
            urlencode('Halo ' . $storeName . ', saya mau tanya sparepart alat berat.')
        : null;
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : null;

    // Format angka stok desimal: 12.00 -> "12", 2.50 -> "2,5"
    $fmtQty = fn($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');

    // Ikon otomatis berdasarkan nama kategori
    $iconFor = function ($name) {
        $n = strtolower($name ?? '');
        // Urutan penting: yang lebih spesifik di atas (mis. "hidrolik" sebelum "oli")
        $map = [
            'fa-filter' => ['filter', 'saringan', 'element'],
            'fa-gauge-high' => ['hidrolik', 'hydraulic', 'pompa', 'pump', 'silinder', 'cylinder', 'valve'],
            'fa-link' => ['undercarriage', 'track', 'roller', 'idler', 'sprocket', 'shoe', 'rantai'],
            'fa-trowel' => ['bucket', 'teeth', 'tooth', 'kuku', 'cutting', 'adapter', 'ripper', 'blade'],
            'fa-circle-notch' => ['bearing', 'bushing', 'bos', 'pin'],
            'fa-life-ring' => ['seal', 'o-ring', 'oring', 'packing'],
            'fa-gears' => ['mesin', 'engine', 'piston', 'liner', 'gasket', 'gear', 'transmisi', 'final drive'],
            'fa-fan' => ['turbo', 'radiator', 'pendingin', 'cooling', 'fan'],
            'fa-wave-square' => ['selang', 'hose', 'fitting', 'pipa'],
            'fa-oil-can' => ['oli', 'oil', 'pelumas', 'grease'],
            'fa-lightbulb' => ['lampu', 'light'],
            'fa-car-battery' => [
                'aki',
                'battery',
                'kelistrikan',
                'listrik',
                'elektrik',
                'starter',
                'alternator',
                'sensor',
            ],
            'fa-tractor' => ['kabin', 'cabin', 'kaca', 'body', 'bodi', 'aksesoris'],
        ];
        foreach ($map as $icon => $keys) {
            foreach ($keys as $k) {
                if (str_contains($n, $k)) {
                    return $icon;
                }
            }
        }
        return 'fa-screwdriver-wrench';
    };
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0c0e11">

    <title>{{ $setting->store_name ?? 'Toko Utama Caterpillar' }} — Sparepart Alat Berat</title>
    <meta name="description"
        content="{{ $setting->store_description ?? 'Sparepart alat berat untuk excavator, bulldozer, wheel loader, dan motor grader. Stok ready, harga jujur, siap kirim ke lokasi proyek.' }}">

    <link rel="icon" href="{{ $setting->logo ? $setting->logo_url : asset('images/logo-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>


<body data-whatsapp="{{ $waNumber ?? '' }}" data-store="{{ $storeName }}">

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="container topbar-inner">
            <div class="topbar-left">
                <span><i class="fa-solid fa-location-dot"></i>{{ $setting->address ?? 'Alamat toko' }}</span>
                <span><i class="fa-regular fa-clock"></i>Senin – Sabtu, 08:00 – 17:00</span>
            </div>
            @if ($setting->phone)
                <a href="tel:{{ $setting->phone }}"><i class="fa-solid fa-phone"></i>{{ $setting->phone }}</a>
            @endif
        </div>
    </div>

    {{-- NAVBAR --}}
    <header class="navbar" id="navbar">
        <div class="container nav-inner">
            <a href="#home" class="brand" aria-label="{{ $storeName }}">
                <span class="brand-icon">
                    @if ($setting->logo)
                        <img src="{{ $setting->logo_url }}" alt="{{ $storeName }}">
                    @else
                        <i class="fa-solid fa-gear"></i>
                    @endif
                </span>
                <span class="brand-copy">
                    <strong>{{ $storeName }}</strong>
                    <small>{{ $setting->store_tagline ?? 'Sparepart alat berat' }}</small>
                </span>
            </a>

            <nav class="desktop-nav" aria-label="Navigasi utama">
                <a href="#home" class="active">Beranda</a>
                <a href="#categories">Kategori</a>
                <a href="#products">Produk</a>
                <a href="#why">Layanan</a>
                <a href="#contact">Kontak</a>
            </nav>

            <div class="nav-actions">
                @if ($loginUrl)
                    <a href="{{ $loginUrl }}" class="nav-login">
                        <i class="fa-solid fa-right-to-bracket"></i><span>Login</span>
                    </a>
                @endif
                @if ($waLink)
                    <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn btn-accent nav-order">
                        <i class="fa-brands fa-whatsapp"></i> Pesan
                    </a>
                @endif
                <button type="button" class="menu-btn" id="menuBtn" aria-label="Buka menu" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

        <div class="mobile-menu" id="mobileMenu">
            <div class="container mobile-menu-inner">
                <a href="#home"><i class="fa-solid fa-house"></i>Beranda</a>
                <a href="#categories"><i class="fa-solid fa-layer-group"></i>Kategori</a>
                <a href="#products"><i class="fa-solid fa-box-open"></i>Produk</a>
                <a href="#why"><i class="fa-solid fa-screwdriver-wrench"></i>Layanan</a>
                <a href="#contact"><i class="fa-solid fa-location-dot"></i>Kontak</a>
                @if ($loginUrl)
                    <a href="{{ $loginUrl }}"><i class="fa-solid fa-right-to-bracket"></i>Login</a>
                @endif
                @if ($waLink)
                    <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn btn-accent">
                        <i class="fa-brands fa-whatsapp"></i> Pesan via WhatsApp
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main>
        {{-- HERO --}}
        {{-- Foto opsional: simpan di public/images/hero-sparepart.jpg. Kalau belum ada, --}}
        {{-- background tetap tampil rapi (gradient + grid blueprint). --}}
        <section class="hero" id="home"
            style="background-image: url('{{ asset('images/hero-sparepart.jpg') }}');">
            <div class="container hero-inner">
                <div class="hero-content">
                    <span class="eyebrow"><span class="pulse"></span> Stok ready · Siap kirim ke lokasi proyek</span>

                    <h1>{!! $setting->hero_title ?? 'Part yang <span class="hl">Tepat</span>, Unit Tetap Kerja' !!}</h1>

                    <p class="hero-desc">
                        {{ $setting->hero_subtitle ?? 'Sparepart alat berat untuk excavator, bulldozer, wheel loader, dan motor grader. Cari berdasarkan nama part, part number, atau model unit — kami bantu pastikan cocok sebelum Anda beli.' }}
                    </p>

                    <form class="hero-search" id="heroSearch" role="search">
                        <label>
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" id="heroSearchInput"
                                placeholder="Contoh: filter oli, track roller, part number…" autocomplete="off"
                                aria-label="Cari sparepart">
                        </label>
                        <button type="submit" class="btn btn-accent">Cari Part</button>
                    </form>

                    <div class="hero-tags">
                        <span>Populer:</span>
                        <button type="button" data-quick="filter">Filter</button>
                        <button type="button" data-quick="seal">Seal kit</button>
                        <button type="button" data-quick="bucket">Bucket teeth</button>
                        <button type="button" data-quick="roller">Track roller</button>
                        <button type="button" data-quick="bearing">Bearing</button>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="spec-card">
                        <i class="fa-solid fa-gear spec-gear" aria-hidden="true"></i>
                        @if ($topProducts->isNotEmpty())
                            <span class="spec-label"><i class="fa-solid fa-fire"></i> Terlaris {{ $range }}
                                hari</span>
                            <h3>Paling banyak<br>dicari pelanggan</h3>
                            <p>Berdasarkan penjualan {{ $range }} hari terakhir.</p>

                            <div class="spec-rows">
                                @foreach ($topProducts->take(3) as $i => $top)
                                    <div class="spec-row">
                                        <span><i
                                                class="fa-solid fa-{{ $i + 1 }}"></i>{{ \Illuminate\Support\Str::limit($top->name, 30) }}</span>
                                        <strong>{{ $fmtQty($top->total_qty) }} terjual</strong>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="spec-label"><i class="fa-solid fa-shield-halved"></i> Jaminan toko</span>
                            <h3>Original atau<br>uang kembali</h3>
                            <p>Setiap part dicek sebelum dikirim.</p>

                            <div class="spec-rows">
                                <div class="spec-row">
                                    <span><i class="fa-solid fa-certificate"></i>Part original & bergaransi</span>
                                    <strong><i class="fa-solid fa-check"></i></strong>
                                </div>
                                <div class="spec-row">
                                    <span><i class="fa-solid fa-magnifying-glass-chart"></i>Cek kecocokan model
                                        unit</span>
                                    <strong><i class="fa-solid fa-check"></i></strong>
                                </div>
                                <div class="spec-row">
                                    <span><i class="fa-solid fa-barcode"></i>Dicocokkan lewat part number</span>
                                    <strong><i class="fa-solid fa-check"></i></strong>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mini-cards">
                        <div class="mini-card">
                            <i class="fa-solid fa-truck-fast"></i>
                            <div><strong>Kirim ke site</strong><small>Tambang, proyek, kebun</small></div>
                        </div>
                        <div class="mini-card">
                            <i class="fa-solid fa-tags"></i>
                            <div><strong>Harga jujur</strong><small>Kontraktor & rental</small></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- STATS --}}
        <div class="stats">
            <div class="container stats-inner">
                <div class="stat">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <div><strong>{{ $products->count() }}+</strong><span>Produk tersedia</span></div>
                </div>
                <div class="stat">
                    <i class="fa-solid fa-layer-group"></i>
                    <div><strong>{{ $categories->count() }}</strong><span>Kategori part</span></div>
                </div>
                <div class="stat">
                    <i class="fa-solid fa-award"></i>
                    <div><strong>100%</strong><span>Barang dicek</span></div>
                </div>
                <div class="stat">
                    <i class="fa-solid fa-headset"></i>
                    <div><strong>08–17</strong><span>Konsultasi gratis</span></div>
                </div>
            </div>
        </div>

        {{-- CATEGORIES --}}
        <section class="section categories" id="categories">
            <div class="container">
                <div class="section-head reveal">
                    <div>
                        <span class="kicker">Kategori</span>
                        <h2>Cari part <em>sesuai kebutuhan</em></h2>
                    </div>
                    <p>Dari engine, hidrolik, undercarriage, sampai kelistrikan — pilih kategori untuk langsung melihat
                        produknya.</p>
                </div>

                <div class="category-grid">
                    @forelse ($categories as $category)
                        <a href="#products" class="category-card reveal" data-category-link="{{ $category->id }}">
                            <span class="category-icon"><i
                                    class="fa-solid {{ $iconFor($category->name) }}"></i></span>
                            <div>
                                <h3>{{ $category->name }}</h3>
                                <div class="category-foot">
                                    <span>{{ $products->where('category_id', $category->id)->count() }} produk</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="empty-box static" style="border-color: var(--line); color: var(--muted);">
                            <i class="fa-solid fa-layer-group"></i>
                            <h3>Kategori belum tersedia</h3>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- PRODUCTS --}}
        <section class="section products" id="products">
            <div class="container">
                <div class="section-head">
                    <div>
                        <span class="kicker">Katalog</span>
                        <h2>Semua <em>sparepart</em></h2>
                        <p class="section-desc">Cek harga, stok, dan kecocokan unit. Klik produk untuk detail
                            lengkap.</p>
                    </div>
                    <div class="product-total">
                        <strong id="productCount">{{ $products->count() }}</strong>
                        <span>produk ditemukan</span>
                    </div>
                </div>

                <div class="toolbar-row">
                    <label class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="productSearch"
                            placeholder="Cari nama part, merek, part number, atau model unit…" autocomplete="off">
                        <button type="button" class="clear-search" id="clearSearch" aria-label="Hapus pencarian">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </label>

                    <label class="sort-box">
                        <i class="fa-solid fa-arrow-down-wide-short"></i>
                        <select id="productSort" aria-label="Urutkan produk">
                            <option value="default">Rekomendasi</option>
                            <option value="best">Terlaris dulu</option>
                            <option value="price-asc">Harga terendah</option>
                            <option value="price-desc">Harga tertinggi</option>
                            <option value="name">Nama A–Z</option>
                        </select>
                    </label>
                </div>

                <div class="filter-scroll">
                    <button type="button" class="chip active" data-category="all">Semua</button>
                    @foreach ($categories as $category)
                        <button type="button" class="chip"
                            data-category="{{ $category->id }}">{{ $category->name }}</button>
                    @endforeach
                </div>

                <p class="showing" id="showing" aria-live="polite"></p>

                <div class="product-grid" id="productGrid">
                    @forelse ($products as $product)
                        @php
                            $icon = $iconFor($product->category->name ?? '');
                            $inStock = $product->status && (float) ($product->stock ?? 0) > 0;
                            $isBest = $product->is_favorite || in_array($product->id, $topProductIds);
                            $unit = $product->base_unit ?? 'PCS';
                            $brand = $product->brand ?? '';
                            $sku = $product->part_number ?? '';
                            $fit = $product->compatibility ?? '';
                        @endphp

                        <article class="product-card"
                            data-search="{{ strtolower($product->name . ' ' . $brand . ' ' . $sku . ' ' . $fit . ' ' . ($product->category->name ?? '') . ' ' . ($product->description ?? '')) }}"
                            data-name="{{ strtolower($product->name) }}" data-category="{{ $product->category_id }}"
                            data-price="{{ (int) $product->price }}" data-best="{{ $isBest ? 1 : 0 }}">

                            <div class="product-image">
                                @if ($product->image)
                                    <img src="{{ asset($product->image) }}" alt="{{ $product->name }}"
                                        loading="lazy">
                                @else
                                    <div class="product-placeholder">
                                        <i class="fa-solid {{ $icon }}"></i>
                                        <span>Foto segera hadir</span>
                                    </div>
                                @endif

                                @if ($isBest)
                                    <span class="badge badge-best"><i class="fa-solid fa-fire"></i> Terlaris</span>
                                @endif
                                <span class="badge badge-stock {{ $inStock ? '' : 'out' }}">
                                    {{ $inStock ? 'Ready' : 'Habis' }}
                                </span>
                            </div>

                            <div class="product-body">
                                <div class="product-meta">
                                    <span>{{ $brand ?: $product->category->name ?? 'Part' }}</span>
                                    @if ($sku)
                                        <small>{{ $sku }}</small>
                                    @endif
                                </div>

                                <h3>{{ $product->name }}</h3>

                                @if ($fit)
                                    <p class="product-fit"><i class="fa-solid fa-circle-check"></i>
                                        {{ \Illuminate\Support\Str::limit($fit, 48) }}</p>
                                @endif

                                <div class="product-bottom">
                                    <div class="price">
                                        <small>Harga</small>
                                        <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                                    </div>
                                    <button type="button" class="detail-btn"
                                        aria-label="Detail {{ $product->name }}" data-name="{{ $product->name }}"
                                        data-price="{{ number_format($product->price, 0, ',', '.') }}"
                                        data-category="{{ $product->category->name ?? 'Sparepart' }}"
                                        data-brand="{{ $brand ?: '-' }}" data-sku="{{ $sku ?: '-' }}"
                                        data-fit="{{ $fit ?: 'Tanyakan ke admin' }}"
                                        data-stock="{{ $inStock ? $fmtQty($product->stock) . ' ' . $unit : 'Stok habis' }}"
                                        data-description="{{ $product->description ?? 'Belum ada deskripsi produk.' }}"
                                        data-image="{{ $product->image ? asset($product->image) : '' }}"
                                        data-icon="{{ $icon }}">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="empty-box static">
                            <i class="fa-solid fa-box-open"></i>
                            <h3>Belum ada produk</h3>
                            <p>Katalog sedang disiapkan.</p>
                        </div>
                    @endforelse

                    <div class="empty-box" id="searchEmpty">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <h3>Part tidak ditemukan</h3>
                        <p>Coba kata kunci lain, atau tanyakan langsung ke admin kami.</p>
                        <button type="button" id="resetFilter">Tampilkan semua produk</button>
                    </div>
                </div>

                <div class="load-more-wrap" id="loadMoreWrap" hidden>
                    <button type="button" class="load-more" id="loadMore">
                        Muat produk lainnya <i class="fa-solid fa-chevron-down"></i>
                    </button>
                </div>
            </div>
        </section>

        {{-- BRANDS --}}
        <div class="brands" aria-label="Merek yang tersedia">
            <div class="brands-track">
                @php $brandList = ['Caterpillar', 'Komatsu', 'Hitachi', 'Kobelco', 'Volvo', 'Doosan', 'Sumitomo', 'Hyundai', 'Sany', 'Donaldson', 'Fleetguard', 'Berco']; @endphp
                @foreach (array_merge($brandList, $brandList) as $b)
                    <span>{{ $b }}</span>
                @endforeach
            </div>
        </div>

        {{-- WHY US --}}
        <section class="section why" id="why">
            <div class="container why-grid">
                <div class="why-visual reveal">
                    <div class="why-photo">
                        {{-- Opsional: public/images/bengkel.jpg (foto toko/bengkel asli) --}}
                        <span class="why-fallback"><i class="fa-solid fa-tractor"></i></span>
                        <img src="{{ asset('images/bengkel.jpg') }}" alt="Toko {{ $storeName }}" loading="lazy"
                            onerror="this.remove()">
                    </div>
                    <div class="why-badge">
                        <strong>10+</strong>
                        <span>Tahun<br>pengalaman</span>
                    </div>
                </div>

                <div class="why-content reveal">
                    <span class="kicker">Kenapa kami</span>
                    <h2>Bukan sekadar <em>jual part.</em></h2>
                    <p>
                        {{ $setting->store_description ?? 'Kami bantu Anda memilih part yang benar-benar cocok dengan model dan serial number unit, serta menjelaskan pilihan genuine, OEM, dan aftermarket — supaya alat berat Anda cepat kembali bekerja.' }}
                    </p>

                    <div class="feature-grid">
                        <div class="feature">
                            <i class="fa-solid fa-certificate"></i>
                            <strong>Original terjamin</strong>
                            <small>Langsung dari distributor resmi, lengkap dengan garansi.</small>
                        </div>
                        <div class="feature">
                            <i class="fa-solid fa-magnifying-glass-chart"></i>
                            <strong>Cek kecocokan</strong>
                            <small>Kirim model & serial number unit, kami pastikan part-nya pas.</small>
                        </div>
                        <div class="feature">
                            <i class="fa-solid fa-layer-group"></i>
                            <strong>Genuine, OEM, aftermarket</strong>
                            <small>Pilihan part sesuai kebutuhan dan anggaran proyek.</small>
                        </div>
                        <div class="feature">
                            <i class="fa-solid fa-truck-fast"></i>
                            <strong>Kirim ke site</strong>
                            <small>Pesanan sebelum jam 14:00 dikirim di hari yang sama.</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="cta" id="contact">
            <div class="container">
                <div class="cta-box reveal">
                    <div>
                        <span class="kicker">Belum ketemu part-nya?</span>
                        <h2>Kirim foto part atau name plate, <em>kami carikan.</em></h2>
                        <p>Admin kami bantu cek part number dan ketersediaan stok dalam hitungan menit.</p>
                    </div>
                    <div class="cta-actions">
                        @if ($waLink)
                            <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn btn-dark">
                                <i class="fa-brands fa-whatsapp"></i> Chat WhatsApp
                            </a>
                        @endif
                        @if ($setting->phone)
                            <a href="tel:{{ $setting->phone }}" class="btn btn-white">
                                <i class="fa-solid fa-phone"></i> Telepon
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- FOOTER --}}
    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a href="#home" class="brand">
                    <span class="brand-icon">
                        @if ($setting->logo)
                            <img src="{{ $setting->logo_url }}" alt="{{ $storeName }}">
                        @else
                            <i class="fa-solid fa-gear"></i>
                        @endif
                    </span>
                    <span class="brand-copy">
                        <strong>{{ $storeName }}</strong>
                        <small>{{ $setting->store_tagline ?? 'Sparepart alat berat' }}</small>
                    </span>
                </a>
                <p>{{ $setting->store_description ?? 'Toko sparepart alat berat dengan stok lengkap, harga jujur, dan bantuan cek kecocokan part.' }}
                </p>
            </div>

            <div class="footer-col">
                <h4>Jelajahi</h4>
                <a href="#categories">Kategori</a>
                <a href="#products">Produk</a>
                <a href="#why">Layanan</a>
                @if ($loginUrl)
                    <a href="{{ $loginUrl }}">Login</a>
                @endif
            </div>

            <div class="footer-col">
                <h4>Jam Buka</h4>
                <span>Senin – Sabtu</span>
                <strong>08:00 – 17:00</strong>
                <span>Minggu: tutup</span>
            </div>

            <div class="footer-col">
                <h4>Kontak</h4>
                <span>{{ $setting->address ?? 'Alamat toko' }}</span>
                @if ($setting->phone)
                    <a href="tel:{{ $setting->phone }}">{{ $setting->phone }}</a>
                @endif
                @if ($waLink)
                    <a href="{{ $waLink }}" target="_blank" rel="noopener">WhatsApp</a>
                @endif
            </div>
        </div>

        <div class="container footer-bottom">
            <span>© {{ date('Y') }} {{ $storeName }}. All rights reserved.</span>
            <span>Part tepat, unit tetap kerja.</span>
        </div>
    </footer>

    {{-- PRODUCT MODAL --}}
    <div class="modal" id="productModal" aria-hidden="true">
        <div class="modal-overlay" data-close></div>
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="mName">
            <button type="button" class="modal-close" data-close aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="modal-image">
                <img id="mImage" src="" alt="" hidden>
                <div class="product-placeholder" id="mPlaceholder">
                    <i class="fa-solid fa-screwdriver-wrench" id="mIcon"></i>
                    <span>Foto segera hadir</span>
                </div>
            </div>

            <div class="modal-info">
                <span class="modal-cat" id="mCategory">Sparepart</span>
                <h2 id="mName">Nama produk</h2>
                <p class="modal-sku">Part number: <span id="mSku">-</span></p>
                <div class="modal-price" id="mPrice">Rp 0</div>

                <div class="spec-table">
                    <div><span>Merek</span><strong id="mBrand">-</strong></div>
                    <div><span>Cocok untuk unit</span><strong id="mFit">-</strong></div>
                    <div><span>Stok</span><strong id="mStock">-</strong></div>
                </div>

                <p class="modal-desc" id="mDesc">-</p>

                @if ($waNumber)
                    <a href="#" target="_blank" rel="noopener" class="btn btn-accent modal-order"
                        id="mOrder">
                        <i class="fa-brands fa-whatsapp"></i> Pesan / Tanya Stok
                    </a>
                @else
                    <a href="#contact" class="btn btn-accent modal-order" data-close>
                        <i class="fa-solid fa-phone"></i> Hubungi Kami
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if ($waLink)
        <a href="{{ $waLink }}" target="_blank" rel="noopener" class="float-wa" aria-label="Chat WhatsApp">
            <i class="fa-brands fa-whatsapp"></i><span>Tanya Part</span>
        </a>
    @endif

    <button type="button" class="back-top" id="backTop" aria-label="Kembali ke atas">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.body;
            const $ = (id) => document.getElementById(id);

            /* ---------- MOBILE MENU ---------- */
            const menuBtn = $('menuBtn');
            const mobileMenu = $('mobileMenu');

            const closeMenu = () => {
                menuBtn?.classList.remove('active');
                mobileMenu?.classList.remove('show');
                menuBtn?.setAttribute('aria-expanded', 'false');
                body.classList.remove('lock');
            };

            menuBtn?.addEventListener('click', () => {
                const open = mobileMenu.classList.toggle('show');
                menuBtn.classList.toggle('active', open);
                menuBtn.setAttribute('aria-expanded', String(open));
                body.classList.toggle('lock', open);
            });

            mobileMenu?.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeMenu));

            window.addEventListener('resize', () => {
                if (window.innerWidth > 820) closeMenu();
            });

            /* ---------- KATALOG: SEARCH / FILTER / SORT / LOAD MORE ---------- */
            const PAGE = 12;
            const grid = $('productGrid');
            const search = $('productSearch');
            const clear = $('clearSearch');
            const sort = $('productSort');
            const chips = [...document.querySelectorAll('.chip')];
            const cards = grid ? [...grid.querySelectorAll('.product-card')] : [];
            const emptyBox = $('searchEmpty');
            let category = 'all';
            let limit = PAGE;

            const filtered = () => {
                const terms = (search?.value || '').toLowerCase().trim().split(/\s+/).filter(Boolean);
                const list = cards.filter((c) =>
                    (category === 'all' || c.dataset.category === category) &&
                    terms.every((t) => c.dataset.search.includes(t))
                );
                const by = sort?.value;
                if (by === 'price-asc') list.sort((a, b) => a.dataset.price - b.dataset.price);
                if (by === 'price-desc') list.sort((a, b) => b.dataset.price - a.dataset.price);
                if (by === 'name') list.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
                if (by === 'best') list.sort((a, b) => b.dataset.best - a.dataset.best);
                return list;
            };

            const render = (resetPage = false) => {
                if (!cards.length) return;
                if (resetPage) limit = PAGE;

                const list = filtered();
                const shown = new Set(list.slice(0, limit));

                cards.forEach((c) => (c.hidden = !shown.has(c)));
                list.slice(0, limit).forEach((c) => grid.insertBefore(c, emptyBox));

                $('productCount').textContent = list.length;
                $('showing').textContent = list.length ?
                    `Menampilkan ${shown.size} dari ${list.length} produk` : '';
                emptyBox?.classList.toggle('show', list.length === 0);
                clear?.classList.toggle('show', Boolean(search?.value));
                $('loadMoreWrap').hidden = limit >= list.length;
            };

            const setCategory = (id) => {
                category = id;
                chips.forEach((c) => c.classList.toggle('active', c.dataset.category === id));
                render(true);
            };

            search?.addEventListener('input', () => render(true));
            sort?.addEventListener('change', () => render(true));
            chips.forEach((c) => c.addEventListener('click', () => setCategory(c.dataset.category)));

            clear?.addEventListener('click', () => {
                search.value = '';
                render(true);
                search.focus();
            });

            $('loadMore')?.addEventListener('click', () => {
                limit += PAGE;
                render();
            });

            $('resetFilter')?.addEventListener('click', () => {
                search.value = '';
                sort.value = 'default';
                setCategory('all');
            });

            document.querySelectorAll('[data-category-link]').forEach((link) => {
                link.addEventListener('click', () => setCategory(link.dataset.categoryLink));
            });

            /* pencarian dari hero → lompat ke katalog */
            const goSearch = (q) => {
                if (!search) return;
                search.value = q;
                setCategory('all');
                document.getElementById('products').scrollIntoView({
                    behavior: 'smooth'
                });
            };

            $('heroSearch')?.addEventListener('submit', (e) => {
                e.preventDefault();
                goSearch($('heroSearchInput').value.trim());
            });

            document.querySelectorAll('[data-quick]').forEach((b) =>
                b.addEventListener('click', () => goSearch(b.dataset.quick))
            );

            render();

            /* ---------- MODAL DETAIL ---------- */
            const modal = $('productModal');

            const closeModal = () => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                body.classList.remove('lock');
            };

            document.querySelectorAll('.detail-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const d = btn.dataset;
                    $('mName').textContent = d.name;
                    $('mPrice').textContent = `Rp ${d.price}`;
                    $('mCategory').textContent = d.category;
                    $('mBrand').textContent = d.brand;
                    $('mSku').textContent = d.sku;
                    $('mFit').textContent = d.fit;
                    $('mStock').textContent = d.stock;
                    $('mDesc').textContent = d.description;

                    const hasImg = Boolean(d.image);
                    $('mImage').hidden = !hasImg;
                    $('mImage').src = hasImg ? d.image : '';
                    $('mImage').alt = d.name;
                    $('mPlaceholder').hidden = hasImg;
                    $('mIcon').className = `fa-solid ${d.icon}`;

                    const order = $('mOrder');
                    if (order && body.dataset.whatsapp) {
                        const msg =
                            `Halo ${body.dataset.store}, saya mau pesan:\n• ${d.name}\n• Part number: ${d.sku}\nApakah stok masih ada?`;
                        order.href =
                            `https://wa.me/${body.dataset.whatsapp}?text=${encodeURIComponent(msg)}`;
                    }

                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    body.classList.add('lock');
                    modal.querySelector('.modal-close').focus();
                });
            });

            modal?.querySelectorAll('[data-close]').forEach((el) => el.addEventListener('click', closeModal));

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeModal();
                    closeMenu();
                }
            });

            /* ---------- SCROLL: NAVBAR, BACK TOP, NAV AKTIF ---------- */
            const navbar = $('navbar');
            const backTop = $('backTop');

            const onScroll = () => {
                navbar.classList.toggle('scrolled', scrollY > 20);
                backTop.classList.toggle('show', scrollY > 600);
            };
            addEventListener('scroll', onScroll, {
                passive: true
            });
            onScroll();

            backTop.addEventListener('click', () => scrollTo({
                top: 0,
                behavior: 'smooth'
            }));

            const navLinks = [...document.querySelectorAll('.desktop-nav a')];

            if ('IntersectionObserver' in window) {
                const navObs = new IntersectionObserver((entries) => {
                    entries.forEach((en) => {
                        if (!en.isIntersecting) return;
                        navLinks.forEach((l) =>
                            l.classList.toggle('active', l.getAttribute('href') ===
                                `#${en.target.id}`)
                        );
                    });
                }, {
                    rootMargin: '-40% 0px -55% 0px'
                });
                document.querySelectorAll('main section[id]').forEach((s) => navObs.observe(s));

                const revObs = new IntersectionObserver((entries) => {
                    entries.forEach((en) => {
                        if (en.isIntersecting) {
                            en.target.classList.add('revealed');
                            revObs.unobserve(en.target);
                        }
                    });
                }, {
                    threshold: .1
                });
                document.querySelectorAll('.reveal').forEach((el) => revObs.observe(el));
            } else {
                document.querySelectorAll('.reveal').forEach((el) => el.classList.add('revealed'));
            }
        });
    </script>
</body>

</html>

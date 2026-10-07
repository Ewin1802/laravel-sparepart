@php
    /*
    | Setting toko diambil SEKALI di sini, lalu dipakai ulang oleh
    | sidebar (variabel $appSetting) → hemat 1 query per halaman.
    */
    $appSetting = $appSetting ?? \App\Models\Setting::first();
    $appName = $appSetting?->store_name ?? 'MJM Part - Marisa';
    $appFavicon = $appSetting?->logo ? $appSetting->logo_url : asset('icons/default-favicon.svg');

    $flashTypes = [
        'success' => ['success', 'Berhasil'],
        'error' => ['danger', 'Gagal'],
        'warning' => ['warning', 'Peringatan'],
        'info' => ['info', 'Informasi'],
    ];
    $flashes = collect($flashTypes)
        ->filter(fn($meta, $key) => session()->has($key))
        ->map(fn($meta, $key) => ['type' => $meta[0], 'title' => $meta[1], 'message' => session($key)])
        ->values();
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0c0e11">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Dashboard') | {{ $appName }}</title>

    <link rel="icon" href="{{ $appFavicon }}">

    {{-- ======================================================
         KONEKSI AWAL ke CDN (DNS + TLS dibuka lebih dulu)
    ======================================================= --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

    {{-- ======================================================
         FONT — sama dengan landing & halaman login
         display=swap: teks langsung tampil, tidak menunggu font
    ======================================================= --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    {{-- ======================================================
         CSS UTAMA (render-blocking, memang dibutuhkan)
    ======================================================= --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    {{-- sidebar & navbar dipakai di SEMUA halaman admin, jadi dimuat di sini.
         dashboard.css berisi gaya navbar atas + komponen kartu bersama. --}}
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    {{-- CSS bersama semua halaman kelola data (produk, kategori, user, dst.) --}}
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">

    {{-- CSS khusus halaman --}}
    @stack('css')

    {{-- ======================================================
         FONT AWESOME — dimuat tanpa memblokir render
         (admin memakai Lucide; FA hanya cadangan untuk ikon lama)
    ======================================================= --}}
    <link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    </noscript>
</head>

<body class="no-transition">

    {{-- Pulihkan status sidebar diringkas SEBELUM halaman tampil (tanpa kedip) --}}
    <script>
        try {
            if (localStorage.getItem('sidebar') === 'collapse' && innerWidth > 768) {
                document.body.classList.add('sidebar-collapse');
            }
        } catch (e) {}
    </script>

    <a href="#main-content" class="skip-link">Lewati ke konten</a>

    <div class="wrapper">

        {{-- SIDEBAR --}}
        @include('components.sidebar')

        {{-- MAIN --}}
        <main class="main">
            <div class="page">

                {{-- NAVBAR --}}
                @include('components.navbar')

                {{-- CONTENT --}}
                <div class="content" id="main-content" tabindex="-1">
                    @yield('content')
                </div>

            </div>
        </main>

    </div>

    {{-- TOAST --}}
    <div class="toast-container" aria-live="polite" aria-atomic="true"></div>

    {{-- GLOBAL LOADING --}}
    <div class="loading" role="status" aria-live="polite">
        <div class="loading-content">
            <div class="spinner"></div>
            <div>Sedang memproses...</div>
        </div>
    </div>

    {{-- ======================================================
         LUCIDE — versi dikunci (bukan @latest) supaya tidak ada
         redirect tambahan & bisa di-cache lama oleh browser
    ======================================================= --}}
    <script src="https://cdn.jsdelivr.net/npm/lucide@0/dist/umd/lucide.min.js"></script>

    {{-- ======================================================
         APEXCHARTS sengaja TIDAK dimuat global lagi (±500 KB).
         - Dashboard memuatnya sendiri saat grafik terlihat.
         - Halaman lain yang butuh grafik cukup menambahkan:
             @push('scripts')
                 <script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
                 ...kode grafik...
             @endpush
    ======================================================= --}}

    {{-- PROJECT JAVASCRIPT — semua fungsi admin ada di app.js --}}
    <script src="{{ asset('js/app.js') }}"></script>

    {{-- ======================================================
         INIT: ikon Lucide + notifikasi flash (digabung jadi satu)
    ======================================================= --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.lucide?.createIcons();

            const flashes = @json($flashes);

            if (typeof showToast === 'function') {
                flashes.forEach((f) => showToast(f.type, f.title, f.message));
            }
        });
    </script>

    {{-- SCRIPT KHUSUS HALAMAN --}}
    @stack('scripts')

</body>

</html>

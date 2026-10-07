<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1d120d">

    <title>Riwayat Transaksi — {{ $setting?->store_name ?? 'Marimoi Cafe' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    @if ($setting?->favicon)
        <link rel="icon" href="{{ $setting->favicon_url }}" type="image/png">
    @endif

    <style>
        :root {
            --espresso: #1d120d;
            --espresso-soft: #2a1a14;
            --coffee: #5a3525;
            --coffee-light: #81563f;
            --caramel: #c98954;
            --gold: #d7a66a;

            --cream: #fbf7f0;
            --cream-dark: #f3ebe0;

            --white: #ffffff;
            --ink: #241914;
            --muted: #8b7d74;
            --muted-light: #aa9e96;

            --line: rgba(36, 25, 20, .08);

            --shadow-soft:
                0 10px 30px rgba(29, 18, 13, .06);

            --shadow-card:
                0 24px 70px rgba(29, 18, 13, .09);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(
                    circle at 15% 0%,
                    rgba(201, 137, 84, .10),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 100% 20%,
                    rgba(90, 53, 37, .06),
                    transparent 30%
                ),
                var(--cream);

            color: var(--ink);
            font-family: 'DM Sans', sans-serif;

            padding:
                28px 16px 60px;

            -webkit-font-smoothing: antialiased;
        }

        .wrap {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }

        /* =========================
           HEADER
        ========================= */

        .top-bar {
            display: flex;
            align-items: center;
            gap: 14px;

            margin-bottom: 22px;
        }

        .back-button {
            width: 42px;
            height: 42px;

            flex-shrink: 0;

            display: grid;
            place-items: center;

            border-radius: 14px;

            background: rgba(255, 255, 255, .92);

            color: var(--coffee);

            text-decoration: none;

            border: 1px solid rgba(36, 25, 20, .06);

            box-shadow:
                0 8px 25px rgba(29, 18, 13, .07);

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .back-button i {
            font-size: 14px;
        }

        .back-button:hover {
            background: var(--white);

            transform: translateX(-2px);

            box-shadow:
                0 12px 30px rgba(29, 18, 13, .11);
        }

        .header-content {
            min-width: 0;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 7px;

            margin-bottom: 3px;

            color: var(--caramel);

            font-size: 9px;
            font-weight: 700;

            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";

            width: 18px;
            height: 1px;

            background: var(--caramel);
        }

        .top-bar h1 {
            font-family: 'Playfair Display', serif;

            font-size: 22px;
            line-height: 1.15;

            color: var(--espresso);

            letter-spacing: -.2px;
        }

        .subtitle {
            margin-top: 4px;

            color: var(--muted);

            font-size: 11px;
        }

        /* =========================
           MAIN CARD
        ========================= */

        .card {
            position: relative;

            background: rgba(255, 255, 255, .96);

            border: 1px solid rgba(36, 25, 20, .06);

            border-radius: 26px;

            overflow: hidden;

            box-shadow: var(--shadow-card);

            backdrop-filter: blur(10px);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 18px 20px;

            border-bottom: 1px solid var(--line);

            background:
                linear-gradient(
                    180deg,
                    rgba(251, 247, 240, .7),
                    rgba(255, 255, 255, .95)
                );
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title-icon {
            width: 34px;
            height: 34px;

            display: grid;
            place-items: center;

            border-radius: 11px;

            background:
                linear-gradient(
                    145deg,
                    #f2dfca,
                    #f8eee3
                );

            color: var(--coffee);

            font-size: 13px;
        }

        .card-title strong {
            display: block;

            color: var(--espresso);

            font-size: 13px;
            font-weight: 700;
        }

        .card-title small {
            display: block;

            margin-top: 2px;

            color: var(--muted);

            font-size: 10px;
        }

        .history-count {
            padding: 6px 9px;

            border-radius: 9px;

            background: var(--cream);

            color: var(--coffee);

            font-size: 10px;
            font-weight: 700;
        }

        /* =========================
           ORDER ITEM
        ========================= */

        .order-item {
            position: relative;

            padding: 18px 20px;

            border-bottom: 1px solid var(--line);

            transition:
                background .2s ease,
                padding-left .2s ease;
        }

        .order-item:last-child {
            border-bottom: 0;
        }

        .order-item:hover {
            background:
                linear-gradient(
                    90deg,
                    rgba(201, 137, 84, .045),
                    transparent
                );
        }

        .order-main {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .receipt-icon {
            width: 42px;
            height: 42px;

            flex-shrink: 0;

            display: grid;
            place-items: center;

            border-radius: 13px;

            background:
                linear-gradient(
                    145deg,
                    #f1dfca,
                    #fbf5ec
                );

            color: var(--coffee);

            box-shadow:
                inset 0 0 0 1px rgba(90, 53, 37, .05);
        }

        .receipt-icon i {
            font-size: 14px;
        }

        .order-content {
            min-width: 0;
            flex: 1;
        }

        .order-top {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 12px;
        }

        .invoice {
            color: var(--espresso);

            font-size: 12px;
            font-weight: 700;

            letter-spacing: .15px;
        }

        .order-total {
            white-space: nowrap;

            color: var(--coffee);

            font-size: 13px;
            font-weight: 700;
        }

        .order-bottom {
            display: flex;
            align-items: center;

            gap: 9px;

            margin-top: 5px;

            color: var(--muted);

            font-size: 10.5px;
        }

        .order-bottom span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .order-bottom i {
            color: var(--caramel);

            font-size: 9px;
        }

        .order-divider {
            width: 3px;
            height: 3px;

            border-radius: 50%;

            background: var(--muted-light);
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {
            padding: 70px 24px 75px;

            text-align: center;
        }

        .empty-icon {
            position: relative;

            width: 70px;
            height: 70px;

            margin: 0 auto 20px;

            display: grid;
            place-items: center;

            border-radius: 22px;

            background:
                linear-gradient(
                    145deg,
                    #f1dfca,
                    #fbf5ed
                );

            color: var(--coffee);

            box-shadow:
                0 14px 30px rgba(90, 53, 37, .08);
        }

        .empty-icon::after {
            content: "";

            position: absolute;

            inset: -7px;

            border-radius: 27px;

            border: 1px solid rgba(201, 137, 84, .12);
        }

        .empty-icon i {
            font-size: 24px;
        }

        .empty-state h3 {
            margin-bottom: 7px;

            font-family: 'Playfair Display', serif;

            color: var(--espresso);

            font-size: 18px;
        }

        .empty-state p {
            max-width: 260px;

            margin: 0 auto;

            color: var(--muted);

            font-size: 11px;
            line-height: 1.7;
        }

        /* =========================
           PAGINATION
        ========================= */

        .pagination-wrap {
            margin-top: 18px;

            display: flex;
            justify-content: center;

            width: 100%;
        }

        .pagination-wrap nav {
            width: 100%;
        }

        .pagination-wrap nav > div:first-child {
            margin-bottom: 10px;

            color: var(--muted);

            text-align: center;

            font-size: 10px;
        }

        .pagination-wrap nav > div:last-child {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
        }

        .pagination-wrap a,
        .pagination-wrap span {
            min-width: 34px;
            height: 34px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 0 10px;

            margin: 0;

            border-radius: 10px;

            border: 1px solid rgba(36, 25, 20, .07);

            background: rgba(255, 255, 255, .92);

            color: var(--coffee);

            font-size: 10.5px;
            font-weight: 600;

            text-decoration: none;

            box-shadow:
                0 5px 15px rgba(29, 18, 13, .04);

            transition:
                background .2s ease,
                color .2s ease,
                transform .2s ease;
        }

        .pagination-wrap a:hover {
            background: var(--coffee);

            color: white;

            transform: translateY(-1px);
        }

        .pagination-wrap span[aria-current="page"] {
            background: var(--espresso);

            color: white;

            border-color: var(--espresso);
        }

        /* =========================
           FOOTER
        ========================= */

        .footer-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;

            margin-top: 22px;

            color: var(--muted-light);

            font-size: 9.5px;
        }

        .footer-note i {
            color: var(--caramel);

            font-size: 8px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 380px) {
            body {
                padding: 22px 12px 50px;
            }

            .top-bar h1 {
                font-size: 20px;
            }

            .card {
                border-radius: 22px;
            }

            .order-item {
                padding: 16px;
            }

            .receipt-icon {
                width: 39px;
                height: 39px;
            }

            .order-total {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

    <div class="wrap">

        <!-- HEADER -->

        <div class="top-bar">

            <a href="{{ route('member.portal') }}"
                class="back-button"
                aria-label="Kembali">

                <i class="fa-solid fa-arrow-left"></i>

            </a>

            <div class="header-content">

                <div class="eyebrow">
                    Marimoi Member
                </div>

                <h1>Riwayat Transaksi</h1>

                <p class="subtitle">
                    Lihat kembali perjalanan transaksi Anda
                </p>

            </div>

        </div>


        <!-- TRANSACTION CARD -->

        <div class="card">

            <div class="card-header">

                <div class="card-title">

                    <div class="card-title-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>

                    <div>
                        <strong>Transaksi Anda</strong>
                        <small>Riwayat pembelian</small>
                    </div>

                </div>

                @if ($orders->total() > 0)
                    <div class="history-count">
                        {{ $orders->total() }} transaksi
                    </div>
                @endif

            </div>


            @forelse ($orders as $order)

                <div class="order-item">

                    <div class="order-main">

                        <div class="receipt-icon">
                            <i class="fa-solid fa-receipt"></i>
                        </div>

                        <div class="order-content">

                            <div class="order-top">

                                <b class="invoice">
                                    INV{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                                </b>

                                <span class="order-total">
                                    Rp {{ number_format($order->total, 0, ',', '.') }}
                                </span>

                            </div>


                            <div class="order-bottom">

                                <span>
                                    <i class="fa-regular fa-calendar"></i>

                                    {{ \Illuminate\Support\Carbon::parse($order->transaction_time)->translatedFormat('d M Y, H:i') }}
                                </span>

                                <span class="order-divider"></span>

                                <span>
                                    <i class="fa-solid fa-bag-shopping"></i>

                                    {{ $order->total_item }} item
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            @empty

                <div class="empty-state">

                    <div class="empty-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>

                    <h3>Belum Ada Transaksi</h3>

                    <p>
                        Riwayat transaksi Anda akan muncul di sini
                        setelah melakukan pembelian di Marimoi.
                    </p>

                </div>

            @endforelse

        </div>


        <!-- PAGINATION -->

        @if ($orders->hasPages())

            <div class="pagination-wrap">
                {{ $orders->links() }}
            </div>

        @endif


        <!-- FOOTER -->

        <div class="footer-note">

            <i class="fa-solid fa-mug-hot"></i>

            <span>
                Terima kasih telah menjadi bagian dari Marimoi
            </span>

        </div>

    </div>

</body>

</html>


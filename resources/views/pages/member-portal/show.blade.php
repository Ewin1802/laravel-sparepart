<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1d120d">

    <title>Kartu Member Saya — {{ $setting?->store_name ?? 'Marimoi Cafe' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    @if ($setting?->favicon)
        <link rel="icon" href="{{ $setting->favicon_url }}" type="image/png">
    @endif

    <style>
        :root {
            --espresso: #1d120d;
            --coffee: #5a3525;
            --caramel: #c98954;
            --gold: #f6d6ad;
            --cream: #fbf7f0;
            --ink: #241914;
            --muted: #7c7069;
            --line: rgba(36, 25, 20, .10);
            --green: #2f7d4a;
            --red: #c0392b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: var(--cream);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            padding: 40px 20px;
        }

        .wrap {
            max-width: 440px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 30px 80px rgba(29, 18, 13, .12);
            overflow: hidden;
            margin-bottom: 18px;
        }

        .card-header {
            padding: 28px 32px 22px;
            background: var(--espresso);
            color: #fff;
            text-align: center;
        }

        .card-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .card-header p {
            font-size: 12px;
            color: rgba(255, 255, 255, .6);
        }

        .card-body {
            padding: 30px 32px;
            text-align: center;
        }

        /* ---------- ALERT ---------- */

        .alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 12.5px;
            text-align: left;
            line-height: 1.6;
        }

        .alert-success {
            background: rgba(47, 125, 74, .08);
            border: 1px solid rgba(47, 125, 74, .25);
            color: var(--green);
        }

        .alert-error {
            background: rgba(192, 57, 43, .08);
            border: 1px solid rgba(192, 57, 43, .25);
            color: var(--red);
        }

        /* ---------- QR ---------- */

        .qr-box {
            margin-bottom: 20px;
            padding: 18px;
            border-radius: 18px;
            border: 1px dashed var(--line);
            background: var(--cream);
        }

        .qr-box img {
            display: block;
            margin: 0 auto;
            border-radius: 10px;
            background: #fff;
            padding: 8px;
        }

        .member-code {
            margin-bottom: 22px;
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--coffee);
        }

        /* ---------- STATUS BADGE ---------- */

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 22px;
        }

        .status-badge.active {
            background: rgba(47, 125, 74, .10);
            color: var(--green);
        }

        .status-badge.inactive {
            background: rgba(192, 57, 43, .10);
            color: var(--red);
        }

        /* ---------- STAMP CARD ---------- */

        .stamp-card {
            text-align: left;
            padding: 22px;
            border-radius: 20px;
            background: var(--espresso);
            color: #fff;
            margin-bottom: 12px;
        }

        .stamp-card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .stamp-card-head h3 {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            margin-bottom: 3px;
        }

        .stamp-card-head span {
            font-size: 10.5px;
            color: var(--gold);
            font-weight: 600;
        }

        .stamp-count-pill {
            padding: 5px 11px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .10);
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .stamp-dots {
            display: flex;
            gap: 6px;
            margin-bottom: 14px;
        }

        .stamp-dot {
            flex: 1;
            aspect-ratio: 1;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 13px;
            border: 1.5px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .06);
            color: rgba(255, 255, 255, .3);
        }

        .stamp-dot.filled {
            background: var(--gold);
            border-color: var(--gold);
            color: var(--espresso);
        }

        .stamp-status-text {
            font-size: 11px;
            color: rgba(255, 255, 255, .65);
        }

        .stamp-note {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed rgba(255, 255, 255, .16);
            font-size: 10.5px;
            line-height: 1.6;
            color: rgba(255, 255, 255, .55);
        }

        .stamp-note i {
            margin-top: 1px;
            color: var(--gold);
        }

        .stamp-note strong {
            color: var(--gold);
            font-weight: 700;
        }

        .btn-redeem {
            display: inline-flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 48px;
            margin-top: 16px;
            border: 0;
            border-radius: 14px;
            background: var(--gold);
            color: var(--espresso);
            font-size: 13.5px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
        }

        /* ---------- LOGOUT ---------- */

        .btn-logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 46px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: transparent;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: .2s ease;
        }

        .btn-logout:hover {
            border-color: var(--caramel);
            color: var(--caramel);
        }

        /* ---------- HISTORY CARD ---------- */

        .history-card {
            padding: 24px 26px;
        }

        .history-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .history-head h3 {
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            color: var(--coffee);
        }

        .history-head a {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--caramel);
            text-decoration: none;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--line);
            text-align: left;
        }

        .history-item:last-child {
            border-bottom: 0;
        }

        .history-item .icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--cream);
            color: var(--caramel);
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .history-item .info {
            flex: 1;
            padding: 0 12px;
        }

        .history-item .info b {
            display: block;
            font-size: 12.5px;
        }

        .history-item .info small {
            color: var(--muted);
            font-size: 10.5px;
        }

        .history-item .amount {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--coffee);
            white-space: nowrap;
        }

        .history-empty {
            padding: 20px 0;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }
    </style>
</head>

<body>

    <div class="wrap">

        <div class="card">

            <div class="card-header">
                <h1>Kartu Member Saya</h1>
                <p>{{ $user->name }}</p>
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif

                <div class="qr-box">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data={{ urlencode($member->code) }}"
                        alt="QR Code Member" width="220" height="220" loading="lazy">
                </div>

                <div class="member-code">{{ $member->code }}</div>

                <span class="status-badge {{ $member->is_active ? 'active' : 'inactive' }}">
                    <i class="fa-solid {{ $member->is_active ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                    {{ $member->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>

                {{-- ============================================ --}}
                {{-- STAMP CARD --}}
                {{-- ============================================ --}}

                <div class="stamp-card">

                    <div class="stamp-card-head">
                        <div>
                            <h3>Kumpulkan Stamp</h3>
                            <span>{{ $mysteryReady ? 'Reward kamu sudah siap!' : 'Dapatkan Minuman Gratis!' }}</span>
                        </div>

                        <span class="stamp-count-pill">{{ $member->stamp_count }} / {{ $member->stamp_target }}</span>
                    </div>

                    <div class="stamp-dots">
                        @for ($i = 1; $i <= $member->stamp_target; $i++)
                            <span class="stamp-dot {{ $i <= $member->stamp_count ? 'filled' : '' }}">
                                <i class="fa-solid fa-mug-hot"></i>
                            </span>
                        @endfor
                    </div>

                    <div class="stamp-status-text">
                        @if ($mysteryReady)
                            Mystery Box siap digunakan 🎁
                        @else
                            {{ $member->stamp_target - $member->stamp_count }} kunjungan lagi untuk Mystery Box
                        @endif
                    </div>

                    <div class="stamp-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Stamp diberikan untuk transaksi dengan minimal belanja <strong>Rp50.000</strong>.</span>
                    </div>

                    @if ($mysteryReady)
                        <form method="POST" action="{{ route('member.portal.redeem') }}"
                            onsubmit="return confirm('Redeem Mystery Box sekarang? Stamp kamu akan direset.')">
                            @csrf
                            <button type="submit" class="btn-redeem">
                                <i class="fa-solid fa-gift"></i>
                                Redeem Mystery Box
                            </button>
                        </form>
                    @endif

                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </button>
                </form>

            </div>

        </div>

        {{-- ================================================ --}}
        {{-- RIWAYAT TRANSAKSI TERAKHIR --}}
        {{-- ================================================ --}}

        <div class="card history-card">

            <div class="history-head">
                <h3>Riwayat Transaksi</h3>
                <a href="{{ route('member.portal.history') }}">Lihat Semua</a>
            </div>

            @forelse ($recentOrders as $order)
                <div class="history-item">
                    <span class="icon"><i class="fa-solid fa-receipt"></i></span>

                    <div class="info">
                        <b>INV{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</b>
                        <small>{{ \Illuminate\Support\Carbon::parse($order->transaction_time)->translatedFormat('d M Y, H:i') }}</small>
                    </div>

                    <span class="amount">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            @empty
                <div class="history-empty">Belum ada transaksi.</div>
            @endforelse

        </div>

    </div>

</body>

</html>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1d120d">

    <title>Pendaftaran Berhasil — {{ $setting->store_name ?? 'Marimoi Cafe' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    @if ($setting && $setting->favicon)
        <link rel="icon" href="{{ $setting->favicon_url }}" type="image/png">
    @endif

    <style>
        :root {
            --espresso: #1d120d;
            --coffee: #5a3525;
            --caramel: #c98954;
            --cream: #fbf7f0;
            --ink: #241914;
            --muted: #7c7069;
            --line: rgba(36, 25, 20, .10);
            --green: #2f7d4a;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 30px 80px rgba(29, 18, 13, .12);
            padding: 40px 32px;
            text-align: center;
        }

        .success-icon {
            width: 68px;
            height: 68px;
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(47, 125, 74, .10);
            color: var(--green);
            font-size: 30px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 26px;
        }

        .member-code-box {
            padding: 20px;
            margin-bottom: 26px;
            border-radius: 18px;
            background: var(--espresso);
            color: #fff;
        }

        .member-code-box small {
            display: block;
            font-size: 10.5px;
            color: rgba(255, 255, 255, .55);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .member-code-box strong {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            letter-spacing: 2px;
            color: #f6d6ad;
        }

        .qr-box {
            margin-bottom: 26px;
            padding: 18px;
            border-radius: 18px;
            border: 1px dashed var(--line);
            background: var(--cream);
        }

        .qr-box img {
            display: block;
            margin: 0 auto 10px;
            border-radius: 10px;
            background: #fff;
            padding: 8px;
        }

        .qr-box span {
            display: block;
            font-size: 11px;
            color: var(--muted);
        }

        .info-list {
            text-align: left;
            margin-bottom: 26px;
        }

        .info-list div {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--line);
            font-size: 12.5px;
        }

        .info-list div:last-child {
            border-bottom: 0;
        }

        .info-list i {
            color: var(--caramel);
            width: 18px;
            padding-top: 2px;
        }

        .info-list b {
            display: block;
            margin-bottom: 2px;
        }

        .info-list span {
            color: var(--muted);
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 48px;
            padding: 0 24px;
            border-radius: 14px;
            background: var(--caramel);
            color: #fff;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            transition: .25s ease;
        }

        .btn-home:hover {
            background: #b97746;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <div class="card">

        <div class="success-icon">
            <i class="fa-solid fa-check"></i>
        </div>

        <h1>Pendaftaran Berhasil!</h1>

        <p class="subtitle">
            Selamat datang, {{ $member['name'] }}! Kartu member kamu sudah aktif.
        </p>

        <div class="member-code-box">
            <small>Kode Member Kamu</small>
            <strong>{{ $member['code'] }}</strong>
        </div>

        <div class="qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=8&data={{ urlencode($member['code']) }}"
                alt="QR Code Member" width="220" height="220" loading="lazy">
            <span>Tunjukkan QR ini ke kasir untuk di-scan pada saat transaksi</span>
        </div>

        <div class="info-list">

            <div>
                <i class="fa-solid fa-mobile-screen-button"></i>
                <div>
                    <b>Punya HP Android?</b>
                    <span>Download aplikasi Marimoi Member, lalu login pakai email
                        <strong>{{ $member['email'] }}</strong> dan password yang baru saja kamu buat.</span>
                </div>
            </div>

            <div>
                <i class="fa-solid fa-cash-register"></i>
                <div>
                    <b>Belum punya app / pakai iPhone?</b>
                    <span>Tunjukkan QR code di atas ke kasir {{ $setting->store_name ?? 'Marimoi Cafe' }} untuk di-scan
                        setiap kali transaksi, biar stamp tetap kehitung. Simpan/screenshot halaman ini, atau
                        login lagi kapan aja di <a
                            href="{{ route('member.portal') }}">{{ route('member.portal') }}</a>.</span>
                </div>
            </div>

            <div>
                <i class="fa-solid fa-stamp"></i>
                <div>
                    <b>Syarat dapat stamp</b>
                    <span>Stamp diberikan untuk transaksi dengan minimal belanja
                        <strong>Rp50.000</strong>.</span>
                </div>
            </div>

        </div>

        <a href="{{ route('landing') }}" class="btn-home">
            <i class="fa-solid fa-house"></i>
            Kembali ke Beranda
        </a>

    </div>

</body>

</html>

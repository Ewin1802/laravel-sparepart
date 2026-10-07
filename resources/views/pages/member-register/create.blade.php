<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Member — {{ $setting?->store_name ?? 'Marimoi Cafe' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        :root {
            --espresso: #1d120d;
            --coffee: #5a3525;
            --coffee-light: #8a5a40;
            --caramel: #c98954;
            --cream: #fbf7f0;
            --cream-2: #f4ecdf;
            --ink: #241914;
            --muted: #7c7069;
            --line: rgba(36, 25, 20, .10);
            --danger: #c0392b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
            background: var(--cream);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            line-height: 1.6;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible {
            outline: 2px solid var(--caramel);
            outline-offset: 3px;
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 26px;
            box-shadow: 0 30px 70px rgba(29, 18, 13, .12);
            overflow: hidden;
        }

        .card-header {
            padding: 34px 32px 26px;
            background: var(--espresso);
            color: #fff;
            text-align: center;
        }

        .card-header .brand-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 14px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: var(--coffee);
            color: #f6d6ad;
            font-size: 22px;
        }

        .card-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .card-header p {
            color: rgba(255, 255, 255, .65);
            font-size: 12.5px;
        }

        .card-body {
            padding: 30px 32px 34px;
        }

        .alert-error {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 12px;
            background: rgba(192, 57, 43, .08);
            border: 1px solid rgba(192, 57, 43, .25);
            color: var(--danger);
            font-size: 12.5px;
        }

        .alert-error ul {
            padding-left: 18px;
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--coffee);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-control {
            width: 100%;
            height: 48px;
            padding: 0 15px;
            border: 1px solid var(--line);
            border-radius: 13px;
            background: var(--cream-2);
            color: var(--ink);
            font-size: 13.5px;
            font-family: inherit;
            transition: .2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--caramel);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(201, 137, 84, .12);
        }

        .form-control.is-invalid {
            border-color: var(--danger);
        }

        .invalid-feedback {
            margin-top: 6px;
            color: var(--danger);
            font-size: 11px;
        }

        .form-hint {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 10.5px;
        }

        .btn-submit {
            width: 100%;
            height: 50px;
            margin-top: 6px;
            border: 0;
            border-radius: 14px;
            background: var(--caramel);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
        }

        .btn-submit:hover {
            background: #b97746;
        }

        .footer-note {
            margin-top: 22px;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }

        .footer-note a {
            color: var(--coffee);
            font-weight: 700;
            text-decoration: none;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .card-header {
                padding: 28px 22px 22px;
            }

            .card-body {
                padding: 24px 22px 28px;
            }
        }
    </style>
</head>

<body>

    <div class="card">

        <div class="card-header">

            <div class="brand-icon">
                <i class="fa-solid fa-mug-hot"></i>
            </div>

            <h1>Gabung Jadi Member</h1>
            <p>Kumpulkan stamp & minuman gratis di {{ $setting?->store_name ?? 'Marimoi Cafe' }}.</p>

        </div>

        <div class="card-body">

            @if ($errors->any())
                <div class="alert-error">
                    Ada beberapa data yang perlu diperbaiki:
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('member.register.store') }}">

                @csrf

                <div class="form-group">

                    <label for="name">Nama Lengkap</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror"
                        placeholder="Contoh: Abing Pontoh"
                        required>

                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                </div>

                <div class="form-group">

                    <label for="email">Email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="nama@email.com"
                        required>

                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label for="phone_number">Nomor HP</label>

                        <input
                            type="text"
                            id="phone_number"
                            name="phone_number"
                            value="{{ old('phone_number') }}"
                            class="form-control @error('phone_number') is-invalid @enderror"
                            placeholder="0812xxxxxxxx">

                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                    </div>

                    <div class="form-group">

                        <label for="birth_date">Tanggal Lahir</label>

                        <input
                            type="date"
                            id="birth_date"
                            name="birth_date"
                            value="{{ old('birth_date') }}"
                            class="form-control @error('birth_date') is-invalid @enderror"
                            required>

                        @error('birth_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label for="password">Password</label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Minimal 6 karakter"
                            required>

                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                    </div>

                    <div class="form-group">

                        <label for="password_confirmation">Ulangi Password</label>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            placeholder="Ulangi password"
                            required>

                    </div>

                </div>

                <small class="form-hint">
                    Data ini juga bisa dipakai untuk login di aplikasi Marimoi Member (Android) kalau nanti kamu install.
                </small>

                <button type="submit" class="btn-submit">
                    Daftar Sekarang
                </button>

            </form>

            <p class="footer-note">
                Sudah punya aplikasi Android?
                <a href="{{ route('login') }}">Login di sini</a>
            </p>

        </div>

    </div>

</body>

</html>

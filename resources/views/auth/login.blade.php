<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - CRM_SIVMI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="halaman-login">
        <form class="kartu-login" method="POST" action="{{ route('login.masuk') }}">
            @csrf
            <div>
                <span class="logo">SI</span>
                <h1>CRM_SIVMI</h1>
                
            </div>

            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email') <small class="error">{{ $message }}</small> @enderror
            </label>
            <label>
                Password
                <span class="bungkus-password">
                    <input type="password" name="password" required data-input-password>
                    <button class="tombol-mata-password" type="button" data-toggle-password aria-label="Tampilkan password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </span>
                @error('password') <small class="error">{{ $message }}</small> @enderror
            </label>
            <label class="cek">
                <input type="checkbox" name="ingat" value="1">
                Ingat saya
            </label>
            <button class="tombol utama" type="submit">Masuk</button>
        </form>
    </main>
</body>
</html>

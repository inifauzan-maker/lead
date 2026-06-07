<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistem Informasi Leads</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="halaman-login">
        <form class="kartu-login" method="POST" action="{{ route('login.masuk') }}">
            @csrf
            <div>
                <span class="logo">SI</span>
                <h1>Masuk Sistem Leads</h1>
                <p>Gunakan akun sesuai role dan cabang yang diberikan.</p>
            </div>

            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email') <small class="error">{{ $message }}</small> @enderror
            </label>
            <label>
                Password
                <input type="password" name="password" required>
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

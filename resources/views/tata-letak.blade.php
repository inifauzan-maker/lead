<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ?? 'CRM_SIVMI' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @php
        $userAktif = auth()->user();
        $jumlahNotifikasi = \Illuminate\Support\Facades\Schema::hasTable('notifications')
            ? \App\Models\SistemNotification::query()
                ->whereNull('dibaca_pada')
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $userAktif->id))
                ->count()
            : \App\Models\Prospek::query()
                ->whereIn('status', ['Dihubungi', 'Follow Up'])
                ->when(! $userAktif->aksesSemuaCabang() && $userAktif->role === 'staff', fn ($query) => $query->where('user_id', $userAktif->id))
                ->when(! $userAktif->aksesSemuaCabang() && $userAktif->role !== 'staff', fn ($query) => $query->where('cabang', $userAktif->cabang))
                ->count();
        $inisialUser = collect(explode(' ', trim($userAktif->name)))
            ->filter()
            ->take(2)
            ->map(fn ($nama) => strtoupper(substr($nama, 0, 1)))
            ->implode('');
        [$namaEmail, $domainEmail] = array_pad(explode('@', $userAktif->email, 2), 2, '');
        $emailTersamar = str_repeat('*', min(4, max(1, strlen($namaEmail)))).($domainEmail ? '@'.$domainEmail : '');
    @endphp
    <div class="aplikasi" data-app>
        <aside class="sidebar" data-sidebar>
            <div class="merek">
                <span class="logo">SI</span>
                <span class="teks-merek">
                    CRM_SIVMI
                </span>
            </div>
            <nav class="menu">
                <a class="menu-item {{ request()->routeIs('dashboard') ? 'aktif' : '' }}" href="{{ route('dashboard') }}">
                    <span>Dashboard</span>
                </a>
                <a class="menu-item {{ request()->routeIs('prospek.*') ? 'aktif' : '' }}" href="{{ route('prospek.index') }}">
                    <span>Data Leads</span>
                </a>
                <a class="menu-item {{ request()->routeIs('follow-up.*') ? 'aktif' : '' }}" href="{{ route('follow-up.index') }}">
                    <span>Follow Up</span>
                </a>
                <a class="menu-item {{ request()->routeIs('data-siswa.*') ? 'aktif' : '' }}" href="{{ route('data-siswa.index') }}">
                    <span>Data Siswa</span>
                </a>
                @if (auth()->user()->bisaInputLeads())
                    <a class="menu-item" href="{{ route('prospek.create') }}">
                        <span>Tambah Leads</span>
                    </a>
                @endif
                @if (auth()->user()->bisaKelolaPengguna())
                    <a class="menu-item {{ request()->routeIs('pengaturan.*') ? 'aktif' : '' }}" href="{{ route('pengaturan.index') }}">
                        <span>Pengaturan</span>
                    </a>
                @endif
                @if (in_array(auth()->user()->role, ['superadmin', 'direksi'], true))
                    <a class="menu-item {{ request()->routeIs('log-aktivitas.*') ? 'aktif' : '' }}" href="{{ route('log-aktivitas.index') }}">
                        <span>Log Aktivitas</span>
                    </a>
                @endif
            </nav>
        </aside>

        <div class="overlay" data-overlay></div>

        <main class="konten">
            <header class="bar-atas">
                <button class="tombol-icon" type="button" data-toggle-sidebar aria-label="Buka tutup sidebar">
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <h1>{{ $judul ?? 'CRM_SIVMI' }}</h1>
                </div>
                <a class="ikon-notifikasi" href="{{ route('notifikasi.index') }}" aria-label="Notifikasi sistem">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M15 17H9m10-2.5c-.9-.9-1.4-2.1-1.4-3.4V9a5.6 5.6 0 0 0-11.2 0v2.1c0 1.3-.5 2.5-1.4 3.4L4 15.5V17h16v-1.5l-1-1ZM13.7 19a2 2 0 0 1-3.4 0"/>
                    </svg>
                    @if ($jumlahNotifikasi > 0)
                        <span>{{ $jumlahNotifikasi > 99 ? '99+' : $jumlahNotifikasi }}</span>
                    @endif
                </a>
                <div class="profil-menu" data-profil-menu>
                    <button class="avatar-user avatar-header tombol-profil" type="button" data-toggle-profil aria-expanded="false" aria-label="Buka menu profil {{ auth()->user()->name }}">
                        {{ $inisialUser ?: 'U' }}
                    </button>
                    <div class="dropdown-profil" data-dropdown-profil>
                        <div class="ringkasan-dropdown-profil">
                            <span class="avatar-user avatar-dropdown">{{ $inisialUser ?: 'U' }}</span>
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>{{ $emailTersamar }}</small>
                        </div>

                        <div class="paket-profil">
                            <strong>{{ auth()->user()->roleLabel() }}</strong>
                            <span>{{ auth()->user()->cabang ?: 'Semua cabang' }}</span>
                            <small>{{ $jumlahNotifikasi }} notifikasi belum dibaca</small>
                        </div>

                        <nav class="daftar-menu-profil" aria-label="Menu profil">
                            <a href="{{ route('profil.index') }}">
                                <span class="ikon-menu-profil">◎</span>
                                Informasi Akun
                            </a>
                            <a href="{{ route('profil.pembelajaran') }}">
                                <span class="ikon-menu-profil">▣</span>
                                Pembelajaran
                            </a>
                            <a href="{{ route('profil.laporan') }}">
                                <span class="ikon-menu-profil">▤</span>
                                Laporan
                            </a>
                            <a href="{{ route('notifikasi.index') }}">
                                <span class="ikon-menu-profil">◇</span>
                                Notifikasi
                            </a>
                            @if (auth()->user()->bisaKelolaPengguna())
                                <a href="{{ route('pengaturan.index') }}">
                                    <span class="ikon-menu-profil">⌘</span>
                                    Pengaturan Sistem
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">
                                    <span class="ikon-menu-profil">↪</span>
                                    Keluar
                                </button>
                            </form>
                        </nav>
                    </div>
                </div>
            </header>

            @if (session('berhasil'))
                <div class="notifikasi">{{ session('berhasil') }}</div>
            @endif

            @if ($errors->any())
                <div class="notifikasi error-box">
                    <strong>Periksa input:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('konten')
        </main>
    </div>

    <div class="modal-konfirmasi" data-modal-konfirmasi hidden>
        <div class="kartu-konfirmasi" role="dialog" aria-modal="true" aria-labelledby="judul-konfirmasi">
            <span class="label-konfirmasi">Konfirmasi</span>
            <h2 id="judul-konfirmasi" data-judul-konfirmasi>Konfirmasi aksi</h2>
            <p data-pesan-konfirmasi>Pastikan data sudah benar sebelum melanjutkan.</p>
            <div class="aksi-konfirmasi">
                <button class="tombol sekunder" type="button" data-batal-konfirmasi>Batal</button>
                <button class="tombol bahaya" type="button" data-setuju-konfirmasi>Hapus</button>
            </div>
        </div>
    </div>
</body>
</html>

@extends('tata-letak', ['judul' => 'Profil User'])

@section('konten')
    @php
        $inisialUser = collect(explode(' ', trim(auth()->user()->name)))
            ->filter()
            ->take(2)
            ->map(fn ($nama) => strtoupper(substr($nama, 0, 1)))
            ->implode('');
    @endphp

    <section class="hero-profil">
        <div class="avatar-user avatar-profil-besar">{{ $inisialUser ?: 'U' }}</div>
        <div>
            <span>Dashboard Personal</span>
            <h2>{{ auth()->user()->name }}</h2>
            <p>{{ auth()->user()->roleLabel() }}{{ auth()->user()->cabang ? ' - '.auth()->user()->cabang : ' - Semua cabang' }}</p>
        </div>
        <div class="skor-profil">
            <strong>{{ $ringkasanProfil['rasioClosing'] }}%</strong>
            <span>Rasio closing</span>
        </div>
    </section>

    <section class="grid-kartu-dashboard-profil">
        <a class="kartu-dashboard-profil oranye" href="#sosial">
            <div class="kepala-dashboard-profil">
                <i>SO</i>
                <strong>Media Sosial</strong>
            </div>
            <p>Terhubung dengan {{ $ringkasanProfil['sosial'] }} akun sosial</p>
            <div class="meta-dashboard-profil ikon-sosial-profil">
                @forelse ($sosialTerhubung as $nama => $tautan)
                    <span title="{{ $nama }}">{{ strtoupper(substr($nama, 0, 2)) }}</span>
                @empty
                    <small>Belum ada akun sosial</small>
                @endforelse
            </div>
        </a>
        <a class="kartu-dashboard-profil biru" href="{{ route('prospek.index') }}">
            <div class="kepala-dashboard-profil">
                <i>LD</i>
                <strong>Leads</strong>
            </div>
            <p>{{ $ringkasanProfil['laporan'] }} leads dalam akses akun ini</p>
            <div class="stat-dashboard-profil">
                <span><strong>{{ $ringkasanProfil['baru'] }}</strong> Baru</span>
                <span><strong>{{ $ringkasanProfil['proses'] }}</strong> Proses</span>
                <span><strong>{{ $ringkasanProfil['closing'] }}</strong> Deal</span>
            </div>
        </a>
        <a class="kartu-dashboard-profil hijau" href="{{ route('profil.pembelajaran') }}">
            <div class="kepala-dashboard-profil">
                <i>PB</i>
                <strong>Pembelajaran</strong>
            </div>
            <p>{{ $ringkasanProfil['pembelajaran'] }} kursus tersedia</p>
            <div class="stat-dashboard-profil">
                <span><strong>{{ $ringkasanProfil['kelasSelesai'] }}</strong> Selesai</span>
                <span><strong>{{ $ringkasanProfil['kelasBerjalan'] }}</strong> Berjalan</span>
            </div>
        </a>
        <a class="kartu-dashboard-profil ungu" href="{{ route('profil.tugas') }}">
            <div class="kepala-dashboard-profil">
                <i>TG</i>
                <strong>Tugas</strong>
            </div>
            <p>{{ $ringkasanProfil['tugas'] }} tugas follow up aktif</p>
            <div class="stat-dashboard-profil">
                <span><strong>{{ $ringkasanProfil['tugasHariIni'] }}</strong> Hari ini</span>
                <span><strong>{{ $ringkasanProfil['tugasMingguIni'] }}</strong> Minggu ini</span>
                <span><strong>{{ $ringkasanProfil['tugasTertunda'] }}</strong> Tertunda</span>
            </div>
        </a>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel">
            <div class="judul-panel">
                <h2>Menu Profil</h2>
                <span>Akses cepat modul personal</span>
            </div>
            <div class="grid-kartu-profil">
                <a class="kartu-profil-modul" href="{{ route('profil.tim') }}">
                    <span>TIM</span>
                    <strong>{{ $ringkasanProfil['tim'] }}</strong>
                    <small>anggota aktif</small>
                </a>
                <a class="kartu-profil-modul" href="{{ route('profil.tugas') }}">
                    <span>Tugas</span>
                    <strong>{{ $ringkasanProfil['tugas'] }}</strong>
                    <small>perlu follow up</small>
                </a>
                <a class="kartu-profil-modul" href="{{ route('profil.laporan') }}">
                    <span>Laporan</span>
                    <strong>{{ $ringkasanProfil['laporan'] }}</strong>
                    <small>total leads akses</small>
                </a>
                <a class="kartu-profil-modul" href="{{ route('profil.pembelajaran') }}">
                    <span>Pembelajaran</span>
                    <strong>{{ $ringkasanProfil['pembelajaran'] }}</strong>
                    <small>kelas tersedia</small>
                </a>
            </div>
        </div>

        <div class="panel">
            <div class="judul-panel">
                <h2>Pencapaian</h2>
                <span>Ringkasan performa user</span>
            </div>
            <div class="daftar-pencapaian">
                @foreach ($pencapaian as $item)
                    <article class="item-pencapaian">
                        <i>{{ $item['ikon'] }}</i>
                        <div>
                            <strong>{{ $item['judul'] }}</strong>
                            <p>{{ $item['deskripsi'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel">
            <div class="judul-panel">
                <h2>Aktivitas Terbaru</h2>
                <span>Update leads berdasarkan akses user</span>
            </div>
            <div class="daftar-aktivitas-profil">
                @forelse ($aktivitas as $item)
                    <article class="aktivitas-profil">
                        <i class="{{ $item['warna'] }}"></i>
                        <div>
                            <strong>{{ $item['judul'] }}</strong>
                            <small>{{ $item['waktu'] }}</small>
                        </div>
                    </article>
                @empty
                    <p class="kosong">Belum ada aktivitas terbaru.</p>
                @endforelse
            </div>
        </div>

        <div class="panel" id="sosial">
            <div class="judul-panel">
                <h2>Akun Sosial Terhubung</h2>
                <span>{{ $sosialTerhubung->count() }} akun terhubung</span>
            </div>
            <div class="daftar-sosial-profil">
                @forelse ($sosialTerhubung as $nama => $tautan)
                    <a class="item-sosial-profil" href="{{ $tautan }}" target="_blank" rel="noreferrer">
                        <strong>{{ $nama }}</strong>
                        <small>{{ $tautan }}</small>
                    </a>
                @empty
                    <p class="kosong">Belum ada tautan media sosial yang diisi.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="panel jarak-atas">
        <div class="profil-ringkas">
            <div class="avatar-user avatar-profil">{{ $inisialUser ?: 'U' }}</div>
            <div>
                <h2>Profil User</h2>
                <span>{{ auth()->user()->roleLabel() }}{{ auth()->user()->cabang ? ' - '.auth()->user()->cabang : ' - Semua cabang' }}</span>
            </div>
        </div>

        <form class="formulir" method="POST" action="{{ route('profil.update') }}">
            @csrf
            @method('PUT')
            <div class="grid-form">
                <label>
                    Nama
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                    @error('name') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                    @error('email') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Role
                    <input type="text" value="{{ auth()->user()->roleLabel() }}" disabled>
                </label>
                <label>
                    Cabang
                    <input type="text" value="{{ auth()->user()->cabang ?: 'Semua cabang' }}" disabled>
                </label>
                <label>
                    Facebook
                    <input type="url" name="facebook" value="{{ old('facebook', auth()->user()->facebook) }}" placeholder="https://facebook.com/nama-akun">
                    @error('facebook') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Instagram
                    <input type="url" name="instagram" value="{{ old('instagram', auth()->user()->instagram) }}" placeholder="https://instagram.com/nama-akun">
                    @error('instagram') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    TikTok
                    <input type="url" name="tiktok" value="{{ old('tiktok', auth()->user()->tiktok) }}" placeholder="https://tiktok.com/@nama-akun">
                    @error('tiktok') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Blog
                    <input type="url" name="blog" value="{{ old('blog', auth()->user()->blog) }}" placeholder="https://blog-anda.com">
                    @error('blog') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label class="penuh">
                    Channel YouTube
                    <input type="url" name="youtube" value="{{ old('youtube', auth()->user()->youtube) }}" placeholder="https://youtube.com/@nama-channel">
                    @error('youtube') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Password Lama
                    <input type="password" name="password_lama" autocomplete="current-password">
                    <small class="petunjuk">Wajib diisi jika ingin mengganti password.</small>
                    @error('password_lama') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Password Baru
                    <input type="password" name="password" autocomplete="new-password">
                    @error('password') <small class="error">{{ $message }}</small> @enderror
                </label>
                <label>
                    Konfirmasi Password Baru
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </label>
            </div>
            <div class="aksi-form">
                <a class="tombol sekunder" href="{{ route('dashboard') }}">Kembali</a>
                <button class="tombol utama" type="submit">Simpan Profil</button>
            </div>
        </form>
    </section>
@endsection

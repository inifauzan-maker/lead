@extends('tata-letak', ['judul' => 'Pembelajaran'])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>Online Course Gratis</span>
            <h2>Materi pembelajaran internal gratis untuk peningkatan performa tim.</h2>
        </div>
        <strong>{{ $kelas->count() }} materi tersedia</strong>
    </section>

    @if ($bisaKelolaPembelajaran)
        <section class="panel jarak-atas">
            <div class="judul-panel">
                <div>
                    <h2>Tambah Materi</h2>
                    <span>Materi utama seperti kategori kelas di Skill Academy, tetapi gratis untuk tim.</span>
                </div>
            </div>
            <form class="grid-form" method="POST" action="{{ route('profil.pembelajaran.store') }}">
                @csrf
                <label>
                    Judul Materi
                    <input type="text" name="judul" value="{{ old('judul') }}" placeholder="Contoh: Strategi Follow Up Efektif" required>
                </label>
                <label>
                    Level/Kategori
                    <input type="text" name="level" value="{{ old('level', 'Gratis') }}" placeholder="Gratis, Sales, Staff, Leader" required>
                </label>
                <label>
                    Topik/Tema
                    <input type="text" name="topik" value="{{ old('topik') }}" placeholder="Contoh: Follow Up, Closing, Digital Marketing">
                </label>
                <label>
                    Durasi Total (menit)
                    <input type="number" name="durasi_menit" min="0" value="{{ old('durasi_menit', 0) }}">
                </label>
                <label>
                    Urutan
                    <input type="number" name="urutan" min="0" value="{{ old('urutan', 0) }}">
                </label>
                <label class="penuh">
                    Deskripsi
                    <textarea name="deskripsi" rows="3" placeholder="Ringkasan manfaat materi.">{{ old('deskripsi') }}</textarea>
                </label>
                <label class="cek">
                    <input type="checkbox" name="aktif" value="1" checked>
                    Aktif
                </label>
                <div class="aksi-form penuh">
                    <button class="tombol utama" type="submit">Tambah Materi</button>
                </div>
            </form>
        </section>
    @endif

    <section class="grid-kartu-modul">
        @forelse ($kelas as $item)
            <article class="kartu-kelas kartu-kelas-gratis">
                <div class="kepala-kelas">
                    <span>{{ $item['level'] }}</span>
                    <em>Gratis</em>
                </div>
                @if ($item['topik'])
                    <small class="topik-kelas">{{ $item['topik'] }}</small>
                @endif
                <h3>{{ $item['judul'] }}</h3>
                <p>{{ $item['deskripsi'] ?: 'Materi pembelajaran internal untuk tim.' }}</p>
                <p>{{ $item['modul'] }} sub materi - {{ $item['durasi'] }}</p>
                <div class="progress-kelas"><i style="width: {{ $item['progress'] }}%"></i></div>
                <div>
                    <small>{{ $item['progress'] }}% selesai</small>
                    <a class="tombol sekunder" href="{{ route('profil.pembelajaran.detail', $item['id']) }}">Mulai Belajar</a>
                </div>

                @if ($bisaKelolaPembelajaran)
                    <details class="kelola-kelas">
                        <summary>Kelola materi</summary>
                        <form class="form-ringkas" method="POST" action="{{ route('profil.pembelajaran.update', $item['id']) }}">
                            @csrf
                            @method('PUT')
                            <input type="text" name="judul" value="{{ $item['judul'] }}" required>
                            <input type="text" name="level" value="{{ $item['level'] }}" required>
                            <input type="text" name="topik" value="{{ $item['topik'] }}" placeholder="Topik/Tema">
                            <textarea name="deskripsi" rows="3">{{ $item['deskripsi'] }}</textarea>
                            <input type="number" name="durasi_menit" min="0" value="{{ $item['durasi_menit'] }}">
                            <input type="number" name="urutan" min="0" value="{{ $item['urutan'] }}">
                            <label class="cek">
                                <input type="checkbox" name="aktif" value="1" @checked($item['aktif'])>
                                Aktif
                            </label>
                            <button class="tombol sekunder" type="submit">Update</button>
                        </form>
                        <form method="POST" action="{{ route('profil.pembelajaran.destroy', $item['id']) }}" data-konfirmasi data-judul-konfirmasi="Hapus materi?" data-pesan-konfirmasi="Hapus materi {{ $item['judul'] }} beserta semua sub materi?" data-label-setuju="Hapus">
                            @csrf
                            @method('DELETE')
                            <button class="tombol bahaya" type="submit">Hapus Materi</button>
                        </form>
                    </details>
                @endif
            </article>
        @empty
            <p class="kosong">Belum ada materi pembelajaran.</p>
        @endforelse
    </section>
@endsection

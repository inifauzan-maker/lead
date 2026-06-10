@extends('tata-letak', ['judul' => $course->judul])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>{{ $course->level }} - Gratis</span>
            <h2>{{ $course->judul }}</h2>
            @if ($course->topik)
                <p class="topik-hero">Topik: {{ $course->topik }}</p>
            @endif
            <p>{{ $course->deskripsi }}</p>
        </div>
        <strong>{{ $durasi }}</strong>
    </section>

    @if ($bisaKelolaPembelajaran)
        <section class="panel jarak-atas">
            <div class="judul-panel">
                <div>
                    <h2>Tambah Sub Materi</h2>
                    <span>Tambahkan video YouTube dan konten pendukung.</span>
                </div>
            </div>
            <form class="grid-form" method="POST" action="{{ route('profil.pembelajaran.sub-materi.store', $course) }}">
                @csrf
                <label>
                    Judul Sub Materi
                    <input type="text" name="judul" value="{{ old('judul') }}" placeholder="Contoh: Script WhatsApp Follow Up" required>
                </label>
                <label>
                    Link YouTube
                    <input type="url" name="video_youtube" value="{{ old('video_youtube') }}" placeholder="https://youtu.be/...">
                </label>
                <label>
                    Durasi (menit)
                    <input type="number" name="durasi_menit" min="0" value="{{ old('durasi_menit', 0) }}">
                </label>
                <label>
                    Urutan
                    <input type="number" name="urutan" min="0" value="{{ old('urutan', $course->lessons->count() + 1) }}">
                </label>
                <label class="penuh">
                    Konten
                    <textarea name="konten" rows="4" placeholder="Ringkasan, poin penting, atau instruksi belajar.">{{ old('konten') }}</textarea>
                </label>
                <label class="cek">
                    <input type="checkbox" name="aktif" value="1" checked>
                    Aktif
                </label>
                <div class="aksi-form penuh">
                    <button class="tombol utama" type="submit">Tambah Sub Materi</button>
                </div>
            </form>
        </section>
    @endif

    <section class="grid-dua">
        <div class="panel">
            <div class="judul-panel">
                <h2>Sub Materi</h2>
                <span>{{ $course->lessons->count() }} sub materi</span>
            </div>
            <div class="daftar-ringkas">
                @forelse ($course->lessons as $lesson)
                    <article class="baris-ringkas sub-materi">
                        <div>
                            <strong>{{ $lesson->urutan }}. {{ $lesson->judul }}</strong>
                            <small>{{ $lesson->konten }}</small>
                            @if ($lesson->embedYoutube())
                                <div class="video-pembelajaran">
                                    <iframe
                                        src="{{ $lesson->embedYoutube() }}"
                                        title="Video {{ $lesson->judul }}"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                    ></iframe>
                                </div>
                            @elseif ($lesson->video_youtube)
                                <small class="error">Link YouTube belum bisa di-embed. Gunakan format youtube.com/watch?v=... atau youtu.be/...</small>
                            @endif
                            @if ($bisaKelolaPembelajaran)
                                <details class="kelola-kelas">
                                    <summary>Edit sub materi</summary>
                                    <form class="form-ringkas" method="POST" action="{{ route('profil.pembelajaran.sub-materi.update', [$course, $lesson]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="judul" value="{{ $lesson->judul }}" required>
                                        <input type="url" name="video_youtube" value="{{ $lesson->video_youtube }}" placeholder="Link YouTube">
                                        <textarea name="konten" rows="3">{{ $lesson->konten }}</textarea>
                                        <input type="number" name="durasi_menit" min="0" value="{{ $lesson->durasi_menit }}">
                                        <input type="number" name="urutan" min="0" value="{{ $lesson->urutan }}">
                                        <label class="cek">
                                            <input type="checkbox" name="aktif" value="1" @checked($lesson->aktif)>
                                            Aktif
                                        </label>
                                        <button class="tombol sekunder" type="submit">Update Sub Materi</button>
                                    </form>
                                    <form method="POST" action="{{ route('profil.pembelajaran.sub-materi.destroy', [$course, $lesson]) }}" data-konfirmasi data-judul-konfirmasi="Hapus sub materi?" data-pesan-konfirmasi="Hapus sub materi {{ $lesson->judul }}?" data-label-setuju="Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button class="tombol bahaya" type="submit">Hapus Sub Materi</button>
                                    </form>
                                </details>
                            @endif
                        </div>
                        <em>{{ $lesson->durasi_menit }} menit</em>
                    </article>
                @empty
                    <p class="kosong">Belum ada sub materi.</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="judul-panel">
                <h2>Progress Saya</h2>
                <span>{{ $progress?->status ?: 'Belum Mulai' }}</span>
            </div>
            <div class="progress-kelas progress-detail"><i style="width: {{ $progress?->progress_persen ?? 0 }}%"></i></div>
            <form class="grid-form jarak-atas" method="POST" action="{{ route('profil.pembelajaran.progress', $course) }}">
                @csrf
                @method('PUT')
                <label class="penuh">
                    Progress (%)
                    <input type="number" name="progress_persen" min="0" max="100" value="{{ old('progress_persen', $progress?->progress_persen ?? 0) }}" required>
                </label>
                <div class="aksi-form penuh">
                    <a class="tombol sekunder" href="{{ route('profil.pembelajaran') }}">Kembali</a>
                    <button class="tombol utama" type="submit">Simpan Progress</button>
                </div>
            </form>
        </div>
    </section>
@endsection

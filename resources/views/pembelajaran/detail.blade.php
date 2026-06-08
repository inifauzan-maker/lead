@extends('tata-letak', ['judul' => $course->judul])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>{{ $course->level }}</span>
            <h2>{{ $course->judul }}</h2>
            <p>{{ $course->deskripsi }}</p>
        </div>
        <strong>{{ $durasi }}</strong>
    </section>

    <section class="grid-dua">
        <div class="panel">
            <div class="judul-panel">
                <h2>Materi Course</h2>
                <span>{{ $course->lessons->count() }} materi</span>
            </div>
            <div class="daftar-ringkas">
                @forelse ($course->lessons as $lesson)
                    <article class="baris-ringkas">
                        <div>
                            <strong>{{ $lesson->urutan }}. {{ $lesson->judul }}</strong>
                            <small>{{ $lesson->konten }}</small>
                        </div>
                        <em>{{ $lesson->durasi_menit }} menit</em>
                    </article>
                @empty
                    <p class="kosong">Belum ada materi.</p>
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

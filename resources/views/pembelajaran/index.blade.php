@extends('tata-letak', ['judul' => 'Pembelajaran'])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>Online Course</span>
            <h2>Materi pembelajaran internal untuk peningkatan performa tim.</h2>
        </div>
        <strong>{{ $kelas->count() }} kelas tersedia</strong>
    </section>

    <section class="grid-kartu-modul">
        @foreach ($kelas as $item)
            <article class="kartu-kelas">
                <span>{{ $item['level'] }}</span>
                <h3>{{ $item['judul'] }}</h3>
                <p>{{ $item['modul'] }} modul - {{ $item['durasi'] }}</p>
                <div class="progress-kelas"><i style="width: {{ $item['progress'] }}%"></i></div>
                <div>
                    <small>{{ $item['progress'] }}% selesai</small>
                    <a class="tombol sekunder" href="{{ route('profil.pembelajaran.detail', $item['id']) }}">Mulai</a>
                </div>
            </article>
        @endforeach
    </section>
@endsection

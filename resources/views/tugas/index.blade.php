@extends('tata-letak', ['judul' => 'Tugas'])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>Task Management</span>
            <h2>Prioritaskan pekerjaan berdasarkan status leads terbaru.</h2>
        </div>
        <a class="tombol utama" href="{{ route('prospek.create') }}">Tambah Leads</a>
    </section>

    <section class="kanban-tugas">
        @foreach ($tugas as $kolom)
            <div class="kolom-tugas">
                <div class="kepala-tugas">
                    <span class="titik-status {{ $kolom['warna'] }}"></span>
                    <h2>{{ $kolom['judul'] }}</h2>
                    <strong>{{ $kolom['items']->count() }}</strong>
                </div>
                <div class="daftar-tugas">
                    @forelse ($kolom['items'] as $item)
                        <article class="kartu-tugas">
                            <span>{{ $item->program ?: 'Tanpa program' }}</span>
                            <h3>{{ $item->nama }}</h3>
                            <p>{{ $item->asal_sekolah ?: 'Sekolah belum diisi' }}</p>
                            <div>
                                <small>{{ $item->cabang ?: '-' }}</small>
                                @if (auth()->user()->role !== 'direksi')
                                    <a href="{{ route('prospek.edit', $item) }}">Buka</a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="kosong">Tidak ada tugas.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </section>
@endsection

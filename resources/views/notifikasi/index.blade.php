@extends('tata-letak', ['judul' => 'Notifikasi'])

@section('konten')
    <section class="panel">
        <div class="judul-panel">
            <div>
                <h2>Notifikasi Sistem</h2>
                <span>{{ $items->total() }} notifikasi tersedia.</span>
            </div>
            <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                @csrf
                @method('PUT')
                <button class="tombol sekunder" type="submit">Tandai Semua Dibaca</button>
            </form>
        </div>

        <div class="daftar-ringkas">
            @forelse ($items as $item)
                <article class="baris-ringkas {{ $item->dibaca_pada ? '' : 'belum-dibaca' }}">
                    <div>
                        <strong>{{ $item->judul }}</strong>
                        <small>{{ $item->pesan ?: '-' }}</small>
                        <small>{{ $item->created_at?->diffForHumans() }} - {{ $item->prioritas }}</small>
                    </div>
                    <div class="aksi-tabel">
                        @if ($item->tautan)
                            <a href="{{ $item->tautan }}">Buka</a>
                        @endif
                        @unless ($item->dibaca_pada)
                            <form method="POST" action="{{ route('notifikasi.baca', $item) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit">Dibaca</button>
                            </form>
                        @endunless
                    </div>
                </article>
            @empty
                <p class="kosong">Belum ada notifikasi.</p>
            @endforelse
        </div>

        <div class="paginasi">{{ $items->links() }}</div>
    </section>
@endsection

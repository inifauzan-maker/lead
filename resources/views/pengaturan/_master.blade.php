<div class="panel">
    <div class="judul-panel">
        <h2>{{ $judul }}</h2>
        <span>{{ $items->count() }} data</span>
    </div>
    <form class="form-pengaturan" method="POST" action="{{ $routeStore }}">
        @csrf
        <input type="text" name="nama" placeholder="{{ $placeholder }}" required>
        <label class="cek">
            <input type="checkbox" name="aktif" value="1" checked>
            Aktif
        </label>
        <button class="tombol utama" type="submit">Tambah</button>
    </form>
    <div class="daftar-pengaturan">
        @forelse ($items as $item)
            <div class="baris-pengaturan">
                <form method="POST" action="{{ route($routeUpdate, [$parameter => $item]) }}">
                    @csrf
                    @method('PUT')
                    <input type="text" name="nama" value="{{ $item->nama }}" required>
                    <label class="cek">
                        <input type="checkbox" name="aktif" value="1" @checked($item->aktif)>
                        Aktif
                    </label>
                    <button class="tombol sekunder" type="submit">Update</button>
                </form>
                <form
                    method="POST"
                    action="{{ route($routeDestroy, [$parameter => $item]) }}"
                    data-konfirmasi
                    data-judul-konfirmasi="Hapus data?"
                    data-pesan-konfirmasi="Hapus {{ $item->nama }}? Data yang dihapus tidak bisa dikembalikan."
                    data-label-setuju="Hapus"
                >
                    @csrf
                    @method('DELETE')
                    <button class="tombol bahaya" type="submit">Hapus</button>
                </form>
            </div>
        @empty
            <p class="kosong">Belum ada data.</p>
        @endforelse
    </div>
</div>

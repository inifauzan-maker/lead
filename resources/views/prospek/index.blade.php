@extends('tata-letak', ['judul' => 'Data Leads'])

@section('konten')
    <section class="panel">
        <div class="toolbar-leads">
            <form class="filter filter-leads" method="GET" action="{{ route('prospek.index') }}">
                <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari nama, sekolah, WA, program">
                <select name="status">
                    <option value="">Semua status</option>
                    @foreach ($status as $item)
                        <option value="{{ $item }}" @selected(request('status') === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                <select name="sumber">
                    <option value="">Semua sumber</option>
                    @foreach ($sumber as $item)
                        <option value="{{ $item }}" @selected(request('sumber') === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                @if (auth()->user()->aksesSemuaCabang())
                    <select name="cabang">
                        <option value="">Semua cabang</option>
                        @foreach ($cabang as $item)
                            <option value="{{ $item }}" @selected(request('cabang') === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                @endif
                <button class="tombol sekunder" type="submit">Filter</button>
            </form>
            <div class="aksi-data-leads">
                <a class="tombol sekunder" href="{{ route('prospek.contoh-import') }}">Contoh File</a>
                <a class="tombol sekunder" href="{{ route('prospek.export', request()->query()) }}">Export</a>
                @if (auth()->user()->role !== 'direksi')
                    <form class="form-import" method="POST" action="{{ route('prospek.import') }}" enctype="multipart/form-data">
                        @csrf
                        <label class="tombol sekunder">
                            Import
                            <input type="file" name="file_import" accept=".csv,text/csv" onchange="this.form.submit()">
                        </label>
                    </form>
                    <a class="tombol utama" href="{{ route('prospek.create') }}">Tambah Leads</a>
                @endif
            </div>
        </div>

        <form class="aksi-massal" method="POST" action="{{ route('prospek.aksi-massal') }}" data-form-massal>
            @csrf
            <div data-input-massal></div>
            <span><strong data-jumlah-terpilih>0</strong> leads dipilih</span>
            <select name="aksi" aria-label="Aksi massal">
                <option value="export">Export terpilih</option>
                @if (auth()->user()->role !== 'direksi')
                    <option value="hapus">Hapus terpilih</option>
                @endif
            </select>
            <button class="tombol sekunder" type="submit" disabled data-tombol-massal>Jalankan</button>
        </form>

        <div class="bungkus-tabel">
            <table>
                <thead>
                    <tr>
                        <th class="kolom-pilih">
                            <input type="checkbox" data-pilih-semua aria-label="Pilih semua leads di halaman ini">
                        </th>
                        <th>Nama</th>
                        <th>Asal Sekolah</th>
                        <th>WA</th>
                        <th>Program</th>
                        <th>Status</th>
                        <th>Cabang</th>
                        <th>Sumber</th>
                        <th>Tgl Masuk</th>
                        @if (auth()->user()->role !== 'direksi')
                            <th>Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prospek as $item)
                        @php($bisaUbah = $item->bisaDiubahOleh(auth()->user()))
                        <tr>
                            <td class="kolom-pilih">
                                <input
                                    type="checkbox"
                                    value="{{ $item->id }}"
                                    data-pilih-leads
                                    aria-label="Pilih leads {{ $item->nama }}"
                                >
                            </td>
                            <td>
                                <strong>{{ $item->nama }}</strong>
                                <small>{{ $item->kota_asal ?: 'Kota belum diisi' }}</small>
                            </td>
                            <td>{{ $item->asal_sekolah ?: '-' }}</td>
                            <td>{{ $item->noWaUntuk(auth()->user()) }}</td>
                            <td>{{ $item->program ?: '-' }}</td>
                            <td><span class="badge">{{ $item->status }}</span></td>
                            <td>{{ $item->cabang ?: '-' }}</td>
                            <td>{{ $item->sumber ?: '-' }}</td>
                            <td>{{ $item->tgl_masuk?->format('d M Y') ?: '-' }}</td>
                            @if (auth()->user()->role !== 'direksi')
                                <td class="aksi-tabel">
                                    @if ($bisaUbah)
                                        <a href="{{ route('prospek.edit', $item) }}">Edit</a>
                                        <form
                                            method="POST"
                                            action="{{ route('prospek.destroy', $item) }}"
                                            data-konfirmasi
                                            data-judul-konfirmasi="Hapus leads?"
                                            data-pesan-konfirmasi="Hapus leads {{ $item->nama }}? Data yang dihapus tidak bisa dikembalikan."
                                            data-label-setuju="Hapus"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">Hapus</button>
                                        </form>
                                    @else
                                        <span class="petunjuk">Lihat saja</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role !== 'direksi' ? 10 : 9 }}" class="kosong">Belum ada data leads.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">{{ $prospek->links() }}</div>
    </section>
@endsection

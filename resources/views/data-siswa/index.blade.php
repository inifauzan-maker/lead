@extends('tata-letak', ['judul' => 'Data Siswa'])

@section('konten')
    <section class="panel">
        <div class="toolbar-leads">
            <form class="filter filter-leads" method="GET" action="{{ route('data-siswa.index') }}">
                <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari nama, sekolah, WA, program">
                @if (auth()->user()->aksesSemuaCabang())
                    <select name="cabang">
                        <option value="">Semua cabang</option>
                        @foreach ($cabang as $item)
                            <option value="{{ $item }}" @selected(request('cabang') === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="admin">
                    <option value="">Semua admin</option>
                    @foreach ($adminCabang as $item)
                        <option value="{{ $item }}" @selected(request('admin') === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                <select name="staff">
                    <option value="">Semua staff</option>
                    @foreach ($staffFilter as $item)
                        <option value="{{ $item->id }}" @selected((string) request('staff') === (string) $item->id)>
                            {{ $item->name }}{{ $item->cabang ? ' - '.$item->cabang : '' }}
                        </option>
                    @endforeach
                </select>
                <button class="tombol sekunder" type="submit">Filter</button>
            </form>
        </div>

        <div class="judul-panel">
            <h2>Data Siswa Closing</h2>
            <span>{{ $prospek->total() }} data</span>
        </div>

        <div class="bungkus-tabel">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Asal Sekolah</th>
                        <th>WA</th>
                        <th>Program</th>
                        <th>Cabang</th>
                        <th>Sumber</th>
                        <th>Tgl Masuk</th>
                        <th>Tgl Closing</th>
                        @if (auth()->user()->role !== 'direksi')
                            <th>Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prospek as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->nama }}</strong>
                                <small>{{ $item->kota_asal ?: 'Kota belum diisi' }}</small>
                            </td>
                            <td>{{ $item->asal_sekolah ?: '-' }}</td>
                            <td>{{ $item->no_wa ?: '-' }}</td>
                            <td>{{ $item->program ?: '-' }}</td>
                            <td>{{ $item->cabang ?: '-' }}</td>
                            <td>{{ $item->sumber ?: '-' }}</td>
                            <td>{{ $item->tgl_masuk?->format('d M Y') ?: '-' }}</td>
                            <td>{{ $item->updated_at?->format('d M Y') ?: '-' }}</td>
                            @if (auth()->user()->role !== 'direksi')
                                <td class="aksi-tabel">
                                    <a href="{{ route('prospek.edit', $item) }}">Edit</a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role !== 'direksi' ? 9 : 8 }}" class="kosong">Belum ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">{{ $prospek->links() }}</div>
    </section>
@endsection

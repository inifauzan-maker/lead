@extends('tata-letak', ['judul' => 'Data Siswa'])

@section('konten')
    <section class="panel">
        <div class="toolbar-leads">
            <form class="filter filter-leads" method="GET" action="{{ route('data-siswa.index') }}">
                <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari nama, sekolah, WA, program">
                <select name="cabang">
                    <option value="">Semua cabang</option>
                    @foreach ($cabang as $item)
                        <option value="{{ $item }}" @selected(request('cabang') === $item)>{{ $item }}</option>
                    @endforeach
                </select>
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
                <select name="status_pembayaran">
                    <option value="">Semua pembayaran</option>
                    @foreach ($statusPembayaran as $item)
                        <option value="{{ $item }}" @selected(request('status_pembayaran') === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                <button class="tombol sekunder" type="submit">Filter</button>
            </form>
            <a class="tombol utama" href="{{ route('data-siswa.export', request()->query()) }}">Export Data Siswa</a>
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
                        <th>Pembayaran</th>
                        <th>Cabang</th>
                        <th>Tgl Closing</th>
                        <th>Kelas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prospek as $item)
                        @php($bisaUbah = $item->bisaDiubahOleh(auth()->user()))
                        <tr>
                            <td>
                                <strong>{{ $item->nama }}</strong>
                                <small>{{ $item->kota_asal ?: 'Kota belum diisi' }}</small>
                            </td>
                            <td>{{ $item->asal_sekolah ?: '-' }}</td>
                            <td>{{ $item->noWaUntuk(auth()->user()) }}</td>
                            <td>{{ $item->program_final ?: ($item->program ?: '-') }}</td>
                            <td>
                                <strong>{{ $item->status_pembayaran ?: 'Belum Diisi' }}</strong>
                                <small>{{ $item->nominal_pembayaran ? 'Rp '.number_format((float) $item->nominal_pembayaran, 0, ',', '.') : 'Nominal belum diisi' }}</small>
                            </td>
                            <td>{{ $item->cabang ?: '-' }}</td>
                            <td>{{ $item->tanggal_daftar?->format('d M Y') ?: $item->updated_at?->format('d M Y') }}</td>
                            <td>{{ $item->kelas_angkatan ?: ($item->kelas ?: '-') }}</td>
                            <td class="aksi-tabel">
                                <a href="{{ route('data-siswa.show', $item) }}">Detail</a>
                                @if ($bisaUbah)
                                    <a href="{{ route('prospek.edit', $item) }}">Edit</a>
                                @else
                                    <span class="petunjuk">Lihat saja</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="kosong">Belum ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">{{ $prospek->links() }}</div>
    </section>
@endsection

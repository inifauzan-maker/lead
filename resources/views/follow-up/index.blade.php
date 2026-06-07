@extends('tata-letak', ['judul' => 'Follow Up'])

@section('konten')
    <section class="panel">
        <div class="judul-panel judul-heatmap">
            <div>
                <h2>Kalender Follow Up</h2>
                <span>{{ $kalender['total'] }} aktivitas follow up pada {{ $kalender['judul'] }}.</span>
            </div>
            <form class="filter-dashboard" method="GET" action="{{ route('follow-up.index') }}">
                <select name="bulan">
                    @foreach ($daftarBulan as $value => $label)
                        <option value="{{ $value }}" @selected((int) $bulanFilter === (int) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="tahun">
                    @foreach ($daftarTahun as $tahun)
                        <option value="{{ $tahun }}" @selected((int) $tahunFilter === (int) $tahun)>{{ $tahun }}</option>
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

        <div class="kalender-follow-up">
            @foreach ($kalender['hari'] as $hari)
                <div class="nama-hari">{{ $hari }}</div>
            @endforeach
            @foreach ($kalender['pekan'] as $pekan)
                @foreach ($pekan as $hari)
                    <div class="tanggal-kalender {{ $hari['bulan_aktif'] ? '' : 'tanggal-luar-bulan' }} {{ $hari['hari_ini'] ? 'tanggal-hari-ini' : '' }}">
                        <span>{{ $hari['nomor'] }}</span>
                        @if ($hari['total'] > 0)
                            <strong>{{ $hari['total'] }} leads</strong>
                        @else
                            <em>-</em>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <h2>Data Leads Sudah Follow Up</h2>
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
                        <th>Status</th>
                        <th>Cabang</th>
                        <th>Diserahkan ke</th>
                        <th>Update</th>
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
                            <td><span class="badge">{{ $item->status }}</span></td>
                            <td>{{ $item->cabang ?: '-' }}</td>
                            <td>{{ $item->diserahkan_ke ?: '-' }}</td>
                            <td>{{ $item->updated_at?->format('d M Y') ?: '-' }}</td>
                            @if (auth()->user()->role !== 'direksi')
                                <td class="aksi-tabel">
                                    <a href="{{ route('prospek.edit', $item) }}">Edit</a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role !== 'direksi' ? 9 : 8 }}" class="kosong">Belum ada data follow up.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">{{ $prospek->links() }}</div>
    </section>
@endsection

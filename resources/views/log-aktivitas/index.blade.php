@extends('tata-letak', ['judul' => 'Log Aktivitas'])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>Audit Sistem</span>
            <h2>Pantau aktivitas user, perubahan data, dan akses penting di aplikasi.</h2>
        </div>
        <strong>{{ $items->total() }} log</strong>
    </section>

    <section class="panel">
        <form class="filter filter-log" method="GET" action="{{ route('log-aktivitas.index') }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari user, modul, route, URL">
            <select name="aksi">
                <option value="">Semua aksi</option>
                @foreach ($aksi as $item)
                    <option value="{{ $item }}" @selected(request('aksi') === $item)>{{ $item }}</option>
                @endforeach
            </select>
            <select name="modul">
                <option value="">Semua modul</option>
                @foreach ($modul as $item)
                    <option value="{{ $item }}" @selected(request('modul') === $item)>{{ $item }}</option>
                @endforeach
            </select>
            <select name="user_id">
                <option value="">Semua user</option>
                @foreach ($pengguna as $user)
                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" aria-label="Tanggal mulai">
            <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" aria-label="Tanggal selesai">
            <button class="tombol sekunder" type="submit">Filter</button>
        </form>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Riwayat Aktivitas</h2>
                <span>Data terbaru tampil paling atas.</span>
            </div>
        </div>

        <div class="bungkus-tabel">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>Modul</th>
                        <th>Detail</th>
                        <th>Status</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->created_at?->format('d M Y') }}</strong>
                                <small>{{ $item->created_at?->format('H:i:s') }}</small>
                            </td>
                            <td>
                                <strong>{{ $item->nama_user ?: ($item->user?->name ?: 'Sistem') }}</strong>
                                <small>{{ ucfirst($item->role ?: '-') }}{{ $item->cabang ? ' - '.$item->cabang : '' }}</small>
                            </td>
                            <td><span class="badge-log">{{ $item->aksi }}</span></td>
                            <td>{{ $item->modul ?: '-' }}</td>
                            <td>
                                <strong>{{ $item->deskripsi ?: '-' }}</strong>
                                <small>{{ $item->method }} {{ $item->route_name ?: '-' }}</small>
                                <small class="url-log">{{ $item->url }}</small>
                                @if ($item->payload)
                                    <details class="detail-payload">
                                        <summary>Lihat payload</summary>
                                        <pre>{{ json_encode($item->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @endif
                            </td>
                            <td>{{ $item->status_code ?: '-' }}</td>
                            <td>{{ $item->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="kosong">Belum ada log aktivitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">{{ $items->links() }}</div>
    </section>
@endsection

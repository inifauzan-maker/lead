@extends('tata-letak', ['judul' => 'Pengaturan'])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>Master Sistem</span>
            <h2>Atur cabang, sumber leads, program, dan role user dari satu halaman.</h2>
        </div>
        <strong>{{ $pengguna->total() }} user</strong>
    </section>

    <section class="grid-dua">
        @include('pengaturan._master', [
            'judul' => 'CRUD Cabang',
            'items' => $cabang,
            'routeStore' => route('pengaturan.cabang.store'),
            'routeUpdate' => 'pengaturan.cabang.update',
            'routeDestroy' => 'pengaturan.cabang.destroy',
            'parameter' => 'cabang',
            'placeholder' => 'Nama cabang',
        ])

        @include('pengaturan._master', [
            'judul' => 'CRUD Sumber Leads',
            'items' => $sumber,
            'routeStore' => route('pengaturan.sumber.store'),
            'routeUpdate' => 'pengaturan.sumber.update',
            'routeDestroy' => 'pengaturan.sumber.destroy',
            'parameter' => 'sumber',
            'placeholder' => 'Nama sumber leads',
        ])
    </section>

    <section class="grid-dua jarak-atas">
        @include('pengaturan._master', [
            'judul' => 'CRUD Program',
            'items' => $program,
            'routeStore' => route('pengaturan.program.store'),
            'routeUpdate' => 'pengaturan.program.update',
            'routeDestroy' => 'pengaturan.program.destroy',
            'parameter' => 'program',
            'placeholder' => 'Nama program',
        ])

        <div class="panel panel-role-user">
            <div class="judul-panel">
                <h2>Manajemen Role User</h2>
                <span>{{ $pengguna->total() }} akun</span>
            </div>
            <form class="form-tambah-user" method="POST" action="{{ route('pengaturan.user.store') }}">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama user" required>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="Email login" required>
                <input type="password" name="password" placeholder="Password minimal 8 karakter" required>
                <select name="role" required>
                    @foreach ($role as $item)
                        <option value="{{ $item }}" @selected(old('role', 'staff') === $item)>{{ ucfirst($item) }}</option>
                    @endforeach
                </select>
                <select name="cabang">
                    <option value="">Semua cabang</option>
                    @foreach ($daftarCabang as $item)
                        <option value="{{ $item }}" @selected(old('cabang') === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                <label class="cek">
                    <input type="checkbox" name="aktif" value="1" checked>
                    Aktif
                </label>
                <button class="tombol utama" type="submit">Tambah User</button>
            </form>
            <div class="daftar-pengaturan">
                @foreach ($pengguna as $userItem)
                    <form class="baris-pengaturan baris-role-user" method="POST" action="{{ route('pengaturan.user-role.update', $userItem) }}">
                        @csrf
                        @method('PUT')
                        <div class="identitas-user">
                            <strong>{{ $userItem->name }}</strong>
                            <small>{{ $userItem->email }}</small>
                        </div>
                        <div class="kontrol-role-user">
                            <select name="role" required>
                                @foreach ($role as $item)
                                    <option value="{{ $item }}" @selected(old('role', $userItem->role) === $item)>{{ ucfirst($item) }}</option>
                                @endforeach
                            </select>
                            <select name="cabang">
                                <option value="">Semua cabang</option>
                                @foreach ($daftarCabang as $item)
                                    <option value="{{ $item }}" @selected(old('cabang', $userItem->cabang) === $item)>{{ $item }}</option>
                                @endforeach
                            </select>
                            <label class="cek">
                                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $userItem->aktif ?? true))>
                                Aktif
                            </label>
                            <button class="tombol sekunder" type="submit">Update</button>
                        </div>
                    </form>
                @endforeach
            </div>
            <div class="paginasi">{{ $pengguna->links() }}</div>
        </div>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Target Kinerja Bulanan</h2>
                <span>Tentukan target leads aktif dan closing untuk dashboard performa bulanan.</span>
            </div>
            <strong>{{ $targetKinerja->total() }} target</strong>
        </div>
        <div class="panduan-target-kinerja">
            <div>
                <strong>Target cabang</strong>
                <span>Dipakai untuk mengukur capaian seluruh user dalam satu cabang.</span>
            </div>
            <div>
                <strong>Target staff</strong>
                <span>Dipakai untuk mengukur capaian personal staff tertentu.</span>
            </div>
            <div>
                <strong>Periode unik</strong>
                <span>Satu kombinasi bulan, tahun, tipe, dan cabang/staff akan diperbarui jika sudah ada.</span>
            </div>
        </div>
        <form class="form-target-kinerja" method="POST" action="{{ route('pengaturan.target-kinerja.store') }}" data-form-target-kinerja>
            @csrf
            <label class="field-target field-target-kecil">
                <span>Bulan target</span>
                <select name="bulan" required>
                    @foreach ($daftarBulan as $value => $label)
                        <option value="{{ $value }}" @selected((int) old('bulan', now()->month) === (int) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field-target field-target-kecil">
                <span>Tahun target</span>
                <select name="tahun" required>
                    @foreach ($daftarTahun as $tahun)
                        <option value="{{ $tahun }}" @selected((int) old('tahun', now()->year) === (int) $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field-target field-target-sedang">
                <span>Jenis target</span>
                <select name="tipe" required data-target-tipe>
                    <option value="cabang" @selected(old('tipe', 'cabang') === 'cabang')>Target per cabang</option>
                    <option value="staff" @selected(old('tipe') === 'staff')>Target per staff</option>
                </select>
            </label>
            <label class="field-target field-target-lebar" data-field-target-cabang>
                <span>Cabang yang ditargetkan</span>
                <select name="cabang" data-target-cabang>
                    <option value="">Pilih cabang</option>
                    @foreach ($daftarCabang as $item)
                        <option value="{{ $item }}" @selected(old('cabang') === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                <small>Wajib untuk target cabang.</small>
            </label>
            <label class="field-target field-target-lebar" data-field-target-staff>
                <span>Staff yang ditargetkan</span>
                <select name="user_id" data-target-staff>
                    <option value="">Pilih staff aktif</option>
                    @foreach ($staffTarget as $userItem)
                        <option value="{{ $userItem->id }}" @selected((string) old('user_id') === (string) $userItem->id)>
                            {{ $userItem->name }}{{ $userItem->cabang ? ' - '.$userItem->cabang : '' }}
                        </option>
                    @endforeach
                </select>
                <small>Wajib untuk target staff. Cabang mengikuti cabang staff.</small>
            </label>
            <label class="field-target field-target-sedang">
                <span>Target leads aktif</span>
                <input type="number" name="target_leads" value="{{ old('target_leads', 0) }}" min="0" placeholder="Contoh: 100" required>
                <small>Jumlah leads aktif yang ingin dicapai pada periode ini.</small>
            </label>
            <label class="field-target field-target-sedang">
                <span>Target closing</span>
                <input type="number" name="target_closing" value="{{ old('target_closing', 0) }}" min="0" placeholder="Contoh: 20" required>
                <small>Jumlah leads yang ditargetkan menjadi data siswa/closing.</small>
            </label>
            <button class="tombol utama tombol-target" type="submit">Simpan Target</button>
        </form>
        <div class="daftar-pengaturan">
            @forelse ($targetKinerja as $target)
                <div class="baris-pengaturan baris-target-kinerja">
                    <form method="POST" action="{{ route('pengaturan.target-kinerja.update', $target) }}">
                        @csrf
                        @method('PUT')
                        <div class="identitas-user">
                            <strong>{{ $target->tipe === 'staff' ? ($target->user?->name ?: 'Staff tidak aktif') : $target->cabang }}</strong>
                            <small>{{ $daftarBulan[$target->bulan] ?? $target->bulan }} {{ $target->tahun }} | {{ ucfirst($target->tipe) }}{{ $target->cabang ? ' - '.$target->cabang : '' }}</small>
                        </div>
                        <input type="hidden" name="bulan" value="{{ $target->bulan }}">
                        <input type="hidden" name="tahun" value="{{ $target->tahun }}">
                        <input type="hidden" name="tipe" value="{{ $target->tipe }}">
                        <input type="hidden" name="cabang" value="{{ $target->cabang }}">
                        <input type="hidden" name="user_id" value="{{ $target->user_id }}">
                        <input type="number" name="target_leads" value="{{ $target->target_leads }}" min="0" required>
                        <input type="number" name="target_closing" value="{{ $target->target_closing }}" min="0" required>
                        <button class="tombol sekunder" type="submit">Update</button>
                    </form>
                    <form method="POST" action="{{ route('pengaturan.target-kinerja.destroy', $target) }}" data-konfirmasi data-judul-konfirmasi="Hapus target?" data-pesan-konfirmasi="Target kinerja ini akan dihapus." data-label-setuju="Hapus">
                        @csrf
                        @method('DELETE')
                        <button class="tombol bahaya" type="submit">Hapus</button>
                    </form>
                </div>
            @empty
                <p class="kosong">Belum ada target kinerja.</p>
            @endforelse
        </div>
        <div class="paginasi">{{ $targetKinerja->links() }}</div>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Backup dan Restore Data</h2>
                <span>Export semua data penting aplikasi ke file SQL untuk arsip dan proses restore manual.</span>
            </div>
            <a class="tombol utama" href="{{ route('pengaturan.backup.export') }}">Export Backup SQL</a>
        </div>
        <div class="daftar-ringkas">
            <div class="baris-ringkas">
                <strong>Isi backup</strong>
                <small>Cabang, sumber leads, program, user, leads, follow up, tugas, pembelajaran, notifikasi, dan log aktivitas.</small>
            </div>
            <div class="baris-ringkas">
                <strong>Restore database</strong>
                <small>Restore dilakukan dari phpMyAdmin atau perintah MySQL. Ikuti panduan di file dokumentasi.md sebelum menimpa database produksi.</small>
            </div>
        </div>
    </section>
@endsection

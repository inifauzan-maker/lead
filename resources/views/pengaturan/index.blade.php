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

        <div class="panel">
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
@endsection

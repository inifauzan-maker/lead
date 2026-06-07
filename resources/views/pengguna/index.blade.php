@extends('tata-letak', ['judul' => 'Pengguna'])

@section('konten')
    <section class="panel">
        <div class="judul-panel">
            <h2>Tambah Pengguna</h2>
            <span>Role: superadmin, admin, leader, staff, direksi</span>
        </div>
        <form class="formulir" method="POST" action="{{ route('pengguna.store') }}">
            @csrf
            @include('pengguna._formulir', ['userItem' => $penggunaBaru])
            <div class="aksi-form">
                <button class="tombol utama" type="submit">Simpan Pengguna</button>
            </div>
        </form>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <h2>Daftar Pengguna</h2>
            <span>{{ $pengguna->total() }} akun</span>
        </div>
        <div class="bungkus-tabel">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Cabang</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pengguna as $userItem)
                        <tr>
                            <td colspan="6">
                                <form class="baris-pengguna" method="POST" action="{{ route('pengguna.update', $userItem) }}">
                                    @csrf
                                    @method('PUT')
                                    @include('pengguna._formulir')
                                    <button class="tombol sekunder" type="submit">Update</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="paginasi">{{ $pengguna->links() }}</div>
    </section>
@endsection

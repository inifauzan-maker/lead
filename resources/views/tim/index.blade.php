@extends('tata-letak', ['judul' => 'TIM'])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>Kolaborasi Cabang</span>
            <h2>Kelola performa dan struktur tim dalam satu tampilan.</h2>
        </div>
        <strong>{{ $anggota->count() }} anggota aktif</strong>
    </section>

    <section class="grid-ringkasan">
        <article class="kartu-stat"><span>Admin</span><strong>{{ $jumlahRole->get('admin', 0) }}</strong></article>
        <article class="kartu-stat"><span>Leader</span><strong>{{ $jumlahRole->get('leader', 0) }}</strong></article>
        <article class="kartu-stat"><span>Staff</span><strong>{{ $jumlahRole->get('staff', 0) }}</strong></article>
        <article class="kartu-stat"><span>Cabang Aktif</span><strong>{{ $jumlahCabang->count() }}</strong></article>
    </section>

    <section class="grid-kartu-modul">
        @forelse ($anggota as $user)
            @php
                $inisial = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($nama) => strtoupper(substr($nama, 0, 1)))->implode('');
            @endphp
            <article class="kartu-anggota">
                <div class="avatar-user avatar-daftar">{{ $inisial ?: 'U' }}</div>
                <div>
                    <h3>{{ $user->name }}</h3>
                    <span>{{ $user->roleLabel() }}{{ $user->cabang ? ' - '.$user->cabang : ' - Semua cabang' }}</span>
                </div>
                <div class="metrik-mini">
                    <span>{{ $jumlahLeadsPerUser->get($user->id, 0) }} leads</span>
                    <span>{{ $jumlahClosingPerUser->get($user->id, 0) }} closing</span>
                </div>
            </article>
        @empty
            <section class="panel"><p class="kosong">Belum ada anggota tim aktif.</p></section>
        @endforelse
    </section>
@endsection

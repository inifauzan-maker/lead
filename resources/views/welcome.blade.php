@extends('tata-letak', ['judul' => 'Sistem Informasi Leads'])

@section('konten')
    <section class="panel">
        <p class="kosong">Buka dashboard untuk melihat ringkasan dan mengelola data leads.</p>
        <div class="aksi-form">
            <a class="tombol utama" href="{{ route('dashboard') }}">Ke Dashboard</a>
        </div>
    </section>
@endsection

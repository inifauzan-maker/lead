@extends('tata-letak', ['judul' => 'CRM_SIVMI'])

@section('konten')
    <section class="panel">
        <p class="kosong">Buka dashboard untuk melihat ringkasan dan mengelola data leads.</p>
        <div class="aksi-form">
            <a class="tombol utama" href="{{ route('dashboard') }}">Ke Dashboard</a>
        </div>
    </section>
@endsection

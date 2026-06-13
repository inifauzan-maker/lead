@extends('tata-letak', ['judul' => 'Tambah Leads'])

@section('konten')
    <section class="panel">
        <form class="formulir" method="POST" action="{{ route('prospek.store') }}" data-form-closing>
            @csrf
            @include('prospek._formulir')
            <div class="aksi-form">
                <a class="tombol sekunder" href="{{ route('prospek.index') }}">Batal</a>
                <button class="tombol utama" type="submit">Simpan Leads</button>
            </div>
        </form>
    </section>
@endsection

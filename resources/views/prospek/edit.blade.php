@extends('tata-letak', ['judul' => 'Edit Leads'])

@section('konten')
    <section class="panel">
        <form class="formulir" method="POST" action="{{ route('prospek.update', $prospek) }}" data-form-closing>
            @csrf
            @method('PUT')
            @include('prospek._formulir')
            <div class="aksi-form">
                <a class="tombol sekunder" href="{{ route('prospek.index') }}">Batal</a>
                <button class="tombol utama" type="submit">Simpan Perubahan</button>
            </div>
        </form>
    </section>
@endsection

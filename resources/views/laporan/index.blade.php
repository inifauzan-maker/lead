@extends('tata-letak', ['judul' => 'Laporan'])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>Report Center</span>
            <h2>Ringkasan leads dan closing milik user login.</h2>
        </div>
        <a class="tombol sekunder" href="{{ route('profil.laporan.export') }}">Export Leads & Closing Saya</a>
    </section>

    <section class="grid-ringkasan">
        <article class="kartu-stat"><span>Total Leads</span><strong>{{ $total }}</strong></article>
        <article class="kartu-stat"><span>Leads Baru</span><strong>{{ $baru }}</strong></article>
        <article class="kartu-stat"><span>Follow Up</span><strong>{{ $followUp }}</strong></article>
        <article class="kartu-stat"><span>Rasio Closing</span><strong>{{ $rasioClosing }}%</strong></article>
    </section>

    <section class="grid-dua">
        <div class="panel">
            <div class="judul-panel"><h2>Laporan per Cabang</h2><span>{{ $perCabang->sum('total') }} data</span></div>
            <div class="daftar-progress">
                @foreach ($perCabang as $item)
                    <div class="bar-progress">
                        <div><span>{{ $item->label }}</span><strong>{{ $item->total }}</strong></div>
                        <i style="width: {{ $total > 0 ? max(4, ($item->total / $total) * 100) : 0 }}%"></i>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="panel">
            <div class="judul-panel"><h2>Laporan per Status</h2><span>{{ $perStatus->sum('total') }} data</span></div>
            <div class="daftar-progress">
                @foreach ($perStatus as $item)
                    <div class="bar-progress">
                        <div><span>{{ $item->label }}</span><strong>{{ $item->total }}</strong></div>
                        <i style="width: {{ $total > 0 ? max(4, ($item->total / $total) * 100) : 0 }}%"></i>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection

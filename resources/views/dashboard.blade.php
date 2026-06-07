@extends('tata-letak', ['judul' => 'Dashboard Leads'])

@section('konten')
    <section class="panel panel-heatmap">
        <div class="judul-panel judul-heatmap">
            <div>
                <h2>Peraihan Leads Harian</h2>
                <span>Bulan {{ $grafikHarian['bulan'] }} berdasarkan tanggal masuk.</span>
            </div>
            <form class="filter-dashboard" method="GET" action="{{ route('dashboard') }}">
                <select name="bulan">
                    @foreach ($daftarBulan as $value => $label)
                        <option value="{{ $value }}" @selected((int) $bulanFilter === (int) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="tahun">
                    @foreach ($daftarTahun as $tahun)
                        <option value="{{ $tahun }}" @selected((int) $tahunFilter === (int) $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>
                @if (auth()->user()->aksesSemuaCabang())
                    <select name="cabang">
                        <option value="">Semua cabang</option>
                        @foreach ($cabang as $item)
                            <option value="{{ $item }}" @selected(request('cabang') === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="admin">
                    <option value="">Semua admin</option>
                    @foreach ($adminCabang as $item)
                        <option value="{{ $item }}" @selected(request('admin') === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                <select name="staff">
                    <option value="">Semua staff</option>
                    @foreach ($staffFilter as $item)
                        <option value="{{ $item->id }}" @selected((string) request('staff') === (string) $item->id)>
                            {{ $item->name }}{{ $item->cabang ? ' - '.$item->cabang : '' }}
                        </option>
                    @endforeach
                </select>
                <button class="tombol sekunder" type="submit">Filter</button>
            </form>
        </div>

        <div class="grafik-wrap">
            <div class="legenda-grafik">
                <span><i class="biru"></i> CLOSING</span>
                <span><i class="merah"></i> LEAD</span>
            </div>
            <div class="grafik-scroll">
                <div class="grafik-kanvas">
                    <div class="sumbu-y">
                        @foreach ($grafikHarian['skala'] as $nilai)
                            <span>{{ $nilai }}</span>
                        @endforeach
                    </div>
                    <svg
                        class="grafik-svg"
                        viewBox="0 0 {{ $grafikHarian['lebar'] }} {{ $grafikHarian['tinggi'] }}"
                        preserveAspectRatio="none"
                        aria-label="Grafik leads harian"
                    >
                        <polyline class="area-lead" points="{{ $grafikHarian['areaLeadPoints'] }}"></polyline>
                        <polyline class="garis-lead" points="{{ $grafikHarian['leadPoints'] }}"></polyline>
                        <polyline class="garis-closing" points="{{ $grafikHarian['closingPoints'] }}"></polyline>
                    </svg>
                    <div class="sumbu-x" style="grid-template-columns: repeat({{ count($grafikHarian['hari']) }}, minmax(0, 1fr))">
                        @foreach ($grafikHarian['hari'] as $hari)
                            <span title="Lead: {{ $hari['lead'] }}, Closing: {{ $hari['closing'] }}">{{ $hari['nomor'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid-ringkasan">
        <article class="kartu-stat">
            <span>Total Leads</span>
            <strong>{{ $total }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Leads Baru</span>
            <strong>{{ $baru }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Butuh Follow Up</span>
            <strong>{{ $followUp }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Closing</span>
            <strong>{{ $daftar }}</strong>
        </article>
    </section>

    <section class="grid-dua">
        <div class="panel">
            <div class="judul-panel">
                <h2>Lead per Sumber</h2>
                <span>{{ $perSumber->sum('total') }} data</span>
            </div>
            @php
                $warnaSumber = ['#ef4444', '#3b82f6', '#cbdde3', '#f59e0b', '#b91c1c', '#c27ba0', '#10b981', '#8b5cf6'];
                $maksSumber = max(1, (int) $perSumber->max('total'));
            @endphp
            <div class="chart-batang">
                @forelse ($perSumber as $item)
                    <div class="kolom-batang" title="{{ $item->sumber }}: {{ $item->total }}">
                        <div class="nilai-batang">{{ $item->total }}</div>
                        <div
                            class="batang"
                            style="height: {{ max(8, round(($item->total / $maksSumber) * 220)) }}px; background: {{ $warnaSumber[$loop->index % count($warnaSumber)] }}"
                        ></div>
                        <div class="label-batang">{{ $item->sumber }}</div>
                    </div>
                @empty
                    <p class="kosong">Belum ada data sumber.</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="judul-panel">
                <h2>Leads Berdasarkan Program</h2>
                <span>{{ $perProgram->sum('total') }} data</span>
            </div>
            @php
                $warnaProgram = ['#ef4444', '#3b82f6', '#cbdde3', '#f59e0b', '#b91c1c', '#c27ba0', '#10b981', '#8b5cf6'];
                $maksProgram = max(1, (int) $perProgram->max('total'));
            @endphp
            <div class="chart-batang">
                @forelse ($perProgram as $item)
                    <div class="kolom-batang" title="{{ $item->program }}: {{ $item->total }}">
                        <div class="nilai-batang">{{ $item->total }}</div>
                        <div
                            class="batang"
                            style="height: {{ max(8, round(($item->total / $maksProgram) * 220)) }}px; background: {{ $warnaProgram[$loop->index % count($warnaProgram)] }}"
                        ></div>
                        <div class="label-batang">{{ $item->program }}</div>
                    </div>
                @empty
                    <p class="kosong">Belum ada data program.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel">
            <div class="judul-panel">
                <h2>Leads Berdasarkan Cabang</h2>
                <span>{{ $perCabang->sum('total') }} data</span>
            </div>
            @php
                $warnaCabang = ['#ef4444', '#3b82f6', '#f59e0b', '#10b981'];
                $maksCabang = max(1, (int) $perCabang->max('total'));
            @endphp
            <div class="chart-batang chart-batang-ringkas">
                @forelse ($perCabang as $item)
                    <div class="kolom-batang" title="{{ $item->cabang }}: {{ $item->total }}">
                        <div class="nilai-batang">{{ $item->total }}</div>
                        <div
                            class="batang"
                            style="height: {{ max(8, round(($item->total / $maksCabang) * 220)) }}px; background: {{ $warnaCabang[$loop->index % count($warnaCabang)] }}"
                        ></div>
                        <div class="label-batang">{{ $item->cabang }}</div>
                    </div>
                @empty
                    <p class="kosong">Belum ada data cabang.</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="judul-panel">
                <h2>5 Asal Sekolah Terbanyak</h2>
                <span>{{ $perSekolah->sum('total') }} data</span>
            </div>
            @php
                $warnaSekolah = ['#ef4444', '#3b82f6', '#cbdde3', '#f59e0b', '#b91c1c'];
                $maksSekolah = max(1, (int) $perSekolah->max('total'));
            @endphp
            <div class="chart-batang chart-batang-ringkas">
                @forelse ($perSekolah as $item)
                    <div class="kolom-batang" title="{{ $item->asal_sekolah }}: {{ $item->total }}">
                        <div class="nilai-batang">{{ $item->total }}</div>
                        <div
                            class="batang"
                            style="height: {{ max(8, round(($item->total / $maksSekolah) * 220)) }}px; background: {{ $warnaSekolah[$loop->index % count($warnaSekolah)] }}"
                        ></div>
                        <div class="label-batang">{{ $item->asal_sekolah }}</div>
                    </div>
                @empty
                    <p class="kosong">Belum ada data asal sekolah.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection

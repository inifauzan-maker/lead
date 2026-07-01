@extends('tata-letak', ['judul' => 'Dashboard Leads'])

@section('konten')
    @php
        $warnaSumber = ['#ef4444', '#3b82f6', '#cbdde3', '#f59e0b', '#b91c1c', '#c27ba0', '#10b981', '#8b5cf6'];
        $warnaProgram = ['#ef4444', '#3b82f6', '#cbdde3', '#f59e0b', '#b91c1c', '#c27ba0', '#10b981', '#8b5cf6'];
        $warnaCabang = ['#ef4444', '#3b82f6', '#f59e0b', '#10b981'];
        $warnaSekolah = ['#ef4444', '#3b82f6', '#cbdde3', '#f59e0b', '#b91c1c'];
        $maksSumber = max(1, (int) $perSumber->max('total'));
        $maksProgram = max(1, (int) $perProgram->max('total'));
        $maksCabang = max(1, (int) $perCabang->max('total'));
        $maksSekolah = max(1, (int) $perSekolah->max('total'));
    @endphp

    <section class="hero-modul">
        <div>
            <span>{{ $dashboardRole['labelRole'] }}</span>
            <h2>{{ $dashboardRole['judul'] }}</h2>
            <p>{{ $dashboardRole['deskripsi'] }}</p>
        </div>
        <strong>{{ $dashboardRole['cabangTerkunci'] ?: 'Semua Cabang' }}</strong>
    </section>

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
                @if ($dashboardRole['bolehFilterCabang'])
                    <select name="cabang">
                        <option value="">Semua cabang</option>
                        @foreach ($cabang as $item)
                            <option value="{{ $item }}" @selected(request('cabang') === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($dashboardRole['bolehFilterAdmin'])
                    <select name="admin">
                        <option value="">Semua admin</option>
                        @foreach ($adminCabang as $item)
                            <option value="{{ $item }}" @selected(request('admin') === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($dashboardRole['bolehFilterStaff'])
                    <select name="staff">
                        <option value="">Semua staff</option>
                        @foreach ($staffFilter as $item)
                            <option value="{{ $item->id }}" @selected((string) request('staff') === (string) $item->id)>
                                {{ $item->name }}{{ $item->cabang ? ' - '.$item->cabang : '' }}
                            </option>
                        @endforeach
                    </select>
                @endif
                @if (! $dashboardRole['bolehFilterCabang'] && $dashboardRole['cabangTerkunci'])
                    <input type="hidden" name="cabang" value="{{ $dashboardRole['cabangTerkunci'] }}">
                @endif
                @if (! $dashboardRole['bolehFilterStaff'])
                    @foreach (['admin', 'staff'] as $field)
                        <input type="hidden" name="{{ $field }}" value="">
                    @endforeach
                @endif
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
            <span>Total Leads Aktif</span>
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
        <article class="kartu-stat">
            <span>Follow Up Rate</span>
            <strong>{{ $kpiOperasional['followUpRate'] }}%</strong>
        </article>
        <article class="kartu-stat">
            <span>Belum Follow Up</span>
            <strong>{{ $kpiOperasional['belumFollowUp'] }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Follow Up Terlambat</span>
            <strong>{{ $kpiOperasional['followUpTerlambat'] }}</strong>
        </article>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel">
            <div class="judul-panel">
                <div>
                    <h2>Aging Leads Aktif</h2>
                    <span>Umur leads aktif sejak tanggal masuk.</span>
                </div>
            </div>
            <div class="daftar-progress">
                @forelse ($agingLeads as $item)
                    <div class="bar-progress">
                        <div>
                            <strong>{{ $item['label'] }}</strong>
                            <span>{{ $item['total'] }} leads</span>
                        </div>
                        <span class="progress-kelas"><i style="width: {{ $item['persen'] }}%"></i></span>
                    </div>
                @empty
                    <p class="kosong">Belum ada aging leads aktif pada periode ini.</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="judul-panel">
                <div>
                    <h2>Performa User Input</h2>
                    <span>Konversi leads berdasarkan user yang input data.</span>
                </div>
            </div>
            <div class="daftar-progress">
                @forelse ($performaInputUser as $item)
                    <div class="bar-progress">
                        <div>
                            <strong>{{ $item['label'] }}</strong>
                            <span>{{ $item['leads'] }} leads | {{ $item['closing'] }} closing | konversi {{ $item['rasio'] }}%</span>
                        </div>
                        <span class="progress-kelas"><i style="width: {{ $item['persen'] }}%"></i></span>
                    </div>
                @empty
                    <p class="kosong">Belum ada performa user input pada periode ini.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Konversi per Sumber</h2>
                <span>Kualitas sumber leads berdasarkan rasio closing.</span>
            </div>
        </div>
        <div class="daftar-progress">
            @forelse ($konversiSumber as $item)
                <div class="bar-progress">
                    <div>
                        <strong>{{ $item['label'] }}</strong>
                        <span>{{ $item['leads'] }} leads | {{ $item['closing'] }} closing | konversi {{ $item['rasio'] }}%</span>
                    </div>
                    <span class="progress-kelas"><i style="width: {{ $item['persen'] }}%"></i></span>
                </div>
            @empty
                <p class="kosong">Belum ada data konversi sumber pada periode ini.</p>
            @endforelse
        </div>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel">
            <div class="judul-panel">
                <div>
                    <h2>Target dan Konversi</h2>
                    <span>{{ $targetKinerja['labelTarget'] }}</span>
                </div>
                <strong>{{ $targetKinerja['rasioKonversi'] }}%</strong>
            </div>
            <div class="daftar-progress">
                <div class="bar-progress">
                    <div>
                        <strong>Target Leads</strong>
                        <span>{{ $targetKinerja['leadsAktif'] }} / {{ $targetKinerja['targetLeads'] }} leads aktif</span>
                    </div>
                    <span class="progress-kelas"><i style="width: {{ $targetKinerja['persenLeads'] }}%"></i></span>
                </div>
                <div class="bar-progress">
                    <div>
                        <strong>Target Closing</strong>
                        <span>{{ $targetKinerja['closing'] }} / {{ $targetKinerja['targetClosing'] }} closing</span>
                    </div>
                    <span class="progress-kelas"><i style="width: {{ $targetKinerja['persenClosing'] }}%"></i></span>
                </div>
                <div class="bar-progress">
                    <div>
                        <strong>Rasio Konversi</strong>
                        <span>{{ $targetKinerja['closing'] }} closing dari {{ $targetKinerja['leadsAktif'] + $targetKinerja['closing'] }} total leads periode ini</span>
                    </div>
                    <span class="progress-kelas"><i style="width: {{ min(100, $targetKinerja['rasioKonversi']) }}%"></i></span>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="judul-panel">
                <div>
                    <h2>{{ $rankingKinerja['judul'] }}</h2>
                    <span>{{ $rankingKinerja['subjudul'] }}</span>
                </div>
            </div>
            <div class="daftar-progress">
                @forelse ($rankingKinerja['items'] as $item)
                    @php
                        $persenRanking = $item['target_closing'] > 0
                            ? min(100, round(($item['closing'] / $item['target_closing']) * 100, 1))
                            : min(100, $item['closing'] * 10);
                    @endphp
                    <div class="bar-progress">
                        <div>
                            <strong>{{ $loop->iteration }}. {{ $item['label'] }}</strong>
                            <span>
                                {{ $item['leads'] }} leads | {{ $item['closing'] }} closing
                                | target {{ $item['target_closing'] }}
                                | konversi {{ $item['rasio'] }}%
                            </span>
                        </div>
                        <span class="progress-kelas"><i style="width: {{ $persenRanking }}%"></i></span>
                    </div>
                @empty
                    <p class="kosong">Belum ada data ranking pada periode ini.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>{{ $performaRole['judul'] }}</h2>
                <span>{{ $performaRole['subjudul'] }}</span>
            </div>
        </div>
        <div class="daftar-progress">
            @forelse ($performaRole['items'] as $item)
                <div class="bar-progress">
                    <div>
                        <strong>{{ $item['label'] }}</strong>
                        <span>
                            {{ $item['total'] }}{{ $item['satuan'] ?? ' leads' }}
                            @if (! isset($item['satuan']))
                                | {{ $item['closing'] }} closing | {{ $item['follow_up'] }} follow up
                            @endif
                        </span>
                    </div>
                    <span class="progress-kelas">
                        <i style="width: {{ max(0, min(100, $item['persen'])) }}%"></i>
                    </span>
                </div>
            @empty
                <p class="kosong">Belum ada data performa pada periode ini.</p>
            @endforelse
        </div>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel">
            <div class="judul-panel">
                <div>
                    <h2>Dashboard Closing</h2>
                    <span>{{ $dashboardClosing['total'] }} siswa closing pada periode ini.</span>
                </div>
                <strong>Rp {{ number_format((float) $dashboardClosing['nominal'], 0, ',', '.') }}</strong>
            </div>
            @php
                $maksClosingProgram = max(1, (int) $dashboardClosing['perProgram']->max('total'));
            @endphp
            <div class="daftar-progress">
                @forelse ($dashboardClosing['perProgram'] as $item)
                    <div class="bar-progress">
                        <div>
                            <strong>{{ $item->label }}</strong>
                            <span>{{ $item->total }} siswa</span>
                        </div>
                        <span class="progress-kelas"><i style="width: {{ round(($item->total / $maksClosingProgram) * 100) }}%"></i></span>
                    </div>
                @empty
                    <p class="kosong">Belum ada closing pada periode ini.</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="judul-panel">
                <div>
                    <h2>Status Pembayaran Closing</h2>
                    <span>Distribusi pembayaran siswa closing.</span>
                </div>
            </div>
            @php
                $maksPembayaran = max(1, (int) $dashboardClosing['perPembayaran']->max('total'));
            @endphp
            <div class="daftar-progress">
                @forelse ($dashboardClosing['perPembayaran'] as $item)
                    <div class="bar-progress">
                        <div>
                            <strong>{{ $item->label }}</strong>
                            <span>{{ $item->total }} siswa</span>
                        </div>
                        <span class="progress-kelas"><i style="width: {{ round(($item->total / $maksPembayaran) * 100) }}%"></i></span>
                    </div>
                @empty
                    <p class="kosong">Belum ada status pembayaran closing.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Closing per Cabang</h2>
                <span>Jumlah siswa closing per cabang pada periode filter.</span>
            </div>
        </div>
        @php
            $maksClosingCabang = max(1, (int) $dashboardClosing['perCabang']->max('total'));
        @endphp
        <div class="daftar-progress">
            @forelse ($dashboardClosing['perCabang'] as $item)
                <div class="bar-progress">
                    <div>
                        <strong>{{ $item->label }}</strong>
                        <span>{{ $item->total }} siswa</span>
                    </div>
                    <span class="progress-kelas"><i style="width: {{ round(($item->total / $maksClosingCabang) * 100) }}%"></i></span>
                </div>
            @empty
                <p class="kosong">Belum ada closing per cabang pada periode ini.</p>
            @endforelse
        </div>
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

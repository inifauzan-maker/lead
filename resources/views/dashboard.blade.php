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

    @php
        $warnaPieSumber = ['#145f5b', '#f4a43b', '#3f7faf', '#e96c43', '#8bb438', '#8a5f3f', '#8d9aae', '#6f8f9f'];
        $totalPieSumber = max(0, (int) $perSumber->sum('total'));
        $posisiPie = 0;
        $irisanPie = [];

        foreach ($perSumber as $index => $item) {
            $persen = $totalPieSumber > 0 ? (((int) $item->total / $totalPieSumber) * 100) : 0;
            $akhir = $posisiPie + $persen;
            $warna = $warnaPieSumber[$index % count($warnaPieSumber)];
            $irisanPie[] = "{$warna} {$posisiPie}% {$akhir}%";
            $posisiPie = $akhir;
        }

        $gradientPie = $irisanPie ? 'conic-gradient('.implode(', ', $irisanPie).')' : 'conic-gradient(#e5e7eb 0 100%)';
    @endphp

    <section class="dashboard-kpi">
        <div class="kpi-hero">
            <div>
                <h2>Dashboard KPI CRM Leads</h2>
                <p>Monitoring lead, follow up, dan performa CSO</p>
            </div>
        </div>

        <div class="kpi-metrics">
            <article class="kpi-card">
                <span>Total Lead</span>
                <strong>{{ $totalLeadKeseluruhan }}</strong>
            </article>
            <article class="kpi-card">
                <span>Conversion Rate</span>
                <strong>{{ number_format($conversionRate, 2) }}%</strong>
            </article>
            <article class="kpi-card">
                <span>CSO Aktif</span>
                <strong>{{ $csoAktif }}</strong>
            </article>
            <article class="kpi-card">
                <span>Asal Sekolah</span>
                <strong>{{ $totalAsalSekolah }}</strong>
            </article>
        </div>

        <div class="kpi-analytics">
            <section class="panel kpi-growth-panel">
                <div class="judul-panel kpi-panel-title">
                    <div>
                        <h2>Pertumbuhan Lead</h2>
                        <span>{{ $semuaPeriode ? 'Semua data' : $grafikHarian['bulan'] }}</span>
                    </div>
                    <div class="kpi-tabs" aria-label="Mode grafik">
                        <span class="aktif">Harian</span>
                        <span>Bulanan</span>
                    </div>
                </div>
                <div class="grafik-wrap kpi-chart-wrap">
                    <div class="grafik-scroll">
                        <div class="grafik-kanvas kpi-line-canvas">
                            <div class="sumbu-y">
                                @foreach ($grafikHarian['skala'] as $nilai)
                                    <span>{{ $nilai }}</span>
                                @endforeach
                            </div>
                            <svg
                                class="grafik-svg kpi-line-svg"
                                viewBox="0 0 {{ $grafikHarian['lebar'] }} {{ $grafikHarian['tinggi'] }}"
                                preserveAspectRatio="none"
                                aria-label="Grafik pertumbuhan lead"
                            >
                                <polyline class="garis-lead kpi-line" points="{{ $grafikHarian['leadPoints'] }}"></polyline>
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

            <section class="panel kpi-source-panel">
                <div class="judul-panel">
                    <h2>Sumber Lead</h2>
                </div>
                <div class="kpi-pie" style="--pie: {{ $gradientPie }}"></div>
                <div class="kpi-pie-legend">
                    @forelse ($perSumber as $item)
                        <span>
                            <i style="background: {{ $warnaPieSumber[$loop->index % count($warnaPieSumber)] }}"></i>
                            {{ $item->sumber }}
                        </span>
                    @empty
                        <span><i></i>Belum ada data</span>
                    @endforelse
                </div>
            </section>
        </div>
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
                    <p class="kosong">Belum ada aging leads aktif pada filter ini.</p>
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
                    <p class="kosong">Belum ada performa user input pada filter ini.</p>
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
                <p class="kosong">Belum ada data konversi sumber pada filter ini.</p>
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
                        <span>{{ $targetKinerja['closing'] }} closing dari {{ $targetKinerja['leadsAktif'] + $targetKinerja['closing'] }} total leads filter ini</span>
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
                    <p class="kosong">Belum ada data ranking pada filter ini.</p>
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
                <p class="kosong">Belum ada data performa pada filter ini.</p>
            @endforelse
        </div>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel">
            <div class="judul-panel">
                <div>
                    <h2>Dashboard Closing</h2>
                    <span>{{ $dashboardClosing['total'] }} siswa closing pada filter ini.</span>
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
                    <p class="kosong">Belum ada closing pada filter ini.</p>
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
                <span>Jumlah siswa closing per cabang pada filter aktif.</span>
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
                <p class="kosong">Belum ada closing per cabang pada filter ini.</p>
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

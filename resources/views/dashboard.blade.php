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
                        <span>{{ $grafikHarian['bulan'] }}</span>
                    </div>
                    <div class="kpi-tabs" aria-label="Mode grafik">
                        <a
                            href="{{ route('dashboard', array_merge(request()->except('grafik'), ['grafik' => 'harian'])) }}"
                            class="{{ $modeGrafikPertumbuhan === 'harian' ? 'aktif' : '' }}"
                        >Harian</a>
                        <a
                            href="{{ route('dashboard', array_merge(request()->except('grafik'), ['grafik' => 'bulanan'])) }}"
                            class="{{ $modeGrafikPertumbuhan === 'bulanan' ? 'aktif' : '' }}"
                        >Bulanan</a>
                        <a
                            href="{{ route('dashboard', array_merge(request()->except('grafik'), ['grafik' => 'tahunan'])) }}"
                            class="{{ $modeGrafikPertumbuhan === 'tahunan' ? 'aktif' : '' }}"
                        >Tahunan</a>
                    </div>
                </div>
                <div class="grafik-wrap kpi-chart-wrap">
                    <div class="grafik-scroll">
                        <div class="grafik-kanvas kpi-line-canvas" style="--chart-width: {{ $grafikHarian['lebar'] }}px">
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
                                <path class="kpi-line-area" d="{{ $grafikHarian['areaLeadPath'] }}"></path>
                                <path class="garis-lead kpi-line" d="{{ $grafikHarian['leadPath'] }}"></path>
                                @foreach ($grafikHarian['titikLead'] as $titik)
                                    <circle
                                        class="kpi-line-dot"
                                        cx="{{ $titik['x'] }}"
                                        cy="{{ $titik['y'] }}"
                                        r="3.6"
                                    >
                                        <title>{{ $titik['label'] }}: {{ $titik['lead'] }} lead, {{ $titik['closing'] }} closing</title>
                                    </circle>
                                @endforeach
                            </svg>
                            <div class="sumbu-x" style="grid-template-columns: repeat({{ count($grafikHarian['hari']) }}, minmax(0, 1fr))">
                                @foreach ($grafikHarian['hari'] as $hari)
                                    <span title="{{ $hari['label'] }}: {{ $hari['lead'] }} lead, {{ $hari['closing'] }} closing">
                                        {{ $hari['tampil_label'] ? $hari['label'] : '' }}
                                    </span>
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
                <div class="kpi-pie" style="--pie: {{ $gradientPie }}" data-total="{{ $totalPieSumber }}"></div>
                <div class="kpi-pie-legend">
                    @forelse ($perSumber as $item)
                        @php
                            $persenSumber = $totalPieSumber > 0 ? round(((int) $item->total / $totalPieSumber) * 100, 1) : 0;
                        @endphp
                        <span>
                            <i style="background: {{ $warnaPieSumber[$loop->index % count($warnaPieSumber)] }}"></i>
                            <strong>{{ $item->sumber }}</strong>
                            <em>{{ $item->total }} | {{ $persenSumber }}%</em>
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

    <section class="grid-dua jarak-atas">
        <div class="panel kpi-table-panel">
            <div class="judul-panel">
                <div>
                    <h2>Aging Leads Aktif</h2>
                    <span>Umur leads aktif sejak tanggal masuk.</span>
                </div>
            </div>
            <div class="kpi-table-wrap">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th>Umur Lead</th>
                            <th>Total Lead</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agingLeads as $item)
                            <tr>
                                <td>{{ $item['label'] }}</td>
                                <td>{{ $item['total'] }} leads</td>
                                <td><span class="kpi-badge kpi-badge-blue">{{ $item['persen'] }}%</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="kpi-table-empty">Belum ada aging leads aktif pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel kpi-table-panel">
            <div class="judul-panel">
                <div>
                    <h2>Performa User Input</h2>
                    <span>Konversi leads berdasarkan user yang input data.</span>
                </div>
            </div>
            <div class="kpi-table-wrap">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Total Lead</th>
                            <th>Lead Terkonversi</th>
                            <th>Conversion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($performaInputUser as $item)
                            <tr>
                                <td>{{ $item['label'] }}</td>
                                <td>{{ $item['leads'] }}</td>
                                <td>{{ $item['closing'] }}</td>
                                <td><span class="kpi-badge kpi-badge-green">{{ $item['rasio'] }}%</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="kpi-table-empty">Belum ada performa user input pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="panel kpi-table-panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Konversi per Sumber</h2>
                <span>Kualitas sumber leads berdasarkan rasio closing.</span>
            </div>
        </div>
        <div class="kpi-table-wrap">
            <table class="kpi-table">
                <thead>
                    <tr>
                        <th>Sumber</th>
                        <th>Total Lead</th>
                        <th>Lead Terkonversi</th>
                        <th>Conversion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($konversiSumber as $item)
                        <tr>
                            <td>{{ $item['label'] }}</td>
                            <td>{{ $item['leads'] }}</td>
                            <td>{{ $item['closing'] }}</td>
                            <td><span class="kpi-badge kpi-badge-green">{{ $item['rasio'] }}%</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="kpi-table-empty">Belum ada data konversi sumber pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel kpi-table-panel">
            <div class="judul-panel">
                <div>
                    <h2>Target dan Konversi</h2>
                    <span>{{ $targetKinerja['labelTarget'] }}</span>
                </div>
                <strong>{{ $targetKinerja['rasioKonversi'] }}%</strong>
            </div>
            <div class="kpi-table-wrap">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th>KPI</th>
                            <th>Realisasi</th>
                            <th>Target</th>
                            <th>Capaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Target Leads</td>
                            <td>{{ $targetKinerja['leadsAktif'] }} leads aktif</td>
                            <td>{{ $targetKinerja['targetLeads'] }}</td>
                            <td><span class="kpi-badge kpi-badge-blue">{{ $targetKinerja['persenLeads'] }}%</span></td>
                        </tr>
                        <tr>
                            <td>Target Closing</td>
                            <td>{{ $targetKinerja['closing'] }} closing</td>
                            <td>{{ $targetKinerja['targetClosing'] }}</td>
                            <td><span class="kpi-badge kpi-badge-green">{{ $targetKinerja['persenClosing'] }}%</span></td>
                        </tr>
                        <tr>
                            <td>Rasio Konversi</td>
                            <td>{{ $targetKinerja['closing'] }} / {{ $targetKinerja['leadsAktif'] + $targetKinerja['closing'] }} total leads</td>
                            <td>-</td>
                            <td><span class="kpi-badge kpi-badge-green">{{ $targetKinerja['rasioKonversi'] }}%</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel kpi-table-panel">
            <div class="judul-panel">
                <div>
                    <h2>{{ $rankingKinerja['judul'] }}</h2>
                    <span>{{ $rankingKinerja['subjudul'] }}</span>
                </div>
            </div>
            <div class="kpi-table-wrap">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th>{{ str_contains($rankingKinerja['judul'], 'Cabang') ? 'Cabang' : 'User' }}</th>
                            <th>Total Lead</th>
                            <th>Closing</th>
                            <th>Target Closing</th>
                            <th>Conversion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rankingKinerja['items'] as $item)
                            <tr>
                                <td>{{ $item['label'] }}</td>
                                <td>{{ $item['leads'] }}</td>
                                <td>{{ $item['closing'] }}</td>
                                <td>{{ $item['target_closing'] }}</td>
                                <td><span class="kpi-badge kpi-badge-green">{{ $item['rasio'] }}%</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="kpi-table-empty">Belum ada data ranking pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="panel kpi-table-panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>{{ $performaRole['judul'] }}</h2>
                <span>{{ $performaRole['subjudul'] }}</span>
            </div>
        </div>
        <div class="kpi-table-wrap">
            <table class="kpi-table">
                <thead>
                    <tr>
                        <th>{{ str_contains($performaRole['judul'], 'Cabang') ? 'Cabang' : 'User' }}</th>
                        <th>Total</th>
                        <th>Follow Up</th>
                        <th>Closing</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($performaRole['items'] as $item)
                        <tr>
                            <td>{{ $item['label'] }}</td>
                            <td>{{ $item['total'] }}{{ $item['satuan'] ?? '' }}</td>
                            <td>{{ isset($item['satuan']) ? '-' : $item['follow_up'] }}</td>
                            <td>{{ isset($item['satuan']) ? '-' : $item['closing'] }}</td>
                            <td><span class="kpi-badge kpi-badge-blue">{{ max(0, min(100, $item['persen'])) }}%</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="kpi-table-empty">Belum ada data performa pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel kpi-table-panel">
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
            <div class="kpi-table-wrap">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Total Siswa</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dashboardClosing['perProgram'] as $item)
                            <tr>
                                <td>{{ $item->label }}</td>
                                <td>{{ $item->total }}</td>
                                <td><span class="kpi-badge kpi-badge-blue">{{ round(($item->total / $maksClosingProgram) * 100) }}%</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="kpi-table-empty">Belum ada closing pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel kpi-table-panel">
            <div class="judul-panel">
                <div>
                    <h2>Status Pembayaran Closing</h2>
                    <span>Distribusi pembayaran siswa closing.</span>
                </div>
            </div>
            @php
                $maksPembayaran = max(1, (int) $dashboardClosing['perPembayaran']->max('total'));
            @endphp
            <div class="kpi-table-wrap">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Total Siswa</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dashboardClosing['perPembayaran'] as $item)
                            <tr>
                                <td>{{ $item->label }}</td>
                                <td>{{ $item->total }}</td>
                                <td><span class="kpi-badge kpi-badge-blue">{{ round(($item->total / $maksPembayaran) * 100) }}%</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="kpi-table-empty">Belum ada status pembayaran closing.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="panel kpi-table-panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Closing per Cabang</h2>
                <span>Jumlah siswa closing per cabang pada filter aktif.</span>
            </div>
        </div>
        @php
            $maksClosingCabang = max(1, (int) $dashboardClosing['perCabang']->max('total'));
        @endphp
        <div class="kpi-table-wrap">
            <table class="kpi-table">
                <thead>
                    <tr>
                        <th>Cabang</th>
                        <th>Total Siswa</th>
                        <th>Share</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dashboardClosing['perCabang'] as $item)
                        <tr>
                            <td>{{ $item->label }}</td>
                            <td>{{ $item->total }}</td>
                            <td><span class="kpi-badge kpi-badge-blue">{{ round(($item->total / $maksClosingCabang) * 100) }}%</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="kpi-table-empty">Belum ada closing per cabang pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

@endsection

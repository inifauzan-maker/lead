@extends('tata-letak', ['judul' => 'Follow Up'])

@section('konten')
    <section class="grid-ringkasan">
        <div class="kartu-stat">
            <span>Total Aktivitas</span>
            <strong>{{ $totalAktivitas }}</strong>
        </div>
        <div class="kartu-stat">
            <span>Follow Up Hari Ini</span>
            <strong>{{ $butuhFollowUpHariIni }}</strong>
        </div>
        <div class="kartu-stat">
            <span>Terlambat</span>
            <strong>{{ $followUpTerlambat }}</strong>
        </div>
        <div class="kartu-stat">
            <span>Closing dari Follow Up</span>
            <strong>{{ $closingFollowUp }}</strong>
        </div>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Reminder Follow Up</h2>
                <span>{{ $reminderFollowUp->count() }} jadwal hari ini atau terlambat.</span>
            </div>
        </div>
        <div class="daftar-ringkas">
            @forelse ($reminderFollowUp as $item)
                <article class="baris-ringkas {{ $item->overdue() ? 'reminder-terlambat' : '' }}">
                    <div>
                        <strong>{{ $item->prospek?->nama ?: 'Leads terhapus' }}</strong>
                        <small>
                            Jadwal: {{ $item->tanggal_follow_up_berikutnya?->format('d M Y') }}
                            ({{ $item->statusJadwal() }})
                        </small>
                        <small>Hasil terakhir: {{ $item->hasil }} oleh {{ $item->user?->name ?: 'User tidak aktif' }}</small>
                        <small>{{ $item->tindak_lanjut ?: 'Tindak lanjut belum diisi' }}</small>
                    </div>
                    <div class="aksi-tabel">
                        @if ($item->prospek)
                            <a href="{{ route('prospek.show', $item->prospek) }}">Detail</a>
                        @endif
                        <em>{{ $item->prioritas }}</em>
                    </div>
                </article>
            @empty
                <p class="kosong">Tidak ada reminder follow up hari ini.</p>
            @endforelse
        </div>
    </section>

    @if (auth()->user()->bisaInputLeads())
        <section class="panel">
            <div class="judul-panel">
                <div>
                    <h2>Catat Aktivitas Follow Up</h2>
                    <span>Simpan setiap kontak agar jumlah dan hasil follow up per leads tercatat.</span>
                </div>
            </div>

            <form class="grid-form" method="POST" action="{{ route('follow-up.store') }}">
                @csrf
                <label class="penuh">
                    Leads
                    <select name="prospek_id" required>
                        <option value="">Pilih leads</option>
                        @foreach ($calonProspek as $item)
                            <option value="{{ $item->id }}" @selected(old('prospek_id') == $item->id)>
                                {{ $item->nama }} - {{ $item->no_wa ?: 'WA belum diisi' }} - Follow up ke-{{ $item->follow_ups_count + 1 }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Tanggal Follow Up
                    <input type="datetime-local" name="tanggal_follow_up" value="{{ old('tanggal_follow_up', now()->format('Y-m-d\TH:i')) }}" required>
                </label>
                <label>
                    Metode
                    <select name="metode" required>
                        @foreach ($metodeFollowUp as $item)
                            <option value="{{ $item }}" @selected(old('metode', 'WhatsApp') === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Hasil
                    <select name="hasil" required>
                        @foreach ($hasilFollowUp as $item)
                            <option value="{{ $item }}" @selected(old('hasil', 'Tersambung') === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Prioritas
                    <select name="prioritas" required>
                        @foreach ($prioritasFollowUp as $item)
                            <option value="{{ $item }}" @selected(old('prioritas', 'Normal') === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Jadwal Follow Up Berikutnya
                    <input type="date" name="tanggal_follow_up_berikutnya" value="{{ old('tanggal_follow_up_berikutnya') }}">
                </label>
                <label class="penuh">
                    Catatan Percakapan
                    <textarea name="catatan" rows="3" placeholder="Contoh: Orang tua meminta brosur dan rincian biaya.">{{ old('catatan') }}</textarea>
                </label>
                <label class="penuh">
                    Tindak Lanjut
                    <textarea name="tindak_lanjut" rows="2" placeholder="Contoh: Kirim reminder besok sore.">{{ old('tindak_lanjut') }}</textarea>
                </label>
                <div class="aksi-form penuh">
                    <button class="tombol utama" type="submit">Simpan Follow Up</button>
                </div>
            </form>
        </section>
    @endif

    <section class="panel jarak-atas">
        <div class="judul-panel judul-heatmap">
            <div>
                <h2>Kalender Aktivitas Follow Up</h2>
                <span>{{ $kalender['total'] }} aktivitas follow up pada {{ $kalender['judul'] }}.</span>
            </div>
            <form class="filter-dashboard" method="GET" action="{{ route('follow-up.index') }}">
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
                <select name="cabang">
                    <option value="">Semua cabang</option>
                    @foreach ($cabang as $item)
                        <option value="{{ $item }}" @selected(request('cabang') === $item)>{{ $item }}</option>
                    @endforeach
                </select>
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

        <div class="kalender-follow-up">
            @foreach ($kalender['hari'] as $hari)
                <div class="nama-hari">{{ $hari }}</div>
            @endforeach
            @foreach ($kalender['pekan'] as $pekan)
                @foreach ($pekan as $hari)
                    <div class="tanggal-kalender {{ $hari['bulan_aktif'] ? '' : 'tanggal-luar-bulan' }} {{ $hari['hari_ini'] ? 'tanggal-hari-ini' : '' }}">
                        <span>{{ $hari['nomor'] }}</span>
                        @if ($hari['total'] > 0)
                            <strong>{{ $hari['total'] }} aktivitas</strong>
                        @else
                            <em>-</em>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Data Leads Follow Up</h2>
                <span>{{ $prospek->total() }} data leads dengan status atau riwayat follow up.</span>
            </div>
        </div>

        <div class="bungkus-tabel">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>WA</th>
                        <th>Program</th>
                        <th>Status</th>
                        <th>Follow Up Ke</th>
                        <th>Hasil Terakhir</th>
                        <th>Jadwal Berikutnya</th>
                        <th>Status Jadwal</th>
                        <th>PIC Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prospek as $item)
                        @php($terakhir = $item->followUpTerakhir)
                        @php($berikutnya = $item->followUpBerikutnya)
                        @php($bisaUbah = $item->bisaDiubahOleh(auth()->user()))
                        <tr>
                            <td>
                                <strong>{{ $item->nama }}</strong>
                                <small>{{ $item->asal_sekolah ?: 'Sekolah belum diisi' }}</small>
                            </td>
                            <td>{{ $item->noWaUntuk(auth()->user()) }}</td>
                            <td>{{ $item->program ?: '-' }}</td>
                            <td><span class="badge">{{ $item->status }}</span></td>
                            <td>
                                <strong>{{ $item->follow_ups_count }}</strong>
                                <small>{{ $item->follow_ups_count > 0 ? 'kali follow up' : 'belum dicatat' }}</small>
                            </td>
                            <td>
                                <strong>{{ $terakhir?->hasil ?: '-' }}</strong>
                                <small>{{ $terakhir?->tanggal_follow_up?->format('d M Y H:i') ?: 'Belum ada aktivitas' }}</small>
                            </td>
                            <td>
                                <strong>{{ $berikutnya?->tanggal_follow_up_berikutnya?->format('d M Y') ?: '-' }}</strong>
                                <small>{{ $berikutnya?->tindak_lanjut ?: 'Belum ada tindak lanjut' }}</small>
                            </td>
                            <td>
                                @php($statusJadwal = $item->statusFollowUp())
                                <span class="badge {{ $statusJadwal === 'Overdue' ? 'badge-bahaya' : ($statusJadwal === 'Hari ini' ? 'badge-peringatan' : '') }}">
                                    {{ $statusJadwal }}
                                </span>
                            </td>
                            <td>{{ $terakhir?->user?->name ?: ($item->penanggungJawab?->name ?: '-') }}</td>
                            <td class="aksi-tabel">
                                <a href="{{ route('prospek.show', $item) }}">Detail</a>
                                @if ($bisaUbah)
                                    <a href="{{ route('prospek.edit', $item) }}">Edit</a>
                                @else
                                    <span class="petunjuk">Lihat saja</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="kosong">Belum ada data follow up.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paginasi">{{ $prospek->links() }}</div>
    </section>

    <section class="panel jarak-atas">
        <div class="judul-panel">
            <div>
                <h2>Riwayat Aktivitas Terbaru</h2>
                <span>Timeline follow up terakhir yang tercatat di sistem.</span>
            </div>
        </div>

        <div class="daftar-aktivitas-profil">
            @forelse ($aktivitasTerbaru as $item)
                <div class="aktivitas-profil">
                    <i class="{{ $item->hasil === 'Closing' ? 'hijau' : ($item->hasil === 'Tidak tertarik' ? 'ungu' : 'biru') }}"></i>
                    <div>
                        <strong>{{ $item->prospek?->nama ?: 'Leads terhapus' }} - {{ $item->hasil }}</strong>
                        <small>
                            {{ $item->tanggal_follow_up?->format('d M Y H:i') }} oleh {{ $item->user?->name ?: 'User tidak aktif' }}
                            melalui {{ $item->metode }}
                        </small>
                        @if ($item->catatan)
                            <p>{{ $item->catatan }}</p>
                        @endif
                        @if ($item->tindak_lanjut || $item->tanggal_follow_up_berikutnya)
                            <small>
                                Tindak lanjut: {{ $item->tindak_lanjut ?: '-' }}
                                {{ $item->tanggal_follow_up_berikutnya ? ' | Jadwal '.$item->tanggal_follow_up_berikutnya->format('d M Y') : '' }}
                            </small>
                        @endif
                    </div>
                </div>
            @empty
                <p class="kosong">Belum ada riwayat follow up.</p>
            @endforelse
        </div>
    </section>
@endsection

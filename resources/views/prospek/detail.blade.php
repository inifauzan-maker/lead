@extends('tata-letak', ['judul' => 'Detail Leads'])

@section('konten')
    @php($whatsappWebUrl = $prospek->whatsappWebUrlUntuk(auth()->user(), $templateWhatsapp))

    <section class="hero-modul">
        <div>
            <span>{{ $prospek->status }}</span>
            <h2>{{ $prospek->nama }}</h2>
            <p>{{ $prospek->asal_sekolah ?: 'Asal sekolah belum diisi' }}{{ $prospek->program ? ' - '.$prospek->program : '' }}</p>
        </div>
        <strong>{{ $prospek->cabang ?: 'Tanpa cabang' }}</strong>
    </section>

    <section class="grid-ringkasan">
        <article class="kartu-stat">
            <span>Status</span>
            <strong>{{ $prospek->status }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Follow Up</span>
            <strong>{{ $prospek->followUps->count() }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Hasil Terakhir</span>
            <strong>{{ $prospek->followUpTerakhir?->hasil ?: '-' }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Status Jadwal</span>
            <strong>{{ $prospek->statusFollowUp() }}</strong>
        </article>
    </section>

    <section class="grid-dua">
        <div class="panel">
            <div class="judul-panel">
                <h2>Profil Leads</h2>
                @if ($bisaUbah)
                    <a href="{{ route('prospek.edit', $prospek) }}">Edit</a>
                @endif
            </div>
            <div class="daftar-ringkas">
                <div class="baris-ringkas">
                    <strong>No WA</strong>
                    <span>
                        {{ $prospek->noWaUntuk(auth()->user()) }}
                        @if ($whatsappWebUrl)
                            <a href="{{ $whatsappWebUrl }}" target="_blank" rel="noopener noreferrer">WA Web</a>
                        @endif
                    </span>
                </div>
                <div class="baris-ringkas"><strong>Jenjang</strong><span>{{ $prospek->jenjang ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Kelas</strong><span>{{ $prospek->kelas ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Kota Asal</strong><span>{{ $prospek->kota_asal ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Sumber</strong><span>{{ $prospek->sumber ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Input Oleh</strong><span>{{ $prospek->pembuat?->name ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Diserahkan ke</strong><span>{{ $prospek->diserahkan_ke ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Tgl Masuk</strong><span>{{ $prospek->tgl_masuk?->format('d M Y') ?: '-' }}</span></div>
            </div>
            @if ($prospek->keterangan)
                <div class="catatan-detail">
                    <strong>Keterangan</strong>
                    <p>{{ $prospek->keterangan }}</p>
                </div>
            @endif
        </div>

        <div class="panel">
            <div class="judul-panel">
                <h2>Reminder Follow Up</h2>
                <span>{{ $prospek->followUps->whereNotNull('tanggal_follow_up_berikutnya')->count() }} jadwal</span>
            </div>
            @if ($prospek->followUpBerikutnya)
                <div class="baris-ringkas {{ $prospek->followUpBerikutnya->overdue() ? 'reminder-terlambat' : '' }}">
                    <div>
                        <strong>Jadwal aktif berikutnya</strong>
                        <small>{{ $prospek->followUpBerikutnya->tanggal_follow_up_berikutnya?->format('d M Y') }} - {{ $prospek->followUpBerikutnya->statusJadwal() }}</small>
                        <small>{{ $prospek->followUpBerikutnya->tindak_lanjut ?: 'Tindak lanjut belum diisi' }}</small>
                    </div>
                </div>
            @endif
            <div class="daftar-ringkas">
                @forelse ($prospek->followUps->whereNotNull('tanggal_follow_up_berikutnya')->take(5) as $item)
                    <article class="baris-ringkas {{ $item->overdue() ? 'reminder-terlambat' : '' }}">
                        <div>
                            <strong>{{ $item->tanggal_follow_up_berikutnya?->format('d M Y') }}</strong>
                            <small>{{ $item->statusJadwal() }} - {{ $item->tindak_lanjut ?: 'Tindak lanjut belum diisi' }}</small>
                        </div>
                        <em>{{ $item->prioritas }}</em>
                    </article>
                @empty
                    <p class="kosong">Belum ada reminder follow up.</p>
                @endforelse
            </div>
        </div>
    </section>

    @if ($prospek->status === 'Daftar')
        <section class="panel jarak-atas">
            <div class="judul-panel">
                <div>
                    <h2>Data Siswa / Closing</h2>
                    <span>Data administrasi siswa setelah leads dinyatakan closing.</span>
                </div>
            </div>
            <div class="daftar-ringkas">
                <div class="baris-ringkas"><strong>Tgl Daftar</strong><span>{{ $prospek->tanggal_daftar?->format('d M Y') ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Program Final</strong><span>{{ $prospek->program_final ?: ($prospek->program ?: '-') }}</span></div>
                <div class="baris-ringkas"><strong>Status Pembayaran</strong><span>{{ $prospek->status_pembayaran ?: 'Belum Diisi' }}</span></div>
                <div class="baris-ringkas"><strong>Nominal Pembayaran</strong><span>{{ $prospek->nominal_pembayaran ? 'Rp '.number_format((float) $prospek->nominal_pembayaran, 0, ',', '.') : '-' }}</span></div>
                <div class="baris-ringkas"><strong>Kelas / Angkatan</strong><span>{{ $prospek->kelas_angkatan ?: '-' }}</span></div>
            </div>
            @if ($prospek->catatan_administrasi)
                <div class="catatan-detail">
                    <strong>Catatan Administrasi</strong>
                    <p>{{ $prospek->catatan_administrasi }}</p>
                </div>
            @endif
        </section>
    @endif

    <section class="grid-dua jarak-atas">
        <div class="panel">
            <div class="judul-panel">
                <h2>Riwayat Follow Up</h2>
                <span>{{ $prospek->followUps->count() }} aktivitas</span>
            </div>
            <div class="daftar-aktivitas-profil">
                @forelse ($prospek->followUps as $item)
                    <div class="aktivitas-profil">
                        <i class="{{ $item->hasil === 'Closing' ? 'hijau' : ($item->hasil === 'Tidak tertarik' ? 'ungu' : 'biru') }}"></i>
                        <div>
                            <strong>{{ $item->hasil }} - {{ $item->metode }}</strong>
                            <small>{{ $item->tanggal_follow_up?->format('d M Y H:i') }} oleh {{ $item->user?->name ?: 'User tidak aktif' }}</small>
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
        </div>

        <div class="panel">
            <div class="judul-panel">
                <h2>Tugas Terkait</h2>
                <span>{{ $prospek->tasks->count() }} tugas</span>
            </div>
            <div class="daftar-ringkas">
                @forelse ($prospek->tasks as $task)
                    <article class="baris-ringkas">
                        <div>
                            <strong>{{ $task->judul }}</strong>
                            <small>{{ $task->deskripsi ?: 'Tanpa deskripsi' }}</small>
                            <small>PIC: {{ $task->penanggungJawab?->name ?: '-' }} | Tenggat: {{ $task->tenggat?->format('d M Y') ?: '-' }}</small>
                        </div>
                        <em>{{ $task->status }}</em>
                    </article>
                @empty
                    <p class="kosong">Belum ada tugas terkait.</p>
                @endforelse
            </div>
        </div>
    </section>

    <div class="aksi-form">
        <a class="tombol sekunder" href="{{ route('prospek.index') }}">Kembali</a>
    </div>
@endsection

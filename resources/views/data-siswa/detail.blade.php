@extends('tata-letak', ['judul' => 'Detail Data Siswa'])

@section('konten')
    @php($whatsappWebUrl = $prospek->whatsappWebUrlUntuk(auth()->user(), $templateWhatsapp))

    <section class="hero-modul">
        <div>
            <span>Data Siswa Closing</span>
            <h2>{{ $prospek->nama }}</h2>
            <p>{{ $prospek->asal_sekolah ?: 'Asal sekolah belum diisi' }}{{ $prospek->program_final || $prospek->program ? ' - '.($prospek->program_final ?: $prospek->program) : '' }}</p>
        </div>
        <strong>{{ $prospek->cabang ?: 'Tanpa cabang' }}</strong>
    </section>

    <section class="grid-ringkasan">
        <article class="kartu-stat">
            <span>Tgl Closing</span>
            <strong>{{ $prospek->tanggal_daftar?->format('d M Y') ?: $prospek->updated_at?->format('d M Y') }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Program Final</span>
            <strong>{{ $prospek->program_final ?: ($prospek->program ?: '-') }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Pembayaran</span>
            <strong>{{ $prospek->status_pembayaran ?: 'Belum Diisi' }}</strong>
        </article>
        <article class="kartu-stat">
            <span>Total Follow Up</span>
            <strong>{{ $prospek->followUps->count() }}</strong>
        </article>
    </section>

    <section class="grid-dua">
        <div class="panel">
            <div class="judul-panel">
                <div>
                    <h2>Profil Siswa</h2>
                    <span>Data identitas dan asal leads.</span>
                </div>
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
                <div class="baris-ringkas"><strong>Asal Sekolah</strong><span>{{ $prospek->asal_sekolah ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Jenjang</strong><span>{{ $prospek->jenjang ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Kelas Awal</strong><span>{{ $prospek->kelas ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Kelas / Angkatan</strong><span>{{ $prospek->kelas_angkatan ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Kota Asal</strong><span>{{ $prospek->kota_asal ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Sumber Leads</strong><span>{{ $prospek->sumber ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Input Oleh</strong><span>{{ $prospek->pembuat?->name ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>PIC</strong><span>{{ $prospek->penanggungJawab?->name ?: 'Belum ditugaskan' }}</span></div>
                <div class="baris-ringkas"><strong>Tgl Masuk Leads</strong><span>{{ $prospek->tgl_masuk?->format('d M Y') ?: '-' }}</span></div>
            </div>
        </div>

        <div class="panel">
            <div class="judul-panel">
                <div>
                    <h2>Administrasi Closing</h2>
                    <span>Pembayaran dan catatan administrasi siswa.</span>
                </div>
            </div>
            <div class="daftar-ringkas">
                <div class="baris-ringkas"><strong>Status Pembayaran</strong><span>{{ $prospek->status_pembayaran ?: 'Belum Diisi' }}</span></div>
                <div class="baris-ringkas"><strong>Nominal Pembayaran</strong><span>{{ $prospek->nominal_pembayaran ? 'Rp '.number_format((float) $prospek->nominal_pembayaran, 0, ',', '.') : '-' }}</span></div>
                <div class="baris-ringkas"><strong>Program Awal</strong><span>{{ $prospek->program ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Program Final</strong><span>{{ $prospek->program_final ?: ($prospek->program ?: '-') }}</span></div>
            </div>
            @if ($prospek->catatan_administrasi)
                <div class="catatan-detail">
                    <strong>Catatan Administrasi</strong>
                    <p>{{ $prospek->catatan_administrasi }}</p>
                </div>
            @endif
        </div>
    </section>

    <section class="grid-dua jarak-atas">
        <div class="panel">
            <div class="judul-panel">
                <h2>Riwayat Perubahan Status</h2>
                <span>{{ $prospek->riwayatStatus->count() }} catatan</span>
            </div>
            <div class="daftar-aktivitas-profil">
                @forelse ($prospek->riwayatStatus as $item)
                    <div class="aktivitas-profil">
                        <i class="{{ $item->status_baru === 'Daftar' ? 'hijau' : ($item->status_baru === 'Tidak Tertarik' ? 'ungu' : 'biru') }}"></i>
                        <div>
                            <strong>{{ $item->status_lama ?: 'Status awal' }} -> {{ $item->status_baru }}</strong>
                            <small>{{ $item->created_at?->format('d M Y H:i') }} oleh {{ $item->user?->name ?: 'Sistem' }} | {{ ucfirst(str_replace('_', ' ', $item->sumber)) }}</small>
                            @if ($item->catatan)
                                <p>{{ $item->catatan }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="kosong">Belum ada riwayat perubahan status.</p>
                @endforelse
            </div>
        </div>

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
    </section>

    <section class="panel jarak-atas">
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
    </section>

    <div class="aksi-form">
        <a class="tombol sekunder" href="{{ route('data-siswa.index') }}">Kembali ke Data Siswa</a>
        <a class="tombol sekunder" href="{{ route('prospek.show', $prospek) }}">Detail Leads</a>
    </div>
@endsection

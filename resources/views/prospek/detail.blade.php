@extends('tata-letak', ['judul' => 'Detail Leads'])

@section('konten')
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
            <span>Tugas</span>
            <strong>{{ $prospek->tasks->count() }}</strong>
        </article>
        <article class="kartu-stat">
            <span>PIC</span>
            <strong>{{ $prospek->penanggungJawab?->name ?: '-' }}</strong>
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
                <div class="baris-ringkas"><strong>No WA</strong><span>{{ $prospek->noWaUntuk(auth()->user()) }}</span></div>
                <div class="baris-ringkas"><strong>Kelas</strong><span>{{ $prospek->kelas ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Kota Asal</strong><span>{{ $prospek->kota_asal ?: '-' }}</span></div>
                <div class="baris-ringkas"><strong>Sumber</strong><span>{{ $prospek->sumber ?: '-' }}</span></div>
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
            <div class="daftar-ringkas">
                @forelse ($prospek->followUps->whereNotNull('tanggal_follow_up_berikutnya')->take(5) as $item)
                    <article class="baris-ringkas {{ $item->tanggal_follow_up_berikutnya?->isPast() && ! $item->tanggal_follow_up_berikutnya?->isToday() ? 'reminder-terlambat' : '' }}">
                        <div>
                            <strong>{{ $item->tanggal_follow_up_berikutnya?->format('d M Y') }}</strong>
                            <small>{{ $item->tindak_lanjut ?: 'Tindak lanjut belum diisi' }}</small>
                        </div>
                        <em>{{ $item->prioritas }}</em>
                    </article>
                @empty
                    <p class="kosong">Belum ada reminder follow up.</p>
                @endforelse
            </div>
        </div>
    </section>

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

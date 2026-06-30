@extends('tata-letak', ['judul' => 'Tugas'])

@section('konten')
    <section class="hero-modul">
        <div>
            <span>Task Management</span>
            <h2>Prioritaskan pekerjaan berdasarkan status leads terbaru.</h2>
        </div>
        @if (auth()->user()->bisaKelolaTugas())
            <a class="tombol utama" href="#tambah-tugas">Tambah Tugas</a>
        @endif
    </section>

    @if (auth()->user()->bisaKelolaTugas())
        <section class="panel" id="tambah-tugas">
            <div class="judul-panel">
                <div>
                    <h2>Tambah Tugas</h2>
                    <span>Buat task operasional dan hubungkan ke leads jika diperlukan.</span>
                </div>
            </div>
            <form class="grid-form" method="POST" action="{{ route('profil.tugas.store') }}">
                @csrf
                <label>
                    Judul
                    <input type="text" name="judul" value="{{ old('judul') }}" required>
                </label>
                <label>
                    Penanggung Jawab
                    <select name="assigned_to">
                        <option value="">Belum ditugaskan</option>
                        @foreach ($adminTugas as $user)
                            <option value="{{ $user->id }}" @selected(old('assigned_to') == $user->id)>
                                {{ $user->name }}{{ $user->cabang ? ' - '.$user->cabang : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Status
                    <select name="status" required>
                        @foreach ($statusTugas as $status)
                            <option value="{{ $status }}" @selected(old('status', 'Baru') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Prioritas
                    <select name="prioritas" required>
                        @foreach ($prioritasTugas as $prioritas)
                            <option value="{{ $prioritas }}" @selected(old('prioritas', 'Normal') === $prioritas)>{{ $prioritas }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Tenggat
                    <input type="date" name="tenggat" value="{{ old('tenggat') }}">
                </label>
                <label>
                    Leads Terkait
                    <select name="prospek_id">
                        <option value="">Tidak terhubung ke leads</option>
                        @foreach ($prospek as $item)
                            <option value="{{ $item->id }}" @selected(old('prospek_id') == $item->id)>
                                {{ $item->nama }} - {{ $item->status }}
                            </option>
                        @endforeach
                    </select>
                </label>
                @if (auth()->user()->aksesSemuaCabang())
                    <label>
                        Cabang
                        <input type="text" name="cabang" value="{{ old('cabang') }}" placeholder="Contoh: Bandung">
                    </label>
                @endif
                <label class="penuh">
                    Deskripsi
                    <textarea name="deskripsi" rows="3">{{ old('deskripsi') }}</textarea>
                </label>
                <div class="aksi-form penuh">
                    <button class="tombol utama" type="submit">Simpan Tugas</button>
                </div>
            </form>
        </section>
    @endif

    <section class="kanban-tugas">
        @foreach ($tugas as $kolom)
            <div class="kolom-tugas">
                <div class="kepala-tugas">
                    <span class="titik-status {{ $kolom['warna'] }}"></span>
                    <h2>{{ $kolom['judul'] }}</h2>
                    <strong>{{ $kolom['items']->count() }}</strong>
                </div>
                <div class="daftar-tugas">
                    @forelse ($kolom['items'] as $item)
                        <article class="kartu-tugas">
                            <span>{{ $item->prioritas }}{{ $item->tenggat ? ' - '.$item->tenggat->format('d M Y') : '' }}</span>
                            <h3>{{ $item->judul }}</h3>
                            <p>{{ $item->deskripsi ?: ($item->prospek?->nama ?: 'Tidak terhubung ke leads') }}</p>
                            <div>
                                <small>{{ $item->cabang ?: '-' }}{{ $item->penanggungJawab ? ' - '.$item->penanggungJawab->name : '' }}</small>
                                @if (auth()->user()->bisaKelolaTugas() && $item->prospek)
                                    <a href="{{ route('prospek.edit', $item->prospek) }}">Buka</a>
                                @endif
                            </div>
                            @if (auth()->user()->bisaKelolaTugas())
                                <form class="form-ringkas" method="POST" action="{{ route('profil.tugas.update', $item) }}">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" aria-label="Status tugas">
                                        @foreach ($statusTugas as $status)
                                            <option value="{{ $status }}" @selected($item->status === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <select name="prioritas" aria-label="Prioritas tugas">
                                        @foreach ($prioritasTugas as $prioritas)
                                            <option value="{{ $prioritas }}" @selected($item->prioritas === $prioritas)>{{ $prioritas }}</option>
                                        @endforeach
                                    </select>
                                    <input type="date" name="tenggat" value="{{ $item->tenggat?->format('Y-m-d') }}" aria-label="Tenggat tugas">
                                    <input type="hidden" name="assigned_to" value="{{ $item->assigned_to }}">
                                    <button class="tombol sekunder" type="submit">Update</button>
                                </form>
                                <form class="form-ringkas" method="POST" action="{{ route('profil.tugas.komentar.store', $item) }}">
                                    @csrf
                                    <textarea name="komentar" rows="2" placeholder="Tambah komentar tugas" required></textarea>
                                    <button class="tombol sekunder" type="submit">Komentar</button>
                                </form>
                                @if ($item->komentar->isNotEmpty())
                                    <div class="komentar-tugas">
                                        @foreach ($item->komentar->take(2) as $komentar)
                                            <small><strong>{{ $komentar->user?->name ?: 'User' }}:</strong> {{ $komentar->komentar }}</small>
                                        @endforeach
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('profil.tugas.destroy', $item) }}" data-konfirmasi data-judul-konfirmasi="Hapus tugas?" data-pesan-konfirmasi="Hapus tugas {{ $item->judul }}?" data-label-setuju="Hapus">
                                    @csrf
                                    @method('DELETE')
                                    <button class="tombol bahaya" type="submit">Hapus</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="kosong">Tidak ada tugas.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </section>
@endsection

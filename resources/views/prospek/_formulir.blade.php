<div class="grid-form">
    <label>
        Nama
        <input type="text" name="nama" value="{{ old('nama', $prospek->nama) }}" required>
        @error('nama') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        No WA
        <input type="text" name="no_wa" value="{{ old('no_wa', $prospek->no_wa) }}">
        <small class="petunjuk">No WA yang sama akan ditolak agar tidak ada input ganda.</small>
        @error('no_wa') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Asal Sekolah
        <span class="autocomplete-sekolah">
            <input
                type="text"
                name="asal_sekolah"
                value="{{ old('asal_sekolah', $prospek->asal_sekolah) }}"
                autocomplete="off"
                placeholder="Contoh: SMAN 1 Bandung atau SMAS Al Azhar 1"
                data-input-sekolah
            >
            <span class="panel-saran-sekolah" data-panel-sekolah></span>
        </span>
        <script type="application/json" data-data-sekolah>@json($sekolah)</script>
        <small class="petunjuk">Untuk SMA gunakan SMAN jika negeri dan SMAS jika swasta. Contoh: SMAN 1 Bandung, SMAS Al Azhar 1.</small>
        @error('asal_sekolah') <small class="error">{{ $message }}</small> @enderror
    </label>
    @php($nilaiJenjang = old('jenjang', $prospek->jenjang ?: (in_array($prospek->kelas, $jenjang, true) ? $prospek->kelas : null)))
    @php($nilaiKelas = old('kelas', in_array($prospek->kelas, $jenjang, true) ? null : $prospek->kelas))
    <label>
        Jenjang
        <select name="jenjang" data-jenjang-leads>
            <option value="">Pilih jenjang</option>
            @foreach ($jenjang as $item)
                <option value="{{ $item }}" @selected($nilaiJenjang === $item)>{{ $item }}</option>
            @endforeach
        </select>
        <small class="petunjuk">Pilih salah satu: SD, SMP, SMA, atau Gapyear.</small>
        @error('jenjang') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Kelas
        <select name="kelas" data-kelas-leads data-kelas-per-jenjang='@json($kelasPerJenjang)'>
            <option value="">Pilih kelas</option>
            @foreach ($kelasPerJenjang as $jenjangItem => $kelasItems)
                @foreach ($kelasItems as $kelasItem)
                    <option value="{{ $kelasItem }}" data-jenjang="{{ $jenjangItem }}" @selected($nilaiKelas === $kelasItem)>{{ $kelasItem }}</option>
                @endforeach
            @endforeach
        </select>
        <small class="petunjuk">SD: 1-6, SMP: 7-9, SMA: X-XII. Gapyear dikosongkan.</small>
        @error('kelas') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Kota Asal
        <input type="text" name="kota_asal" value="{{ old('kota_asal', $prospek->kota_asal) }}">
        @error('kota_asal') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Program
        <select name="program">
            <option value="">Pilih program</option>
            @foreach ($program as $item)
                <option value="{{ $item }}" @selected(old('program', $prospek->program) === $item)>{{ $item }}</option>
            @endforeach
            @if ($prospek->program && ! in_array($prospek->program, $program, true))
                <option value="{{ $prospek->program }}" selected>{{ $prospek->program }}</option>
            @endif
        </select>
        @error('program') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Status
        <select name="status" required data-status-leads>
            @foreach ($status as $item)
                <option value="{{ $item }}" @selected(old('status', $prospek->status) === $item)>{{ $item }}</option>
            @endforeach
        </select>
        <small class="petunjuk">Jika status diubah menjadi Daftar, data akan otomatis keluar dari Data Leads dan masuk ke Data Siswa.</small>
        @error('status') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Cabang
        <select name="cabang" @disabled(! auth()->user()->bisaMengubahSemuaLeads())>
            <option value="">Pilih cabang</option>
            @foreach ($cabang as $item)
                <option value="{{ $item }}" @selected(old('cabang', $prospek->cabang ?: auth()->user()->cabang) === $item)>{{ $item }}</option>
            @endforeach
        </select>
        @if (! auth()->user()->bisaMengubahSemuaLeads())
            <input type="hidden" name="cabang" value="{{ auth()->user()->cabang }}">
        @endif
        @error('cabang') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Diserahkan ke
        <select name="diserahkan_ke">
            <option value="">Pilih admin</option>
            @foreach ($adminCabang as $item)
                <option value="{{ $item }}" @selected(old('diserahkan_ke', $prospek->diserahkan_ke) === $item)>{{ $item }}</option>
            @endforeach
        </select>
        @error('diserahkan_ke') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Sumber
        <select name="sumber">
            <option value="">Pilih sumber</option>
            @foreach ($sumber as $item)
                <option value="{{ $item }}" @selected(old('sumber', $prospek->sumber) === $item)>{{ $item }}</option>
            @endforeach
        </select>
        @error('sumber') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Tgl Masuk
        <input type="date" name="tgl_masuk" value="{{ old('tgl_masuk', $prospek->tgl_masuk?->format('Y-m-d')) }}">
        @error('tgl_masuk') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label class="penuh">
        Keterangan
        <textarea name="keterangan" rows="4">{{ old('keterangan', $prospek->keterangan) }}</textarea>
        @error('keterangan') <small class="error">{{ $message }}</small> @enderror
    </label>

    <div class="panel-form-closing penuh" data-section-closing>
        <div class="judul-panel">
            <div>
                <h2>Data Siswa / Closing</h2>
                <span>Isi saat leads sudah daftar. Data ini tampil di menu Data Siswa.</span>
            </div>
        </div>
        <div class="grid-form">
            <label>
                Tgl Daftar
                <input type="date" name="tanggal_daftar" value="{{ old('tanggal_daftar', $prospek->tanggal_daftar?->format('Y-m-d')) }}">
                @error('tanggal_daftar') <small class="error">{{ $message }}</small> @enderror
            </label>
            <label>
                Program Final
                <select name="program_final">
                    <option value="">Ikuti program leads</option>
                    @foreach ($program as $item)
                        <option value="{{ $item }}" @selected(old('program_final', $prospek->program_final) === $item)>{{ $item }}</option>
                    @endforeach
                    @if ($prospek->program_final && ! in_array($prospek->program_final, $program, true))
                        <option value="{{ $prospek->program_final }}" selected>{{ $prospek->program_final }}</option>
                    @endif
                </select>
                @error('program_final') <small class="error">{{ $message }}</small> @enderror
            </label>
            <label>
                Nominal Pembayaran
                <input type="number" name="nominal_pembayaran" min="0" step="1000" value="{{ old('nominal_pembayaran', $prospek->nominal_pembayaran) }}">
                @error('nominal_pembayaran') <small class="error">{{ $message }}</small> @enderror
            </label>
            <label>
                Status Pembayaran
                <select name="status_pembayaran">
                    <option value="">Pilih status pembayaran</option>
                    @foreach ($statusPembayaran as $item)
                        <option value="{{ $item }}" @selected(old('status_pembayaran', $prospek->status_pembayaran) === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                @error('status_pembayaran') <small class="error">{{ $message }}</small> @enderror
            </label>
            <label>
                Kelas / Angkatan
                <input type="text" name="kelas_angkatan" value="{{ old('kelas_angkatan', $prospek->kelas_angkatan) }}">
                @error('kelas_angkatan') <small class="error">{{ $message }}</small> @enderror
            </label>
            <label class="penuh">
                Catatan Administrasi
                <textarea name="catatan_administrasi" rows="3">{{ old('catatan_administrasi', $prospek->catatan_administrasi) }}</textarea>
                @error('catatan_administrasi') <small class="error">{{ $message }}</small> @enderror
            </label>
        </div>
    </div>
</div>

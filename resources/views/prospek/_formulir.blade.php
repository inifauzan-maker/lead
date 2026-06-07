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
                data-input-sekolah
            >
            <span class="panel-saran-sekolah" data-panel-sekolah></span>
        </span>
        <script type="application/json" data-data-sekolah>@json($sekolah)</script>
        <small class="petunjuk">Pilih dari saran sekolah, atau lanjut ketik manual jika data tidak ditemukan.</small>
        @error('asal_sekolah') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Kelas
        <input type="text" name="kelas" value="{{ old('kelas', $prospek->kelas) }}">
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
        <select name="status" required>
            @foreach ($status as $item)
                <option value="{{ $item }}" @selected(old('status', $prospek->status) === $item)>{{ $item }}</option>
            @endforeach
        </select>
        @error('status') <small class="error">{{ $message }}</small> @enderror
    </label>
    <label>
        Cabang
        <select name="cabang" @disabled(! auth()->user()->aksesSemuaCabang())>
            <option value="">Pilih cabang</option>
            @foreach ($cabang as $item)
                <option value="{{ $item }}" @selected(old('cabang', $prospek->cabang ?: auth()->user()->cabang) === $item)>{{ $item }}</option>
            @endforeach
        </select>
        @if (! auth()->user()->aksesSemuaCabang())
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
</div>

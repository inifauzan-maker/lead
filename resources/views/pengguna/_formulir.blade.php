<div class="grid-pengguna">
    <input type="text" name="name" value="{{ old('name', $userItem->name) }}" placeholder="Nama" required>
    <input type="email" name="email" value="{{ old('email', $userItem->email) }}" placeholder="Email" required>
    <select name="role" required>
        @foreach ($role as $item)
            <option value="{{ $item }}" @selected(old('role', $userItem->role) === $item)>{{ ucfirst($item) }}</option>
        @endforeach
    </select>
    <select name="cabang">
        <option value="">Semua cabang</option>
        @foreach ($cabang as $item)
            <option value="{{ $item }}" @selected(old('cabang', $userItem->cabang) === $item)>{{ $item }}</option>
        @endforeach
    </select>
    <input type="password" name="password" placeholder="{{ $userItem->exists ? 'Password baru opsional' : 'Password' }}" @required(! $userItem->exists)>
    <label class="cek">
        <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $userItem->aktif ?? true))>
        Aktif
    </label>
</div>

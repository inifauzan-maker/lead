<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public const ROLE = ['superadmin', 'admin', 'staff', 'direksi'];

    public function index(): View
    {
        $pengguna = User::latest()->paginate(10);

        return view('pengguna.index', [
            'pengguna' => $pengguna,
            'penggunaBaru' => new User(['aktif' => true]),
            'role' => self::ROLE,
            'cabang' => $this->daftarCabang(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validasi($request));

        return redirect()->route('pengguna.index')->with('berhasil', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $data = $this->validasi($request, $pengguna);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $pengguna->update($data);

        return redirect()->route('pengguna.index')->with('berhasil', 'Pengguna berhasil diperbarui.');
    }

    private function validasi(Request $request, ?User $pengguna = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($pengguna?->id)],
            'password' => [$pengguna ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(self::ROLE)],
            'cabang' => ['nullable', Rule::in($this->daftarCabang())],
            'aktif' => ['nullable', 'boolean'],
        ]);

        if (in_array($data['role'], ['admin', 'staff'], true) && blank($data['cabang'] ?? null)) {
            throw ValidationException::withMessages([
                'cabang' => 'Cabang wajib diisi untuk admin dan staff.',
            ]);
        }

        if (in_array($data['role'], ['superadmin', 'direksi'], true)) {
            $data['cabang'] = null;
        }

        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }

    private function daftarCabang(): array
    {
        $items = Cabang::query()->where('aktif', true)->orderBy('nama')->pluck('nama')->all();

        return $items ?: ['Bandung', 'Jaksel', 'Jakpus'];
    }
}

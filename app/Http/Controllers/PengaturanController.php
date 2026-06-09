<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\ProgramLead;
use App\Models\SumberLead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    private const ROLE = ['superadmin', 'admin', 'leader', 'staff', 'direksi'];

    public function index(): View
    {
        return view('pengaturan.index', [
            'cabang' => Cabang::orderBy('nama')->get(),
            'sumber' => SumberLead::orderBy('nama')->get(),
            'program' => ProgramLead::orderBy('nama')->get(),
            'pengguna' => User::orderBy('name')->paginate(10),
            'role' => self::ROLE,
            'daftarCabang' => $this->daftarCabang(),
        ]);
    }

    public function storeCabang(Request $request): RedirectResponse
    {
        Cabang::create($this->validasiMaster($request, 'cabang'));

        return back()->with('berhasil', 'Cabang berhasil ditambahkan.');
    }

    public function updateCabang(Request $request, Cabang $cabang): RedirectResponse
    {
        $cabang->update($this->validasiMaster($request, 'cabang', $cabang->id));

        return back()->with('berhasil', 'Cabang berhasil diperbarui.');
    }

    public function destroyCabang(Cabang $cabang): RedirectResponse
    {
        $cabang->delete();

        return back()->with('berhasil', 'Cabang berhasil dihapus.');
    }

    public function storeSumber(Request $request): RedirectResponse
    {
        SumberLead::create($this->validasiMaster($request, 'sumber_leads'));

        return back()->with('berhasil', 'Sumber leads berhasil ditambahkan.');
    }

    public function updateSumber(Request $request, SumberLead $sumber): RedirectResponse
    {
        $sumber->update($this->validasiMaster($request, 'sumber_leads', $sumber->id));

        return back()->with('berhasil', 'Sumber leads berhasil diperbarui.');
    }

    public function destroySumber(SumberLead $sumber): RedirectResponse
    {
        $sumber->delete();

        return back()->with('berhasil', 'Sumber leads berhasil dihapus.');
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        ProgramLead::create($this->validasiMaster($request, 'program_leads'));

        return back()->with('berhasil', 'Program berhasil ditambahkan.');
    }

    public function updateProgram(Request $request, ProgramLead $program): RedirectResponse
    {
        $program->update($this->validasiMaster($request, 'program_leads', $program->id));

        return back()->with('berhasil', 'Program berhasil diperbarui.');
    }

    public function destroyProgram(ProgramLead $program): RedirectResponse
    {
        $program->delete();

        return back()->with('berhasil', 'Program berhasil dihapus.');
    }

    public function updateRoleUser(Request $request, User $user): RedirectResponse
    {
        $user->update($this->validasiUser($request, $user, hanyaRole: true));

        return back()->with('berhasil', 'Role user berhasil diperbarui.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        User::create($this->validasiUser($request));

        return back()->with('berhasil', 'User berhasil ditambahkan.');
    }

    private function validasiMaster(Request $request, string $table, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique($table, 'nama')->ignore($ignoreId),
            ],
            'aktif' => ['nullable', 'boolean'],
        ]) + [
            'aktif' => $request->boolean('aktif'),
        ];
    }

    private function daftarCabang(): array
    {
        return Cabang::query()
            ->where('aktif', true)
            ->orderBy('nama')
            ->pluck('nama')
            ->all();
    }

    private function validasiUser(Request $request, ?User $user = null, bool $hanyaRole = false): array
    {
        $aturan = [
            'role' => ['required', Rule::in(self::ROLE)],
            'cabang' => ['nullable', Rule::in($this->daftarCabang())],
            'aktif' => ['nullable', 'boolean'],
        ];

        if (! $hanyaRole) {
            $aturan = [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
                'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            ] + $aturan;
        }

        $data = $request->validate($aturan);

        if (in_array($data['role'], ['admin', 'leader', 'staff'], true) && blank($data['cabang'] ?? null)) {
            throw ValidationException::withMessages([
                'cabang' => 'Cabang wajib diisi untuk admin, leader, dan staff.',
            ]);
        }

        if (in_array($data['role'], ['superadmin', 'direksi'], true)) {
            $data['cabang'] = null;
        }

        $data['aktif'] = $request->boolean('aktif');

        if ($user && blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}

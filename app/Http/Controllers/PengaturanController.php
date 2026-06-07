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
        $data = $request->validate([
            'role' => ['required', Rule::in(self::ROLE)],
            'cabang' => ['nullable', Rule::in($this->daftarCabang())],
            'aktif' => ['nullable', 'boolean'],
        ]);

        if (in_array($data['role'], ['admin', 'leader', 'staff'], true) && blank($data['cabang'] ?? null)) {
            throw ValidationException::withMessages([
                'cabang' => 'Cabang wajib diisi untuk admin, leader, dan staff.',
            ]);
        }

        if (in_array($data['role'], ['superadmin', 'direksi'], true)) {
            $data['cabang'] = null;
        }

        $data['aktif'] = $request->boolean('aktif');
        $user->update($data);

        return back()->with('berhasil', 'Role user berhasil diperbarui.');
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
}

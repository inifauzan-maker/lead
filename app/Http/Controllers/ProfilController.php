<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Prospek;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->queryAkses($request);
        $user = $request->user();
        $anggotaTim = User::query()
            ->where('aktif', true)
            ->when(! $user->aksesSemuaCabang(), fn ($query) => $query->where('cabang', $user->cabang))
            ->count();
        $totalLeads = (clone $query)->count();
        $leadsBaru = (clone $query)->where('status', 'Baru')->count();
        $followUp = (clone $query)->whereIn('status', ['Dihubungi', 'Follow Up'])->count();
        $closing = (clone $query)->where('status', 'Daftar')->count();
        $queryTugas = $this->queryTugas($request);
        $totalTugas = (clone $queryTugas)->whereIn('status', ['Baru', 'Proses'])->count();
        $totalKelas = Course::query()->where('aktif', true)->count();
        $kelasSelesai = CourseProgress::query()
            ->where('user_id', $user->id)
            ->where('status', 'Selesai')
            ->count();
        $kelasBerjalan = CourseProgress::query()
            ->where('user_id', $user->id)
            ->where('status', 'Berjalan')
            ->count();
        $sosial = collect([
            'Facebook' => $user->facebook,
            'Instagram' => $user->instagram,
            'TikTok' => $user->tiktok,
            'Blog' => $user->blog,
            'YouTube' => $user->youtube,
        ])->filter();
        $aktivitas = (clone $query)
            ->latest('updated_at')
            ->limit(4)
            ->get()
            ->map(function ($prospek) {
                $judul = match ($prospek->status) {
                    'Daftar' => 'Closing "'.$prospek->nama.'"',
                    'Dihubungi', 'Follow Up' => 'Follow up "'.$prospek->nama.'"',
                    default => 'Leads "'.$prospek->nama.'" diperbarui',
                };

                return [
                    'judul' => $judul,
                    'waktu' => $prospek->updated_at?->diffForHumans(),
                    'warna' => match ($prospek->status) {
                        'Daftar' => 'hijau',
                        'Dihubungi', 'Follow Up' => 'biru',
                        default => 'ungu',
                    },
                ];
            });
        $pencapaian = collect([
            [
                'judul' => 'Profil Aktif',
                'deskripsi' => 'Akun aktif dan siap dipakai untuk operasional harian.',
                'ikon' => 'PA',
            ],
            [
                'judul' => 'Jejaring Sosial',
                'deskripsi' => $sosial->count().' akun sosial sudah terhubung ke profil user.',
                'ikon' => 'JS',
            ],
            [
                'judul' => 'Closing Tracker',
                'deskripsi' => $closing.' data closing tercatat pada akses akun ini.',
                'ikon' => 'CT',
            ],
        ]);

        return view('profil.index', [
            'ringkasanProfil' => [
                'tim' => $anggotaTim,
                'tugas' => $totalTugas,
                'laporan' => $totalLeads,
                'pembelajaran' => $totalKelas,
                'closing' => $closing,
                'baru' => $leadsBaru,
                'proses' => $followUp,
                'sosial' => $sosial->count(),
                'kelasSelesai' => $kelasSelesai,
                'kelasBerjalan' => $kelasBerjalan,
                'tugasHariIni' => (clone $queryTugas)->whereDate('tenggat', today())->count(),
                'tugasMingguIni' => (clone $queryTugas)->whereBetween('tenggat', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'tugasTertunda' => (clone $queryTugas)->whereDate('tenggat', '<', today())->where('status', '!=', 'Selesai')->count(),
                'rasioClosing' => $totalLeads > 0 ? round(($closing / $totalLeads) * 100, 1) : 0,
            ],
            'sosialTerhubung' => $sosial,
            'aktivitas' => $aktivitas,
            'pencapaian' => $pencapaian,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'blog' => ['nullable', 'url', 'max:255'],
            'youtube' => ['nullable', 'url', 'max:255'],
            'password_lama' => ['required_with:password', 'nullable', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'password_lama.current_password' => 'Password lama tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->facebook = $data['facebook'] ?? null;
        $user->instagram = $data['instagram'] ?? null;
        $user->tiktok = $data['tiktok'] ?? null;
        $user->blog = $data['blog'] ?? null;
        $user->youtube = $data['youtube'] ?? null;

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('berhasil', 'Profil berhasil diperbarui.');
    }

    private function queryAkses(Request $request)
    {
        $user = $request->user();
        $query = Prospek::query();

        if ($user->aksesSemuaCabang()) {
            return $query;
        }

        if ($user->role === 'staff') {
            return $query->where('user_id', $user->id);
        }

        return $query->where('cabang', $user->cabang);
    }

    private function queryTugas(Request $request)
    {
        $user = $request->user();
        $query = Task::query();

        if ($user->aksesSemuaCabang()) {
            return $query;
        }

        if ($user->role === 'staff') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('cabang', $user->cabang);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Prospek;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModulController extends Controller
{
    public function tim(Request $request): View
    {
        $anggota = User::query()
            ->where('aktif', true)
            ->when(! $request->user()->aksesSemuaCabang(), fn ($query) => $query->where('cabang', $request->user()->cabang))
            ->orderByRaw("CASE role WHEN 'superadmin' THEN 1 WHEN 'admin' THEN 2 WHEN 'leader' THEN 3 WHEN 'staff' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->get();

        $jumlahLeadsPerUser = Prospek::query()
            ->selectRaw('user_id, COUNT(*) as total')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $jumlahClosingPerUser = Prospek::query()
            ->selectRaw('user_id, COUNT(*) as total')
            ->where('status', 'Daftar')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return view('tim.index', [
            'anggota' => $anggota,
            'jumlahRole' => $anggota->countBy('role'),
            'jumlahCabang' => $anggota->countBy(fn ($user) => $user->cabang ?: 'Semua Cabang'),
            'jumlahLeadsPerUser' => $jumlahLeadsPerUser,
            'jumlahClosingPerUser' => $jumlahClosingPerUser,
        ]);
    }

    public function tugas(Request $request): View
    {
        $query = $this->queryAkses($request);
        $kolom = [
            'Leads Baru' => ['status' => ['Baru'], 'warna' => 'merah'],
            'Follow Up' => ['status' => ['Dihubungi', 'Follow Up'], 'warna' => 'kuning'],
            'Closing' => ['status' => ['Daftar'], 'warna' => 'hijau'],
            'Arsip' => ['status' => ['Tidak Tertarik'], 'warna' => 'abu'],
        ];

        $tugas = collect($kolom)->map(function ($konfigurasi, $judul) use ($query) {
            return [
                'judul' => $judul,
                'warna' => $konfigurasi['warna'],
                'items' => (clone $query)
                    ->whereIn('status', $konfigurasi['status'])
                    ->latest()
                    ->limit(6)
                    ->get(),
            ];
        });

        return view('tugas.index', ['tugas' => $tugas]);
    }

    public function laporan(Request $request): View
    {
        $query = $this->queryAkses($request);
        $total = (clone $query)->count();
        $closing = (clone $query)->where('status', 'Daftar')->count();
        $followUp = (clone $query)->whereIn('status', ['Dihubungi', 'Follow Up'])->count();
        $baru = (clone $query)->where('status', 'Baru')->count();
        $perCabang = (clone $query)
            ->selectRaw('COALESCE(cabang, "Tanpa Cabang") as label, COUNT(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();
        $perStatus = (clone $query)
            ->selectRaw('status as label, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return view('laporan.index', [
            'total' => $total,
            'closing' => $closing,
            'followUp' => $followUp,
            'baru' => $baru,
            'rasioClosing' => $total > 0 ? round(($closing / $total) * 100, 1) : 0,
            'perCabang' => $perCabang,
            'perStatus' => $perStatus,
        ]);
    }

    public function pembelajaran(): View
    {
        $kelas = collect([
            [
                'judul' => 'Dasar Pengelolaan Leads',
                'modul' => 6,
                'durasi' => '45 menit',
                'progress' => 80,
                'level' => 'Wajib',
            ],
            [
                'judul' => 'Strategi Follow Up Efektif',
                'modul' => 8,
                'durasi' => '1 jam 20 menit',
                'progress' => 45,
                'level' => 'Sales',
            ],
            [
                'judul' => 'Membaca Laporan Cabang',
                'modul' => 5,
                'durasi' => '35 menit',
                'progress' => 30,
                'level' => 'Leader',
            ],
            [
                'judul' => 'Etika Komunikasi Orang Tua',
                'modul' => 7,
                'durasi' => '55 menit',
                'progress' => 15,
                'level' => 'Staff',
            ],
        ]);

        return view('pembelajaran.index', ['kelas' => $kelas]);
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
}

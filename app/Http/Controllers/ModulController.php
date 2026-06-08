<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Prospek;
use App\Models\SistemNotification;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $query = $this->queryTugas($request)->with(['prospek', 'penanggungJawab', 'komentar.user']);
        $kolom = [
            'Baru' => 'merah',
            'Proses' => 'kuning',
            'Selesai' => 'hijau',
            'Arsip' => 'abu',
        ];

        $tugas = collect($kolom)->map(function ($warna, $judul) use ($query) {
            return [
                'judul' => $judul,
                'warna' => $warna,
                'items' => (clone $query)
                    ->where('status', $judul)
                    ->orderByRaw("CASE prioritas WHEN 'Tinggi' THEN 1 WHEN 'Normal' THEN 2 ELSE 3 END")
                    ->orderByRaw('tenggat IS NULL, tenggat ASC')
                    ->limit(6)
                    ->get(),
            ];
        });

        return view('tugas.index', [
            'tugas' => $tugas,
            'staff' => $this->staffTersedia($request),
            'prospek' => $this->queryAkses($request)->orderBy('nama')->limit(80)->get(),
            'statusTugas' => array_keys($kolom),
            'prioritasTugas' => ['Rendah', 'Normal', 'Tinggi'],
        ]);
    }

    public function storeTugas(Request $request): RedirectResponse
    {
        $this->pastikanBolehUbah($request);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['Baru', 'Proses', 'Selesai', 'Arsip'])],
            'prioritas' => ['required', Rule::in(['Rendah', 'Normal', 'Tinggi'])],
            'tenggat' => ['nullable', 'date'],
            'prospek_id' => ['nullable', 'exists:prospek,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'cabang' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        if (! $user->aksesSemuaCabang()) {
            $data['cabang'] = $user->cabang;
        }

        if ($user->role === 'staff') {
            $data['assigned_to'] = $user->id;
        }

        if (filled($data['prospek_id'] ?? null)) {
            $prospek = Prospek::findOrFail($data['prospek_id']);
            $this->pastikanBolehAksesProspek($request, $prospek);
            $data['cabang'] = $data['cabang'] ?: $prospek->cabang;
        }

        $data['created_by'] = $user->id;
        Task::create($data);

        return back()->with('berhasil', 'Tugas berhasil ditambahkan.');
    }

    public function updateTugas(Request $request, Task $task): RedirectResponse
    {
        $this->pastikanBolehUbah($request);
        $this->pastikanBolehAksesTugas($request, $task);

        $data = $request->validate([
            'status' => ['required', Rule::in(['Baru', 'Proses', 'Selesai', 'Arsip'])],
            'prioritas' => ['required', Rule::in(['Rendah', 'Normal', 'Tinggi'])],
            'tenggat' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        if ($request->user()->role === 'staff') {
            unset($data['assigned_to']);
        }

        $task->update($data);

        return back()->with('berhasil', 'Tugas berhasil diperbarui.');
    }

    public function storeKomentarTugas(Request $request, Task $task): RedirectResponse
    {
        $this->pastikanBolehUbah($request);
        $this->pastikanBolehAksesTugas($request, $task);

        $data = $request->validate([
            'komentar' => ['required', 'string'],
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'komentar' => $data['komentar'],
        ]);

        return back()->with('berhasil', 'Komentar tugas berhasil ditambahkan.');
    }

    public function destroyTugas(Request $request, Task $task): RedirectResponse
    {
        $this->pastikanBolehUbah($request);
        $this->pastikanBolehAksesTugas($request, $task);
        $task->delete();

        return back()->with('berhasil', 'Tugas berhasil dihapus.');
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

    public function pembelajaran(Request $request): View
    {
        $progress = CourseProgress::query()
            ->where('user_id', $request->user()->id)
            ->pluck('progress_persen', 'course_id');
        $kelas = Course::query()
            ->where('aktif', true)
            ->withCount(['lessons' => fn ($query) => $query->where('aktif', true)])
            ->orderBy('urutan')
            ->orderBy('judul')
            ->get()
            ->map(function ($course) use ($progress) {
                return [
                    'id' => $course->id,
                    'judul' => $course->judul,
                    'modul' => $course->lessons_count,
                    'durasi' => $this->formatDurasi($course->durasi_menit),
                    'progress' => (int) ($progress[$course->id] ?? 0),
                    'level' => $course->level,
                ];
            });

        return view('pembelajaran.index', ['kelas' => $kelas]);
    }

    public function detailPembelajaran(Request $request, Course $course): View
    {
        abort_unless($course->aktif, 404);

        $course->load(['lessons' => fn ($query) => $query->where('aktif', true)->orderBy('urutan')]);
        $progress = CourseProgress::query()
            ->where('course_id', $course->id)
            ->where('user_id', $request->user()->id)
            ->first();

        return view('pembelajaran.detail', [
            'course' => $course,
            'progress' => $progress,
            'durasi' => $this->formatDurasi($course->durasi_menit),
        ]);
    }

    public function updateProgressPembelajaran(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->aktif, 404);

        $data = $request->validate([
            'progress_persen' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $status = match (true) {
            (int) $data['progress_persen'] >= 100 => 'Selesai',
            (int) $data['progress_persen'] > 0 => 'Berjalan',
            default => 'Belum Mulai',
        };

        CourseProgress::updateOrCreate(
            [
                'course_id' => $course->id,
                'course_lesson_id' => null,
                'user_id' => $request->user()->id,
            ],
            [
                'status' => $status,
                'progress_persen' => $data['progress_persen'],
                'completed_at' => $status === 'Selesai' ? now() : null,
            ],
        );

        return back()->with('berhasil', 'Progress pembelajaran berhasil diperbarui.');
    }

    public function notifikasi(Request $request): View
    {
        $items = SistemNotification::query()
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $request->user()->id))
            ->latest()
            ->paginate(12);

        return view('notifikasi.index', ['items' => $items]);
    }

    public function bacaNotifikasi(Request $request, SistemNotification $notifikasi): RedirectResponse
    {
        abort_if($notifikasi->user_id && (int) $notifikasi->user_id !== (int) $request->user()->id, 403);
        $notifikasi->update(['dibaca_pada' => now()]);

        return back()->with('berhasil', 'Notifikasi ditandai sudah dibaca.');
    }

    public function bacaSemuaNotifikasi(Request $request): RedirectResponse
    {
        SistemNotification::query()
            ->whereNull('dibaca_pada')
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $request->user()->id))
            ->update(['dibaca_pada' => now()]);

        return back()->with('berhasil', 'Semua notifikasi ditandai sudah dibaca.');
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
            return $query->where('assigned_to', $user->id);
        }

        return $query->where('cabang', $user->cabang);
    }

    private function staffTersedia(Request $request)
    {
        return User::query()
            ->where('aktif', true)
            ->whereIn('role', ['leader', 'staff'])
            ->when(! $request->user()->aksesSemuaCabang(), fn ($query) => $query->where('cabang', $request->user()->cabang))
            ->orderBy('name')
            ->get();
    }

    private function pastikanBolehUbah(Request $request): void
    {
        abort_if($request->user()->role === 'direksi', 403);
    }

    private function pastikanBolehAksesTugas(Request $request, Task $task): void
    {
        $user = $request->user();

        if ($user->aksesSemuaCabang()) {
            return;
        }

        if ($user->role === 'staff') {
            abort_unless((int) $task->assigned_to === (int) $user->id, 403);

            return;
        }

        abort_unless($task->cabang === $user->cabang, 403);
    }

    private function pastikanBolehAksesProspek(Request $request, Prospek $prospek): void
    {
        $user = $request->user();

        if ($user->aksesSemuaCabang()) {
            return;
        }

        if ($user->role === 'staff') {
            abort_unless((int) $prospek->user_id === (int) $user->id, 403);

            return;
        }

        abort_unless($prospek->cabang === $user->cabang, 403);
    }

    private function formatDurasi(int $menit): string
    {
        if ($menit < 60) {
            return $menit.' menit';
        }

        $jam = intdiv($menit, 60);
        $sisaMenit = $menit % 60;

        return $sisaMenit > 0 ? "{$jam} jam {$sisaMenit} menit" : "{$jam} jam";
    }
}

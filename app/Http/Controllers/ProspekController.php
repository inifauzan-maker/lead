<?php

namespace App\Http\Controllers;

use App\Models\Prospek;
use App\Models\Cabang;
use App\Models\FollowUp;
use App\Models\ProgramLead;
use App\Models\SumberLead;
use App\Models\SistemNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProspekController extends Controller
{
    private const STATUS = ['Baru', 'Dihubungi', 'Follow Up', 'Daftar', 'Tidak Tertarik'];

    private const METODE_FOLLOW_UP = ['WhatsApp', 'Telepon', 'Kunjungan', 'Email', 'Lainnya'];

    private const HASIL_FOLLOW_UP = [
        'Tidak tersambung',
        'Tersambung',
        'Berminat',
        'Minta dihubungi ulang',
        'Closing',
        'Tidak tertarik',
        'Nomor tidak aktif',
    ];

    private const PRIORITAS_FOLLOW_UP = ['Rendah', 'Normal', 'Tinggi'];

    private const KOLOM_IMPORT = [
        'nama',
        'asal_sekolah',
        'kelas',
        'kota_asal',
        'no_wa',
        'program',
        'status',
        'cabang',
        'diserahkan_ke',
        'sumber',
        'keterangan',
        'tgl_masuk',
    ];

    public function dashboard(Request $request): View
    {
        $periode = $this->periodeDashboard($request);
        $query = $this->queryAksesDashboard($request);
        $total = (clone $query)->count();
        $baru = (clone $query)->where('status', 'Baru')->count();
        $followUp = (clone $query)->whereIn('status', ['Dihubungi', 'Follow Up'])->count();
        $daftar = (clone $query)->where('status', 'Daftar')->count();
        $perSumber = (clone $query)->selectRaw('COALESCE(sumber, "Tanpa Sumber") as sumber, COUNT(*) as total')
            ->groupBy('sumber')
            ->orderByDesc('total')
            ->limit(6)
            ->get();
        $perProgram = (clone $query)->selectRaw('COALESCE(program, "Tanpa Program") as program, COUNT(*) as total')
            ->groupBy('program')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
        $perSekolah = (clone $query)->selectRaw('COALESCE(asal_sekolah, "Tanpa Sekolah") as asal_sekolah, COUNT(*) as total')
            ->groupBy('asal_sekolah')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $perCabang = (clone $query)->selectRaw('COALESCE(cabang, "Tanpa Cabang") as cabang, COUNT(*) as total')
            ->groupBy('cabang')
            ->orderByDesc('total')
            ->get();
        $grafikHarian = $this->grafikLeadsHarian((clone $query), $periode);
        $staffFilter = $this->staffTersedia($request->string('cabang')->toString() ?: null);

        return view('dashboard', [
            'total' => $total,
            'baru' => $baru,
            'followUp' => $followUp,
            'daftar' => $daftar,
            'perSumber' => $perSumber,
            'perProgram' => $perProgram,
            'perSekolah' => $perSekolah,
            'perCabang' => $perCabang,
            'grafikHarian' => $grafikHarian,
            'cabang' => $this->daftarCabang(),
            'adminCabang' => $this->daftarAdminCabang(),
            'staffFilter' => $staffFilter,
            'bulanFilter' => $periode['bulan'],
            'tahunFilter' => $periode['tahun'],
            'daftarBulan' => $this->daftarBulan(),
            'daftarTahun' => range((int) now()->year, (int) now()->year - 5),
        ]);
    }

    public function index(Request $request): View
    {
        $prospek = $this->queryDaftar($request)
            ->paginate(10)
            ->withQueryString();

        return view('prospek.index', [
            'prospek' => $prospek,
            'sumber' => $this->daftarSumber(),
            'status' => self::STATUS,
            'cabang' => $this->daftarCabang(),
        ]);
    }

    public function followUp(Request $request): View
    {
        $periode = $this->periodeDashboard($request);
        $query = $this->queryAksesFollowUp($request);
        $mulai = Carbon::create($periode['tahun'], $periode['bulan'], 1)->startOfMonth();
        $akhir = $mulai->copy()->endOfMonth();

        $prospek = (clone $query)
            ->with(['followUpTerakhir.user', 'penanggungJawab'])
            ->withCount('followUps')
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();
        $queryRiwayat = $this->queryRiwayatFollowUp($request);
        $kalender = $this->kalenderFollowUp((clone $queryRiwayat), $mulai, $akhir);
        $aktivitasTerbaru = (clone $queryRiwayat)
            ->with(['prospek', 'user'])
            ->latest('tanggal_follow_up')
            ->limit(8)
            ->get();
        $calonProspek = $this->queryAksesUbahDashboard($request)
            ->withCount('followUps')
            ->whereNotIn('status', ['Daftar', 'Tidak Tertarik'])
            ->orderBy('nama')
            ->limit(80)
            ->get();
        $hariIni = now()->toDateString();

        return view('follow-up.index', [
            'prospek' => $prospek,
            'kalender' => $kalender,
            'aktivitasTerbaru' => $aktivitasTerbaru,
            'calonProspek' => $calonProspek,
            'metodeFollowUp' => self::METODE_FOLLOW_UP,
            'hasilFollowUp' => self::HASIL_FOLLOW_UP,
            'prioritasFollowUp' => self::PRIORITAS_FOLLOW_UP,
            'totalAktivitas' => (clone $queryRiwayat)->count(),
            'butuhFollowUpHariIni' => (clone $queryRiwayat)
                ->whereDate('tanggal_follow_up_berikutnya', $hariIni)
                ->distinct('prospek_id')
                ->count('prospek_id'),
            'followUpTerlambat' => (clone $queryRiwayat)
                ->whereDate('tanggal_follow_up_berikutnya', '<', $hariIni)
                ->whereNotIn('hasil', ['Closing', 'Tidak tertarik'])
                ->distinct('prospek_id')
                ->count('prospek_id'),
            'closingFollowUp' => (clone $queryRiwayat)->where('hasil', 'Closing')->count(),
            'cabang' => $this->daftarCabang(),
            'adminCabang' => $this->daftarAdminCabang(),
            'staffFilter' => $this->staffTersedia($request->string('cabang')->toString() ?: null),
            'bulanFilter' => $periode['bulan'],
            'tahunFilter' => $periode['tahun'],
            'daftarBulan' => $this->daftarBulan(),
            'daftarTahun' => range((int) now()->year, (int) now()->year - 5),
        ]);
    }

    public function storeFollowUp(Request $request): RedirectResponse
    {
        $this->pastikanBolehUbah();

        $data = $request->validate([
            'prospek_id' => ['required', 'exists:prospek,id'],
            'tanggal_follow_up' => ['required', 'date'],
            'metode' => ['required', Rule::in(self::METODE_FOLLOW_UP)],
            'hasil' => ['required', Rule::in(self::HASIL_FOLLOW_UP)],
            'catatan' => ['nullable', 'string'],
            'tindak_lanjut' => ['nullable', 'string'],
            'tanggal_follow_up_berikutnya' => ['nullable', 'date'],
            'prioritas' => ['required', Rule::in(self::PRIORITAS_FOLLOW_UP)],
        ]);
        $prospek = Prospek::query()->findOrFail($data['prospek_id']);
        $this->pastikanBolehAkses($prospek);

        $data['user_id'] = $request->user()->id;
        FollowUp::create($data);

        $prospek->update([
            'status' => $this->statusDariHasilFollowUp($data['hasil']),
            'user_id' => $prospek->user_id ?: $request->user()->id,
        ]);
        $this->kirimNotifikasiFollowUp($prospek->fresh(), $data);

        return back()->with('berhasil', 'Aktivitas follow up berhasil dicatat.');
    }

    public function dataSiswa(Request $request): View
    {
        $prospek = $this->queryAksesDataSiswa($request)
            ->when($request->filled('cari'), function ($query) use ($request) {
                $cari = $request->string('cari');

                $query->where(function ($query) use ($cari) {
                    $query->where('nama', 'like', "%{$cari}%")
                        ->orWhere('asal_sekolah', 'like', "%{$cari}%")
                        ->orWhere('no_wa', 'like', "%{$cari}%")
                        ->orWhere('program', 'like', "%{$cari}%");
                });
            })
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('data-siswa.index', [
            'prospek' => $prospek,
            'cabang' => $this->daftarCabang(),
            'adminCabang' => $this->daftarAdminCabang(),
            'staffFilter' => $this->staffTersedia($request->string('cabang')->toString() ?: null),
        ]);
    }

    public function export(Request $request)
    {
        $namaFile = 'export-leads-'.now()->format('Ymd-His').'.csv';

        return $this->unduhCsv($this->queryDaftar($request), $namaFile);
    }

    public function aksiMassal(Request $request)
    {
        $data = $request->validate([
            'aksi' => ['required', Rule::in(['export', 'hapus'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Pilih minimal satu leads.',
            'ids.min' => 'Pilih minimal satu leads.',
        ]);

        if ($data['aksi'] === 'export') {
            $query = $this->queryAkses()
                ->whereIn('id', $data['ids']);

            return $this->unduhCsv($query->orderBy('id'), 'export-leads-terpilih-'.now()->format('Ymd-His').'.csv');
        }

        $this->pastikanBolehUbah();
        $query = $this->queryAksesUbah()
            ->whereIn('id', $data['ids']);
        $jumlah = (clone $query)->delete();

        return back()->with('berhasil', "{$jumlah} leads terpilih berhasil dihapus.");
    }

    public function contohImport()
    {
        $contoh = [
            'Budi Santoso',
            'SMAI Al Azhar 1',
            '12',
            'Jakarta Selatan',
            '081234567890',
            'SR GOLD',
            'Baru',
            $this->daftarCabang()[0] ?? 'Jaksel',
            $this->daftarAdminCabang()[0] ?? 'Admin Jaksel',
            'Instagram',
            'Contoh data import',
            now()->format('Y-m-d'),
        ];

        return response()->streamDownload(function () use ($contoh) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, self::KOLOM_IMPORT);
            fputcsv($handle, $contoh);
            fclose($handle);
        }, 'contoh-import-leads.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->pastikanBolehUbah();

        $request->validate([
            'file_import' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('file_import')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            return back()->withErrors(['file_import' => 'File import kosong atau format CSV tidak valid.']);
        }

        $header = array_map(fn ($kolom) => str($kolom)->replace("\xEF\xBB\xBF", '')->lower()->replace(' ', '_')->trim()->toString(), $header);
        $berhasil = 0;
        $dilewati = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_slice(array_pad($row, count($header), null), 0, count($header));
            $baris = array_combine($header, $row);

            if (! is_array($baris) || blank($baris['nama'] ?? null)) {
                $dilewati++;
                continue;
            }

            $noWa = $this->rapikanNomorWa($baris['no_wa'] ?? null);

            if ($noWa && Prospek::where('no_wa', $noWa)->exists()) {
                $dilewati++;
                continue;
            }

            $data = [
                'nama' => trim((string) ($baris['nama'] ?? '')),
                'asal_sekolah' => $this->nilaiImport($baris['asal_sekolah'] ?? null),
                'kelas' => $this->nilaiImport($baris['kelas'] ?? null),
                'kota_asal' => $this->nilaiImport($baris['kota_asal'] ?? null),
                'no_wa' => $noWa,
                'program' => $this->nilaiImport($baris['program'] ?? null),
                'status' => in_array($baris['status'] ?? '', self::STATUS, true) ? $baris['status'] : 'Baru',
                'cabang' => in_array($baris['cabang'] ?? '', $this->daftarCabang(), true) ? $baris['cabang'] : null,
                'diserahkan_ke' => in_array($baris['diserahkan_ke'] ?? '', $this->daftarAdminCabang(), true) ? $baris['diserahkan_ke'] : null,
                'sumber' => $this->nilaiImport($baris['sumber'] ?? null),
                'keterangan' => $this->nilaiImport($baris['keterangan'] ?? null),
                'tgl_masuk' => $this->tanggalImport($baris['tgl_masuk'] ?? null),
            ];

            if (! $request->user()->aksesSemuaCabang()) {
                $data['cabang'] = $request->user()->cabang;
            }

            if ($request->user()->role === 'staff') {
                $data['user_id'] = $request->user()->id;
            }

            Prospek::create($data);
            $berhasil++;
        }

        fclose($handle);

        if ($berhasil > 0) {
            $this->kirimNotifikasiImport($request, $berhasil, $dilewati);
        }

        return back()->with('berhasil', "Import selesai. {$berhasil} data masuk, {$dilewati} data dilewati.");
    }

    public function create(): View
    {
        $this->pastikanBolehUbah();

        return view('prospek.tambah', [
            'prospek' => new Prospek(['tgl_masuk' => now()]),
            'sumber' => $this->daftarSumber(),
            'status' => self::STATUS,
            'cabang' => $this->daftarCabang(),
            'adminCabang' => $this->daftarAdminCabang(),
            'program' => $this->daftarProgram(),
            'staff' => $this->staffTersedia(),
            'sekolah' => $this->sekolahTersedia(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->pastikanBolehUbah();
        $prospek = Prospek::create($this->validasi($request));
        $this->kirimNotifikasiLeads($prospek, 'Leads baru ditambahkan', "Leads {$prospek->nama} masuk ke cabang ".($prospek->cabang ?: '-').'.', 'Normal');

        return redirect()->route('prospek.index')->with('berhasil', 'Leads berhasil ditambahkan.');
    }

    public function edit(Prospek $prospek): View
    {
        $this->pastikanBolehUbah();
        $this->pastikanBolehAkses($prospek);

        return view('prospek.edit', [
            'prospek' => $prospek,
            'sumber' => $this->daftarSumber(),
            'status' => self::STATUS,
            'cabang' => $this->daftarCabang(),
            'adminCabang' => $this->daftarAdminCabang(),
            'program' => $this->daftarProgram(),
            'staff' => $this->staffTersedia($prospek->cabang),
            'sekolah' => $this->sekolahTersedia(),
        ]);
    }

    public function update(Request $request, Prospek $prospek): RedirectResponse
    {
        $this->pastikanBolehUbah();
        $this->pastikanBolehAkses($prospek);
        $statusLama = $prospek->status;
        $userLama = $prospek->user_id;
        $prospek->update($this->validasi($request, $prospek));

        if ($statusLama !== $prospek->status) {
            $prioritas = $prospek->status === 'Daftar' ? 'Tinggi' : 'Normal';
            $this->kirimNotifikasiLeads($prospek, 'Status leads diperbarui', "Status {$prospek->nama} berubah dari {$statusLama} menjadi {$prospek->status}.", $prioritas);
        }

        if ((int) $userLama !== (int) $prospek->user_id && $prospek->user_id) {
            $this->kirimNotifikasiKeUser($prospek->user_id, [
                'tipe' => 'leads',
                'judul' => 'Leads ditugaskan ke Anda',
                'pesan' => "Anda menjadi penanggung jawab leads {$prospek->nama}.",
                'tautan' => route('prospek.edit', $prospek),
                'prioritas' => 'Tinggi',
            ]);
        }

        return redirect()->route('prospek.index')->with('berhasil', 'Leads berhasil diperbarui.');
    }

    public function destroy(Prospek $prospek): RedirectResponse
    {
        $this->pastikanBolehUbah();
        $this->pastikanBolehAkses($prospek);
        $nama = $prospek->nama;
        $cabang = $prospek->cabang;
        $prospek->delete();
        $this->kirimNotifikasiCabang($cabang, [
            'tipe' => 'leads',
            'judul' => 'Leads dihapus',
            'pesan' => "Leads {$nama} dihapus dari sistem.",
            'tautan' => route('prospek.index'),
            'prioritas' => 'Tinggi',
        ]);

        return redirect()->route('prospek.index')->with('berhasil', 'Leads berhasil dihapus.');
    }

    private function validasi(Request $request, ?Prospek $prospek = null): array
    {
        $request->merge([
            'no_wa' => $this->rapikanNomorWa($request->input('no_wa')),
        ]);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'asal_sekolah' => ['nullable', 'string', 'max:255'],
            'kelas' => ['nullable', 'string', 'max:100'],
            'kota_asal' => ['nullable', 'string', 'max:255'],
            'no_wa' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('prospek', 'no_wa')->ignore($prospek?->id),
            ],
            'program' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:100'],
            'cabang' => ['nullable', Rule::in($this->daftarCabang())],
            'user_id' => ['nullable', 'exists:users,id'],
            'diserahkan_ke' => ['nullable', Rule::in($this->daftarAdminCabang())],
            'sumber' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'tgl_masuk' => ['nullable', 'date'],
        ], [
            'no_wa.unique' => 'No WA ini sudah terdaftar, gunakan data leads yang sudah ada agar tidak input ganda.',
        ]);

        $user = $request->user();

        if (! $user->aksesSemuaCabang()) {
            $data['cabang'] = $user->cabang;
        }

        if ($user->role === 'staff') {
            $data['user_id'] = $user->id;
        }

        if (blank($data['cabang'] ?? null)) {
            $data['cabang'] = $user->cabang;
        }

        if (filled($data['user_id'] ?? null)) {
            $penanggungJawab = User::query()
                ->where('id', $data['user_id'])
                ->where('aktif', true)
                ->whereIn('role', ['leader', 'staff'])
                ->first();

            abort_unless($penanggungJawab, 422, 'Penanggung jawab tidak valid.');
            abort_if($data['cabang'] && $penanggungJawab->cabang !== $data['cabang'], 422, 'Penanggung jawab harus berada di cabang yang sama.');
        }

        return $data;
    }

    private function rapikanNomorWa(?string $nomor): ?string
    {
        if ($nomor === null) {
            return null;
        }

        $nomor = trim($nomor);

        return $nomor === '' ? null : $nomor;
    }

    private function nilaiImport($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }

    private function tanggalImport($nilai): ?string
    {
        $nilai = $this->nilaiImport($nilai);

        if ($nilai === null) {
            return null;
        }

        $timestamp = strtotime($nilai);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function unduhCsv($query, string $namaFile)
    {
        $kolom = self::KOLOM_IMPORT;

        return response()->streamDownload(function () use ($query, $kolom) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $kolom);

            $query->chunk(200, function ($items) use ($handle, $kolom) {
                foreach ($items as $item) {
                    fputcsv($handle, collect($kolom)->map(function ($kolom) use ($item) {
                        if ($kolom === 'no_wa') {
                            return $item->noWaUntuk(request()->user());
                        }

                        return $kolom === 'tgl_masuk'
                            ? $item->tgl_masuk?->format('Y-m-d')
                            : $item->{$kolom};
                    })->all());
                }
            });

            fclose($handle);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function queryDaftar(Request $request)
    {
        return $this->queryAkses()
            ->when($request->filled('cari'), function ($query) use ($request) {
                $cari = $request->string('cari');

                $query->where(function ($query) use ($cari) {
                    $query->where('nama', 'like', "%{$cari}%")
                        ->orWhere('asal_sekolah', 'like', "%{$cari}%")
                        ->orWhere('no_wa', 'like', "%{$cari}%")
                        ->orWhere('program', 'like', "%{$cari}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('sumber'), fn ($query) => $query->where('sumber', $request->sumber))
            ->when($request->user()->aksesSemuaCabang() && $request->filled('cabang'), fn ($query) => $query->where('cabang', $request->cabang))
            ->latest();
    }

    private function queryAkses()
    {
        $user = request()->user();
        $query = Prospek::query();

        if ($user->aksesSemuaCabang()) {
            return $query;
        }

        return $query->where('cabang', $user->cabang);
    }

    private function queryAksesUbah()
    {
        $user = request()->user();
        $query = Prospek::query();

        if ($user->aksesSemuaCabang()) {
            return $query;
        }

        if ($user->role === 'staff') {
            return $query->where('user_id', $user->id);
        }

        return $query->where('cabang', $user->cabang);
    }

    private function queryAksesDashboard(Request $request)
    {
        $query = $this->queryAkses();

        return $query
            ->when($request->user()->aksesSemuaCabang() && $request->filled('cabang'), fn ($query) => $query->where('cabang', $request->cabang))
            ->when($request->filled('admin'), fn ($query) => $query->where('diserahkan_ke', $request->admin))
            ->when($request->filled('staff'), fn ($query) => $query->where('user_id', $request->staff));
    }

    private function queryAksesUbahDashboard(Request $request)
    {
        $query = $this->queryAksesUbah();

        return $query
            ->when($request->user()->aksesSemuaCabang() && $request->filled('cabang'), fn ($query) => $query->where('cabang', $request->cabang))
            ->when($request->filled('admin'), fn ($query) => $query->where('diserahkan_ke', $request->admin))
            ->when($request->filled('staff'), fn ($query) => $query->where('user_id', $request->staff));
    }

    private function queryAksesFollowUp(Request $request)
    {
        return $this->queryAksesDashboard($request)
            ->where(function ($query) {
                $query->whereIn('status', ['Dihubungi', 'Follow Up'])
                    ->orWhereHas('followUps');
            });
    }

    private function queryRiwayatFollowUp(Request $request)
    {
        return FollowUp::query()
            ->whereHas('prospek', fn ($query) => $this->filterAksesDashboard($query, $request));
    }

    private function filterAksesDashboard($query, Request $request)
    {
        $user = $request->user();

        if (! $user->aksesSemuaCabang()) {
            $query->where('cabang', $user->cabang);
        }

        return $query
            ->when($user->aksesSemuaCabang() && $request->filled('cabang'), fn ($query) => $query->where('cabang', $request->cabang))
            ->when($request->filled('admin'), fn ($query) => $query->where('diserahkan_ke', $request->admin))
            ->when($request->filled('staff'), fn ($query) => $query->where('user_id', $request->staff));
    }

    private function queryAksesDataSiswa(Request $request)
    {
        return $this->queryAksesDashboard($request)
            ->where('status', 'Daftar');
    }

    private function kalenderFollowUp($query, Carbon $mulai, Carbon $akhir): array
    {
        $jumlahPerTanggal = (clone $query)
            ->selectRaw('DATE(tanggal_follow_up) as tanggal, COUNT(*) as total')
            ->whereBetween('tanggal_follow_up', [$mulai, $akhir])
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');
        $tanggalPertamaGrid = $mulai->copy()->startOfWeek(Carbon::MONDAY);
        $tanggalTerakhirGrid = $akhir->copy()->endOfWeek(Carbon::SUNDAY);
        $pekan = [];
        $tanggal = $tanggalPertamaGrid->copy();

        while ($tanggal <= $tanggalTerakhirGrid) {
            $hari = [];

            for ($index = 0; $index < 7; $index++) {
                $tanggalKey = $tanggal->toDateString();

                $hari[] = [
                    'tanggal' => $tanggalKey,
                    'nomor' => $tanggal->day,
                    'bulan_aktif' => $tanggal->month === $mulai->month,
                    'hari_ini' => $tanggal->isToday(),
                    'total' => (int) ($jumlahPerTanggal[$tanggalKey] ?? 0),
                ];

                $tanggal->addDay();
            }

            $pekan[] = $hari;
        }

        return [
            'judul' => $mulai->translatedFormat('F Y'),
            'total' => (int) $jumlahPerTanggal->sum(),
            'pekan' => $pekan,
            'hari' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        ];
    }

    private function grafikLeadsHarian($query, array $periode): array
    {
        $mulai = Carbon::create($periode['tahun'], $periode['bulan'], 1)->startOfMonth();
        $akhir = $mulai->copy()->endOfMonth();
        $jumlahLead = (clone $query)
            ->selectRaw('DATE(COALESCE(tgl_masuk, created_at)) as tanggal, COUNT(*) as total')
            ->whereRaw('DATE(COALESCE(tgl_masuk, created_at)) between ? and ?', [
                $mulai->toDateString(),
                $akhir->toDateString(),
            ])
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');
        $jumlahClosing = (clone $query)
            ->where('status', 'Daftar')
            ->selectRaw('DATE(COALESCE(tgl_masuk, created_at)) as tanggal, COUNT(*) as total')
            ->whereRaw('DATE(COALESCE(tgl_masuk, created_at)) between ? and ?', [
                $mulai->toDateString(),
                $akhir->toDateString(),
            ])
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $hari = [];
        $tanggal = $mulai->copy();

        while ($tanggal <= $akhir) {
            $tanggalKey = $tanggal->toDateString();

            $hari[] = [
                'tanggal' => $tanggalKey,
                'nomor' => $tanggal->day,
                'lead' => (int) ($jumlahLead[$tanggalKey] ?? 0),
                'closing' => (int) ($jumlahClosing[$tanggalKey] ?? 0),
            ];

            $tanggal->addDay();
        }

        $maksData = (int) collect($hari)->max(fn ($item) => max($item['lead'], $item['closing']));
        $skalaGrafik = $this->skalaGrafikHarian($maksData);
        $maks = $skalaGrafik['maks'];
        $tinggi = 170;
        $lebar = 1000;
        $lebarLangkah = count($hari) > 1 ? $lebar / (count($hari) - 1) : 0;
        $buatTitik = function (string $key) use ($hari, $maks, $tinggi, $lebarLangkah): string {
            return collect($hari)
                ->map(function ($item, $index) use ($key, $maks, $tinggi, $lebarLangkah) {
                    $x = $index * $lebarLangkah;
                    $y = $tinggi - (($item[$key] / $maks) * $tinggi);

                    return round($x, 2).','.round($y, 2);
                })
                ->implode(' ');
        };

        return [
            'hari' => $hari,
            'maks' => $maks,
            'bulan' => $mulai->translatedFormat('F Y'),
            'lebar' => $lebar,
            'tinggi' => $tinggi,
            'leadPoints' => $buatTitik('lead'),
            'closingPoints' => $buatTitik('closing'),
            'areaLeadPoints' => '0,'.$tinggi.' '.$buatTitik('lead').' '.$lebar.','.$tinggi,
            'skala' => $skalaGrafik['label'],
        ];
    }

    private function skalaGrafikHarian(int $maksData): array
    {
        if ($maksData <= 0) {
            return [
                'maks' => 4,
                'label' => [4, 3, 2, 1, 0],
            ];
        }

        $targetPerSegmen = max(1, (int) ceil($maksData / 4));
        $pilihanStep = [1, 2, 5, 10, 20, 25, 50, 100];
        $step = collect($pilihanStep)->first(fn ($nilai) => $nilai >= $targetPerSegmen);

        if ($step === null) {
            $step = (int) ceil($targetPerSegmen / 100) * 100;
        }

        return [
            'maks' => $step * 4,
            'label' => collect(range(4, 0))->map(fn ($index) => $step * $index)->all(),
        ];
    }

    private function periodeDashboard(Request $request): array
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        if ($tahun < 2000 || $tahun > now()->year + 1) {
            $tahun = now()->year;
        }

        return compact('bulan', 'tahun');
    }

    private function statusDariHasilFollowUp(string $hasil): string
    {
        return match ($hasil) {
            'Closing' => 'Daftar',
            'Tidak tertarik', 'Nomor tidak aktif' => 'Tidak Tertarik',
            'Tidak tersambung' => 'Dihubungi',
            default => 'Follow Up',
        };
    }

    private function daftarBulan(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn ($bulan) => [$bulan => Carbon::create(null, $bulan, 1)->translatedFormat('F')])
            ->all();
    }

    private function daftarCabang(): array
    {
        $items = Cabang::query()->where('aktif', true)->orderBy('nama')->pluck('nama')->all();

        return $items ?: ['Bandung', 'Jaksel', 'Jakpus'];
    }

    private function daftarAdminCabang(): array
    {
        return collect($this->daftarCabang())
            ->map(fn ($cabang) => 'Admin '.$cabang)
            ->all();
    }

    private function daftarSumber(): array
    {
        $items = SumberLead::query()->where('aktif', true)->orderBy('nama')->pluck('nama')->all();

        return $items ?: ['Iklan', 'Instagram', 'Tiktok', 'Facebook', 'Kunjungan', 'Website'];
    }

    private function daftarProgram(): array
    {
        return ProgramLead::query()
            ->where('aktif', true)
            ->orderBy('nama')
            ->pluck('nama')
            ->all();
    }

    private function pastikanBolehAkses(Prospek $prospek): void
    {
        $user = request()->user();

        if ($user->aksesSemuaCabang()) {
            return;
        }

        if ($user->role === 'staff') {
            abort_unless((int) $prospek->user_id === (int) $user->id, 403);

            return;
        }

        abort_unless($prospek->cabang === $user->cabang, 403);
    }

    private function pastikanBolehUbah(): void
    {
        abort_if(request()->user()->role === 'direksi', 403);
    }

    private function staffTersedia(?string $cabang = null)
    {
        $user = request()->user();

        return User::query()
            ->where('aktif', true)
            ->whereIn('role', ['leader', 'staff'])
            ->when(! $user->aksesSemuaCabang(), fn ($query) => $query->where('cabang', $user->cabang))
            ->when($cabang, fn ($query) => $query->where('cabang', $cabang))
            ->orderBy('name')
            ->get();
    }

    private function kirimNotifikasiLeads(Prospek $prospek, string $judul, string $pesan, string $prioritas = 'Normal'): void
    {
        $penerima = SistemNotification::penerimaCabang($prospek->cabang);

        if ($prospek->user_id) {
            $penerima = $penerima->push(User::find($prospek->user_id));
        }

        SistemNotification::kirim($penerima, [
            'tipe' => 'leads',
            'judul' => $judul,
            'pesan' => $pesan,
            'tautan' => route('prospek.index'),
            'prioritas' => $prioritas,
        ]);
    }

    private function kirimNotifikasiFollowUp(Prospek $prospek, array $data): void
    {
        $pesan = "Follow up {$prospek->nama} dicatat dengan hasil {$data['hasil']}.";

        if (! blank($data['tanggal_follow_up_berikutnya'] ?? null)) {
            $pesan .= ' Jadwal berikutnya '.$data['tanggal_follow_up_berikutnya'].'.';
        }

        $penerima = SistemNotification::penerimaCabang($prospek->cabang);

        if ($prospek->user_id) {
            $penerima = $penerima->push(User::find($prospek->user_id));
        }

        SistemNotification::kirim($penerima, [
            'tipe' => 'follow_up',
            'judul' => 'Aktivitas follow up baru',
            'pesan' => $pesan,
            'tautan' => route('follow-up.index'),
            'prioritas' => ($data['prioritas'] ?? 'Normal') === 'Tinggi' ? 'Tinggi' : 'Normal',
        ]);
    }

    private function kirimNotifikasiImport(Request $request, int $berhasil, int $dilewati): void
    {
        $penerima = $request->user()->aksesSemuaCabang()
            ? User::query()->where('aktif', true)->whereIn('role', ['superadmin', 'direksi'])->get()
            : SistemNotification::penerimaCabang($request->user()->cabang);

        SistemNotification::kirim($penerima, [
            'tipe' => 'leads',
            'judul' => 'Import leads selesai',
            'pesan' => "{$berhasil} leads berhasil diimport, {$dilewati} data dilewati.",
            'tautan' => route('prospek.index'),
            'prioritas' => 'Normal',
        ]);
    }

    private function kirimNotifikasiCabang(?string $cabang, array $data): void
    {
        SistemNotification::kirim(SistemNotification::penerimaCabang($cabang), $data);
    }

    private function kirimNotifikasiKeUser(int $userId, array $data): void
    {
        SistemNotification::kirim(User::query()->where('id', $userId)->where('aktif', true)->get(), $data);
    }

    private function sekolahTersedia(): array
    {
        $path = database_path('sekolahVM.json');

        if (! File::exists($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            return [];
        }

        return collect($data)
            ->pluck('nama_sekolah')
            ->filter()
            ->map(fn ($nama) => trim((string) $nama))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}

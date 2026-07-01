<?php

namespace App\Http\Controllers;

use App\Models\Prospek;
use App\Models\Cabang;
use App\Models\FollowUp;
use App\Models\ProgramLead;
use App\Models\Sekolah;
use App\Models\SumberLead;
use App\Models\SistemNotification;
use App\Models\ProspekStatusHistory;
use App\Models\TargetKinerja;
use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    private const STATUS_PEMBAYARAN = ['Belum Bayar', 'DP', 'Lunas', 'Cicilan'];

    private const JENJANG = ['SD', 'SMP', 'SMA', 'Gapyear'];

    private const KELAS_PER_JENJANG = [
        'SD' => ['1', '2', '3', '4', '5', '6'],
        'SMP' => ['7', '8', '9'],
        'SMA' => ['X', 'XI', 'XII'],
        'Gapyear' => [],
    ];

    private const KOLOM_IMPORT = [
        'nama',
        'asal_sekolah',
        'jenjang',
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
        $dashboardRole = $this->konteksDashboard($request);
        $query = $this->filterPeriodeDashboard($this->queryDashboardPerRole($request), $periode);
        $queryClosing = $this->filterPeriodeClosingDashboard($this->queryDashboardPerRole($request), $periode)
            ->where('status', 'Daftar');
        $queryLeadsAktif = $this->queryLeadsAktif(clone $query);
        $total = (clone $queryLeadsAktif)->count();
        $baru = (clone $queryLeadsAktif)->where('status', 'Baru')->count();
        $followUp = (clone $queryLeadsAktif)->whereIn('status', ['Dihubungi', 'Follow Up'])->count();
        $daftar = (clone $queryClosing)->count();
        $perSumber = (clone $queryLeadsAktif)->selectRaw('COALESCE(sumber, "Tanpa Sumber") as sumber, COUNT(*) as total')
            ->groupBy('sumber')
            ->orderByDesc('total')
            ->limit(6)
            ->get();
        $perProgram = (clone $queryLeadsAktif)->selectRaw('COALESCE(program, "Tanpa Program") as program, COUNT(*) as total')
            ->groupBy('program')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
        $perSekolah = (clone $queryLeadsAktif)->selectRaw('COALESCE(asal_sekolah, "Tanpa Sekolah") as asal_sekolah, COUNT(*) as total')
            ->groupBy('asal_sekolah')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $perCabang = (clone $queryLeadsAktif)->selectRaw('COALESCE(cabang, "Tanpa Cabang") as cabang, COUNT(*) as total')
            ->groupBy('cabang')
            ->orderByDesc('total')
            ->get();
        $totalLeadKeseluruhan = $total + $daftar;
        $conversionRate = $totalLeadKeseluruhan > 0 ? round(($daftar / $totalLeadKeseluruhan) * 100, 2) : 0;
        $cabangStaffFilter = $dashboardRole['cabangTerkunci'] ?? ($request->string('cabang')->toString() ?: null);
        $csoAktif = User::query()
            ->where('aktif', true)
            ->where('role', 'staff')
            ->when($cabangStaffFilter, fn ($query) => $query->where('cabang', $cabangStaffFilter))
            ->count();
        $totalAsalSekolah = (clone $queryLeadsAktif)
            ->whereNotNull('asal_sekolah')
            ->where('asal_sekolah', '!=', '')
            ->distinct()
            ->count('asal_sekolah');
        $grafikHarian = $this->grafikLeadsHarian((clone $query), $periode);
        $dashboardClosing = $this->dashboardClosing((clone $queryClosing));
        $targetKinerja = $this->targetKinerjaDashboard($request, $periode, $dashboardRole, $total, $daftar);
        $rankingKinerja = $this->rankingKinerjaDashboard($request, $periode, $dashboardRole);
        $kpiOperasional = $this->kpiOperasionalDashboard($request, (clone $query), $periode);
        $agingLeads = $this->agingLeadsDashboard((clone $query));
        $performaInputUser = $this->performaInputUserDashboard($request, $periode);
        $konversiSumber = $this->konversiSumberDashboard($request, $periode);
        $staffFilter = $dashboardRole['bolehFilterStaff']
            ? $this->staffTersedia($cabangStaffFilter, batasiAkses: false)
            : collect();

        return view('dashboard', [
            'dashboardRole' => $dashboardRole,
            'total' => $total,
            'totalLeadKeseluruhan' => $totalLeadKeseluruhan,
            'conversionRate' => $conversionRate,
            'csoAktif' => $csoAktif,
            'totalAsalSekolah' => $totalAsalSekolah,
            'baru' => $baru,
            'followUp' => $followUp,
            'daftar' => $daftar,
            'perSumber' => $perSumber,
            'perProgram' => $perProgram,
            'perSekolah' => $perSekolah,
            'perCabang' => $perCabang,
            'performaRole' => $this->performaDashboardRole((clone $query), $dashboardRole),
            'dashboardClosing' => $dashboardClosing,
            'targetKinerja' => $targetKinerja,
            'rankingKinerja' => $rankingKinerja,
            'kpiOperasional' => $kpiOperasional,
            'agingLeads' => $agingLeads,
            'performaInputUser' => $performaInputUser,
            'konversiSumber' => $konversiSumber,
            'grafikHarian' => $grafikHarian,
            'cabang' => $this->daftarCabang(),
            'adminCabang' => $this->daftarAdminCabang($dashboardRole['cabangTerkunci']),
            'staffFilter' => $staffFilter,
            'bulanFilter' => $periode['bulan'],
            'tahunFilter' => $periode['tahun'],
            'semuaPeriode' => $periode['semua'],
            'daftarBulan' => $this->daftarBulan(),
            'daftarTahun' => range((int) now()->year, (int) now()->year - 5),
        ]);
    }

    public function index(Request $request): View
    {
        $prospek = $this->queryDaftar($request)
            ->with(['penanggungJawab', 'pembuat'])
            ->paginate(10)
            ->withQueryString();

        return view('prospek.index', [
            'prospek' => $prospek,
            'sumber' => $this->daftarSumber(),
            'status' => $this->statusDataLeads(),
            'cabang' => $this->daftarCabang(),
            'inputUsers' => $this->userInputTersedia(),
            'templateWhatsapp' => WhatsappTemplate::aktifDefault(),
        ]);
    }

    public function show(Request $request, Prospek $prospek): View
    {
        $this->pastikanBolehLihat($prospek);

        $prospek->load([
            'penanggungJawab',
            'pembuat',
            'followUps' => fn ($query) => $query->with('user')->latest('tanggal_follow_up'),
            'followUpTerakhir.user',
            'followUpBerikutnya',
            'tasks' => fn ($query) => $query->with(['penanggungJawab', 'komentar.user'])->latest(),
            'riwayatStatus' => fn ($query) => $query->with('user')->latest(),
        ]);

        return view('prospek.detail', [
            'prospek' => $prospek,
            'bisaUbah' => $prospek->bisaDiubahOleh($request->user()),
            'templateWhatsapp' => WhatsappTemplate::aktifDefault(),
        ]);
    }

    public function followUp(Request $request): View
    {
        $periode = $this->periodeDashboard($request);
        $query = $this->queryAksesFollowUp($request);
        $this->buatNotifikasiReminderFollowUp($request);
        $reminderFollowUp = $this->reminderFollowUp($request);
        $mulai = Carbon::create($periode['tahun'], $periode['bulan'], 1)->startOfMonth();
        $akhir = $mulai->copy()->endOfMonth();

        $prospek = (clone $query)
            ->with(['followUpTerakhir.user', 'followUpBerikutnya.user', 'penanggungJawab'])
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
            'reminderFollowUp' => $reminderFollowUp,
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
            'staffFilter' => $this->staffTersedia($request->string('cabang')->toString() ?: null, batasiAkses: false),
            'bulanFilter' => $periode['bulan'],
            'tahunFilter' => $periode['tahun'],
            'daftarBulan' => $this->daftarBulan(),
            'daftarTahun' => range((int) now()->year, (int) now()->year - 5),
            'templateWhatsapp' => WhatsappTemplate::aktifDefault(),
        ]);
    }

    public function storeFollowUp(Request $request): RedirectResponse
    {
        $this->pastikanBolehFollowUp();

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

        $statusLama = $prospek->status;
        $dataProspek = [
            'status' => $this->statusDariHasilFollowUp($data['hasil']),
            'user_id' => $prospek->user_id ?: $request->user()->id,
        ];

        if ($dataProspek['status'] === 'Daftar') {
            $dataProspek['tanggal_daftar'] = now()->toDateString();
            $dataProspek['program_final'] = $prospek->program;
            $dataProspek['status_pembayaran'] = $prospek->status_pembayaran ?: 'Belum Bayar';
        }

        $prospek->update($dataProspek);
        $prospek = $prospek->fresh();

        if ($statusLama !== $prospek->status) {
            $this->catatRiwayatStatus($prospek, $statusLama, $prospek->status, $request->user(), 'follow_up', 'Hasil follow up: '.$data['hasil']);
        }

        $this->kirimNotifikasiFollowUp($prospek, $data);

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
            ->when($request->filled('status_pembayaran'), fn ($query) => $query->where('status_pembayaran', $request->status_pembayaran))
            ->orderByRaw('COALESCE(tanggal_daftar, updated_at) desc')
            ->paginate(10)
            ->withQueryString();

        return view('data-siswa.index', [
            'prospek' => $prospek,
            'cabang' => $this->daftarCabang(),
            'adminCabang' => $this->daftarAdminCabang(),
            'staffFilter' => $this->staffTersedia($request->string('cabang')->toString() ?: null, batasiAkses: false),
            'statusPembayaran' => self::STATUS_PEMBAYARAN,
            'templateWhatsapp' => WhatsappTemplate::aktifDefault(),
        ]);
    }

    public function detailDataSiswa(Request $request, Prospek $prospek): View
    {
        $this->pastikanBolehLihat($prospek);
        abort_unless($prospek->status === 'Daftar', 404);

        $prospek->load([
            'penanggungJawab',
            'pembuat',
            'followUps' => fn ($query) => $query->with('user')->latest('tanggal_follow_up'),
            'followUpTerakhir.user',
            'tasks' => fn ($query) => $query->with(['penanggungJawab', 'komentar.user'])->latest(),
            'riwayatStatus' => fn ($query) => $query->with('user')->latest(),
        ]);

        return view('data-siswa.detail', [
            'prospek' => $prospek,
            'bisaUbah' => $prospek->bisaDiubahOleh($request->user()),
            'templateWhatsapp' => WhatsappTemplate::aktifDefault(),
        ]);
    }

    public function exportDataSiswa(Request $request)
    {
        $namaFile = 'export-data-siswa-'.now()->format('Ymd-His').'.csv';

        return $this->unduhCsvDataSiswa(
            $this->queryAksesDataSiswa($request)
                ->when($request->filled('cari'), function ($query) use ($request) {
                    $cari = $request->string('cari');

                    $query->where(function ($query) use ($cari) {
                        $query->where('nama', 'like', "%{$cari}%")
                            ->orWhere('asal_sekolah', 'like', "%{$cari}%")
                            ->orWhere('no_wa', 'like', "%{$cari}%")
                            ->orWhere('program', 'like', "%{$cari}%")
                            ->orWhere('program_final', 'like', "%{$cari}%");
                    });
                })
                ->when($request->filled('status_pembayaran'), fn ($query) => $query->where('status_pembayaran', $request->status_pembayaran))
                ->orderByRaw('COALESCE(tanggal_daftar, updated_at) desc'),
            $namaFile
        );
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

        $this->pastikanBolehHapus();
        $query = $this->queryAksesUbah()
            ->whereIn('id', $data['ids']);
        $jumlah = (clone $query)->delete();

        return back()->with('berhasil', "{$jumlah} leads terpilih berhasil dihapus.");
    }

    public function contohImport()
    {
        $contoh = [
            'Budi Santoso',
            'SMAS Al Azhar 1',
            'SMA',
            'XII',
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
        $kolomKurang = array_values(array_diff(self::KOLOM_IMPORT, $header));

        if ($kolomKurang) {
            fclose($handle);

            return back()->withErrors([
                'file_import' => 'Kolom wajib tidak ditemukan: '.implode(', ', $kolomKurang).'. Gunakan tombol Contoh File sebagai format acuan.',
            ]);
        }

        $berhasil = 0;
        $dilewati = 0;
        $errorImport = [];
        $nomorWaDalamFile = [];
        $nomorBaris = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $nomorBaris++;
            $row = array_slice(array_pad($row, count($header), null), 0, count($header));
            $baris = array_combine($header, $row);

            [$data, $errorBaris, $noWa] = $this->validasiBarisImport($baris, $request, $nomorWaDalamFile);

            if ($noWa) {
                $nomorWaDalamFile[$noWa] = true;
            }

            if ($errorBaris) {
                $dilewati++;
                $errorImport[] = [
                    'baris' => $nomorBaris,
                    'nama' => $this->nilaiImport($baris['nama'] ?? null) ?: '-',
                    'no_wa' => $noWa ?: '-',
                    'alasan' => $errorBaris,
                ];
                continue;
            }

            $data['created_by'] = $request->user()?->id;

            $prospek = Prospek::create($data);
            $this->simpanSekolahBaru($prospek->asal_sekolah, 'import', $request->user()?->id);
            $this->catatRiwayatStatus($prospek, null, $prospek->status, $request->user(), 'import', 'Status awal dari import CSV.');
            $berhasil++;
        }

        fclose($handle);

        if ($berhasil > 0) {
            $this->kirimNotifikasiImport($request, $berhasil, $dilewati);
        }

        return back()
            ->with('berhasil', "Import selesai. {$berhasil} data masuk, {$dilewati} data gagal.")
            ->with('error_import', $errorImport);
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
            'jenjang' => self::JENJANG,
            'kelasPerJenjang' => self::KELAS_PER_JENJANG,
            'statusPembayaran' => self::STATUS_PEMBAYARAN,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->pastikanBolehUbah();
        $data = $this->validasi($request);
        $data['created_by'] = $request->user()?->id;

        $prospek = Prospek::create($data);
        $this->simpanSekolahBaru($prospek->asal_sekolah, 'manual', $request->user()?->id);
        $this->catatRiwayatStatus($prospek, null, $prospek->status, $request->user(), 'manual', 'Status awal saat leads dibuat.');
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
            'jenjang' => self::JENJANG,
            'kelasPerJenjang' => self::KELAS_PER_JENJANG,
            'statusPembayaran' => self::STATUS_PEMBAYARAN,
        ]);
    }

    public function update(Request $request, Prospek $prospek): RedirectResponse
    {
        $this->pastikanBolehUbah();
        $this->pastikanBolehAkses($prospek);
        $statusLama = $prospek->status;
        $userLama = $prospek->user_id;
        $prospek->update($this->validasi($request, $prospek));
        $this->simpanSekolahBaru($prospek->asal_sekolah, 'manual', $request->user()?->id);

        if ($statusLama !== $prospek->status) {
            $this->catatRiwayatStatus($prospek, $statusLama, $prospek->status, $request->user(), 'manual', 'Status diperbarui dari form leads.');
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
        $this->pastikanBolehHapus();
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
            'asal_sekolah' => $this->rapikanAsalSekolah($request->input('asal_sekolah')),
            'kelas' => $this->rapikanKelas($request->input('kelas')),
        ]);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'asal_sekolah' => ['nullable', 'string', 'max:255'],
            'jenjang' => ['nullable', Rule::in(self::JENJANG)],
            'kelas' => ['nullable', Rule::in($this->semuaKelas())],
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
            'tanggal_daftar' => ['nullable', 'date'],
            'program_final' => ['nullable', 'string', 'max:255'],
            'nominal_pembayaran' => ['nullable', 'numeric', 'min:0'],
            'status_pembayaran' => ['nullable', Rule::in(self::STATUS_PEMBAYARAN)],
            'kelas_angkatan' => ['nullable', 'string', 'max:255'],
            'catatan_administrasi' => ['nullable', 'string'],
        ], [
            'no_wa.unique' => 'No WA ini sudah terdaftar, gunakan data leads yang sudah ada agar tidak input ganda.',
            'jenjang.in' => 'Jenjang hanya boleh SD, SMP, SMA, atau Gapyear.',
            'kelas.in' => 'Kelas tidak valid untuk jenjang yang dipilih.',
        ]);

        $this->validasiKelasUntukJenjang($data);
        $this->validasiFormatAsalSekolah($data);

        $user = $request->user();

        if (! $user->bisaMengubahSemuaLeads()) {
            $data['cabang'] = $user->cabang;
        }

        if ($user->bisaMengubahLeadsMilikSendiri()) {
            $data['user_id'] = $user->id;
        }

        if (blank($data['cabang'] ?? null)) {
            $data['cabang'] = $user->cabang;
        }

        if (($data['status'] ?? null) === 'Daftar') {
            $data['tanggal_daftar'] = filled($data['tanggal_daftar'] ?? null)
                ? $data['tanggal_daftar']
                : now()->toDateString();
            $data['program_final'] = filled($data['program_final'] ?? null)
                ? $data['program_final']
                : ($data['program'] ?? null);
            $data['status_pembayaran'] = filled($data['status_pembayaran'] ?? null)
                ? $data['status_pembayaran']
                : 'Belum Bayar';
        } else {
            $data['tanggal_daftar'] = null;
            $data['program_final'] = null;
            $data['nominal_pembayaran'] = null;
            $data['status_pembayaran'] = null;
            $data['kelas_angkatan'] = null;
            $data['catatan_administrasi'] = null;
        }

        if (filled($data['user_id'] ?? null)) {
            $penanggungJawab = User::query()
                ->where('id', $data['user_id'])
                ->where('aktif', true)
                ->where('role', 'staff')
                ->first();

            abort_unless($penanggungJawab, 422, 'Penanggung jawab tidak valid.');
            abort_if($data['cabang'] && $penanggungJawab->cabang !== $data['cabang'], 422, 'Penanggung jawab harus berada di cabang yang sama.');
        }

        return $data;
    }

    private function semuaKelas(): array
    {
        return collect(self::KELAS_PER_JENJANG)->flatten()->unique()->values()->all();
    }

    private function rapikanKelas($kelas): ?string
    {
        $kelas = $this->nilaiImport($kelas);

        return $kelas ? strtoupper($kelas) : null;
    }

    private function validasiKelasUntukJenjang(array &$data): void
    {
        $jenjang = $data['jenjang'] ?? null;
        $kelas = $data['kelas'] ?? null;

        if ($jenjang === 'Gapyear') {
            $data['kelas'] = null;

            return;
        }

        if (! $jenjang) {
            if ($kelas) {
                throw ValidationException::withMessages([
                    'jenjang' => 'Pilih jenjang sebelum memilih kelas.',
                ]);
            }

            return;
        }

        if (! $kelas) {
            return;
        }

        if (! in_array($kelas, self::KELAS_PER_JENJANG[$jenjang] ?? [], true)) {
            throw ValidationException::withMessages([
                'kelas' => "Kelas {$kelas} tidak sesuai dengan jenjang {$jenjang}.",
            ]);
        }
    }

    private function rapikanNomorWa(?string $nomor): ?string
    {
        if ($nomor === null) {
            return null;
        }

        $nomor = trim($nomor);

        return $nomor === '' ? null : $nomor;
    }

    private function rapikanAsalSekolah(?string $sekolah): ?string
    {
        if ($sekolah === null) {
            return null;
        }

        $sekolah = preg_replace('/\s+/', ' ', trim($sekolah));

        if ($sekolah === '') {
            return null;
        }

        $sekolah = preg_replace('/^SMA\s+NEGERI\b/i', 'SMAN', $sekolah);
        $sekolah = preg_replace('/^SMA\s+N\b/i', 'SMAN', $sekolah);
        $sekolah = preg_replace('/^SMA\s+SWASTA\b/i', 'SMAS', $sekolah);
        $sekolah = preg_replace('/^SMA\s+S\b/i', 'SMAS', $sekolah);
        $sekolah = preg_replace('/^SMAI(T)?\b/i', 'SMAS', $sekolah);

        if (preg_match('/^(SMAN|SMAS)\b(.*)$/i', $sekolah, $cocok)) {
            $nama = $this->judulSekolah($cocok[2]);

            return strtoupper($cocok[1]).($nama ? ' '.$nama : '');
        }

        return $this->judulSekolah($sekolah);
    }

    private function judulSekolah(string $sekolah): string
    {
        $sekolah = preg_replace('/\s+/', ' ', trim($sekolah));

        return $sekolah === '' ? '' : ucwords(strtolower($sekolah));
    }

    private function validasiFormatAsalSekolah(array $data): void
    {
        if (($data['jenjang'] ?? null) !== 'SMA' || blank($data['asal_sekolah'] ?? null)) {
            return;
        }

        if (! preg_match('/^SMA[NS]\b/', $data['asal_sekolah'])) {
            throw ValidationException::withMessages([
                'asal_sekolah' => 'Untuk jenjang SMA, isi asal sekolah dengan format SMAN untuk negeri atau SMAS untuk swasta. Contoh: SMAN 1 Bandung atau SMAS Al Azhar 1.',
            ]);
        }
    }

    private function simpanSekolahBaru(?string $namaSekolah, string $sumber, ?int $userId = null): void
    {
        if (blank($namaSekolah) || ! Schema::hasTable('sekolah')) {
            return;
        }

        $namaSekolah = $this->rapikanAsalSekolah($namaSekolah);

        if (blank($namaSekolah) || $this->adaDiReferensiSekolahJson($namaSekolah)) {
            return;
        }

        Sekolah::query()->firstOrCreate(
            ['nama_normalized' => $this->normalisasiNamaSekolah($namaSekolah)],
            [
                'nama_sekolah' => $namaSekolah,
                'sumber' => $sumber,
                'created_by' => $userId,
            ]
        );
    }

    private function adaDiReferensiSekolahJson(string $namaSekolah): bool
    {
        $namaNormalized = $this->normalisasiNamaSekolah($namaSekolah);

        return collect($this->sekolahJson())
            ->contains(fn ($nama) => $this->normalisasiNamaSekolah($nama) === $namaNormalized);
    }

    private function normalisasiNamaSekolah(string $namaSekolah): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($namaSekolah)));
    }

    private function nilaiImport($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }

    private function validasiBarisImport(array|false $baris, Request $request, array $nomorWaDalamFile): array
    {
        if (! is_array($baris)) {
            return [null, ['Format baris CSV tidak valid.'], null];
        }

        $error = [];
        $noWa = $this->rapikanNomorWa($baris['no_wa'] ?? null);
        $nama = $this->nilaiImport($baris['nama'] ?? null);
        $status = $this->nilaiImport($baris['status'] ?? null);
        $cabang = $this->nilaiImport($baris['cabang'] ?? null);
        $diserahkanKe = $this->nilaiImport($baris['diserahkan_ke'] ?? null);
        $tanggalMasukMentah = $this->nilaiImport($baris['tgl_masuk'] ?? null);
        $tanggalMasuk = $this->tanggalImport($tanggalMasukMentah);
        $jenjang = $this->nilaiImport($baris['jenjang'] ?? null);
        $kelas = $this->rapikanKelas($baris['kelas'] ?? null);
        $asalSekolah = $this->rapikanAsalSekolah($baris['asal_sekolah'] ?? null);

        if (! $nama) {
            $error[] = 'Nama wajib diisi.';
        }

        if ($noWa && Prospek::where('no_wa', $noWa)->exists()) {
            $error[] = "Nomor WA duplikat dengan data sistem: {$noWa}.";
        }

        if ($noWa && isset($nomorWaDalamFile[$noWa])) {
            $error[] = "Nomor WA duplikat di file import: {$noWa}.";
        }

        if ($status && ! in_array($status, self::STATUS, true)) {
            $error[] = "Status tidak valid: {$status}.";
        }

        if ($jenjang && ! in_array($jenjang, self::JENJANG, true)) {
            $error[] = "Jenjang tidak valid: {$jenjang}. Gunakan SD, SMP, SMA, atau Gapyear.";
        }

        if ($kelas && ! in_array($kelas, $this->semuaKelas(), true)) {
            $error[] = "Kelas tidak valid: {$kelas}.";
        }

        if ($jenjang && $kelas && ! in_array($kelas, self::KELAS_PER_JENJANG[$jenjang] ?? [], true)) {
            $error[] = "Kelas {$kelas} tidak sesuai dengan jenjang {$jenjang}.";
        }

        if ($jenjang === 'Gapyear') {
            $kelas = null;
        }

        if ($jenjang === 'SMA' && $asalSekolah && ! preg_match('/^SMA[NS]\b/', $asalSekolah)) {
            $error[] = "Asal sekolah jenjang SMA harus memakai format SMAN untuk negeri atau SMAS untuk swasta: {$asalSekolah}.";
        }

        if ($request->user()->bisaMengubahSemuaLeads()) {
            if ($cabang && ! in_array($cabang, $this->daftarCabang(), true)) {
                $error[] = "Cabang tidak valid: {$cabang}.";
            }
        } elseif ($cabang && $cabang !== $request->user()->cabang) {
            $error[] = "Cabang tidak sesuai akses user: {$cabang}.";
        }

        if ($diserahkanKe && ! in_array($diserahkanKe, $this->daftarAdminCabang(), true)) {
            $error[] = "Diserahkan ke tidak valid: {$diserahkanKe}.";
        }

        if ($tanggalMasukMentah && ! $tanggalMasuk) {
            $error[] = "Tanggal masuk tidak valid: {$tanggalMasukMentah}.";
        }

        if ($error) {
            return [null, $error, $noWa];
        }

        $data = [
            'nama' => $nama,
            'asal_sekolah' => $asalSekolah,
            'jenjang' => $jenjang,
            'kelas' => $kelas,
            'kota_asal' => $this->nilaiImport($baris['kota_asal'] ?? null),
            'no_wa' => $noWa,
            'program' => $this->nilaiImport($baris['program'] ?? null),
            'status' => $status ?: 'Baru',
            'cabang' => $cabang,
            'diserahkan_ke' => $diserahkanKe,
            'sumber' => $this->nilaiImport($baris['sumber'] ?? null),
            'keterangan' => $this->nilaiImport($baris['keterangan'] ?? null),
            'tgl_masuk' => $tanggalMasuk,
        ];

        if (! $request->user()->bisaMengubahSemuaLeads()) {
            $data['cabang'] = $request->user()->cabang;
        }

        if ($request->user()->bisaMengubahLeadsMilikSendiri()) {
            $data['user_id'] = $request->user()->id;
        }

        return [$data, [], $noWa];
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

    private function unduhCsvDataSiswa($query, string $namaFile)
    {
        $kolom = [
            'nama',
            'asal_sekolah',
            'jenjang',
            'kelas',
            'kelas_angkatan',
            'kota_asal',
            'no_wa',
            'program_awal',
            'program_final',
            'status_pembayaran',
            'nominal_pembayaran',
            'cabang',
            'sumber',
            'penanggung_jawab',
            'tgl_masuk',
            'tanggal_closing',
            'catatan_administrasi',
        ];

        return response()->streamDownload(function () use ($query, $kolom) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $kolom);

            $query->with('penanggungJawab')->chunk(200, function ($items) use ($handle) {
                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->nama,
                        $item->asal_sekolah,
                        $item->jenjang,
                        $item->kelas,
                        $item->kelas_angkatan,
                        $item->kota_asal,
                        $item->noWaUntuk(request()->user()),
                        $item->program,
                        $item->program_final ?: $item->program,
                        $item->status_pembayaran ?: 'Belum Diisi',
                        $item->nominal_pembayaran,
                        $item->cabang,
                        $item->sumber,
                        $item->penanggungJawab?->name,
                        $item->tgl_masuk?->format('Y-m-d'),
                        $item->tanggal_daftar?->format('Y-m-d') ?: $item->updated_at?->format('Y-m-d'),
                        $item->catatan_administrasi,
                    ]);
                }
            });

            fclose($handle);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function queryDaftar(Request $request)
    {
        return $this->queryAkses()
            ->where('status', '!=', 'Daftar')
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
            ->when($request->filled('cabang'), fn ($query) => $query->where('cabang', $request->cabang))
            ->when($request->filled('created_by'), fn ($query) => $query->where('created_by', $request->integer('created_by')))
            ->latest();
    }

    private function userInputTersedia()
    {
        return User::query()
            ->where('aktif', true)
            ->whereIn('role', ['admin', 'staff'])
            ->orderBy('name')
            ->get();
    }

    private function statusDataLeads(): array
    {
        return array_values(array_diff(self::STATUS, ['Daftar']));
    }

    private function queryAkses()
    {
        $user = request()->user();
        $query = Prospek::query();

        if ($user?->bisaLihatSemuaLeads()) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    private function queryAksesUbah()
    {
        $user = request()->user();
        $query = Prospek::query();

        if ($user->bisaMengubahSemuaLeads()) {
            return $query;
        }

        if ($user->bisaMengubahLeadsMilikSendiri()) {
            return $query->where('user_id', $user->id);
        }

        if ($user->bisaMengubahLeadsCabang()) {
            return $query->where('cabang', $user->cabang);
        }

        return $query->whereRaw('1 = 0');
    }

    private function queryAksesDashboard(Request $request)
    {
        $query = $this->queryAkses();

        return $query
            ->when($request->filled('cabang'), fn ($query) => $query->where('cabang', $request->cabang))
            ->when($request->filled('admin'), fn ($query) => $query->where('diserahkan_ke', $request->admin))
            ->when($request->filled('staff'), fn ($query) => $query->where('user_id', $request->staff));
    }

    private function queryLeadsAktif($query)
    {
        return $query->whereNotIn('status', ['Daftar', 'Tidak Tertarik']);
    }

    private function queryDashboardPerRole(Request $request)
    {
        return $this->queryAksesDashboard($request);
    }

    private function filterPeriodeDashboard($query, array $periode)
    {
        if ($periode['semua']) {
            return $query;
        }

        $mulai = Carbon::create($periode['tahun'], $periode['bulan'], 1)->startOfMonth();
        $akhir = $mulai->copy()->endOfMonth();

        return $query->whereRaw('DATE(COALESCE(tgl_masuk, created_at)) between ? and ?', [
            $mulai->toDateString(),
            $akhir->toDateString(),
        ]);
    }

    private function filterPeriodeClosingDashboard($query, array $periode)
    {
        if ($periode['semua']) {
            return $query;
        }

        $mulai = Carbon::create($periode['tahun'], $periode['bulan'], 1)->startOfMonth();
        $akhir = $mulai->copy()->endOfMonth();

        return $query->whereRaw('DATE(COALESCE(tanggal_daftar, updated_at)) between ? and ?', [
            $mulai->toDateString(),
            $akhir->toDateString(),
        ]);
    }

    private function konteksDashboard(Request $request): array
    {
        $user = $request->user();

        return match ($user->role) {
            'admin' => [
                'role' => 'admin',
                'labelRole' => 'Admin',
                'judul' => 'Dashboard Semua User',
                'deskripsi' => 'Ringkasan data leads, follow up, dan closing dari seluruh user. Gunakan filter untuk melihat cabang atau staff tertentu.',
                'panelJudul' => 'Performa Cabang',
                'panelSubjudul' => 'Perbandingan semua cabang berdasarkan leads masuk.',
                'cabangTerkunci' => null,
                'bolehFilterCabang' => true,
                'bolehFilterAdmin' => true,
                'bolehFilterStaff' => true,
                'tipePanel' => 'cabang',
            ],
            'staff' => [
                'role' => 'staff',
                'labelRole' => 'Staff',
                'judul' => 'Dashboard Semua User',
                'deskripsi' => 'Ringkasan data leads, follow up, dan closing dari seluruh user. Gunakan filter untuk melihat data pribadi atau staff tertentu.',
                'panelJudul' => 'Performa Cabang',
                'panelSubjudul' => 'Perbandingan semua cabang berdasarkan leads masuk.',
                'cabangTerkunci' => null,
                'bolehFilterCabang' => true,
                'bolehFilterAdmin' => true,
                'bolehFilterStaff' => true,
                'tipePanel' => 'cabang',
            ],
            'direksi' => [
                'role' => 'direksi',
                'labelRole' => 'Direksi',
                'judul' => 'Ringkasan Semua Cabang',
                'deskripsi' => 'Pantau total leads, follow up, closing, dan kontribusi setiap cabang.',
                'panelJudul' => 'Performa Cabang',
                'panelSubjudul' => 'Perbandingan semua cabang berdasarkan leads masuk.',
                'cabangTerkunci' => null,
                'bolehFilterCabang' => true,
                'bolehFilterAdmin' => true,
                'bolehFilterStaff' => true,
                'tipePanel' => 'cabang',
            ],
            default => [
                'role' => 'superadmin',
                'labelRole' => 'Superadmin',
                'judul' => 'Kontrol Semua Cabang',
                'deskripsi' => 'Akses penuh untuk memantau performa semua cabang dan seluruh user.',
                'panelJudul' => 'Performa Cabang',
                'panelSubjudul' => 'Perbandingan semua cabang berdasarkan leads masuk.',
                'cabangTerkunci' => null,
                'bolehFilterCabang' => true,
                'bolehFilterAdmin' => true,
                'bolehFilterStaff' => true,
                'tipePanel' => 'cabang',
            ],
        };
    }

    private function performaDashboardRole($query, array $konteks): array
    {
        if ($konteks['tipePanel'] === 'pribadi') {
            return $this->performaPribadiDashboard($query, $konteks);
        }

        if ($konteks['tipePanel'] === 'tim') {
            return $this->performaTimDashboard($query, $konteks);
        }

        return $this->performaCabangDashboard($query, $konteks);
    }

    private function dashboardClosing($query): array
    {
        $total = (clone $query)->count();
        $nominal = (clone $query)->sum('nominal_pembayaran');
        $perProgram = (clone $query)
            ->selectRaw("COALESCE(program_final, program, 'Tanpa Program') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(6)
            ->get();
        $perCabang = (clone $query)
            ->selectRaw("COALESCE(cabang, 'Tanpa Cabang') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();
        $perPembayaran = (clone $query)
            ->selectRaw("COALESCE(status_pembayaran, 'Belum Diisi') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        return compact('total', 'nominal', 'perProgram', 'perCabang', 'perPembayaran');
    }

    private function kpiOperasionalDashboard(Request $request, $query, array $periode): array
    {
        $queryAktif = $this->queryLeadsAktif(clone $query);
        $totalAktif = (clone $queryAktif)->count();
        $sudahFollowUp = (clone $queryAktif)->whereHas('followUps')->count();
        $belumFollowUp = max(0, $totalAktif - $sudahFollowUp);
        $followUpTerlambat = FollowUp::query()
            ->whereNotNull('tanggal_follow_up_berikutnya')
            ->whereDate('tanggal_follow_up_berikutnya', '<', now()->toDateString())
            ->whereNotIn('hasil', ['Closing', 'Tidak tertarik'])
            ->whereHas('prospek', function ($query) use ($request, $periode) {
                $this->filterPeriodeDashboard($this->filterAksesDashboard($query, $request), $periode)
                    ->whereNotIn('status', ['Daftar', 'Tidak Tertarik']);
            })
            ->distinct('prospek_id')
            ->count('prospek_id');

        return [
            'totalAktif' => $totalAktif,
            'sudahFollowUp' => $sudahFollowUp,
            'belumFollowUp' => $belumFollowUp,
            'followUpTerlambat' => $followUpTerlambat,
            'followUpRate' => $totalAktif > 0 ? round(($sudahFollowUp / $totalAktif) * 100, 1) : 0,
        ];
    }

    private function agingLeadsDashboard($query)
    {
        $batas = [
            '0-1 hari' => [0, 1],
            '2-3 hari' => [2, 3],
            '4-7 hari' => [4, 7],
            '> 7 hari' => [8, null],
        ];
        $items = array_fill_keys(array_keys($batas), 0);

        $this->queryLeadsAktif(clone $query)
            ->get(['tgl_masuk', 'created_at'])
            ->each(function (Prospek $prospek) use ($batas, &$items) {
                $tanggal = $prospek->tgl_masuk ?: $prospek->created_at;
                $umurHari = $tanggal ? $tanggal->startOfDay()->diffInDays(now()->startOfDay()) : 0;

                foreach ($batas as $label => [$minimal, $maksimal]) {
                    if ($umurHari >= $minimal && ($maksimal === null || $umurHari <= $maksimal)) {
                        $items[$label]++;
                        break;
                    }
                }
            });

        $maks = max(1, max($items));

        return collect($items)->map(fn ($total, $label) => [
            'label' => $label,
            'total' => $total,
            'persen' => round(($total / $maks) * 100),
        ])->values();
    }

    private function performaInputUserDashboard(Request $request, array $periode)
    {
        $queryLeads = $this->filterPeriodeDashboard($this->queryDashboardPerRole($request), $periode);
        $queryClosing = $this->filterPeriodeClosingDashboard($this->queryDashboardPerRole($request), $periode)
            ->where('status', 'Daftar');
        $leads = (clone $queryLeads)
            ->selectRaw('COALESCE(created_by, 0) as user_id, COUNT(*) as total')
            ->groupByRaw('COALESCE(created_by, 0)')
            ->pluck('total', 'user_id');
        $closing = (clone $queryClosing)
            ->selectRaw('COALESCE(created_by, 0) as user_id, COUNT(*) as total')
            ->groupByRaw('COALESCE(created_by, 0)')
            ->pluck('total', 'user_id');
        $userIds = $leads->keys()
            ->merge($closing->keys())
            ->filter(fn ($id) => (int) $id > 0)
            ->unique()
            ->values()
            ->all();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        return $leads->keys()
            ->merge($closing->keys())
            ->unique()
            ->values()
            ->map(function ($userId) use ($leads, $closing, $users) {
                $userId = (int) $userId;
                $totalLeads = (int) ($leads[$userId] ?? 0);
                $totalClosing = (int) ($closing[$userId] ?? 0);
                $basis = max(1, $totalLeads);

                return [
                    'label' => $userId > 0 ? ($users->get($userId)?->name ?: 'User tidak aktif') : 'Tanpa user input',
                    'leads' => $totalLeads,
                    'closing' => $totalClosing,
                    'rasio' => round(($totalClosing / $basis) * 100, 1),
                    'persen' => min(100, round(($totalClosing / $basis) * 100, 1)),
                ];
            })
            ->sortByDesc('closing')
            ->sortByDesc('rasio')
            ->values()
            ->take(8);
    }

    private function konversiSumberDashboard(Request $request, array $periode)
    {
        $queryLeads = $this->filterPeriodeDashboard($this->queryDashboardPerRole($request), $periode);
        $queryClosing = $this->filterPeriodeClosingDashboard($this->queryDashboardPerRole($request), $periode)
            ->where('status', 'Daftar');
        $leads = (clone $queryLeads)
            ->selectRaw("COALESCE(sumber, 'Tanpa Sumber') as label, COUNT(*) as total")
            ->groupByRaw("COALESCE(sumber, 'Tanpa Sumber')")
            ->pluck('total', 'label');
        $closing = (clone $queryClosing)
            ->selectRaw("COALESCE(sumber, 'Tanpa Sumber') as label, COUNT(*) as total")
            ->groupByRaw("COALESCE(sumber, 'Tanpa Sumber')")
            ->pluck('total', 'label');

        return $leads->keys()
            ->merge($closing->keys())
            ->unique()
            ->values()
            ->map(function ($label) use ($leads, $closing) {
                $totalLeads = (int) ($leads[$label] ?? 0);
                $totalClosing = (int) ($closing[$label] ?? 0);
                $basis = max(1, $totalLeads);

                return [
                    'label' => $label,
                    'leads' => $totalLeads,
                    'closing' => $totalClosing,
                    'rasio' => round(($totalClosing / $basis) * 100, 1),
                    'persen' => min(100, round(($totalClosing / $basis) * 100, 1)),
                ];
            })
            ->sortByDesc('closing')
            ->sortByDesc('rasio')
            ->values()
            ->take(8);
    }

    private function targetKinerjaDashboard(Request $request, array $periode, array $konteks, int $leadsAktif, int $closing): array
    {
        $target = $this->targetUntukKonteks($request, $periode, $konteks);
        $basisKonversi = $leadsAktif + $closing;
        $rasioKonversi = $basisKonversi > 0 ? round(($closing / $basisKonversi) * 100, 1) : 0;

        return [
            'targetLeads' => $target['target_leads'],
            'targetClosing' => $target['target_closing'],
            'leadsAktif' => $leadsAktif,
            'closing' => $closing,
            'persenLeads' => $target['target_leads'] > 0 ? min(100, round(($leadsAktif / $target['target_leads']) * 100, 1)) : 0,
            'persenClosing' => $target['target_closing'] > 0 ? min(100, round(($closing / $target['target_closing']) * 100, 1)) : 0,
            'rasioKonversi' => $rasioKonversi,
            'labelTarget' => $target['label'],
        ];
    }

    private function targetUntukKonteks(Request $request, array $periode, array $konteks): array
    {
        if ($periode['semua']) {
            return [
                'target_leads' => 0,
                'target_closing' => 0,
                'label' => 'Target bulanan tidak diterapkan untuk semua data',
            ];
        }

        $query = TargetKinerja::query()
            ->where('bulan', $periode['bulan'])
            ->where('tahun', $periode['tahun']);

        if ($request->filled('staff')) {
            $target = (clone $query)->where('tipe', 'staff')->where('user_id', $request->staff)->first();

            return [
                'target_leads' => (int) ($target?->target_leads ?? 0),
                'target_closing' => (int) ($target?->target_closing ?? 0),
                'label' => 'Target staff terpilih',
            ];
        }

        $cabang = $konteks['cabangTerkunci'] ?? ($request->string('cabang')->toString() ?: null);

        if ($cabang) {
            $target = (clone $query)->where('tipe', 'cabang')->where('cabang', $cabang)->first();

            return [
                'target_leads' => (int) ($target?->target_leads ?? 0),
                'target_closing' => (int) ($target?->target_closing ?? 0),
                'label' => 'Target cabang '.$cabang,
            ];
        }

        return [
            'target_leads' => (int) (clone $query)->where('tipe', 'cabang')->sum('target_leads'),
            'target_closing' => (int) (clone $query)->where('tipe', 'cabang')->sum('target_closing'),
            'label' => 'Akumulasi target semua cabang',
        ];
    }

    private function rankingKinerjaDashboard(Request $request, array $periode, array $konteks): array
    {
        if ($konteks['tipePanel'] === 'cabang') {
            return $this->rankingCabangDashboard($request, $periode, $konteks);
        }

        return $this->rankingStaffDashboard($request, $periode, $konteks);
    }

    private function rankingCabangDashboard(Request $request, array $periode, array $konteks): array
    {
        $queryLeads = $this->filterPeriodeDashboard($this->queryDashboardPerRole($request), $periode);
        $queryClosing = $this->filterPeriodeClosingDashboard($this->queryDashboardPerRole($request), $periode)
            ->where('status', 'Daftar');
        $closing = (clone $queryClosing)
            ->selectRaw("COALESCE(cabang, 'Tanpa Cabang') as label, COUNT(*) as total")
            ->groupBy('label')
            ->pluck('total', 'label');
        $targets = $periode['semua']
            ? collect()
            : TargetKinerja::query()
                ->where('bulan', $periode['bulan'])
                ->where('tahun', $periode['tahun'])
                ->where('tipe', 'cabang')
                ->get()
                ->keyBy('cabang');
        $leads = (clone $queryLeads)
            ->selectRaw("COALESCE(cabang, 'Tanpa Cabang') as label, COUNT(*) as total")
            ->groupBy('label')
            ->pluck('total', 'label');
        $labels = $leads->keys()
            ->merge($closing->keys())
            ->merge($targets->keys())
            ->filter()
            ->unique()
            ->values();
        $items = $labels
            ->map(function ($label) use ($leads, $closing, $targets) {
                $closingTotal = (int) ($closing[$label] ?? 0);
                $leadsTotal = (int) ($leads[$label] ?? 0);
                $target = $targets->get($label);
                $targetClosing = (int) ($target?->target_closing ?? 0);
                $rasio = ($leadsTotal + $closingTotal) > 0 ? round(($closingTotal / ($leadsTotal + $closingTotal)) * 100, 1) : 0;

                return [
                    'label' => $label,
                    'leads' => $leadsTotal,
                    'closing' => $closingTotal,
                    'target_leads' => (int) ($target?->target_leads ?? 0),
                    'target_closing' => $targetClosing,
                    'rasio' => $rasio,
                    'skor' => $targetClosing > 0 ? round(($closingTotal / $targetClosing) * 100, 1) : $closingTotal,
                ];
            })
            ->sortByDesc('skor')
            ->values()
            ->take(8);

        return [
            'judul' => 'Ranking Cabang',
            'subjudul' => 'Diurutkan dari capaian closing terhadap target.',
            'items' => $items,
        ];
    }

    private function rankingStaffDashboard(Request $request, array $periode, array $konteks): array
    {
        $queryLeads = $this->filterPeriodeDashboard($this->queryDashboardPerRole($request), $periode);
        $queryClosing = $this->filterPeriodeClosingDashboard($this->queryDashboardPerRole($request), $periode)
            ->where('status', 'Daftar');
        $closing = (clone $queryClosing)
            ->selectRaw('COALESCE(user_id, 0) as user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');
        $leads = (clone $queryLeads)
            ->selectRaw('COALESCE(user_id, 0) as user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');
        $targets = collect();

        if (! $periode['semua']) {
            $targetQuery = TargetKinerja::query()
                ->where('bulan', $periode['bulan'])
                ->where('tahun', $periode['tahun'])
                ->where('tipe', 'staff');

            if ($request->user()->role === 'staff') {
                $targetQuery->where('user_id', $request->user()->id);
            } elseif ($request->filled('staff')) {
                $targetQuery->where('user_id', $request->staff);
            } elseif ($konteks['cabangTerkunci']) {
                $targetQuery->where('cabang', $konteks['cabangTerkunci']);
            } elseif ($request->filled('cabang')) {
                $targetQuery->where('cabang', $request->cabang);
            }

            $targets = $targetQuery->get()
                ->keyBy('user_id');
        }
        $userIds = $leads->keys()
            ->merge($closing->keys())
            ->merge($targets->keys())
            ->filter(fn ($id) => (int) $id > 0)
            ->unique()
            ->values()
            ->all();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $items = collect($leads->keys())
            ->merge($closing->keys())
            ->merge($targets->keys())
            ->unique()
            ->values()
            ->map(function ($userId) use ($leads, $closing, $users, $targets) {
                $userId = (int) $userId;
                $closingTotal = (int) ($closing[$userId] ?? 0);
                $leadsTotal = (int) ($leads[$userId] ?? 0);
                $target = $targets->get($userId);
                $targetClosing = (int) ($target?->target_closing ?? 0);
                $rasio = ($leadsTotal + $closingTotal) > 0 ? round(($closingTotal / ($leadsTotal + $closingTotal)) * 100, 1) : 0;
                $user = $users->get($userId);

                return [
                    'label' => $userId > 0 ? ($user?->name ?: 'User tidak aktif') : 'Belum ditugaskan',
                    'leads' => $leadsTotal,
                    'closing' => $closingTotal,
                    'target_leads' => (int) ($target?->target_leads ?? 0),
                    'target_closing' => $targetClosing,
                    'rasio' => $rasio,
                    'skor' => $targetClosing > 0 ? round(($closingTotal / $targetClosing) * 100, 1) : $closingTotal,
                ];
            })
            ->sortByDesc('skor')
            ->values()
            ->take(8);

        return [
            'judul' => $konteks['tipePanel'] === 'pribadi' ? 'Capaian Pribadi' : 'Ranking Staff',
            'subjudul' => 'Diurutkan dari capaian closing terhadap target.',
            'items' => $items,
        ];
    }

    private function performaPribadiDashboard($query, array $konteks): array
    {
        $queryAktif = $this->queryLeadsAktif(clone $query);
        $total = (clone $queryAktif)->count();
        $followUp = (clone $queryAktif)->whereIn('status', ['Dihubungi', 'Follow Up'])->count();
        $closing = (clone $query)->where('status', 'Daftar')->count();
        $baru = (clone $queryAktif)->where('status', 'Baru')->count();
        $basisRasio = $total + $closing;
        $rasio = $basisRasio > 0 ? round(($closing / $basisRasio) * 100) : 0;
        $maks = max(1, $total, $closing);

        return [
            'judul' => $konteks['panelJudul'],
            'subjudul' => $konteks['panelSubjudul'],
            'items' => collect([
                ['label' => 'Leads Baru', 'total' => $baru, 'closing' => 0, 'follow_up' => 0, 'persen' => round(($baru / $maks) * 100)],
                ['label' => 'Butuh Follow Up', 'total' => $followUp, 'closing' => 0, 'follow_up' => $followUp, 'persen' => round(($followUp / $maks) * 100)],
                ['label' => 'Closing', 'total' => $closing, 'closing' => $closing, 'follow_up' => 0, 'persen' => round(($closing / $maks) * 100)],
                ['label' => 'Rasio Closing', 'total' => $rasio, 'closing' => $closing, 'follow_up' => $followUp, 'persen' => min(100, $rasio), 'satuan' => '%'],
            ]),
        ];
    }

    private function performaTimDashboard($query, array $konteks): array
    {
        $queryAktif = $this->queryLeadsAktif(clone $query);
        $closingPerUser = (clone $query)
            ->where('status', 'Daftar')
            ->selectRaw('COALESCE(user_id, 0) as user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');
        $items = (clone $queryAktif)
            ->selectRaw('COALESCE(user_id, 0) as user_id, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN ("Dihubungi", "Follow Up") THEN 1 ELSE 0 END) as follow_up')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
        $namaUser = User::query()
            ->whereIn('id', $items->pluck('user_id')->filter()->all())
            ->pluck('name', 'id');
        $maks = max(1, (int) $items->max('total'));

        return [
            'judul' => $konteks['panelJudul'],
            'subjudul' => $konteks['panelSubjudul'],
            'items' => $items->map(fn ($item) => [
                'label' => (int) $item->user_id > 0 ? ($namaUser[$item->user_id] ?? 'User tidak aktif') : 'Belum ditugaskan',
                'total' => (int) $item->total,
                'closing' => (int) ($closingPerUser[$item->user_id] ?? 0),
                'follow_up' => (int) $item->follow_up,
                'persen' => round(((int) $item->total / $maks) * 100),
            ]),
        ];
    }

    private function performaCabangDashboard($query, array $konteks): array
    {
        $queryAktif = $this->queryLeadsAktif(clone $query);
        $closingPerCabang = (clone $query)
            ->where('status', 'Daftar')
            ->selectRaw('COALESCE(cabang, "Tanpa Cabang") as label, COUNT(*) as total')
            ->groupBy('label')
            ->pluck('total', 'label');
        $items = (clone $queryAktif)
            ->selectRaw('COALESCE(cabang, "Tanpa Cabang") as label, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN ("Dihubungi", "Follow Up") THEN 1 ELSE 0 END) as follow_up')
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();
        $maks = max(1, (int) $items->max('total'));

        return [
            'judul' => $konteks['panelJudul'],
            'subjudul' => $konteks['panelSubjudul'],
            'items' => $items->map(fn ($item) => [
                'label' => $item->label,
                'total' => (int) $item->total,
                'closing' => (int) ($closingPerCabang[$item->label] ?? 0),
                'follow_up' => (int) $item->follow_up,
                'persen' => round(((int) $item->total / $maks) * 100),
            ]),
        ];
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

        return $query
            ->when($request->filled('cabang'), fn ($query) => $query->where('cabang', $request->cabang))
            ->when($request->filled('admin'), fn ($query) => $query->where('diserahkan_ke', $request->admin))
            ->when($request->filled('staff'), fn ($query) => $query->where('user_id', $request->staff));
    }

    private function queryAksesDataSiswa(Request $request)
    {
        return $this->queryAksesDashboard($request)
            ->where('status', 'Daftar');
    }

    private function reminderFollowUp(Request $request)
    {
        return FollowUp::query()
            ->with(['prospek.penanggungJawab', 'user'])
            ->whereNotNull('tanggal_follow_up_berikutnya')
            ->whereDate('tanggal_follow_up_berikutnya', '<=', now()->toDateString())
            ->whereNotIn('hasil', ['Closing', 'Tidak tertarik'])
            ->whereHas('prospek', fn ($query) => $this->filterAksesDashboard($query, $request)
                ->whereNotIn('status', ['Daftar', 'Tidak Tertarik']))
            ->latest('tanggal_follow_up_berikutnya')
            ->limit(10)
            ->get();
    }

    private function buatNotifikasiReminderFollowUp(Request $request): void
    {
        $this->reminderFollowUp($request)
            ->filter(fn ($item) => $item->prospek)
            ->each(function (FollowUp $followUp) {
                $prospek = $followUp->prospek;
                $terlambat = $followUp->tanggal_follow_up_berikutnya?->isPast()
                    && ! $followUp->tanggal_follow_up_berikutnya?->isToday();
                $penerima = collect();

                if ($prospek->user_id) {
                    $penerima = $penerima->push(User::find($prospek->user_id));
                }

                $penerima = $penerima->merge(SistemNotification::penerimaCabang($prospek->cabang));

                SistemNotification::kirimSekali($penerima, [
                    'tipe' => 'follow_up_reminder',
                    'judul' => $terlambat ? 'Follow up terlambat' : 'Follow up hari ini',
                    'pesan' => "Leads {$prospek->nama} perlu di-follow up pada ".$followUp->tanggal_follow_up_berikutnya?->format('d M Y').'. Hasil terakhir: '.$followUp->hasil.'.',
                    'tautan' => route('prospek.show', $prospek),
                    'prioritas' => $terlambat ? 'Tinggi' : 'Normal',
                ]);
            });
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
        if ($periode['semua']) {
            return $this->grafikLeadsSemuaPeriode($query);
        }

        $mulai = Carbon::create($periode['tahun'], $periode['bulan'], 1)->startOfMonth();
        $akhir = $mulai->copy()->endOfMonth();
        $jumlahLead = $this->queryLeadsAktif(clone $query)
            ->selectRaw('DATE(COALESCE(tgl_masuk, created_at)) as tanggal, COUNT(*) as total')
            ->whereRaw('DATE(COALESCE(tgl_masuk, created_at)) between ? and ?', [
                $mulai->toDateString(),
                $akhir->toDateString(),
            ])
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');
        $jumlahClosing = (clone $query)
            ->where('status', 'Daftar')
            ->selectRaw('DATE(COALESCE(tanggal_daftar, updated_at)) as tanggal, COUNT(*) as total')
            ->whereRaw('DATE(COALESCE(tanggal_daftar, updated_at)) between ? and ?', [
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

    private function grafikLeadsSemuaPeriode($query): array
    {
        $jumlahLead = $this->queryLeadsAktif(clone $query)
            ->selectRaw('DATE(COALESCE(tgl_masuk, created_at)) as tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');
        $jumlahClosing = (clone $query)
            ->where('status', 'Daftar')
            ->selectRaw('DATE(COALESCE(tanggal_daftar, updated_at)) as tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');
        $tanggalItems = $jumlahLead->keys()
            ->merge($jumlahClosing->keys())
            ->filter()
            ->sort()
            ->values();

        if ($tanggalItems->isEmpty()) {
            $hari = collect([now()->toDateString()])
                ->map(fn ($tanggal) => [
                    'tanggal' => $tanggal,
                    'nomor' => Carbon::parse($tanggal)->translatedFormat('d M'),
                    'lead' => 0,
                    'closing' => 0,
                ])
                ->all();
        } else {
            $hari = $tanggalItems
                ->map(fn ($tanggal) => [
                    'tanggal' => $tanggal,
                    'nomor' => Carbon::parse($tanggal)->translatedFormat('d M'),
                    'lead' => (int) ($jumlahLead[$tanggal] ?? 0),
                    'closing' => (int) ($jumlahClosing[$tanggal] ?? 0),
                ])
                ->all();
        }

        $maksData = (int) collect($hari)->max(fn ($item) => max($item['lead'], $item['closing']));
        $skalaGrafik = $this->skalaGrafikHarian($maksData);
        $maks = $skalaGrafik['maks'];
        $tinggi = 170;
        $lebar = max(1000, count($hari) * 40);
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
            'bulan' => 'Semua data',
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
        $semua = ! $request->filled('bulan') || $request->input('bulan') === 'semua';
        $bulan = $semua ? now()->month : (int) $request->input('bulan');
        $tahun = (int) $request->input('tahun', now()->year);

        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        if ($tahun < 2000 || $tahun > now()->year + 1) {
            $tahun = now()->year;
        }

        return compact('bulan', 'tahun', 'semua');
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

    private function daftarAdminCabang(?string $cabangTertentu = null): array
    {
        $cabang = $cabangTertentu ? [$cabangTertentu] : $this->daftarCabang();

        return collect($cabang)
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

        if ($prospek->bisaDiubahOleh($user)) {
            return;
        }

        abort(403);
    }

    private function pastikanBolehLihat(Prospek $prospek): void
    {
        abort_unless(request()->user()?->bisaLihatSemuaLeads(), 403);
    }

    private function pastikanBolehUbah(): void
    {
        abort_unless(request()->user()?->bisaInputLeads(), 403);
    }

    private function pastikanBolehFollowUp(): void
    {
        abort_unless(request()->user()?->bisaFollowUpLeads(), 403);
    }

    private function pastikanBolehHapus(): void
    {
        abort_unless(request()->user()?->bisaHapusLeads(), 403);
    }

    private function staffTersedia(?string $cabang = null, bool $batasiAkses = true)
    {
        $user = request()->user();

        return User::query()
            ->where('aktif', true)
            ->where('role', 'staff')
            ->when($batasiAkses && ! $user->aksesSemuaCabang(), fn ($query) => $query->where('cabang', $user->cabang))
            ->when($cabang, fn ($query) => $query->where('cabang', $cabang))
            ->orderBy('name')
            ->get();
    }

    private function catatRiwayatStatus(Prospek $prospek, ?string $statusLama, string $statusBaru, ?User $user, string $sumber, ?string $catatan = null): void
    {
        ProspekStatusHistory::create([
            'prospek_id' => $prospek->id,
            'user_id' => $user?->id,
            'status_lama' => $statusLama,
            'status_baru' => $statusBaru,
            'sumber' => $sumber,
            'catatan' => $catatan,
        ]);
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
        $sekolahDatabase = Schema::hasTable('sekolah')
            ? Sekolah::query()->pluck('nama_sekolah')->all()
            : [];

        return collect($this->sekolahJson())
            ->merge($sekolahDatabase)
            ->filter()
            ->map(fn ($nama) => trim((string) $nama))
            ->filter()
            ->unique(fn ($nama) => $this->normalisasiNamaSekolah($nama))
            ->sort()
            ->values()
            ->all();
    }

    private function sekolahJson(): array
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

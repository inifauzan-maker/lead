<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\ProgramLead;
use App\Models\Prospek;
use App\Models\SumberLead;
use App\Models\SistemNotification;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = 'password';

        foreach (['Bandung', 'Jaksel', 'Jakpus'] as $nama) {
            Cabang::updateOrCreate(['nama' => $nama], ['aktif' => true]);
        }

        foreach (['Iklan', 'Instagram', 'Tiktok', 'Facebook', 'Kunjungan', 'Website'] as $nama) {
            SumberLead::updateOrCreate(['nama' => $nama], ['aktif' => true]);
        }

        foreach (['SR GOLD', 'SR ONLINE', 'AR GOLD', 'AR ONLINE', 'MINAT SENI', 'MINAT ARSI'] as $nama) {
            ProgramLead::updateOrCreate(['nama' => $nama], ['aktif' => true]);
        }

        WhatsappTemplate::updateOrCreate(
            ['nama' => 'Follow up awal'],
            [
                'isi_pesan' => "Halo {nama}, kami dari CRM_SIVMI cabang {cabang} ingin follow up minat program {program}.\n\nApakah masih berkenan kami bantu informasinya?",
                'aktif' => true,
                'urutan' => 1,
            ],
        );

        User::where('email', 'admin.jaktim@leads.test')->update([
            'name' => 'Admin Jaksel',
            'email' => 'admin.jaksel@leads.test',
            'cabang' => 'Jaksel',
        ]);

        $akun = [
            ['Superadmin', 'superadmin@leads.test', 'superadmin', null],
            ['Admin Bandung', 'admin.bandung@leads.test', 'admin', 'Bandung'],
            ['Admin Jaksel', 'admin.jaksel@leads.test', 'admin', 'Jaksel'],
            ['Admin Jakpus', 'admin.jakpus@leads.test', 'admin', 'Jakpus'],
            ['Staff Bandung', 'staff.bandung@leads.test', 'staff', 'Bandung'],
            ['Staff Jaksel', 'staff.jaksel@leads.test', 'staff', 'Jaksel'],
            ['Staff Jakpus', 'staff.jakpus@leads.test', 'staff', 'Jakpus'],
            ['Direksi', 'direksi@leads.test', 'direksi', null],
        ];

        foreach ($akun as [$name, $email, $role, $cabang]) {
            User::updateOrCreate(
                ['email' => $email],
                compact('name', 'password', 'role', 'cabang') + ['aktif' => true],
            );
        }

        $adminBandung = User::query()->where('email', 'admin.bandung@leads.test')->first();

        foreach ([
            ['Dasar Pengelolaan Leads', 'Alur dasar input, validasi, distribusi, dan follow up leads.', 'Wajib', 45, 1, ['Mengenal Leads', 'Input Data Leads', 'Mencegah Input Ganda']],
            ['Strategi Follow Up Efektif', 'Teknik komunikasi, pencatatan hasil, dan penjadwalan ulang follow up.', 'Sales', 80, 2, ['Persiapan Follow Up', 'Script WhatsApp', 'Evaluasi Hasil']],
            ['Membaca Laporan Cabang', 'Cara membaca dashboard, grafik harian, sumber leads, dan performa cabang.', 'Admin', 35, 3, ['Dashboard Cabang', 'Rasio Closing', 'Evaluasi Staff']],
            ['Etika Komunikasi Orang Tua', 'Standar komunikasi dengan orang tua dan calon siswa.', 'Staff', 55, 4, ['Etika Dasar', 'Menjawab Keberatan', 'Follow Up Lanjutan']],
        ] as [$judul, $deskripsi, $level, $durasi, $urutan, $lessons]) {
            $courseData = compact('deskripsi', 'level') + [
                'durasi_menit' => $durasi,
                'urutan' => $urutan,
                'aktif' => true,
            ];

            if (Schema::hasColumn('courses', 'level_id')) {
                $courseData['level_id'] = 1;
            }

            if (Schema::hasColumn('courses', 'created_by')) {
                $courseData['created_by'] = $adminBandung?->id ?? 1;
            }

            if (Schema::hasColumn('courses', 'title')) {
                $courseData['title'] = $judul;
            }

            if (Schema::hasColumn('courses', 'description')) {
                $courseData['description'] = $deskripsi;
            }

            if (Schema::hasColumn('courses', 'youtube_url')) {
                $courseData['youtube_url'] = '';
            }

            if (Schema::hasColumn('courses', 'is_published')) {
                $courseData['is_published'] = true;
            }

            $course = Course::unguarded(fn () => Course::updateOrCreate(['judul' => $judul], $courseData));

            foreach ($lessons as $index => $lesson) {
                CourseLesson::updateOrCreate(
                    ['course_id' => $course->id, 'judul' => $lesson],
                    [
                        'konten' => 'Materi '.$lesson.' untuk '.$judul.'.',
                        'durasi_menit' => max(10, (int) floor($durasi / max(1, count($lessons)))),
                        'urutan' => $index + 1,
                        'aktif' => true,
                    ],
                );
            }
        }

        if ($adminBandung) {
            foreach ([
                ['Follow up leads prioritas Bandung', 'Hubungi leads baru yang belum mendapatkan respons.', 'Baru', 'Tinggi', now()->toDateString()],
                ['Review hasil follow up minggu ini', 'Rekap hasil follow up dan tandai peluang closing.', 'Proses', 'Normal', now()->addDays(3)->toDateString()],
            ] as [$judul, $deskripsi, $status, $prioritas, $tenggat]) {
                Task::updateOrCreate(
                    ['judul' => $judul, 'assigned_to' => $adminBandung->id],
                    [
                        'deskripsi' => $deskripsi,
                        'status' => $status,
                        'prioritas' => $prioritas,
                        'tenggat' => $tenggat,
                        'created_by' => $adminBandung->id,
                        'cabang' => 'Bandung',
                    ],
                );
            }
        }

        $staffByCabang = User::query()
            ->where('role', 'staff')
            ->whereIn('cabang', ['Bandung', 'Jaksel', 'Jakpus'])
            ->get()
            ->keyBy('cabang');
        $adminByCabang = [
            'Bandung' => 'Admin Bandung',
            'Jaksel' => 'Admin Jaksel',
            'Jakpus' => 'Admin Jakpus',
        ];
        $contohBulanan = [
            [1, 'Bandung', 'Iklan', 'SR GOLD', 'Baru', 7, null],
            [1, 'Jaksel', 'Instagram', 'AR ONLINE', 'Daftar', 18, 2500000],
            [2, 'Jakpus', 'Tiktok', 'SR ONLINE', 'Follow Up', 9, null],
            [2, 'Bandung', 'Website', 'AR GOLD', 'Daftar', 21, 1800000],
            [3, 'Jaksel', 'Facebook', 'MINAT SENI', 'Dihubungi', 5, null],
            [3, 'Jakpus', 'Iklan', 'SR GOLD', 'Daftar', 25, 3000000],
            [4, 'Bandung', 'Instagram', 'AR ONLINE', 'Follow Up', 8, null],
            [4, 'Jaksel', 'Kunjungan', 'SR ONLINE', 'Daftar', 20, 2200000],
            [5, 'Jakpus', 'Website', 'MINAT ARSI', 'Baru', 11, null],
            [5, 'Bandung', 'Tiktok', 'SR GOLD', 'Daftar', 24, 2700000],
            [6, 'Jaksel', 'Iklan', 'AR GOLD', 'Follow Up', 6, null],
            [6, 'Jakpus', 'Instagram', 'SR ONLINE', 'Daftar', 19, 2400000],
            [7, 'Bandung', 'Facebook', 'MINAT SENI', 'Baru', 4, null],
            [7, 'Jaksel', 'Website', 'AR ONLINE', 'Daftar', 22, 2100000],
            [8, 'Jakpus', 'Kunjungan', 'SR GOLD', 'Dihubungi', 10, null],
            [8, 'Bandung', 'Iklan', 'SR ONLINE', 'Daftar', 26, 2900000],
            [9, 'Jaksel', 'Tiktok', 'MINAT ARSI', 'Follow Up', 12, null],
            [9, 'Jakpus', 'Facebook', 'AR GOLD', 'Daftar', 23, 2300000],
            [10, 'Bandung', 'Website', 'SR GOLD', 'Baru', 3, null],
            [10, 'Jaksel', 'Instagram', 'SR ONLINE', 'Daftar', 17, 2600000],
            [11, 'Jakpus', 'Iklan', 'AR ONLINE', 'Follow Up', 13, null],
            [11, 'Bandung', 'Kunjungan', 'MINAT SENI', 'Daftar', 28, 2000000],
            [12, 'Jaksel', 'Facebook', 'SR GOLD', 'Dihubungi', 6, null],
            [12, 'Jakpus', 'Website', 'AR GOLD', 'Daftar', 18, 3100000],
        ];

        foreach ($contohBulanan as $index => [$bulan, $cabang, $sumber, $program, $status, $hari, $nominal]) {
            $tanggalMasuk = sprintf('2026-%02d-%02d', $bulan, $hari);
            $tanggalDaftar = $status === 'Daftar'
                ? sprintf('2026-%02d-%02d', $bulan, min($hari + 3, 28))
                : null;
            $nomorWa = '62812026'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $staff = $staffByCabang->get($cabang);

            Prospek::updateOrCreate(
                ['no_wa' => $nomorWa],
                [
                    'nama' => 'Lead Bulanan '.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'asal_sekolah' => 'SMA Contoh '.(($index % 6) + 1),
                    'jenjang' => 'SMA',
                    'kelas' => ['X', 'XI', 'XII'][$index % 3],
                    'kota_asal' => $cabang === 'Bandung' ? 'Bandung' : 'Jakarta',
                    'program' => $program,
                    'program_final' => $status === 'Daftar' ? $program : null,
                    'status' => $status,
                    'status_pembayaran' => $status === 'Daftar' ? ['DP', 'Lunas', 'Cicilan'][$index % 3] : null,
                    'nominal_pembayaran' => $nominal,
                    'kelas_angkatan' => $status === 'Daftar' ? 'Angkatan 2026' : null,
                    'cabang' => $cabang,
                    'user_id' => $staff?->id,
                    'created_by' => $staff?->id,
                    'diserahkan_ke' => $adminByCabang[$cabang],
                    'sumber' => $sumber,
                    'keterangan' => 'Data contoh untuk grafik bulanan dashboard KPI.',
                    'tgl_masuk' => $tanggalMasuk,
                    'tanggal_daftar' => $tanggalDaftar,
                ],
            );
        }

        $contohTahunan = [
            [2023, 'Bandung', 'Iklan', 'SR GOLD', 9, 3],
            [2024, 'Jaksel', 'Instagram', 'AR ONLINE', 14, 5],
            [2025, 'Jakpus', 'Website', 'SR ONLINE', 18, 7],
        ];

        foreach ($contohTahunan as [$tahun, $cabang, $sumber, $program, $totalLead, $totalClosing]) {
            $staff = $staffByCabang->get($cabang);

            for ($index = 1; $index <= $totalLead; $index++) {
                $status = $index <= $totalClosing ? 'Daftar' : (['Baru', 'Dihubungi', 'Follow Up'][$index % 3]);
                $bulan = (($index - 1) % 12) + 1;
                $hari = min(28, 4 + $index);
                $tanggalMasuk = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);
                $tanggalDaftar = $status === 'Daftar'
                    ? sprintf('%04d-%02d-%02d', $tahun, $bulan, min(28, $hari + 4))
                    : null;
                $nomorWa = '62813'.$tahun.str_pad((string) $index, 3, '0', STR_PAD_LEFT);

                Prospek::updateOrCreate(
                    ['no_wa' => $nomorWa],
                    [
                        'nama' => "Lead Tahunan {$tahun}-".str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                        'asal_sekolah' => 'SMA Tahunan '.(($index % 5) + 1),
                        'jenjang' => 'SMA',
                        'kelas' => ['X', 'XI', 'XII'][$index % 3],
                        'kota_asal' => $cabang === 'Bandung' ? 'Bandung' : 'Jakarta',
                        'program' => $program,
                        'program_final' => $status === 'Daftar' ? $program : null,
                        'status' => $status,
                        'status_pembayaran' => $status === 'Daftar' ? ['DP', 'Lunas', 'Cicilan'][$index % 3] : null,
                        'nominal_pembayaran' => $status === 'Daftar' ? 1750000 + ($index * 100000) : null,
                        'kelas_angkatan' => $status === 'Daftar' ? 'Angkatan '.$tahun : null,
                        'cabang' => $cabang,
                        'user_id' => $staff?->id,
                        'created_by' => $staff?->id,
                        'diserahkan_ke' => $adminByCabang[$cabang],
                        'sumber' => $sumber,
                        'keterangan' => 'Data contoh untuk grafik tahunan dashboard KPI.',
                        'tgl_masuk' => $tanggalMasuk,
                        'tanggal_daftar' => $tanggalDaftar,
                    ],
                );
            }
        }

        SistemNotification::updateOrCreate(
            ['judul' => 'Sistem siap digunakan', 'user_id' => null],
            [
                'tipe' => 'info',
                'pesan' => 'Data master, course awal, dan task awal sudah tersedia.',
                'tautan' => '/profil',
                'prioritas' => 'Normal',
                'dibaca_pada' => null,
            ],
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\ProgramLead;
use App\Models\SumberLead;
use App\Models\SistemNotification;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            ['Direksi', 'direksi@leads.test', 'direksi', null],
        ];

        foreach ($akun as [$name, $email, $role, $cabang]) {
            User::updateOrCreate(
                ['email' => $email],
                compact('name', 'password', 'role', 'cabang') + ['aktif' => true],
            );
        }

        $staffBandung = User::query()->where('email', 'staff.bandung@leads.test')->first();
        $adminBandung = User::query()->where('email', 'admin.bandung@leads.test')->first();

        foreach ([
            ['Dasar Pengelolaan Leads', 'Alur dasar input, validasi, distribusi, dan follow up leads.', 'Wajib', 45, 1, ['Mengenal Leads', 'Input Data Leads', 'Mencegah Input Ganda']],
            ['Strategi Follow Up Efektif', 'Teknik komunikasi, pencatatan hasil, dan penjadwalan ulang follow up.', 'Sales', 80, 2, ['Persiapan Follow Up', 'Script WhatsApp', 'Evaluasi Hasil']],
            ['Membaca Laporan Cabang', 'Cara membaca dashboard, grafik harian, sumber leads, dan performa cabang.', 'Admin', 35, 3, ['Dashboard Cabang', 'Rasio Closing', 'Evaluasi Staff']],
            ['Etika Komunikasi Orang Tua', 'Standar komunikasi dengan orang tua dan calon siswa.', 'Staff', 55, 4, ['Etika Dasar', 'Menjawab Keberatan', 'Follow Up Lanjutan']],
        ] as [$judul, $deskripsi, $level, $durasi, $urutan, $lessons]) {
            $course = Course::updateOrCreate(
                ['judul' => $judul],
                compact('deskripsi', 'level') + [
                    'durasi_menit' => $durasi,
                    'urutan' => $urutan,
                    'aktif' => true,
                ],
            );

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

        if ($staffBandung && $adminBandung) {
            foreach ([
                ['Follow up leads prioritas Bandung', 'Hubungi leads baru yang belum mendapatkan respons.', 'Baru', 'Tinggi', now()->toDateString()],
                ['Review hasil follow up minggu ini', 'Rekap hasil follow up dan tandai peluang closing.', 'Proses', 'Normal', now()->addDays(3)->toDateString()],
            ] as [$judul, $deskripsi, $status, $prioritas, $tenggat]) {
                Task::updateOrCreate(
                    ['judul' => $judul, 'assigned_to' => $staffBandung->id],
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

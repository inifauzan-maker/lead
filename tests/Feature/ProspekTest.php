<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Course;
use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\SistemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProspekTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_bisa_memperbarui_cabang_leads(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);

        $prospek = Prospek::create([
            'nama' => 'Johan',
            'status' => 'Baru',
            'cabang' => null,
            'sumber' => 'Instagram',
            'tgl_masuk' => '2030-05-26',
        ]);

        $this->actingAs($superadmin)
            ->put(route('prospek.update', $prospek), [
                'nama' => 'Johan',
                'asal_sekolah' => 'SMAI Al Azhar 1',
                'kelas' => null,
                'kota_asal' => 'Jakarta Selatan',
                'no_wa' => null,
                'program' => 'SR GOLD',
                'status' => 'Baru',
                'cabang' => 'Jaksel',
                'user_id' => null,
                'diserahkan_ke' => 'Admin Jaksel',
                'sumber' => 'Instagram',
                'keterangan' => null,
                'tgl_masuk' => '2030-05-26',
            ])
            ->assertRedirect(route('prospek.index'));

        $this->assertDatabaseHas('prospek', [
            'id' => $prospek->id,
            'cabang' => 'Jaksel',
            'diserahkan_ke' => 'Admin Jaksel',
        ]);
    }

    public function test_admin_bisa_menghapus_banyak_leads_di_cabangnya(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        $leadBandungSatu = Prospek::create([
            'nama' => 'Lead Bandung Satu',
            'status' => 'Baru',
            'cabang' => 'Bandung',
        ]);
        $leadBandungDua = Prospek::create([
            'nama' => 'Lead Bandung Dua',
            'status' => 'Baru',
            'cabang' => 'Bandung',
        ]);
        $leadJaksel = Prospek::create([
            'nama' => 'Lead Jaksel',
            'status' => 'Baru',
            'cabang' => 'Jaksel',
        ]);

        $this->actingAs($admin)
            ->post(route('prospek.aksi-massal'), [
                'aksi' => 'hapus',
                'ids' => [$leadBandungSatu->id, $leadBandungDua->id, $leadJaksel->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('prospek', ['id' => $leadBandungSatu->id]);
        $this->assertDatabaseMissing('prospek', ['id' => $leadBandungDua->id]);
        $this->assertDatabaseHas('prospek', ['id' => $leadJaksel->id]);
    }

    public function test_import_csv_menampilkan_preview_error_per_baris(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);
        Prospek::create([
            'nama' => 'Lead Lama',
            'status' => 'Baru',
            'cabang' => 'Bandung',
            'no_wa' => '081111111111',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'leads-import-');
        file_put_contents($path, implode("\n", [
            'nama,asal_sekolah,kelas,kota_asal,no_wa,program,status,cabang,diserahkan_ke,sumber,keterangan,tgl_masuk',
            'Lead Valid,SMA 1,12,Bandung,082222222222,SR GOLD,Baru,Bandung,Admin Bandung,Instagram,Valid,2026-06-13',
            'Lead Duplikat,SMA 2,12,Bandung,081111111111,SR GOLD,Baru,Bandung,Admin Bandung,Instagram,Duplikat,2026-06-13',
            'Lead Cabang,SMA 3,12,Bandung,083333333333,SR GOLD,Baru,Cabang Salah,Admin Bandung,Instagram,Cabang salah,2026-06-13',
            'Lead Duplikat File,SMA 4,12,Bandung,082222222222,SR GOLD,Baru,Bandung,Admin Bandung,Instagram,Duplikat file,2026-06-13',
        ]));

        $response = $this->actingAs($superadmin)
            ->post(route('prospek.import'), [
                'file_import' => new UploadedFile($path, 'leads.csv', 'text/csv', null, true),
            ]);

        $response->assertRedirect()
            ->assertSessionHas('error_import');

        $errorImport = session('error_import');

        $this->assertDatabaseHas('prospek', [
            'nama' => 'Lead Valid',
            'no_wa' => '082222222222',
        ]);
        $this->assertCount(3, $errorImport);
        $this->assertSame(3, $errorImport[0]['baris']);
        $this->assertStringContainsString('Nomor WA duplikat', implode(' ', $errorImport[0]['alasan']));
        $this->assertSame(4, $errorImport[1]['baris']);
        $this->assertStringContainsString('Cabang tidak valid', implode(' ', $errorImport[1]['alasan']));
        $this->assertSame(5, $errorImport[2]['baris']);
        $this->assertStringContainsString('Nomor WA duplikat di file import', implode(' ', $errorImport[2]['alasan']));
    }

    public function test_matriks_hak_akses_edit_leads_sesuai_role(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'aktif' => true]);
        $adminBandung = User::factory()->create(['role' => 'admin', 'cabang' => 'Bandung', 'aktif' => true]);
        $leaderBandung = User::factory()->create(['role' => 'leader', 'cabang' => 'Bandung', 'aktif' => true]);
        $staffBandung = User::factory()->create(['role' => 'staff', 'cabang' => 'Bandung', 'aktif' => true]);
        $staffLain = User::factory()->create(['role' => 'staff', 'cabang' => 'Bandung', 'aktif' => true]);
        $direksi = User::factory()->create(['role' => 'direksi', 'aktif' => true]);

        $leadBandung = Prospek::create([
            'nama' => 'Lead Bandung',
            'status' => 'Baru',
            'cabang' => 'Bandung',
            'user_id' => $staffBandung->id,
        ]);
        $leadJaksel = Prospek::create([
            'nama' => 'Lead Jaksel',
            'status' => 'Baru',
            'cabang' => 'Jaksel',
            'user_id' => $staffLain->id,
        ]);

        $this->assertTrue($leadBandung->bisaDiubahOleh($superadmin));
        $this->assertTrue($leadJaksel->bisaDiubahOleh($superadmin));
        $this->assertTrue($leadBandung->bisaDiubahOleh($adminBandung));
        $this->assertFalse($leadJaksel->bisaDiubahOleh($adminBandung));
        $this->assertTrue($leadBandung->bisaDiubahOleh($leaderBandung));
        $this->assertFalse($leadJaksel->bisaDiubahOleh($leaderBandung));
        $this->assertTrue($leadBandung->bisaDiubahOleh($staffBandung));
        $this->assertFalse($leadBandung->bisaDiubahOleh($staffLain));
        $this->assertFalse($leadBandung->bisaDiubahOleh($direksi));
    }

    public function test_semua_role_login_bisa_melihat_detail_leads(): void
    {
        $lead = Prospek::create([
            'nama' => 'Lead Bisa Dilihat',
            'status' => 'Baru',
            'cabang' => 'Jaksel',
        ]);

        foreach (['superadmin', 'admin', 'leader', 'staff', 'direksi'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'cabang' => 'Bandung',
                'aktif' => true,
            ]);

            $this->actingAs($user)
                ->get(route('prospek.show', $lead))
                ->assertOk()
                ->assertSee('Lead Bisa Dilihat');
        }
    }

    public function test_superadmin_bisa_export_backup_sql(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);

        Prospek::create([
            'nama' => 'Lead Backup',
            'status' => 'Baru',
            'cabang' => 'Bandung',
        ]);

        $response = $this->actingAs($superadmin)
            ->get(route('pengaturan.backup.export'))
            ->assertOk();

        $konten = $response->streamedContent();

        $this->assertStringContainsString('Backup CRM_SIVMI', $konten);
        $this->assertStringContainsString('DELETE FROM `prospek`;', $konten);
        $this->assertStringContainsString('INSERT INTO `prospek`', $konten);
        $this->assertStringContainsString('Lead Backup', $konten);
    }

    public function test_staff_tidak_bisa_export_backup_sql(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('pengaturan.backup.export'))
            ->assertForbidden();
    }

    public function test_menu_follow_up_menampilkan_leads_yang_sudah_difollow_up(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        Prospek::create([
            'nama' => 'Lead Sudah Dihubungi',
            'status' => 'Dihubungi',
            'cabang' => 'Bandung',
        ]);
        Prospek::create([
            'nama' => 'Lead Perlu Follow Up',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
        ]);
        Prospek::create([
            'nama' => 'Lead Baru',
            'status' => 'Baru',
            'cabang' => 'Bandung',
        ]);

        $this->actingAs($admin)
            ->get(route('follow-up.index'))
            ->assertOk()
            ->assertSee('Catat Aktivitas Follow Up')
            ->assertSee('Lead Sudah Dihubungi')
            ->assertSee('Lead Perlu Follow Up')
            ->assertSee('Lead Baru');
    }

    public function test_user_bisa_mencatat_aktivitas_follow_up(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $prospek = Prospek::create([
            'nama' => 'Lead Baru Follow Up',
            'status' => 'Baru',
            'cabang' => 'Bandung',
            'user_id' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->post(route('follow-up.store'), [
                'prospek_id' => $prospek->id,
                'tanggal_follow_up' => '2026-06-07 10:00:00',
                'metode' => 'WhatsApp',
                'hasil' => 'Berminat',
                'catatan' => 'Minta dikirimkan brosur.',
                'tindak_lanjut' => 'Kirim reminder pendaftaran.',
                'tanggal_follow_up_berikutnya' => '2026-06-08',
                'prioritas' => 'Tinggi',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('follow_ups', [
            'prospek_id' => $prospek->id,
            'user_id' => $staff->id,
            'hasil' => 'Berminat',
            'prioritas' => 'Tinggi',
        ]);
        $this->assertDatabaseHas('prospek', [
            'id' => $prospek->id,
            'status' => 'Follow Up',
        ]);
    }

    public function test_follow_up_menampilkan_jumlah_hasil_jadwal_overdue_dan_notifikasi(): void
    {
        Carbon::setTestNow('2026-06-13 08:00:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $staff = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $prospek = Prospek::create([
            'nama' => 'Lead Overdue',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
            'user_id' => $staff->id,
        ]);

        FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id' => $staff->id,
            'tanggal_follow_up' => '2026-06-10 09:00:00',
            'metode' => 'WhatsApp',
            'hasil' => 'Tersambung',
            'tindak_lanjut' => 'Kirim ulang brosur.',
            'tanggal_follow_up_berikutnya' => '2026-06-12',
            'prioritas' => 'Tinggi',
        ]);
        FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id' => $staff->id,
            'tanggal_follow_up' => '2026-06-11 09:00:00',
            'metode' => 'Telepon',
            'hasil' => 'Berminat',
            'tindak_lanjut' => 'Hubungi orang tua.',
            'tanggal_follow_up_berikutnya' => '2026-06-14',
            'prioritas' => 'Normal',
        ]);

        $this->actingAs($admin)
            ->get(route('follow-up.index'))
            ->assertOk()
            ->assertSee('Lead Overdue')
            ->assertSee('2')
            ->assertSee('Berminat')
            ->assertSee('12 Jun 2026')
            ->assertSee('Overdue')
            ->assertSee('Kirim ulang brosur.');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $staff->id,
            'tipe' => 'follow_up_reminder',
            'judul' => 'Follow up terlambat',
            'prioritas' => 'Tinggi',
        ]);

        Carbon::setTestNow();
    }

    public function test_menu_data_siswa_menampilkan_leads_yang_sudah_daftar(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        Prospek::create([
            'nama' => 'Siswa Closing',
            'status' => 'Daftar',
            'cabang' => 'Bandung',
        ]);
        Prospek::create([
            'nama' => 'Lead Follow Up',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
        ]);

        $this->actingAs($admin)
            ->get(route('data-siswa.index'))
            ->assertOk()
            ->assertSee('Siswa Closing')
            ->assertDontSee('Lead Follow Up');
    }

    public function test_dashboard_menghitung_leads_aktif_dan_closing_masuk_data_siswa(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        Prospek::create([
            'nama' => 'Lead Aktif',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
            'tgl_masuk' => '2026-06-13',
        ]);
        Prospek::create([
            'nama' => 'Siswa Closing Satu',
            'status' => 'Daftar',
            'cabang' => 'Bandung',
            'tgl_masuk' => '2026-06-13',
        ]);
        Prospek::create([
            'nama' => 'Siswa Closing Dua',
            'status' => 'Daftar',
            'cabang' => 'Bandung',
            'tgl_masuk' => '2026-06-13',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard', ['bulan' => 6, 'tahun' => 2026]))
            ->assertOk()
            ->assertSee('Total Leads Aktif')
            ->assertSeeInOrder(['Total Leads Aktif', '1', 'Leads Baru', '0', 'Butuh Follow Up', '1', 'Closing', '2']);

        $this->actingAs($admin)
            ->get(route('data-siswa.index'))
            ->assertOk()
            ->assertSee('Siswa Closing Satu')
            ->assertSee('Siswa Closing Dua')
            ->assertDontSee('Lead Aktif');
    }

    public function test_user_bisa_membuka_dan_memperbarui_profil(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@leads.test',
            'password' => 'password',
            'role' => 'superadmin',
            'aktif' => true,
        ]);

        $this->actingAs($user)
            ->get(route('profil.index'))
            ->assertOk()
            ->assertSee('Profil User')
            ->assertSee('Dashboard Personal')
            ->assertSee('TIM')
            ->assertSee('Tugas')
            ->assertSee('Laporan')
            ->assertSee('Pembelajaran');

        $this->actingAs($user)
            ->put(route('profil.update'), [
                'name' => 'Nama Baru',
                'email' => 'baru@leads.test',
                'facebook' => 'https://facebook.com/nama-baru',
                'instagram' => 'https://instagram.com/nama-baru',
                'tiktok' => 'https://tiktok.com/@nama-baru',
                'blog' => 'https://blog.example.com',
                'youtube' => 'https://youtube.com/@nama-baru',
                'password_lama' => 'password',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@leads.test', $user->email);
        $this->assertSame('https://facebook.com/nama-baru', $user->facebook);
        $this->assertSame('https://instagram.com/nama-baru', $user->instagram);
        $this->assertSame('https://tiktok.com/@nama-baru', $user->tiktok);
        $this->assertSame('https://blog.example.com', $user->blog);
        $this->assertSame('https://youtube.com/@nama-baru', $user->youtube);
        $this->assertTrue(Hash::check('password-baru', $user->password));
    }

    public function test_menu_modul_baru_bisa_dibuka(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);

        $this->actingAs($user)->get(route('profil.tim'))->assertOk()->assertSee('Kolaborasi Cabang');
        $this->actingAs($user)->get(route('profil.tugas'))->assertOk()->assertSee('Task Management');
        $this->actingAs($user)->get(route('profil.laporan'))->assertOk()->assertSee('Report Center');
        $this->actingAs($user)->get(route('profil.pembelajaran'))->assertOk()->assertSee('Online Course');
    }

    public function test_user_bisa_membuat_tugas_dan_update_progress_course(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $course = Course::create([
            'judul' => 'Course Test',
            'level' => 'Wajib',
            'durasi_menit' => 30,
            'aktif' => true,
        ]);

        $this->actingAs($user)
            ->post(route('profil.tugas.store'), [
                'judul' => 'Hubungi leads prioritas',
                'deskripsi' => 'Pastikan sudah follow up hari ini.',
                'status' => 'Baru',
                'prioritas' => 'Tinggi',
                'tenggat' => '2026-06-08',
                'assigned_to' => $user->id,
                'cabang' => 'Bandung',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'judul' => 'Hubungi leads prioritas',
            'assigned_to' => $user->id,
            'cabang' => 'Bandung',
        ]);

        $this->actingAs($user)
            ->put(route('profil.pembelajaran.progress', $course), [
                'progress_persen' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('course_progress', [
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 'Selesai',
            'progress_persen' => 100,
        ]);
    }

    public function test_user_bisa_menandai_notifikasi_dibaca(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'aktif' => true,
        ]);
        $notifikasi = SistemNotification::create([
            'user_id' => $user->id,
            'judul' => 'Tugas baru',
            'pesan' => 'Ada tugas baru untuk Anda.',
        ]);

        $this->actingAs($user)
            ->get(route('notifikasi.index'))
            ->assertOk()
            ->assertSee('Tugas baru');

        $this->actingAs($user)
            ->put(route('notifikasi.baca', $notifikasi))
            ->assertRedirect();

        $this->assertNotNull($notifikasi->refresh()->dibaca_pada);
    }

    public function test_superadmin_bisa_mengelola_pengaturan_master(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);

        $this->actingAs($user)
            ->get(route('pengaturan.index'))
            ->assertOk()
            ->assertSee('CRUD Cabang')
            ->assertSee('Manajemen Role User');

        $this->actingAs($user)
            ->post(route('pengaturan.cabang.store'), [
                'nama' => 'Bekasi',
                'aktif' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cabang', [
            'nama' => 'Bekasi',
            'aktif' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pengaturan.user.store'), [
                'name' => 'Staff Bekasi',
                'email' => 'staff.bekasi@leads.test',
                'password' => 'password',
                'role' => 'staff',
                'cabang' => 'Bekasi',
                'aktif' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Staff Bekasi',
            'email' => 'staff.bekasi@leads.test',
            'role' => 'staff',
            'cabang' => 'Bekasi',
            'aktif' => true,
        ]);
    }

    public function test_manajemen_role_user_menggunakan_cabang_master(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);
        $staff = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        Cabang::create([
            'nama' => 'Bekasi',
            'aktif' => true,
        ]);

        $this->actingAs($superadmin)
            ->put(route('pengaturan.user-role.update', $staff), [
                'role' => 'leader',
                'cabang' => 'Bekasi',
                'aktif' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'role' => 'leader',
            'cabang' => 'Bekasi',
            'aktif' => true,
        ]);
    }
}

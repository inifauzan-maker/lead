<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Course;
use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\SistemNotification;
use App\Models\Task;
use App\Models\TargetKinerja;
use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProspekTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_tidak_bisa_input_edit_import_atau_follow_up_leads(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);
        $prospek = Prospek::create([
            'nama' => 'Johan',
            'status' => 'Baru',
            'cabang' => 'Jaksel',
            'sumber' => 'Instagram',
            'tgl_masuk' => '2030-05-26',
        ]);

        $this->actingAs($superadmin)
            ->get(route('prospek.create'))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->put(route('prospek.update', $prospek), [
                'nama' => 'Johan',
                'asal_sekolah' => 'SMAI Al Azhar 1',
                'jenjang' => null,
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
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->post(route('follow-up.store'), [
                'prospek_id' => $prospek->id,
                'tanggal_follow_up' => '2026-06-13 10:00:00',
                'metode' => 'WhatsApp',
                'hasil' => 'Berminat',
                'tanggal_follow_up_berikutnya' => null,
                'prioritas' => 'Normal',
            ])
            ->assertForbidden();

        $path = tempnam(sys_get_temp_dir(), 'leads-import-');
        file_put_contents($path, implode("\n", [
            'nama,asal_sekolah,jenjang,kelas,kota_asal,no_wa,program,status,cabang,diserahkan_ke,sumber,keterangan,tgl_masuk',
            'Lead Superadmin,SMAN 1 Bandung,SMA,XII,Bandung,089999999999,SR GOLD,Baru,Bandung,Admin Bandung,Instagram,Valid,2026-06-13',
        ]));

        $this->actingAs($superadmin)
            ->post(route('prospek.import'), [
                'file_import' => new UploadedFile($path, 'leads.csv', 'text/csv', null, true),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('prospek', [
            'nama' => 'Lead Superadmin',
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
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
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
            'nama,asal_sekolah,jenjang,kelas,kota_asal,no_wa,program,status,cabang,diserahkan_ke,sumber,keterangan,tgl_masuk',
            'Lead Valid,SMAN 1 Bandung,SMA,XII,Bandung,082222222222,SR GOLD,Baru,Bandung,Admin Bandung,Instagram,Valid,2026-06-13',
            'Lead Duplikat,SMAN 2 Bandung,SMA,XII,Bandung,081111111111,SR GOLD,Baru,Bandung,Admin Bandung,Instagram,Duplikat,2026-06-13',
            'Lead Cabang,SMAN 3 Bandung,SMA,XII,Bandung,083333333333,SR GOLD,Baru,Cabang Salah,Admin Bandung,Instagram,Cabang salah,2026-06-13',
            'Lead Duplikat File,SMAN 4 Bandung,SMA,XII,Bandung,082222222222,SR GOLD,Baru,Bandung,Admin Bandung,Instagram,Duplikat file,2026-06-13',
        ]));

        $response = $this->actingAs($admin)
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
        $this->assertStringContainsString('Cabang tidak sesuai akses user', implode(' ', $errorImport[1]['alasan']));
        $this->assertSame(5, $errorImport[2]['baris']);
        $this->assertStringContainsString('Nomor WA duplikat di file import', implode(' ', $errorImport[2]['alasan']));
    }

    public function test_input_leads_menerapkan_format_asal_sekolah_dan_jenjang(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('prospek.store'), [
                'nama' => 'Lead Format Salah',
                'asal_sekolah' => 'SMA 1 Bandung',
                'jenjang' => 'SMA',
                'kelas' => 'XII',
                'kota_asal' => 'Bandung',
                'no_wa' => '087700000001',
                'program' => 'SR GOLD',
                'status' => 'Baru',
                'cabang' => 'Bandung',
                'user_id' => null,
                'diserahkan_ke' => null,
                'sumber' => 'Instagram',
                'keterangan' => null,
                'tgl_masuk' => '2026-06-13',
            ])
            ->assertSessionHasErrors('asal_sekolah');

        $this->actingAs($admin)
            ->post(route('prospek.store'), [
                'nama' => 'Lead Format Benar',
                'asal_sekolah' => 'sma swasta al azhar 1',
                'jenjang' => 'SMA',
                'kelas' => 'XII',
                'kota_asal' => 'Bandung',
                'no_wa' => '087700000002',
                'program' => 'SR GOLD',
                'status' => 'Baru',
                'cabang' => 'Bandung',
                'user_id' => null,
                'diserahkan_ke' => null,
                'sumber' => 'Instagram',
                'keterangan' => null,
                'tgl_masuk' => '2026-06-13',
            ])
            ->assertRedirect(route('prospek.index'));

        $this->assertDatabaseHas('prospek', [
            'nama' => 'Lead Format Benar',
            'asal_sekolah' => 'SMAS Al Azhar 1',
            'jenjang' => 'SMA',
            'kelas' => 'XII',
        ]);
    }

    public function test_asal_sekolah_manual_yang_tidak_ada_di_json_masuk_master_sekolah(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('prospek.store'), [
                'nama' => 'Lead Sekolah Baru',
                'asal_sekolah' => 'sma swasta citra baru nusantara',
                'jenjang' => 'SMA',
                'kelas' => 'XII',
                'kota_asal' => 'Bandung',
                'no_wa' => '087700000003',
                'program' => 'SR GOLD',
                'status' => 'Baru',
                'cabang' => 'Bandung',
                'user_id' => null,
                'diserahkan_ke' => null,
                'sumber' => 'Instagram',
                'keterangan' => null,
                'tgl_masuk' => '2026-06-13',
            ])
            ->assertRedirect(route('prospek.index'));

        $this->assertDatabaseHas('sekolah', [
            'nama_sekolah' => 'SMAS Citra Baru Nusantara',
            'nama_normalized' => 'smas citra baru nusantara',
            'sumber' => 'manual',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('prospek.create'))
            ->assertOk()
            ->assertSee('SMAS Citra Baru Nusantara');
    }

    public function test_asal_sekolah_dari_json_tidak_diduplikasi_ke_master_sekolah(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('prospek.store'), [
                'nama' => 'Lead Sekolah Json',
                'asal_sekolah' => 'SMAN 1 Bandung',
                'jenjang' => 'SMA',
                'kelas' => 'XII',
                'kota_asal' => 'Bandung',
                'no_wa' => '087700000004',
                'program' => 'SR GOLD',
                'status' => 'Baru',
                'cabang' => 'Bandung',
                'user_id' => null,
                'diserahkan_ke' => null,
                'sumber' => 'Instagram',
                'keterangan' => null,
                'tgl_masuk' => '2026-06-13',
            ])
            ->assertRedirect(route('prospek.index'));

        $this->assertSame(0, Sekolah::query()->where('nama_normalized', 'sman 1 bandung')->count());
    }

    public function test_matriks_hak_akses_edit_leads_sesuai_role(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'aktif' => true]);
        $adminBandung = User::factory()->create(['role' => 'admin', 'cabang' => 'Bandung', 'aktif' => true]);
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

        $this->assertFalse($leadBandung->bisaDiubahOleh($superadmin));
        $this->assertFalse($leadJaksel->bisaDiubahOleh($superadmin));
        $this->assertTrue($leadBandung->bisaDiubahOleh($adminBandung));
        $this->assertFalse($leadJaksel->bisaDiubahOleh($adminBandung));
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

        foreach (['superadmin', 'admin', 'staff', 'direksi'] as $role) {
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

    public function test_link_whatsapp_web_hanya_muncul_untuk_user_yang_boleh_melihat_nomor_asli(): void
    {
        $staffPemilik = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $adminCabang = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $lead = Prospek::create([
            'nama' => 'Lead WhatsApp',
            'status' => 'Baru',
            'cabang' => 'Bandung',
            'user_id' => $staffPemilik->id,
            'no_wa' => '081234567890',
        ]);

        $this->actingAs($staffPemilik)
            ->get(route('prospek.show', $lead))
            ->assertOk()
            ->assertSee('WA Web')
            ->assertSee('https://web.whatsapp.com/send?phone=6281234567890');

        $this->actingAs($adminCabang)
            ->get(route('prospek.show', $lead))
            ->assertOk()
            ->assertSee('0812345678xx')
            ->assertDontSee('WA Web')
            ->assertDontSee('web.whatsapp.com/send');
    }

    public function test_link_whatsapp_web_memakai_template_aktif(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
            'name' => 'Staff Follow Up',
        ]);
        $lead = Prospek::create([
            'nama' => 'Lead Template',
            'status' => 'Baru',
            'cabang' => 'Bandung',
            'user_id' => $staff->id,
            'no_wa' => '081234567890',
            'program' => 'SR GOLD',
        ]);
        WhatsappTemplate::create([
            'nama' => 'Template Test',
            'isi_pesan' => 'Halo {nama}, info {program} kelas {kelas} dari {user}.',
            'aktif' => true,
            'urutan' => 1,
        ]);

        $this->actingAs($staff)
            ->get(route('prospek.show', $lead))
            ->assertOk()
            ->assertSee('WA Web')
            ->assertSee('Halo%20Lead%20Template%2C%20info%20SR%20GOLD%20kelas%20-%20dari%20Staff%20Follow%20Up.', false);
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

    public function test_admin_bisa_mencatat_aktivitas_follow_up(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $prospek = Prospek::create([
            'nama' => 'Lead Baru Follow Up',
            'status' => 'Baru',
            'cabang' => 'Bandung',
        ]);

        $this->actingAs($admin)
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
            'user_id' => $admin->id,
            'hasil' => 'Berminat',
            'prioritas' => 'Tinggi',
        ]);
        $this->assertDatabaseHas('prospek', [
            'id' => $prospek->id,
            'status' => 'Follow Up',
        ]);
    }

    public function test_staff_tidak_bisa_follow_up_atau_hapus_leads(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $prospek = Prospek::create([
            'nama' => 'Lead Staff Terbatas',
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
                'tanggal_follow_up_berikutnya' => null,
                'prioritas' => 'Normal',
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->delete(route('prospek.destroy', $prospek))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('prospek.aksi-massal'), [
                'aksi' => 'hapus',
                'ids' => [$prospek->id],
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('follow-up.index'))
            ->assertOk()
            ->assertDontSee('Catat Aktivitas Follow Up');

        $this->actingAs($staff)
            ->get(route('prospek.index'))
            ->assertOk()
            ->assertDontSee('Hapus leads?')
            ->assertDontSee('Hapus terpilih');

        $this->assertDatabaseHas('prospek', [
            'id' => $prospek->id,
        ]);
        $this->assertDatabaseMissing('follow_ups', [
            'prospek_id' => $prospek->id,
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

    public function test_update_status_daftar_mengisi_data_siswa_dan_dashboard_closing(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        $prospek = Prospek::create([
            'nama' => 'Lead Jadi Siswa',
            'asal_sekolah' => 'SMAN 1 Bandung',
            'kota_asal' => 'Bandung',
            'no_wa' => '081234567890',
            'program' => 'SR GOLD',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
            'sumber' => 'Instagram',
            'tgl_masuk' => '2026-06-10',
            'user_id' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('prospek.update', $prospek), [
                'nama' => 'Lead Jadi Siswa',
                'asal_sekolah' => 'SMAN 1 Bandung',
                'jenjang' => 'SMA',
                'kelas' => 'XII',
                'kota_asal' => 'Bandung',
                'no_wa' => '081234567890',
                'program' => 'SR GOLD',
                'status' => 'Daftar',
                'cabang' => 'Bandung',
                'user_id' => null,
                'diserahkan_ke' => null,
                'sumber' => 'Instagram',
                'keterangan' => null,
                'tgl_masuk' => '2026-06-10',
                'tanggal_daftar' => '2026-06-12',
                'program_final' => 'SR GOLD',
                'nominal_pembayaran' => 1500000,
                'status_pembayaran' => 'DP',
                'kelas_angkatan' => 'Angkatan 2026',
                'catatan_administrasi' => 'Sudah kirim bukti pembayaran.',
            ])
            ->assertRedirect(route('prospek.index'));

        $prospek->refresh();

        $this->assertSame('Daftar', $prospek->status);
        $this->assertSame('2026-06-12', $prospek->tanggal_daftar?->toDateString());
        $this->assertSame('SR GOLD', $prospek->program_final);
        $this->assertSame('DP', $prospek->status_pembayaran);
        $this->assertSame('Angkatan 2026', $prospek->kelas_angkatan);
        $this->assertDatabaseHas('prospek_status_histories', [
            'prospek_id' => $prospek->id,
            'user_id' => $admin->id,
            'status_lama' => 'Follow Up',
            'status_baru' => 'Daftar',
            'sumber' => 'manual',
        ]);

        $this->actingAs($admin)
            ->get(route('prospek.index'))
            ->assertOk()
            ->assertDontSee('Lead Jadi Siswa');

        $this->actingAs($admin)
            ->get(route('data-siswa.index'))
            ->assertOk()
            ->assertSee('Lead Jadi Siswa')
            ->assertSee('DP')
            ->assertSee('Rp 1.500.000')
            ->assertSee('12 Jun 2026')
            ->assertSee('Angkatan 2026');

        $this->actingAs($admin)
            ->get(route('data-siswa.show', $prospek))
            ->assertOk()
            ->assertSee('Detail Data Siswa')
            ->assertSee('Riwayat Perubahan Status')
            ->assertSee('Follow Up -> Daftar', false)
            ->assertSee('Status diperbarui dari form leads.');

        $this->actingAs($admin)
            ->get(route('dashboard', ['bulan' => 6, 'tahun' => 2026]))
            ->assertOk()
            ->assertSee('Dashboard Closing')
            ->assertSee('Status Pembayaran Closing')
            ->assertSee('Rp 1.500.000')
            ->assertSee('DP');
    }

    public function test_data_leads_tidak_menampilkan_leads_yang_sudah_daftar(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        Prospek::create([
            'nama' => 'Siswa Sudah Closing',
            'status' => 'Daftar',
            'cabang' => 'Bandung',
        ]);
        Prospek::create([
            'nama' => 'Lead Masih Aktif',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('prospek.index'))
            ->assertOk()
            ->assertSee('Lead Masih Aktif')
            ->assertSee($admin->name)
            ->assertDontSee('Siswa Sudah Closing');
    }

    public function test_data_leads_bisa_difilter_berdasarkan_user_input(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $inputBandung = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
            'name' => 'Admin Input Bandung',
        ]);
        $inputJakpus = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Jakpus',
            'aktif' => true,
            'name' => 'Staff Input Jakpus',
        ]);

        Prospek::create([
            'nama' => 'Lead Input Bandung',
            'status' => 'Baru',
            'cabang' => 'Bandung',
            'created_by' => $inputBandung->id,
        ]);
        Prospek::create([
            'nama' => 'Lead Input Jakpus',
            'status' => 'Baru',
            'cabang' => 'Jakpus',
            'created_by' => $inputJakpus->id,
        ]);

        $this->actingAs($admin)
            ->get(route('prospek.index', ['created_by' => $inputBandung->id]))
            ->assertOk()
            ->assertSee('Lead Input Bandung')
            ->assertSee('Admin Input Bandung')
            ->assertDontSee('Lead Input Jakpus');
    }

    public function test_follow_up_closing_mencatat_riwayat_status_data_siswa(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $prospek = Prospek::create([
            'nama' => 'Lead Closing Follow Up',
            'status' => 'Follow Up',
            'program' => 'AR ONLINE',
            'cabang' => 'Bandung',
        ]);

        $this->actingAs($admin)
            ->post(route('follow-up.store'), [
                'prospek_id' => $prospek->id,
                'tanggal_follow_up' => '2026-06-13 10:00:00',
                'metode' => 'WhatsApp',
                'hasil' => 'Closing',
                'catatan' => 'Sudah daftar.',
                'tindak_lanjut' => null,
                'tanggal_follow_up_berikutnya' => null,
                'prioritas' => 'Tinggi',
            ])
            ->assertRedirect();

        $prospek->refresh();

        $this->assertSame('Daftar', $prospek->status);
        $this->assertSame('AR ONLINE', $prospek->program_final);
        $this->assertSame('Belum Bayar', $prospek->status_pembayaran);
        $this->assertDatabaseHas('prospek_status_histories', [
            'prospek_id' => $prospek->id,
            'user_id' => $admin->id,
            'status_lama' => 'Follow Up',
            'status_baru' => 'Daftar',
            'sumber' => 'follow_up',
        ]);

        $this->actingAs($admin)
            ->get(route('data-siswa.show', $prospek))
            ->assertOk()
            ->assertSee('Lead Closing Follow Up')
            ->assertSee('Follow Up -> Daftar', false)
            ->assertSee('Hasil follow up: Closing');
    }

    public function test_admin_bisa_export_data_siswa_sesuai_filter(): void
    {
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

        Prospek::create([
            'nama' => 'Siswa Export',
            'asal_sekolah' => 'SMAN 1 Bandung',
            'jenjang' => 'SMA',
            'kelas' => 'XII',
            'kelas_angkatan' => 'Angkatan 2026',
            'kota_asal' => 'Bandung',
            'no_wa' => '081234567890',
            'program' => 'SR GOLD',
            'program_final' => 'SR GOLD',
            'status' => 'Daftar',
            'status_pembayaran' => 'Lunas',
            'nominal_pembayaran' => 2500000,
            'cabang' => 'Bandung',
            'sumber' => 'Instagram',
            'user_id' => $staff->id,
            'tgl_masuk' => '2026-06-01',
            'tanggal_daftar' => '2026-06-12',
            'catatan_administrasi' => 'Lengkap.',
        ]);
        Prospek::create([
            'nama' => 'Lead Aktif Export',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('data-siswa.export', ['status_pembayaran' => 'Lunas']))
            ->assertOk();

        $konten = $response->streamedContent();

        $this->assertStringContainsString('tanggal_closing', $konten);
        $this->assertStringContainsString('Siswa Export', $konten);
        $this->assertStringContainsString('0812345678xx', $konten);
        $this->assertStringContainsString('Lunas', $konten);
        $this->assertStringContainsString('2500000', $konten);
        $this->assertStringNotContainsString('Lead Aktif Export', $konten);
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

    public function test_dashboard_menampilkan_target_konversi_dan_ranking(): void
    {
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

        TargetKinerja::create([
            'bulan' => 6,
            'tahun' => 2026,
            'tipe' => 'cabang',
            'cabang' => 'Bandung',
            'target_leads' => 10,
            'target_closing' => 4,
        ]);
        TargetKinerja::create([
            'bulan' => 6,
            'tahun' => 2026,
            'tipe' => 'staff',
            'cabang' => 'Bandung',
            'user_id' => $staff->id,
            'target_leads' => 5,
            'target_closing' => 2,
        ]);

        Prospek::create([
            'nama' => 'Lead Aktif Target',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
            'user_id' => $staff->id,
            'tgl_masuk' => '2026-06-03',
        ]);
        Prospek::create([
            'nama' => 'Closing Target',
            'status' => 'Daftar',
            'cabang' => 'Bandung',
            'user_id' => $staff->id,
            'tgl_masuk' => '2026-06-02',
            'tanggal_daftar' => '2026-06-10',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard', ['bulan' => 6, 'tahun' => 2026]))
            ->assertOk()
            ->assertSee('Target dan Konversi')
            ->assertSee('Akumulasi target semua cabang')
            ->assertSee('1 / 10 leads aktif')
            ->assertSee('1 / 4 closing')
            ->assertSee('50%')
            ->assertSee('Ranking Cabang')
            ->assertSee('Bandung');
    }

    public function test_dashboard_menampilkan_kpi_operasional_aging_user_input_dan_konversi_sumber(): void
    {
        Carbon::setTestNow('2026-06-30 09:00:00');

        $admin = User::factory()->create([
            'name' => 'Admin KPI',
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $inputUser = User::factory()->create([
            'name' => 'Input Bandung',
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        $leadBaru = Prospek::create([
            'nama' => 'Lead Belum FU',
            'status' => 'Baru',
            'cabang' => 'Bandung',
            'sumber' => 'Instagram',
            'created_by' => $inputUser->id,
            'tgl_masuk' => '2026-06-30',
        ]);
        $leadTerlambat = Prospek::create([
            'nama' => 'Lead FU Terlambat',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
            'sumber' => 'Instagram',
            'created_by' => $inputUser->id,
            'tgl_masuk' => '2026-06-25',
        ]);
        Prospek::create([
            'nama' => 'Closing KPI',
            'status' => 'Daftar',
            'cabang' => 'Bandung',
            'sumber' => 'Instagram',
            'created_by' => $inputUser->id,
            'tgl_masuk' => '2026-06-10',
            'tanggal_daftar' => '2026-06-20',
        ]);
        FollowUp::create([
            'prospek_id' => $leadTerlambat->id,
            'user_id' => $admin->id,
            'tanggal_follow_up' => '2026-06-26 10:00:00',
            'metode' => 'WhatsApp',
            'hasil' => 'Berminat',
            'tanggal_follow_up_berikutnya' => '2026-06-28',
            'prioritas' => 'Normal',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard', ['bulan' => 6, 'tahun' => 2026]))
            ->assertOk()
            ->assertSee('Follow Up Rate')
            ->assertSee('50%')
            ->assertSeeInOrder(['Belum Follow Up', '1'])
            ->assertSeeInOrder(['Follow Up Terlambat', '1'])
            ->assertSee('Aging Leads Aktif')
            ->assertSee('0-1 hari')
            ->assertSee('4-7 hari')
            ->assertSee('Performa User Input')
            ->assertSee('Input Bandung')
            ->assertSee('Konversi per Sumber')
            ->assertSee('Instagram');

        Carbon::setTestNow();
    }

    public function test_dashboard_admin_melihat_data_seluruh_user_dan_cabang(): void
    {
        $adminJaksel = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Jaksel',
            'aktif' => true,
        ]);
        $staffBandung = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        Prospek::create([
            'nama' => 'Lead Staff Bandung',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
            'user_id' => $staffBandung->id,
            'tgl_masuk' => '2026-06-03',
        ]);
        Prospek::create([
            'nama' => 'Closing Jakpus',
            'status' => 'Daftar',
            'cabang' => 'Jakpus',
            'tgl_masuk' => '2026-06-04',
            'tanggal_daftar' => '2026-06-10',
        ]);

        $this->actingAs($adminJaksel)
            ->get(route('dashboard', ['bulan' => 6, 'tahun' => 2026]))
            ->assertOk()
            ->assertSee('Dashboard Semua User')
            ->assertSeeInOrder(['Total Leads Aktif', '1', 'Leads Baru', '0', 'Butuh Follow Up', '1', 'Closing', '1'])
            ->assertSee('Bandung')
            ->assertSee('Jakpus');
    }

    public function test_superadmin_bisa_mengelola_target_kinerja(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);
        Cabang::create([
            'nama' => 'Bandung',
            'aktif' => true,
        ]);

        $this->actingAs($superadmin)
            ->post(route('pengaturan.target-kinerja.store'), [
                'bulan' => 6,
                'tahun' => 2026,
                'tipe' => 'cabang',
                'cabang' => 'Bandung',
                'user_id' => null,
                'target_leads' => 25,
                'target_closing' => 8,
            ])
            ->assertRedirect();

        $target = TargetKinerja::firstOrFail();

        $this->assertDatabaseHas('target_kinerja', [
            'bulan' => 6,
            'tahun' => 2026,
            'tipe' => 'cabang',
            'cabang' => 'Bandung',
            'target_leads' => 25,
            'target_closing' => 8,
        ]);

        $this->actingAs($superadmin)
            ->put(route('pengaturan.target-kinerja.update', $target), [
                'bulan' => 6,
                'tahun' => 2026,
                'tipe' => 'cabang',
                'cabang' => 'Bandung',
                'user_id' => null,
                'target_leads' => 30,
                'target_closing' => 10,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('target_kinerja', [
            'id' => $target->id,
            'target_leads' => 30,
            'target_closing' => 10,
        ]);
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

    public function test_laporan_export_hanya_data_leads_dan_closing_user_sendiri(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);
        $staffLain = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
        ]);

        Prospek::create([
            'nama' => 'Lead Milik Saya',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
            'user_id' => $staff->id,
            'no_wa' => '081111111111',
            'tgl_masuk' => '2026-06-10',
        ]);
        Prospek::create([
            'nama' => 'Closing Milik Saya',
            'status' => 'Daftar',
            'cabang' => 'Bandung',
            'user_id' => $staff->id,
            'no_wa' => '082222222222',
            'program' => 'SR GOLD',
            'program_final' => 'SR GOLD',
            'status_pembayaran' => 'Lunas',
            'nominal_pembayaran' => 3000000,
            'tgl_masuk' => '2026-06-09',
            'tanggal_daftar' => '2026-06-12',
        ]);
        Prospek::create([
            'nama' => 'Lead User Lain',
            'status' => 'Follow Up',
            'cabang' => 'Bandung',
            'user_id' => $staffLain->id,
        ]);

        $this->actingAs($staff)
            ->get(route('profil.laporan'))
            ->assertOk()
            ->assertSee('Ringkasan leads dan closing milik user login')
            ->assertSeeInOrder(['Total Leads', '2', 'Leads Baru', '0', 'Follow Up', '1', 'Rasio Closing', '50%']);

        $response = $this->actingAs($staff)
            ->get(route('profil.laporan.export'))
            ->assertOk();

        $konten = $response->streamedContent();

        $this->assertStringContainsString('Lead Milik Saya', $konten);
        $this->assertStringContainsString('Closing Milik Saya', $konten);
        $this->assertStringContainsString('Lunas', $konten);
        $this->assertStringContainsString('3000000', $konten);
        $this->assertStringContainsString('081111111111', $konten);
        $this->assertStringNotContainsString('Lead User Lain', $konten);
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

    public function test_tugas_hanya_bisa_dikelola_antar_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
            'name' => 'Admin Tugas',
        ]);
        $adminLain = User::factory()->create([
            'role' => 'admin',
            'cabang' => 'Bandung',
            'aktif' => true,
            'name' => 'Admin Penerima',
        ]);
        $staff = User::factory()->create([
            'role' => 'staff',
            'cabang' => 'Bandung',
            'aktif' => true,
            'name' => 'Staff Bukan Tugas',
        ]);

        $this->actingAs($admin)
            ->post(route('profil.tugas.store'), [
                'judul' => 'Tugas Antar Admin',
                'deskripsi' => 'Koordinasi cabang.',
                'status' => 'Baru',
                'prioritas' => 'Normal',
                'assigned_to' => $adminLain->id,
                'cabang' => 'Bandung',
            ])
            ->assertRedirect();

        $task = Task::query()->where('judul', 'Tugas Antar Admin')->firstOrFail();

        $this->assertSame($adminLain->id, $task->assigned_to);
        $this->assertSame($admin->id, $task->created_by);

        $this->actingAs($admin)
            ->post(route('profil.tugas.store'), [
                'judul' => 'Tugas Untuk Staff',
                'status' => 'Baru',
                'prioritas' => 'Normal',
                'assigned_to' => $staff->id,
                'cabang' => 'Bandung',
            ])
            ->assertInvalid(['assigned_to']);

        $this->actingAs($staff)
            ->get(route('profil.tugas'))
            ->assertOk()
            ->assertDontSee('Tambah Tugas')
            ->assertDontSee('Tugas Antar Admin');

        $this->actingAs($staff)
            ->post(route('profil.tugas.store'), [
                'judul' => 'Staff Buat Tugas',
                'status' => 'Baru',
                'prioritas' => 'Normal',
                'assigned_to' => $admin->id,
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->put(route('profil.tugas.update', $task), [
                'status' => 'Proses',
                'prioritas' => 'Normal',
                'assigned_to' => $adminLain->id,
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('profil.tugas.komentar.store', $task), [
                'komentar' => 'Coba komentar.',
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->delete(route('profil.tugas.destroy', $task))
            ->assertForbidden();
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

    public function test_superadmin_bisa_mengelola_template_whatsapp(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'aktif' => true,
        ]);

        $this->actingAs($superadmin)
            ->post(route('pengaturan.whatsapp-template.store'), [
                'nama' => 'Follow up awal',
                'isi_pesan' => 'Halo {nama}, kami follow up program {program}.',
                'urutan' => 1,
                'aktif' => '1',
            ])
            ->assertRedirect();

        $template = WhatsappTemplate::firstOrFail();

        $this->assertDatabaseHas('whatsapp_templates', [
            'nama' => 'Follow up awal',
            'aktif' => true,
            'urutan' => 1,
        ]);

        $this->actingAs($superadmin)
            ->put(route('pengaturan.whatsapp-template.update', ['template' => $template]), [
                'nama' => 'Reminder daftar',
                'isi_pesan' => 'Halo {nama}, apakah sudah siap daftar?',
                'urutan' => 2,
                'aktif' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('whatsapp_templates', [
            'id' => $template->id,
            'nama' => 'Reminder daftar',
            'isi_pesan' => 'Halo {nama}, apakah sudah siap daftar?',
            'urutan' => 2,
        ]);

        $this->actingAs($superadmin)
            ->delete(route('pengaturan.whatsapp-template.destroy', ['template' => $template]))
            ->assertRedirect();

        $this->assertDatabaseMissing('whatsapp_templates', [
            'id' => $template->id,
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
                'role' => 'admin',
                'cabang' => 'Bekasi',
                'aktif' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'role' => 'admin',
            'cabang' => 'Bekasi',
            'aktif' => true,
        ]);
    }
}

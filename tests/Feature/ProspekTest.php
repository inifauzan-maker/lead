<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\Cabang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Lead Sudah Dihubungi')
            ->assertSee('Lead Perlu Follow Up')
            ->assertDontSee('Lead Baru');
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

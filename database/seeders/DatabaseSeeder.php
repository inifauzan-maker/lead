<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\ProgramLead;
use App\Models\SumberLead;
use App\Models\User;
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
            ['Leader Bandung', 'leader.bandung@leads.test', 'leader', 'Bandung'],
            ['Staff Bandung', 'staff.bandung@leads.test', 'staff', 'Bandung'],
            ['Direksi', 'direksi@leads.test', 'direksi', null],
        ];

        foreach ($akun as [$name, $email, $role, $cabang]) {
            User::updateOrCreate(
                ['email' => $email],
                compact('name', 'password', 'role', 'cabang') + ['aktif' => true],
            );
        }
    }
}

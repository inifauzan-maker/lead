<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'admin.jaktim@leads.test')
            ->update([
                'name' => 'Admin Jaksel',
                'email' => 'admin.jaksel@leads.test',
                'cabang' => 'Jaksel',
            ]);

        DB::table('users')
            ->where('cabang', 'Jaktim')
            ->update(['cabang' => 'Jaksel']);

        DB::table('prospek')
            ->where('cabang', 'Jaktim')
            ->update(['cabang' => 'Jaksel']);

        DB::table('prospek')
            ->where('diserahkan_ke', 'Admin Jaktim')
            ->update(['diserahkan_ke' => 'Admin Jaksel']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'admin.jaksel@leads.test')
            ->update([
                'name' => 'Admin Jaktim',
                'email' => 'admin.jaktim@leads.test',
                'cabang' => 'Jaktim',
            ]);

        DB::table('users')
            ->where('cabang', 'Jaksel')
            ->update(['cabang' => 'Jaktim']);

        DB::table('prospek')
            ->where('cabang', 'Jaksel')
            ->update(['cabang' => 'Jaktim']);

        DB::table('prospek')
            ->where('diserahkan_ke', 'Admin Jaksel')
            ->update(['diserahkan_ke' => 'Admin Jaktim']);
    }
};

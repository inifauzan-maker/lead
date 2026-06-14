<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'leader')
            ->update(['role' => 'staff']);
    }

    public function down(): void
    {
        // Tidak dikembalikan otomatis karena role leader sudah dihapus dari sistem.
    }
};

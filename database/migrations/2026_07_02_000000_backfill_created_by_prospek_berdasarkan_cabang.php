<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('prospek', 'created_by')) {
            return;
        }

        DB::table('prospek')
            ->whereNull('created_by')
            ->whereNotNull('user_id')
            ->update([
                'created_by' => DB::raw('user_id'),
            ]);

        DB::table('users')
            ->select(['id', 'cabang'])
            ->where('role', 'admin')
            ->where('aktif', true)
            ->whereNotNull('cabang')
            ->orderBy('id')
            ->get()
            ->unique(fn ($user) => strtolower(trim($user->cabang)))
            ->each(function ($admin) {
                DB::table('prospek')
                    ->whereNull('created_by')
                    ->where('cabang', $admin->cabang)
                    ->update([
                        'created_by' => $admin->id,
                    ]);
            });

        $superadminId = DB::table('users')
            ->where('role', 'superadmin')
            ->where('aktif', true)
            ->orderBy('id')
            ->value('id');

        if ($superadminId) {
            DB::table('prospek')
                ->whereNull('created_by')
                ->update([
                    'created_by' => $superadminId,
                ]);
        }
    }

    public function down(): void
    {
        // Data backfill is intentionally not reversed.
    }
};

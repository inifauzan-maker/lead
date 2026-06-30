<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospek', function (Blueprint $table) {
            if (! Schema::hasColumn('prospek', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('prospek')
            ->whereNull('created_by')
            ->whereNotNull('user_id')
            ->update([
                'created_by' => DB::raw('user_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('prospek', function (Blueprint $table) {
            if (Schema::hasColumn('prospek', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};

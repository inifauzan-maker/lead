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
            if (! Schema::hasColumn('prospek', 'jenjang')) {
                $table->string('jenjang')->nullable()->after('asal_sekolah');
            }
        });

        DB::table('prospek')
            ->whereIn('kelas', ['SD', 'SMP', 'SMA', 'Gapyear'])
            ->update([
                'jenjang' => DB::raw('kelas'),
                'kelas' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('prospek')
            ->whereNull('kelas')
            ->whereIn('jenjang', ['SD', 'SMP', 'SMA', 'Gapyear'])
            ->update([
                'kelas' => DB::raw('jenjang'),
            ]);

        Schema::table('prospek', function (Blueprint $table) {
            if (Schema::hasColumn('prospek', 'jenjang')) {
                $table->dropColumn('jenjang');
            }
        });
    }
};

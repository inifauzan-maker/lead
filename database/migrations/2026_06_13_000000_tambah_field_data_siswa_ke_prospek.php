<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospek', function (Blueprint $table) {
            if (! Schema::hasColumn('prospek', 'tanggal_daftar')) {
                $table->date('tanggal_daftar')->nullable()->after('tgl_masuk');
            }

            if (! Schema::hasColumn('prospek', 'program_final')) {
                $table->string('program_final')->nullable()->after('tanggal_daftar');
            }

            if (! Schema::hasColumn('prospek', 'nominal_pembayaran')) {
                $table->decimal('nominal_pembayaran', 15, 2)->nullable()->after('program_final');
            }

            if (! Schema::hasColumn('prospek', 'status_pembayaran')) {
                $table->string('status_pembayaran')->nullable()->after('nominal_pembayaran');
            }

            if (! Schema::hasColumn('prospek', 'kelas_angkatan')) {
                $table->string('kelas_angkatan')->nullable()->after('status_pembayaran');
            }

            if (! Schema::hasColumn('prospek', 'catatan_administrasi')) {
                $table->text('catatan_administrasi')->nullable()->after('kelas_angkatan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospek', function (Blueprint $table) {
            $kolom = collect([
                'tanggal_daftar',
                'program_final',
                'nominal_pembayaran',
                'status_pembayaran',
                'kelas_angkatan',
                'catatan_administrasi',
            ])
                ->filter(fn ($kolom) => Schema::hasColumn('prospek', $kolom))
                ->all();

            if ($kolom !== []) {
                $table->dropColumn($kolom);
            }
        });
    }
};

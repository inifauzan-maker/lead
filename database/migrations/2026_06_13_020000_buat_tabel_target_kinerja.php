<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_kinerja', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->string('tipe');
            $table->string('cabang')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('target_leads')->default(0);
            $table->unsignedInteger('target_closing')->default(0);
            $table->timestamps();

            $table->unique(['bulan', 'tahun', 'tipe', 'cabang', 'user_id'], 'target_kinerja_unik');
            $table->index(['bulan', 'tahun', 'tipe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_kinerja');
    }
};

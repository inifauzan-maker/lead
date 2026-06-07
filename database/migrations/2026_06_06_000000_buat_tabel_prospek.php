<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospek', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('asal_sekolah')->nullable();
            $table->string('kelas')->nullable();
            $table->string('kota_asal')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('program')->nullable();
            $table->string('status')->default('Baru');
            $table->string('diserahkan_ke')->nullable();
            $table->string('sumber')->nullable();
            $table->text('keterangan')->nullable();
            $table->date('tgl_masuk')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospek');
    }
};

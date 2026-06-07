<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospek_id')->constrained('prospek')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('tanggal_follow_up');
            $table->string('metode')->default('WhatsApp');
            $table->string('hasil')->default('Tersambung');
            $table->text('catatan')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->date('tanggal_follow_up_berikutnya')->nullable();
            $table->string('prioritas')->default('Normal');
            $table->timestamps();

            $table->index(['tanggal_follow_up', 'hasil']);
            $table->index('tanggal_follow_up_berikutnya');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};

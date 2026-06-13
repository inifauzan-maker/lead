<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospek_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospek_id')->constrained('prospek')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_lama')->nullable();
            $table->string('status_baru');
            $table->string('sumber')->default('manual');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['prospek_id', 'created_at']);
            $table->index(['status_baru', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospek_status_histories');
    }
};

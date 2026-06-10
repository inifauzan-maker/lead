<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama_user')->nullable();
            $table->string('role', 50)->nullable();
            $table->string('cabang')->nullable();
            $table->string('aksi', 80);
            $table->string('modul', 80)->nullable();
            $table->string('deskripsi')->nullable();
            $table->string('method', 12);
            $table->string('route_name')->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['aksi', 'created_at']);
            $table->index(['modul', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

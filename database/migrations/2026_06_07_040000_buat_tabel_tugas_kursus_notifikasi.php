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
        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('judul');
                $table->text('deskripsi')->nullable();
                $table->string('status')->default('Baru');
                $table->string('prioritas')->default('Normal');
                $table->date('tenggat')->nullable();
                $table->foreignId('prospek_id')->nullable()->constrained('prospek')->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('cabang')->nullable();
                $table->timestamps();

                $table->index(['status', 'prioritas']);
                $table->index('tenggat');
            });
        }

        if (! Schema::hasTable('task_comments')) {
            Schema::create('task_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('komentar');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('judul');
                $table->text('deskripsi')->nullable();
                $table->string('level')->default('Umum');
                $table->unsignedInteger('durasi_menit')->default(0);
                $table->boolean('aktif')->default(true);
                $table->unsignedInteger('urutan')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('course_lessons')) {
            Schema::create('course_lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->string('judul');
                $table->longText('konten')->nullable();
                $table->unsignedInteger('durasi_menit')->default(0);
                $table->unsignedInteger('urutan')->default(0);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('course_progress')) {
            Schema::create('course_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('course_lesson_id')->nullable()->constrained('course_lessons')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('Belum Mulai');
                $table->unsignedTinyInteger('progress_persen')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['course_id', 'course_lesson_id', 'user_id'], 'course_progress_unique');
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('tipe')->default('info');
                $table->string('judul');
                $table->text('pesan')->nullable();
                $table->string('tautan')->nullable();
                $table->string('prioritas')->default('Normal');
                $table->timestamp('dibaca_pada')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'dibaca_pada']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['notifications', 'course_progress', 'course_lessons', 'courses', 'task_comments', 'tasks'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

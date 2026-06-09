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
        $this->lengkapiTasks();
        $this->lengkapiTaskComments();
        $this->lengkapiCourses();
        $this->lengkapiCourseLessons();
        $this->lengkapiCourseProgress();
        $this->lengkapiNotifications();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migration ini bersifat perbaikan idempotent untuk database parsial.
    }

    private function lengkapiTasks(): void
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
            });

            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $this->stringJikaBelumAda($table, 'tasks', 'judul');
            $this->textJikaBelumAda($table, 'tasks', 'deskripsi');
            $this->stringJikaBelumAda($table, 'tasks', 'status', 'Baru');
            $this->stringJikaBelumAda($table, 'tasks', 'prioritas', 'Normal');
            $this->dateJikaBelumAda($table, 'tasks', 'tenggat');
            $this->unsignedBigIntegerJikaBelumAda($table, 'tasks', 'prospek_id');
            $this->unsignedBigIntegerJikaBelumAda($table, 'tasks', 'assigned_to');
            $this->unsignedBigIntegerJikaBelumAda($table, 'tasks', 'created_by');
            $this->stringJikaBelumAda($table, 'tasks', 'cabang', nullable: true);
            $this->timestampsJikaBelumAda($table, 'tasks');
        });
    }

    private function lengkapiTaskComments(): void
    {
        if (! Schema::hasTable('task_comments')) {
            Schema::create('task_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('komentar');
                $table->timestamps();
            });

            return;
        }

        Schema::table('task_comments', function (Blueprint $table) {
            $this->unsignedBigIntegerJikaBelumAda($table, 'task_comments', 'task_id');
            $this->unsignedBigIntegerJikaBelumAda($table, 'task_comments', 'user_id');
            $this->textJikaBelumAda($table, 'task_comments', 'komentar', nullable: false);
            $this->timestampsJikaBelumAda($table, 'task_comments');
        });
    }

    private function lengkapiCourses(): void
    {
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

            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $this->stringJikaBelumAda($table, 'courses', 'judul');
            $this->textJikaBelumAda($table, 'courses', 'deskripsi');
            $this->stringJikaBelumAda($table, 'courses', 'level', 'Umum');
            $this->unsignedIntegerJikaBelumAda($table, 'courses', 'durasi_menit');
            $this->booleanJikaBelumAda($table, 'courses', 'aktif');
            $this->unsignedIntegerJikaBelumAda($table, 'courses', 'urutan');
            $this->timestampsJikaBelumAda($table, 'courses');
        });
    }

    private function lengkapiCourseLessons(): void
    {
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

            return;
        }

        Schema::table('course_lessons', function (Blueprint $table) {
            $this->unsignedBigIntegerJikaBelumAda($table, 'course_lessons', 'course_id');
            $this->stringJikaBelumAda($table, 'course_lessons', 'judul');
            $this->longTextJikaBelumAda($table, 'course_lessons', 'konten');
            $this->unsignedIntegerJikaBelumAda($table, 'course_lessons', 'durasi_menit');
            $this->unsignedIntegerJikaBelumAda($table, 'course_lessons', 'urutan');
            $this->booleanJikaBelumAda($table, 'course_lessons', 'aktif');
            $this->timestampsJikaBelumAda($table, 'course_lessons');
        });
    }

    private function lengkapiCourseProgress(): void
    {
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
            });

            return;
        }

        Schema::table('course_progress', function (Blueprint $table) {
            $this->unsignedBigIntegerJikaBelumAda($table, 'course_progress', 'course_id');
            $this->unsignedBigIntegerJikaBelumAda($table, 'course_progress', 'course_lesson_id');
            $this->unsignedBigIntegerJikaBelumAda($table, 'course_progress', 'user_id', nullable: false);
            $this->stringJikaBelumAda($table, 'course_progress', 'status', 'Belum Mulai');
            $this->unsignedTinyIntegerJikaBelumAda($table, 'course_progress', 'progress_persen');
            $this->timestampJikaBelumAda($table, 'course_progress', 'completed_at');
            $this->timestampsJikaBelumAda($table, 'course_progress');
        });
    }

    private function lengkapiNotifications(): void
    {
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
            });

            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $this->unsignedBigIntegerJikaBelumAda($table, 'notifications', 'user_id');
            $this->stringJikaBelumAda($table, 'notifications', 'tipe', 'info');
            $this->stringJikaBelumAda($table, 'notifications', 'judul');
            $this->textJikaBelumAda($table, 'notifications', 'pesan');
            $this->stringJikaBelumAda($table, 'notifications', 'tautan', nullable: true);
            $this->stringJikaBelumAda($table, 'notifications', 'prioritas', 'Normal');
            $this->timestampJikaBelumAda($table, 'notifications', 'dibaca_pada');
            $this->timestampsJikaBelumAda($table, 'notifications');
        });
    }

    private function stringJikaBelumAda(Blueprint $table, string $namaTabel, string $kolom, ?string $default = null, bool $nullable = false): void
    {
        if (Schema::hasColumn($namaTabel, $kolom)) {
            return;
        }

        $column = $table->string($kolom);
        $nullable ? $column->nullable() : null;
        $default !== null ? $column->default($default) : null;
    }

    private function textJikaBelumAda(Blueprint $table, string $namaTabel, string $kolom, bool $nullable = true): void
    {
        if (! Schema::hasColumn($namaTabel, $kolom)) {
            $column = $table->text($kolom);
            $nullable ? $column->nullable() : null;
        }
    }

    private function longTextJikaBelumAda(Blueprint $table, string $namaTabel, string $kolom): void
    {
        if (! Schema::hasColumn($namaTabel, $kolom)) {
            $table->longText($kolom)->nullable();
        }
    }

    private function dateJikaBelumAda(Blueprint $table, string $namaTabel, string $kolom): void
    {
        if (! Schema::hasColumn($namaTabel, $kolom)) {
            $table->date($kolom)->nullable();
        }
    }

    private function timestampJikaBelumAda(Blueprint $table, string $namaTabel, string $kolom): void
    {
        if (! Schema::hasColumn($namaTabel, $kolom)) {
            $table->timestamp($kolom)->nullable();
        }
    }

    private function unsignedBigIntegerJikaBelumAda(Blueprint $table, string $namaTabel, string $kolom, bool $nullable = true): void
    {
        if (Schema::hasColumn($namaTabel, $kolom)) {
            return;
        }

        $column = $table->unsignedBigInteger($kolom);
        $nullable ? $column->nullable() : null;
    }

    private function unsignedIntegerJikaBelumAda(Blueprint $table, string $namaTabel, string $kolom): void
    {
        if (! Schema::hasColumn($namaTabel, $kolom)) {
            $table->unsignedInteger($kolom)->default(0);
        }
    }

    private function unsignedTinyIntegerJikaBelumAda(Blueprint $table, string $namaTabel, string $kolom): void
    {
        if (! Schema::hasColumn($namaTabel, $kolom)) {
            $table->unsignedTinyInteger($kolom)->default(0);
        }
    }

    private function booleanJikaBelumAda(Blueprint $table, string $namaTabel, string $kolom): void
    {
        if (! Schema::hasColumn($namaTabel, $kolom)) {
            $table->boolean($kolom)->default(true);
        }
    }

    private function timestampsJikaBelumAda(Blueprint $table, string $namaTabel): void
    {
        if (! Schema::hasColumn($namaTabel, 'created_at')) {
            $table->timestamp('created_at')->nullable();
        }

        if (! Schema::hasColumn($namaTabel, 'updated_at')) {
            $table->timestamp('updated_at')->nullable();
        }
    }
};

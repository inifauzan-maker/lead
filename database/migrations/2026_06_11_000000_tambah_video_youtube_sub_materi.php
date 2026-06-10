<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_lessons') || Schema::hasColumn('course_lessons', 'video_youtube')) {
            return;
        }

        Schema::table('course_lessons', function (Blueprint $table) {
            $table->string('video_youtube')->nullable()->after('konten');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('course_lessons') || ! Schema::hasColumn('course_lessons', 'video_youtube')) {
            return;
        }

        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropColumn('video_youtube');
        });
    }
};

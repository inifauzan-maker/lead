<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses') || Schema::hasColumn('courses', 'topik')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->string('topik')->nullable()->after('level');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('courses') || ! Schema::hasColumn('courses', 'topik')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('topik');
        });
    }
};

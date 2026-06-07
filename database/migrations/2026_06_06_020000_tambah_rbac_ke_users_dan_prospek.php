<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->after('password');
            $table->string('cabang')->nullable()->after('role');
            $table->boolean('aktif')->default(true)->after('cabang');
        });

        Schema::table('prospek', function (Blueprint $table) {
            $table->string('cabang')->nullable()->after('status');
            $table->foreignId('user_id')->nullable()->after('cabang')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prospek', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('cabang');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'cabang', 'aktif']);
        });
    }
};

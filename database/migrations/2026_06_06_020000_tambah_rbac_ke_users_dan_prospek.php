<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('staff')->after('password');
            }

            if (! Schema::hasColumn('users', 'cabang')) {
                $table->string('cabang')->nullable()->after('role');
            }

            if (! Schema::hasColumn('users', 'aktif')) {
                $table->boolean('aktif')->default(true)->after('cabang');
            }
        });

        Schema::table('prospek', function (Blueprint $table) {
            if (! Schema::hasColumn('prospek', 'cabang')) {
                $table->string('cabang')->nullable()->after('status');
            }

            if (! Schema::hasColumn('prospek', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('cabang')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospek', function (Blueprint $table) {
            if (Schema::hasColumn('prospek', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            if (Schema::hasColumn('prospek', 'cabang')) {
                $table->dropColumn('cabang');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $kolom = collect(['role', 'cabang', 'aktif'])
                ->filter(fn ($kolom) => Schema::hasColumn('users', $kolom))
                ->all();

            if ($kolom !== []) {
                $table->dropColumn($kolom);
            }
        });
    }
};

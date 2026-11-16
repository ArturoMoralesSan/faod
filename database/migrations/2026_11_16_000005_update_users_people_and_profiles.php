<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla basura creada por error.
        Schema::dropIfExists('create_people_tables');

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            // Rol con el que el usuario está navegando (selector de menú).
            $table->foreignId('active_role_id')
                ->nullable()
                ->after('last_login_at')
                ->constrained('roles')
                ->nullOnDelete();
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('maternal_surname');
        });

        // El ->unique() encadenado después de ->constrained() no crea el índice.
        foreach (['student_profiles', 'teacher_profiles', 'staff_profiles'] as $profileTable) {
            Schema::table($profileTable, function (Blueprint $table) use ($profileTable) {
                $table->unique('people_id', "{$profileTable}_people_id_unique");
            });
        }

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('position')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        foreach (['student_profiles', 'teacher_profiles', 'staff_profiles'] as $profileTable) {
            Schema::table($profileTable, function (Blueprint $table) use ($profileTable) {
                $table->dropUnique("{$profileTable}_people_id_unique");
            });
        }

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_role_id');
            $table->dropColumn(['is_active', 'last_login_at']);
        });
    }
};

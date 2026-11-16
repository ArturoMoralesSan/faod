<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('specialty_id')
                ->nullable()
                ->constrained('specialties')
                ->nullOnDelete();

            // especialidad | maestria | doctorado | diplomado
            $table->string('type', 30);
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->unsignedSmallInteger('duration_value')->nullable();
            // semestres | cuatrimestres | anios | meses | semanas
            $table->string('duration_unit', 20)->nullable();
            $table->unsignedSmallInteger('total_periods')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
        });

        Schema::create('program_objectives', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_program_id')
                ->constrained('academic_programs')
                ->cascadeOnDelete();

            // objetivo | competencia | perfil_ingreso | perfil_egreso
            $table->string('type', 30);
            $table->text('description');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Docentes asignados al programa (incluye coordinadores).
        Schema::create('academic_program_teacher', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_program_id')
                ->constrained('academic_programs')
                ->cascadeOnDelete();

            $table->foreignId('teacher_profile_id')
                ->constrained('teacher_profiles')
                ->cascadeOnDelete();

            // coordinador | docente | tutor
            $table->string('role', 30)->default('docente');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['academic_program_id', 'teacher_profile_id', 'role'], 'program_teacher_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_program_teacher');
        Schema::dropIfExists('program_objectives');
        Schema::dropIfExists('academic_programs');
    }
};

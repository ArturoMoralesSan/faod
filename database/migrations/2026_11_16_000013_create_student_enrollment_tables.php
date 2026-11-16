<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alumno inscrito internamente a un programa y generación.
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->cascadeOnDelete();

            $table->foreignId('academic_program_id')
                ->constrained('academic_programs')
                ->cascadeOnDelete();

            $table->foreignId('cohort_id')
                ->nullable()
                ->constrained('cohorts')
                ->nullOnDelete();

            $table->foreignId('study_plan_id')
                ->nullable()
                ->constrained('study_plans')
                ->nullOnDelete();

            // activo | baja | egresado
            $table->string('status', 30)->default('activo');
            $table->date('started_at')->nullable();
            $table->date('withdrawn_at')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->date('graduated_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_profile_id', 'academic_program_id', 'cohort_id'], 'enrollment_unique');
            $table->index('status');
        });

        // Situación de inscripción ante la universidad, por periodo.
        Schema::create('university_registrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_enrollment_id')
                ->constrained('student_enrollments')
                ->cascadeOnDelete();

            $table->foreignId('academic_period_id')
                ->constrained('academic_periods')
                ->cascadeOnDelete();

            // inscrito | no_inscrito | pendiente
            $table->string('status', 30)->default('pendiente');
            $table->date('registered_at')->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_enrollment_id', 'academic_period_id'], 'univ_registration_unique');
            $table->index('status');
        });

        // Historial interno de cambios de situación del alumno.
        Schema::create('student_status_changes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_enrollment_id')
                ->constrained('student_enrollments')
                ->cascadeOnDelete();

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->date('changed_at');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_status_changes');
        Schema::dropIfExists('university_registrations');
        Schema::dropIfExists('student_enrollments');
    }
};

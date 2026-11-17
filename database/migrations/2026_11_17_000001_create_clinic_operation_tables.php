<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operación de clínicas: áreas, supervisores, equipos, disponibilidad
 * de consultorios, sesiones clínicas e incidencias.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
        | Áreas de una clínica (p. ej. Endodoncia, Rayos X, Esterilización).
        */
        Schema::create('clinic_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['clinic_id', 'name']);
        });

        Schema::table('spaces', function (Blueprint $table) {
            $table->foreignId('clinic_area_id')
                ->nullable()
                ->after('clinic_id')
                ->constrained('clinic_areas')
                ->nullOnDelete();
        });

        /*
        | Docentes responsables o supervisores de una clínica (o de un área).
        */
        Schema::create('clinic_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('clinic_area_id')->nullable()->constrained('clinic_areas')->nullOnDelete();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();

            // responsable | supervisor
            $table->string('role', 30)->default('supervisor');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'teacher_profile_id']);
        });

        /*
        | Equipo e instrumental mayor de la clínica.
        */
        Schema::create('clinic_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('space_id')->nullable()->constrained('spaces')->nullOnDelete();

            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            // operativo | mantenimiento | fuera_servicio | baja
            $table->string('status', 30)->default('operativo');
            $table->date('acquired_at')->nullable();
            $table->date('last_maintenance_at')->nullable();
            $table->date('next_maintenance_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinic_id', 'status']);
        });

        /*
        | Horario en que un consultorio está disponible para atención clínica.
        | Sin periodo = aplica a todos los periodos.
        */
        Schema::create('space_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['space_id', 'day_of_week']);
        });

        /*
        | Sesión clínica: práctica supervisada en una fecha y consultorio.
        */
        Schema::create('clinical_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('space_id')->nullable()->constrained('spaces')->nullOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
            $table->foreignId('academic_group_id')->nullable()->constrained('academic_groups')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            // programada | en_curso | concluida | cancelada
            $table->string('status', 30)->default('programada');
            $table->string('topic')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinic_id', 'date']);
            $table->index(['space_id', 'date']);
        });

        Schema::create('clinical_session_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_session_id')->constrained('clinical_sessions')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();

            // pendiente | asistio | falta | justificada
            $table->string('attendance', 30)->default('pendiente');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['clinical_session_id', 'student_profile_id'], 'clinical_session_student_unique');
        });

        /*
        | Incidencias de la clínica: fallas de equipo, instalaciones, bioseguridad, etc.
        */
        Schema::create('clinic_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('space_id')->nullable()->constrained('spaces')->nullOnDelete();
            $table->foreignId('clinic_equipment_id')->nullable()->constrained('clinic_equipment')->nullOnDelete();
            $table->foreignId('clinical_session_id')->nullable()->constrained('clinical_sessions')->nullOnDelete();

            // equipo | instalaciones | bioseguridad | seguridad | paciente | otro
            $table->string('type', 30);
            // baja | media | alta | critica
            $table->string('severity', 20)->default('media');
            // abierta | en_proceso | resuelta | cerrada
            $table->string('status', 30)->default('abierta');

            $table->string('title');
            $table->text('description');
            $table->dateTime('occurred_at');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('resolution')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinic_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_incidents');
        Schema::dropIfExists('clinical_session_student');
        Schema::dropIfExists('clinical_sessions');
        Schema::dropIfExists('space_availabilities');
        Schema::dropIfExists('clinic_equipment');
        Schema::dropIfExists('clinic_supervisors');

        Schema::table('spaces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clinic_area_id');
        });

        Schema::dropIfExists('clinic_areas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cursos, seminarios, congresos, talleres y demás actividades académicas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_activities', function (Blueprint $table) {
            $table->id();
            // curso | seminario | congreso | taller | diplomado | conferencia | otro
            $table->string('type', 20);
            $table->string('title');
            $table->text('description')->nullable();
            // presencial | en_linea | mixta
            $table->string('modality', 20)->default('presencial');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->string('schedule')->nullable();
            $table->foreignId('space_id')->nullable()->constrained('spaces')->nullOnDelete();
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->unsignedSmallInteger('hours')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            // planeada | abierta | en_curso | concluida | cancelada
            $table->string('status', 20)->default('planeada');
            $table->foreignId('coordinator_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();
            $table->boolean('issues_certificate')->default(true);
            // Asistencia mínima para tener constancia.
            $table->unsignedTinyInteger('min_attendance')->default(80);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at']);
        });

        /*
        | Ponentes e instructores: docentes de la facultad o externos.
        */
        Schema::create('activity_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_activity_id')->constrained('academic_activities')->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('role', 50)->default('ponente');
            $table->timestamps();
        });

        /*
        | Participantes: cualquier persona registrada (alumno, docente, externo).
        */
        Schema::create('activity_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_activity_id')->constrained('academic_activities')->cascadeOnDelete();
            $table->foreignId('people_id')->constrained('people')->cascadeOnDelete();
            // inscrito | acreditado | no_acreditado | baja
            $table->string('status', 20)->default('inscrito');
            $table->unsignedTinyInteger('attendance')->nullable();
            $table->decimal('grade', 4, 1)->nullable();
            // no_aplica | pendiente | pagado | becado
            $table->string('payment_status', 20)->default('no_aplica');
            $table->string('certificate_folio', 30)->nullable()->unique();
            $table->dateTime('certificate_issued_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['academic_activity_id', 'people_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_participants');
        Schema::dropIfExists('activity_instructors');
        Schema::dropIfExists('academic_activities');
    }
};

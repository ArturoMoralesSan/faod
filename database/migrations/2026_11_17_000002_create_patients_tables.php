<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pacientes: expediente clínico, contactos de emergencia, antecedentes,
 * alergias, medicamentos, consultas y notas de evolución.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
        | Perfil de paciente de una persona (people). Una persona puede ser
        | alumno o docente y a la vez paciente.
        */
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('people_id')->unique()->constrained('people')->cascadeOnDelete();
            $table->string('record_number', 30)->unique();
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();

            $table->string('blood_type', 5)->nullable();
            $table->string('occupation')->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('referred_by')->nullable();

            // activo | alta | baja
            $table->string('status', 20)->default('activo');
            $table->date('registered_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('patient_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship', 50)->nullable();
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->timestamps();
        });

        /*
        | Antecedentes: heredofamiliares, personales patológicos y no patológicos,
        | odontológicos y ginecoobstétricos.
        */
        Schema::create('patient_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'category']);
        });

        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('allergen');
            $table->string('reaction')->nullable();
            // leve | moderada | grave
            $table->string('severity', 20)->default('moderada');
            $table->timestamps();
        });

        Schema::create('patient_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('name');
            $table->string('dose')->nullable();
            $table->string('frequency')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
        | Consulta: motivo, signos vitales y exploración. La atiende un alumno
        | y la supervisa un docente.
        */
        Schema::create('patient_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignId('clinical_session_id')->nullable()->constrained('clinical_sessions')->nullOnDelete();
            $table->foreignId('student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();
            $table->foreignId('teacher_profile_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();

            $table->dateTime('attended_at');
            $table->text('reason');
            $table->string('blood_pressure', 20)->nullable();
            $table->unsignedSmallInteger('heart_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 4, 2)->nullable();
            $table->text('findings')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'attended_at']);
        });

        /*
        | Nota de evolución del paciente (opcionalmente ligada a una consulta).
        */
        Schema::create('patient_progress_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();
            $table->foreignId('student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();
            $table->foreignId('teacher_profile_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();
            $table->dateTime('noted_at');
            $table->text('note');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'noted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_progress_notes');
        Schema::dropIfExists('patient_consultations');
        Schema::dropIfExists('patient_medications');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('patient_conditions');
        Schema::dropIfExists('patient_emergency_contacts');
        Schema::dropIfExists('patients');
    }
};

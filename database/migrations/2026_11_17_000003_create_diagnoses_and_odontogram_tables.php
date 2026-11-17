<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnósticos (catálogo y del paciente), odontograma y revisiones docentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
        | Catálogo de diagnósticos (p. ej. CIE-10 capítulo K00–K14).
        */
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('category', 60)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /*
        | Diagnóstico de un paciente. Lo captura el alumno y lo autoriza un docente.
        */
        Schema::create('patient_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_consultation_id')->nullable()->constrained('patient_consultations')->nullOnDelete();
            $table->foreignId('diagnosis_id')->nullable()->constrained('diagnoses')->nullOnDelete();

            $table->string('description')->nullable();
            // Nomenclatura FDI (11–48 permanentes, 51–85 temporales). Vacío = general.
            $table->string('tooth', 3)->nullable();
            $table->string('surfaces', 10)->nullable();

            // presuntivo | definitivo
            $table->string('type', 20)->default('presuntivo');
            // pendiente | autorizado | correcciones | rechazado
            $table->string('review_status', 20)->default('pendiente');

            $table->date('diagnosed_at');
            $table->foreignId('student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();
            // Docente que debe revisarlo.
            $table->foreignId('teacher_profile_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'diagnosed_at']);
            $table->index('review_status');
        });

        /*
        | Revisión docente de cualquier registro clínico (diagnósticos, planes de tratamiento…).
        */
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('teacher_profile_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();
            // autorizado | correcciones | rechazado
            $table->string('decision', 20);
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        /*
        | Odontograma: cada registro es el estado de un diente (o una de sus caras)
        | en una fecha. El estado actual es el registro más reciente.
        */
        Schema::create('odontogram_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('patient_diagnosis_id')->nullable()->constrained('patient_diagnoses')->nullOnDelete();
            $table->string('tooth', 3);
            // O | M | D | V | L. Vacío = diente completo.
            $table->string('surface', 1)->nullable();
            $table->string('condition', 30);
            $table->string('notes')->nullable();
            $table->dateTime('recorded_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'tooth']);
        });

        DB::table('diagnoses')->insert(collect([
            ['K00.6', 'Alteraciones en la erupción dentaria', 'Desarrollo y erupción'],
            ['K01.1', 'Diente impactado', 'Desarrollo y erupción'],
            ['K02.0', 'Caries limitada al esmalte', 'Caries'],
            ['K02.1', 'Caries de la dentina', 'Caries'],
            ['K02.2', 'Caries del cemento', 'Caries'],
            ['K03.0', 'Atrición excesiva de los dientes', 'Tejidos duros'],
            ['K03.1', 'Abrasión de los dientes', 'Tejidos duros'],
            ['K04.0', 'Pulpitis', 'Pulpa y periápice'],
            ['K04.1', 'Necrosis de la pulpa', 'Pulpa y periápice'],
            ['K04.5', 'Periodontitis apical crónica', 'Pulpa y periápice'],
            ['K04.7', 'Absceso periapical sin fístula', 'Pulpa y periápice'],
            ['K05.0', 'Gingivitis aguda', 'Periodonto'],
            ['K05.1', 'Gingivitis crónica', 'Periodonto'],
            ['K05.3', 'Periodontitis crónica', 'Periodonto'],
            ['K06.0', 'Retracción gingival', 'Periodonto'],
            ['K07.2', 'Anomalías de la relación entre los arcos dentarios', 'Ortodoncia'],
            ['K07.3', 'Anomalías de la posición del diente', 'Ortodoncia'],
            ['K08.1', 'Pérdida de dientes por accidente, extracción o enfermedad periodontal', 'Prótesis'],
            ['K08.3', 'Raíz dental retenida', 'Prótesis'],
            ['K12.0', 'Estomatitis aftosa recurrente', 'Mucosa oral'],
            ['S02.5', 'Fractura de los dientes', 'Traumatismo'],
        ])->map(fn ($row) => [
            'code' => $row[0],
            'name' => $row[1],
            'category' => $row[2],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogram_entries');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('patient_diagnoses');
        Schema::dropIfExists('diagnoses');
    }
};

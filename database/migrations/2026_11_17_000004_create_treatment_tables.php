<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tratamientos: catálogo de procedimientos, planes, etapas, procedimientos
 * del paciente y materiales utilizados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('category', 60)->nullable();
            $table->decimal('default_cost', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /*
        | Plan de tratamiento: lo propone el alumno y lo autoriza el docente.
        */
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            // en_proceso | concluido | suspendido | cancelado
            $table->string('status', 20)->default('en_proceso');
            // pendiente | autorizado | correcciones | rechazado
            $table->string('review_status', 20)->default('pendiente');
            $table->foreignId('student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();
            $table->foreignId('teacher_profile_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();
            $table->date('started_at');
            $table->date('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index('review_status');
        });

        Schema::create('treatment_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained('treatment_plans')->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        /*
        | Procedimiento planeado o realizado. Al marcarse como realizado,
        | queda pendiente de validación docente.
        */
        Schema::create('treatment_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained('treatment_plans')->cascadeOnDelete();
            $table->foreignId('treatment_stage_id')->nullable()->constrained('treatment_stages')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('procedures')->nullOnDelete();
            $table->foreignId('patient_diagnosis_id')->nullable()->constrained('patient_diagnoses')->nullOnDelete();

            $table->string('description')->nullable();
            $table->string('tooth', 3)->nullable();
            $table->string('surfaces', 10)->nullable();
            // planeado | realizado | cancelado
            $table->string('status', 20)->default('planeado');
            // null mientras no se realiza; después pendiente | autorizado | correcciones | rechazado
            $table->string('review_status', 20)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->dateTime('performed_at')->nullable();
            $table->foreignId('student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();
            $table->foreignId('teacher_profile_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['treatment_plan_id', 'status']);
            $table->index('review_status');
        });

        Schema::create('procedure_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_procedure_id')->constrained('treatment_procedures')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->nullable();
            $table->timestamps();
        });

        DB::table('procedures')->insert(collect([
            ['DX-01', 'Historia clínica y exploración', 'Diagnóstico', 0],
            ['DX-02', 'Radiografía periapical', 'Diagnóstico', 80],
            ['DX-03', 'Radiografía panorámica', 'Diagnóstico', 250],
            ['PRV-01', 'Profilaxis', 'Prevención', 200],
            ['PRV-02', 'Aplicación tópica de flúor', 'Prevención', 120],
            ['PRV-03', 'Sellador de fosetas y fisuras', 'Prevención', 150],
            ['PER-01', 'Raspado y alisado radicular por cuadrante', 'Periodoncia', 350],
            ['OP-01', 'Resina compuesta', 'Operatoria', 400],
            ['OP-02', 'Amalgama', 'Operatoria', 300],
            ['OP-03', 'Ionómero de vidrio', 'Operatoria', 250],
            ['END-01', 'Pulpotomía', 'Endodoncia', 500],
            ['END-02', 'Tratamiento de conductos unirradicular', 'Endodoncia', 1500],
            ['END-03', 'Tratamiento de conductos multirradicular', 'Endodoncia', 2500],
            ['CIR-01', 'Extracción simple', 'Cirugía', 400],
            ['CIR-02', 'Extracción de tercer molar', 'Cirugía', 1200],
            ['PRO-01', 'Corona', 'Prótesis', 2500],
            ['PRO-02', 'Prótesis parcial removible', 'Prótesis', 3500],
            ['PRO-03', 'Prótesis total', 'Prótesis', 4500],
        ])->map(fn ($row) => [
            'code' => $row[0],
            'name' => $row[1],
            'category' => $row[2],
            'default_cost' => $row[3],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_materials');
        Schema::dropIfExists('treatment_procedures');
        Schema::dropIfExists('treatment_stages');
        Schema::dropIfExists('treatment_plans');
        Schema::dropIfExists('procedures');
    }
};

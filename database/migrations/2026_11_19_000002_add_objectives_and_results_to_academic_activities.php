<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actividades académicas: objetivos, evaluación interna y resultados.
 * Tratamientos: indicaciones para el paciente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_activities', function (Blueprint $table) {
            $table->text('objectives')->nullable()->after('description');
            // Evaluación interna de la actividad (0 a 10) y sus comentarios.
            $table->decimal('evaluation_score', 3, 1)->nullable()->after('min_attendance');
            $table->text('evaluation_notes')->nullable()->after('evaluation_score');
            // Resultados y observaciones al concluir.
            $table->text('results')->nullable()->after('evaluation_notes');
        });

        Schema::table('treatment_procedures', function (Blueprint $table) {
            $table->text('patient_instructions')->nullable()->after('notes');
        });

        Schema::table('treatment_plans', function (Blueprint $table) {
            $table->text('patient_instructions')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('academic_activities', function (Blueprint $table) {
            $table->dropColumn(['objectives', 'evaluation_score', 'evaluation_notes', 'results']);
        });

        Schema::table('treatment_procedures', function (Blueprint $table) {
            $table->dropColumn('patient_instructions');
        });

        Schema::table('treatment_plans', function (Blueprint $table) {
            $table->dropColumn('patient_instructions');
        });
    }
};

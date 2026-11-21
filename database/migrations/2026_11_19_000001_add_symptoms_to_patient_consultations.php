<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Padecimiento actual y síntomas bucales en la consulta odontológica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_consultations', function (Blueprint $table) {
            // Relato del paciente: inicio, evolución, qué lo provoca o alivia.
            $table->text('current_illness')->nullable()->after('reason');
            // Claves de PatientConsultation::SYMPTOMS.
            $table->json('symptoms')->nullable()->after('current_illness');
            // Escala de dolor 0–10.
            $table->unsignedTinyInteger('pain_intensity')->nullable()->after('symptoms');
        });
    }

    public function down(): void
    {
        Schema::table('patient_consultations', function (Blueprint $table) {
            $table->dropColumn(['current_illness', 'symptoms', 'pain_intensity']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cambios de esquema para completar el documento de requerimientos.
 */
return new class extends Migration
{
    public function up(): void
    {
        /* 1.10 Bitácora de accesos */
        Schema::create('login_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // login | logout | failed
            $table->string('event', 20);
            // Correo con el que se intentó entrar (útil en intentos fallidos).
            $table->string('email')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });

        /* 7.5 y 9.4 Diagnósticos y procedimientos por especialidad */
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->foreignId('specialty_id')->nullable()->after('category')->constrained('specialties')->nullOnDelete();
        });

        Schema::table('procedures', function (Blueprint $table) {
            $table->foreignId('specialty_id')->nullable()->after('category')->constrained('specialties')->nullOnDelete();
        });

        /* 8.2 Áreas de clínica ligadas a una especialidad */
        Schema::table('clinic_areas', function (Blueprint $table) {
            $table->foreignId('specialty_id')->nullable()->after('clinic_id')->constrained('specialties')->nullOnDelete();
        });

        /* 8.4 Equipo o instrumental */
        Schema::table('clinic_equipment', function (Blueprint $table) {
            $table->string('kind', 20)->default('equipo')->after('clinic_id');
        });

        /* 11.2 Tipo de artículo: material, instrumental o equipo */
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('kind', 20)->default('material')->after('category');
        });

        /* 11.11 Almacén de laboratorio */
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('space_id')->nullable()->after('clinic_id')->constrained('spaces')->nullOnDelete();
        });

        /* 11.12 Entregas a alumnos y devoluciones */
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('student_profile_id')->nullable()->after('supplier_id')->constrained('student_profiles')->nullOnDelete();
        });

        /* 12.11 Gastos de laboratorio */
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('space_id')->nullable()->after('clinic_id')->constrained('spaces')->nullOnDelete();
        });

        /*
         * 12.4 y 12.9 Ingresos sin adeudo (cursos, externos, donativos): el pago puede
         * no tener alumno ni paciente; entonces se guarda el nombre de quien paga.
         */
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payer_type')->nullable()->change();
            $table->unsignedBigInteger('payer_id')->nullable()->change();
            $table->string('payer_name')->nullable()->after('payer_id');
            $table->string('concept')->nullable()->after('payer_name');
            $table->foreignId('fee_concept_id')->nullable()->after('concept')->constrained('fee_concepts')->nullOnDelete();
            $table->foreignId('activity_participant_id')->nullable()->after('fee_concept_id')->constrained('activity_participants')->nullOnDelete();
        });

        /* 12.12 Movimientos de caja que no son cobros ni gastos */
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_session_id')->constrained('cash_register_sessions')->cascadeOnDelete();
            // deposito (entra efectivo) | retiro (sale efectivo)
            $table->string('type', 20);
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_participant_id');
            $table->dropConstrainedForeignId('fee_concept_id');
            $table->dropColumn(['payer_name', 'concept']);
        });

        Schema::table('expenses', fn (Blueprint $table) => $table->dropConstrainedForeignId('space_id'));
        Schema::table('inventory_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('student_profile_id'));
        Schema::table('warehouses', fn (Blueprint $table) => $table->dropConstrainedForeignId('space_id'));
        Schema::table('inventory_items', fn (Blueprint $table) => $table->dropColumn('kind'));
        Schema::table('clinic_equipment', fn (Blueprint $table) => $table->dropColumn('kind'));
        Schema::table('clinic_areas', fn (Blueprint $table) => $table->dropConstrainedForeignId('specialty_id'));
        Schema::table('procedures', fn (Blueprint $table) => $table->dropConstrainedForeignId('specialty_id'));
        Schema::table('diagnoses', fn (Blueprint $table) => $table->dropConstrainedForeignId('specialty_id'));

        Schema::dropIfExists('login_events');
    }
};

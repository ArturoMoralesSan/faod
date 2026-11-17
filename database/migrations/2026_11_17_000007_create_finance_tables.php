<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finanzas: conceptos de cobro, adeudos, pagos (con aplicación a adeudos),
 * gastos, cajas y cortes de caja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_concepts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            // colegiatura | inscripcion | tratamiento | curso | constancia | otro
            $table->string('category', 30)->default('otro');
            $table->decimal('amount', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /*
        | Turno de caja: se abre con un fondo y se cierra con el corte.
        */
        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained('cash_registers')->cascadeOnDelete();
            // abierta | cerrada
            $table->string('status', 20)->default('abierta');
            $table->decimal('opening_amount', 10, 2)->default(0);
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('opened_at');
            $table->decimal('expected_cash', 10, 2)->nullable();
            $table->decimal('counted_cash', 10, 2)->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cash_register_id', 'status']);
        });

        /*
        | Adeudo de un alumno o paciente (payer polimórfico).
        */
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->morphs('payer');
            $table->foreignId('fee_concept_id')->nullable()->constrained('fee_concepts')->nullOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
            $table->foreignId('treatment_procedure_id')->nullable()->unique()->constrained('treatment_procedures')->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->foreignId('discount_authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->date('due_date')->nullable();
            // pendiente | parcial | pagado | cancelado
            $table->string('status', 20)->default('pendiente');
            $table->string('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->morphs('payer');
            $table->foreignId('cash_register_session_id')->nullable()->constrained('cash_register_sessions')->nullOnDelete();
            // efectivo | tarjeta | transferencia | deposito
            $table->string('method', 20);
            $table->decimal('amount', 10, 2);
            $table->string('reference', 100)->nullable();
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('paid_at');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('charge_id')->constrained('charges')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            // material | mantenimiento | servicios | papeleria | otro
            $table->string('category', 30);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('method', 20);
            $table->dateTime('paid_at');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            // Gasto pagado en efectivo desde una caja abierta.
            $table->foreignId('cash_register_session_id')->nullable()->constrained('cash_register_sessions')->nullOnDelete();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('charges');
        Schema::dropIfExists('cash_register_sessions');
        Schema::dropIfExists('cash_registers');
        Schema::dropIfExists('fee_concepts');
    }
};

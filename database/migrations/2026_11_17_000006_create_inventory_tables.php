<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario: proveedores, almacenes, artículos, lotes, movimientos y conteos físicos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rfc', 13)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        /*
        | Almacén general o almacén de una clínica.
        */
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('category', 60)->nullable();
            // pza, caja, ml, g, par…
            $table->string('unit', 20)->default('pza');
            $table->decimal('minimum_stock', 12, 2)->default(0);
            // Material con lote y caducidad (anestésicos, resinas…).
            $table->boolean('tracks_expiration')->default(false);
            $table->foreignId('default_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        /*
        | Existencia por lote y almacén. Para artículos sin caducidad hay un
        | solo "lote" genérico por almacén.
        */
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('lot_number', 50)->nullable();
            $table->date('expires_at')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->date('received_at')->nullable();
            $table->timestamps();

            $table->index(['inventory_item_id', 'warehouse_id']);
            $table->index('expires_at');
        });

        /*
        | Kárdex: cada entrada, salida, ajuste o traspaso.
        | quantity es positiva para entradas y negativa para salidas.
        */
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            // entrada | salida | ajuste | traspaso | consumo
            $table->string('type', 20);
            $table->decimal('quantity', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('reason')->nullable();
            $table->string('reference', 100)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('treatment_procedure_id')->nullable()->constrained('treatment_procedures')->nullOnDelete();
            $table->foreignId('inventory_count_id')->nullable();
            // Movimiento par de un traspaso.
            $table->foreignId('related_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('moved_at');
            $table->timestamps();

            $table->index(['inventory_item_id', 'moved_at']);
            $table->index(['warehouse_id', 'moved_at']);
            $table->index('type');
        });

        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            // abierto | cerrado
            $table->string('status', 20)->default('abierto');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained('inventory_counts')->cascadeOnDelete();
            $table->foreignId('inventory_lot_id')->constrained('inventory_lots')->cascadeOnDelete();
            $table->decimal('system_quantity', 12, 2);
            $table->decimal('counted_quantity', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['inventory_count_id', 'inventory_lot_id']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreign('inventory_count_id')->references('id')->on('inventory_counts')->nullOnDelete();
        });

        /*
        | Material de un procedimiento tomado del inventario.
        */
        Schema::table('procedure_materials', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable()->after('treatment_procedure_id')->constrained('inventory_items')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('inventory_item_id')->constrained('warehouses')->nullOnDelete();
        });

        DB::table('warehouses')->insert([
            'code' => 'ALM',
            'name' => 'Almacén general',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('procedure_materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('inventory_item_id');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['inventory_count_id']);
        });

        Schema::dropIfExists('inventory_count_lines');
        Schema::dropIfExists('inventory_counts');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_lots');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('suppliers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Catálogos simples: tipos de documento, parentescos, métodos de pago,
        | tipos de sangre, unidades de medida, etc.
        */
        Schema::create('catalog_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // no se puede eliminar
            $table->timestamps();
        });

        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_type_id')
                ->constrained('catalog_types')
                ->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name');
            $table->json('meta')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['catalog_type_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
        Schema::dropIfExists('catalog_types');
    }
};

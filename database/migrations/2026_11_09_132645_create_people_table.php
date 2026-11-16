<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            // Identificación
            $table->string('curp', 18)->nullable()->unique();
            $table->string('rfc', 13)->nullable()->unique();

            // Datos personales
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('paternal_surname', 100);
            $table->string('maternal_surname', 100)->nullable();

            $table->date('birth_date')->nullable();
            $table->string('gender', 30)->nullable();

            // Contacto
            $table->string('phone', 20)->nullable();
            $table->string('personal_email')->nullable();

            // Domicilio
            $table->string('street')->nullable();
            $table->string('exterior_number', 20)->nullable();
            $table->string('interior_number', 20)->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country', 100)->default('México');

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'paternal_surname',
                'maternal_surname',
                'first_name',
            ], 'people_name_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};

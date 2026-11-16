<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        /*
        | Aulas, laboratorios, consultorios y auditorios en una sola tabla.
        */
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinic_id')
                ->nullable()
                ->constrained('clinics')
                ->nullOnDelete();

            // aula | laboratorio | consultorio | auditorio | otro
            $table->string('type', 30);
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('building')->nullable();
            $table->string('floor', 20)->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
        Schema::dropIfExists('clinics');
    }
};

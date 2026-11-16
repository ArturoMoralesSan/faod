<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Generaciones
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_program_id')
                ->constrained('academic_programs')
                ->cascadeOnDelete();

            $table->string('name'); // p. ej. 2026-2029
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            // planeada | en_curso | concluida
            $table->string('status', 30)->default('planeada');
            $table->timestamps();

            $table->unique(['academic_program_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cohorts');
    }
};

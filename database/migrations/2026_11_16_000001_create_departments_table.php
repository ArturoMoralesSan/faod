<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            // departamento | coordinacion
            $table->string('type', 30)->default('departamento');
            $table->string('code', 30)->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->foreignId('head_people_id')
                ->nullable()
                ->constrained('people')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};

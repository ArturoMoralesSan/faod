<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('credits', 5, 2)->nullable();
            $table->unsignedSmallInteger('theory_hours')->default(0);
            $table->unsignedSmallInteger('practice_hours')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Planes académicos internos
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_program_id')
                ->constrained('academic_programs')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('version', 20);
            $table->date('valid_from')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academic_program_id', 'version']);
        });

        Schema::create('academic_modules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('study_plan_id')
                ->constrained('study_plans')
                ->cascadeOnDelete();

            $table->string('name');
            $table->unsignedSmallInteger('period_number')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('study_plan_subject', function (Blueprint $table) {
            $table->id();

            $table->foreignId('study_plan_id')
                ->constrained('study_plans')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->foreignId('academic_module_id')
                ->nullable()
                ->constrained('academic_modules')
                ->nullOnDelete();

            $table->unsignedSmallInteger('period_number'); // semestre/periodo
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['study_plan_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan_subject');
        Schema::dropIfExists('academic_modules');
        Schema::dropIfExists('study_plans');
        Schema::dropIfExists('subjects');
    }
};

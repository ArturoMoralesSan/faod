<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "groups" es palabra reservada en MySQL 8, por eso academic_groups.
        Schema::create('academic_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cohort_id')
                ->constrained('cohorts')
                ->cascadeOnDelete();

            $table->foreignId('academic_period_id')
                ->constrained('academic_periods')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('period_number'); // semestre que curso
            $table->string('code', 30);
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();

            $table->unique(['academic_period_id', 'code']);
        });

        Schema::create('academic_group_student', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_group_id')
                ->constrained('academic_groups')
                ->cascadeOnDelete();

            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['academic_group_id', 'student_profile_id'], 'group_student_unique');
        });

        // Materia impartida a un grupo por un docente.
        Schema::create('class_sections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_group_id')
                ->constrained('academic_groups')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->foreignId('teacher_profile_id')
                ->nullable()
                ->constrained('teacher_profiles')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['academic_group_id', 'subject_id']);
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_section_id')
                ->constrained('class_sections')
                ->cascadeOnDelete();

            $table->foreignId('space_id')
                ->nullable()
                ->constrained('spaces')
                ->nullOnDelete();

            $table->unsignedTinyInteger('day_of_week'); // 1 = lunes ... 7 = domingo
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['day_of_week', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('class_sections');
        Schema::dropIfExists('academic_group_student');
        Schema::dropIfExists('academic_groups');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialty_teacher_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained('specialties')->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['specialty_id', 'teacher_profile_id'], 'specialty_teacher_unique');
        });

        Schema::create('teacher_degrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            // licenciatura | especialidad | maestria | doctorado | posdoctorado
            $table->string('level', 30);
            $table->string('title');
            $table->string('institution');
            $table->string('license_number', 50)->nullable(); // cédula
            $table->date('obtained_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->string('name');
            $table->string('issuer')->nullable();
            $table->string('folio', 100)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->string('institution');
            $table->string('position');
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('knowledge_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('knowledge_area_teacher_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_area_id')->constrained('knowledge_areas')->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['knowledge_area_id', 'teacher_profile_id'], 'knowledge_area_teacher_unique');
        });

        Schema::create('teacher_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = lunes ... 7 = domingo
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_availabilities');
        Schema::dropIfExists('knowledge_area_teacher_profile');
        Schema::dropIfExists('knowledge_areas');
        Schema::dropIfExists('teacher_experiences');
        Schema::dropIfExists('teacher_certifications');
        Schema::dropIfExists('teacher_degrees');
        Schema::dropIfExists('specialty_teacher_profile');
    }
};

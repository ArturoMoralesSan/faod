<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('people_id')
                ->constrained('people')
                ->cascadeOnDelete()
                ->unique();

            $table->string('employee_number', 50)->nullable()->unique();
            $table->string('professional_license', 50)->nullable()->unique();

            $table->string('professional_title')->nullable();
            $table->string('highest_degree')->nullable();
            $table->string('specialty')->nullable();

            $table->unsignedSmallInteger('years_experience')->nullable();

            $table->string('status', 30)->default('active');
            $table->text('professional_biography')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};

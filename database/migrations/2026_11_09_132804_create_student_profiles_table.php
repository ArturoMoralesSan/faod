<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('people_id')
                ->constrained('people')
                ->cascadeOnDelete()
                ->unique();

            $table->string('registration_number', 50)->unique();

            $table->string('status', 30)->default('active');
            $table->date('admission_date')->nullable();

            $table->text('internal_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};

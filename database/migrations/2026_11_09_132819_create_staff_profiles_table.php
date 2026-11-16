<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('people_id')
                ->constrained('people')
                ->cascadeOnDelete()
                ->unique();

            $table->string('employee_number', 50)->nullable()->unique();

            $table->string('position');
            $table->string('department')->nullable();

            $table->string('status', 30)->default('active');
            $table->date('hired_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // p. ej. 2026-B
            $table->string('name');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};

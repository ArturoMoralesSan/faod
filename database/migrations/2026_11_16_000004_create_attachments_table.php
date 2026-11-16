<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Archivos adjuntos polimórficos: comprobantes de inscripción,
        | documentos de alumnos y docentes, materiales de cursos, etc.
        */
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            $table->morphs('attachable');

            $table->foreignId('document_type_id')
                ->nullable()
                ->constrained('catalog_items')
                ->nullOnDelete();

            $table->string('title');
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};

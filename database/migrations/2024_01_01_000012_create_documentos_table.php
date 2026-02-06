<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $table->foreignId('tipo_documento_id')->nullable()->constrained('tipos_documento')->nullOnDelete();
            $table->string('nombre_archivo', 255)->nullable();
            $table->text('url_almacenamiento')->nullable()->comment('S3 / Blob Storage URL');
            $table->string('estado_validacion', 50)->nullable()->comment('PENDIENTE, APROBADO, RECHAZADO');
            $table->foreignId('subido_por_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->smallInteger('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seguimiento_mensual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->onDelete('cascade');
            $table->integer('mes'); // 1-12
            $table->enum('fuente', ['SECOP', 'SIA']);
            $table->string('estado')->default('PENDIENTE');
            $table->timestamps();

            // Evitar duplicados para el mismo mes y fuente en un contrato
            $table->unique(['contrato_id', 'mes', 'fuente']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimiento_mensual');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metricas_diarias', function (Blueprint $table) {
            $table->date('fecha');
            $table->foreignId('bloque_id')->constrained('bloques_workflow')->cascadeOnDelete();
            $table->integer('cantidad_procesada')->default(0);
            $table->decimal('promedio_tiempo_horas', 10, 2)->nullable();
            $table->integer('cumplimiento_sla_pct')->nullable();
            
            $table->primary(['fecha', 'bloque_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metricas_diarias');
    }
};

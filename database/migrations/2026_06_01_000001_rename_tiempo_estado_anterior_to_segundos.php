<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Renombra el campo para eliminar ambigüedad semántica.
     * El nombre anterior (_minutos) causó confusión y bugs históricos.
     * El nuevo nombre (_segundos) es explícito y correcto.
     * 
     * Nota: Esta migración debe ejecutarse DESPUÉS de la normalización
     * de consistencia para garantizar que todos los valores sean segundos.
     */
    public function up(): void
    {
        Schema::table('historial_workflow', function (Blueprint $table) {
            $table->renameColumn('tiempo_en_estado_anterior_minutos', 'tiempo_en_estado_anterior_segundos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_workflow', function (Blueprint $table) {
            $table->renameColumn('tiempo_en_estado_anterior_segundos', 'tiempo_en_estado_anterior_minutos');
        });
    }
};

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
        Schema::table('contratos', function (Blueprint $table) {
            $table->string('tipo_contratista')->nullable()->after('contratista_id');
            $table->string('no_planta')->nullable()->after('planta_id');
            $table->string('concepto_precontractual')->nullable()->after('concepto_id');

            // Checklist extendido
            $table->string('soportes_status')->nullable()->default('PENDIENTE');
            $table->string('acuerdo_confidencialidad_status')->nullable()->default('PENDIENTE');
            $table->string('arl_status')->nullable()->default('PENDIENTE');

            // Roles
            $table->string('contador_responsable')->nullable()->after('abogado_responsable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            //
        });
    }
};

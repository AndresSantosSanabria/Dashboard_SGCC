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
            $table->string('plazo_ejecucion')->nullable();
            $table->string('secop_estado_contrato')->nullable();
            $table->string('aprobado_y_pagado')->nullable();
            $table->string('modificaciones_y_cierre')->nullable();

            // 12 Cuentas con sus dos estados
            for ($i = 1; $i <= 12; $i++) {
                $table->string("cta{$i}_secop_status")->nullable()->default('PENDIENTE');
                $table->string("cta{$i}_sia_status")->nullable()->default('PENDIENTE');
            }

            // Campos de Liquidación y Cierre
            $table->string('evaluacion_proveedor_status')->nullable();
            $table->string('acta_cierre_expediente_status')->nullable();
            $table->string('requiere_acta_liq_status')->nullable();
            $table->string('acta_liq_repositorio_status')->nullable();
            $table->string('acta_liq_secop_status')->nullable();
            $table->string('acta_liq_sia_status')->nullable();

            $table->decimal('saldo', 19, 2)->default(0);
            $table->text('observacion_1_razon')->nullable();
            $table->text('observacion_2_accion')->nullable();
            $table->text('razon_no_liquidacion')->nullable();
            $table->string('abogado_responsable')->nullable();
            $table->string('ops_juridico')->nullable();
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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TABLA 18 DE 23: CUENTAS DE COBRO
     * Núcleo del sistema - Cuentas de cobro con workflow
     */
    public function up(): void
    {
        Schema::create('cuentas_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->onDelete('cascade');
            $table->string('numero_cuenta', 50)->comment('NUMERO DE CUENTA del Excel (col 10)');
            $table->decimal('valor_cobro', 19, 2);
            $table->dateTime('fecha_radicacion')->nullable()->comment('FECHA DE RADICACIÓN del Excel (col 19)');
            $table->integer('numero_pagos_totales')->nullable()->comment('NUMERO DE PAGOS TOTALES del Excel (col 11)');
            $table->integer('numero_facturas_radicadas')->default(0)->comment('N° FACTURAS RADICADAS del Excel (col 12)');
            $table->decimal('porcentaje_cuentas', 5, 2)->nullable()->comment('PORCENTAJE DE CUENTAS del Excel (col 13)');
            $table->string('radicado_por', 100)->nullable()->comment('RADICADO POR del Excel (col 18)');
            $table->foreignId('bloque_actual_id')->constrained('bloques_workflow');
            $table->foreignId('estado_actual_id')->constrained('estados_workflow');
            $table->boolean('finalizada')->default(false)->comment('TRUE cuando completa bloque 5');
            $table->foreignId('responsable_actual_id')->nullable()->constrained('usuarios');
            $table->text('observaciones')->nullable()->comment('OBSERVACIONES del Excel (col 20)');
            $table->timestamps();
            
            $table->index('numero_cuenta');
            $table->index('contrato_id');
            $table->index(['bloque_actual_id', 'estado_actual_id']);
            $table->index('finalizada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_cobro');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $table->string('numero_cuenta', 50)->unique();
            $table->decimal('valor_cobro', 19, 2);
            $table->dateTime('fecha_radicacion')->nullable();
            $table->integer('numero_pagos_totales')->nullable();
            $table->integer('numero_facturas_radicadas')->nullable();
            $table->decimal('porcentaje_cuentas', 5, 2)->nullable();
            $table->string('mes_planilla_seguridad_social', 20)->nullable();
            $table->string('radicado_por', 100)->nullable();
            $table->boolean('finalizada')->default(false);
            $table->foreignId('responsable_actual_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->integer('diferencia_cuentas')->nullable()->comment('Diferencia entre cuentas totales vs radicadas');
            $table->string('ultima_factura_hacienda', 50)->nullable()->comment('Número de última factura radicada en hacienda');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_cobro');
    }
};

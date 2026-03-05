<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SEGUIMIENTO MANIFIESTO (Normalización Post-Migración)
     * 
     * Este archivo desglosa los antiguos campos planos del contrato en tablas 
     * relacionales específicas. Esto permite un historial ilimitado de seguimientos 
     * sin alterar la estructura de la tabla contratos.
     */
    public function up(): void
    {
        // Seguimiento Mensual (Unificado SECOP, SIA, REP)
        // Sustituye a las 24 columnas (cta1...cta12) que existían anteriormente.
        // Ahora podemos filtrar por año y mes de forma nativa en SQL.
        Schema::create('seguimiento_mensual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();

            $table->integer('mes'); // 1-12
            $table->year('anio');

            // Fuente: Identifica si el seguimiento viene de SECOP, SIA o Repositorio Interno.
            $table->enum('fuente', ['SECOP', 'SIA', 'REP']);
            $table->string('estado')->nullable(); // Ej: PENDIENTE, CARGADO, ERROR

            // Datos financieros del pago mensual
            $table->decimal('valor_pago', 19, 2)->nullable();
            $table->text('actividades_realizadas')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Garantizamos que no se repita el seguimiento para la misma fuente/mes/año
            $table->unique(['contrato_id', 'mes', 'anio', 'fuente'], 'uk_seguimiento_mensual_unico');
        });

        // Seguimiento de Requisitos (Checklist Documental)
        // Convierte el checklist estático en una lista dinámica de requisitos cumplidos.
        Schema::create('seguimiento_requisitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();

            // Nombre del requisito (Ej: arl, poliza, rpc)
            $table->string('nombre', 100);
            $table->string('estado')->nullable();
            $table->date('fecha_verificacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Un contrato solo tiene un estado por cada requisito.
            $table->unique(['contrato_id', 'nombre'], 'uk_seguimiento_requisito_contrato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_requisitos');
        Schema::dropIfExists('seguimiento_mensual');
    }
};

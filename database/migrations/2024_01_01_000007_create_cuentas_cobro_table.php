<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CUENTAS DE COBRO (El motor del Workflow)
     * 
     * Esta es la tabla con mayor dinamismo del sistema. Implementa un patrón 
     * de Máquina de Estados, donde cada cuenta "vive" en un Bloque y Estado específico.
     */
    public function up(): void
    {
        Schema::create('cuentas_cobro', function (Blueprint $table) {
            $table->id();

            // Relación fuerte: Si se borra el contrato, la gestión de sus cuentas desaparece.
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();

            // Datos identificadores y financieros
            $table->string('numero_cuenta', 50)->comment('ID de cuenta (Columna 10 Excel)');
            $table->decimal('valor_cobro', 19, 2);
            $table->dateTime('fecha_radicacion')->nullable();

            // Trazabilidad de pagos y facturas
            $table->integer('numero_pagos_totales')->nullable();
            $table->integer('numero_facturas_radicadas')->default(0);
            $table->decimal('porcentaje_cuentas', 5, 2)->nullable();
            $table->string('radicado_por', 100)->nullable();

            // ESTADO DEL WORKFLOW
            // Estos campos controlan la posición de la cuenta en el tablero Kanban.
            $table->foreignId('bloque_actual_id')->constrained('bloques_workflow');
            $table->foreignId('estado_actual_id')->constrained('estados_workflow');

            // Control de finalización: Indica que la cuenta ha pasado por todos los filtros.
            $table->boolean('finalizada')->default(false);

            // Responsable actual: El usuario que tiene la "pelota" en su cancha actualmente.
            $table->foreignId('responsable_actual_id')->nullable()->constrained('usuarios');
            $table->text('observaciones')->nullable();

            // Campos de integración con el área de Hacienda (Post-radicación interna)
            $table->string('ultima_factura_hacienda', 50)->nullable();
            $table->dateTime('fecha_radicacion_hacienda')->nullable();
            $table->text('observacion_hacienda')->nullable();

            $table->softDeletes(); // Integridad: No borramos realmente, marcamos como eliminado.
            $table->timestamps();

            // ÍNDICES DE RENDIMIENTO:
            // Cruciales para que el dashboard cargue rápido al filtrar por bloque/estado.
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

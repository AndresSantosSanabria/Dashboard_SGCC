<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CONTRATOS Y REGISTROS PRESUPUESTALES
     * 
     * Esta migración define el núcleo legal y financiero del proyecto.
     * Hemos optado por una estructura normalizada (3NF) para evitar la redundancia 
     * que solía venir de los reportes en Excel, garantizando que cada entidad 
     * (Contratistas, Supervisores, Modalidades) tenga su propia "fuente de verdad".
     */
    public function up(): void
    {
        // TABLA: Contratos
        // Es la entidad principal. Centraliza la información del proceso contractual
        // y vincula los actores responsables del seguimiento.
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();

            // Usamos un índice único en numero_contrato porque es nuestra clave de negocio.
            // Esto evita duplicados accidentales incluso si la lógica de validación falla.
            $table->string('numero_proceso', 50)->nullable();
            $table->string('numero_contrato', 50)->unique()->comment('Identificador único del contrato (Columna 1 del Excel)');

            // Relaciones normalizadas: Preferimos IDs sobre nombres en texto para:
            // 1. Integridad referencial (no podemos borrar un contratista con contratos activos).
            // 2. Performance en búsquedas y reportes consolidados.
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidades');
            $table->foreignId('contratista_id')->constrained('contratistas');
            $table->foreignId('tipo_contratista_id')->nullable()->constrained('tipos_contratista');
            $table->foreignId('supervisor_id')->nullable()->constrained('supervisores');

            $table->text('objeto')->nullable();
            $table->decimal('monto_total', 19, 2); // 19,2 para manejar grandes cifras con precisión monetaria

            // Ubicación y Conceptos
            $table->foreignId('planta_id')->nullable()->constrained('plantas');
            $table->string('no_planta')->nullable();
            $table->foreignId('concepto_id')->nullable()->constrained('conceptos');
            $table->string('concepto_precontractual')->nullable();

            // Integración con SECOP y trazabilidad financiera inicial
            $table->string('cdp_codigo', 50)->nullable();
            $table->string('link_secop')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('plazo_ejecucion')->nullable();
            $table->foreignId('estado_secop_id')->nullable()->constrained('estados_contrato_secop');

            // Campos de estado para reportes ejecutivos rápidos
            $table->string('aprobado_y_pagado')->nullable();
            $table->string('modificaciones_y_cierre')->nullable();
            $table->decimal('saldo', 19, 2)->default(0); // Cálculo derivado para visibilidad inmediata

            // Notas de gestión humana y administrativa
            $table->text('observacion_1_razon')->nullable();
            $table->text('observacion_2_accion')->nullable();
            $table->text('razon_no_liquidacion')->nullable();

            // Campos de Compatibilidad Excel/BI (Strings para facilitar importación)
            $table->string('tipo_contratista')->nullable();
            $table->string('secop_estado_contrato')->nullable();
            $table->string('abogado_responsable')->nullable();
            $table->string('contador_responsable')->nullable();

            // RESPONSABLES (Normalización a Usuarios del sistema)
            // Permitimos auditoría de quién debe gestionar cada contrato en el flujo interno.
            $table->foreignId('abogado_user_id')->nullable()->constrained('usuarios');
            $table->foreignId('contador_user_id')->nullable()->constrained('usuarios');
            $table->foreignId('ops_user_id')->nullable()->constrained('usuarios');

            $table->boolean('es_activo')->default(true);
            $table->timestamps();

            // ÍNDICES ESTRATÉGICOS: Optimizamos las búsquedas frecuentes.
            $table->index('numero_proceso');
            $table->index('contratista_id');
            $table->index('supervisor_id');
            $table->index('modalidad_id');
            $table->index('planta_id');
            $table->index('concepto_id');
            $table->index('secop_estado_contrato');
            $table->index('es_activo');
        });

        // TABLA: Registros Presupuestales (RP)
        // Un contrato puede tener múltiples RPs asociados (Relación 1:N).
        Schema::create('registros_presupuestales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();

            $table->string('numero_rp', 50)->comment('Número de RP asignado');
            $table->date('fecha_rp')->nullable();
            $table->decimal('valor_rp', 19, 2)->nullable();
            $table->string('estado', 50)->default('ACTIVO');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('numero_rp');
            $table->index('contrato_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_presupuestales');
        Schema::dropIfExists('contratos');
    }
};

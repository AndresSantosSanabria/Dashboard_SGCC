<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MONITOREO DE CONTRATOS - SEPARACIÓN DE CONTADORES
     *
     * Problema raíz: el sistema confundía "tiempo en el estado actual"
     * (volátil, debe resetearse) con "tiempo total del proceso"
     * (persistente, no debe resetearse jamás).
     *
     * Solución:
     *  - fecha_ultimo_cambio_estado : Timestamp DB, se actualiza en cada
     *    cambio de estado. La alerta de reposo se calcula como:
     *    (NOW() - fecha_ultimo_cambio_estado) >= tiempo_limite del estado.
     *  - tiempo_total_proceso_segundos : Acumulado desde created_at,
     *    nunca se resetea. Reemplaza tiempo_total_segundos para el reporte.
     *  - tiempo_limite_horas en estados_workflow : permite configurar el
     *    umbral de alerta por tipo de estado (sin valores "quemados").
     */
    public function up(): void
    {
        // 1. Añadir campos a cuentas_cobro
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            // VOLÁTIL: Se resetea en cada cambio de estado.
            // Es la fuente de verdad para "¿cuánto lleva en el estado actual?"
            $table->timestamp('fecha_ultimo_cambio_estado')
                  ->nullable()
                  ->after('ultimo_inicio_conteo')
                  ->comment('Se actualiza en cada transición. Para calcular reposo en estado actual.');

            // PERSISTENTE: Solo aumenta, nunca se resetea.
            // Calculado como SUM del historial + tiempo desde ultimo cambio.
            $table->bigInteger('tiempo_total_proceso_segundos')
                  ->default(0)
                  ->after('fecha_ultimo_cambio_estado')
                  ->comment('Tiempo total acumulado del ciclo. Nunca se resetea al cambiar estado.');
        });

        // 2. Añadir tiempo_limite_horas a estados_workflow
        // Permite que cada estado tenga su propio umbral de alerta configurable.
        Schema::table('estados_workflow', function (Blueprint $table) {
            $table->decimal('tiempo_limite_horas', 8, 2)
                  ->nullable()
                  ->after('contabiliza_tiempo')
                  ->comment('Horas máximas permitidas en este estado antes de generar alerta. NULL = usar config global.');
        });

        // 3. Inicializar fecha_ultimo_cambio_estado con updated_at de las cuentas existentes
        // para no generar falsas alertas en el primer arranque.
        DB::statement("
            UPDATE cuentas_cobro
            SET fecha_ultimo_cambio_estado = COALESCE(ultimo_inicio_conteo, updated_at, created_at)
            WHERE fecha_ultimo_cambio_estado IS NULL
        ");

        // 4. Inicializar tiempo_total_proceso_segundos desde tasks_time_log + tiempo_total_segundos actual
        DB::statement("
            UPDATE cuentas_cobro cc
            SET tiempo_total_proceso_segundos = COALESCE(cc.tiempo_total_segundos, 0) +
                COALESCE((
                    SELECT SUM(tl.duracion_segundos)
                    FROM tasks_time_log tl
                    WHERE tl.cuenta_cobro_id = cc.id
                      AND tl.end_time IS NOT NULL
                ), 0)
        ");
    }

    public function down(): void
    {
        Schema::table('estados_workflow', function (Blueprint $table) {
            $table->dropColumn('tiempo_limite_horas');
        });

        Schema::table('cuentas_cobro', function (Blueprint $table) {
            $table->dropColumn(['fecha_ultimo_cambio_estado', 'tiempo_total_proceso_segundos']);
        });
    }
};

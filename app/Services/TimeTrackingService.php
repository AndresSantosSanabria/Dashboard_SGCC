<?php

namespace App\Services;

use App\Models\CuentaCobro;
use App\Models\TaskTimeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SERVICIO DE TRACKING DE TIEMPOS - CONTADORES DUALES
 *
 * Arquitectura de dos contadores independientes:
 *
 * ┌─────────────────────────────────────────────────────────────────────┐
 * │  CONTADOR 1: VOLÁTIL (por estado)                                   │
 * │  Campo: fecha_ultimo_cambio_estado (timestamp DB)                   │
 * │  Cálculo: NOW() - fecha_ultimo_cambio_estado                        │
 * │  Comportamiento: Se RESETEA en cada cambio de estado                │
 * │  Uso: Alerta de "Contrato Reposado" (inactividad en estado actual)  │
 * ├─────────────────────────────────────────────────────────────────────┤
 * │  CONTADOR 2: PERSISTENTE (del proceso completo)                     │
 * │  Campo: tiempo_total_proceso_segundos (bigint)                      │
 * │  Cálculo: Acumula horas laborales de TODOS los estados anteriores   │
 * │  Comportamiento: NUNCA se resetea. Solo crece.                      │
 * │  Uso: Tiempo total del ciclo de pago (desde radicación)             │
 * └─────────────────────────────────────────────────────────────────────┘
 */
class TimeTrackingService
{
    protected BusinessTimeService $businessTime;

    public function __construct(BusinessTimeService $businessTime)
    {
        $this->businessTime = $businessTime;
    }

    /**
     * TRANSICIÓN: Cierra el estado anterior y abre el nuevo.
     *
     * Se llama desde el Observer ANTES del save() (en 'updating').
     * Realiza las siguientes operaciones atómicas:
     *  1. Calcula el tiempo laborable acumulado en el estado anterior.
     *  2. Suma ese tiempo a tiempo_total_proceso_segundos (PERSISTENTE).
     *  3. Cierra el log abierto en tasks_time_log.
     *  4. Marca el modelo con fecha_ultimo_cambio_estado = null
     *     (el Observer 'updated' lo inicializará con el nuevo now()).
     *
     * IMPORTANTE: Usamos DB::table directamente para los campos persistentes
     * para evitar que el ->save() del modelo los sobreescriba en carrera.
     */
    public function onStateChange(CuentaCobro $cuenta): void
    {
        // Tomamos los valores ORIGINALES (antes del cambio pendiente)
        $fechaUltimoCambio = $cuenta->getOriginal('fecha_ultimo_cambio_estado');
        $tiempoBaseActual  = (int) ($cuenta->getOriginal('tiempo_total_proceso_segundos') ?? 0);
        $estadoOrigenId    = $cuenta->getOriginal('estado_actual_id');

        // --- 1. Calcular tiempo acumulado en el estado que está cerrando ---
        $segundosEstadoCerrado = 0;

        if ($fechaUltimoCambio !== null) {
            // Verificar si el estado de origen contabiliza tiempo
            $estadoOrigen = \App\Models\EstadoWorkflow::find($estadoOrigenId);
            $cuentaTiempo = $estadoOrigen ? $estadoOrigen->contabiliza_tiempo : true;

            if ($cuentaTiempo) {
                // Usamos horas laborales reales
                $segundosEstadoCerrado = $this->businessTime->getWorkingSecondsBetween(
                    Carbon::parse($fechaUltimoCambio),
                    now()
                );
            }
        }

        // --- 2. Cerrar el log abierto de tasks_time_log ---
        $logAbierto = TaskTimeLog::where('cuenta_cobro_id', $cuenta->id)
            ->whereNull('end_time')
            ->orderByDesc('start_time')
            ->first();

        if ($logAbierto) {
            $logAbierto->update([
                'end_time'          => now(),
                'duracion_segundos' => $segundosEstadoCerrado,
                'tipo_cierre'       => 'TRANSICION',
            ]);
        }

        // --- 3. Persistir el contador total SIN tocar el modelo Eloquent ---
        // Lo hacemos vía DB::table para que el ->save() subsiguiente del modelo
        // no sobreescriba este valor (el campo no estará "dirty" en el modelo).
        $nuevoTotal = $tiempoBaseActual + $segundosEstadoCerrado;

        DB::table('cuentas_cobro')
            ->where('id', $cuenta->id)
            ->update(['tiempo_total_proceso_segundos' => $nuevoTotal]);

        // --- 4. Preparar el modelo para el NUEVO estado ---
        // El modelo marcará fecha_ultimo_cambio_estado = null en el save(),
        // y el evento 'updated' del Observer lo inicializará con now().
        // Esto garantiza que al entrar al nuevo estado el contador volátil empiece en 0.
        $cuenta->fecha_ultimo_cambio_estado = null;

        Log::debug("[TimeTracking] Estado cerrado. EstadoId={$estadoOrigenId}, " .
            "Seg cerrado={$segundosEstadoCerrado}, Total={$nuevoTotal}");
    }

    /**
     * INICIO: Registra el timestamp de entrada al nuevo estado.
     *
     * Se llama desde el Observer DESPUÉS del save() (en 'updated').
     * Persiste fecha_ultimo_cambio_estado = now() e inicia un nuevo log.
     */
    public function onStateOpened(CuentaCobro $cuenta): void
    {
        // Refrescar para asegurar que tenemos el estado real de la DB
        $cuenta->refresh();

        // Si ya tiene fecha_ultimo_cambio_estado, el inicio ya fue registrado (no duplicar)
        if ($cuenta->fecha_ultimo_cambio_estado !== null) {
            Log::debug("[TimeTracking] Estado ya iniciado para cuenta {$cuenta->id}. Saliendo.");
            return;
        }

        $ahora = now();

        // Persistir timestamp de inicio del nuevo estado
        DB::table('cuentas_cobro')
            ->where('id', $cuenta->id)
            ->update(['fecha_ultimo_cambio_estado' => $ahora]);

        // Crear log de seguimiento en tasks_time_log
        TaskTimeLog::create([
            'cuenta_cobro_id' => $cuenta->id,
            'estado_id'       => $cuenta->estado_actual_id,
            'usuario_id'      => $cuenta->responsable_actual_id, // Siempre el responsable de la cuenta
            'start_time'      => $ahora,
        ]);

        Log::debug("[TimeTracking] Estado abierto. CuentaId={$cuenta->id}, " .
            "EstadoId={$cuenta->estado_actual_id}, Inicio={$ahora}");
    }

    /**
     * PAUSA POR HORARIO: Cierra la jornada laboral sin cambiar de estado.
     *
     * El contador VOLÁTIL (fecha_ultimo_cambio_estado) NO se actualiza aquí,
     * porque el estado no cambió. El cierre de jornada solo congela el log
     * abierto y acumula el tiempo del día en tiempo_total_proceso_segundos.
     * Al siguiente día, el sistema calcula el delta desde fecha_ultimo_cambio_estado.
     *
     * NOTA: El tiempo de horas no laborales se excluye automáticamente
     * via BusinessTimeService::getWorkingSecondsBetween().
     */
    public function pauseBySchedule(CuentaCobro $cuenta, string $tipoCierre = 'HORARIO'): void
    {
        $cuentaFresh = CuentaCobro::find($cuenta->id);
        if (! $cuentaFresh || ! $cuentaFresh->fecha_ultimo_cambio_estado) {
            return;
        }

        // Para cierre de horario, usamos el fin de jornada como referencia
        $endTime = now();
        if ($tipoCierre === 'HORARIO') {
            $rawEnd  = \App\Models\Configuracion::getValor('HORARIO_LABORAL_FIN', '18:00');
            $endTime = now()->setTimeFromTimeString($rawEnd);
        }

        $segundosDia = $this->businessTime->getWorkingSecondsBetween(
            Carbon::parse($cuentaFresh->fecha_ultimo_cambio_estado),
            $endTime
        );

        // Cerrar log del día
        $logAbierto = TaskTimeLog::where('cuenta_cobro_id', $cuentaFresh->id)
            ->whereNull('end_time')
            ->orderByDesc('start_time')
            ->first();

        if ($logAbierto) {
            $logAbierto->update([
                'end_time'          => $endTime,
                'duracion_segundos' => $segundosDia,
                'tipo_cierre'       => $tipoCierre,
            ]);
        }

        // Acumular tiempo del día en el contador PERSISTENTE
        // NO tocamos fecha_ultimo_cambio_estado porque el estado no cambió.
        DB::table('cuentas_cobro')
            ->where('id', $cuentaFresh->id)
            ->update([
                'tiempo_total_proceso_segundos' => DB::raw(
                    "COALESCE(tiempo_total_proceso_segundos, 0) + {$segundosDia}"
                ),
            ]);

        Log::info("[TimeTracking] Pausa horario. CuentaId={$cuentaFresh->id}, " .
            "Seg={$segundosDia}, Cierre={$tipoCierre}");
    }

    /**
     * CIERRE MASIVO: Pausa todas las tareas activas al final de jornada.
     * Llamado desde el comando sgcc:workday-close.
     */
    public function forceWorkflowClosing(): int
    {
        $cuentasActivas = CuentaCobro::whereNotNull('fecha_ultimo_cambio_estado')
            ->where('finalizada', false)
            ->get();

        foreach ($cuentasActivas as $cuenta) {
            $this->pauseBySchedule($cuenta, 'HORARIO');
        }

        return $cuentasActivas->count();
    }

    // ─── MÉTODOS LEGACY (compatibilidad con código existente) ───────────────

    /**
     * @deprecated Usar onStateOpened()
     */
    public function start(CuentaCobro $cuenta, $startTime = null): void
    {
        $this->onStateOpened($cuenta);
    }

    /**
     * @deprecated Usar onStateChange()
     */
    public function persistAndReset(CuentaCobro $cuenta): void
    {
        $this->onStateChange($cuenta);
    }

    /**
     * @deprecated Usar pauseBySchedule()
     */
    public function pause(CuentaCobro $cuenta, string $tipoCierre = 'MANUAL'): void
    {
        $this->pauseBySchedule($cuenta, $tipoCierre);
    }
}

<?php

namespace App\Observers;

use App\Models\CuentaCobro;
use App\Models\Configuracion;
use App\Models\EstadoWorkflow;
use App\Services\TimeTrackingService;
use App\Jobs\CheckStagnationJob;
use Illuminate\Support\Facades\Log;

class CuentaCobroObserver
{
    protected TimeTrackingService $timeService;

    public function __construct(TimeTrackingService $timeService)
    {
        $this->timeService = $timeService;
    }

    /**
     * ANTES DE GUARDAR: Cierra el estado anterior.
     *
     * Cuando el estado_actual_id cambia, cerramos el cronómetro del estado
     * saliente y acumulamos su tiempo en tiempo_total_proceso_segundos.
     */
    public function updating(CuentaCobro $cuenta): void
    {
        $estadoCambiado = $cuenta->isDirty('estado_actual_id')
            && $cuenta->getOriginal('estado_actual_id') != $cuenta->estado_actual_id;

        $bloqueCambiado = $cuenta->isDirty('bloque_actual_id')
            && $cuenta->getOriginal('bloque_actual_id') != $cuenta->bloque_actual_id;

        $responsableCambiado = $cuenta->isDirty('responsable_actual_id')
            && $cuenta->getOriginal('responsable_actual_id') != $cuenta->responsable_actual_id;

        if ($estadoCambiado || $bloqueCambiado || $responsableCambiado) {
            // Cierra el contador volátil y acumula en el contador persistente.
            // NO genera dirty en el modelo para tiempo_total_proceso_segundos,
            // ya que la escritura se hace vía DB::table directamente.
            $this->timeService->onStateChange($cuenta);
        }
    }

    /**
     * DESPUÉS DE GUARDAR: Abre el nuevo estado.
     *
     * Después de que el modelo se guardó con el nuevo estado_actual_id,
     * registramos fecha_ultimo_cambio_estado = now() para comenzar a medir
     * el tiempo en el nuevo estado (contador volátil).
     */
    public function updated(CuentaCobro $cuenta): void
    {
        if ($cuenta->wasChanged('estado_actual_id') || $cuenta->wasChanged('bloque_actual_id') || $cuenta->wasChanged('responsable_actual_id')) {
            // Abre el cronómetro del estado entrante.
            $this->timeService->onStateOpened($cuenta);

            // Despacha el Job de monitoreo de estancamiento para el NUEVO estado.
            if ($cuenta->wasChanged('estado_actual_id') || $cuenta->wasChanged('bloque_actual_id')) {
                $this->dispatchStagnationCheck($cuenta);
            }
        }
    }

    /**
     * AL CREAR: Inicia el primer cronómetro.
     */
    public function created(CuentaCobro $cuenta): void
    {
        $this->timeService->onStateOpened($cuenta);
        $this->dispatchStagnationCheck($cuenta);
    }

    /**
     * Despacha el Job de estancamiento con delay configurable.
     *
     * CLAVE: El Job guarda el estado_actual_id en el momento del despacho.
     * Cuando se ejecuta (X minutos después), si el estado cambió, el Job
     * cancela su propia ejecución. Esto asegura que el Job siempre es
     * relevante para el estado que lo originó.
     *
     * El delay ahora se toma del campo tiempo_limite_horas del estado,
     * o hace fallback a la configuración global.
     */
    private function dispatchStagnationCheck(CuentaCobro $cuenta): void
    {
        try {
            // Prioridad 1: tiempo_limite_horas específico del estado
            $estado = EstadoWorkflow::find($cuenta->estado_actual_id);
            $limiteHoras = $estado?->tiempo_limite_horas;

            // Prioridad 2: config global en minutos
            if ($limiteHoras === null) {
                $minutosConfig = (int) Configuracion::getValor('ALERTA_ESTANCAMIENTO_MINUTOS', 120);
                $limiteHoras   = $minutosConfig / 60;
            }

            $delayMinutos = (int) ($limiteHoras * 60);

            if ($delayMinutos > 0) {
                // CALCULO DE DELAY EN TIEMPO REAL:
                // Si el límite son 2 horas laborales y son las 5 PM (cierre 6 PM), 
                // el Job debe ejecutarse mañana a las 7 AM.
                $businessTime = app(\App\Services\BusinessTimeService::class);
                $fechaAlerta = $businessTime->addBusinessSeconds(now(), $delayMinutos * 60);
                
                CheckStagnationJob::dispatch($cuenta->id, $cuenta->estado_actual_id)
                    ->delay($fechaAlerta);

                Log::debug("[Observer] CheckStagnationJob despachado con TIEMPO LABORAL. " .
                    "CuentaId={$cuenta->id}, Estado={$cuenta->estado_actual_id}, " .
                    "Alerta programada para: {$fechaAlerta}");
            }
        } catch (\Exception $e) {
            Log::error("[Observer] Error despachando CheckStagnationJob: " . $e->getMessage());
        }
    }
}

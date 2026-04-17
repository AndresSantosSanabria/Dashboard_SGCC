<?php

namespace App\Jobs;

use App\Models\CuentaCobro;
use App\Services\StagnationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckStagnationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cuentaId;
    protected $estadoIdOriginal;

    /**
     * Create a new job instance.
     * 
     * @param int $cuentaId ID de la cuenta a revisar
     * @param int $estadoIdOriginal ID del estado en el que estaba al despachar
     */
    public function __construct($cuentaId, $estadoIdOriginal)
    {
        $this->cuentaId = $cuentaId;
        $this->estadoIdOriginal = $estadoIdOriginal;
    }

    /**
     * Execute the job.
     */
    public function handle(StagnationService $stagnationService): void
    {
        $cuenta = CuentaCobro::find($this->cuentaId);

        // VALIDACIÓN DE ESTADO (Requerimiento Técnico)
        if (!$cuenta || $cuenta->finalizada) {
            return;
        }

        // Si la cuenta cambió de estado, este Job ya no es válido para este intervalo
        if ($cuenta->estado_actual_id != $this->estadoIdOriginal) {
            return;
        }

        // Ejecutar la verificación proactiva "Push"
        $stagnationService->runCheckOnAccount($this->cuentaId);
    }
}

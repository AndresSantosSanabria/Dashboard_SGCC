<?php

namespace App\Console\Commands;

use App\Services\TimeTrackingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseWorkdayCommand extends Command
{
    /**
     * El nombre y firma del comando.
     */
    protected $signature = 'sgcc:workday-close';

    /**
     * Descripción del comando.
     */
    protected $description = 'Cierra forzosamente los cronómetros activos al finalizar la jornada laboral para asegurar la persistencia.';

    /**
     * Ejecuta el comando.
     */
    public function handle(TimeTrackingService $timeService)
    {
        $this->info('Iniciando cierre forzado de jornada laboral...');
        
        $count = $timeService->forceWorkflowClosing();
        
        if ($count > 0) {
            $this->success("Se cerraron y persistieron {$count} tareas activas.");
            Log::info("sgcc:workday-close - Cierre automatizado ejecutado. Tareas afectadas: {$count}");
        } else {
            $this->info("No hay tareas activas para cerrar en este momento.");
        }
        
        return 0;
    }
}

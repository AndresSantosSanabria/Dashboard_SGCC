<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\CuentaCobro;

class DebugTimeline extends Command
{
    protected $signature = 'debug:timeline {cuentaId?}';
    protected $description = 'Debug timeline calculation for a specific cuenta';

    public function handle()
    {
        // Si no dan cuenta, tomar una random que tenga historial
        $cuentaId = $this->argument('cuentaId') ?? DB::table('historial_workflow')
            ->distinct('cuenta_cobro_id')
            ->value('cuenta_cobro_id');

        if (!$cuentaId) {
            $this->error('No hay cuentas con historial');
            return;
        }

        $this->info("=== DEBUG TIMELINE CUENTA #$cuentaId ===\n");

        $cuenta = CuentaCobro::find($cuentaId);
        if (!$cuenta) {
            $this->error("Cuenta no encontrada");
            return;
        }

        // Ver el historial crudo en BD
        $historial = DB::table('historial_workflow')
            ->where('cuenta_cobro_id', $cuentaId)
            ->orderBy('fecha_transicion', 'asc')
            ->get(['id', 'estado_origen_id', 'estado_destino_id', 'tiempo_en_estado_anterior_segundos', 'fecha_transicion']);

        $this->info("Historial en BD:");
        $this->table(
            ['ID', 'Origen', 'Destino', 'Segundos', 'Días', 'Fecha'],
            $historial->map(function ($h) {
                return [
                    $h->id,
                    $h->estado_origen_id,
                    $h->estado_destino_id,
                    $h->tiempo_en_estado_anterior_segundos,
                    round($h->tiempo_en_estado_anterior_segundos / 86400, 2),
                    $h->fecha_transicion
                ];
            })->toArray()
        );

        // Ver el timeline_completa calculado
        $timeline = $cuenta->timeline_completa;
        $this->info("\nTimeline calculado (timeline_completa):");
        $timelineData = $timeline->map(function ($evento) {
            return [
                data_get($evento, 'estado_destino.nombre'),
                data_get($evento, 'tiempo_segundos'),
                round(data_get($evento, 'tiempo_segundos', 0) / 86400, 2),
                data_get($evento, 'tiempo_formateado'),
                data_get($evento, 'fecha')
            ];
        })->toArray();

        $this->table(
            ['Estado Destino', 'Seg', 'Días', 'Formateado', 'Fecha'],
            $timelineData
        );

        // Ver el total
        $this->info("\n=== TOTAL ===");
        $this->line("Tiempo Total Ejecución: " . $cuenta->tiempo_total_ejecucion);

        // Verificar suma manual
        $totalSegundos = $timeline->sum(function ($evento) {
            return (int) data_get($evento, 'tiempo_segundos', 0);
        });
        $this->line("Suma manual: $totalSegundos segundos = " . round($totalSegundos / 86400, 2) . " días");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTimeValues extends Command
{
    protected $signature = 'check:time-values';
    protected $description = 'Check tiempo_en_estado_anterior_segundos values in database';

    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE VALORES EN BD ===');
        
        $records = DB::table('historial_workflow')
            ->whereNotNull('tiempo_en_estado_anterior_segundos')
            ->where('tiempo_en_estado_anterior_segundos', '>', 0)
            ->orderBy('tiempo_en_estado_anterior_segundos', 'desc')
            ->limit(15)
            ->get(['id', 'cuenta_cobro_id', 'estado_origen_id', 'estado_destino_id', 'tiempo_en_estado_anterior_segundos', 'fecha_transicion']);

        $this->table(
            ['ID', 'Cuenta', 'Origen', 'Destino', 'Segundos', 'Días (aprox)', 'Fecha'],
            $records->map(function ($r) {
                return [
                    $r->id,
                    $r->cuenta_cobro_id,
                    $r->estado_origen_id,
                    $r->estado_destino_id,
                    $r->tiempo_en_estado_anterior_segundos,
                    round($r->tiempo_en_estado_anterior_segundos / 86400, 2),
                    $r->fecha_transicion
                ];
            })->toArray()
        );

        // Detectar valores sospechosos
        $this->info("\n=== ANÁLISIS DE SOSPECHOSOS ===");
        $sospechosos = $records->filter(function ($r) {
            // Si es > 100 días, es sospechoso
            return ($r->tiempo_en_estado_anterior_segundos / 86400) > 100;
        });

        if ($sospechosos->count() > 0) {
            $this->warn("⚠️ Detectados " . $sospechosos->count() . " valores > 100 días:");
            foreach ($sospechosos as $s) {
                $dias = $s->tiempo_en_estado_anterior_segundos / 86400;
                $this->warn("   ID $s->id: $s->tiempo_en_estado_anterior_segundos seg = $dias días");
            }
        } else {
            $this->info("✓ No se detectan valores > 100 días");
        }

        // Total de registros con tiempo
        $totalCount = DB::table('historial_workflow')
            ->whereNotNull('tiempo_en_estado_anterior_segundos')
            ->where('tiempo_en_estado_anterior_segundos', '>', 0)
            ->count();
        
        $this->info("\nTotal de registros con tiempo_en_estado_anterior_segundos > 0: $totalCount");
    }
}

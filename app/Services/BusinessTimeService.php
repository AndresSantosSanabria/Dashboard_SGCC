<?php

namespace App\Services;

use App\Models\Festivo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessTimeService
{
    protected static $festivosConfig = null;

    /**
     * Calcula los segundos laborables entre dos fechas.
     * Versión ULTRA-OPTIMIZADA: Usa comparaciones de strings para evitar Carbon::parse.
     */
    public function getWorkingSecondsBetween($startInput, $endInput)
    {
        // Forzar objetos Carbon solo para el inicio del cálculo
        $start = ($startInput instanceof Carbon) ? $startInput : Carbon::parse($startInput);
        $end   = ($endInput instanceof Carbon)   ? $endInput   : Carbon::parse($endInput);

        if ($start->gt($end)) return 0;

        // Cargar festivos pre-procesados una vez para toda la vida de la petición
        if (is_null(self::$festivosConfig)) {
            $raw = DB::table('festivos')->pluck('fecha')->toArray();
            self::$festivosConfig = [];
            foreach ($raw as $f) {
                // $f ya viene como string YYYY-MM-DD del DB
                $dateStr = substr($f, 0, 10);
                $c = Carbon::parse($dateStr);
                self::$festivosConfig[] = [
                    'date' => $dateStr,
                    'is_laborable' => ($c->dayOfWeek !== 0 && $c->dayOfWeek !== 6)
                ];
            }
        }

        $startStr = $start->format('Y-m-d');
        $endStr   = $end->format('Y-m-d');

        // Caso mismo día
        if ($startStr === $endStr) {
            if ($start->isWeekend()) return 0;
            foreach (self::$festivosConfig as $f) {
                if ($f['date'] === $startStr) return 0;
            }
            return (int) $start->diffInSeconds($end);
        }

        // 1. Días de calendario
        $periodStart = $start->copy()->startOfDay();
        $periodEnd   = $end->copy()->startOfDay();
        $daysDiff    = $periodStart->diffInDays($periodEnd);

        // 2. Fines de semana (Fórmula Matemática sin bucles)
        $weeks = floor($daysDiff / 7);
        $weekends = $weeks * 2;
        $remaining = $daysDiff % 7;
        if ($remaining > 0) {
            $startDay = (int) $start->format('w');
            for ($i = 1; $i <= $remaining; $i++) {
                $day = ($startDay + $i) % 7;
                if ($day == 0 || $day == 6) $weekends++;
            }
        }

        // 3. Festivos (Bucle sobre ~500 registros de texto, muy rápido)
        $holidaysCount = 0;
        foreach (self::$festivosConfig as $f) {
            if ($f['date'] >= $startStr && $f['date'] <= $endStr && $f['is_laborable']) {
                $holidaysCount++;
            }
        }

        $workingDays = max(0, $daysDiff - $weekends - $holidaysCount);
        $totalSeconds = $workingDays * 86400;

        // 4. Extremos (Inicio y Fin parciales)
        $isStartWork = ($start->dayOfWeek !== 0 && $start->dayOfWeek !== 6);
        $isEndWork   = ($end->dayOfWeek   !== 0 && $end->dayOfWeek   !== 6);
        foreach (self::$festivosConfig as $f) {
            if ($f['date'] === $startStr) $isStartWork = false;
            if ($f['date'] === $endStr)   $isEndWork   = false;
        }

        if ($isStartWork) $totalSeconds += $start->diffInSeconds($start->copy()->endOfDay());
        if ($isEndWork)   $totalSeconds += $end->copy()->startOfDay()->diffInSeconds($end);

        return (int) $totalSeconds;
    }

    public function formatInterval(int $totalSeconds): string
    {
        $days = floor($totalSeconds / 86400);
        $rem  = $totalSeconds % 86400;
        $hours   = floor($rem / 3600);
        $rem    %= 3600;
        $minutes = floor($rem / 60);

        $parts = [];
        if ($days > 0)    $parts[] = "$days "    . ($days == 1 ? 'día' : 'días');
        if ($hours > 0)   $parts[] = "$hours "   . ($hours == 1 ? 'hora' : 'horas');
        if ($minutes > 0) $parts[] = "$minutes " . ($minutes == 1 ? 'minuto' : 'minutos');
        if (empty($parts)) return ($totalSeconds % 60) . " segundos";

        return implode(', ', $parts);
    }
}

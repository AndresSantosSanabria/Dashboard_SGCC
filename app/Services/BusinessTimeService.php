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

    /**
     * Obtiene los festivos de Colombia para un año específico (Ley Emiliani).
     */
    public function getColombianHolidays(int $year): array
    {
        $holidays = [];

        // 1. Festivos Fijos
        $fixed = [
            "$year-01-01", // Año Nuevo
            "$year-05-01", // Día del Trabajo
            "$year-07-20", // Grito de Independencia
            "$year-08-07", // Batalla de Boyacá
            "$year-12-08", // Inmaculada Concepción
            "$year-12-25", // Navidad
        ];
        foreach ($fixed as $f) $holidays[] = $f;

        // 2. Festivos Ley Emiliani (Se mueven al siguiente lunes si no caen lunes)
        $emiliani = [
            "$year-01-06", // Reyes Magos
            "$year-03-19", // San José
            "$year-06-29", // San Pedro y San Pablo
            "$year-08-15", // Asunción de la Virgen
            "$year-10-12", // Día de la Raza
            "$year-11-01", // Todos los Santos
            "$year-11-11", // Independencia de Cartagena
        ];
        foreach ($emiliani as $f) {
            $holidays[] = $this->moveToNextMonday($f);
        }

        // 3. Festivos basados en Pascua (Domingo de Resurrección)
        $easter = $this->calculateEaster($year);

        // Jueves y Viernes Santo
        $holidays[] = $easter->copy()->subDays(3)->format('Y-m-d');
        $holidays[] = $easter->copy()->subDays(2)->format('Y-m-d');

        // Movibles Emiliani (Basados en Pascua + X días, movidos a Lunes)
        $holidays[] = $this->moveToNextMonday($easter->copy()->addDays(39)->format('Y-m-d')); // Ascensión
        $holidays[] = $this->moveToNextMonday($easter->copy()->addDays(60)->format('Y-m-d')); // Corpus Christi
        $holidays[] = $this->moveToNextMonday($easter->copy()->addDays(67)->format('Y-m-d')); // Sagrado Corazón

        sort($holidays);
        return array_unique($holidays);
    }

    private function moveToNextMonday(string $dateStr): string
    {
        $date = Carbon::parse($dateStr);
        if ($date->dayOfWeek !== Carbon::MONDAY) {
            return $date->next(Carbon::MONDAY)->format('Y-m-d');
        }
        return $dateStr;
    }

    /**
     * Calcula el Domingo de Pascua sin depender de la extensión php-calendar (easter_days).
     * Algoritmo de Butcher-Meeus.
     */
    private function calculateEaster(int $year): Carbon
    {
        if (function_exists('easter_days')) {
            return Carbon::create($year, 3, 21)->addDays(easter_days($year));
        }

        $a = $year % 19;
        $b = floor($year / 100);
        $c = $year % 100;
        $d = floor($b / 4);
        $e = $b % 4;
        $f = floor(($b + 8) / 25);
        $g = floor(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = floor($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = floor(($a + 11 * $h + 22 * $l) / 451);
        $mouth = floor(($h + $l - 7 * $m + 114) / 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create((int)$year, (int)$mouth, (int)$day);
    }
}

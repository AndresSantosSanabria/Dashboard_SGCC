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
     * Garantiza que el tiempo se detenga al final de la jornada laboral.
     */
    public function getWorkingSecondsBetween($startInput, $endInput)
    {
        $tz = config('app.timezone', 'America/Bogota');
        $start = Carbon::parse($startInput)->setTimezone($tz);
        $end = Carbon::parse($endInput)->setTimezone($tz);

        if ($start->gt($end))
            return 0;

        // Cargar configuración de horarios y normalizar para asegurar que Carbon lo entienda
        $rawStart = \App\Models\Configuracion::getValor('HORARIO_LABORAL_INICIO', '06:00');
        $rawEnd = \App\Models\Configuracion::getValor('HORARIO_LABORAL_FIN', '18:00');

        $workStartStr = Carbon::parse($rawStart)->format('H:i');
        $workEndStr = Carbon::parse($rawEnd)->format('H:i');

        // Cargar festivos una sola vez por ejecución
        if (is_null(self::$festivosConfig)) {
            self::$festivosConfig = DB::table('festivos')
                ->pluck('fecha')
                ->map(fn($f) => substr($f, 0, 10))
                ->toArray();
        }

        $totalSeconds = 0;
        $p = $start->copy()->startOfDay();
        $endDateStr = $end->format('Y-m-d');

        while ($p->format('Y-m-d') <= $endDateStr) {
            $dateStr = $p->format('Y-m-d');
            $isWeekend = ($p->dayOfWeek === 0 || $p->dayOfWeek === 6);
            $isHoliday = in_array($dateStr, self::$festivosConfig);

            if (!$isWeekend && !$isHoliday) {
                // Definir los límites laborales del día actual en la iteración
                $dayStart = $p->copy()->setTimeFromTimeString($workStartStr);
                $dayEnd = $p->copy()->setTimeFromTimeString($workEndStr);

                /**
                 * LÓGICA DE INTERSECCIÓN:
                 * overlapStart: Si el evento empezó después de abrir, usamos esa hora. Si no, la de apertura.
                 * overlapEnd: Si el evento terminó después de cerrar, usamos la de cierre (TRUNCADO).
                 */
                $overlapStart = $start->gt($dayStart) ? $start->copy() : $dayStart->copy();
                $overlapEnd = $end->lt($dayEnd) ? $end->copy() : $dayEnd->copy();

                // Solo sumar si el inicio es menor al fin dentro de la ventana laboral
                if ($overlapStart->lt($overlapEnd)) {
                    $totalSeconds += $overlapStart->diffInSeconds($overlapEnd);
                }
            }

            // Avanzar al siguiente día
            $p->addDay();
        }

        return (int) $totalSeconds;
    }

    /**
     * Formatea segundos en un string legible (ej: 1d 2h 30m).
     */
    public function formatInterval(int $totalSeconds): string
    {
        if ($totalSeconds <= 0)
            return '0s';

        // Hacer la duración de un "día" completamente dinámica según la configuración actual
        $rawStart = \App\Models\Configuracion::getValor('HORARIO_LABORAL_INICIO', '06:00');
        $rawEnd = \App\Models\Configuracion::getValor('HORARIO_LABORAL_FIN', '18:00');
        
        $t1 = Carbon::parse($rawStart);
        $t2 = Carbon::parse($rawEnd);
        
        $secondsInDay = max(3600, $t1->diffInSeconds($t2));

        $d = floor($totalSeconds / $secondsInDay);
        $rem = $totalSeconds % $secondsInDay;
        $h = floor($rem / 3600);
        $rem %= 3600;
        $m = floor($rem / 60);
        $s = $rem % 60;

        $parts = [];
        if ($d > 0)
            $parts[] = "{$d}d";
        if ($h > 0 || ($d > 0 && ($m > 0 || $s > 0)))
            $parts[] = "{$h}h";
        if ($m > 0 || (($d > 0 || $h > 0) && $s > 0))
            $parts[] = "{$m}m";
        if ($s > 0 || empty($parts))
            $parts[] = "{$s}s";

        return implode(' ', $parts);
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
        foreach ($fixed as $f)
            $holidays[] = $f;

        // 2. Festivos Ley Emiliani (Se mueven al siguiente lunes)
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

        // 3. Festivos basados en Pascua
        $easter = $this->calculateEaster($year);

        // Jueves y Viernes Santo
        $holidays[] = $easter->copy()->subDays(3)->format('Y-m-d');
        $holidays[] = $easter->copy()->subDays(2)->format('Y-m-d');

        // Movibles Emiliani (Pascua + X días)
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

        return Carbon::create((int) $year, (int) $mouth, (int) $day);
    }
}
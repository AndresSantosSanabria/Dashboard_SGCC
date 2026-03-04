<?php

namespace App\Services;

use App\Models\Festivo;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BusinessTimeService
{
    /**
     * Calcula los segundos laborables entre dos fechas (24 horas, excluyendo fines de semana y festivos).
     */
    public function getWorkingSecondsBetween(Carbon $start, Carbon $end)
    {
        if ($start->gt($end)) return 0;

        $totalSeconds = 0;
        $years = range($start->year, $end->year);
        $festivos = [];
        foreach ($years as $year) {
            $festivos = array_merge($festivos, $this->getColombianHolidays($year));
        }

        $period = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            
            // Ignorar sábados (6) y domingos (0)
            if ($date->isWeekend()) continue;

            // Ignorar festivos (BD + Automáticos)
            if (in_array($dateStr, $festivos)) continue;
            if (Festivo::where('fecha', $dateStr)->exists()) continue;

            $currentDayStart = $date->copy()->startOfDay();
            $currentDayEnd = $date->copy()->endOfDay();

            // Determinar el inicio efectivo para este día
            $effectiveStart = $start->isSameDay($date) ? $start : $currentDayStart;
            
            // Determinar el fin efectivo para este día
            $effectiveEnd = $end->isSameDay($date) ? $end : $currentDayEnd;

            if ($effectiveEnd->gt($effectiveStart)) {
                $totalSeconds += $effectiveStart->diffInSeconds($effectiveEnd);
            }
        }

        return $totalSeconds;
    }

    /**
     * Calcula los festivos de Colombia para un año específico (Ley Emiliani).
     */
    public function getColombianHolidays(int $year): array
    {
        $holidays = [
            $year . '-01-01', // Año Nuevo
            $year . '-05-01', // Día del Trabajo
            $year . '-07-20', // Independencia
            $year . '-08-07', // Batalla de Boyacá
            $year . '-12-08', // Inmaculada Concepción
            $year . '-12-25', // Navidad
        ];

        // Festivos que se mueven al siguiente lunes (Ley Emiliani)
        $emiliani = [
            $year . '-01-06', // Reyes Magos
            $year . '-03-19', // San José
            $year . '-06-29', // San Pedro y San Pablo
            $year . '-08-15', // Asunción de la Virgen
            $year . '-10-12', // Día de la Raza
            $year . '-11-01', // Todos los Santos
            $year . '-11-11', // Independencia de Cartagena
        ];

        foreach ($emiliani as $date) {
            $cDate = Carbon::parse($date);
            if ($cDate->dayOfWeek !== Carbon::MONDAY) {
                $cDate->next(Carbon::MONDAY);
            }
            $holidays[] = $cDate->format('Y-m-d');
        }

        // Basados en Pascua
        $easter = Carbon::parse(date("Y-m-d", easter_date($year)));
        
        $holidays[] = $easter->copy()->subDays(3)->format('Y-m-d'); // Jueves Santo
        $holidays[] = $easter->copy()->subDays(2)->format('Y-m-d'); // Viernes Santo
        
        // Fiestas móviles basadas en Pascua que se mueven al lunes
        $movable = [
            $easter->copy()->addDays(39), // Ascensión del Señor (40 días después)
            $easter->copy()->addDays(60), // Corpus Christi (60 días después)
            $easter->copy()->addDays(68), // Sagrado Corazón (68 días después)
        ];

        foreach ($movable as $mDate) {
            if ($mDate->dayOfWeek !== Carbon::MONDAY) {
                $mDate->next(Carbon::MONDAY);
            }
            $holidays[] = $mDate->format('Y-m-d');
        }

        return array_unique($holidays);
    }

    /**
     * Formatea una cantidad de segundos en una cadena legible basada en 24h.
     */
    public function formatInterval(int $totalSeconds): string
    {
        $secondsPerDay = 86400; // 24 horas

        $days = floor($totalSeconds / $secondsPerDay);
        $remainingSeconds = $totalSeconds % $secondsPerDay;

        $hours = floor($remainingSeconds / 3600);
        $remainingSeconds %= 3600;

        $minutes = floor($remainingSeconds / 60);
        $seconds = $remainingSeconds % 60;

        $parts = [];
        if ($days > 0) $parts[] = $days . ($days == 1 ? ' día' : ' días');
        if ($hours > 0) $parts[] = $hours . ($hours == 1 ? ' hora' : ' horas');
        if ($minutes > 0) $parts[] = $minutes . ($minutes == 1 ? ' minuto' : ' minutos');
        if ($seconds > 0 || empty($parts)) $parts[] = $seconds . ($seconds == 1 ? ' segundo' : ' segundos');

        return implode(', ', $parts);
    }
}

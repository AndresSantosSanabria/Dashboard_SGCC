<?php

namespace App\Traits;

use App\Services\BusinessTimeService;
use Carbon\Carbon;

trait HasBusinessDays
{
    /**
     * Obtiene la instancia del servicio de tiempo laboral.
     */
    protected function getBusinessTimeService(): BusinessTimeService
    {
        return app(BusinessTimeService::class);
    }

    /**
     * Calcula los días hábiles entre dos fechas.
     *
     * @param mixed $startDate
     * @param mixed $endDate
     * @return int
     */
    public function getNetWorkDays($startDate, $endDate): int
    {
        return $this->getBusinessTimeService()->getNetWorkDays($startDate, $endDate);
    }

    /**
     * Calcula los segundos laborales entre dos fechas/horas.
     *
     * @param mixed $start
     * @param mixed $end
     * @return int
     */
    public function getWorkingSeconds($start, $end): int
    {
        return $this->getBusinessTimeService()->getWorkingSecondsBetween($start, $end);
    }

    /**
     * Formatea un intervalo de segundos en texto legible.
     *
     * @param int $seconds
     * @return string
     */
    public function formatBusinessInterval(int $seconds): string
    {
        return $this->getBusinessTimeService()->formatInterval($seconds);
    }

    /**
     * Suma días hábiles a una fecha.
     *
     * @param mixed $date
     * @param int $days
     * @return Carbon
     */
    public function addBusinessDays($date, int $days): Carbon
    {
        return $this->getBusinessTimeService()->addBusinessDays($date, $days);
    }

    /**
     * Suma segundos hábiles a una fecha (considerando horario laboral).
     *
     * @param mixed $date
     * @param int $seconds
     * @return Carbon
     */
    public function addBusinessSeconds($date, int $seconds): Carbon
    {
        return $this->getBusinessTimeService()->addBusinessSeconds($date, $seconds);
    }
}

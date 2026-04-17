<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * PROGRAMACIÓN DE TAREAS - SGCC
 *
 * Sistema híbrido de monitoreo:
 *  - PUSH: CheckStagnationJob se despacha desde el Observer al cambiar estado.
 *         Delay = tiempo_limite_horas del estado (o config global como fallback).
 *  - PULL: sgcc:workday-close cierra jornada y congela fecha_ultimo_cambio_estado.
 *  - SWEEP: Revisión masiva diaria como red de seguridad.
 */

// 1. CIERRE FORZADO DE JORNADA
// Pausa el contador VOLÁTIL (fecha_ultimo_cambio_estado) sin resetearlo.
// El tiempo entre sesiones se excluye por BusinessTimeService (no horas laborales).
// Se ejecuta cada 15 min después del fin de jornada configurado.
Schedule::command('sgcc:workday-close')
    ->everyFifteenMinutes()
    ->when(function () {
        $rawEnd = \App\Models\Configuracion::getValor('HORARIO_LABORAL_FIN', '18:00');
        return now()->format('H:i') >= $rawEnd;
    });

// 2. SWEEP DE SEGURIDAD: Revisión masiva una vez al día (07:30 AM)
// Captura contratos que pudieron quedar sin Job push (reinicios del servidor, etc.)
Schedule::call(function () {
    $activa = \App\Models\Configuracion::getValor('ALERTA_ESTANCAMIENTO_ACTIVA', '0');
    if (in_array($activa, ['1', 'true'], true)) {
        app(\App\Services\StagnationService::class)->checkAll();
        \Illuminate\Support\Facades\Log::info('[Schedule] Sweep masivo de estancamiento ejecutado.');
    }
})->dailyAt('07:30')->name('sgcc:stagnation-sweep')->withoutOverlapping();

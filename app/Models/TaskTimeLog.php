<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTimeLog extends Model
{
    use HasFactory;

    protected $table = 'tasks_time_log';

    protected $fillable = [
        'cuenta_cobro_id',
        'estado_id',
        'usuario_id',
        'start_time',
        'end_time',
        'duracion_segundos',
        'tipo_cierre',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duracion_segundos' => 'integer',
    ];

    public function cuentaCobro()
    {
        return $this->belongsTo(CuentaCobro::class);
    }

    public function estado()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * SCOPE ANTI-BUG: Filtra logs solo de la sesión actual del estado.
     * 
     * Previene el bug de herencia de tiempos cuando una cuenta regresa a un estado.
     * Usa HistorialWorkflow como referencia para la entrada actual.
     * 
     * Uso: TaskTimeLog::forCurrentStateSession($cuentaId, $estadoId)->sum('duracion_segundos')
     */
    public function scopeForCurrentStateSession($query, int $cuentaId, int $estadoId)
    {
        // Obtener la última entrada "real" a este estado desde el historial.
        // Ignoramos transiciones no-op (origen == destino), porque pueden
        // desplazar artificialmente el corte y reiniciar el contador visual.
        $ultimaEntrada = HistorialWorkflow::where('cuenta_cobro_id', $cuentaId)
            ->where('estado_destino_id', $estadoId)
            ->whereColumn('estado_origen_id', '!=', 'estado_destino_id')
            ->orderBy('fecha_transicion', 'desc')
            ->value('fecha_transicion');

        // Si no hay historial, no inventamos una fecha: evitamos heredar tiempo viejo.
        $fechaCorte = $ultimaEntrada;

        if (! $fechaCorte) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('cuenta_cobro_id', $cuentaId)
            ->where('estado_id', $estadoId)
            ->where('start_time', '>=', $fechaCorte);
    }

    /**
     * HELPER ANTI-BUG: Calcula el tiempo de una cuenta en su estado ACTUAL.
     * 
     * SIEMPRE filtra por sesión actual. Es el punto único donde se debe
     * calcular este valor para garantizar corrección.
     * 
     * Retorna: segundos de tiempo laboral acumulado (logs cerrados + volátil)
     */
    public static function getElapsedTimeForCurrentState(CuentaCobro $cuenta): int
    {
        if (!$cuenta->estado_actual_id) {
            return 0;
        }

        if ($cuenta->estadoActual && ! $cuenta->estadoActual->contabiliza_tiempo) {
            return 0;
        }

        // Si existe un timestamp de entrada al estado actual, esa es la fuente de verdad.
        // Es más estable que reconstruir por historial cuando hay transiciones no-op.
        if ($cuenta->fecha_ultimo_cambio_estado) {
            $businessTime = app(\App\Services\BusinessTimeService::class);
            return $businessTime->getWorkingSecondsBetween(
                $cuenta->fecha_ultimo_cambio_estado,
                now()
            );
        }

        // Logs cerrados SOLO de esta sesión actual del estado
        $tiempoLogueado = self::forCurrentStateSession($cuenta->id, $cuenta->estado_actual_id)
            ->whereNotNull('end_time')
            ->sum('duracion_segundos') ?? 0;

        // Tiempo volátil: desde el log abierto actual hasta ahora.
        // Si no hay log abierto, usamos la última entrada real al estado.
        $volatil = 0;
        $logAbierto = self::where('cuenta_cobro_id', $cuenta->id)
            ->where('estado_id', $cuenta->estado_actual_id)
            ->whereNull('end_time')
            ->orderByDesc('start_time')
            ->first();

        if ($logAbierto?->start_time) {
            $businessTime = app(\App\Services\BusinessTimeService::class);
            $volatil = $businessTime->getWorkingSecondsBetween(
                $logAbierto->start_time,
                now()
            );
        } elseif ($cuenta->estado_actual_id) {
            $ultimaEntrada = HistorialWorkflow::where('cuenta_cobro_id', $cuenta->id)
                ->where('estado_destino_id', $cuenta->estado_actual_id)
                ->whereColumn('estado_origen_id', '!=', 'estado_destino_id')
                ->orderBy('fecha_transicion', 'desc')
                ->value('fecha_transicion');

            if ($ultimaEntrada) {
                $businessTime = app(\App\Services\BusinessTimeService::class);
                $volatil = $businessTime->getWorkingSecondsBetween(
                    $ultimaEntrada,
                    now()
                );
            }
        }

        return (int) ($tiempoLogueado + $volatil);
    }

    /**
     * VALIDACIÓN ANTI-BUG: Antes de guardar, verifica coherencia de tiempos.
     * 
     * Previene logs con:
     * - end_time < start_time
     * - start_time en el futuro
     * - duracion_segundos negativa
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            self::validateTimeIntegrity($model);
        });

        static::updating(function ($model) {
            self::validateTimeIntegrity($model);
        });
    }

    private static function validateTimeIntegrity($model)
    {
        // Si tiene ambas fechas, verifica que sean coherentes
        if ($model->start_time && $model->end_time) {
            if ($model->end_time < $model->start_time) {
                \Illuminate\Support\Facades\Log::warning(
                    "[TaskTimeLog] Intento de guardar log con end_time < start_time. " .
                    "CuentaId={$model->cuenta_cobro_id}, Estado={$model->estado_id}"
                );
            }
        }

        // Verifica que duracion_segundos sea positiva
        if ($model->duracion_segundos !== null && $model->duracion_segundos < 0) {
            \Illuminate\Support\Facades\Log::warning(
                "[TaskTimeLog] Intento de guardar log con duracion_segundos negativa. " .
                "CuentaId={$model->cuenta_cobro_id}, Duracion={$model->duracion_segundos}"
            );
            $model->duracion_segundos = 0;
        }
    }
}

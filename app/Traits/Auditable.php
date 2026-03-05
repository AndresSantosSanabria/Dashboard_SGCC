<?php

namespace App\Traits;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * MOTOR DE AUDITORÍA AUTOMÁTICA (Interceptor de Eventos)
     * 
     * Este Trait permite que cualquier modelo de la aplicación registre 
     * automáticamente sus cambios en la tabla 'auditorias'. 
     * Utilizamos los "Hooks" de Eloquent para capturar el estado antes 
     * y después de cada operación.
     */
    protected static function bootAuditable()
    {
        // 1. REGISTRO DE CREACIÓN: Capturamos todo el objeto inicial.
        static::created(function ($model) {
            self::logAudit($model, 'INSERT', null, $model->getAttributes());
        });

        // 2. REGISTRO DE EDICIÓN: El punto más crítico para la trazabilidad.
        static::updated(function ($model) {
            // Comparamos el estado original contra los cambios actuales. 
            // array_intersect_key nos garantiza que solo guardamos lo que varió.
            $oldValues = array_intersect_key($model->getOriginal(), $model->getChanges());
            $newValues = $model->getChanges();

            // Optimización: Si el cambio es solo administrativo (timestamp), no ensuciamos el log.
            if (count($newValues) === 1 && isset($newValues['updated_at'])) return;

            self::logAudit($model, 'UPDATE', $oldValues, $newValues);
        });

        // 3. REGISTRO DE ELIMINACIÓN: Guardamos una "caja negra" del último estado conocido.
        static::deleted(function ($model) {
            self::logAudit($model, 'DELETE', $model->getAttributes(), null);
        });
    }


    /**
     * Permite registrar eventos de auditoría manualmente (ej: Lecturas de Vista)
     */
    public static function logManualAudit($model, $accion, $mensaje = null, $tablaManual = null, $datosIntento = null)
    {
        $tabla = $tablaManual ?? ($model ? $model->getTable() : 'SISTEMA');
        $nuevo = is_array($mensaje) ? $mensaje : ['detalle' => $mensaje];

        if ($datosIntento) {
            $nuevo['intentado'] = $datosIntento;
        }

        self::logAudit($model, $accion, $model ? $model->getOriginal() : null, $nuevo, $tabla);
    }

    /**
     * Registra un fallo de base de datos o excepción de forma completa
     */
    public static function logException(\Throwable $e, $tabla = 'SISTEMA', $datosExtra = [])
    {
        try {
            self::logAudit(null, 'FAILURE_DATABASE', null, [
                'error' => $e->getMessage(),
                'clase' => get_class($e),
                'ubicacion' => $e->getFile() . ':' . $e->getLine(),
                'contexto' => $datosExtra,
                'trace' => substr($e->getTraceAsString(), 0, 800),
            ], $tabla);
        } catch (\Exception $ex) {
            Log::error('Fallo crítico al intentar auditar un error: ' . $ex->getMessage());
        }
    }

    protected static function logAudit($model, $accion, $anterior, $nuevo, $tablaManual = null)
    {
        try {
            // Evitar loggear la propia tabla de auditoría
            if ($model && $model->getTable() === 'auditorias') {
                return;
            }
            if ($tablaManual === 'auditorias') {
                return;
            }

            Auditoria::create([
                'usuario_id' => Auth::id(),
                'tabla_afectada' => $tablaManual ?? ($model ? $model->getTable() : 'SISTEMA'),
                'registro_id' => $model ? $model->id : 0,
                'accion' => $accion,
                'payload_anterior' => $anterior,
                'payload_nuevo' => $nuevo,
                'ip_origen' => Request::ip() ?? '127.0.0.1',
                'user_agent' => substr(Request::userAgent() ?? 'none', 0, 200),
            ]);
        } catch (\Exception $e) {
            Log::error('Error guardando auditoría: ' . $e->getMessage());
        }
    }
}

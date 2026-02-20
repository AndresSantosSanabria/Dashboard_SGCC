<?php

namespace App\Traits;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            self::logAudit($model, 'INSERT', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getChanges());
            $newValues = $model->getChanges();
            
            // Ignorar si solo cambió updated_at
            if (count($newValues) === 1 && isset($newValues['updated_at'])) {
                return;
            }

            self::logAudit($model, 'UPDATE', $oldValues, $newValues);
        });

        static::deleted(function ($model) {
            self::logAudit($model, 'DELETE', $model->getAttributes(), null);
        });
    }

    protected static function logAudit($model, $accion, $anterior, $nuevo)
    {
        try {
            Auditoria::create([
                'usuario_id' => Auth::id(),
                'tabla_afectada' => $model->getTable(),
                'registro_id' => $model->id,
                'accion' => $accion,
                'payload_anterior' => $anterior,
                'payload_nuevo' => $nuevo,
                'ip_origen' => Request::ip(),
                'user_agent' => substr(Request::userAgent(), 0, 200),
            ]);
        } catch (\Exception $e) {
            // No queremos que un error en auditoría rompa la ejecución principal
            \Log::error("Error guardando auditoría: " . $e->getMessage());
        }
    }
}

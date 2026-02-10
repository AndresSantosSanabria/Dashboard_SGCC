<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialWorkflow extends Model
{
    use HasFactory;

    protected $table = 'historial_workflow';

    public $timestamps = false;

    protected $fillable = [
        'cuenta_cobro_id',
        'bloque_id',
        'estado_origen_id',
        'estado_destino_id',
        'usuario_accion_id',
        'fecha_transicion',
        'tiempo_en_estado_anterior_minutos',
        'accion',
        'comentarios',
        'documentos_adjuntos',
        'metadata',
    ];

    protected $casts = [
        'fecha_transicion' => 'datetime',
        'tiempo_en_estado_anterior_minutos' => 'integer',
        'documentos_adjuntos' => 'array',
        'metadata' => 'array',
    ];

    // Relaciones
    public function cuentaCobro()
    {
        return $this->belongsTo(CuentaCobro::class, 'cuenta_cobro_id');
    }

    public function bloque()
    {
        return $this->belongsTo(BloqueWorkflow::class, 'bloque_id');
    }

    public function estadoOrigen()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_origen_id');
    }

    public function estadoDestino()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_destino_id');
    }

    public function usuarioAccion()
    {
        return $this->belongsTo(Usuario::class, 'usuario_accion_id');
    }

    // Scopes
    public function scopePorCuenta($query, $cuentaCobroId)
    {
        return $query->where('cuenta_cobro_id', $cuentaCobroId)
            ->orderBy('fecha_transicion', 'desc');
    }

    public function scopePorBloque($query, $bloqueId)
    {
        return $query->where('bloque_id', $bloqueId);
    }

    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_transicion', [$fechaInicio, $fechaFin]);
    }

    // Accessor para tiempo formateado
    public function getTiempoFormateadoAttribute()
    {
        if (!$this->tiempo_en_estado_anterior_minutos) {
            return null;
        }

        $minutos = $this->tiempo_en_estado_anterior_minutos;
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        $dias = floor($horas / 24);
        $hrs = $horas % 24;

        if ($dias > 0) {
            return "{$dias}d {$hrs}h {$mins}m";
        } elseif ($horas > 0) {
            return "{$horas}h {$mins}m";
        } else {
            return "{$minutos}m";
        }
    }
}

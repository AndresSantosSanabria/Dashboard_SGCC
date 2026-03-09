<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoBloqueCuenta extends Model
{
    use HasFactory;

    protected $table = 'estado_bloque_cuenta';

    protected $fillable = [
        'cuenta_cobro_id',
        'bloque_id',
        'estado_actual_id',
        'responsable_id',
        'fecha_ingreso_bloque',
        'fecha_completado_bloque',
        'fecha_ultima_actualizacion',
        'numero_devoluciones',
        'bloque_completado',
        'observaciones',
        'metadata',
    ];

    protected $casts = [
        'fecha_ingreso_bloque' => 'datetime',
        'fecha_completado_bloque' => 'datetime',
        'fecha_ultima_actualizacion' => 'datetime',
        'numero_devoluciones' => 'integer',
        'bloque_completado' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function estadoActual()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_actual_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    // Scopes
    public function scopeCompletados($query)
    {
        return $query->where('bloque_completado', true);
    }

    public function scopeEnProceso($query)
    {
        return $query->where('bloque_completado', false);
    }

    public function scopePorBloque($query, $bloqueId)
    {
        return $query->where('bloque_id', $bloqueId);
    }

    // Accessor para tiempo en bloque (en horas)
    public function getTiempoEnBloqueHorasAttribute()
    {
        // Protegemos contra fecha_ingreso_bloque nula (registros incompletos)
        if (! $this->fecha_ingreso_bloque) {
            return 0;
        }

        $fechaFin = $this->fecha_completado_bloque ?? now();

        return $this->fecha_ingreso_bloque->diffInHours($fechaFin);
    }

    // Accessor para verificar si cumple SLA
    public function getCumpleSlaAttribute()
    {
        $slaHoras = $this->bloque->sla_horas;
        if (! $slaHoras) {
            return null;
        }

        return $this->tiempo_en_bloque_horas <= $slaHoras;
    }
}

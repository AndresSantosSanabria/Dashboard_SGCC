<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricaDiaria extends Model
{
    use HasFactory;

    protected $table = 'metricas_diarias';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = ['fecha', 'bloque_id'];

    protected $fillable = [
        'fecha',
        'bloque_id',
        'cantidad_procesada',
        'cantidad_aprobada',
        'cantidad_devuelta',
        'promedio_tiempo_horas',
        'cumplimiento_sla_pct',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad_procesada' => 'integer',
        'cantidad_aprobada' => 'integer',
        'cantidad_devuelta' => 'integer',
        'promedio_tiempo_horas' => 'decimal:2',
        'cumplimiento_sla_pct' => 'integer',
    ];

    // Relaciones
    public function bloque()
    {
        return $this->belongsTo(BloqueWorkflow::class, 'bloque_id');
    }

    // Scopes
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    public function scopePorBloque($query, $bloqueId)
    {
        return $query->where('bloque_id', $bloqueId);
    }

    public function scopeUltimoMes($query)
    {
        return $query->whereBetween('fecha', [now()->subMonth(), now()]);
    }

    // Accessor para porcentaje de aprobación
    public function getPorcentajeAprobacionAttribute()
    {
        if ($this->cantidad_procesada == 0) {
            return 0;
        }

        return round(($this->cantidad_aprobada / $this->cantidad_procesada) * 100, 2);
    }

    // Accessor para porcentaje de devolución
    public function getPorcentajeDevolucionAttribute()
    {
        if ($this->cantidad_procesada == 0) {
            return 0;
        }

        return round(($this->cantidad_devuelta / $this->cantidad_procesada) * 100, 2);
    }
}

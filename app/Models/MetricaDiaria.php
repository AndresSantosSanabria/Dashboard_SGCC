<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricaDiaria extends Model
{
    use HasFactory;

    protected $table = 'metricas_diarias';

    public $timestamps = false;
    
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'fecha',
        'bloque_id',
        'cantidad_procesada',
        'promedio_tiempo_horas',
        'cumplimiento_sla_pct'
    ];

    protected $casts = [
        'fecha' => 'date',
        'promedio_tiempo_horas' => 'decimal:2'
    ];

    public function bloque()
    {
        return $this->belongsTo(BloqueWorkflow::class, 'bloque_id');
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->where('fecha', $fecha);
    }

    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }
}

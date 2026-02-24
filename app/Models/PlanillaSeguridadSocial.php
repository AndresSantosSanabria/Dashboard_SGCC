<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class PlanillaSeguridadSocial extends Model
{
    use HasFactory, Auditable;

    protected $table = 'planillas_seguridad_social';

    protected $fillable = [
        'cuenta_cobro_id',
        'mes_planilla',
        'anio_planilla',
        'numero_planilla',
        'valor_total',
        'fecha_pago',
        'es_ultima',
    ];

    protected $casts = [
        'anio_planilla' => 'integer',
        'valor_total' => 'decimal:2',
        'fecha_pago' => 'date',
        'es_ultima' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function cuentaCobro()
    {
        return $this->belongsTo(CuentaCobro::class, 'cuenta_cobro_id');
    }

    // Accessor para periodo completo
    public function getPeriodoCompletoAttribute()
    {
        return $this->mes_planilla . ' ' . $this->anio_planilla;
    }

    // Scopes
    public function scopeUltimas($query)
    {
        return $query->where('es_ultima', true);
    }

    public function scopePorPeriodo($query, $mes, $anio)
    {
        return $query->where('mes_planilla', $mes)
            ->where('anio_planilla', $anio);
    }
}

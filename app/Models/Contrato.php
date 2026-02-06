<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    use HasFactory;

    protected $table = 'contratos';

    protected $fillable = [
        'numero_proceso',
        'numero_contrato',
        'modalidad_id',
        'contratista_id',
        'supervisor_id',
        'objeto',
        'monto_total',
        'planta_id',
        'concepto_id',
        'cdp_codigo',
        'fecha_inicio',
        'fecha_fin',
        'rp',
        'fecha_rp',
        'valor_rp',
        'es_activo'
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'valor_rp' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_rp' => 'date',
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }

    public function contratista()
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }

    public function planta()
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }

    public function concepto()
    {
        return $this->belongsTo(Concepto::class, 'concepto_id');
    }

    public function cuentasCobro()
    {
        return $this->hasMany(CuentaCobro::class, 'contrato_id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'contrato_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('es_activo', true);
    }

    public function scopeVigentes($query)
    {
        return $query->whereDate('fecha_inicio', '<=', now())
                    ->whereDate('fecha_fin', '>=', now());
    }
}

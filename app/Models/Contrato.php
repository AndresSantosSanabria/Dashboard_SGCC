<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SeguimientoMensual;
use App\Models\SeguimientoRequisito;

use App\Traits\Auditable;

class Contrato extends Model
{
    use HasFactory, Auditable;

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
        'es_activo',
        'link_secop',
        'plazo_ejecucion',
        'secop_estado_contrato',
        'aprobado_y_pagado',
        'modificaciones_y_cierre',

        'saldo',
        'observacion_1_razon',
        'observacion_2_accion',
        'razon_no_liquidacion',
        'abogado_responsable',
        'contador_responsable',
        'ops_juridico',

        // Checklist y otros
        'tipo_contratista',
        'no_planta',
        'concepto_precontractual',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
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

    public function registrosPresupuestales()
    {
        return $this->hasMany(RegistroPresupuestal::class, 'contrato_id');
    }

    public function cuentasCobro()
    {
        return $this->hasMany(CuentaCobro::class, 'contrato_id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'contrato_id');
    }

    public function seguimientoMensual()
    {
        return $this->hasMany(SeguimientoMensual::class, 'contrato_id');
    }

    public function seguimientoRequisitos()
    {
        return $this->hasMany(SeguimientoRequisito::class, 'contrato_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('es_activo', true);
    }

    public function scopeVigentes($query)
    {
        $hoy = now();
        return $query->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy);
    }
}

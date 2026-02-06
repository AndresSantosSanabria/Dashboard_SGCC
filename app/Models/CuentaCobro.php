<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaCobro extends Model
{
    use HasFactory;

    protected $table = 'cuentas_cobro';

    protected $fillable = [
        'contrato_id',
        'numero_cuenta',
        'valor_cobro',
        'fecha_radicacion',
        'numero_pagos_totales',
        'numero_facturas_radicadas',
        'porcentaje_cuentas',
        'mes_planilla_seguridad_social',
        'radicado_por',
        'finalizada',
        'responsable_actual_id',
        'observaciones',
        'diferencia_cuentas', 
        'ultima_factura_hacienda', 
    ];

    protected $casts = [
        'valor_cobro' => 'decimal:2',
        'porcentaje_cuentas' => 'decimal:2',
        'fecha_radicacion' => 'datetime',
        'finalizada' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function responsableActual()
    {
        return $this->belongsTo(Usuario::class, 'responsable_actual_id');
    }

    public function transiciones()
    {
        return $this->hasMany(TransicionEstado::class, 'cuenta_cobro_id');
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'cuenta_cobro_id');
    }

    public function scopePendientes($query)
    {
        return $query->where('finalizada', false);
    }

    public function scopeFinalizadas($query)
    {
        return $query->where('finalizada', true);
    }

    public function getEstadoActualAttribute()
    {
        return $this->transiciones()
            ->latest()
            ->first()
            ?->estadoDestino;
    }
}

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
        'radicado_por',
        'bloque_actual_id',
        'estado_actual_id',
        'finalizada',
        'responsable_actual_id',
        'observaciones',
        'ultima_factura_hacienda',
        'fecha_radicacion_hacienda',
        'observacion_hacienda',
    ];

    protected $casts = [
        'valor_cobro' => 'decimal:2',
        'fecha_radicacion' => 'datetime',
        'numero_pagos_totales' => 'integer',
        'numero_facturas_radicadas' => 'integer',
        'porcentaje_cuentas' => 'decimal:2',
        'finalizada' => 'boolean',
        'fecha_radicacion_hacienda' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function bloqueActual()
    {
        return $this->belongsTo(BloqueWorkflow::class, 'bloque_actual_id');
    }

    public function estadoActual()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_actual_id');
    }

    public function responsableActual()
    {
        return $this->belongsTo(Usuario::class, 'responsable_actual_id');
    }

    public function planillasSeguridadSocial()
    {
        return $this->hasMany(PlanillaSeguridadSocial::class, 'cuenta_cobro_id');
    }

    public function estadosBloques()
    {
        return $this->hasMany(EstadoBloqueCuenta::class, 'cuenta_cobro_id');
    }

    public function historialWorkflow()
    {
        return $this->hasMany(HistorialWorkflow::class, 'cuenta_cobro_id');
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'cuenta_cobro_id');
    }

    // Relación con el estado del bloque actual
    public function estadoBloqueActual()
    {
        return $this->hasOne(EstadoBloqueCuenta::class, 'cuenta_cobro_id')
            ->where('bloque_id', $this->bloque_actual_id);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('finalizada', false);
    }

    public function scopeFinalizadas($query)
    {
        return $query->where('finalizada', true);
    }

    public function scopeEnBloque($query, $bloqueId)
    {
        return $query->where('bloque_actual_id', $bloqueId);
    }

    public function scopeEnEstado($query, $estadoId)
    {
        return $query->where('estado_actual_id', $estadoId);
    }

    public function scopeAsignadasA($query, $usuarioId)
    {
        return $query->where('responsable_actual_id', $usuarioId);
    }

    // Accessors for Dashboard
    public function getDiferenciaCuentasAttribute()
    {
        return ($this->numero_pagos_totales ?? 0) - ($this->numero_facturas_radicadas ?? 0);
    }

    public function getUltimaFacturaHaciendaAttribute($value)
    {
        return $value ?? 'N/A';
    }

    public function getObservacionHaciendaAttribute($value)
    {
        return $value ?? 'N/A';
    }
}

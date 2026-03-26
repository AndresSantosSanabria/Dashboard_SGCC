<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoWorkflow extends Model
{
    use Auditable, HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'estados_workflow';

    protected $fillable = [
        'bloque_id',
        'nombre',
        'codigo',
        'tipo',
        'es_inicial',
        'es_final',
        'permite_devolucion',
        'contabiliza_tiempo',
        'afecta_indicadores',
        'color_hex',
        'descripcion',
        'es_activo',
    ];

    protected $casts = [
        'es_inicial' => 'boolean',
        'es_final' => 'boolean',
        'permite_devolucion' => 'boolean',
        'contabiliza_tiempo' => 'boolean',
        'afecta_indicadores' => 'boolean',
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function bloque()
    {
        return $this->belongsTo(BloqueWorkflow::class, 'bloque_id');
    }

    public function cuentasCobroActuales()
    {
        return $this->hasMany(CuentaCobro::class, 'estado_actual_id');
    }

    public function estadoBloqueCuentas()
    {
        return $this->hasMany(EstadoBloqueCuenta::class, 'estado_actual_id');
    }

    public function transicionesOrigen()
    {
        return $this->hasMany(TransicionPermitida::class, 'estado_origen_id');
    }

    public function transicionesDestino()
    {
        return $this->hasMany(TransicionPermitida::class, 'estado_destino_id');
    }

    public function historialComoOrigen()
    {
        return $this->hasMany(HistorialWorkflow::class, 'estado_origen_id');
    }

    public function historialComoDestino()
    {
        return $this->hasMany(HistorialWorkflow::class, 'estado_destino_id');
    }

    // Scopes
    public function scopeIniciales($query)
    {
        return $query->where('es_inicial', true);
    }

    public function scopeFinales($query)
    {
        return $query->where('es_final', true);
    }
}

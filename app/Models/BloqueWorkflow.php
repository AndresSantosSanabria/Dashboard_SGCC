<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloqueWorkflow extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'bloques_workflow';

    protected $fillable = [
        'nombre',
        'codigo',
        'orden',
        'sla_horas',
        'requiere_aprobacion',
        'roles_permitidos',
        'descripcion',
        'icono',
        'color_hex',
        'es_activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'sla_horas' => 'integer',
        'requiere_aprobacion' => 'boolean',
        'roles_permitidos' => 'array',
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function estados()
    {
        return $this->hasMany(EstadoWorkflow::class, 'bloque_id');
    }

    public function estadoInicial()
    {
        return $this->hasOne(EstadoWorkflow::class, 'bloque_id')
            ->where('es_inicial', true);
    }

    public function estadoFinal()
    {
        return $this->hasOne(EstadoWorkflow::class, 'bloque_id')
            ->where('es_final', true);
    }

    public function cuentasCobro()
    {
        return $this->hasMany(CuentaCobro::class, 'bloque_actual_id');
    }

    public function estadoBloqueCuentas()
    {
        return $this->hasMany(EstadoBloqueCuenta::class, 'bloque_id');
    }

    public function historialWorkflow()
    {
        return $this->hasMany(HistorialWorkflow::class, 'bloque_id');
    }

    public function metricasDiarias()
    {
        return $this->hasMany(MetricaDiaria::class, 'bloque_id');
    }

    // Scopes
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }
}

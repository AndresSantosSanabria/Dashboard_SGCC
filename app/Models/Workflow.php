<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloqueWorkflow extends Model
{
    use HasFactory;

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

class EstadoWorkflow extends Model
{
    use HasFactory;

    protected $table = 'estados_workflow';

    protected $fillable = [
        'bloque_id',
        'nombre',
        'codigo',
        'tipo',
        'es_inicial',
        'es_final',
        'permite_devolucion',
        'color_hex',
        'descripcion',
        'es_activo',
    ];

    protected $casts = [
        'es_inicial' => 'boolean',
        'es_final' => 'boolean',
        'permite_devolucion' => 'boolean',
        'es_activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

class TransicionPermitida extends Model
{
    use HasFactory;

    protected $table = 'transiciones_permitidas';

    protected $fillable = [
        'estado_origen_id',
        'estado_destino_id',
        'requiere_comentario',
        'requiere_documento',
        'accion',
        'descripcion',
        'es_activa',
    ];

    protected $casts = [
        'requiere_comentario' => 'boolean',
        'requiere_documento' => 'boolean',
        'es_activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function estadoOrigen()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_origen_id');
    }

    public function estadoDestino()
    {
        return $this->belongsTo(EstadoWorkflow::class, 'estado_destino_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('es_activa', true);
    }
}

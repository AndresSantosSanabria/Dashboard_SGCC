<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    protected $fillable = [
        'usuario_id',
        'tabla_afectada',
        'registro_id',
        'accion',
        'payload_anterior',
        'payload_nuevo',
        'ip_origen',
        'user_agent',
    ];

    protected $casts = [
        'registro_id' => 'integer',
        'payload_anterior' => 'array',
        'payload_nuevo' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // Scopes
    public function scopePorTabla($query, $tabla)
    {
        return $query->where('tabla_afectada', $tabla);
    }

    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }
}

class Alerta extends Model
{
    use HasFactory;

    protected $table = 'alertas';

    protected $fillable = [
        'cuenta_cobro_id',
        'nivel',
        'tipo_alerta',
        'mensaje',
        'leida',
        'usuario_destino_id',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function cuentaCobro()
    {
        return $this->belongsTo(CuentaCobro::class, 'cuenta_cobro_id');
    }

    public function usuarioDestino()
    {
        return $this->belongsTo(Usuario::class, 'usuario_destino_id');
    }

    // Scopes
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopeLeidas($query)
    {
        return $query->where('leida', true);
    }

    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    public function scopeCriticas($query)
    {
        return $query->where('nivel', 'CRITICAL');
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_destino_id', $usuarioId);
    }
}

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

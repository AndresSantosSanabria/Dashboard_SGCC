<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class CuentaCobro extends Model
{
    use HasFactory, Auditable;

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
        return $this->hasMany(HistorialWorkflow::class, 'cuenta_cobro_id')
            ->orderBy('fecha_transicion', 'desc')
            ->orderBy('id', 'desc');
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
        $numeroCuenta = (int)($this->numero_cuenta ?? 0);
        if ($numeroCuenta <= 0) $numeroCuenta = 1;
        return (int)($this->numero_pagos_totales ?? 0) - $numeroCuenta;
    }

    public function getUltimaFacturaHaciendaAttribute($value)
    {
        return $value ?? 'N/A';
    }

    public function getObservacionHaciendaAttribute($value)
    {
        return $value ?? 'N/A';
    }

    /**
     * Accessor for dynamic percentage calculation.
     * New formula: (Filed Invoices / Total Payments) * 100
     */
    public function getPorcentajeCuentasAttribute($value)
    {
        // If we have valid numbers, calculate dynamically
        // New formula: (Filed Invoices / Total Payments) * 100
        if (($this->numero_pagos_totales ?? 0) > 0) {
            return round((($this->numero_facturas_radicadas ?? 0) / $this->numero_pagos_totales) * 100, 2);
        }
        // Fallback to stored value or 0
        return $value ?? 0;
    }

    /**
     * Calcula el tiempo total que lleva la cuenta en el workflow
     * Debe ser el tiempo global desde su creación/radicación, no desde el último cambio.
     */
    public function getTiempoTotalEjecucionAttribute()
    {
        // Usar created_at como el inicio real del workflow en el sistema
        $primera = $this->created_at;

        if (!$primera) return '0s';

        // Si la cuenta está finalizada, el tiempo se cuenta hasta la última transición en el historial
        // de lo contrario, se cuenta hasta el momento actual (now)
        $ultima = $this->finalizada
            ? ($this->historialWorkflow()->max('fecha_transicion') ?? now())
            : now();

        $ultima = \Carbon\Carbon::parse($ultima);
        $primera = \Carbon\Carbon::parse($primera);

        // Diferencia absoluta para el tiempo global
        $diff = $primera->diff($ultima);

        $partes = [];
        if ($diff->y > 0) $partes[] = $diff->y . 'a';
        if ($diff->m > 0) $partes[] = $diff->m . 'mes';
        if ($diff->d > 0) $partes[] = $diff->d . 'd';
        if ($diff->h > 0) $partes[] = $diff->h . 'h';
        if ($diff->i > 0) $partes[] = $diff->i . 'm';

        // Siempre mostrar segundos si el tiempo es muy corto
        if (empty($partes) || $diff->s > 0) {
            $partes[] = $diff->s . 's';
        }

        return implode(' ', $partes);
    }

    public function setNumeroCuentaAttribute($value)
    {
        $this->attributes['numero_cuenta'] = (empty($value) || $value <= 0) ? 1 : $value;
    }
}

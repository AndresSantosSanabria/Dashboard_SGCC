<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaCobro extends Model
{
    /**
     * MODELO CUENTA DE COBRO: El corazón reactivo del Workflow.
     * 
     * Este modelo actúa como una Máquina de Estados. Controla en qué punto del 
     * proceso se encuentra cada trámite de pago y quién es el responsable hoy.
     */
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'cuentas_cobro';

    /**
     * Campos de seguimiento: 
     * 'bloque_actual_id' y 'estado_actual_id' definen la posición en el Kanban.
     * 'finalizada' es el flag que indica que el proceso contable terminó.
     */
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

    /**
     * Casts: Aseguramos que las fechas de radicación sean objetos Carbon 
     * para cálculos precisos de tiempos de respuesta (SLAs).
     */
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

    // --- RELACIONES DE FLUJO ---

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    /**
     * Atajo de Relación: Acceso directo al contratista.
     * Aunque la cuenta pertenece al contrato, para analítica y workflow 
     * frecuentemente necesitamos al contratista de primer nivel.
     */
    public function contratista()
    {
        return $this->hasOneThrough(
            Contratista::class,
            Contrato::class,
            'id',             // FK en contratos
            'id',             // FK en contratistas
            'contrato_id',    // Local key en cuenta_cobro
            'contratista_id'  // Local key en contrato
        );
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

    /**
     * Historial de Workflow: 
     * Esencial para auditoría y para calcular cuánto tiempo 
     * pasó la cuenta en cada etapa anteriormente.
     */
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

    /**
     * Estados por bloque (historial del workflow por fase).
     * Esta relación es clave para la tabla del dashboard, donde mostramos
     * el estado de cada etapa (Revisión, SAP, Facturación, Firma, Hacienda).
     */
    public function estadosBloques()
    {
        return $this->hasMany(EstadoBloqueCuenta::class, 'cuenta_cobro_id');
    }

    /**
     * Planillas de Seguridad Social asociadas a esta cuenta de cobro.
     * Una cuenta puede tener varias planillas, pero la última es la vigente.
     */
    public function planillasSeguridadSocial()
    {
        return $this->hasMany(PlanillaSeguridadSocial::class, 'cuenta_cobro_id')
            ->orderByDesc('created_at');
    }

    // --- ACCESSORS: INTELIGENCIA DE DATOS ---

    /**
     * Meta de Radicación:
     * Calcula cuántas facturas faltan para completar la meta del contrato.
     */
    public function getDiferenciaCuentasAttribute()
    {
        $numeroCuenta = (int) ($this->numero_cuenta ?? 0);
        if ($numeroCuenta <= 0) $numeroCuenta = 1;

        return (int) ($this->numero_pagos_totales ?? 0) - $numeroCuenta;
    }

    /**
     * Avance Porcentual Dinámico:
     * El porcentaje se calcula en tiempo real basado en (Facturas / Meta).
     */
    public function getPorcentajeCuentasAttribute($value)
    {
        if (($this->numero_pagos_totales ?? 0) > 0) {
            return round((($this->numero_facturas_radicadas ?? 0) / $this->numero_pagos_totales) * 100, 2);
        }
        return $value ?? 0;
    }

    /**
     * CÁLCULO DE TIEMPO NETO (SLA Engine):
     * 
     * Este es un algoritmo crítico. No solo resta fechas, sino que recorre 
     * el historial para DESCONTAR el tiempo pasado en estados que no cuentan 
     * tiempo (ej: cuando la cuenta fue devuelta al contratista).
     */
    public function getTiempoTotalEjecucionAttribute()
    {
        // Determinamos el marco temporal de la gestión activa
        $inicio = $this->fecha_radicacion ?? $this->created_at;
        if (! $inicio) return '0m';

        $fin = $this->finalizada
            ? ($this->historialWorkflow->max('fecha_transicion') ?? now())
            : now();

        $inicio = \Carbon\Carbon::parse($inicio);
        $fin = \Carbon\Carbon::parse($fin);

        // Consultamos el rastro de estados para detectar periodos de "pausa"
        $historial = $this->historialWorkflow()
            ->where('fecha_transicion', '>=', $inicio->copy()->subSeconds(2))
            ->orderBy('fecha_transicion', 'asc')
            ->get();

        $tiempoMuertoMinutos = 0;
        $referenciaTemporal = $inicio;

        foreach ($historial as $h) {
            $fechaTransicion = \Carbon\Carbon::parse($h->fecha_transicion);

            // Si el estado de origen no sumaba tiempo, este tramo se resta del total
            if ($h->estadoOrigen && ! ($h->estadoOrigen->contabiliza_tiempo ?? true)) {
                $tiempoMuertoMinutos += max(0, $referenciaTemporal->diffInMinutes($fechaTransicion));
            }
            $referenciaTemporal = $fechaTransicion;
        }

        // Caso final: Verificar el estado actual
        if ($this->estadoActual && ! ($this->estadoActual->contabiliza_tiempo ?? true)) {
            $tiempoMuertoMinutos += max(0, $referenciaTemporal->diffInMinutes($fin));
        }

        $totalBrutoMinutos = $inicio->diffInMinutes($fin);
        $minutosNetos = max(0, $totalBrutoMinutos - $tiempoMuertoMinutos);

        if ($minutosNetos <= 0) return '0m';

        // Formateo legible (ej: 2d 5h 30m)
        $d = floor($minutosNetos / 1440);
        $h = floor(($minutosNetos % 1440) / 60);
        $m = $minutosNetos % 60;

        $partes = [];
        if ($d > 0) $partes[] = "{$d}d";
        if ($h > 0) $partes[] = "{$h}h";
        if ($m > 0 || empty($partes)) $partes[] = "{$m}m";

        return implode(' ', $partes);
    }

    public function setNumeroCuentaAttribute($value)
    {
        $this->attributes['numero_cuenta'] = (empty($value) || $value <= 0) ? 1 : $value;
    }
}

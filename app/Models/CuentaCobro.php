<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBusinessDays;
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
    use Auditable, HasFactory, SoftDeletes, HasBusinessDays;

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
        'ss_ultima_cuenta',
        'diferencia_cuentas',
        'ultima_factura_hacienda',
        'fecha_radicacion_hacienda',
        'observacion_hacienda',
        // --- TIMETRACKING LEGACY (se mantiene por compatibilidad) ---
        'tiempo_total_segundos',
        'ultimo_inicio_conteo',
        // --- TIMETRACKING v2: Contadores duales separados ---
        'fecha_ultimo_cambio_estado',    // VOLÁTIL: se resetea en cada cambio de estado
        'tiempo_total_proceso_segundos', // PERSISTENTE: nunca se resetea
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
        'diferencia_cuentas' => 'integer',
        'finalizada' => 'boolean',
        'fecha_radicacion_hacienda' => 'datetime',
        // Legacy
        'ultimo_inicio_conteo' => 'datetime',
        'tiempo_total_segundos' => 'integer',
        // v2
        'fecha_ultimo_cambio_estado' => 'datetime',
        'tiempo_total_proceso_segundos' => 'integer',
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
        return (int) ($this->numero_pagos_totales ?? 0) - (int) ($this->numero_facturas_radicadas ?? 0);
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
     * TIEMPO TOTAL DEL PROCESO (PERSISTENTE)
     *
     * Basado en tiempo_total_proceso_segundos (acumulado histórico) +
     * el tiempo que lleva corriendo en el estado actual desde fecha_ultimo_cambio_estado.
     *
     * NUNCA se resetea al cambiar de estado. Mide el ciclo completo desde created_at.
     */
    public function getTiempoTotalEjecucionAttribute(): string
    {
        $businessTime = app(\App\Services\BusinessTimeService::class);

        // Base persistente: suma de todos los estados anteriores ya cerrados.
        $base = (int) ($this->tiempo_total_proceso_segundos ?? 0);

        // Volatil: tiempo transcurrido en el estado ACTUAL (aun no cerrado).
        // Solo sumamos si el estado actual está configurado para contabilizar tiempo.
        $volatil = ($this->fecha_ultimo_cambio_estado && ($this->estadoActual->contabiliza_tiempo ?? true))
            ? $businessTime->getWorkingSecondsBetween($this->fecha_ultimo_cambio_estado, now())
            : 0;

        $totalSegundos = $base + $volatil;

        if ($totalSegundos <= 0)
            return '0m';

        return $businessTime->formatInterval($totalSegundos);
    }

    /**
     * TIEMPO EN ESTADO ACTUAL (VOLÁTIL)
     *
     * Mide EXCLUSIVAMENTE cuánto lleva la cuenta en su estado ACTUAL.
     * Se resetea a 0 en cada cambio de estado porque fecha_ultimo_cambio_estado
     * se actualiza con cada transición.
     *
     * Esta es la métrica que alimenta las alertas de "Contrato Reposado":
     *   (NOW() - fecha_ultimo_cambio_estado) >= tiempo_limite del estado
     */
    public function getTiempoEnEstadoActualAttribute(): string
    {
        if (!$this->fecha_ultimo_cambio_estado)
            return '0m';

        $businessTime = app(\App\Services\BusinessTimeService::class);
        $segundos = $businessTime->getWorkingSecondsBetween($this->fecha_ultimo_cambio_estado, now());

        return $businessTime->formatInterval($segundos);
    }

    /**
     * Retorna los segundos en el estado actual (para comparaciones numéricas).
     */
    public function getSegundosEnEstadoActualAttribute(): int
    {
        if (!$this->fecha_ultimo_cambio_estado)
            return 0;

        $businessTime = app(\App\Services\BusinessTimeService::class);
        return $businessTime->getWorkingSecondsBetween($this->fecha_ultimo_cambio_estado, now());
    }

    /**
     * Indica si el contrato está "reposado" en el estado actual
     * según el límite configurado para ese estado (tiempo_limite_horas).
     * Fallback a la config global ALERTA_ESTANCAMIENTO_MINUTOS.
     */
    public function getEstaReposadoAttribute(): bool
    {
        if (!$this->fecha_ultimo_cambio_estado || !$this->estadoActual)
            return false;

        // Límite específico del estado (prioridad máxima)
        $limiteHoras = $this->estadoActual->tiempo_limite_horas;

        // Fallback: config global en minutos → convertir a horas
        if ($limiteHoras === null) {
            $minutos = (int) \App\Models\Configuracion::getValor('ALERTA_ESTANCAMIENTO_MINUTOS', 120);
            $limiteHoras = $minutos / 60;
        }

        $segundosLimite = (int) ($limiteHoras * 3600);
        return $this->segundos_en_estado_actual >= $segundosLimite;
    }

    public function setNumeroCuentaAttribute($value)
    {
        $this->attributes['numero_cuenta'] = (empty($value) || $value <= 0) ? 1 : $value;
    }
}

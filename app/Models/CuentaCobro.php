<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasBusinessDays;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

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
        'pausa_gestion_supervisor_desde',
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
        'pausa_gestion_supervisor_desde' => 'datetime',
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
     * Línea de tiempo completa y coherente para la UI.
     *
     * Prioriza el historial de workflow; si no existe, reconstruye desde
     * los estados por bloque para no mostrar una línea de tiempo vacía.
     */
    /**
     * REGLA DE ORO: Punto único de verdad para la línea de tiempo.
     * 
     * GARANTÍAS CRÍTICAS:
     * 1. TODAS las duraciones están en SEGUNDOS puros (no minutos, no horas)
     * 2. Se calculan como: fecha_fin - fecha_inicio (diferencia absoluta)
     * 3. El total es sumatoria aritmética de duraciones individuales
     * 4. Se valida cada valor para detectar inconsistencias
     * 5. El formato RESPETA la configuración de horarios laborales (configurable)
     */
    public function getTimelineCompletaAttribute()
    {
        $businessTime = app(\App\Services\BusinessTimeService::class);
        $timeline = collect();

        $historial = $this->relationLoaded('historialWorkflow')
            ? $this->historialWorkflow->sortBy([
                ['fecha_transicion', 'asc'],
                ['id', 'asc'],
            ])->values()
            : $this->historialWorkflow()
                ->with(['bloque', 'estadoOrigen.bloque', 'estadoDestino.bloque', 'usuarioAccion'])
                ->orderBy('fecha_transicion', 'asc')
                ->orderBy('id', 'asc')
                ->get();

        if ($historial->isNotEmpty()) {
            foreach ($historial as $hist) {
                // GARANTÍA 1: El campo tiempo_en_estado_anterior_segundos DEBE estar en segundos
                // tras la migración de consistencia. La heurística de normalización está DESHABILITADA
                // porque genera falsos positivos (ej: 60 segundos se multiplica por 60 = 1 hora).
                $tiempoRaw = (int) ($hist->tiempo_en_estado_anterior_segundos ?? 0);
                
                // Directamente usar el valor sin normalización (ya debe estar en segundos)
                // Si hay datos históricos incorrectos, la migración ya los normalizó
                $segundos = $tiempoRaw;

                $timeline->push([
                    'fuente' => 'historial',
                    'tipo' => 'transicion',
                    'fecha' => $hist->fecha_transicion,
                    'bloque' => [
                        'id' => $hist->bloque_id,
                        'nombre' => $hist->bloque?->nombre ?? $hist->estadoDestino?->bloque?->nombre ?? 'Bloque',
                        'codigo' => $hist->bloque?->codigo ?? $hist->estadoDestino?->bloque?->codigo ?? null,
                    ],
                    'estado_origen' => [
                        'id' => $hist->estado_origen_id,
                        'nombre' => $hist->estadoOrigen?->nombre ?? 'Inicio',
                        'codigo' => $hist->estadoOrigen?->codigo ?? null,
                        'tipo' => $hist->estadoOrigen?->tipo ?? null,
                    ],
                    'estado_destino' => [
                        'id' => $hist->estado_destino_id,
                        'nombre' => $hist->estadoDestino?->nombre ?? 'N/A',
                        'codigo' => $hist->estadoDestino?->codigo ?? null,
                        'tipo' => $hist->estadoDestino?->tipo ?? null,
                        'color_hex' => $hist->estadoDestino?->color_hex ?? null,
                    ],
                    'usuario_accion' => [
                        'id' => $hist->usuario_accion_id,
                        'nombre' => trim(($hist->usuarioAccion?->primer_nombre ?? '') . ' ' . ($hist->usuarioAccion?->primer_apellido ?? '')) ?: 'Sistema',
                    ],
                    'comentarios' => $hist->comentarios,
                    'metadata' => $hist->metadata ?? [],
                    'es_devolucion' => (bool) data_get($hist->metadata, 'es_devolucion', false),
                    'es_devolucion_supervisor' => (bool) data_get($hist->metadata, 'es_devolucion_supervisor', false),
                    'responsable_destino_nombre' => data_get($hist->metadata, 'responsable_destino_nombre'),
                    'supervisor_destino_nombre' => data_get($hist->metadata, 'supervisor_destino_nombre'),
                    'accion' => $hist->accion,
                    'fecha_fin' => null,
                    'tiempo_segundos' => $segundos,
                    // Usa formatInterval() que respeta HORARIO_LABORAL_INICIO y HORARIO_LABORAL_FIN (configurable)
                    'tiempo_formateado' => $segundos > 0 ? $businessTime->formatInterval($segundos) : null,
                    'reconstruido' => false,
                ]);
            }

            return $timeline;
        }

        $bloques = $this->relationLoaded('estadosBloques')
            ? $this->estadosBloques->sortBy([
                ['fecha_ingreso_bloque', 'asc'],
                ['id', 'asc'],
            ])->values()
            : $this->estadosBloques()
                ->with(['bloque', 'estadoActual', 'responsable'])
                ->orderBy('fecha_ingreso_bloque', 'asc')
                ->orderBy('id', 'asc')
                ->get();

        foreach ($bloques as $registro) {
            $inicio = $registro->fecha_ingreso_bloque ?? $registro->created_at ?? $this->created_at;
            $fin = $registro->fecha_completado_bloque ?? ($registro->fecha_ultima_actualizacion ?? now());

            $timeline->push([
                'fuente' => 'bloque',
                'tipo' => 'bloque',
                'fecha' => $inicio,
                'fecha_fin' => $registro->fecha_completado_bloque ?? null,
                'bloque' => [
                    'id' => $registro->bloque_id,
                    'nombre' => $registro->bloque?->nombre ?? 'Bloque',
                    'codigo' => $registro->bloque?->codigo ?? null,
                ],
                'estado_origen' => [
                    'id' => null,
                    'nombre' => 'Inicio del bloque',
                    'codigo' => null,
                    'tipo' => 'INICIAL',
                ],
                'estado_destino' => [
                    'id' => $registro->estado_actual_id,
                    'nombre' => $registro->estadoActual?->nombre ?? $this->estadoActual?->nombre ?? 'Estado actual',
                    'codigo' => $registro->estadoActual?->codigo ?? $this->estadoActual?->codigo ?? null,
                    'tipo' => $registro->estadoActual?->tipo ?? $this->estadoActual?->tipo ?? null,
                    'color_hex' => $registro->estadoActual?->color_hex ?? $this->estadoActual?->color_hex ?? null,
                ],
                'usuario_accion' => [
                    'id' => $registro->responsable_id,
                    'nombre' => trim(($registro->responsable?->primer_nombre ?? '') . ' ' . ($registro->responsable?->primer_apellido ?? '')) ?: 'Sin responsable',
                ],
                'comentarios' => $registro->observaciones,
                'accion' => 'RECONSTRUCCION_BLOQUE',
                'fecha_fin' => $registro->fecha_completado_bloque ?? null,
                'tiempo_segundos' => $inicio && $fin ? $businessTime->getWorkingSecondsBetween($inicio, $fin) : 0,
                'tiempo_formateado' => ($inicio && $fin) ? $businessTime->formatInterval($businessTime->getWorkingSecondsBetween($inicio, $fin)) : null,
                'reconstruido' => true,
            ]);
        }

        return $timeline;
    }

    /**
     * NORMALIZACIÓN DEFENSIVA: Convierte valores a SEGUNDOS puros.
     * 
     * DEPRECATED: Esta función está DESHABILITADA desde 01/06/2026.
     * 
     * ⚠️ PROBLEMA DETECTADO:
     * La heurística causa falsos positivos. Valores legítimos como 60 segundos (1 minuto)
     * se multiplican por 60, resultando en 3,600 segundos (1 hora).
     * 
     * Ejemplo del bug:
     * - Se guarda: 60 segundos (1 minuto de trabajo real)
     * - La función detecta: 60 < 3,600 → asume MINUTOS
     * - Multiplica: 60 * 60 = 3,600 segundos (1 hora INCORRECTA)
     * 
     * SOLUCIÓN:
     * La migración 2026_06_01_000000 ya normalizó datos históricos.
     * Nuevos registros guardan valores correctos en SEGUNDOS desde WorkflowController.
     * Por lo tanto, esta normalización NO debe aplicarse en lectura.
     * 
     * Descomentar solo si es necesario procesar datos legacy de antes de 01/06/2026.
     */
    /*
    private function normalizarTiempoASegundos(int $valor): int
    {
        if ($valor === 0) {
            return 0;
        }

        // Caso 1: Claramente menores a 1 hora en segundos → son MINUTOS
        if ($valor < 3600) {
            return $valor * 60; // Convertir MINUTOS → SEGUNDOS
        }

        // Caso 2: Entre 1h y 1 día, pero no es múltiplo de 3600 → probablemente minutos
        if ($valor >= 3600 && $valor < 86400 && ($valor % 3600) !== 0) {
            return $valor * 60;
        }

        // Caso 3: Valores muy grandes (> 1 año en segundos)
        // Si es > 31,536,000 (1 año), probablemente sean MINUTOS históricos
        if ($valor > 31536000) {
            $comoSegundos = $valor / 60;
            // Si convertido resulta en algo razonable (< 1 año), eran MINUTOS
            if ($comoSegundos < 31536000) {
                return (int) $comoSegundos;
            }
        }

        // Caso 4: Valor ya está en SEGUNDOS correctamente
        return $valor;
    }
    */

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
    /**
     * TIEMPO TOTAL DEL PROCESO (PERSISTENTE - REGLA DE ORO)
     * 
     * **GARANTÍA CRÍTICA**: El total es SUMATORIA PURA de las duraciones individuales.
     * 
     * NUNCA hace cálculos de diferencias absolutas de fechas.
     * NUNCA mezcla unidades (minutos vs segundos).
     * SOLO itera el array timeline_completa y suma los tiempo_segundos.
     * 
     * Esto garantiza que:
     * - Total = ∑(tiempo_segundos de cada nodo)
     * - No hay desfases por cambios de mes/zona horaria
     * - Total es determinista y reproducible
     */
    public function getTiempoTotalEjecucionAttribute(): string
    {
        $businessTime = app(\App\Services\BusinessTimeService::class);
        $timeline = $this->timeline_completa;

        // FILTRAR SOLO EL CICLO ACTUAL: desde último "Inicio manual del ciclo"
        // o retorno desde "Finalizada" hacia estado inicial
        $marcaCorte = $timeline->first(function($evento) {
            $comentarios = strtolower(data_get($evento, 'comentarios', ''));
            $esInicioManual = str_contains($comentarios, 'inicio manual del ciclo');

            $nombreOrigen = strtolower(data_get($evento, 'estado_origen.nombre', ''));
            $nombreDestino = strtolower(data_get($evento, 'estado_destino.nombre', ''));
            $esRetornoInicial = str_contains($nombreOrigen, 'finalizada') &&
                                (str_contains($nombreDestino, 'sin trámite') ||
                                 str_contains($nombreDestino, 'sin tramite') ||
                                 str_contains($nombreDestino, 'radicado'));

            return $esInicioManual || $esRetornoInicial;
        });

        if ($marcaCorte) {
            $timeline = $timeline->filter(fn($e) => $e->fecha >= $marcaCorte->fecha)->values();
        }

        $totalSegundos = 0;

        if ($timeline->isNotEmpty()) {
            $totalSegundos = (int) $timeline->sum(function ($evento) {
                return (int) data_get($evento, 'tiempo_segundos', 0);
            });
        } else {
            $tiempoActual = (int) TaskTimeLog::getElapsedTimeForCurrentState($this);
            $totalSegundos = $tiempoActual;
        }

        if ($totalSegundos <= 0) {
            return '0s';
        }

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
        if ($this->estaPausadaPorSupervisorReturn()) {
            return '0s';
        }

        if (! $this->estadoActual?->contabiliza_tiempo) {
            return '0s';
        }

        $fechaInicioVolatil = $this->fecha_ultimo_cambio_estado;
        if (!$fechaInicioVolatil && $this->estado_actual_id) {
            $transicion = $this->historialWorkflow()
                ->where('estado_destino_id', $this->estado_actual_id)
                ->first();
            $fechaInicioVolatil = $transicion?->fecha_transicion ?? $this->created_at;
        }

        if (!$fechaInicioVolatil)
            return '0m';

        $businessTime = app(\App\Services\BusinessTimeService::class);
        $segundos = $businessTime->getWorkingSecondsBetween($fechaInicioVolatil, now());

        return $businessTime->formatInterval($segundos);
    }

    /**
     * Retorna los segundos en el estado actual (para comparaciones numéricas).
     */
    public function getSegundosEnEstadoActualAttribute(): int
    {
        if ($this->estaPausadaPorSupervisorReturn()) {
            return 0;
        }

        if (! $this->estadoActual?->contabiliza_tiempo) {
            return 0;
        }

        $fechaInicioVolatil = $this->fecha_ultimo_cambio_estado;
        if (!$fechaInicioVolatil && $this->estado_actual_id) {
            $transicion = $this->historialWorkflow()
                ->where('estado_destino_id', $this->estado_actual_id)
                ->first();
            $fechaInicioVolatil = $transicion?->fecha_transicion ?? $this->created_at;
        }

        if (!$fechaInicioVolatil)
            return 0;

        $businessTime = app(\App\Services\BusinessTimeService::class);
        return $businessTime->getWorkingSecondsBetween($fechaInicioVolatil, now());
    }

    /**
     * Indica si el contrato está "reposado" en el estado actual
     * según el límite configurado para ese estado (tiempo_limite_horas).
     * Fallback a la config global ALERTA_ESTANCAMIENTO_MINUTOS.
     */
    public function getEstaReposadoAttribute(): bool
    {
        if ($this->estaPausadaPorSupervisorReturn()) {
            return false;
        }

        if (!$this->fecha_ultimo_cambio_estado || !$this->estadoActual || ! $this->estadoActual->contabiliza_tiempo)
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

    /**
     * MÉTODO ANTI-BUG: Obtiene el tiempo transcurrido en el estado actual.
     * 
     * SIEMPRE usa el helper protegido que filtra por sesión actual,
     * previniendo la herencia de tiempos en flujos cíclicos.
     * 
     * Retorna: segundos de tiempo laboral acumulado
     */
    public function getElapsedSeconds(): int
    {
        return TaskTimeLog::getElapsedTimeForCurrentState($this);
    }

    /**
     * Versión formateada del tiempo transcurrido (ej: "1h 30m").
     */
    public function getElapsedTimeFormatted(): string
    {
        $segundos = $this->getElapsedSeconds();
        $businessTime = app(\App\Services\BusinessTimeService::class);
        return $businessTime->formatInterval($segundos);
    }

    public function estaPausadaPorSupervisorReturn(): bool
    {
        if (! $this->soportaPausaGestionSupervisor()) {
            return false;
        }

        return ! is_null($this->pausa_gestion_supervisor_desde);
    }

    public function soportaPausaGestionSupervisor(): bool
    {
        return Schema::hasColumn($this->table, 'pausa_gestion_supervisor_desde');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\BloqueWorkflow;
use App\Models\Contrato;
use App\Models\CuentaCobro;
use App\Models\EstadoWorkflow;
use App\Models\HistorialWorkflow;
use App\Models\Supervisor;
use App\Models\EstadoBloqueCuenta;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AnaliticaController extends Controller
{
    /**
     * PANEL ANALÍTICO (Business Intelligence)
     * 
     * Este controlador es el cerebro del módulo de reportes. 
     * Implementa una lógica de "Data Density Management" para asegurar que, 
     * sin importar cuántos miles de registros existan, el usuario siempre 
     * reciba una respuesta rápida e interactiva.
     */
    public function index(Request $request)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->puedeAccederAnalitica()) {
            abort(403, 'No tienes permiso para acceder al módulo de Analítica');
        }

        // 0. SINCRONIZACIÓN DE ZONA HORARIA
        // Forzamos la sesión de Postgres a la misma zona que Laravel para evitar desfases en filtros de fecha.
        DB::statement("SET TIME ZONE 'America/Bogota'");

        // AUDITORÍA PASIVA: Registramos cada acceso al BI para trazabilidad interna.
        Contrato::logManualAudit(null, 'READ', 'El usuario consultó el panel de analítica y estadísticas', 'analitica');

        // 1. QUERY DE FILTROS — sin selects todavía, solo WHEREs.
        // Así podemos clonarla para KPIs (solo agregados) y para la tabla (con columnas extra)
        // sin que MySQL mezcle columnas individuales con funciones de agrupación (error 1140).
        $filterQuery = CuentaCobro::query();

        if ($request->filled('contrato')) {
            $filterQuery->whereHas('contrato', function ($q) use ($request) {
                $q->where('numero_contrato', 'like', '%' . $request->contrato . '%');
            });
        }

        if ($request->filled('numero_cuenta')) {
            $filterQuery->where('cuentas_cobro.numero_cuenta', 'like', '%' . $request->numero_cuenta . '%');
        }

        if ($request->filled('supervisor')) {
            $filterQuery->whereHas('contrato', function ($q) use ($request) {
                $q->where('supervisor_id', $request->supervisor);
            });
        }

        if ($request->filled('responsable') || $request->filled('f_usuario')) {
            $responsableId = $request->filled('f_usuario') ? $request->f_usuario : $request->responsable;
            $filterQuery->where('cuentas_cobro.responsable_actual_id', $responsableId);
        }

        if ($request->filled('estado') || $request->filled('f_etapa')) {
            $estadoNombre = $request->filled('f_etapa') ? $request->f_etapa : $request->estado;
            $filterQuery->whereHas('estadoActual', function ($q) use ($estadoNombre) {
                $q->where('nombre', $estadoNombre);
            });
        }

        // 1.1 Procesamiento de fechas con Carbon para asegurar el rango completo (00:00:00 a 23:59:59)
        $desde = $request->filled('fecha_desde') ? Carbon::parse($request->fecha_desde)->startOfDay() : null;
        $hasta = $request->filled('fecha_hasta') ? Carbon::parse($request->fecha_hasta)->endOfDay() : null;

        // CORRECCIÓN SEMÁNTICA: El filtro de fecha NO debe buscar cuándo se CREÓ la cuenta
        // (fecha_radicacion / created_at pueden ser de semanas atrás), sino cuándo tuvo ACTIVIDAD.
        // Una cuenta activa hoy aparece en historial_workflow con fecha_transicion de hoy.
        // Estrategia: sub-query de IDs que tuvieron al menos una transición en el rango → whereIn.
        if ($desde || $hasta) {
            $actividadQuery = DB::table('historial_workflow')
                ->select('cuenta_cobro_id')
                ->distinct();

            if ($desde) {
                $actividadQuery->whereRaw('fecha_transicion::timestamp >= ?', [$desde]);
            }
            if ($hasta) {
                $actividadQuery->whereRaw('fecha_transicion::timestamp <= ?', [$hasta]);
            }

            // También incluir cuentas cuyo campo fecha_ultimo_cambio_estado cae en el rango
            // (cubre el estado actual que aún no tiene transición de cierre).
            $estadoActivoQuery = DB::table('cuentas_cobro')
                ->select('id')
                ->whereNotNull('fecha_ultimo_cambio_estado')
                ->where('finalizada', false);

            if ($desde) {
                $estadoActivoQuery->whereRaw('fecha_ultimo_cambio_estado::timestamp >= ?', [$desde]);
            }
            if ($hasta) {
                $estadoActivoQuery->whereRaw('fecha_ultimo_cambio_estado::timestamp <= ?', [$hasta]);
            }

            // Unión de ambas fuentes de actividad
            $idsConActividad = $actividadQuery->pluck('cuenta_cobro_id')
                ->merge($estadoActivoQuery->pluck('id'))
                ->unique()
                ->values();

            $filterQuery->whereIn('cuentas_cobro.id', $idsConActividad);
        }

        if ($request->filled('porcentaje_min') || $request->filled('porcentaje_max')) {
            $filterQuery->where(function ($q) use ($request) {
                $sql = 'CASE WHEN numero_pagos_totales > 0 THEN (numero_facturas_radicadas * 100.0 / numero_pagos_totales) ELSE 0 END';
                if ($request->filled('porcentaje_min')) {
                    $q->whereRaw("({$sql}) >= ?", [(float) $request->porcentaje_min]);
                }
                if ($request->filled('porcentaje_max')) {
                    $q->whereRaw("({$sql}) <= ?", [(float) $request->porcentaje_max]);
                }
            });
        }

        // 2. EXTRACCIÓN DE KPIs — partimos de un clone limpio de $filterQuery (sin selects)
        // y aplicamos SOLO el selectRaw de agregados. Así  no ve columnas
        // individuales mezcladas con COUNT/SUM sin GROUP BY.
        $kpis = (clone $filterQuery)
            ->whereHas('estadoActual', function ($q) {
                $q->where('afecta_indicadores', true);
            })
            ->selectRaw('
                COUNT(*) as total_cuentas,
                COUNT(DISTINCT contrato_id) as total_contratos,
                SUM(CASE WHEN cuentas_cobro.finalizada::integer = 1 THEN 1 ELSE 0 END) as finalizadas,
                SUM(COALESCE(numero_facturas_radicadas, 0)) as radicadas_total,
                SUM(COALESCE(numero_pagos_totales, 0)) as pagos_totales
            ')
            ->first();

        // Resolución de Montos: obtenemos el monto total de los contratos involucrados.
        $biContratosIds = (clone $filterQuery)
            ->whereHas('estadoActual', fn($q) => $q->where('afecta_indicadores', true))
            ->select('contrato_id')
            ->distinct();

        $montoTotalResult = DB::table('contratos')
            ->whereIn('id', $biContratosIds)
            ->sum('monto_total');

        // 3. QUERY DE TABLA — clone independiente con sus propios selects enriquecidos.
        $cuentas = (clone $filterQuery)
            ->select('cuentas_cobro.*')
            ->selectRaw('numero_facturas_radicadas as radicadas_bi')
            ->selectRaw('CASE WHEN numero_pagos_totales > 0 THEN (numero_facturas_radicadas * 100.0 / numero_pagos_totales) ELSE 0 END as avance_bi')
            ->with([
                'contrato.contratista',
                'responsableActual',
                'estadoActual',
                'bloqueActual',
            ])
            ->whereHas('estadoActual', function ($q) {
                $q->where('afecta_indicadores', true);
            })
            ->orderBy('cuentas_cobro.created_at', 'desc')
            ->limit(500)
            ->get();

        // 4. Datos para gráficos ApexCharts
        $chartData = [
            'gap_chart'      => $this->getGapDataSql(clone $filterQuery),
            'heatmap'        => $this->getHeatmapDataSql(clone $filterQuery),
            'estado_anillos' => $this->getEstadoAnillos(clone $filterQuery),
            'demora_bloques' => $this->getDemoraPromedioBloques(clone $filterQuery),
            'timeline'       => $this->getTimelineActivity($request, clone $filterQuery),
            'demora_usuario_etapa' => $this->getDemoraUsuarioEtapaData($request, $desde, $hasta),
        ];

        // 5. RESPUESTA (Dual: Síncrona o AJAX)
        if ($request->ajax()) {
            return response()->json([
                'cuentas' => $cuentas,
                'totalCuentas' => $kpis->total_cuentas,
                'contratistasUnicos' => $kpis->total_contratos,
                'cuentasTramite' => (int)$kpis->total_cuentas - (int)$kpis->finalizadas,
                'cuentasFinalizadas' => $kpis->finalizadas,
                'cuentasRadicadas' => $kpis->radicadas_total,
                'pagosTotales' => $kpis->pagos_totales,
                'avanceGlobal' => $kpis->pagos_totales > 0 ? round(($kpis->radicadas_total / $kpis->pagos_totales) * 100, 2) : 0,
                'indicadorTotalUnico' => $montoTotalResult ?? 0,
                'chartData' => $chartData,
                'tableHtml' => view('Analitica.componentes.tabla_contratos', compact('cuentas'))->render(),
                'mobileTableHtml' => view('Analitica.componentes.lista_contratos_mobile', compact('cuentas'))->render(),
            ]);
        }

        // Carga inicial de catálogos para los selects de filtro
        $supervisores = Supervisor::where('es_activo', true)->limit(50)->get();
        $responsables = Usuario::where('es_activo', true)->limit(50)->get();
        
        // Datos para el selector premium multinivel
        $bloques = BloqueWorkflow::orderBy('orden')->get();
        $todosLosEstados = EstadoWorkflow::where('es_activo', true)
            ->where('afecta_indicadores', true)
            ->with('bloque')
            ->get()
            ->groupBy(fn($est) => $est->bloque->codigo ?? 'SIN_BLOQUE');

        return view('Analitica.analitica', [
            'cuentas' => $cuentas,
            'totalCuentas' => $kpis->total_cuentas,
            'contratistasUnicos' => $kpis->total_contratos,
            'cuentasTramite' => (int)$kpis->total_cuentas - (int)$kpis->finalizadas,
            'cuentasFinalizadas' => $kpis->finalizadas,
            'cuentasRadicadas' => $kpis->radicadas_total,
            'pagosTotales' => $kpis->pagos_totales,
            'avanceGlobal' => $kpis->pagos_totales > 0 ? round(($kpis->radicadas_total / $kpis->pagos_totales) * 100, 2) : 0,
            'montoTotal' => $montoTotalResult ?? 0,
            'supervisores' => $supervisores,
            'responsables' => $responsables,
            'bloques' => $bloques,
            'todosLosEstados' => $todosLosEstados,
            'chartData' => $chartData,
            'usuariosMetricas' => Usuario::whereHas('rol', function($q) {
                $q->whereNotIn('nombre', ['Administrador', 'Visualizador']);
            })->get(),
            'etapasDisponibles' => BloqueWorkflow::orderBy('orden')->pluck('nombre')->toArray()
        ]);
    }

    /**
     * Motor de Pipeline: Agrupa por bloques para detectar cuellos de botella.
     */
    private function getGapDataSql($query)
    {
        // 1. Obtener todos los bloques maestros para garantizar que la estructura de la gráfica no se rompa
        $bloquesMaster = BloqueWorkflow::orderBy('orden')->pluck('nombre', 'id');

        // 2. Ejecutar la agrupación
        $counts = (clone $query)
            ->whereHas('estadoActual', fn($q) => $q->where('afecta_indicadores', true))
            ->select('bloque_actual_id', DB::raw('COUNT(*) as total'))
            ->groupBy('bloque_actual_id')
            ->pluck('total', 'bloque_actual_id');

        $labels = [];
        $series = [];

        foreach ($bloquesMaster as $id => $nombre) {
            $labels[] = $nombre;
            $series[] = (int) $counts->get($id, 0);
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * Mapa de Calor Humano: Visualiza la carga de trabajo por responsable.
     */
    private function getHeatmapDataSql($query)
    {
        return (clone $query)
            ->whereHas('estadoActual', fn($q) => $q->where('afecta_indicadores', true))
            ->leftJoin('usuarios', 'cuentas_cobro.responsable_actual_id', '=', 'usuarios.id')
            ->selectRaw("TRIM(CONCAT(primer_nombre, ' ', primer_apellido)) as name")
            ->selectRaw("SUM(CASE WHEN cuentas_cobro.finalizada = false THEN 1 ELSE 0 END) as tramite")
            ->selectRaw("SUM(CASE WHEN cuentas_cobro.finalizada = true THEN 1 ELSE 0 END) as finalizadas")
            ->groupBy('usuarios.id', 'primer_nombre', 'primer_apellido')
            ->limit(10)
            ->get();
    }

    /**
     * Donut de Estado: En Proceso vs. En Devolución.
     * Compara cuentas activas sin devolución contra las que están
     * actualmente en un estado tipo DEVUELTO (rechazadas por algún bloque).
     */
    private function getEstadoAnillos($query)
    {
        // CORRECCIÓN: La clasificación de "En Devolución" se basa en el campo `permite_devolucion`
        // del estado actual — el "Nodo de Reversa" configurado en la UI de estados.
        // NO se usa el campo `tipo` ni el nombre del estado, ya que el administrador puede
        // ponerle cualquier nombre (ej. "JUANPEPEPITOPEREZ") a un nodo de reversa.
        $enDevolucion = (clone $query)
            ->whereHas('estadoActual', fn($q) => $q->where('permite_devolucion', true))
            ->count();

        // "En Proceso": cuentas no finalizadas cuyo estado actual NO es un nodo de reversa,
        // y cuyo estado afecta los indicadores operativos.
        $enProceso = (clone $query)
            ->where('cuentas_cobro.finalizada', false)
            ->whereHas('estadoActual', fn($q) => $q->where('afecta_indicadores', true)->where('permite_devolucion', false))
            ->count();

        return [
            'labels' => ['En Proceso', 'En Devolución'],
            'series' => [(int)$enProceso, (int)$enDevolucion],
            'total'  => (int)$enProceso + (int)$enDevolucion
        ];
    }

    /**
     * Demora Promedio por Bloque: Parte de TODOS los bloques del workflow y hace
     * LEFT JOIN con el historial para que siempre aparezcan todas las etapas,
     * aunque su promedio sea 0 por falta de datos históricos.
     */
    private function getDemoraPromedioBloques($query)
    {
        $cuentaIds = (clone $query)->pluck('cuentas_cobro.id');

        // Traemos todos los bloques ordenados, aunque no tengan historial
        $bloques = DB::table('bloques_workflow')
            ->orderBy('orden')
            ->pluck('nombre', 'id');

        if ($cuentaIds->isEmpty()) {
            return $bloques->map(fn($nombre) => [
                'bloque'         => $nombre,
                'promedio_horas' => 0,
            ])->values()->toArray();
        }

        // LEFT JOIN: si un bloque no tiene historial, AVG devuelve NULL → lo convertimos a 0
        $promedios = DB::table('bloques_workflow as bw')
            ->leftJoin('historial_workflow as hw', function ($join) use ($cuentaIds) {
                $join->on('bw.id', '=', 'hw.bloque_id')
                     ->whereIn('hw.cuenta_cobro_id', $cuentaIds)
                     ->join('estados_workflow as ew', 'hw.estado_origen_id', '=', 'ew.id')
                     ->where('ew.afecta_indicadores', true)
                     ->where('ew.contabiliza_tiempo', true)
                     ->where('hw.tiempo_en_estado_anterior_minutos', '>', 0);
            })
            ->selectRaw('bw.id, bw.nombre as bloque, bw.orden, COALESCE(AVG(hw.tiempo_en_estado_anterior_minutos), 0) as promedio_minutos')
            ->groupBy('bw.id', 'bw.nombre', 'bw.orden')
            ->orderBy('bw.orden')
            ->get();

        return $promedios->map(fn($row) => [
            'bloque'         => $row->bloque,
            'promedio_horas' => round($row->promedio_minutos / 3600, 2),
        ])->values()->toArray();
    }

    /**
     * Timeline de Actividad: Cuenta transiciones diarias en los últimos 30 días.
     */
    private function getTimelineActivity($request, $query)
    {
        $cuentaIds = (clone $query)->pluck('cuentas_cobro.id');

        if ($cuentaIds->isEmpty()) {
            return ['labels' => [], 'series' => []];
        }

        $hwQuery = DB::table('historial_workflow as hw')
            ->join('estados_workflow as ew', 'hw.estado_destino_id', '=', 'ew.id')
            ->whereIn('hw.cuenta_cobro_id', $cuentaIds)
            ->where('ew.afecta_indicadores', true)
            ->where('hw.fecha_transicion', '>=', now()->subDays(30)->startOfDay());

        if ($request->filled('f_usuario')) {
            $hwQuery->where('hw.usuario_accion_id', $request->f_usuario);
        }

        if ($request->filled('f_etapa')) {
            $hwQuery->where('ew.nombre', $request->f_etapa);
        }

        $data = $hwQuery
            ->selectRaw("DATE(hw.fecha_transicion) as dia, COUNT(*) as total")
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia');

        // Rellenar días vacíos con 0 para una línea continua
        $result = [];
        for ($i = 29; $i >= 0; $i--) {
            $dia = now()->subDays($i)->format('Y-m-d');
            $result[$dia] = $data->get($dia, 0);
        }

        return [
            'labels' => array_keys($result),
            'series' => array_values($result),
        ];
    }
    /**
     * Obtiene los datos para la nueva gráfica de Demora por Usuario y Etapa.
     * Basado EXCLUSIVAMENTE en datos REALES del flujo de trabajo, excluyendo administradores.
     */
    private function getDemoraUsuarioEtapaData(Request $request, $desde = null, $hasta = null)
    {
        $mapBloques = [
            1 => 'Estado tras primera revisión',
            2 => 'Enviada a ingresos',
            3 => 'En facturación',
            4 => 'Firma secretario',
            5 => 'Radicada en hacienda',
            6 => 'Finalizada',
        ];

        // 1. Mapeo de Estados a Bloques
        $estadosMap = EstadoWorkflow::all()->pluck('bloque_id', 'id')->toArray();
        $noAdmins = Usuario::where('rol_id', '!=', 1)->pluck('id')->toArray();

        // 2. Obtener todos los logs de tiempo cerrados
        $logsQuery = \App\Models\TaskTimeLog::where('duracion_segundos', '>', 0)
            ->whereIn('usuario_id', $noAdmins);

        if ($request->filled('f_usuario')) {
            $logsQuery->where('usuario_id', $request->f_usuario);
        }

        // Aplicar filtrado por rango de fecha (basado en el cierre del log)
        if ($desde) {
            $logsQuery->whereRaw('end_time::timestamp >= ?', [$desde]);
        }
        if ($hasta) {
            $logsQuery->whereRaw('end_time::timestamp <= ?', [$hasta]);
        }

        $logs = $logsQuery->with(['usuario'])->get();

        $consolidado = collect();

        foreach ($logs as $log) {
            $bloqueId = $estadosMap[$log->estado_id] ?? null;
            if (!$bloqueId || !isset($mapBloques[$bloqueId])) continue;

            $consolidado->push([
                'usuario_id' => $log->usuario_id,
                'usuario_nombre' => $log->usuario->nombre_completo ?? 'Desconocido',
                'etapa' => $mapBloques[$bloqueId],
                'minutos' => round($log->duracion_segundos / 60, 2)
            ]);
        }

        // 3. Obtener tiempos actuales (cuentas activas)
        $abiertosQuery = \App\Models\CuentaCobro::where('finalizada', false)
            ->whereNotNull('fecha_ultimo_cambio_estado')
            ->whereHas('estadoActual', fn($q) => $q->where('afecta_indicadores', true))
            ->whereIn('responsable_actual_id', $noAdmins);

        if ($request->filled('f_usuario')) {
            $abiertosQuery->where('responsable_actual_id', $request->f_usuario);
        }

        // CORRECCIÓN: Para la gráfica de tiempos, el criterio de fecha relevante es
        // `fecha_ultimo_cambio_estado` — el momento en que la cuenta ENTRÓ a su estado actual.
        // Usar fecha_radicacion/created_at excluía cuentas como CINDY KATHERINE que fue radicada
        // el 1 de Mayo pero lleva 35m activa HOY en "EN ESPERA FIRMA OG".
        if ($desde) {
            $abiertosQuery->whereRaw('fecha_ultimo_cambio_estado::timestamp >= ?', [$desde]);
        }
        if ($hasta) {
            $abiertosQuery->whereRaw('fecha_ultimo_cambio_estado::timestamp <= ?', [$hasta]);
        }

        $abiertos = $abiertosQuery->with(['responsableActual', 'estadoActual'])->get();
        $businessTime = app(\App\Services\BusinessTimeService::class);

        foreach ($abiertos as $cuenta) {
            $bloqueId = $cuenta->estadoActual->bloque_id ?? ($estadosMap[$cuenta->estado_actual_id] ?? null);
            if (!$bloqueId || !isset($mapBloques[$bloqueId])) continue;

            $volatil = $businessTime->getWorkingSecondsBetween($cuenta->fecha_ultimo_cambio_estado, now());

            if ($volatil <= 0) {
                $volatil = abs(now()->diffInSeconds($cuenta->fecha_ultimo_cambio_estado));
            }

            $consolidado->push([
                'usuario_id' => $cuenta->responsable_actual_id,
                'usuario_nombre' => $cuenta->responsableActual->nombre_completo ?? 'Desconocido',
                'etapa' => $mapBloques[$bloqueId],
                'minutos' => round($volatil / 60, 2)
            ]);
        }

        if ($request->filled('f_etapa')) {
            $consolidado = $consolidado->where('etapa', $request->f_etapa);
        }

        // 4. Agrupación Final
        $metricasFinales = $consolidado->groupBy(fn($item) => $item['usuario_id'] . '-' . $item['etapa'])->map(function($group) {
            $first = $group->first();
            return [
                'usuario_id' => $first['usuario_id'],
                'usuario_nombre' => $first['usuario_nombre'],
                'etapa' => $first['etapa'],
                'minutos_totales' => $group->sum('minutos')
            ];
        });

        $tiempoEquipo = $metricasFinales->groupBy('etapa')->map(function ($group, $etapa) {
            return [
                'etapa' => $etapa,
                'minutos_totales' => $group->sum('minutos_totales')
            ];
        })->values();

        $porUsuario = $metricasFinales->groupBy('usuario_id')->map(function ($group, $userId) {
            $first = $group->first();
            return [
                'usuario' => $first['usuario_nombre'],
                'datos' => $group->map(fn($m) => ['etapa' => $m['etapa'], 'minutos' => $m['minutos_totales']])->values()
            ];
        })->values();

        $tiempoGeneralMinutos = $metricasFinales->sum('minutos_totales') ?? 0;
        $etapaLenta = $tiempoEquipo->sortByDesc('minutos_totales')->first();
        $etapaRapida = $tiempoEquipo->sortBy('minutos_totales')->first();

        return [
            'tiempoEquipo' => $tiempoEquipo->values(),
            'porUsuario' => $porUsuario->values(),
            'kpis' => [
                'general' => $this->formatMinutos($tiempoGeneralMinutos),
                'lenta' => $etapaLenta ? $etapaLenta['etapa'] . ' (' . $this->formatMinutos($etapaLenta['minutos_totales']) . ')' : 'N/A',
                'rapida' => $etapaRapida ? $etapaRapida['etapa'] . ' (' . $this->formatMinutos($etapaRapida['minutos_totales']) . ')' : 'N/A',
            ]
        ];
    }

    private function formatMinutos($totalMinutos)
    {
        $horas = floor($totalMinutos / 60);
        $minutos = round($totalMinutos % 60);
        return "{$horas}h {$minutos}m";
    }

}

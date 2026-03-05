<?php

namespace App\Http\Controllers;

use App\Models\BloqueWorkflow;
use App\Models\Contrato;
use App\Models\CuentaCobro;
use App\Models\EstadoWorkflow;
use App\Models\HistorialWorkflow;
use App\Models\Supervisor;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $filterQuery->where('numero_cuenta', 'like', '%' . $request->numero_cuenta . '%');
        }

        if ($request->filled('supervisor')) {
            $filterQuery->whereHas('contrato', function ($q) use ($request) {
                $q->where('supervisor_id', $request->supervisor);
            });
        }

        if ($request->filled('responsable')) {
            $filterQuery->where('responsable_actual_id', $request->responsable);
        }

        if ($request->filled('estado')) {
            $filterQuery->whereHas('estadoActual', function ($q) use ($request) {
                $q->where('nombre', $request->estado);
            });
        }

        if ($request->filled('fecha_desde')) {
            $filterQuery->where(function ($q) use ($request) {
                $q->whereDate('fecha_radicacion', '>=', $request->fecha_desde)
                    ->orWhereDate('created_at', '>=', $request->fecha_desde);
            });
        }

        if ($request->filled('fecha_hasta')) {
            $filterQuery->where(function ($q) use ($request) {
                $q->whereDate('fecha_radicacion', '<=', $request->fecha_hasta)
                    ->orWhereDate('created_at', '<=', $request->fecha_hasta);
            });
        }

        if ($request->filled('porcentaje_min') || $request->filled('porcentaje_max')) {
            $filterQuery->where(function ($q) use ($request) {
                $sql = 'CASE WHEN numero_pagos_totales > 0 THEN (numero_facturas_radicadas / numero_pagos_totales) * 100 ELSE 0 END';
                if ($request->filled('porcentaje_min')) {
                    $q->where(DB::raw($sql), '>=', (float) $request->porcentaje_min);
                }
                if ($request->filled('porcentaje_max')) {
                    $q->where(DB::raw($sql), '<=', (float) $request->porcentaje_max);
                }
            });
        }

        // 2. EXTRACCIÓN DE KPIs — partimos de un clone limpio de $filterQuery (sin selects)
        // y aplicamos SOLO el selectRaw de agregados. Así MySQL no ve columnas
        // individuales mezcladas con COUNT/SUM sin GROUP BY (error SQLSTATE 42000:1140).
        $kpis = (clone $filterQuery)
            ->whereHas('estadoActual', function ($q) {
                $q->where('afecta_indicadores', true);
            })
            ->selectRaw('
                COUNT(*) as total_cuentas,
                COUNT(DISTINCT contrato_id) as total_contratos,
                SUM(CASE WHEN finalizada = 1 THEN 1 ELSE 0 END) as finalizadas,
                SUM(numero_facturas_radicadas) as radicadas_total,
                SUM(numero_pagos_totales) as pagos_totales
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
            ->selectRaw('CASE WHEN numero_pagos_totales > 0 THEN (numero_facturas_radicadas / numero_pagos_totales) * 100 ELSE 0 END as avance_bi')
            ->with([
                'contrato.contratista',
                'responsableActual',
                'estadoActual',
                'bloqueActual',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();

        // 4. Datos para gráficos ApexCharts
        $chartData = [
            'gap_chart' => $this->getGapDataSql(clone $filterQuery),
            'heatmap'   => $this->getHeatmapDataSql(clone $filterQuery),
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
            ]);
        }

        // Carga inicial de catálogos para los selects de filtro
        $supervisores = Supervisor::where('es_activo', true)->limit(50)->get();
        $responsables = Usuario::where('es_activo', true)->limit(50)->get();
        $estados = EstadoWorkflow::where('es_activo', true)->select('nombre')->distinct()->get();

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
            'estados' => $estados,
            'chartData' => $chartData
        ]);
    }

    /**
     * Motor de Pipeline: Agrupa por bloques para detectar cuellos de botella.
     */
    private function getGapDataSql($query)
    {
        $data = (clone $query)->join('bloques_workflow', 'cuentas_cobro.bloque_actual_id', '=', 'bloques_workflow.id')
            ->select('bloques_workflow.nombre')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('bloques_workflow.nombre')
            ->pluck('total', 'nombre');

        return [
            'labels' => $data->keys()->values(),
            'series' => $data->values(),
        ];
    }

    /**
     * Mapa de Calor Humano: Visualiza la carga de trabajo por responsable.
     */
    private function getHeatmapDataSql($query)
    {
        return (clone $query)->leftJoin('usuarios', 'cuentas_cobro.responsable_actual_id', '=', 'usuarios.id')
            ->selectRaw("CONCAT(COALESCE(primer_nombre, ''), ' ', COALESCE(primer_apellido, '')) as name")
            ->selectRaw("SUM(CASE WHEN finalizada = 0 THEN 1 ELSE 0 END) as tramite")
            ->selectRaw("SUM(CASE WHEN finalizada = 1 THEN 1 ELSE 0 END) as finalizadas")
            ->groupBy('usuarios.id', 'primer_nombre', 'primer_apellido')
            ->limit(10)
            ->get();
    }
}

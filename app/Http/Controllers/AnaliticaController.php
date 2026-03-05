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

        // 1. CONSTRUCCIÓN DE LA QUERY DINÁMICA
        // Utilizamos Eloquent para la legibilidad, pero inyectamos SQL Raw cuando 
        // la complejidad del cálculo (como el porcentaje de avance) penaliza el rendimiento.
        $baseQuery = CuentaCobro::query();

        // Filtros cruzados: La potencia del BI reside en poder filtrar una cuenta 
        // por atributos de su Contrato o Supervisor sin cargar todo el modelo.
        if ($request->filled('contrato')) {
            $baseQuery->whereHas('contrato', function ($q) use ($request) {
                $q->where('numero_contrato', 'like', '%' . $request->contrato . '%');
            });
        }

        if ($request->filled('numero_cuenta')) {
            $baseQuery->where('numero_cuenta', 'like', '%' . $request->numero_cuenta . '%');
        }

        if ($request->filled('supervisor')) {
            $baseQuery->whereHas('contrato', function ($q) use ($request) {
                $q->where('supervisor_id', $request->supervisor);
            });
        }

        if ($request->filled('responsable')) {
            $baseQuery->where('responsable_actual_id', $request->responsable);
        }

        if ($request->filled('estado')) {
            $baseQuery->whereHas('estadoActual', function ($q) use ($request) {
                $q->where('nombre', $request->estado);
            });
        }

        // Búsqueda Temporal: Optimizamos buscando tanto en la fecha de radicación 
        // (negocio) como en la de creación (sistema).
        if ($request->filled('fecha_desde')) {
            $baseQuery->where(function ($q) use ($request) {
                $q->whereDate('fecha_radicacion', '>=', $request->fecha_desde)
                    ->orWhereDate('created_at', '>=', $request->fecha_desde);
            });
        }

        if ($request->filled('fecha_hasta')) {
            $baseQuery->where(function ($q) use ($request) {
                $q->whereDate('fecha_radicacion', '<=', $request->fecha_hasta)
                    ->orWhereDate('created_at', '<=', $request->fecha_hasta);
            });
        }

        // FILTRO DE AVANCE (Cálculo On-the-fly):
        // Delegamos el cálculo matemático del progreso al motor de BD (MySQL/MariaDB) 
        // para evitar hidratar miles de modelos solo para filtrar.
        if ($request->filled('porcentaje_min') || $request->filled('porcentaje_max')) {
            $baseQuery->where(function ($q) use ($request) {
                $sql = 'CASE WHEN numero_pagos_totales > 0 THEN (numero_facturas_radicadas / numero_pagos_totales) * 100 ELSE 0 END';
                if ($request->filled('porcentaje_min')) {
                    $q->where(DB::raw($sql), '>=', (float) $request->porcentaje_min);
                }
                if ($request->filled('porcentaje_max')) {
                    $q->where(DB::raw($sql), '<=', (float) $request->porcentaje_max);
                }
            });
        }

        // 2. EXTRACCIÓN DE KPIs (Agregaciones Masivas)
        // Solo contamos cuentas que "afectan indicadores" (ignoramos borradores o anuladas 
        // según configuración del workflow).
        $biQuery = (clone $baseQuery)->whereHas('estadoActual', function ($q) {
            $q->where('afecta_indicadores', true);
        });

        // Optimizamos extrayendo todos los KPIs en una sola sentencia SQL Raw.
        $kpis = $biQuery->selectRaw('
            COUNT(*) as total_cuentas,
            COUNT(DISTINCT contrato_id) as total_contratos,
            SUM(CASE WHEN finalizada = 1 THEN 1 ELSE 0 END) as finalizadas,
            SUM(numero_facturas_radicadas) as radicadas_total,
            SUM(numero_pagos_totales) as pagos_totales
        ')->first();

        // Resolución de Montos: Evitamos el "Double Counting" de montos de contrato 
        // cuando un contrato tiene múltiples cuentas de cobro.
        $relevantIds = (clone $biQuery)->select('id')->limit(5000)->pluck('id')->toArray();
        $montoTotalResult = DB::table('cuentas_cobro')
            ->join('contratos', 'cuentas_cobro.contrato_id', '=', 'contratos.id')
            ->whereIn('cuentas_cobro.id', $relevantIds)
            ->selectRaw('SUM(DISTINCT contratos.monto_total) as total')
            ->first();

        // 3. PREPARACIÓN DE LA VISTA (Paginación Implícita)
        // Aunque la query base es masiva, la tabla solo carga los últimos 500 registros 
        // para mantener el DOM del navegador ligero y reactivo.
        $cuentas = $baseQuery->with([
            'contrato.contratista',
            'responsableActual',
            'estadoActual',
            'bloqueActual',
        ])->orderBy('created_at', 'desc')->limit(500)->get();

        // Formateo de datos para los gráficos ApexCharts
        $chartData = [
            'gap_chart' => $this->getGapDataSql(clone $biQuery),
            'heatmap' => $this->getHeatmapDataSql(clone $biQuery),
            // ... Otros placeholders para extender el BI
        ];

        // 4. RESPUESTA (Dual: Síncrona o AJAX)
        // Si es una petición AJAX (filtros interactivos), devolvemos JSON 
        // y el HTML de la tabla parcial para un "Seamless Refresh".
        if ($request->ajax()) {
            return response()->json([
                'cuentas' => $cuentas,
                'totalCuentas' => $kpis->total_cuentas,
                'contratistasUnicos' => $kpis->total_contratos,
                'avanceGlobal' => $kpis->pagos_totales > 0 ? round(($kpis->radicadas_total / $kpis->pagos_totales) * 100, 2) : 0,
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
            'montoTotal' => $montoTotalResult->total ?? 0,
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
        $data = $query->join('bloques_workflow', 'cuentas_cobro.bloque_actual_id', '=', 'bloques_workflow.id')
            ->selectRaw('bloques_workflow.nombre, COUNT(*) as total')
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
        return $query->leftJoin('usuarios', 'cuentas_cobro.responsable_actual_id', '=', 'usuarios.id')
            ->selectRaw("CONCAT(COALESCE(primer_nombre, ''), ' ', COALESCE(primer_apellido, '')) as name, 
                SUM(CASE WHEN finalizada = 0 THEN 1 ELSE 0 END) as tramite,
                SUM(CASE WHEN finalizada = 1 THEN 1 ELSE 0 END) as finalizadas")
            ->groupBy('usuarios.id', 'primer_nombre', 'primer_apellido')
            ->limit(10)
            ->get();
    }
}

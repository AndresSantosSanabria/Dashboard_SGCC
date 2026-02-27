<?php

namespace App\Http\Controllers;

use App\Models\CuentaCobro;
use App\Models\Contrato;
use App\Models\Supervisor;
use App\Models\Usuario;
use App\Models\BloqueWorkflow;
use App\Models\HistorialWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnaliticaController extends Controller
{
    public function index(Request $request)
    {
        // Registrar lectura de analítica (Auditoría)
        Contrato::logManualAudit(null, 'READ', 'El usuario consultó el panel de analítica y estadísticas', 'analitica');

        $query = CuentaCobro::with([
            'contrato.contratista',
            'contrato.supervisor',
            'contrato.registrosPresupuestales',
            'responsableActual',
            'estadoActual',
            'bloqueActual'
        ]);

        // Aplicar filtros
        if ($request->filled('contrato')) {
            $query->whereHas('contrato', function ($q) use ($request) {
                $q->where('numero_contrato', 'like', '%' . $request->contrato . '%');
            });
        }

        if ($request->filled('numero_cuenta')) {
            $query->where('numero_cuenta', 'like', '%' . $request->numero_cuenta . '%');
        }

        if ($request->filled('supervisor')) {
            $query->whereHas('contrato', function ($q) use ($request) {
                $q->where('supervisor_id', $request->supervisor);
            });
        }

        if ($request->filled('responsable')) {
            $query->where('responsable_actual_id', $request->responsable);
        }

        // Filtro de fecha mejorado: Inclusive para fecha_radicacion OR created_at
        if ($request->filled('fecha_desde')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('fecha_radicacion', '>=', $request->fecha_desde)
                    ->orWhereDate('created_at', '>=', $request->fecha_desde);
            });
        }

        if ($request->filled('fecha_hasta')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('fecha_radicacion', '<=', $request->fecha_hasta)
                    ->orWhereDate('created_at', '<=', $request->fecha_hasta);
            });
        }

        $cuentas = $query->get();

        // Lógica de Negocio BI
        $cuentas = $cuentas->transform(function ($c) {
            $c->radicadas_bi = (int)($c->numero_facturas_radicadas ?? 0);
            $meta = (int)($c->numero_pagos_totales ?? 1);
            $c->avance_bi = $meta > 0 ? round(($c->radicadas_bi / $meta) * 100, 2) : 0;
            return $c;
        });

        // Filtrar por avance calculado (BI) para coherencia total con la UI
        if ($request->filled('porcentaje_min')) {
            $cuentas = $cuentas->where('avance_bi', '>=', (float)$request->porcentaje_min);
        }

        if ($request->filled('porcentaje_max')) {
            $cuentas = $cuentas->where('avance_bi', '<=', (float)$request->porcentaje_max);
        }

        // Agrupación por contrato para KPIs globales para evitar duplicidad de metas
        $grupoPorContrato = $cuentas->groupBy('contrato_id');

        // KPIs
        $totalCuentas = $cuentas->count();
        $contratistasUnicos = $cuentas->pluck('contrato.contratista_id')->unique()->count();

        // Cuentas en trámite (procesos activos en el workflow)
        $cuentasTramite = $cuentas->filter(function ($c) {
            $estado = strtolower($c->estadoActual->nombre ?? '');
            return !($c->finalizada || str_contains($estado, 'radicad') || str_contains($estado, 'completado'));
        })->count();

        // Métricas agregadas por CONTRATO (Meta y Radicadas)
        $pagosTotales = 0;
        $cuentasRadicadas = 0;
        $indicadorTotalUnico = 0;
        $montoTotal = 0;

        foreach ($grupoPorContrato as $contratoId => $ccGroup) {
            // Se toma el valor más alto de meta y radicadas reportado para este contrato
            $pagosTotales += $ccGroup->max('numero_pagos_totales') ?? 0;
            $cuentasRadicadas += $ccGroup->max('radicadas_bi') ?? 0;

            $contrato = $ccGroup->first()->contrato;
            if ($contrato) {
                $indicadorTotalUnico += $contrato->registrosPresupuestales->sum('valor_rp');
                $montoTotal += $contrato->monto_total ?? 0;
            }
        }

        $avanceGlobal = $pagosTotales > 0 ? round(($cuentasRadicadas / $pagosTotales) * 100, 2) : 0;
        $cuentasFinalizadas = $cuentas->where('finalizada', true)->count();

        // Datos para filtros
        $supervisores = Supervisor::where('es_activo', true)
            ->get()
            ->unique('nombre_completo')
            ->sortBy('nombre_completo');

        $responsables = Usuario::where('es_activo', true)
            ->get()
            ->unique('nombre_completo')
            ->sortBy('nombre_completo');

        // Datos para Gráficos
        $chartData = [
            'gap_chart'          => $this->getGapData($cuentas),
            'estado_anillos'     => $this->getDonutData($cuentas, $cuentasTramite),
            'heatmap'            => $this->getHeatmapData($cuentas),
            'diferencia_barras'  => $this->getDiferenciaData($cuentas),
            'demora_bloques'     => $this->getDemoraBloquesData($cuentas),
            'estados_uso'        => $this->getEstadosUsoData($cuentas),
            'timeline'           => $this->getTimelineData($cuentas),
            'supervisor_perf'    => $this->getSupervisorPerformance($cuentas),
            'sla_compliance'     => $this->getSlaComplianceData($cuentas),
            'bloque_distribucion' => $this->getBloqueDistribucionData($cuentas),
        ];

        if ($request->ajax()) {
            return response()->json([
                'cuentas' => $cuentas,
                'totalCuentas' => $totalCuentas,
                'contratistasUnicos' => $contratistasUnicos,
                'cuentasTramite' => $cuentasTramite,
                'cuentasFinalizadas' => $cuentasFinalizadas,
                'cuentasRadicadas' => $cuentasRadicadas,
                'pagosTotales' => $pagosTotales,
                'avanceGlobal' => $avanceGlobal,
                'indicadorTotalUnico' => $indicadorTotalUnico,
                'chartData' => $chartData,
                'tableHtml' => view('Analitica.componentes.tabla_contratos', compact('cuentas'))->render()
            ]);
        }

        return view('Analitica.analitica', compact(
            'cuentas',
            'totalCuentas',
            'contratistasUnicos',
            'cuentasTramite',
            'cuentasFinalizadas',
            'cuentasRadicadas',
            'pagosTotales',
            'avanceGlobal',
            'montoTotal',
            'indicadorTotalUnico',
            'supervisores',
            'responsables',
            'chartData'
        ));
    }

    private function getGapData($cuentas)
    {
        // Agrupación masiva por bloques del workflow
        $data = $cuentas->groupBy(function ($c) {
            return $c->bloqueActual->nombre ?? 'Sin Bloque';
        })->map(function ($group) {
            return $group->count();
        });

        // Ordenar por el orden natural de los bloques si es posible
        return [
            'labels' => $data->keys()->values(),
            'series' => $data->values()
        ];
    }

    private function getDonutData($cuentas, $cuentasTramite)
    {
        $devueltas = $cuentas->filter(function ($c) {
            $estado = strtolower($c->estadoActual->nombre ?? '');
            return str_contains($estado, 'devuelta') || str_contains($estado, 'rechaz');
        })->count();
        $enProceso = max(0, $cuentasTramite - $devueltas);

        return [
            'labels' => ['En Proceso', 'Devueltas'],
            'series' => [$enProceso, $devueltas]
        ];
    }

    private function getDemoraBloquesData($cuentas)
    {
        // Obtener IDs de las cuentas filtradas
        $cuentaIds = $cuentas->pluck('id')->toArray();

        // Usamos la tabla especializada filtrando por las cuentas que el usuario seleccionó
        return \App\Models\EstadoBloqueCuenta::with('bloque')
            ->whereIn('cuenta_cobro_id', $cuentaIds)
            ->whereNotNull('fecha_ingreso_bloque')
            ->get()
            ->groupBy('bloque_id')
            ->map(function ($group) {
                // Calcular duración para cada registro (histórico o actual)
                $duracionesMinutos = $group->map(function ($ebc) {
                    $inicio = $ebc->fecha_ingreso_bloque;
                    $fin = $ebc->fecha_completado_bloque ?? now();
                    return max(0, $inicio->diffInMinutes($fin));
                });

                $promedioMinutos = $duracionesMinutos->avg();

                return [
                    'bloque'          => $group->first()->bloque->nombre ?? 'N/A',
                    'promedio_horas'  => round($promedioMinutos / 60, 2),
                    'total_casos'     => $group->count(),
                    'orden'           => $group->first()->bloque->orden ?? 99
                ];
            })
            ->sortBy('orden')
            ->values();
    }

    private function getEstadosUsoData($cuentas)
    {
        return $cuentas->groupBy(function ($c) {
            return trim($c->estadoActual->nombre ?? 'N/A');
        })->map(function ($group, $name) {
            return [
                'estado'   => $name,
                'cantidad' => $group->count()
            ];
        })->sortByDesc('cantidad')->take(8)->values();
    }

    private function getHeatmapData($cuentas)
    {
        return $cuentas->groupBy(function ($c) {
            return $c->responsableActual ? $c->responsableActual->nombre_completo : 'Sin Asignar';
        })->map(function ($group, $name) {
            return [
                'name'      => $name,
                'tramite'   => $group->where('finalizada', false)->count(),
                'devueltas' => $group->filter(function ($c) {
                    $estado = strtolower($c->estadoActual->nombre ?? '');
                    return str_contains($estado, 'devuelta');
                })->count(),
                'finalizadas' => $group->where('finalizada', true)->count(),
            ];
        })->values();
    }

    private function getDiferenciaData($cuentas)
    {
        return $cuentas->map(function ($c) {
            return [
                'contrato'   => $c->contrato->numero_contrato ?? 'N/A',
                'diferencia' => ($c->numero_pagos_totales ?? 0) - ($c->radicadas_bi ?? 0)
            ];
        })->sortByDesc('diferencia')->take(10)->values();
    }

    private function getTimelineData($cuentas)
    {
        $cuentaIds = $cuentas->pluck('id')->toArray();

        // Determinar el rango de fechas para el gráfico
        $fechaHasta = request('fecha_hasta') ? Carbon::parse(request('fecha_hasta')) : Carbon::now();
        $fechaDesde = request('fecha_desde') ? Carbon::parse(request('fecha_desde')) : $fechaHasta->copy()->subDays(30);

        $data = HistorialWorkflow::select(
            DB::raw('DATE(fecha_transicion) as fecha'),
            DB::raw('COUNT(*) as total_transiciones')
        )
            ->whereIn('cuenta_cobro_id', $cuentaIds)
            ->whereBetween('fecha_transicion', [$fechaDesde->startOfDay(), $fechaHasta->endOfDay()])
            ->groupBy(DB::raw('DATE(fecha_transicion)'))
            ->orderBy('fecha')
            ->get();

        return [
            'labels' => $data->pluck('fecha')->map(fn($f) => Carbon::parse($f)->format('d M')),
            'series' => $data->pluck('total_transiciones'),
        ];
    }

    private function getSupervisorPerformance($cuentas)
    {
        return $cuentas->groupBy(function ($c) {
            return $c->contrato->supervisor->nombre_completo ?? 'Sin Supervisor';
        })->map(function ($group, $name) {
            $total = $group->count();
            $finalizadas = $group->where('finalizada', true)->count();
            return [
                'supervisor'  => $name,
                'total'       => $total,
                'finalizadas' => $finalizadas,
                'pendientes'  => $total - $finalizadas,
                'eficiencia'  => $total > 0 ? round(($finalizadas / $total) * 100, 1) : 0,
            ];
        })->sortByDesc('total')->take(8)->values();
    }

    private function getSlaComplianceData($cuentas)
    {
        $cuentaIds = $cuentas->pluck('id')->toArray();
        $bloques = BloqueWorkflow::where('es_activo', true)->orderBy('orden')->get();
        $result = [];

        foreach ($bloques as $bloque) {
            if (!$bloque->sla_horas) continue;

            $historial = HistorialWorkflow::where('bloque_id', $bloque->id)
                ->whereIn('cuenta_cobro_id', $cuentaIds)
                ->whereNotNull('tiempo_en_estado_anterior_minutos')
                ->get();

            if ($historial->isEmpty()) continue;

            $slaMinutos = $bloque->sla_horas * 60;
            $cumple = $historial->filter(fn($h) => abs($h->tiempo_en_estado_anterior_minutos) <= $slaMinutos)->count();
            $total = $historial->count();

            $result[] = [
                'bloque'     => $bloque->nombre,
                'sla_horas'  => $bloque->sla_horas,
                'cumple'     => $cumple,
                'no_cumple'  => $total - $cumple,
                'porcentaje' => $total > 0 ? round(($cumple / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    private function getBloqueDistribucionData($cuentas)
    {
        return $cuentas->groupBy(function ($c) {
            return $c->bloqueActual->nombre ?? 'Sin Bloque';
        })->map(function ($group, $name) {
            return [
                'bloque'   => $name,
                'cantidad' => $group->count()
            ];
        })->sortByDesc('cantidad')->values();
    }
}

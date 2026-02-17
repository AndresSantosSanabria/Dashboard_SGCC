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
        $query = CuentaCobro::with([
            'contrato.contratista',
            'contrato.supervisor',
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

        if ($request->filled('porcentaje_min')) {
            $query->where('porcentaje_cuentas', '>=', $request->porcentaje_min);
        }

        if ($request->filled('porcentaje_max')) {
            $query->where('porcentaje_cuentas', '<=', $request->porcentaje_max);
        }

        $cuentas = $query->get();

        // Lógica de Negocio BI
        $cuentas->transform(function ($c) {
            $nCuenta = (int)($c->numero_cuenta ?? 1);
            $c->radicadas_bi = $c->finalizada ? $nCuenta : max(0, $nCuenta - 1);
            $meta = (int)($c->numero_pagos_totales ?? 1);
            $c->avance_bi = $meta > 0 ? round(($c->radicadas_bi / $meta) * 100, 2) : 0;
            return $c;
        });

        // KPIs
        $totalCuentas = $cuentas->count();
        $contratistasUnicos = $cuentas->pluck('contrato.contratista_id')->unique()->count();

        $cuentasTramite = $cuentas->filter(function ($c) {
            $estado = strtolower($c->estadoActual->nombre ?? '');
            return !($c->finalizada || str_contains($estado, 'radicad') || str_contains($estado, 'completado'));
        })->count();

        $cuentasFinalizadas = $cuentas->where('finalizada', true)->count();
        $cuentasRadicadas = $cuentas->sum('radicadas_bi');
        $pagosTotales = $cuentas->sum('numero_pagos_totales');
        $avanceGlobal = $totalCuentas > 0 ? $cuentas->avg('avance_bi') : 0;

        // Monto total gestionado
        $montoTotal = $cuentas->sum(fn($c) => $c->contrato->monto_total ?? 0);

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
            'demora_bloques'     => $this->getDemoraBloquesData(),
            'estados_uso'        => $this->getEstadosUsoData($cuentas),
            'timeline'           => $this->getTimelineData(),
            'supervisor_perf'    => $this->getSupervisorPerformance($cuentas),
            'sla_compliance'     => $this->getSlaComplianceData(),
            'bloque_distribucion' => $this->getBloqueDistribucionData($cuentas),
        ];

        return view('Analitica.analitica', compact(
            'cuentas', 'totalCuentas', 'contratistasUnicos', 'cuentasTramite',
            'cuentasFinalizadas', 'cuentasRadicadas', 'pagosTotales',
            'avanceGlobal', 'montoTotal', 'supervisores', 'responsables', 'chartData'
        ));
    }

    private function getGapData($cuentas)
    {
        $subset = $cuentas->take(12);
        return [
            'labels' => $subset->map(fn($c) => $c->contrato->numero_contrato ?? 'N/A')->values(),
            'metas'  => $subset->pluck('numero_pagos_totales')->values(),
            'reales' => $subset->pluck('radicadas_bi')->values(),
        ];
    }

    private function getDonutData($cuentas, $cuentasTramite)
    {
        $finalizadas = $cuentas->where('finalizada', true)->count();
        $devueltas = $cuentas->filter(function ($c) {
            $estado = strtolower($c->estadoActual->nombre ?? '');
            return str_contains($estado, 'devuelta') || str_contains($estado, 'rechaz');
        })->count();
        $enProceso = max(0, $cuentasTramite - $devueltas);

        return [
            'labels' => ['En Proceso', 'Finalizadas', 'Devueltas'],
            'series' => [$enProceso, $finalizadas, $devueltas]
        ];
    }

    private function getDemoraBloquesData()
    {
        return HistorialWorkflow::with('bloque')
            ->whereNotNull('tiempo_en_estado_anterior_minutos')
            ->get()
            ->groupBy('bloque_id')
            ->map(function ($group) {
                $promedioMinutos = abs($group->avg('tiempo_en_estado_anterior_minutos'));
                return [
                    'bloque'          => $group->first()->bloque->nombre ?? 'Sin Etiqueta',
                    'promedio_horas'  => round($promedioMinutos / 60, 2),
                    'max_horas'       => round(abs($group->max('tiempo_en_estado_anterior_minutos')) / 60, 2),
                    'min_horas'       => round(abs($group->min('tiempo_en_estado_anterior_minutos')) / 60, 2),
                    'total_registros' => $group->count(),
                ];
            })->values();
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

    private function getTimelineData()
    {
        $data = HistorialWorkflow::select(
            DB::raw('DATE(fecha_transicion) as fecha'),
            DB::raw('COUNT(*) as total_transiciones')
        )
            ->where('fecha_transicion', '>=', Carbon::now()->subDays(30))
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

    private function getSlaComplianceData()
    {
        $bloques = BloqueWorkflow::where('es_activo', true)->orderBy('orden')->get();
        $result = [];

        foreach ($bloques as $bloque) {
            if (!$bloque->sla_horas) continue;

            $historial = HistorialWorkflow::where('bloque_id', $bloque->id)
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

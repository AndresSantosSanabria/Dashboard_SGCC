<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Modalidad;
use App\Models\Supervisor;
use App\Models\Contratista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SeguimientoMensual;
use App\Models\SeguimientoRequisito;

class SeguimientoController extends Controller
{
    public function index(Request $request)
    {
        $query = Contrato::with(['contratista', 'supervisor', 'modalidad', 'planta', 'concepto']);

        // Filtros dinámica
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_contrato', 'LIKE', "%{$search}%")
                    ->orWhere('numero_proceso', 'LIKE', "%{$search}%")
                    ->orWhereHas('contratista', function ($sq) use ($search) {
                        $sq->where('nombre_completo', 'LIKE', "%{$search}%")
                            ->orWhere('nit', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('numero_contrato')) {
            $query->where('numero_contrato', 'LIKE', "%{$request->numero_contrato}%");
        }

        if ($request->filled('tipo_contratista')) {
            $query->where('tipo_contratista', 'LIKE', "%{$request->tipo_contratista}%");
        }

        if ($request->filled('supervisor_id')) {
            $query->where('supervisor_id', $request->supervisor_id);
        }

        if ($request->filled('modalidad_id')) {
            $query->where('modalidad_id', $request->modalidad_id);
        }

        // Filtro compuesto Ejemplo: Numero y Estado (se puede extender según necesidad)
        if ($request->filled('estado_filtro')) {
            $estado = $request->estado_filtro;
            $query->where(function ($q) use ($estado) {
                $q->where('cta1_secop_status', $estado)
                    ->orWhere('cta1_sia_status', $estado);
                // Se podrían añadir más campos de estado aquí
            });
        }

        $allContratos = $query->with(['seguimientoMensual', 'seguimientoRequisitos'])->latest()->get();

        // Lógica de Progreso basado en EJECUCIÓN MENSUAL (12 Meses)
        $ctaFields = [];
        for ($i = 1; $i <= 12; $i++) {
            $ctaFields[] = "cta{$i}_secop_status";
            $ctaFields[] = "cta{$i}_sia_status";
        }

        $totalOkCount = 0;
        $totalPendCount = 0;
        $totalCritCount = 0;
        $totalSumPerc = 0;

        foreach ($allContratos as $c) {
            // Mapear seguimiento mensual a atributos dinámicos para que la vista no se rompa
            foreach ($c->seguimientoMensual as $sm) {
                $attr = "cta{$sm->mes}_" . strtolower($sm->fuente) . "_status";
                $c->$attr = $sm->estado;
            }

            // Mapear requisitos a atributos dinámicos
            foreach ($c->seguimientoRequisitos as $sr) {
                $c->{$sr->nombre} = $sr->estado;
            }

            $ok = 0;
            $na = 0;
            $pend = 0;
            $crit = 0;
            foreach ($ctaFields as $field) {
                $val = $c->$field;
                if ($val === 'OK') $ok++;
                elseif ($val === 'N/A') $na++;
                elseif ($val === 'PENDIENTE') $pend++;
                elseif (in_array($val, ['RECHAZADO', 'FALTA', 'CRÍTICO'])) $crit++;
            }

            // Estado general basado en la ejecución
            if ($crit > 0) $c->global_status = 'CRÍTICO';
            elseif ($pend > 0) $c->global_status = 'PENDIENTES';
            elseif (($ok + $na) === 24) $c->global_status = 'COMPLETO';
            elseif (($ok + $na) > 0) $c->global_status = 'EN PROGRESO';
            else $c->global_status = 'VACÍO';

            // El progreso es la suma de OK y N/A sobre el total de 24 campos (12 meses * 2)
            $c->perc_cumplimiento = (($ok + $na) / 24) * 100;

            if ($c->global_status == 'COMPLETO') $totalOkCount++;
            elseif ($c->global_status == 'PENDIENTES' || $c->global_status == 'EN PROGRESO') $totalPendCount++;
            elseif ($c->global_status == 'CRÍTICO') $totalCritCount++;
            $totalSumPerc += $c->perc_cumplimiento;
        }

        // Ordenar por Nivel de Riesgo/Estado (CRÍTICO > PENDIENTES > EN PROGRESO > COMPLETO > VACÍO)
        $priority = ['CRÍTICO' => 4, 'PENDIENTES' => 3, 'EN PROGRESO' => 2, 'COMPLETO' => 1, 'VACÍO' => 0];
        $contratos = $allContratos->sortByDesc(function ($c) use ($priority) {
            return $priority[$c->global_status] ?? 0;
        });

        $stats = [
            'total' => count($allContratos),
            'val_total' => $allContratos->sum('monto_total'),
            'ok_contratos' => $totalOkCount,
            'pend_contratos' => $totalPendCount,
            'crit_contratos' => $totalCritCount,
            'avg_cumplimiento' => count($allContratos) > 0 ? $totalSumPerc / count($allContratos) : 0
        ];

        $supervisores = Supervisor::all();
        $modalidades = Modalidad::all();
        $contratistas = Contratista::all();

        if ($request->ajax()) {
            return view('seguimiento.partials.table', compact('contratos'))->render();
        }

        return view('seguimiento.index', compact('contratos', 'supervisores', 'modalidades', 'contratistas', 'stats'));
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:contratos,id',
            'field' => 'required|string',
            'status' => 'nullable|string'
        ]);

        $field = $request->field;
        $status = $request->status;

        // Determinar si es un campo de seguimiento mensual o un requisito
        if (preg_match('/^cta(\d+)_(secop|sia)_status$/', $field, $matches)) {
            $mes = $matches[1];
            $fuente = strtoupper($matches[2]);

            SeguimientoMensual::updateOrCreate(
                ['contrato_id' => $request->id, 'mes' => $mes, 'fuente' => $fuente],
                ['estado' => $status]
            );
        } else {
            // Es un requisito
            SeguimientoRequisito::updateOrCreate(
                ['contrato_id' => $request->id, 'nombre' => $field],
                ['estado' => $status]
            );
        }

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_proceso' => 'nullable|string',
            'numero_contrato' => 'required|string|unique:contratos,numero_contrato',
            'modalidad_id' => 'nullable|exists:modalidades,id',
            'contratista_nombre' => 'required|string',
            'supervisor_id' => 'nullable|exists:supervisores,id',
            'objeto' => 'nullable|string',
            'monto_total' => 'required|numeric',
            'link_secop' => 'nullable|url',
            'cdp_codigo' => 'nullable|string',
            'plazo_ejecucion' => 'nullable|string',
            'saldo' => 'nullable|numeric',
            'abogado_responsable' => 'nullable|string',
            'ops_juridico' => 'nullable|string',
        ]);

        $contratistaInfo = trim($request->contratista_nombre);
        $contratista = Contratista::updateOrCreate(
            ['razon_social' => $contratistaInfo],
            ['tipo_persona' => 'NATURAL']
        );

        $validated['contratista_id'] = $contratista->id;
        unset($validated['contratista_nombre']);

        Contrato::create($validated);

        return redirect()->back()->with('success', 'Contrato registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'numero_proceso' => 'nullable|string',
            'numero_contrato' => 'required|string|unique:contratos,numero_contrato,' . $id,
            'modalidad_id' => 'nullable|exists:modalidades,id',
            'contratista_nombre' => 'required|string',
            'supervisor_id' => 'nullable|exists:supervisores,id',
            'objeto' => 'nullable|string',
            'monto_total' => 'required|numeric',
            'link_secop' => 'nullable|url',
            'cdp_codigo' => 'nullable|string',
            'plazo_ejecucion' => 'nullable|string',
            'saldo' => 'nullable|numeric',
            'abogado_responsable' => 'nullable|string',
            'ops_juridico' => 'nullable|string',
        ]);

        $contratistaInfo = trim($request->contratista_nombre);
        $contratista = Contratista::updateOrCreate(
            ['razon_social' => $contratistaInfo],
            ['tipo_persona' => 'NATURAL']
        );

        $validated['contratista_id'] = $contratista->id;
        unset($validated['contratista_nombre']);

        $contrato = Contrato::findOrFail($id);
        $contrato->update($validated);

        return redirect()->back()->with('success', 'Contrato actualizado correctamente.');
    }
}

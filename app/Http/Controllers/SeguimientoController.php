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

use Spatie\SimpleExcel\SimpleExcelWriter;

class SeguimientoController extends Controller
{
    private function getFilteredContratos(Request $request)
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

        $allContratos = $query->with(['seguimientoMensual', 'seguimientoRequisitos'])->latest()->get();

        // Lógica de Progreso basado en EJECUCIÓN MENSUAL y REQUISITOS
        $ctaFields = [];
        for ($i = 1; $i <= 12; $i++) {
            $ctaFields[] = "cta{$i}_rep_status";
            $ctaFields[] = "cta{$i}_secop_status";
            $ctaFields[] = "cta{$i}_sia_status";
        }

        $checklistFields = [
            'estudios_previos_status', 'soportes_status', 'idoneidad_status', 
            'acuerdo_confidencialidad_status', 'clausulado_status', 
            'acta_inicio_status', 'delegacion_status', 'arl_status', 'rpc_status'
        ];

        $totalEvaluatedFields = count($ctaFields) + count($checklistFields);

        foreach ($allContratos as $c) {
            foreach ($c->seguimientoMensual as $sm) {
                $attr = "cta{$sm->mes}_" . strtolower($sm->fuente) . "_status";
                $c->$attr = $sm->estado;
            }
            foreach ($c->seguimientoRequisitos as $sr) {
                $c->{$sr->nombre} = $sr->estado;
            }

            $ok = 0; $na = 0; $pend = 0; $crit = 0;
            // Evaluar mensualidad
            foreach ($ctaFields as $field) {
                $val = $c->$field;
                if ($val === 'OK') $ok++;
                elseif ($val === 'N/A') $na++;
                elseif ($val === 'PENDIENTE') $pend++;
                elseif (in_array($val, ['RECHAZADO', 'FALTA', 'CRÍTICO'])) $crit++;
            }
            // Evaluar requisitos checklist
            foreach ($checklistFields as $field) {
                $val = $c->$field;
                if ($val === 'OK') $ok++;
                elseif ($val === 'N/A') $na++;
                elseif ($val === 'PENDIENTE') $pend++;
                elseif (in_array($val, ['RECHAZADO', 'FALTA', 'CRÍTICO'])) $crit++;
            }

            $c->total_ok = $ok;
            $c->total_na = $na;
            $c->total_pend = $pend;
            $c->total_crit = $crit;

            if ($crit > 0) $c->global_status = 'CRÍTICO';
            elseif ($pend > 0) $c->global_status = 'PENDIENTES';
            elseif (($ok + $na) === $totalEvaluatedFields) $c->global_status = 'COMPLETO';
            elseif (($ok + $na) > 0) $c->global_status = 'EN PROGRESO';
            else $c->global_status = 'VACÍO';

            $c->perc_cumplimiento = (($ok + $na) / $totalEvaluatedFields) * 100;
        }

        $priority = ['CRÍTICO' => 4, 'PENDIENTES' => 3, 'EN PROGRESO' => 2, 'COMPLETO' => 1, 'VACÍO' => 0];
        $contratos = $allContratos->sortByDesc(function ($c) use ($priority) {
            return $priority[$c->global_status] ?? 0;
        });

        // Filtros de colección (Filtro Compuesto: Estado Interno Y/O Mes)
        if ($request->filled('estado_filtro') || $request->filled('mes_filtro')) {
            $estadoReq = strtoupper($request->estado_filtro ?? '');
            $mesReq = $request->mes_filtro;

            $contratos = $contratos->filter(function ($c) use ($estadoReq, $mesReq, $ctaFields) {
                // 1. Si hay mes seleccionado, la evaluación se limita estrictamente a ese mes
                if ($mesReq) {
                    $rep = $c->{"cta{$mesReq}_rep_status"} ?? '';
                    $sec = $c->{"cta{$mesReq}_secop_status"} ?? '';
                    $sia = $c->{"cta{$mesReq}_sia_status"} ?? '';
                    $mFields = [$rep, $sec, $sia];
                    $vals = array_filter($mFields, fn($v) => !empty($v));
                    
                    // Si no hay estado seleccionado, mostramos solo si hay alguna actividad en ese mes
                    if (empty($estadoReq)) return count($vals) > 0;

                    // Filtro inclusivo: si alguno de los campos del mes cumple el estado
                    if ($estadoReq === 'OK') return in_array('OK', $mFields);
                    if ($estadoReq === 'PENDIENTE') {
                        return in_array('PENDIENTE', $mFields) || in_array('RECHAZADO', $mFields) || in_array('CRÍTICO', $mFields);
                    }
                    if ($estadoReq === 'N/A') return in_array('N/A', $mFields);
                    if ($estadoReq === 'VACÍO') return count($vals) === 0;
                    if ($estadoReq === 'EN PROGRESO') {
                        // Un mes está "en progreso" si tiene algo pero no todo es OK/NA
                        $hasSomething = count($vals) > 0;
                        $allDone = true;
                        foreach($vals as $v) if (!in_array($v, ['OK', 'N/A'])) $allDone = false;
                        return $hasSomething && !$allDone;
                    }
                    
                    return true;
                }

                // 2. Si NO hay mes, la evaluación es sobre cualquier coincidencia en el contrato
                if ($estadoReq) {
                    if ($estadoReq === 'OK') return $c->total_ok > 0;
                    if ($estadoReq === 'PENDIENTE') return $c->total_pend > 0 || $c->total_crit > 0;
                    if ($estadoReq === 'EN PROGRESO') return $c->global_status === 'EN PROGRESO';
                    if ($estadoReq === 'VACÍO') return $c->global_status === 'VACÍO';
                    if ($estadoReq === 'N/A') return $c->total_na > 0;
                }

                return true;
            });
        }

        if ($request->filled('secop_filtro')) {
            $secopReq = strtoupper($request->secop_filtro);
            $contratos = $contratos->filter(function ($c) use ($secopReq) {
                return strtoupper($c->secop_estado_contrato) === $secopReq;
            });
        }

        return $contratos;
    }

    public function index(Request $request)
    {
        // Registrar lectura de la vista (Auditoría)
        Contrato::logManualAudit(null, 'READ', 'El usuario cargó la vista de seguimiento/dashboard', 'contratos');

        $contratos = $this->getFilteredContratos($request);

        // Recalcular estadísticas basadas en los contratos filtrados
        $fOk = 0; $fPend = 0; $fSum = 0;
        foreach ($contratos as $c) {
            if ($c->global_status == 'COMPLETO') $fOk++;
            elseif (in_array($c->global_status, ['PENDIENTES', 'EN PROGRESO', 'CRÍTICO'])) $fPend++;
            $fSum += $c->perc_cumplimiento;
        }

        // Analíticas secundarias (SECOP y Seguimiento)
        $secCerrado = 0; $secEjecucion = 0; $secVacio = 0;
        foreach ($contratos as $c) {
            $s = strtoupper($c->secop_estado_contrato ?? '');
            if (in_array($s, ['CERRADO', 'TERMINADO'])) $secCerrado++;
            elseif ($s === 'EN EJECUCION') $secEjecucion++;
            else $secVacio++;
        }

        $stats = [
            'total' => count($contratos),
            'val_total' => $contratos->sum('monto_total'),
            'ok_contratos' => $fOk,
            'pend_contratos' => $fPend,
            'avg_cumplimiento' => count($contratos) > 0 ? $fSum / count($contratos) : 0,
            'sec_cerrado' => $secCerrado,
            'sec_ejecucion' => $secEjecucion,
            'sec_vacio' => $secVacio,
            'total_con_seguimiento' => $contratos->where('global_status', '!=', 'VACÍO')->count()
        ];

        // Paginar resultados
        $perPage = 20;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $paginatedContratos = new \Illuminate\Pagination\LengthAwarePaginator(
            $contratos->forPage($page, $perPage),
            $contratos->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );
        $contratos = $paginatedContratos;

        $supervisores = Supervisor::all();
        $modalidades = Modalidad::all();
        $contratistas = Contratista::all();

        if ($request->ajax()) {
            return response()->json([
                'table' => view('seguimiento.partials.table', compact('contratos'))->render(),
                'pagination' => (string) $contratos->appends($request->query())->links('pagination::bootstrap-5'),
                'stats' => $stats
            ]);
        }

        return view('seguimiento.index', compact('contratos', 'supervisores', 'modalidades', 'contratistas', 'stats'));
    }

    public function export(Request $request)
    {
        try {
            // Limpiar búferes de salida de forma agresiva
            while (ob_get_level()) {
                ob_end_clean();
            }

            $contratos = $this->getFilteredContratos($request);
            
            // Crear archivo temporal para evitar problemas de streaming directo
            $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
            $writer = SimpleExcelWriter::create($tempFile);

            foreach ($contratos as $c) {
                $row = [
                    'PROCESO' => $c->numero_proceso,
                    'Nº CONTRATO' => $c->numero_contrato,
                    'CONTRATISTA' => $c->contratista->nombre_completo ?? 'N/A',
                    'TIPO CONTRATISTA' => $c->tipo_contratista,
                    'SUPERVISOR' => $c->supervisor->nombre_completo ?? 'N/A',
                    'OBJETO' => $c->objeto,
                    'VALOR CONTRATO' => (float) ($c->monto_total ?? 0),
                    'PLANTA' => $c->no_planta,
                    'CONCEPTO' => $c->concepto_precontractual,
                    'CDP' => $c->cdp_codigo,
                    'ESTADO GLOBAL' => $c->global_status,
                    'PROGRESO (%)' => (float) round($c->perc_cumplimiento ?? 0, 2),
                    'ESTADO SECOP' => $c->secop_estado_contrato,
                    'APROBADO Y PAGADO' => $c->aprobado_y_pagado,
                    'MODIFICACIONES Y CIERRE' => $c->modificaciones_y_cierre,
                ];

                // Requisitos Checklist
                $reqs = [
                    'estudios_previos_status' => 'ESTUDIOS PREVIOS',
                    'soportes_status' => 'SOPORTES',
                    'idoneidad_status' => 'IDONEIDAD',
                    'acuerdo_confidencialidad_status' => 'CONFIDENCIALIDAD',
                    'clausulado_status' => 'CLAUSULADO',
                    'acta_inicio_status' => 'ACTA INICIO',
                    'delegacion_status' => 'DELEGACIÓN',
                    'arl_status' => 'ARL',
                    'rpc_status' => 'RP',
                ];
                foreach ($reqs as $f => $label) {
                    $row[$label] = $c->$f ?: 'VACÍO';
                }

                // Ejecución Mensual
                for ($i = 1; $i <= 12; $i++) {
                    $row["REP $i"] = $c->{"cta{$i}_rep_status"} ?: '-';
                    $row["SEC $i"] = $c->{"cta{$i}_secop_status"} ?: '-';
                    $row["SIA $i"] = $c->{"cta{$i}_sia_status"} ?: '-';
                }

                // Gestión de Cierre
                $cierre = [
                    'evaluacion_proveedor_status' => 'EVAL. PROV.',
                    'acta_cierre_expediente_status' => 'ACTA CIERRE',
                    'requiere_acta_liq_status' => 'REQ. ACTA LIQ',
                    'acta_liq_repositorio_status' => 'EN REPOS.',
                    'acta_liq_secop_status' => 'LIQ SECOP',
                    'acta_liq_sia_status' => 'LIQ SIA',
                ];
                foreach ($cierre as $f => $label) {
                    $row[$label] = $c->$f ?: '-';
                }

                $row['SALDO'] = (float) ($c->saldo ?? 0);
                $row['OBS 1 RAZON'] = $c->observacion_1_razon;
                $row['OBS 2 ACCION'] = $c->observacion_2_accion;

                $writer->addRow($row);
            }

            $writer->close();

            return response()->download($tempFile, 'seguimiento_secop_' . date('Y-m-d') . '.xlsx')
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', $request->all());
            return redirect()->back()->with('error', 'Error al generar el archivo Excel: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:contratos,id',
                'field' => 'required|string',
                'status' => 'nullable|string'
            ]);

            $field = $request->field;
            $status = $request->status;

            // 1. Campos de Seguimiento Mensual (Cuenta)
            if (preg_match('/^cta(\d+)_(secop|sia|rep)_status$/', $field, $matches)) {
                $mes = $matches[1];
                $fuente = strtoupper($matches[2]);

                SeguimientoMensual::updateOrCreate(
                    ['contrato_id' => $request->id, 'mes' => $mes, 'fuente' => $fuente],
                    ['estado' => $status]
                );
            } 
            // 2. Campos directos del Contrato (ej: secop_estado_contrato)
            elseif (in_array($field, ['secop_estado_contrato', 'aprobado_y_pagado', 'modificaciones_y_cierre'])) {
                $contrato = Contrato::findOrFail($request->id);
                $contrato->$field = $status;
                $contrato->save();
            }
            // 3. Requisitos técnicos
            else {
                SeguimientoRequisito::updateOrCreate(
                    ['contrato_id' => $request->id, 'nombre' => $field],
                    ['estado' => $status]
                );
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', ['id' => $request->id, 'field' => $request->field, 'status' => $request->status]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
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
                'contador_responsable' => 'nullable|string',
                'observacion_1_razon' => 'nullable|string',
                'observacion_2_accion' => 'nullable|string',
                'razon_no_liquidacion' => 'nullable|string',
                'ops_juridico' => 'nullable|string',
                'no_planta' => 'nullable|string',
                'concepto_precontractual' => 'nullable|string',
                'tipo_contratista' => 'nullable|string',
                'aprobado_y_pagado' => 'nullable|string',
                'modificaciones_y_cierre' => 'nullable|string',
            ]);

            $contratistaInfo = trim($request->contratista_nombre);
            $contratista = Contratista::updateOrCreate(
                ['razon_social' => $contratistaInfo],
                ['tipo_persona' => 'NATURAL']
            );

            $validated['contratista_id'] = $contratista->id;
            unset($validated['contratista_nombre']);

            Contrato::create($validated);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Contrato registrado correctamente.']);
            }

            return redirect()->back()->with('success', 'Contrato registrado correctamente.');
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', $request->all());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error al registrar contrato: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
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
                'contador_responsable' => 'nullable|string',
                'observacion_1_razon' => 'nullable|string',
                'observacion_2_accion' => 'nullable|string',
                'razon_no_liquidacion' => 'nullable|string',
                'ops_juridico' => 'nullable|string',
                'no_planta' => 'nullable|string',
                'concepto_precontractual' => 'nullable|string',
                'tipo_contratista' => 'nullable|string',
                'aprobado_y_pagado' => 'nullable|string',
                'modificaciones_y_cierre' => 'nullable|string',
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

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Contrato actualizado correctamente.']);
            }

            return redirect()->back()->with('success', 'Contrato actualizado correctamente.');
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', $request->all());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error al actualizar contrato: ' . $e->getMessage());
        }
    }
}

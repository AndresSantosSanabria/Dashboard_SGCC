<?php

namespace App\Http\Controllers;

use App\Models\Contratista;
use App\Models\Contrato;
use App\Models\Modalidad;
use App\Models\SeguimientoMensual;
use App\Models\SeguimientoRequisito;
use App\Models\Supervisor;
use App\Models\Usuario;
use App\Models\CuentaCobro;
use App\Models\EstadoBloqueCuenta;
use App\Models\HistorialWorkflow;
use App\Models\PlanillaSeguridadSocial;
use App\Models\Alerta;
use App\Models\RegistroPresupuestal;
use App\Models\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Spatie\SimpleExcel\SimpleExcelWriter;

class SeguimientoController extends Controller
{
    /**
     * MOTOR DE SEGUIMIENTO CONTRACTUAL
     * 
     * Este controlador implementa un patrón "Flat-to-Relational Mapping". 
     * Aunque los datos se guardan en tablas normalizadas (3NF) para 
     * escalabilidad, el motor los "aplanan" dinámicamente para que la 
     * interfaz de usuario sea fluida y fácil de usar.
     */
    private function getFilteredContratos(Request $request)
    {
        // 1. CARGA BASE CON RELACIONES
        $query = Contrato::with(['contratista', 'supervisor', 'modalidad', 'planta', 'concepto']);

        // Filtros de búsqueda: Optimizamos usando subconsultas para contratistas
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_contrato', 'LIKE', "%{$search}%")
                    ->orWhereHas('contratista', function ($sq) use ($search) {
                        $sq->where('razon_social', 'LIKE', "%{$search}%");
                    });
            });
        }

        // 2. HIDRATACIÓN DINÁMICA (The Magic Layer)
        // Obtenemos los contratos con sus seguimientos hijos.
        $allContratos = $query->with(['seguimientoMensual', 'seguimientoRequisitos'])->latest()->get();

        // Mapeamos los campos esperados por la vista (12 meses x 3 fuentes + checklist)
        $ctaFields = [];
        for ($i = 1; $i <= 12; $i++) {
            $ctaFields[] = "cta{$i}_rep_status";
            $ctaFields[] = "cta{$i}_secop_status";
            $ctaFields[] = "cta{$i}_sia_status";
        }

        $checklistFields = [
            'planta_status',
            'concepto_status',
            'cdp_status',
            'estudios_previos_status',
            'soportes_status',
            'idoneidad_status',
            'acuerdo_confidencialidad_status',
            'clausulado_status',
            'acta_inicio_status',
            'delegacion_status',
            'arl_status',
            'rpc_status',
        ];

        $totalEvaluatedFields = count($ctaFields) + count($checklistFields);

        foreach ($allContratos as $c) {
            // Transformamos filas de BD en atributos dinámicos del objeto
            foreach ($c->seguimientoMensual as $sm) {
                $attr = "cta{$sm->mes}_" . strtolower($sm->fuente) . '_status';
                $c->$attr = $sm->estado;
            }
            foreach ($c->seguimientoRequisitos as $sr) {
                $c->{$sr->nombre} = $sr->estado;
            }

            // 3. MOTOR DE CÁLCULO DE CUMPLIMIENTO (SLA/Compliance Engine)
            // Evaluamos el "Peso" de cada estado para determinar si el contrato está en riesgo.
            $ok = 0;
            $na = 0;
            $pend = 0;
            $crit = 0;

            $allStatusFields = array_merge($ctaFields, $checklistFields);
            foreach ($allStatusFields as $field) {
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

            // Determinación del Semáforo Global
            if ($crit > 0) $c->global_status = 'CRÍTICO';
            elseif ($pend > 0) $c->global_status = 'PENDIENTES';
            elseif (($ok + $na) === $totalEvaluatedFields) $c->global_status = 'COMPLETO';
            elseif (($ok + $na) > 0) $c->global_status = 'EN PROGRESO';
            else $c->global_status = 'VACÍO';

            $c->perc_cumplimiento = $totalEvaluatedFields > 0 ? (($ok + $na) / $totalEvaluatedFields) * 100 : 0;
        }

        // 4. ORDENAMIENTO POR PRIORIDAD DE RIESGO
        $priority = ['CRÍTICO' => 4, 'PENDIENTES' => 3, 'EN PROGRESO' => 2, 'COMPLETO' => 1, 'VACÍO' => 0];
        $contratos = $allContratos->sortByDesc(fn($c) => $priority[$c->global_status] ?? 0);

        // Filtros de colección (Filtro Compuesto: Estado Interno Y/O Mes)
        if ($request->filled('estado_filtro') || $request->filled('mes_filtro')) {
            $estadoReq = strtoupper($request->estado_filtro ?? '');
            $mesReq = $request->mes_filtro;

            $contratos = $contratos->filter(function ($c) use ($estadoReq, $mesReq) {
                // 1. Si hay mes seleccionado, la evaluación se limita estrictamente a ese mes
                if ($mesReq) {
                    $rep = $c->{"cta{$mesReq}_rep_status"} ?? '';
                    $sec = $c->{"cta{$mesReq}_secop_status"} ?? '';
                    $sia = $c->{"cta{$mesReq}_sia_status"} ?? '';
                    $mFields = [$rep, $sec, $sia];
                    $vals = array_filter($mFields, fn($v) => ! empty($v));

                    if (empty($estadoReq)) {
                        return count($vals) > 0;
                    }

                    if ($estadoReq === 'OK') {
                        return in_array('OK', $mFields);
                    }
                    if ($estadoReq === 'PENDIENTE') {
                        return in_array('PENDIENTE', $mFields) || in_array('RECHAZADO', $mFields) || in_array('CRÍTICO', $mFields);
                    }
                    if ($estadoReq === 'N/A') {
                        return in_array('N/A', $mFields);
                    }
                    if ($estadoReq === 'VACÍO') {
                        return count($vals) === 0;
                    }
                    if ($estadoReq === 'EN PROGRESO') {
                        $hasSomething = count($vals) > 0;
                        $allDone = collect($vals)->every(fn($v) => in_array($v, ['OK', 'N/A']));

                        return $hasSomething && ! $allDone;
                    }

                    return true;
                }

                // 2. Si NO hay mes, la evaluación es sobre cualquier coincidencia en el contrato
                if ($estadoReq) {
                    return match ($estadoReq) {
                        'OK' => $c->total_ok > 0,
                        'PENDIENTE' => $c->total_pend > 0 || $c->total_crit > 0,
                        'EN PROGRESO' => $c->global_status === 'EN PROGRESO',
                        'VACÍO' => $c->global_status === 'VACÍO',
                        'N/A' => $c->total_na > 0,
                        default => true,
                    };
                }

                return true;
            });
        }

        if ($request->filled('secop_filtro')) {
            $secopReq = strtoupper($request->secop_filtro);
            $contratos = $contratos->filter(fn($c) => strtoupper($c->secop_estado_contrato ?? '') === $secopReq);
        }

        return $contratos;
    }

    /**
     * Muestra el dashboard de seguimiento.
     */
    public function index(Request $request)
    {
        Contrato::logManualAudit(null, 'READ', 'El usuario cargó la vista de seguimiento/dashboard', 'contratos');

        $contratos = $this->getFilteredContratos($request);

        // Estadísticas
        $fOk = $contratos->where('global_status', 'COMPLETO')->count();
        $fPend = $contratos->whereIn('global_status', ['PENDIENTES', 'EN PROGRESO', 'CRÍTICO'])->count();
        $fAvg = $contratos->avg('perc_cumplimiento') ?? 0;

        $stats = [
            'total' => $contratos->count(),
            'val_total' => $contratos->sum('monto_total'),
            'ok_contratos' => $fOk,
            'pend_contratos' => $fPend,
            'avg_cumplimiento' => $fAvg,
            'sec_cerrado' => $contratos->filter(fn($c) => in_array(strtoupper($c->secop_estado_contrato ?? ''), ['CERRADO', 'TERMINADO']))->count(),
            'sec_ejecucion' => $contratos->filter(fn($c) => strtoupper($c->secop_estado_contrato ?? '') === 'EN EJECUCION')->count(),
            'sec_vacio' => $contratos->filter(fn($c) => empty($c->secop_estado_contrato))->count(),
            'total_con_seguimiento' => $contratos->where('global_status', '!=', 'VACÍO')->count(),
        ];

        // Paginar resultados
        $perPage = 20;
        $page = Paginator::resolveCurrentPage() ?: 1;
        $paginated = new LengthAwarePaginator(
            $contratos->forPage($page, $perPage),
            $contratos->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $supervisores = Supervisor::all();
        $modalidades = Modalidad::all();
        $contratistas = Contratista::all();

        if ($request->ajax()) {
            return response()->json([
                'table' => view('seguimiento.partials.table', ['contratos' => $paginated])->render(),
                'pagination' => (string) $paginated->appends($request->query())->links('pagination::bootstrap-5'),
                'stats' => $stats,
            ]);
        }

        return view('seguimiento.index', [
            'contratos' => $paginated,
            'supervisores' => $supervisores,
            'modalidades' => $modalidades,
            'contratistas' => $contratistas,
            'stats' => $stats,
        ]);
    }

    /**
     * Exporta los datos a Excel.
     */
    public function export(Request $request)
    {
        try {
            while (ob_get_level()) {
                ob_end_clean();
            }

            $contratos = $this->getFilteredContratos($request);
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

                for ($i = 1; $i <= 12; $i++) {
                    $row["REP $i"] = $c->{"cta{$i}_rep_status"} ?: '-';
                    $row["SEC $i"] = $c->{"cta{$i}_secop_status"} ?: '-';
                    $row["SIA $i"] = $c->{"cta{$i}_sia_status"} ?: '-';
                }

                $writer->addRow($row);
            }

            $writer->close();

            return response()->download($tempFile, 'seguimiento_' . date('Ymd') . '.xlsx')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', $request->all());

            return redirect()->back()->with('error', 'Error al exportar: ' . $e->getMessage());
        }
    }

    /**
     * MOTOR DE PERSISTENCIA (Intelligent Router)
     * 
     * Este método detecta qué tipo de campo se está actualizando (Mensual, 
     * Requisito o Maestro) y lo enruta a la tabla correcta. 
     * Es el corazón de la reactividad del Spreadsheet de seguimiento.
     */
    public function updateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:contratos,id',
                'field' => 'required|string',
                'status' => 'nullable|string',
            ]);

            $field = $validated['field'];
            $status = $validated['status'];

            // CASO A: Seguimiento Mensual (Cta1, Cta2...) - Regex para detectar patrón
            if (preg_match('/^cta(\d+)_(secop|sia|rep)_status$/', $field, $matches)) {
                SeguimientoMensual::updateOrCreate(
                    ['contrato_id' => $validated['id'], 'mes' => $matches[1], 'fuente' => strtoupper($matches[2])],
                    ['estado' => $status]
                );
            }
            // CASO B: Atributos Maestros del Contrato
            elseif (in_array($field, ['secop_estado_contrato', 'aprobado_y_pagado', 'modificaciones_y_cierre', 'link_secop', 'tipo_contratista'])) {
                $contrato = Contrato::findOrFail($validated['id']);
                $contrato->$field = $status;
                $contrato->save();
            }
            // CASO C: Requisitos de Checklist (Normalizados)
            else {
                SeguimientoRequisito::updateOrCreate(
                    ['contrato_id' => $validated['id'], 'nombre' => $field],
                    ['estado' => $status]
                );
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', $request->all());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Registra un nuevo contrato.
     */
    public function store(Request $request)
    {
        return $this->processStoreOrUpdate($request);
    }

    /**
     * Actualiza un contrato existente.
     */
    public function update(Request $request, $id)
    {
        return $this->processStoreOrUpdate($request, $id);
    }

    /**
     * Lógica común para guardar o actualizar.
     */
    private function processStoreOrUpdate(Request $request, $id = null)
    {
        try {
            $rules = [
                'numero_proceso' => 'nullable|string',
                'numero_contrato' => 'required|string',
                'modalidad_id' => 'nullable|exists:modalidades,id',
                'contratista_nombre' => 'required|string',
                'supervisor_id' => 'nullable|exists:supervisores,id',
                'monto_total' => 'required|numeric',
                'objeto' => 'nullable|string',
                'link_secop' => 'nullable|url',
                'cdp_codigo' => 'nullable|string',
            ];

            $validated = $request->validate($rules);

            // Obtener o crear contratista
            $contratista = Contratista::updateOrCreate(
                ['razon_social' => trim($request->contratista_nombre)],
                ['tipo_persona' => 'NATURAL']
            );

            $data = $request->all();
            $data['contratista_id'] = $contratista->id;

            // Normalizar número de contrato para evitar duplicados por espacios o mayúsculas
            $numContrato = strtoupper(trim($validated['numero_contrato']));
            $data['numero_contrato'] = $numContrato;

            // Buscar si ya existe por número de contrato (Regla de negocio principal)
            $contratoExistente = Contrato::where(DB::raw('UPPER(TRIM(numero_contrato))'), $numContrato)->first();

            if ($contratoExistente) {
                $contratoExistente->update($data);
                $msg = 'Contrato actualizado correctamente.';
            } else {
                Contrato::create($data);
                $msg = 'Contrato registrado correctamente.';
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', $request->all());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Elimina un contrato y todos sus registros relacionados.
     */
    public function destroy($id)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->tienePermiso('editar_dashboard')) {
            return response()->json(['success' => false, 'message' => 'No autorizado para eliminar registros.'], 403);
        }

        try {
            $contrato = Contrato::findOrFail($id);
            $numContrato = $contrato->numero_contrato;

            // Transacción robusta: El mismo patrón que CuentaCobroController
            DB::transaction(function () use ($contrato, $id) {
                // Limpieza de relaciones dependientes de cuentas
                $cuentaIds = $contrato->cuentasCobro()->pluck('id');
                
                if ($cuentaIds->isNotEmpty()) {
                    EstadoBloqueCuenta::whereIn('cuenta_cobro_id', $cuentaIds)->delete();
                    HistorialWorkflow::whereIn('cuenta_cobro_id', $cuentaIds)->delete();
                    PlanillaSeguridadSocial::whereIn('cuenta_cobro_id', $cuentaIds)->delete();
                    Alerta::whereIn('cuenta_cobro_id', $cuentaIds)->delete();
                    $contrato->cuentasCobro()->delete();
                }

                // Limpieza de relaciones directas del contrato
                RegistroPresupuestal::where('contrato_id', $id)->delete();
                Documento::where('contrato_id', $id)->delete();
                
                // Eliminación física del contrato (dispara cascada en BD para seguimiento_mensual y seguimiento_requisitos)
                $contrato->delete();
            });

            // Auditoría post-borrado
            Contrato::logManualAudit(null, 'DELETE', "Contrato #$numContrato eliminado globalmente desde seguimiento", 'contratos');

            return response()->json(['success' => true, 'message' => "Contrato #$numContrato eliminado correctamente."]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', ['operacion' => 'destroy', 'id' => $id]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}

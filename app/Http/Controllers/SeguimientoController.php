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
        if ($request->filled('numero_contrato')) {
            $search = $request->numero_contrato;
            $query->where(function ($q) use ($search) {
                $q->where('numero_contrato', 'LIKE', "%{$search}%")
                    ->orWhereHas('contratista', function ($sq) use ($search) {
                        $sq->where('razon_social', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('tipo_contratista') && $request->tipo_contratista !== 'Todos') {
            $query->where('tipo_contratista', $request->tipo_contratista);
        }

        if ($request->filled('supervisor_id') && $request->supervisor_id !== 'Todos') {
            $query->where('supervisor_id', $request->supervisor_id);
        }

        // 2. HIDRATACIÓN DINÁMICA OPTIMIZADA (Database-Driven Calculation)
        // Usamos withCount para obtener los totales directamente desde Postgres, evitando cargar miles de filas en memoria.
        $query->withCount([
            'seguimientoMensual as count_ok_mensual' => fn($q) => $q->where('estado', 'OK'),
            'seguimientoMensual as count_na_mensual' => fn($q) => $q->where('estado', 'N/A'),
            'seguimientoMensual as count_pend_mensual' => fn($q) => $q->whereIn('estado', ['PENDIENTE', 'RECHAZADO', 'FALTA', 'CRÍTICO']),
            'seguimientoRequisitos as count_ok_req' => fn($q) => $q->where('estado', 'OK'),
            'seguimientoRequisitos as count_na_req' => fn($q) => $q->where('estado', 'N/A'),
            'seguimientoRequisitos as count_pend_req' => fn($q) => $q->whereIn('estado', ['PENDIENTE', 'RECHAZADO', 'FALTA', 'CRÍTICO']),
        ]);

        // 3. ORDENAMIENTO POR NÚMERO (Usando el índice funcional natural)
        $order = $request->input('sort_order', 'asc');
        $query->orderByRaw("CAST(NULLIF(regexp_replace(numero_contrato, '[^0-9]', '', 'g'), '') AS NUMERIC) " . ($order === 'desc' ? 'DESC' : 'ASC'));

        // 4. FILTROS DE ESTADO (Integración con Workflow)
        if ($request->filled('estado_filtro')) {
            $estadoReq = $request->estado_filtro;
            
            // Si es un estado del workflow (viene del nuevo dropdown)
            if (!in_array(strtoupper($estadoReq), ['OK', 'PENDIENTE', 'N/A'])) {
                $query->whereHas('cuentasCobro', function($q) use ($estadoReq) {
                    // Buscamos en la cuenta más reciente del contrato
                    $q->whereIn('id', function($sub) {
                        $sub->select(DB::raw('MAX(id)'))
                            ->from('cuentas_cobro')
                            ->groupBy('contrato_id');
                    })->whereHas('estadoActual', function($sq) use ($estadoReq) {
                        $sq->where('nombre', $estadoReq);
                    });
                });
            } else {
                // Lógica antigua de cumplimiento (OK, PENDIENTE, N/A)
                $estadoReq = strtoupper($estadoReq);
                if ($estadoReq === 'OK') {
                    $query->whereRaw('(count_ok_mensual + count_ok_req) > 0');
                } elseif ($estadoReq === 'PENDIENTE') {
                    $query->whereRaw('(count_pend_mensual + count_pend_req) > 0');
                } elseif ($estadoReq === 'N/A') {
                    $query->whereRaw('(count_na_mensual + count_na_req) > 0');
                }
            }
        }

        if ($request->filled('secop_filtro')) {
            $query->where('secop_estado_contrato', 'ilike', $request->secop_filtro);
        }

        return $query;
    }

    /**
     * Muestra el dashboard de seguimiento.
     */
    public function index(Request $request)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        
        // Verificación estricta de permiso: Si no tiene acceso al seguimiento SECOP, redirigir al dashboard base
        if (!$user->puedeAccederSeguimiento()) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para acceder al Seguimiento SECOP.');
        }

        Contrato::logManualAudit(null, 'READ', 'El usuario cargó la vista de seguimiento/dashboard', 'contratos');

        $contratosQuery = $this->getFilteredContratos($request);

        $totalEvaluatedFields = 48; // 12 meses * 3 fuentes + 12 reqs (Aproximado para lógica de cumplimiento)

        // Estadísticas Dinámicas: Calculadas sobre el set filtrado completo
        // Usamos una subconsulta para procesar los count_ ya hidratados en getFilteredContratos
        $statsSub = (clone $contratosQuery);
        $summary = DB::table(DB::raw("({$statsSub->toSql()}) as sub"))
            ->mergeBindings($statsSub->getQuery())
            ->selectRaw("
                COUNT(*) as total_rows,
                SUM(monto_total) as val_total,
                SUM(CASE WHEN (count_pend_mensual + count_pend_req) > 0 THEN 1 ELSE 0 END) as count_pend,
                SUM(CASE WHEN (count_ok_mensual + count_ok_req + count_na_mensual + count_na_req) >= $totalEvaluatedFields AND (count_pend_mensual + count_pend_req) = 0 THEN 1 ELSE 0 END) as count_ok,
                AVG(((count_ok_mensual + count_ok_req + count_na_mensual + count_na_req)::float / $totalEvaluatedFields) * 100) as avg_perc,
                SUM(CASE WHEN UPPER(secop_estado_contrato) IN ('CERRADO', 'TERMINADO') THEN 1 ELSE 0 END) as sec_cerrado,
                SUM(CASE WHEN UPPER(secop_estado_contrato) = 'EN EJECUCION' THEN 1 ELSE 0 END) as sec_ejecucion,
                SUM(CASE WHEN secop_estado_contrato IS NULL OR secop_estado_contrato = '' THEN 1 ELSE 0 END) as sec_vacio
            ")->first();

        $stats = [
            'total' => $summary->total_rows ?? 0,
            'val_total' => $summary->val_total ?? 0,
            'ok_contratos' => $summary->count_ok ?? 0,
            'pend_contratos' => $summary->count_pend ?? 0,
            'avg_cumplimiento' => $summary->avg_perc ?? 0,
            'sec_cerrado' => $summary->sec_cerrado ?? 0,
            'sec_ejecucion' => $summary->sec_ejecucion ?? 0,
            'sec_vacio' => $summary->sec_vacio ?? 0,
            'total_con_seguimiento' => $summary->total_rows ?? 0,
        ];

        // Paginar resultados directamente en la base de datos
        $perPage = 20;
        $paginated = $contratosQuery->paginate($perPage)->appends($request->query());

        // Atributos dinámicos solo para los 20 resultados de la página (Ultra rápido)
        $totalEvaluatedFields = 36 + 12; // 12 meses * 3 fuentes + 12 reqs (Aproximado para lógica de cumplimiento)

        foreach ($paginated as $c) {
            foreach ($c->seguimientoMensual as $sm) {
                $attr = "cta{$sm->mes}_" . strtolower($sm->fuente) . '_status';
                $c->$attr = $sm->estado;
            }
            foreach ($c->seguimientoRequisitos as $sr) {
                $c->{$sr->nombre} = $sr->estado;
            }

            $ok = ($c->count_ok_mensual ?? 0) + ($c->count_ok_req ?? 0);
            $na = ($c->count_na_mensual ?? 0) + ($c->count_na_req ?? 0);
            $pend = ($c->count_pend_mensual ?? 0) + ($c->count_pend_req ?? 0);

            if ($pend > 0) $c->global_status = 'CRÍTICO';
            elseif (($ok + $na) === $totalEvaluatedFields) $c->global_status = 'COMPLETO';
            elseif (($ok + $na) > 0) $c->global_status = 'EN PROGRESO';
            else $c->global_status = 'VACÍO';

            $c->perc_cumplimiento = $totalEvaluatedFields > 0 ? (($ok + $na) / $totalEvaluatedFields) * 100 : 0;
        }

        $supervisores = Supervisor::all();
        $modalidades = Modalidad::all();
        $contratistas = Contratista::all();

        // Obtener estados para el filtro agrupado
        $bloques = \App\Models\BloqueWorkflow::ordenados()->get();
        $todosLosEstados = \App\Models\EstadoWorkflow::where('es_activo', true)->with('bloque')->get()->groupBy('bloque.codigo');

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
            'todosLosEstados' => $todosLosEstados,
            'bloques' => $bloques
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
                    [
                        'contrato_id' => $validated['id'], 
                        'mes' => $matches[1], 
                        'fuente' => strtoupper($matches[2]),
                        'anio' => date('Y') // Fallback al año actual para evitar fallo por nulidad en PostgreSQL
                    ],
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
                // Forzamos una búsqueda explícita para evitar problemas de binding en PostgreSQL
                $requisito = SeguimientoRequisito::where('contrato_id', $validated['id'])
                    ->where('nombre', (string)$field)
                    ->first();

                if ($requisito) {
                    $requisito->update(['estado' => $status]);
                } else {
                    SeguimientoRequisito::create([
                        'contrato_id' => $validated['id'],
                        'nombre' => (string)$field,
                        'estado' => $status
                    ]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', $request->all());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ACTUALIZACIÓN EN LOTE (Batch Processor)
     * Procesa múltiples cambios en una sola transacción para eficiencia y atomicidad.
     */
    public function batchUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'changes' => 'required|array',
                'changes.*.id' => 'required|exists:contratos,id',
                'changes.*.field' => 'required|string',
                'changes.*.newValue' => 'nullable|string',
            ]);

            DB::beginTransaction();

            foreach ($validated['changes'] as $change) {
                $id = $change['id'];
                $field = $change['field'];
                $status = $change['newValue'];

                // CASO A: Seguimiento Mensual (Cta1, Cta2...)
                if (preg_match('/^cta(\d+)_(secop|sia|rep)_status$/', $field, $matches)) {
                    SeguimientoMensual::updateOrCreate(
                        [
                            'contrato_id' => $id,
                            'mes' => $matches[1],
                            'fuente' => strtoupper($matches[2]),
                            'anio' => date('Y')
                        ],
                        ['estado' => $status]
                    );
                }
                // CASO B: Atributos Maestros
                elseif (in_array($field, ['secop_estado_contrato', 'aprobado_y_pagado', 'modificaciones_y_cierre', 'link_secop', 'tipo_contratista'])) {
                    $contrato = Contrato::findOrFail($id);
                    $contrato->$field = $status;
                    $contrato->save();
                }
                // CASO C: Requisitos
                else {
                    $requisito = SeguimientoRequisito::where('contrato_id', $id)
                        ->where('nombre', (string)$field)
                        ->first();

                    if ($requisito) {
                        $requisito->update(['estado' => $status]);
                    } else {
                        SeguimientoRequisito::create([
                            'contrato_id' => $id,
                            'nombre' => (string)$field,
                            'estado' => $status
                        ]);
                    }
                }
            }

            DB::commit();
            
            $count = count($validated['changes']);
            Contrato::logManualAudit(null, 'UPDATE', "Actualización en lote de $count campos en seguimiento", 'contratos');

            return response()->json(['success' => true, 'message' => "Se guardaron $count cambios correctamente."]);
        } catch (\Exception $e) {
            DB::rollBack();
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

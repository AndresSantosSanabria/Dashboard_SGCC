<?php

namespace App\Http\Controllers;

use App\Models\Contratista;
use App\Models\Contrato;
use App\Models\SeguimientoCampo;
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
use App\Models\EstadoWorkflow;
use App\Models\BloqueWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        $query = Contrato::with([
            'contratista',
            'supervisor',
            'modalidad',
            'planta',
            'concepto',
            'cuentaActual',
            'ultimaCuentaFinalizada',
            'seguimientoMensual',
            'seguimientoRequisitos',
        ]);

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
            'seguimientoMensual as count_ok_mensual' => fn($q) => $q->where('seguimiento_mensual.estado', 'OK'),
            'seguimientoMensual as count_na_mensual' => fn($q) => $q->where('seguimiento_mensual.estado', 'N/A'),
            'seguimientoMensual as count_pend_mensual' => fn($q) => $q->whereIn('seguimiento_mensual.estado', ['PENDIENTE', 'RECHAZADO', 'FALTA', 'CRÍTICO']),
            'seguimientoRequisitos as count_ok_req' => fn($q) => $q->where('seguimiento_requisitos.estado', 'OK'),
            'seguimientoRequisitos as count_na_req' => fn($q) => $q->where('seguimiento_requisitos.estado', 'N/A'),
            'seguimientoRequisitos as count_pend_req' => fn($q) => $q->whereIn('seguimiento_requisitos.estado', ['PENDIENTE', 'RECHAZADO', 'FALTA', 'CRÍTICO']),
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

    private function getMaxMesSeguimiento($contratosQuery): int
    {
        $maxMes = SeguimientoMensual::max('mes');

        return max(12, (int) ($maxMes ?? 12));
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
        $maxMesSeguimiento = $this->getMaxMesSeguimiento($contratosQuery);

        // Calculamos el universo de cumplimiento sobre la query filtrada (subquery atomizada para Postgres)
        $totalEvaluatedFields = ($maxMesSeguimiento * 3) + 18; // Meses dinámicos * 3 fuentes + 18 reqs
        
        $statsSub = (clone $contratosQuery);
        // Estadísticas Dinámicas: Calculadas sobre el set filtrado completo
        // Usamos fromSub() directamente con el builder de Eloquent (Laravel 10+ lo soporta y maneja mejor los bindings)
        $summary = DB::query()->fromSub($statsSub, 'sub')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN (count_ok_mensual + count_ok_req + count_na_mensual + count_na_req) >= $totalEvaluatedFields AND (count_pend_mensual + count_pend_req) = 0 THEN 1 ELSE 0 END) as count_ok,
                SUM(CASE WHEN (count_pend_mensual + count_pend_req) > 0 THEN 1 ELSE 0 END) as count_pend,
                AVG(LEAST(100.0, ((count_ok_mensual + count_ok_req + count_na_mensual + count_na_req) * 100.0) / $totalEvaluatedFields)) as avg_cumplimiento,
                SUM(CASE WHEN secop_estado_contrato ILIKE 'CERRADO' OR secop_estado_contrato ILIKE 'TERMINADO' THEN 1 ELSE 0 END) as sec_cerrado,
                SUM(CASE WHEN secop_estado_contrato ILIKE 'EN EJECUCION' THEN 1 ELSE 0 END) as sec_ejecucion,
                SUM(CASE WHEN secop_estado_contrato IS NULL OR secop_estado_contrato = '' THEN 1 ELSE 0 END) as sec_vacio
            ")
            ->first();

        $stats = [
            'total' => $summary->total ?? 0,
            'ok_contratos' => $summary->count_ok ?? 0,
            'pend_contratos' => $summary->count_pend ?? 0,
            'avg_cumplimiento' => $summary->avg_cumplimiento ?? 0,
            'sec_cerrado' => $summary->sec_cerrado ?? 0,
            'sec_ejecucion' => $summary->sec_ejecucion ?? 0,
            'sec_vacio' => $summary->sec_vacio ?? 0,
        ];

        // Paginar resultados directamente en la base de datos
        $perPage = 20;
        $paginated = $contratosQuery->paginate($perPage)->appends($request->query());

        // 3. PROCESAMIENTO DINÁMICO DE ATRIBUTOS (FLAT-TO-MODEL)
        foreach ($paginated as $c) {
            $this->hydrateContratoData($c);
        }

        $supervisores = Supervisor::all();
        $modalidades = Modalidad::all();
        $contratistas = Contratista::all();

        // Obtener estados para el filtro agrupado
        $bloques = \App\Models\BloqueWorkflow::ordenados()->get();
        $todosLosEstados = \App\Models\EstadoWorkflow::where('es_activo', true)->with('bloque')->get()->groupBy('bloque.codigo');

        if ($request->ajax()) {
            return response()->json([
                'table' => view('seguimiento.partials.table', [
                    'contratos' => $paginated,
                    'maxMesSeguimiento' => $maxMesSeguimiento,
                ])->render(),
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
            'bloques' => $bloques,
            'maxMesSeguimiento' => $maxMesSeguimiento,
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

            $contratosQuery = $this->getFilteredContratos($request);
            $maxMesSeguimiento = $this->getMaxMesSeguimiento($contratosQuery);
            $camposPersonalizados = $this->getCamposPersonalizados();
            $headers = $this->buildSeguimientoExportHeaders($maxMesSeguimiento, $camposPersonalizados);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('SEGUIMIENTO SECOP');
            $sheet->fromArray([$headers], null, 'A1');
            $sheet->freezePane('A2');
            $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

            $rowNumber = 2;
            foreach ($contratosQuery->cursor() as $c) {
                $this->hydrateContratoData($c);
                if (! $c->relationLoaded('seguimientoCamposValores')) {
                    $c->load('seguimientoCamposValores.campo');
                }

                $sheet->fromArray([$this->buildSeguimientoExportRow($c, $maxMesSeguimiento, $camposPersonalizados)], null, 'A' . $rowNumber);
                $rowNumber++;
            }

            $filename = 'reporte_' . date('Ymd_His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
            (new Xlsx($spreadsheet))->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Access-Control-Expose-Headers' => 'Content-Disposition'
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            Contrato::logException($e, 'contratos', $request->all());

            return response()->json([
                'success' => false,
                'message' => 'Error interno al generar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

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
            // CASO B: Campos adicionales dinámicos del Seguimiento SECOP
            // CASO C: Atributos Maestros del Contrato
            elseif (in_array($field, ['secop_estado_contrato', 'aprobado_y_pagado', 'modificaciones_y_cierre', 'link_secop', 'tipo_contratista'])) {
                $contrato = Contrato::findOrFail($validated['id']);
                $contrato->$field = $status;
                $contrato->save();
            }
            // CASO D: Requisitos de Checklist (Normalizados)
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
                // CASO B: Campos adicionales dinámicos
                // CASO C: Atributos Maestros
                elseif (in_array($field, ['secop_estado_contrato', 'aprobado_y_pagado', 'modificaciones_y_cierre', 'link_secop', 'tipo_contratista'])) {
                    $contrato = Contrato::findOrFail($id);
                    $contrato->$field = $status;
                    $contrato->save();
                }
                // CASO D: Requisitos
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
     * Agrega el siguiente período mensual para un contrato.
     */
    public function agregarPeriodo(int $contratoId)
    {
        try {
            /** @var Usuario $user */
            $user = Auth::user();
            if (! $user || ! $user->puedeEditarSeguimiento()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para agregar períodos.',
                ], 403);
            }

            $contrato = Contrato::findOrFail($contratoId);
            $nextMes = null;

            DB::transaction(function () use ($contrato, &$nextMes) {
                $maxMes = (int) SeguimientoMensual::where('contrato_id', $contrato->id)->max('mes');
                $nextMes = $maxMes > 0 ? $maxMes + 1 : 1;
                $anio = (int) (SeguimientoMensual::where('contrato_id', $contrato->id)->max('anio') ?: now()->year);

                foreach (['REP', 'SECOP', 'SIA'] as $fuente) {
                    SeguimientoMensual::updateOrCreate(
                        [
                            'contrato_id' => $contrato->id,
                            'mes' => $nextMes,
                            'anio' => $anio,
                            'fuente' => $fuente,
                        ],
                        [
                            'estado' => null,
                        ]
                    );
                }
            });

            $contrato->load(['seguimientoMensual', 'seguimientoRequisitos', 'seguimientoCamposValores.campo', 'contratista', 'supervisor']);
            $this->hydrateContratoData($contrato);

            Contrato::logManualAudit(null, 'UPDATE', "Se agregó el período mensual siguiente al contrato #{$contrato->numero_contrato}", 'contratos', [
                'next_mes' => $nextMes,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Período {$nextMes} agregado correctamente.",
                'next_mes' => $nextMes,
                'numero_contrato' => $contrato->numero_contrato,
                'meses_extra_count' => $contrato->meses_extra_count ?? 0,
                'meses_extra' => $contrato->meses_extra ?? [],
            ]);
        } catch (\Throwable $e) {
            Contrato::logException($e, 'contratos', ['operacion' => 'agregarPeriodo', 'contrato_id' => $contratoId]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo agregar el período: ' . $e->getMessage(),
            ], 500);
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
    public function update(Request $request, int $id)
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
                'tipo_contratista' => 'nullable|string',
                'abogado_responsable' => 'nullable|string',
                'contador_responsable' => 'nullable|string',
                'saldo' => 'nullable|numeric',
                'observacion_1_razon' => 'nullable|string',
                'observacion_2_accion' => 'nullable|string',
                'razon_no_liquidacion' => 'nullable|string',
                'aprobado_y_pagado' => 'nullable|string',
                'modificaciones_y_cierre' => 'nullable|string',
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
    public function destroy(int $id)
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



    /**
     * @param Contrato|object $c
     */
    private function hydrateContratoData(object $c)
    {
        $maxMesSeguimiento = max(12, (int) ($c->seguimientoMensual->max('mes') ?? 12));
        $totalEvaluatedFields = ($maxMesSeguimiento * 3) + 18;

        $mesesExtra = $c->seguimientoMensual
            ->filter(fn ($sm) => (int) $sm->mes > 12)
            ->groupBy('mes')
            ->map(function ($grupo, $mes) {
                return [
                    'mes' => (int) $mes,
                    'items' => $grupo->map(function ($sm) {
                        return [
                            'fuente' => strtoupper((string) $sm->fuente),
                            'estado' => $sm->estado ?: 'VACÍO',
                        ];
                    })->values()->all(),
                ];
            })
            ->sortBy('mes')
            ->values()
            ->all();

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
        elseif (($ok + $na) >= $totalEvaluatedFields) $c->global_status = 'COMPLETO';
        elseif (($ok + $na) > 0) $c->global_status = 'EN PROGRESO';
        else $c->global_status = 'VACÍO';

        $c->perc_cumplimiento = min(100, $totalEvaluatedFields > 0 ? (($ok + $na) / $totalEvaluatedFields) * 100 : 0);
        $c->meses_extra = $mesesExtra;
        $c->meses_extra_count = count($mesesExtra);


    }

    private function getCamposPersonalizados()
    {
        return SeguimientoCampo::where('es_activo', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    private function buildSeguimientoExportHeaders(int $maxMesSeguimiento, $camposPersonalizados = []): array
    {
        $headers = [
            'GESTION',
            'SCORE',
            'TIPO',
            'IDENTIFICADOR',
            'NOMBRE COMPLETO',
            'TIPO ENTIDAD',
            'SUPERVISOR',
            'OBJETO CONTRACTUAL',
            'VALOR TOTAL',
            'SECOP',
            'PLANTA',
            'CONCEPTO',
            'F. CDP',
            'E. PREVIOS',
            'SOPORTES',
            'IDONEIDAD',
            'CONFID.',
            'CLAUS.',
            'ACTA I.',
            'DELEG.',
            'ARL',
            'RP',
            'STATUS S.',
            'PAGADO',
            'CIERRE',
        ];

        for ($i = 1; $i <= $maxMesSeguimiento; $i++) {
            $headers[] = "R{$i}";
            $headers[] = "S{$i}";
            $headers[] = "I{$i}";
        }

        $headers = array_merge($headers, [
            'EVAL.',
            'ACTA C.',
            'REQ. LIQ.',
            'REPOS.',
            'LIQ. S.',
            'LIQ. I.',
            'SALDO RT.',
            'OBS. RAZON',
            'OBS. ACCION',
            'NO LIQ.',
            'ABOGADO',
            'CONTADOR',
        ]);

        foreach ($camposPersonalizados as $campo) {
            $headers[] = $campo->etiqueta ?: $campo->clave;
        }

        return $headers;
    }

    private function buildSeguimientoExportRow(object $c, int $maxMesSeguimiento, $camposPersonalizados = []): array
    {
        $values = [];
        $campoValores = $c->relationLoaded('seguimientoCamposValores')
            ? $c->seguimientoCamposValores->keyBy('seguimiento_campo_id')
            : collect();

        $values[] = '';
        $values[] = $this->excelValue($c->perc_cumplimiento);
        $values[] = $this->excelValue($c->numero_proceso);
        $values[] = $this->excelValue($c->numero_contrato);
        $values[] = $this->excelValue(optional($c->contratista)->nombre_completo);
        $values[] = $this->excelValue($c->tipo_contratista);
        $values[] = $this->excelValue(optional($c->supervisor)->nombre_completo);
        $values[] = $this->excelValue($c->objeto);
        $values[] = $this->excelValue($c->monto_total);
        $values[] = $this->excelValue($c->link_secop);
        $values[] = $this->excelValue($c->planta_status);
        $values[] = $this->excelValue($c->concepto_status);
        $values[] = $this->excelValue($c->cdp_status);

        foreach ([
            'estudios_previos_status',
            'soportes_status',
            'idoneidad_status',
            'acuerdo_confidencialidad_status',
            'clausulado_status',
            'acta_inicio_status',
            'delegacion_status',
            'arl_status',
            'rpc_status',
        ] as $field) {
            $values[] = $this->excelValue($c->$field);
        }

        $values[] = $this->excelValue($c->secop_estado_contrato);
        $values[] = $this->excelValue($c->aprobado_y_pagado);
        $values[] = $this->excelValue($c->modificaciones_y_cierre);

        for ($i = 1; $i <= $maxMesSeguimiento; $i++) {
            $values[] = $this->excelValue($c->{"cta{$i}_rep_status"} ?? '');
            $values[] = $this->excelValue($c->{"cta{$i}_secop_status"} ?? '');
            $values[] = $this->excelValue($c->{"cta{$i}_sia_status"} ?? '');
        }

        $values[] = $this->excelValue($c->evaluacion_proveedor_status);
        $values[] = $this->excelValue($c->acta_cierre_expediente_status);
        $values[] = $this->excelValue($c->requiere_acta_liq_status);
        $values[] = $this->excelValue($c->acta_liq_repositorio_status);
        $values[] = $this->excelValue($c->acta_liq_secop_status);
        $values[] = $this->excelValue($c->acta_liq_sia_status);

        $values[] = $this->excelValue($c->saldo);
        $values[] = $this->excelValue($c->observacion_1_razon ?? '');
        $values[] = $this->excelValue($c->observacion_2_accion ?? '');
        $values[] = $this->excelValue($c->razon_no_liquidacion ?? '');
        $values[] = $this->excelValue($c->abogado_responsable ?? '');
        $values[] = $this->excelValue($c->contador_responsable ?? '');

        foreach ($camposPersonalizados as $campo) {
            $valor = $campoValores->get($campo->id);
            $values[] = $this->excelValue($valor?->valor_mostrado ?? '');
        }

        return $values;
    }

    private function excelValue($value): string|float|int
    {
        if (is_null($value)) {
            return '';
        }

        if (is_string($value)) {
            $clean = trim($value);

            if ($clean === '') {
                return '';
            }

            if (! mb_check_encoding($clean, 'UTF-8')) {
                $converted = @mb_convert_encoding($clean, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                if ($converted !== false) {
                    $clean = $converted;
                }
            }

            return $clean;
        }

        return $value;
    }

    private function upsertSeguimientoCampoValor(int $campoId, int $contratoId, ?string $valor): void
    {
        $campo = SeguimientoCampo::find($campoId);

        if (! $campo) {
            throw new \RuntimeException("El campo dinámico #{$campoId} no existe.");
        }

        $valor = is_null($valor) ? null : trim((string) $valor);

        $data = [
            'valor_texto' => null,
            'valor_decimal' => null,
            'valor_fecha' => null,
            'valor_json' => null,
        ];

        if ($valor !== null && $valor !== '') {
            if ($campo->tipo === 'number') {
                $data['valor_decimal'] = is_numeric($valor) ? $valor : null;
            } elseif ($campo->tipo === 'date') {
                $data['valor_fecha'] = $valor;
            } elseif ($campo->tipo === 'textarea') {
                $data['valor_texto'] = $valor;
            } else {
                $data['valor_texto'] = $valor;
            }
        }

        SeguimientoCampoValor::updateOrCreate(
            [
                'seguimiento_campo_id' => $campoId,
                'contrato_id' => $contratoId,
            ],
            $data
        );
    }
}

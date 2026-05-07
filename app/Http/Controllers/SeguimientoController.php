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
use App\Models\EstadoWorkflow;
use App\Models\BloqueWorkflow;
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
        $query = Contrato::with(['contratista', 'supervisor', 'modalidad', 'planta', 'concepto', 'cuentaActual', 'ultimaCuentaFinalizada']);

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

        // Calculamos el universo de cumplimiento sobre la query filtrada (subquery atomizada para Postgres)
        $totalEvaluatedFields = 54; // 12 meses * 3 fuentes + 18 reqs (3 base + 9 checklist + 6 cierre)
        
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

            // Usando cursor() resolvemos el problema de la sobrecarga de memoria
            // y permitimos que la DB nos entregue los registros 1 a 1 de forma óptima
            $contratos = $this->getFilteredContratos($request)->cursor();
            
            $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
            $writer = SimpleExcelWriter::create($tempFile);

            foreach ($contratos as $c) {
                // Generar los agregados temporales por fila
                $this->hydrateContratoData($c);
                
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

            $filename = 'reporte_' . date('Ymd_His') . '.xlsx';
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Access-Control-Expose-Headers' => 'Content-Disposition'
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Contrato::logException($e, 'contratos', $request->all());

            return response()->json([
                'success' => false,
                'message' => 'Error interno al generar el archivo: ' . $e->getMessage()
            ], 500);
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
     * Inicia automáticamente la siguiente cuenta de cobro para un contrato.
     * Crea un nuevo registro en cuentas_cobro heredando datos y posicionándolo al inicio.
     */
    public function crearSiguienteCuenta(Request $request)
    {
        $request->validate([
            'contrato_id' => 'required|exists:contratos,id',
        ]);

        try {
            DB::beginTransaction();

            // Cargamos el contrato con sus relaciones para validar el estado actual
            $contrato = Contrato::with(['cuentaActual', 'ultimaCuentaFinalizada'])->findOrFail($request->contrato_id);

            // 1. Validar que no haya una cuenta activa (para evitar duplicidad de procesos)
            if ($contrato->cuentaActual) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Ya existe un trámite en curso (#'.$contrato->cuentaActual->numero_cuenta.') para este contrato.'
                ], 422);
            }

            // 2. Determinar el siguiente número de cuenta
            $siguienteNumero = 1;
            $ultimaCta = $contrato->ultimaCuentaFinalizada;
            if ($ultimaCta) {
                $siguienteNumero = (int)$ultimaCta->numero_cuenta + 1;
            }

            // 3. Validar si ya alcanzó el tope de pagos configurado (si existe)
            if ($ultimaCta && $ultimaCta->numero_pagos_totales > 0 && $siguienteNumero > $ultimaCta->numero_pagos_totales) {
                return response()->json([
                    'success' => false,
                    'message' => 'El contrato ya ha completado el total de pagos configurados ('.$ultimaCta->numero_pagos_totales.').'
                ], 422);
            }

            // 4. Obtener el estado inicial del workflow por configuración (es_inicial = true)
            $estadoInicial = EstadoWorkflow::where('es_inicial', true)->where('es_activo', true)->first();
            
            // Fallback si no hay marca de inicial: primer estado del primer bloque
            if (!$estadoInicial) {
                $primerBloque = BloqueWorkflow::orderBy('orden', 'asc')->first();
                if ($primerBloque) {
                    $estadoInicial = EstadoWorkflow::where('bloque_id', $primerBloque->id)
                        ->where('es_activo', true)
                        ->first();
                }
            }

            if (!$estadoInicial) {
                throw new \Exception("No se ha configurado un estado inicial válido para el workflow.");
            }

            // 5. Crear el nuevo registro de Cuenta de Cobro heredando datos clave
            $nuevaCuenta = CuentaCobro::create([
                'contrato_id'               => $contrato->id,
                'numero_cuenta'             => $siguienteNumero,
                'valor_cobro'               => $ultimaCta->valor_cobro ?? 0,
                'numero_pagos_totales'      => $ultimaCta->numero_pagos_totales ?? 0,
                'numero_facturas_radicadas' => $ultimaCta ? ($ultimaCta->numero_facturas_radicadas + 1) : 1,
                'bloque_actual_id'          => $estadoInicial->bloque_id,
                'estado_actual_id'          => $estadoInicial->id,
                'finalizada'                => false,
                'fecha_radicacion'          => now(),
                'responsable_actual_id'     => Auth::id(), // El que inicia el trámite
            ]);

            // 6. Registrar en el historial para trazabilidad
            HistorialWorkflow::create([
                'cuenta_cobro_id'   => $nuevaCuenta->id,
                'estado_origen_id'  => null, // Indica creación
                'estado_destino_id' => $estadoInicial->id,
                'usuario_accion_id' => Auth::id(),
                'fecha_transicion'  => now(),
                'comentario'        => "Trámite iniciado automáticamente desde el dashboard de seguimiento para la cuenta #{$siguienteNumero}.",
                'tipo_accion'       => 'CREACION_AUTOMATICA'
            ]);

            // 7. Auditoría manual
            Contrato::logManualAudit($contrato->id, 'CREATE', "Nueva Cuenta #{$siguienteNumero} creada desde seguimiento", 'cuentas_cobro');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Trámite para la cuenta #{$siguienteNumero} iniciado con éxito.",
                'id' => $nuevaCuenta->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en crearSiguienteCuenta: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'No se pudo crear la cuenta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @param Contrato|object $c
     */
    private function hydrateContratoData(object $c)
    {
        $totalEvaluatedFields = 54;

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

        // Lógica para "Trámite Siguiente Cuenta"
        $c->puede_iniciar_siguiente = false;
        $c->siguiente_numero_cuenta = 1;
        $c->es_proceso_completado = false;

        if (!$c->cuentaActual) {
            // Si no hay cuenta activa, miramos la última finalizada
            if ($c->ultimaCuentaFinalizada) {
                $totalPagos = (int)($c->ultimaCuentaFinalizada->numero_pagos_totales ?? 0);
                $siguiente = (int)$c->ultimaCuentaFinalizada->numero_cuenta + 1;
                
                if ($totalPagos > 0 && $siguiente > $totalPagos) {
                    $c->es_proceso_completado = true;
                } else {
                    $c->puede_iniciar_siguiente = true;
                    $c->siguiente_numero_cuenta = $siguiente;
                }
            } else {
                // Si nunca ha tenido cuentas, puede iniciar la #1
                $c->puede_iniciar_siguiente = true;
                $c->siguiente_numero_cuenta = 1;
            }
        }
    }
}

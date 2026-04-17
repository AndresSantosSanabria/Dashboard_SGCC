<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\BloqueWorkflow;
use App\Models\Contrato;
use App\Models\CuentaCobro;
use App\Models\EstadoBloqueCuenta;
use App\Models\EstadoWorkflow;
use App\Models\HistorialWorkflow;
use App\Models\Supervisor;
use App\Models\TransicionPermitida;
use App\Models\Usuario;
use App\Services\BusinessTimeService;
use App\Services\TimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowController extends Controller
{
    protected $timeService;
    protected $businessTime;

    public function __construct(TimeTrackingService $timeService, BusinessTimeService $businessTime)
    {
        $this->timeService   = $timeService;
        $this->businessTime  = $businessTime;
    }

    /**
     * MOTOR KANBAN - SGCC
     * 
     * Este es el controlador más dinámico del sistema. Encargado de renderizar 
     * el tablero de control (Workflow) basándose en los permisos granulares 
     * de cada usuario.
     */
    public function index(Request $request)
    {
        // AUDITORÍA: Trazabilidad de accesos al tablero de operaciones.
        Contrato::logManualAudit(null, 'READ', 'El usuario consultó el tablero de workflow', 'workflow');

        /** @var Usuario $user */
        $user = Auth::user();

        // 1. CONTROL DE ACCESO 
        // El sistema es multi-perfil. Verificamos si el usuario tiene rol para entrar aquí.
        if (! $user->puedeAccederWorkflow()) {
            abort(403, 'No tienes permiso para acceder al Workflow');
        }

        // 2. CONSTRUCCIÓN DEL DATASET OPERATIVO 
        // Cargamos todas las relaciones necesarias en una sola query 
        // para evitar el problema de N+1, ya que cada tarjeta del Kanban requiere mucha info.
        $query = CuentaCobro::with([
            'contrato.contratista',
            'bloqueActual',
            'estadoActual',
            'estadosBloques',
            'historialWorkflow.usuarioAccion',
            'historialWorkflow.estadoOrigen',
            'historialWorkflow.estadoDestino',
            'contrato.supervisor'
        ])->where('finalizada', false);

        // 3. SEGURIDAD DE FILTRADO 
        // Los usuarios operativos solo ven lo que tienen asignado. Los coordinadores ven todo.
        if ($user->verSoloAsignados()) {
            $query->where(function($q) use ($user) {
                // a. Es el responsable directo del trámite actual
                $q->where('responsable_actual_id', $user->id)
                // b. O es parte del equipo de gestión del contrato (Abogado, Contador, OPS)
                ->orWhereHas('contrato', function($cq) use ($user) {
                    $cq->where('abogado_user_id', $user->id)
                       ->orWhere('contador_user_id', $user->id)
                       ->orWhere('ops_user_id', $user->id);
                    
                    // c. O es el Supervisor del contrato (con el mismo nombre)
                    $nombreCompleto = $user->nombre_completo;
                    if ($nombreCompleto) {
                        $cq->orWhereHas('supervisor', function($sq) use ($nombreCompleto) {
                            $sq->where(DB::raw("TRIM(CONCAT(nombres, ' ', apellidos))"), 'ilike', '%' . $nombreCompleto . '%');
                        });
                    }
                });
            });
        }

        // Restricción por Bloques: Algunos usuarios solo ven bloques específicos.
        $bloquesPermitidos = $user->bloquesPermitidos();
        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $query->whereIn('bloque_actual_id', function ($subQuery) use ($bloquesPermitidos) {
                $subQuery->select('id')->from('bloques_workflow')->whereIn('codigo', $bloquesPermitidos);
            });
        }

        // 4. FILTROS DINÁMICOS DE BÚSQUEDA
        if ($request->filled('supervisor_id')) {
            $query->whereHas('contrato', function ($q) use ($request) {
                $q->where('supervisor_id', $request->supervisor_id);
            });
        }

        if ($request->filled('contratista')) {
            $query->whereHas('contrato.contratista', function ($q) use ($request) {
                $q->where('razon_social', 'like', '%' . $request->contratista . '%');
            });
        }

        if ($request->filled('numero_contrato')) {
            $query->whereHas('contrato', function ($q) use ($request) {
                $q->where('numero_contrato', 'like', '%' . $request->numero_contrato . '%');
            });
        }

        if ($request->filled('estado_nombre')) {
            $query->whereHas('estadoActual', function ($q) use ($request) {
                $q->where('nombre', $request->estado_nombre);
            });
        }

        if ($request->filled('numero_cuenta')) {
            $query->where('numero_cuenta', (int) $request->numero_cuenta);
        }

        if ($request->filled('responsable_id')) {
            $query->where('cuentas_cobro.responsable_actual_id', $request->responsable_id);
        }

        $cuentas = $query->get();

        // 5. OPTIMIZACIÓN: Se removió la regeneración automática de transiciones
        // para mejorar la velocidad de carga. Las transiciones se regeneran 
        // ahora únicamente desde el panel administrativo al modificar la configuración.

        // 6. CONSTRUCCIÓN DE LA MATRIZ DEL WORKFLOW
        // El workflow es dinámico. Consultamos los bloques configurados en BD 
        // y organizamos las cuentas por "Bloque -> Estado".
        $bloquesQuery = BloqueWorkflow::with(['estados' => function ($q) {
            $q->where('es_activo', true)->orderBy('id', 'asc');
        }])->orderBy('orden', 'asc');

        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $bloquesQuery->whereIn('codigo', $bloquesPermitidos);
        }

        // 6.b Filtro dinámico: Ver solo bloques donde el usuario tiene cuentas asignadas
        if ($user->verSoloBloquesConAsignacion()) {
            $bloquesConCuentasIds = $cuentas->pluck('bloque_actual_id')->unique()->toArray();
            $bloquesQuery->whereIn('id', $bloquesConCuentasIds);
        }

        $bloques = $bloquesQuery->get();
        
        // Si no hay bloques resultantes y el filtro de asignación estaba prendido, 
        // podríamos mostrar un mensaje vacío o simplemente dejar el workflow vacío.

        // Estructuramos el JSON/Array para que el frontend (Blade/JS) lo procese como columnas.
        $workflow = [];
        foreach ($bloques as $bloque) {
            $columnas = [];
            foreach ($bloque->estados as $estado) {
                // No mostrar visualmente la columna de devoluciones en el bloque Finalizada (B6)
                if ($bloque->id == 6 && $estado->tipo === 'DEVUELTO') continue;
                    $columnas[$estado->id] = [
                        'nombre' => $estado->nombre,
                        'tipo' => $estado->tipo,
                        'color_hex' => $estado->color_hex,
                        'cuentas' => [],
                    ];
            }

            $workflow[$bloque->id] = [
                'nombre' => $bloque->nombre,
                'color' => $this->getColorPorBloque($bloque->codigo),
                'columnas' => $columnas,
            ];
        }

        // Repartimos las cuentas en sus respectivas columnas de la matriz.
        foreach ($cuentas as $cuenta) {
            if (isset($workflow[$cuenta->bloque_actual_id]['columnas'][$cuenta->estado_actual_id])) {
                $workflow[$cuenta->bloque_actual_id]['columnas'][$cuenta->estado_actual_id]['cuentas'][] = $cuenta;
            }
        }

        $canEdit = $user->puedeEditarWorkflow();

        $umbralCritico = (int) \App\Models\Configuracion::getValor('ALERTA_ESTANCAMIENTO_MINUTOS', 20);
        $umbralInformativo = (int) \App\Models\Configuracion::getValor('ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS', 10);

        // RESPUESTA AJAX: Para el refresco parcial del tablero sin recargar.
        if ($request->ajax()) {
            $businessTime = $this->businessTime;
            return view('workflow.componentes.board', compact('workflow', 'canEdit', 'businessTime', 'umbralCritico', 'umbralInformativo'));
        }

        $supervisores = Supervisor::orderBy('nombres')->get();
        $estados = EstadoWorkflow::where('es_activo', true)->select('nombre')->distinct()->get();
        $todosLosEstados = EstadoWorkflow::where('es_activo', true)->with('bloque')->get()->groupBy('bloque.codigo');
        $responsables = Usuario::where('es_activo', true)
            ->orderBy('primer_nombre')
            ->get()
            ->filter(fn($u) => $u->puedeSerResponsableSap() || $u->puedeSerResponsableFac());

        $businessTime = $this->businessTime;
        return view('workflow.workflow', compact('workflow', 'supervisores', 'estados', 'responsables', 'canEdit', 'bloques', 'todosLosEstados', 'businessTime', 'umbralCritico', 'umbralInformativo'));
    }

    /**
     * Identidad Visual: Mapeo de colores por bloque para reconocimiento rápido.
     */
    private function getColorPorBloque($codigo)
    {
        return match ($codigo) {
            'REV1' => 'morado',    // Revisión inicial
            'SAP' => 'indigo',     // Proceso en ERP
            'FAC' => 'verde',      // Facturación
            'FIR' => 'naranja',    // Firmos y autorizaciones
            'HAC' => 'rosa',       // Tesorería / Hacienda
            'FIN' => 'cian',       // Finalizado (Archivo)
            default => 'morado',
        };
    }

    private function determinarBloqueVisual($cuenta)
    {
        $id = $cuenta->bloque_actual_id;

        if ($id >= 1 && $id <= 6) {
            return "bloque{$id}";
        }

        return 'bloque1'; // Default fallback
    }

    private function mapearEstadoInterno($tipoEstado)
    {
        return match ($tipoEstado) {
            'INICIAL' => 'revision',
            'EN_PROCESO' => 'proceso',
            'APROBADO', 'FINAL' => 'aprobadas',
            'DEVUELTO' => 'rechazadas',
            default => 'revision',
        };
    }

    /**
     * Get available states for a cuenta based on current state
     */
    public function getEstadosDisponibles($cuentaId)
    {
        $cuenta = CuentaCobro::with(['estadoActual', 'bloqueActual'])->findOrFail($cuentaId);

        // Get allowed transitions from current state
        $query = TransicionPermitida::where('estado_origen_id', $cuenta->estado_actual_id)
            ->where('es_activa', true)
            ->with(['estadoDestino:id,nombre,codigo,tipo,color_hex,bloque_id', 'estadoDestino.bloque:id,nombre']);

        // ── REGLA VISUAL ESPECIAL: Sin Tramite → Solo En Revision ────────────────
        if ($cuenta->estadoActual?->codigo === 'REV1_SIN') {
            $query->whereHas('estadoDestino', function($q) {
                $q->where('codigo', 'REV1_REV');
            });
        }
        // ──────────────────────────────────────────────────────────────────────

        $estadosDisponibles = $query->get()
            ->map(function ($transicion) {
                return [
                    'id' => $transicion->estadoDestino->id,
                    'nombre' => $transicion->estadoDestino->nombre,
                    'tipo' => $transicion->estadoDestino->tipo,
                    'color' => $transicion->estadoDestino->color_hex ?? $this->getColorPorTipo($transicion->estadoDestino->tipo),
                    'bloque_id' => $transicion->estadoDestino->bloque_id,
                    'bloque_nombre' => $transicion->estadoDestino->bloque->nombre ?? '',
                    'requiere_comentario' => $transicion->requiere_comentario,
                ];
            });

        return response()->json([
            'success' => true,
            'estado_actual' => [
                'id' => $cuenta->estadoActual->id,
                'nombre' => $cuenta->estadoActual->nombre,
                'tipo' => $cuenta->estadoActual->tipo,
            ],
            'estados_disponibles' => $estadosDisponibles,
        ]);
    }

    /**
     * Change state of a cuenta
     */
    /**
     * GESTIÓN DE TRANSICIONES (State Machine Engine)
     * 
     * Este método es el núcleo de la lógica de negocio. Se encarga de mover 
     * una cuenta de un estado a otro, validando permisos y disparando 
     * automatismos si el flujo lo permite.
     */
    public function cambiarEstado(Request $request, $cuentaId)
    {
        \Log::info("WorkflowController@cambiarEstado - RECIBIDA PETICION - Cuenta: $cuentaId. Data: " . json_encode($request->all()));
        $request->validate([
            'estado_destino_id' => 'required|exists:estados_workflow,id',
            'comentario'        => 'nullable|string|max:500',
            'ss_ultima_cuenta'  => 'nullable|string|in:Enero,Febrero,Marzo,Abril,Mayo,Junio,Julio,Agosto,Septiembre,Octubre,Noviembre,Diciembre',
        ]);

        $cuenta = CuentaCobro::with(['estadoActual', 'bloqueActual'])->findOrFail($cuentaId);

        // 1. VALIDACIÓN DE TRANSICIÓN: 
        // No permitimos saltos "al azar"; solo los definidos en la tabla 'transiciones_permitidas'.
        $transicion = TransicionPermitida::where('estado_origen_id', $cuenta->estado_actual_id)
            ->where('estado_destino_id', $request->estado_destino_id)
            ->where('es_activa', true)
            ->first();

        if (! $transicion) {
            $this->logWorkflowAudit('WORKFLOW_TRANSITION_REJECTED', $cuenta, [
                'motivo' => 'Transición no permitida por el motor de reglas',
                'estado_origen_id' => $cuenta->estado_actual_id,
                'estado_origen_nombre' => $cuenta->estadoActual?->nombre,
                'estado_destino_id' => $request->estado_destino_id,
            ]);
            return response()->json(['success' => false, 'message' => 'Transición no permitida'], 403);
        }

        // ── REGLA ESPECIAL: Sin Tramite → En Revision ──────────────────────────
        // Si el estado origen es REV1_SIN, SOLO se permite ir a REV1_REV.
        // Además SIEMPRE se exige actualizar el mes de SS (ss_ultima_cuenta).
        $estadoOrigenCodigo = $cuenta->estadoActual?->codigo;
        $estadoDestinoCodigo = EstadoWorkflow::find($request->estado_destino_id)?->codigo;

        if ($estadoOrigenCodigo === 'REV1_SIN') {
            // Bloquear si intenta ir a cualquier estado que no sea REV1_REV
            if ($estadoDestinoCodigo !== 'REV1_REV') {
                return response()->json([
                    'success' => false,
                    'message' => 'Desde "Sin Tramite" solo se puede pasar a "En Revisión".',
                ], 422);
            }

            // Si ya viene con el mes informado, guardarlo y continuar normal
            if ($request->filled('ss_ultima_cuenta')) {
                $mes = $request->ss_ultima_cuenta;
                \Log::info("WorkflowController@cambiarEstado - Recibido mes: $mes para cuenta {$cuenta->id}");
                $mesesValidos = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                                 'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                if (!in_array($mes, $mesesValidos)) {
                    \Log::warning("WorkflowController@cambiarEstado - Mes no válido: $mes");
                    return response()->json(['success' => false, 'message' => 'Mes de SS no válido.'], 422);
                }
                // Guardamos el mes ANTES de ejecutar la transición
                // Usamos DB directa para asegurar persistencia inmediata
                \DB::table('cuentas_cobro')->where('id', $cuenta->id)->update(['ss_ultima_cuenta' => $mes]);
                $cuenta->ss_ultima_cuenta = $mes; // Mantener en memoria para el resto del flujo
                \Log::info("WorkflowController@cambiarEstado - Guardado mes '$mes' vía DB::table para cuenta {$cuenta->id}");
            } else {
                // Pedirle al frontend que muestre el selector de mes
                return response()->json([
                    'success'            => true,
                    'requires_ss_cuenta' => true,
                    'cuenta_id'          => $cuentaId,
                    'estado_destino_id'  => $request->estado_destino_id,
                    'message'            => 'Debe indicar el mes de la última cuenta de SS finalizada.',
                ]);
            }
        }
        // ──────────────────────────────────────────────────────────────────────

        $estadoDestino = EstadoWorkflow::findOrFail($request->estado_destino_id);

        // 2. PUNTO DE DECISIÓN (Handoff):
        // Detectamos si el cambio de estado (o su siguiente paso automático) implica un cambio de bloque.
        
        // Verificamos si el estado destino tiene un "Auto-Chaining" (paso automático de bloque)
        $auto = TransicionPermitida::where('estado_origen_id', $estadoDestino->id)
            ->where('accion', 'PASAR_BLOQUE')
            ->where('es_activa', true)
            ->first();
            
        $estadoEfectivo = $auto ? EstadoWorkflow::findOrFail($auto->estado_destino_id) : $estadoDestino;
        
        $esCambioDeBloque = $cuenta->bloque_actual_id != $estadoEfectivo->bloque_id;
        $esBloqueFinal = $estadoEfectivo->bloque_id == 6;
        
        // Una devolución se identifica por el tipo de estado o por el orden del bloque
        $esDevolucion = ($estadoEfectivo->tipo === 'DEVUELTO') || $this->esDevolucionDeBloque($cuenta->bloque_actual_id, $estadoEfectivo->bloque_id);

        // REGLA: El modal aparece si cambia de bloque (y no es el final automático) O si es una devolución.
        if (($esCambioDeBloque && !$esBloqueFinal) || $esDevolucion) {
            
            // FILTRADO DE BLOQUES LÓGICO:
            $queryBloques = BloqueWorkflow::where('es_activo', true);
            $ordenActual = $cuenta->bloqueActual->orden;

            if ($esDevolucion) {
                // Si es devolución, solo permitimos bloques IGUALES o ANTERIORES al actual
                $queryBloques->where('orden', '<=', $ordenActual);
            } else {
                // Si es avance, permitimos bloques IGUALES o POSTERIORES (para saltos de libertad)
                $queryBloques->where('orden', '>=', $ordenActual);
            }

            return response()->json([
                'success' => true,
                'requires_responsible' => true,
                'cuenta_id' => $cuentaId,
                'estado_destino_id' => $request->estado_destino_id,
                'target_bloque_codigo' => $estadoEfectivo->bloque->codigo,
                'target_bloque_id' => $estadoEfectivo->bloque_id,
                'target_bloque_nombre' => $estadoEfectivo->bloque->nombre,
                'es_devolucion' => $esDevolucion,
                'bloques_disponibles' => $queryBloques->orderBy('orden')->get(['id', 'nombre', 'codigo']),
                'message' => $esDevolucion ? "Se requiere motivo y responsable para la devolución" : "Se requiere asignar un responsable para el bloque: {$estadoEfectivo->bloque->nombre}",
            ]);
        }

        // 3. EJECUCIÓN ATÓMICA:
        // Usamos transacciones para garantizar que la cuenta no quede en un estado inconsistente.
        DB::beginTransaction();
        try {
            $this->ejecutarTransicion($cuenta, $request->estado_destino_id, $request->comentario);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'nuevo_estado' => [
                    'nombre' => $cuenta->estadoActual->nombre,
                    'tipo' => $cuenta->estadoActual->tipo,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Contrato::logException($e, 'cuentas_cobro', ['operacion' => 'cambiarEstado', 'cuenta_id' => $cuentaId]);
            $this->logWorkflowAudit('WORKFLOW_ERROR', $cuenta, [
                'operacion' => 'cambiarEstado',
                'error' => $e->getMessage(),
                'estado_destino_id' => $request->estado_destino_id,
            ]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Assign responsible and complete state transition
     */
    public function assignResponsible(Request $request, $cuentaId)
    {
        $request->validate([
            'estado_destino_id' => 'required|exists:estados_workflow,id',
            'responsable_id' => 'required|exists:usuarios,id',
            'bloque_id' => 'nullable|exists:bloques_workflow,id',
            'comentario' => 'nullable|string|max:500',
        ]);

        $cuenta = CuentaCobro::findOrFail($cuentaId);
        \Log::info("WorkflowController@assignResponsible - Iniciando para cuenta {$cuenta->id}. Valor actual SS: " . ($cuenta->ss_ultima_cuenta ?? 'NULL'));
        $estadoOrigenId = $cuenta->estado_actual_id;
        $estadoDestinoId = $request->estado_destino_id;

        // Si se especificó un bloque manualmente, buscamos su estado inicial o de devolución
        if ($request->filled('bloque_id')) {
            $bloqueForzadoId = $request->bloque_id;
            $estadoOriginal = EstadoWorkflow::find($request->estado_destino_id);
            
            // Detectamos si es devolución: por bloque o porque el estado original ya era de tipo devuelto
            $esD = ($estadoOriginal?->tipo === 'DEVUELTO') || $this->esDevolucionDeBloque($cuenta->bloque_actual_id, $bloqueForzadoId);
            
            $queryEstado = EstadoWorkflow::where('bloque_id', $bloqueForzadoId)
                ->where('es_activo', true);

            if ($esD) {
                // Si es devolución, buscamos el estado tipo DEVUELTO del bloque seleccionado
                $estadoDestinoId = (clone $queryEstado)->where('tipo', 'DEVUELTO')->first()?->id 
                                   ?? (clone $queryEstado)->orderBy('id', 'asc')->first()?->id;
            } else {
                // Si es avance, buscamos el inicial
                $estadoDestinoId = (clone $queryEstado)->orderBy('id', 'asc')->first()?->id;
            }
        }

        // Get the actual destination state object
        $estadoDestino = EstadoWorkflow::findOrFail($estadoDestinoId);

        // Start transaction
        DB::beginTransaction();
        try {
            // determine the actual responsible for the process
            $responsableId = $request->responsable_id;

            // Execute the transition logic
            $this->ejecutarTransicion($cuenta, $estadoDestinoId, $request->comentario, false, $responsableId);

            DB::commit();

            // Determine which block to update based on the destination state
            $bloqueTarget = $estadoDestino->bloque;

            if ($bloqueTarget) {
                // Load the responsable user to get their full name
                $responsableUsuario = Usuario::findOrFail($request->responsable_id);
                $nombreResponsable = trim(
                    ($responsableUsuario->primer_nombre ?? '') . ' ' .
                    ($responsableUsuario->primer_apellido ?? '')
                );
                $comentarioAsignacion = "Fue asignado a: {$nombreResponsable} (Bloque: {$bloqueTarget->nombre})";

                // Update or create the block record with the assigned responsible
                EstadoBloqueCuenta::updateOrCreate(
                    ['cuenta_cobro_id' => $cuentaId, 'bloque_id' => $bloqueTarget->id],
                    ['responsable_id' => $request->responsable_id]
                );

                // SYNC: Update the main account's current responsible
                $cuenta->update(['responsable_actual_id' => $request->responsable_id]);

                // Registrar en el historial quién fue asignado al siguiente bloque
                HistorialWorkflow::create([
                    'cuenta_cobro_id' => $cuentaId,
                    'bloque_id'       => $bloqueTarget->id,
                    'estado_origen_id'  => $cuenta->estado_actual_id,
                    'estado_destino_id' => $cuenta->estado_actual_id,
                    'usuario_accion_id' => Auth::id() ?? 1,
                    'fecha_transicion'  => now(),
                    'tiempo_en_estado_anterior_minutos' => 0,
                    'comentarios' => $comentarioAsignacion,
                ]);

                Log::info("Responsible assigned for cuenta {$cuentaId}: User ID = {$request->responsable_id}, Block = {$bloqueTarget->codigo} (ID: {$bloqueTarget->id})");
            }

            DB::commit();

            // Refresh account to get newest state
            $cuenta->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado y responsable asignado correctamente',
                'nuevo_estado' => [
                    'nombre' => $cuenta->estadoActual->nombre,
                    'tipo' => $cuenta->estadoActual->tipo,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Contrato::logException($e, 'cuentas_cobro', ['operacion' => 'assignResponsible', 'cuenta_id' => $cuentaId]);
            $this->logWorkflowAudit('WORKFLOW_ERROR', $cuenta, [
                'operacion' => 'assignResponsible',
                'error' => $e->getMessage(),
                'responsable_id' => $request->responsable_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PIPELINE DE TRANSICIÓN:
     * 
     * Orquestador interno que maneja el historial, los tiempos de respuesta, 
     * detecta si es una devolución y gestiona el "Auto-Chaining" (estados automáticos).
     */
    private function ejecutarTransicion($cuenta, $estadoDestinoId, $comentario = null, $esAutomatica = false, $responsableIdForzado = null)
    {
        $estadoOrigenId = $cuenta->estado_actual_id;
        $estadoDestino = EstadoWorkflow::findOrFail($estadoDestinoId);

        // A. CRONÓMETRO DE ESTADO: Calculamos cuánto tiempo vivió en el estado anterior.
        // Solo contabilizamos si el estado de origen está marcado para ello (SLA Engine rules).
        $estadoOrigen = EstadoWorkflow::find($estadoOrigenId);
        $ultimoHistorial = HistorialWorkflow::where('cuenta_cobro_id', $cuenta->id)
            ->orderBy('fecha_transicion', 'desc')->first();

        $tiempoSegundos = 0;
        if ($estadoOrigen && ($estadoOrigen->contabiliza_tiempo ?? true)) {
            $businessTime = app(\App\Services\BusinessTimeService::class);
            
            // TIEMPO REAL: Sumamos logs cerrados + el tiempo que lleva el cronómetro abierto ahora
            $tiempoLogueado = \App\Models\TaskTimeLog::where('cuenta_cobro_id', $cuenta->id)
                ->where('estado_id', $estadoOrigenId)
                ->whereNotNull('end_time')
                ->sum('duracion_segundos');

            $volatil = $cuenta->ultimo_inicio_conteo
                ? $businessTime->getWorkingSecondsBetween($cuenta->ultimo_inicio_conteo, now())
                : 0;

            $tiempoSegundos = (int) ($tiempoLogueado + $volatil);
        }

        // C. DETECCIÓN DE DEVOLUCIONES:
        // Si el bloque nuevo es "anterior" al actual, restauramos automáticamente 
        // al responsable que lo trabajó antes. UX centrada en la eficiencia.
        $bloqueAnteriorId = $cuenta->bloque_actual_id;
        $esDevolucion = $this->esDevolucionDeBloque($bloqueAnteriorId, $estadoDestino->bloque_id);
        $responsableId = $responsableIdForzado ?? Auth::id() ?? 1;

        // D. LÓGICA DE ASIGNACIÓN AUTOMÁTICA (RECEPTOR B6):
        // Si el estado de destino pertenece al Bloque 6 (Finalizado), buscamos el receptor 
        // configurado con menos carga de trabajo actual para mantener un balance.
        if ($estadoDestino->bloque_id == 6) {
            $receptorIdeal = Usuario::getReceptorMenosCargadoBloque6();
            if ($receptorIdeal) {
                $responsableId = $receptorIdeal->id;
            }
        }

        if ($esDevolucion) {
            $responsableId = $responsableIdForzado ?? $this->obtenerResponsablePrevio($cuenta->id, $estadoDestino->bloque_id) ?? $responsableId;
            $this->marcarBloqueComoDevuelto($cuenta->id, $bloqueAnteriorId, $comentario);

            // Enriquecer el comentario si es una devolución
            $respUser = Usuario::find($responsableId);
            if ($respUser) {
                $nombreResp = trim(($respUser->primer_nombre ?? '') . ' ' . ($respUser->primer_apellido ?? ''));
                $comentario = "Devuelto a: {$nombreResp}" . ($comentario ? " | {$comentario}" : "");
            }
        }

        // B. REGISTRO DE HISTORIA (Audit Trail): Punto innegociable para auditorías externas.
        HistorialWorkflow::create([
            'cuenta_cobro_id' => $cuenta->id,
            'bloque_id' => $cuenta->bloque_actual_id,
            'estado_origen_id' => $estadoOrigenId,
            'estado_destino_id' => $estadoDestinoId,
            'usuario_accion_id' => Auth::id() ?? 1,
            'fecha_transicion' => now(),
            'tiempo_en_estado_anterior_minutos' => $tiempoSegundos,
            'comentarios' => $comentario,
        ]);

        // B2. REGISTRO EN AUDITORÍA GLOBAL: Toda transición queda trazada en el log centralizado.
        $this->logWorkflowAudit(
            $esDevolucion ? 'WORKFLOW_DEVOLUCION' : ($esAutomatica ? 'WORKFLOW_AUTO' : 'WORKFLOW_TRANSICION'),
            $cuenta,
            [
                'estado_origen_id'     => $estadoOrigenId,
                'estado_origen_nombre' => $estadoOrigen?->nombre ?? 'N/A',
                'estado_destino_id'    => $estadoDestinoId,
                'estado_destino_nombre'=> $estadoDestino->nombre,
                'bloque_origen_id'     => $bloqueAnteriorId,
                'bloque_destino_id'    => $estadoDestino->bloque_id,
                'es_devolucion'        => $esDevolucion,
                'es_automatica'        => $esAutomatica,
                'tiempo_previo_seg'    => $tiempoSegundos,
                'comentario'           => $comentario,
            ]
        );

        // D. CIERRE DE BLOQUE: Si avanzamos de fase, sellamos el progreso del bloque anterior.
        if ($bloqueAnteriorId != $estadoDestino->bloque_id && ! $esDevolucion) {
            EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)
                ->where('bloque_id', $bloqueAnteriorId)
                ->update(['bloque_completado' => true, 'fecha_completado_bloque' => now()]);
        }

        // E. ACTUALIZACIÓN DEL MODELO:
        $cuenta->estado_actual_id = $estadoDestinoId;
        if ($comentario && ! str_starts_with($comentario, 'Automatismo:')) {
            $cuenta->observaciones = $comentario;
        }

        // RESET del contador volátil: el Observer 'updating' leerá el valor
        // actual via getOriginal() ANTES de que el save() lo pise, y lo
        // acumulará en tiempo_total_proceso_segundos. El Observer 'updated'
        // después inicializará fecha_ultimo_cambio_estado = now().
        // NO tocar tiempo_total_proceso_segundos aquí: el Observer lo maneja.
        $cuenta->fecha_ultimo_cambio_estado = null;

        // Finalización: Si llega al estado de éxito del bloque 6, la cuenta sale del radar operativo.
        if ($estadoDestino->bloque_id == 6 && $estadoDestino->tipo == 'APROBADO') {
            $cuenta->finalizada = true;
            $cuenta->numero_facturas_radicadas++; // Incremento contable automático
        } else {
            $cuenta->finalizada = false;
        }

        // Gestión de tiempos por bloque para analítica avanzada
        $existeRegistro = EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)
            ->where('bloque_id', $estadoDestino->bloque_id)->first();

        $updateData = [
            'estado_actual_id' => $estadoDestinoId,
            'responsable_id' => $responsableId,
            'fecha_ultima_actualizacion' => now(),
            'bloque_completado' => (bool) $estadoDestino->es_final,
            'fecha_completado_bloque' => $estadoDestino->es_final ? now() : null,
        ];

        if ($estadoDestino->bloque_id != $cuenta->bloque_actual_id || ! $existeRegistro) {
            $updateData['fecha_ingreso_bloque'] = now();
            $cuenta->bloque_actual_id = $estadoDestino->bloque_id;
        }

        EstadoBloqueCuenta::updateOrCreate(
            ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $estadoDestino->bloque_id],
            $updateData
        );

        $cuenta->responsable_actual_id = $responsableId;
        $cuenta->save();

        // F. ASIGNACIÓN DE FACTURA: Lógica especial para Tesorería.
        if ($estadoDestino->codigo === 'HAC_OK') {
            $this->asignarNumeroFactura($cuenta);
        }

        // G. AUTO-CHAINING (Propagación):
        // Algunos estados son "puentes" que deben pasar automáticamente al siguiente (o al anterior) paso.
        $auto = TransicionPermitida::where('estado_origen_id', $estadoDestinoId)
            ->where('accion', 'PASAR_BLOQUE')
            ->where('es_activa', true)
            ->first();

        if ($auto && !$esAutomatica) {
            // Propagamos el comentario original si existe, para que el historial del landing sea útil.
            // La bandera $esAutomatica = true evita que estados que son a su vez "puentes" disparen 
            // saltos infinitos o en cadena (Efecto Cascada).
            $this->ejecutarTransicion($cuenta, $auto->estado_destino_id, $comentario ?? "Automatismo: {$auto->accion}", true, $responsableIdForzado);
        }
    }

    /**
     * Motor de numeración: Garantiza que cada radicación en Hacienda tenga un consecutivo único por contrato.
     */
    private function asignarNumeroFactura($cuenta)
    {
        if (empty($cuenta->ultima_factura_hacienda) || $cuenta->ultima_factura_hacienda === 'N/A') {
            $maxInvoice = CuentaCobro::where('contrato_id', $cuenta->contrato_id)
                ->whereNotNull('ultima_factura_hacienda')
                ->where('ultima_factura_hacienda', '!=', 'N/A')
                ->whereRaw("ultima_factura_hacienda ~ '^[0-9]+$'")
                ->max(DB::raw('CAST(ultima_factura_hacienda AS integer)'));

            $cuenta->update(['ultima_factura_hacienda' => ($maxInvoice ?? 0) + 1]);
        }
    }


    /**
     * Determina si la transición es una devolución a un bloque anterior
     */
    private function esDevolucionDeBloque($bloqueOrigenId, $bloqueDestinoId)
    {
        if ($bloqueOrigenId === $bloqueDestinoId) {
            return false; // Mismo bloque, no es devolución
        }

        $bloqueOrigen = BloqueWorkflow::find($bloqueOrigenId);
        $bloqueDestino = BloqueWorkflow::find($bloqueDestinoId);

        if (! $bloqueOrigen || ! $bloqueDestino) {
            return false;
        }

        // Es devolución si el orden del bloque destino es MENOR que el origen
        return $bloqueDestino->orden < $bloqueOrigen->orden;
    }

    /**
     * Obtiene el ID del responsable que trabajó previamente en un bloque
     */
    private function obtenerResponsablePrevio($cuentaId, $bloqueId)
    {
        $estadoBloqueAnterior = EstadoBloqueCuenta::where('cuenta_cobro_id', $cuentaId)
            ->where('bloque_id', $bloqueId)
            ->whereNotNull('responsable_id')
            ->orderBy('fecha_ultima_actualizacion', 'desc')
            ->first();

        if ($estadoBloqueAnterior && $estadoBloqueAnterior->responsable_id) {
            // Verificar que el usuario todavía existe y está activo
            $usuario = Usuario::where('id', $estadoBloqueAnterior->responsable_id)
                ->where('es_activo', true)
                ->first();

            if ($usuario) {
                return $estadoBloqueAnterior->responsable_id;
            }
        }

        return null;
    }

    /**
     * Marca el bloque anterior como "Devuelto" cuando hay una devolución
     */
    private function marcarBloqueComoDevuelto($cuentaId, $bloqueAnteriorId, $comentario = null)
    {
        // Buscar un estado de tipo "DEVUELTO" para el bloque anterior
        $estadoDevuelto = EstadoWorkflow::where('bloque_id', $bloqueAnteriorId)
            ->where('tipo', 'DEVUELTO')
            ->where('es_activo', true)
            ->first();

        if ($estadoDevuelto) {
            // Robustez: Usamos firstOrNew para asegurar que fecha_ingreso_bloque esté presente si el registro es nuevo
            $registroBloque = EstadoBloqueCuenta::firstOrNew(['cuenta_cobro_id' => $cuentaId, 'bloque_id' => $bloqueAnteriorId]);
            
            if (!$registroBloque->exists) {
                $registroBloque->fecha_ingreso_bloque = now();
            }

            $registroBloque->fill([
                'estado_actual_id' => $estadoDevuelto->id,
                'bloque_completado' => true, // El bloque se "completó" pero con devolución
                'fecha_completado_bloque' => now(),
                'fecha_ultima_actualizacion' => now(),
            ])->save();

            Log::info("📤 Bloque {$bloqueAnteriorId} marcado como DEVUELTO para cuenta {$cuentaId}. Estado: {$estadoDevuelto->nombre}");
        } else {
            Log::warning("⚠️ No se encontró estado tipo DEVUELTO para bloque {$bloqueAnteriorId}. No se pudo marcar la devolución.");
        }
    }

    /**
     * Get color by state type
     */
    private function getColorPorTipo($tipo)
    {
        return match ($tipo) {
            'INICIAL' => '#6c757d',
            'EN_PROCESO' => '#ffc107',
            'APROBADO' => '#28a745',
            'DEVUELTO' => '#dc3545',
            'FINAL' => '#17a2b8',
            default => '#6c757d',
        };
    }

    /**
     * Get the complete history of an account
     */
    public function getHistorial($cuentaId)
    {
        $cuenta = CuentaCobro::findOrFail($cuentaId);
        $historial = HistorialWorkflow::with([
            'bloque',
            'estadoOrigen',
            'estadoDestino',
            'usuarioAccion',
        ])
            ->where('cuenta_cobro_id', $cuentaId)
            ->orderBy('fecha_transicion', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // El historial en el workflow interno NO se filtra para que los funcionarios puedan ver toda la traza.

        return response()->json([
            'success' => true,
            'historial' => $historial,
            'tiempo_total' => $cuenta->tiempo_total_ejecucion,
            'fecha_inicio' => $historial->count() > 0 ? $historial->last()->fecha_transicion->format('d/m/Y H:i') : null,
        ]);
    }

    /**
     * Get users filtered by block responsibility permissions
     */
    public function getUsuariosResponsables(Request $request)
    {
        $bloqueCodigo = $request->query('bloque_codigo');
        $estadoId = $request->query('estado_id');

        if (!$bloqueCodigo && $estadoId) {
            $estado = EstadoWorkflow::with('bloque')->find($estadoId);
            $bloqueCodigo = $estado?->bloque?->codigo;
        }

        if (!$bloqueCodigo) {
            return response()->json(['success' => false, 'message' => 'Código de bloque no proporcionado'], 400);
        }

        $usuarios = Usuario::responsablesBloque($bloqueCodigo)
            ->orderBy('primer_nombre')
            ->get()
            ->map(function ($user) use ($bloqueCodigo) {
                return [
                    'id' => $user->id,
                    'nombre' => $user->nombre_completo,
                    'tipo_responsable' => "Responsable $bloqueCodigo",
                ];
            });

        return response()->json([
            'success' => true,
            'usuarios' => $usuarios,
            'bloque_codigo' => $bloqueCodigo,
        ]);
    }

    /**
     * Inicia manualmente la siguiente cuenta de cobro para un contrato finalizado
     */
    public function iniciarSiguienteCuenta(Request $request, $cuentaId)
    {
        $cuenta = CuentaCobro::findOrFail($cuentaId);

        // Validar que esté finalizada y tenga pagos pendientes
        if (! $cuenta->finalizada) {
            return response()->json(['success' => false, 'message' => 'La cuenta actual no ha finalizado su proceso.'], 422);
        }

        if (($cuenta->numero_cuenta ?? 0) >= ($cuenta->numero_pagos_totales ?? 0)) {
            return response()->json(['success' => false, 'message' => 'El contrato ya ha completado todos sus pagos.'], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Aumentar número de cuenta (se maneja como string en DB pero incrementa numéricamente)
            $cuenta->numero_cuenta = (int)($cuenta->numero_cuenta ?? 0) + 1;

            // 2. Reiniciar flags para el cronómetro del nuevo ciclo
            $cuenta->finalizada                   = false;
            $cuenta->fecha_radicacion             = now();
            $cuenta->ultima_factura_hacienda       = null;
            $cuenta->ss_ultima_cuenta             = null;
            // Resetear ambos contadores para el nuevo ciclo de pago
            $cuenta->fecha_ultimo_cambio_estado    = null; // El Observer lo reabre
            $cuenta->tiempo_total_proceso_segundos = 0;    // Nuevo ciclo empieza desde 0
            // Legacy: también resetear para compatibilidad
            $cuenta->ultimo_inicio_conteo          = null;
            $cuenta->tiempo_total_segundos         = 0;
            $cuenta->save();

            // 3. Buscar el estado inicial del Bloque 1 (Sin Trámite)
            $estadoInicialBloque1 = EstadoWorkflow::where('codigo', 'REV1_SIN')->first();

            if (! $estadoInicialBloque1) {
                throw new \Exception('No se encontró el estado inicial del Bloque 1 (REV1_SIN).');
            }

            // 4. LIMPIEZA: Eliminar registros de progreso de los bloques anteriores para el nuevo ciclo
            EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)->delete();

            // 4b. RESET DE BLOQUE: Evitar que ejecutarTransicion lo tome como una "devolución" desde el bloque 6
            $cuenta->bloque_actual_id = $estadoInicialBloque1->bloque_id;
            $cuenta->save();

            // 5. Transicionar al inicio
            $this->ejecutarTransicion($cuenta, $estadoInicialBloque1->id, "Inicio manual del ciclo - Cuenta #{$cuenta->numero_cuenta}.");

            DB::commit();

            $this->logWorkflowAudit('WORKFLOW_NUEVO_CICLO', $cuenta, [
                'numero_cuenta_nuevo' => $cuenta->numero_cuenta,
                'estado_inicial'      => $estadoInicialBloque1->nombre ?? 'REV1_SIN',
                'fecha_inicio_ciclo'  => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se ha iniciado correctamente la cuenta #{$cuenta->numero_cuenta}.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Contrato::logException($e, 'cuentas_cobro', ['operacion' => 'iniciarSiguienteCuenta', 'cuenta_id' => $cuentaId]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el ciclo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * HELPER: Registra un evento del workflow en la tabla 'auditorias' (log centralizado).
     * Permite ver toda la actividad del workflow desde el Historial de Auditoría.
     */
    private function logWorkflowAudit(string $accion, $cuenta, array $payload = []): void
    {
        try {
            Auditoria::create([
                'usuario_id'      => Auth::id(),
                'tabla_afectada'  => 'workflow',
                'registro_id'     => $cuenta->id ?? 0,
                'accion'          => $accion,
                'payload_anterior'=> null,
                'payload_nuevo'   => array_merge([
                    'cuenta_id'        => $cuenta->id ?? null,
                    'numero_cuenta'    => $cuenta->numero_cuenta ?? null,
                    'contrato'         => $cuenta->contrato?->numero_contrato ?? 'N/A',
                    'contratista'      => $cuenta->contrato?->contratista?->nombre_completo ?? 'N/A',
                ], $payload),
                'ip_origen'       => request()->ip() ?? '127.0.0.1',
                'user_agent'      => substr(request()->userAgent() ?? 'none', 0, 200),
            ]);
        } catch (\Exception $ex) {
            Log::error('[WorkflowAudit] Fallo al registrar en auditorías: ' . $ex->getMessage());
        }
    }

    /**
     * Sincroniza el estado del cronómetro desde el frontend.
     */
    public function syncTimer(Request $request)
    {
        $this->timeService->forceWorkflowClosing();
        return response()->json(['success' => true, 'message' => 'Tiempos sincronizados correctamente.']);
    }
}

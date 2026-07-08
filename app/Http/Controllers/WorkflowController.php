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
use Illuminate\Support\Facades\Schema;

class WorkflowController extends Controller
{
    /** @var TimeTrackingService */
    protected $timeService;

    /** @var BusinessTimeService */
    protected $businessTime;

    public function __construct(TimeTrackingService $timeService, BusinessTimeService $businessTime)
    {
        $this->timeService   = $timeService;
        $this->businessTime  = $businessTime;
    }

    /**
     * MOTOR KANBAN - SGCC
     * 
     * Este es el controlador mÃ¡s dinÃ¡mico del sistema. Encargado de renderizar 
     * el tablero de control (Workflow) basÃ¡ndose en los permisos granulares 
     * de cada usuario.
     */
    public function index(Request $request)
    {
        // AUDITORÃA: Trazabilidad de accesos al tablero de operaciones.
        Contrato::logManualAudit(null, 'READ', 'El usuario consultÃ³ el tablero de workflow', 'workflow');

        /** @var Usuario $user */
        $user = Auth::user();

        // 1. CONTROL DE ACCESO 
        // El sistema es multi-perfil. Verificamos si el usuario tiene rol para entrar aquÃ­.
        if (! $user->puedeAccederWorkflow()) {
            abort(403, 'No tienes permiso para acceder al Workflow');
        }

        // 2. CONSTRUCCIÃ“N DEL DATASET OPERATIVO 
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
                // a. Es el responsable directo del trÃ¡mite actual
                $q->where('responsable_actual_id', $user->id)
                // b. O es parte del equipo de gestiÃ³n del contrato (Abogado, Contador, OPS)
                ->orWhereHas('contrato', function($cq) use ($user) {
                    $cq->where('abogado_user_id', $user->id)
                       ->orWhere('contador_user_id', $user->id)
                       ->orWhere('ops_user_id', $user->id);
                    
                    // c. O es el Supervisor del contrato (con el mismo nombre)
                    $nombreCompleto = $user->nombre_completo;
                    if ($nombreCompleto) {
                        $cq->orWhereHas('supervisor', function($sq) use ($nombreCompleto) {
                            $sq->where(function($q) use ($nombreCompleto) {
                                $q->where('nombres', 'ilike', "%{$nombreCompleto}%")
                                  ->orWhere('apellidos', 'ilike', "%{$nombreCompleto}%")
                                  ->orWhere(DB::raw("CONCAT(nombres, ' ', apellidos)"), 'ilike', "%{$nombreCompleto}%");
                            });
                        });
                    }
                });
            });
        }

        // RestricciÃ³n por Bloques: Algunos usuarios solo ven bloques especÃ­ficos.
        $bloquesPermitidos = $user->bloquesPermitidos();
        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $query->whereIn('bloque_actual_id', function ($subQuery) use ($bloquesPermitidos) {
                $subQuery->select('id')->from('bloques_workflow')->whereIn('codigo', $bloquesPermitidos);
            });
        }

        // 4. FILTROS DINÃMICOS DE BÃšSQUEDA
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

        // 5. OPTIMIZACIÃ“N: Se removiÃ³ la regeneraciÃ³n automÃ¡tica de transiciones
        // para mejorar la velocidad de carga. Las transiciones se regeneran 
        // ahora Ãºnicamente desde el panel administrativo al modificar la configuraciÃ³n.

        // 6. CONSTRUCCIÃ“N DE LA MATRIZ DEL WORKFLOW
        // El workflow es dinÃ¡mico. Consultamos los bloques configurados en BD 
        // y organizamos las cuentas por "Bloque -> Estado".
        $bloquesQuery = BloqueWorkflow::with(['estados' => function ($q) {
            $q->where('es_activo', true)->orderBy('id', 'asc');
        }])->orderBy('orden', 'asc');

        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $bloquesQuery->whereIn('codigo', $bloquesPermitidos);
        }

        // 6.b Filtro dinÃ¡mico: Ver solo bloques donde el usuario tiene cuentas asignadas
        if ($user->verSoloBloquesConAsignacion()) {
            $bloquesConCuentasIds = $cuentas->pluck('bloque_actual_id')->unique()->toArray();
            $bloquesQuery->whereIn('id', $bloquesConCuentasIds);
        }

        $bloques = $bloquesQuery->get();
        
        // Si no hay bloques resultantes y el filtro de asignaciÃ³n estaba prendido, 
        // podrÃ­amos mostrar un mensaje vacÃ­o o simplemente dejar el workflow vacÃ­o.

        // Estructuramos el JSON/Array para que el frontend (Blade/JS) lo procese como columnas.
        $workflow = [];
        foreach ($bloques as $bloque) {
            $columnas = [];
            $ultimoBloqueId = BloqueWorkflow::orderBy('orden', 'desc')->value('id');
            foreach ($bloque->estados as $estado) {
                // No mostrar visualmente la columna de devoluciones en el bloque Finalizada
                if ($bloque->id == $ultimoBloqueId && $estado->tipo === 'DEVUELTO') continue;
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
            $bloqueId = $cuenta->bloque_actual_id;
            $estadoId = $cuenta->estado_actual_id;

            if (isset($workflow[$bloqueId]['columnas'][$estadoId])) {
                $workflow[$bloqueId]['columnas'][$estadoId]['cuentas'][] = $cuenta;
                continue;
            }

            // Fallback de seguridad:
            // si la cuenta tiene discrepancia bloque/estado, ubicarla en "Sin Trámite"
            // del bloque correspondiente para que nunca quede fuera del Kanban.
            $bloqueFallback = $cuenta->bloqueActual
                ?: $cuenta->estadoActual?->bloque
                ?: BloqueWorkflow::where('codigo', 'REV1')->with('estadoInicial')->first();
            $estadoFallback = $bloqueFallback?->estadoInicial
                ?: (($cuenta->estadoActual && $cuenta->estadoActual->bloque_id === ($bloqueFallback?->id))
                    ? $cuenta->estadoActual
                    : null);
            $estadoFallback = $estadoFallback
                ?: EstadoWorkflow::where('codigo', 'REV1_SIN')->first();

            if ($bloqueFallback && $estadoFallback) {
                $fallbackBloqueId = $bloqueFallback->id;

                if (! isset($workflow[$fallbackBloqueId])) {
                    $workflow[$fallbackBloqueId] = [
                        'nombre' => $bloqueFallback->nombre,
                        'color' => $this->getColorPorBloque($bloqueFallback->codigo),
                        'columnas' => [],
                    ];
                }

                if (! isset($workflow[$fallbackBloqueId]['columnas'][$estadoFallback->id])) {
                    $workflow[$fallbackBloqueId]['columnas'][$estadoFallback->id] = [
                        'nombre' => $estadoFallback->nombre,
                        'tipo' => $estadoFallback->tipo,
                        'color_hex' => $estadoFallback->color_hex,
                        'cuentas' => [],
                    ];
                }

                $workflow[$fallbackBloqueId]['columnas'][$estadoFallback->id]['cuentas'][] = $cuenta;
            }
        }

        $canEdit = $user->puedeEditarWorkflow();
        $soportaPausaGestionSupervisor = Schema::hasColumn('cuentas_cobro', 'pausa_gestion_supervisor_desde');

        $umbralCritico = (int) \App\Models\Configuracion::getValor('ALERTA_ESTANCAMIENTO_MINUTOS', 20);
        $umbralInformativo = (int) \App\Models\Configuracion::getValor('ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS', 10);

        // RESPUESTA AJAX: Para el refresco parcial del tablero sin recargar.
        if ($request->ajax()) {
            $businessTime = $this->businessTime;
            $ultimoBloqueId = $bloques->sortByDesc('orden')->first()->id ?? 6;
            return view('workflow.componentes.board', compact('workflow', 'canEdit', 'businessTime', 'umbralCritico', 'umbralInformativo', 'ultimoBloqueId'));
        }

        $supervisores = Supervisor::orderBy('nombres')->get();
        $estados = EstadoWorkflow::where('es_activo', true)->select('nombre')->distinct()->get();
        $todosLosEstados = EstadoWorkflow::where('es_activo', true)->with('bloque')->get()->groupBy('bloque.codigo');
        $responsables = Usuario::responsablesWorkflow()
            ->orderBy('primer_nombre')
            ->get();

        $businessTime = $this->businessTime;
        $ultimoBloqueId = $bloques->sortByDesc('orden')->first()->id ?? 6;
        return view('workflow.workflow', compact('workflow', 'supervisores', 'estados', 'responsables', 'canEdit', 'bloques', 'todosLosEstados', 'businessTime', 'umbralCritico', 'umbralInformativo', 'ultimoBloqueId', 'soportaPausaGestionSupervisor'));
    }

    /**
     * Identidad Visual: Mapeo de colores por bloque para reconocimiento rÃ¡pido.
     */
    private function getColorPorBloque(string $codigo)
    {
        return match ($codigo) {
            'REV1' => 'morado',    // RevisiÃ³n inicial
            'SAP' => 'indigo',     // Proceso en ERP
            'FAC' => 'verde',      // FacturaciÃ³n
            'FIR' => 'naranja',    // Firmos y autorizaciones
            'HAC' => 'rosa',       // TesorerÃ­a / Hacienda
            'FIN' => 'cian',       // Finalizado (Archivo)
            default => 'morado',
        };
    }

    private function determinarBloqueVisual(CuentaCobro $cuenta)
    {
        $id = $cuenta->bloque_actual_id;

        if ($id >= 1 && $id <= 6) {
            return "bloque{$id}";
        }

        return 'bloque1'; // Default fallback
    }

    private function mapearEstadoInterno(string $tipoEstado)
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
    public function getEstadosDisponibles(int $cuentaId)
    {
        try {
            $cuenta = CuentaCobro::with(['estadoActual' => function($q) {
                $q->withTrashed();
            }, 'bloqueActual'])->findOrFail($cuentaId);

            // â”€â”€ REGLA DE ASIGNACIÃ“N ESTRICTA (Mano de Dios) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            /** @var Usuario $user */
            $user = Auth::user();
            if (!$user->puedeMoverCualquierCuenta() && $cuenta->responsable_actual_id !== $user->id) {
                return response()->json([
                    'success' => true,
                    'estado_actual' => [
                        'id' => $cuenta->estado_actual_id,
                        'nombre' => $cuenta->estadoActual?->nombre ?? 'Estado Desconocido',
                        'tipo' => $cuenta->estadoActual?->tipo ?? 'N/A',
                    ],
                    'estados_disponibles' => [],
                    'readonly' => true,
                    'message' => 'Solo el responsable asignado puede realizar movimientos.'
                ]);
            }

            // Get allowed transitions from current state
            $query = TransicionPermitida::where('estado_origen_id', $cuenta->estado_actual_id)
                ->where('es_activa', true)
                ->with(['estadoDestino' => function($q) {
                    $q->withTrashed();
                }, 'estadoDestino.bloque']);

            // â”€â”€ REGLA VISUAL ESPECIAL: Sin Tramite â†’ Solo En Revision â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            if ($cuenta->estadoActual?->codigo === 'REV1_SIN') {
                $query->whereHas('estadoDestino', function($q) {
                    $q->where('codigo', 'REV1_REV');
                });
            }
            // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

            $estadosDisponibles = $query->get()
                ->filter(function ($transicion) use ($cuenta) {
                    // No permitir transiciones a estados eliminados
                    return $transicion->estado_destino_id != $cuenta->estado_actual_id 
                        && $transicion->estadoDestino 
                        && !$transicion->estadoDestino->trashed();
                })
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
                })
                ->values();

            // FALLBACK DE EMERGENCIA: Si el estado actual estÃ¡ ELIMINADO, permitimos saltar 
            // a cualquier estado ACTIVO del mismo bloque para "rescatar" la cuenta.
            if ($estadosDisponibles->isEmpty() && $cuenta->estadoActual?->trashed()) {
                $estadosDisponibles = EstadoWorkflow::where('bloque_id', $cuenta->bloque_actual_id)
                    ->where('es_activo', true)
                    ->whereNull('deleted_at')
                    ->get()
                    ->map(function($e) {
                        return [
                            'id' => $e->id,
                            'nombre' => "ðŸ”„ Rescatar a: " . $e->nombre,
                            'tipo' => $e->tipo,
                            'color' => $e->color_hex ?? $this->getColorPorTipo($e->tipo),
                            'bloque_id' => $e->bloque_id,
                            'bloque_nombre' => $e->bloque->nombre ?? '',
                            'requiere_comentario' => true,
                        ];
                    });
            }

            return response()->json([
                'success' => true,
                'estado_actual' => [
                    'id' => $cuenta->estado_actual_id,
                    'nombre' => $cuenta->estadoActual?->nombre ?? 'Estado Desconocido',
                    'tipo' => $cuenta->estadoActual?->tipo ?? 'N/A',
                ],
                'estados_disponibles' => $estadosDisponibles,
            ]);
        } catch (\Exception $e) {
            Log::error("[Workflow] Error en getEstadosDisponibles para Cuenta $cuentaId: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los estados disponibles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change state of a cuenta
     */
    /**
     * GESTIÃ“N DE TRANSICIONES (State Machine Engine)
     * 
     * Este mÃ©todo es el nÃºcleo de la lÃ³gica de negocio. Se encarga de mover 
     * una cuenta de un estado a otro, validando permisos y disparando 
     * automatismos si el flujo lo permite.
     */
    public function cambiarEstado(Request $request, int $cuentaId)
    {
        Log::info("WorkflowController@cambiarEstado - RECIBIDA PETICION - Cuenta: $cuentaId. Data: " . json_encode($request->all()));
        $request->validate([
            'estado_destino_id' => 'required|exists:estados_workflow,id',
            'comentario'        => 'nullable|string|max:500',
            'ss_ultima_cuenta'  => 'nullable|string|in:Enero,Febrero,Marzo,Abril,Mayo,Junio,Julio,Agosto,Septiembre,Octubre,Noviembre,Diciembre',
        ]);

        $cuenta = CuentaCobro::with(['estadoActual', 'bloqueActual'])->findOrFail($cuentaId);

        // REGLA DE "LIMBO" (Solo Lectura): 
        // Si la cuenta ya estÃ¡ finalizada, impedimos retrocesos o cambios manuales.
        if ($cuenta->finalizada && !Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta ya ha sido finalizada (Cierre de Ciclo) y se encuentra en modo solo lectura. No permite movimientos adicionales.'
            ], 422);
        }

        // 1.a REGLA DE ASIGNACIÃ“N ESTRICTA (Mano de Dios):
        // Si el usuario no tiene permiso global, solo puede mover lo que tiene asignado a su nombre.
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->puedeMoverCualquierCuenta()) {
            if ($cuenta->responsable_actual_id !== $user->id) {
                $responsableNombre = $cuenta->responsableActual?->nombre_completo ?? 'Nadie (Sin asignar)';
                return response()->json([
                    'success' => false,
                    'message' => "No tiene permisos para mover este contrato. Actualmente estÃ¡ asignado a: {$responsableNombre}."
                ], 403);
            }
        }

        // 1. VALIDACIÃ“N DE TRANSICIÃ“N: 
        // No permitimos saltos "al azar"; solo los definidos en la tabla 'transiciones_permitidas'.
        $transicion = TransicionPermitida::where('estado_origen_id', $cuenta->estado_actual_id)
            ->where('estado_destino_id', $request->estado_destino_id)
            ->where('es_activa', true)
            ->first();

        if (! $transicion && !$cuenta->estadoActual?->trashed()) {
            $this->logWorkflowAudit('WORKFLOW_TRANSITION_REJECTED', $cuenta, [
                'motivo' => 'TransiciÃ³n no permitida por el motor de reglas',
                'estado_origen_id' => $cuenta->estado_actual_id,
                'estado_origen_nombre' => $cuenta->estadoActual?->nombre,
                'estado_destino_id' => $request->estado_destino_id,
            ]);
            return response()->json(['success' => false, 'message' => 'TransiciÃ³n no permitida'], 403);
        }

        // â”€â”€ REGLA ESPECIAL: Sin Tramite â†’ En Revision â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        // Si el estado origen es REV1_SIN, SOLO se permite ir a REV1_REV.
        // AdemÃ¡s SIEMPRE se exige actualizar el mes de SS (ss_ultima_cuenta).
        $estadoOrigenCodigo = $cuenta->estadoActual?->codigo;
        $estadoDestinoCodigo = EstadoWorkflow::find($request->estado_destino_id)?->codigo;

        if ($estadoOrigenCodigo === 'REV1_SIN') {
            // Bloquear si intenta ir a cualquier estado que no sea REV1_REV
            if ($estadoDestinoCodigo !== 'REV1_REV') {
                return response()->json([
                    'success' => false,
                    'message' => 'Desde "Sin Tramite" solo se puede pasar a "En RevisiÃ³n".',
                ], 422);
            }

            // Si ya viene con el mes informado, guardarlo y continuar normal
            if ($request->filled('ss_ultima_cuenta')) {
                $mes = $request->ss_ultima_cuenta;
                Log::info("WorkflowController@cambiarEstado - Recibido mes: $mes for account {$cuenta->id}");
                $mesesValidos = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                                 'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                if (!in_array($mes, $mesesValidos)) {
                    Log::warning("WorkflowController@cambiarEstado - Mes no vÃ¡lido: $mes");
                    return response()->json(['success' => false, 'message' => 'Mes de SS no vÃ¡lido.'], 422);
                }
                // Guardamos el mes ANTES de ejecutar la transiciÃ³n
                // Usamos DB directa para asegurar persistencia inmediata
                DB::table('cuentas_cobro')->where('id', $cuenta->id)->update(['ss_ultima_cuenta' => $mes]);
                $cuenta->ss_ultima_cuenta = $mes; // Mantener en memoria para el resto del flujo
                Log::info("WorkflowController@cambiarEstado - Guardado mes '$mes' vÃ­a DB::table para cuenta {$cuenta->id}");
            } else {
                // Pedirle al frontend que muestre el selector de mes
                return response()->json([
                    'success'            => true,
                    'requires_ss_cuenta' => true,
                    'cuenta_id'          => $cuentaId,
                    'estado_destino_id'  => $request->estado_destino_id,
                    'message'            => 'Debe indicar el mes de la Ãºltima cuenta de SS finalizada.',
                ]);
            }
        }
        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

        $estadoDestino = EstadoWorkflow::findOrFail($request->estado_destino_id);

        // 2. PUNTO DE DECISIÃ“N (Handoff):
        // Detectamos si el cambio de estado (o su siguiente paso automÃ¡tico) implica un cambio de bloque.
        
        // Verificamos si el estado destino tiene un "Auto-Chaining" (paso automÃ¡tico de bloque)
        $auto = TransicionPermitida::where('estado_origen_id', $estadoDestino->id)
            ->where('accion', 'PASAR_BLOQUE')
            ->where('es_activa', true)
            ->first();
            
        $estadoEfectivo = $auto ? EstadoWorkflow::findOrFail($auto->estado_destino_id) : $estadoDestino;

        $esCambioDeBloque = $cuenta->bloque_actual_id != $estadoEfectivo->bloque_id;
        $ultimoBloqueId = BloqueWorkflow::orderBy('orden', 'desc')->value('id');
        $esBloqueFinal = $estadoEfectivo->bloque_id == $ultimoBloqueId;

        // Una devoluciÃ³n se identifica por el flag permite_devolucion (configurado en BD)
        // o por el orden del bloque (si retrocede a un bloque anterior)
        $esDevolucion = ($estadoEfectivo->permite_devolucion ?? false) || $this->esDevolucionDeBloque($cuenta->bloque_actual_id, $estadoEfectivo->bloque_id);

        // REGLA: El modal aparece si cambia de bloque (y no es el final automÃ¡tico) O si es una devoluciÃ³n.
        // ADICIÃ“N: TambiÃ©n forzamos si pasa de Sin Tramite a En Revision para asignar un responsable.
        $forzarAsignacion = ($estadoOrigenCodigo === 'REV1_SIN' && $estadoDestinoCodigo === 'REV1_REV');

        if (($esCambioDeBloque && !$esBloqueFinal) || $esDevolucion || $forzarAsignacion) {
            
            // FILTRADO DE BLOQUES LÃ“GICO:
            $queryBloques = BloqueWorkflow::where('es_activo', true);
            $ordenActual = $cuenta->bloqueActual->orden;

            if ($esDevolucion) {
                // Si es devoluciÃ³n, solo permitimos bloques IGUALES o ANTERIORES al actual
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
                'block_locked' => $forzarAsignacion,
                'bloques_disponibles' => $queryBloques->orderBy('orden')->get(['id', 'nombre', 'codigo']),
                'message' => $esDevolucion ? "Se requiere motivo y responsable para la devoluciÃ³n" : "Se requiere asignar un responsable para el bloque: {$estadoEfectivo->bloque->nombre}",
            ]);
        }

        // 3. EJECUCIÃ“N ATÃ“MICA:
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
    public function assignResponsible(Request $request, int $cuentaId)
    {
        $request->validate([
            'estado_destino_id' => 'required|exists:estados_workflow,id',
            'responsable_id'    => 'required|exists:usuarios,id',
            'bloque_id'         => 'nullable|exists:bloques_workflow,id',
            'comentario'        => 'nullable|string|max:500',
            'es_devolucion_supervisor' => 'nullable|boolean',
        ]);

        $cuenta = CuentaCobro::findOrFail($cuentaId);

        // REGLA DE "LIMBO" (Solo Lectura): 
        if ($cuenta->finalizada && !Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta ya ha sido finalizada (Cierre de Ciclo) y se encuentra en modo solo lectura. No permite movimientos adicionales.'
            ], 422);
        }

        // 1.a REGLA DE ASIGNACIÃ“N ESTRICTA (Mano de Dios):
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->puedeMoverCualquierCuenta()) {
            if ($cuenta->responsable_actual_id !== $user->id) {
                $responsableNombre = $cuenta->responsableActual?->nombre_completo ?? 'Nadie (Sin asignar)';
                return response()->json([
                    'success' => false,
                    'message' => "No tiene permisos para gestionar este contrato. Actualmente estÃ¡ asignado a: {$responsableNombre}."
                ], 403);
            }
        }

        $estadoDestinoId = $request->estado_destino_id;
        $estadoOriginal = EstadoWorkflow::find($estadoDestinoId);
        $esDevolucionSupervisor = $request->boolean('es_devolucion_supervisor');

        // 1. LÃ“GICA DE CAMBIO DE BLOQUE FORZADO:
        // Si el usuario seleccionÃ³ un bloque diferente al que corresponde el estado de destino original.
        if ($request->filled('bloque_id') && $estadoOriginal && $request->bloque_id != $estadoOriginal->bloque_id) {
            $bloqueForzadoId = $request->bloque_id;
            
            // Detectamos si es devoluciÃ³n: por flag o por orden de bloque
            $esD = ($estadoOriginal->permite_devolucion ?? false) || $this->esDevolucionDeBloque($cuenta->bloque_actual_id, $bloqueForzadoId);

            $queryEstado = EstadoWorkflow::where('bloque_id', $bloqueForzadoId)->where('es_activo', true);

            if ($esD) {
                // Si es devoluciÃ³n, buscamos el estado con permite_devolucion del bloque seleccionado
                $estadoDestinoId = (clone $queryEstado)->where('permite_devolucion', true)->first()?->id
                                   ?? (clone $queryEstado)->orderBy('id', 'asc')->first()?->id;
            } else {
                // Si es avance, buscamos el inicial
                $estadoDestinoId = (clone $queryEstado)->orderBy('id', 'asc')->first()?->id;
            }
        }

        $estadoDestino = EstadoWorkflow::findOrFail($estadoDestinoId);
        $bloqueDestino = BloqueWorkflow::find($request->bloque_id ?: $estadoDestino->bloque_id);

        if ($esDevolucionSupervisor && (! $bloqueDestino || $bloqueDestino->codigo !== 'REV1')) {
            return response()->json([
                'success' => false,
                'message' => 'La devoluciÃ³n a supervisor solo estÃ¡ disponible para el Bloque 1.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 2. EJECUCIÃ“N DE LA TRANSICIÃ“N:
            // Este mÃ©todo ya gestiona el historial, cronÃ³metros y cambio de responsable en la cuenta.
            $this->ejecutarTransicion(
                $cuenta,
                $estadoDestinoId,
                $request->comentario,
                false,
                $request->responsable_id,
                $esDevolucionSupervisor
            );

            // 3. PERSISTENCIA DE RESPONSABLE POR BLOQUE:
            // Aseguramos que el registro de 'estado_bloques_cuentas' refleje quiÃ©n es el dueÃ±o actual de esta fase.
            $bloqueTarget = $estadoDestino->bloque;
            if ($bloqueTarget) {
                EstadoBloqueCuenta::updateOrCreate(
                    ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $bloqueTarget->id],
                    [
                        'responsable_id' => $request->responsable_id,
                        'fecha_ultima_actualizacion' => now()
                    ]
                );
            }

            DB::commit();

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
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PIPELINE DE TRANSICIÃ“N:
     * 
     * Orquestador interno que maneja el historial, los tiempos de respuesta, 
     * detecta si es una devoluciÃ³n y gestiona el "Auto-Chaining" (estados automÃ¡ticos).
     */
    private function ejecutarTransicion(
        CuentaCobro $cuenta,
        int $estadoDestinoId,
        ?string $comentario = null,
        bool $esAutomatica = false,
        ?int $responsableIdForzado = null,
        bool $esDevolucionSupervisor = false
    )
    {
        $estadoOrigenId = $cuenta->estado_actual_id;
        $estadoDestino = EstadoWorkflow::findOrFail($estadoDestinoId);

        // A. CRONÃ“METRO DE ESTADO: Calculamos cuÃ¡nto tiempo viviÃ³ en el estado anterior.
        // Solo contabilizamos si el estado de origen estÃ¡ marcado para ello (SLA Engine rules).
        $estadoOrigen = EstadoWorkflow::find($estadoOrigenId);
        $ultimoHistorial = HistorialWorkflow::where('cuenta_cobro_id', $cuenta->id)
            ->orderBy('fecha_transicion', 'desc')->first();

        $tiempoSegundos = 0;
        if ($estadoOrigen && ($estadoOrigen->contabiliza_tiempo ?? true)) {
            // ANTI-BUG: Usa el helper que filtra automÃ¡ticamente por sesiÃ³n actual del estado
            // Esto previene la herencia de tiempos en flujos cÃ­clicos
            $tiempoSegundos = \App\Models\TaskTimeLog::getElapsedTimeForCurrentState($cuenta);
            
            // VALIDACIÃ“N CRÃTICA: Garantizar que SIEMPRE guardamos SEGUNDOS puros
            // Nunca permitir valores en minutos o unidades mixtas en la BD
            $tiempoSegundos = (int) max(0, $tiempoSegundos); // Asegurar no-negativo
            
            // ValidaciÃ³n de rango: Si es un valor absurdo (> 10 aÃ±os), loguear como WARNING
            $maxSegundos = 365 * 24 * 60 * 60; // 1 aÃ±o en segundos
            if ($tiempoSegundos > $maxSegundos) {
                \Log::warning('Tiempo de estado inusualmente alto detectado', [
                    'cuenta_id' => $cuenta->id,
                    'estado_origen_id' => $estadoOrigenId,
                    'tiempo_segundos' => $tiempoSegundos,
                    'timestamp' => now(),
                ]);
                // No rechazar, pero sÃ­ loguear para auditorÃ­a
            }
        }

        // C. DETECCIÃ“N DE DEVOLUCIONES:
        // Si el bloque nuevo es "anterior" al actual, restauramos automÃ¡ticamente 
        // al responsable que lo trabajÃ³ antes. UX centrada en la eficiencia.
        $bloqueAnteriorId = $cuenta->bloque_actual_id;
        $esDevolucion = $this->esDevolucionDeBloque($bloqueAnteriorId, $estadoDestino->bloque_id);
        
        // SEGURIDAD: Evitar fallback a ID 1 (Admin). Si no hay usuario, lanzamos error controlado.
        $responsableId = $responsableIdForzado ?? Auth::id();
        
        if (!$responsableId && !app()->runningInConsole()) {
            throw new \Exception("No se puede realizar la transiciÃ³n: No hay un usuario autenticado o responsable asignado.");
        }
        
        $responsableId = $responsableId ?? 1; // Fallback solo para procesos de sistema (CLI)

        // D. LÃ“GICA DE ASIGNACIÃ“N AUTOMÃTICA (RECEPTOR B6):
        // Si el estado de destino pertenece al Bloque 6 (Finalizado), buscamos el receptor 
        // configurado con menos carga de trabajo actual para mantener un balance.
        $ultimoBloqueId = BloqueWorkflow::orderBy('orden', 'desc')->value('id');
        if ($estadoDestino->bloque_id == $ultimoBloqueId) {
            $receptorIdeal = Usuario::getReceptorMenosCargadoBloqueFinal();
            if ($receptorIdeal) {
                $responsableId = $receptorIdeal->id;
            }
        }

        if ($esDevolucion) {
            $responsableId = $responsableIdForzado ?? $this->obtenerResponsablePrevio($cuenta->id, $estadoDestino->bloque_id) ?? $responsableId;
            $this->marcarBloqueComoDevuelto($cuenta->id, $bloqueAnteriorId, $comentario);

            $nombreResp = null;
            $respUser = Usuario::find($responsableId);
            if ($respUser) {
                $nombreResp = trim(($respUser->primer_nombre ?? '') . ' ' . ($respUser->primer_apellido ?? ''));
            }

            $nombreSupervisorContrato = trim($cuenta->contrato?->supervisor?->nombre_completo ?? '');
            if ($esDevolucionSupervisor) {
                $comentarioBase = $nombreSupervisorContrato !== ''
                    ? "Devuelto al supervisor: {$nombreSupervisorContrato}"
                    : 'Devuelto al supervisor';
                $comentario = $comentarioBase . ($comentario ? " | {$comentario}" : "");
            } else {
                $comentario = $nombreResp
                    ? "Devuelto a: {$nombreResp}" . ($comentario ? " | {$comentario}" : "")
                    : $comentario;
            }
        }

        if ($cuenta->soportaPausaGestionSupervisor()) {
            $cuenta->pausa_gestion_supervisor_desde = $esDevolucionSupervisor ? now() : null;
        }

        // B. REGISTRO DE HISTORIA (Audit Trail): Punto innegociable para auditorÃ­as externas.
        HistorialWorkflow::create([
            'cuenta_cobro_id' => $cuenta->id,
            'bloque_id' => $cuenta->bloque_actual_id,
            'estado_origen_id' => $estadoOrigenId,
            'estado_destino_id' => $estadoDestinoId,
            'usuario_accion_id' => Auth::id() ?? 1,
            'fecha_transicion' => now(),
            'tiempo_en_estado_anterior_segundos' => $tiempoSegundos,
            'comentarios' => $comentario,
            'metadata' => array_filter([
                'es_devolucion' => $esDevolucion,
                'es_devolucion_supervisor' => $esDevolucionSupervisor,
                'responsable_forzado_id' => $responsableIdForzado,
                'responsable_destino_id' => $esDevolucion ? $responsableId : null,
                'responsable_destino_nombre' => $esDevolucion ? ($nombreResp ?? null) : null,
                'supervisor_destino_nombre' => $esDevolucionSupervisor ? (trim($cuenta->contrato?->supervisor?->nombre_completo ?? '') ?: null) : null,
                'pausa_gestion_supervisor_desde' => $esDevolucionSupervisor ? now()->toDateTimeString() : null,
            ], fn ($value) => ! is_null($value)),
        ]);

        // B2. REGISTRO EN AUDITORÃA GLOBAL: Toda transiciÃ³n queda trazada en el log centralizado.
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

        // E. ACTUALIZACIÃ“N DEL MODELO:
        $cuenta->estado_actual_id = $estadoDestinoId;
        if ($comentario && ! str_starts_with($comentario, 'Automatismo:')) {
            $cuenta->observaciones = $comentario;
        }

        // RESET del contador volÃ¡til: el Observer 'updating' leerÃ¡ el valor
        // actual via getOriginal() ANTES de que el save() lo pise, y lo
        // acumularÃ¡ en tiempo_total_proceso_segundos. El Observer 'updated'
        // despuÃ©s inicializarÃ¡ fecha_ultimo_cambio_estado = now().
        // NO tocar tiempo_total_proceso_segundos aquÃ­: el Observer lo maneja.
        $cuenta->fecha_ultimo_cambio_estado = null;

        $ultimoBloqueId = BloqueWorkflow::orderBy('orden', 'desc')->value('id');

        // Finalización: Solo cuando la cuenta llega a un estado que sea APROBADO, FINAL o es_final=true
        // dentro del bloque final. "Por Confirmar" (INICIAL) NO finaliza.
        // Una vez finalizada, NUNCA se des-finaliza.
        if ($estadoDestino->bloque_id == $ultimoBloqueId
            && ($estadoDestino->tipo === 'APROBADO' || $estadoDestino->tipo === 'FINAL' || $estadoDestino->es_final)) {
            if (! $cuenta->finalizada) {
                $cuenta->finalizada = true;
                $cuenta->numero_facturas_radicadas++; // Incremento contable automático
            }
        }

        // GestiÃ³n de tiempos por bloque para analÃ­tica avanzada
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

        // F. ASIGNACIÃ“N DE FACTURA: LÃ³gica especial para TesorerÃ­a.
        if ($estadoDestino->codigo === 'HAC_OK') {
            $this->asignarNumeroFactura($cuenta);
        }

        // G. AUTO-CHAINING (PropagaciÃ³n):
        // Algunos estados son "puentes" que deben pasar automÃ¡ticamente al siguiente (o al anterior) paso.
        $auto = TransicionPermitida::where('estado_origen_id', $estadoDestinoId)
            ->where('accion', 'PASAR_BLOQUE')
            ->where('es_activa', true)
            ->first();

        if ($auto && !$esAutomatica) {
            // Propagamos el comentario original si existe, para que el historial del landing sea Ãºtil.
            // La bandera $esAutomatica = true evita que estados que son a su vez "puentes" disparen 
            // saltos infinitos o en cadena (Efecto Cascada).
            $this->ejecutarTransicion($cuenta, $auto->estado_destino_id, $comentario ?? "Automatismo: {$auto->accion}", true, $responsableIdForzado);
        }
    }

    /**
     * Motor de numeraciÃ³n: Garantiza que cada radicaciÃ³n en Hacienda tenga un consecutivo Ãºnico por contrato.
     */
    private function asignarNumeroFactura(CuentaCobro $cuenta)
    {
        if (empty($cuenta->ultima_factura_hacienda) || $cuenta->ultima_factura_hacienda === 'N/A') {
            $maxInvoice = CuentaCobro::where('contrato_id', $cuenta->contrato_id)
                ->whereNotNull('ultima_factura_hacienda')
                ->where('ultima_factura_hacienda', '!=', 'N/A')
                ->where('ultima_factura_hacienda', '~', '^[0-9]+$') // Uso de operador nativo de Postgres para limpieza
                ->selectRaw('MAX(CAST(ultima_factura_hacienda AS INTEGER)) as max_val')
                ->value('max_val');

            $cuenta->update(['ultima_factura_hacienda' => (string) (($maxInvoice ?? 0) + 1)]);
        }
    }


    /**
     * Determina si la transiciÃ³n es una devoluciÃ³n a un bloque anterior
     */
    private function esDevolucionDeBloque(int $bloqueOrigenId, int $bloqueDestinoId)
    {
        if ($bloqueOrigenId === $bloqueDestinoId) {
            return false; // Mismo bloque, no es devoluciÃ³n
        }

        $bloqueOrigen = BloqueWorkflow::find($bloqueOrigenId);
        $bloqueDestino = BloqueWorkflow::find($bloqueDestinoId);

        if (! $bloqueOrigen || ! $bloqueDestino) {
            return false;
        }

        // Es devoluciÃ³n si el orden del bloque destino es MENOR que el origen
        return $bloqueDestino->orden < $bloqueOrigen->orden;
    }

    /**
     * Obtiene el ID del responsable que trabajÃ³ previamente en un bloque
     */
    private function obtenerResponsablePrevio(int $cuentaId, int $bloqueId)
    {
        $estadoBloqueAnterior = EstadoBloqueCuenta::where('cuenta_cobro_id', $cuentaId)
            ->where('bloque_id', $bloqueId)
            ->whereNotNull('responsable_id')
            ->orderBy('fecha_ultima_actualizacion', 'desc')
            ->first();

        if ($estadoBloqueAnterior && $estadoBloqueAnterior->responsable_id) {
            // Verificar que el usuario todavÃ­a existe y estÃ¡ activo
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
     * Marca el bloque anterior como "Devuelto" cuando hay una devoluciÃ³n
     */
    private function marcarBloqueComoDevuelto(int $cuentaId, int $bloqueAnteriorId, ?string $comentario = null)
    {
        // Buscar un estado con flag permite_devolucion = true para el bloque anterior
        $estadoDevuelto = EstadoWorkflow::where('bloque_id', $bloqueAnteriorId)
            ->where('permite_devolucion', true)
            ->where('es_activo', true)
            ->first();

        if ($estadoDevuelto) {
            // Robustez: Usamos firstOrNew para asegurar que fecha_ingreso_bloque estÃ© presente si el registro es nuevo
            $registroBloque = EstadoBloqueCuenta::firstOrNew(['cuenta_cobro_id' => $cuentaId, 'bloque_id' => $bloqueAnteriorId]);

            if (!$registroBloque->exists) {
                $registroBloque->fecha_ingreso_bloque = now();
            }

            $registroBloque->fill([
                'estado_actual_id' => $estadoDevuelto->id,
                'bloque_completado' => true, // El bloque se "completÃ³" pero con devoluciÃ³n
                'fecha_completado_bloque' => now(),
                'fecha_ultima_actualizacion' => now(),
            ])->save();

            Log::info("ðŸ“¤ Bloque {$bloqueAnteriorId} marcado como DEVUELTO para cuenta {$cuentaId}. Estado: {$estadoDevuelto->nombre}");
        } else {
            Log::warning("âš ï¸ No se encontrÃ³ estado con permite_devolucion=true para bloque {$bloqueAnteriorId}. No se pudo marcar la devoluciÃ³n.");
        }
    }

    /**
     * Get color by state type
     */
    private function getColorPorTipo(string $tipo)
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
    public function getHistorial(int $cuentaId)
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
            return response()->json(['success' => false, 'message' => 'CÃ³digo de bloque no proporcionado'], 400);
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
     * Devuelve el supervisor por defecto del contrato asociado a la cuenta.
     */
    public function getSupervisorPorDefectoDeCuenta(int $cuenta)
    {
        $cuentaCobro = CuentaCobro::with('contrato.supervisor')->findOrFail($cuenta);
        $supervisor = $cuentaCobro->contrato?->supervisor;
        $usuario = $supervisor ? $this->resolverUsuarioSupervisor($supervisor) : null;

        return response()->json([
            'success' => true,
            'cuenta_id' => $cuentaCobro->id,
            'contrato_id' => $cuentaCobro->contrato_id,
            'supervisor' => $supervisor ? [
                'id' => $supervisor->id,
                'nombres' => $supervisor->nombres,
                'apellidos' => $supervisor->apellidos,
                'cargo' => $supervisor->cargo,
                'email' => $supervisor->email,
                'nombre_completo' => $supervisor->nombre_completo,
            ] : null,
            'supervisor_usuario' => $usuario ? [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre_completo,
                'user' => $usuario->user,
            ] : null,
        ]);
    }

    private function resolverUsuarioSupervisor(Supervisor $supervisor): ?Usuario
    {
        $usuariosActivos = Usuario::where('es_activo', true)->get();
        if ($usuariosActivos->isEmpty()) {
            return null;
        }

        $supervisorNombre = $this->normalizarTexto(trim($supervisor->nombres . ' ' . $supervisor->apellidos));
        $supervisorEmail = $this->normalizarTexto($supervisor->email ?? '');

        return $usuariosActivos->first(function (Usuario $usuario) use ($supervisorNombre, $supervisorEmail) {
            $candidatos = array_filter([
                $usuario->user,
                $usuario->nombre_completo,
                trim(($usuario->primer_nombre ?? '') . ' ' . ($usuario->primer_apellido ?? '')),
            ]);

            foreach ($candidatos as $candidato) {
                $normalizado = $this->normalizarTexto((string) $candidato);
                if ($normalizado !== '' && ($normalizado === $supervisorNombre || $normalizado === $supervisorEmail)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function normalizarTexto(?string $texto): string
    {
        $texto = (string) $texto;
        $texto = preg_replace('/\s+/', ' ', trim($texto)) ?? '';
        $texto = preg_replace('/[^a-z0-9 ]/i', '', $texto) ?? '';

        return mb_strtolower($texto);
    }

    private function assignNumeroRadicadoIfPossible(CuentaCobro $cuenta, ?string $numeroContrato = null): void
    {
        if (! Schema::hasColumn('cuentas_cobro', 'numero_radicado')) {
            return;
        }

        if (! empty($cuenta->numero_radicado)) {
            return;
        }

        $prefijo = $numeroContrato ?: (string) $cuenta->contrato?->numero_contrato ?: 'TR';
        $radicado = sprintf('%s-%s', $prefijo, $cuenta->id);
        $cuenta->forceFill(['numero_radicado' => $radicado])->saveQuietly();
    }

    /**
     * Inicia manualmente una nueva cuenta de cobro para el mismo contrato.
     * LÍMITE MÁXIMO: 10 cuentas totales por contrato (estricto).
     */
    public function iniciarSiguienteCuenta(Request $request, int $cuentaId)
    {
        $cuentaOrigen = CuentaCobro::with('contrato')->findOrFail($cuentaId);
        $contrato = $cuentaOrigen->contrato;

        if (! $contrato) {
            return response()->json(['success' => false, 'message' => 'No se encontró el contrato asociado a la cuenta.'], 404);
        }

        try {
            $nuevaCuenta = DB::transaction(function () use ($cuentaOrigen, $contrato) {
                // Cuenta el total de registros existentes en cuentas_cobro para ese contrato_id.
                $totalCount = CuentaCobro::where('contrato_id', $contrato->id)
                    ->lockForUpdate()
                    ->pluck('id')
                    ->count();

                $limite = (int) ($cuentaOrigen->numero_pagos_totales ?? 0);

                if ($totalCount >= $limite) {
                    throw new \RuntimeException("El contrato ya ha alcanzado el límite máximo de {$limite} cuentas de cobro (N° Pagos Totales).");
                }

                $bloqueInicial = BloqueWorkflow::with('estadoInicial')
                    ->where('codigo', 'REV1')
                    ->first();
                $estadoInicialBloque1 = $bloqueInicial?->estadoInicial
                    ?? EstadoWorkflow::where('codigo', 'REV1_SIN')->first();
                if (! $bloqueInicial || ! $estadoInicialBloque1) {
                    throw new \Exception('No se encontró el bloque inicial REV1 o su estado inicial REV1_SIN.');
                }

                $now = now();
                $numeroCuenta = $totalCount + 1;

                $nuevaCuenta = CuentaCobro::withoutEvents(function () use ($cuentaOrigen, $contrato, $bloqueInicial, $estadoInicialBloque1, $numeroCuenta, $now) {
                    $cuenta = new CuentaCobro();
                    $cuenta->forceFill([
                        'contrato_id' => $contrato->id,
                        'numero_cuenta' => (string) $numeroCuenta,
                        'valor_cobro' => $cuentaOrigen->valor_cobro,
                        'fecha_radicacion' => $now,
                        'numero_pagos_totales' => $cuentaOrigen->numero_pagos_totales,
                        'numero_facturas_radicadas' => 0,
                        'porcentaje_cuentas' => 0,
                        'radicado_por' => $cuentaOrigen->radicado_por,
                        'bloque_actual_id' => $bloqueInicial->id,
                        'estado_actual_id' => $estadoInicialBloque1->id,
                        'finalizada' => false,
                        'responsable_actual_id' => Auth::id(),
                        'observaciones' => $cuentaOrigen->observaciones,
                        'ss_ultima_cuenta' => $cuentaOrigen->ss_ultima_cuenta,
                        'diferencia_cuentas' => $cuentaOrigen->diferencia_cuentas,
                        'ultima_factura_hacienda' => null,
                        'fecha_radicacion_hacienda' => null,
                        'observacion_hacienda' => null,
                        'tiempo_total_segundos' => 0,
                        'ultimo_inicio_conteo' => null,
                        'fecha_ultimo_cambio_estado' => $now,
                        'tiempo_total_proceso_segundos' => 0,
                        'pausa_gestion_supervisor_desde' => null,
                    ]);
                    $cuenta->save();

                    return $cuenta->fresh(['contrato', 'bloqueActual', 'estadoActual']);
                });

                try {
                    $this->assignNumeroRadicadoIfPossible($nuevaCuenta, $contrato->numero_contrato);
                } catch (\Throwable $radicadoError) {
                    Log::warning('No se pudo asignar numero_radicado a cuenta paralela', [
                        'cuenta_id' => $nuevaCuenta->id,
                        'error' => $radicadoError->getMessage(),
                    ]);
                }

                HistorialWorkflow::create([
                    'cuenta_cobro_id' => $nuevaCuenta->id,
                    'bloque_id' => $bloqueInicial->id,
                    'estado_origen_id' => null,
                    'estado_destino_id' => $estadoInicialBloque1->id,
                    'usuario_accion_id' => Auth::id() ?? 1,
                    'fecha_transicion' => $now,
                    'tiempo_en_estado_anterior_segundos' => 0,
                    'comentarios' => "Inicio manual de cuenta paralela #{$nuevaCuenta->numero_cuenta}.",
                    'metadata' => [
                        'cuenta_origen_id' => $cuentaOrigen->id,
                        'numero_cuenta_origen' => $cuentaOrigen->numero_cuenta,
                        'numero_cuenta_nuevo' => $nuevaCuenta->numero_cuenta,
                    ],
                ]);

                EstadoBloqueCuenta::create([
                    'cuenta_cobro_id' => $nuevaCuenta->id,
                    'bloque_id' => $bloqueInicial->id,
                    'estado_actual_id' => $estadoInicialBloque1->id,
                    'fecha_ingreso_bloque' => $now,
                    'fecha_ultima_actualizacion' => $now,
                    'responsable_id' => Auth::id(),
                    'bloque_completado' => false,
                ]);

                return $nuevaCuenta;
            });

            // Fuera de la transacción: un fallo de auditoría no debe revertir la cuenta creada.
            $this->logWorkflowAudit('CUENTA_PARALELA', $nuevaCuenta, [
                'evento' => 'WORKFLOW_NUEVA_CUENTA_PARALELA',
                'cuenta_origen_id' => $cuentaOrigen->id,
                'numero_cuenta_nuevo' => $nuevaCuenta->numero_cuenta,
                'numero_cuenta_origen' => $cuentaOrigen->numero_cuenta,
                'estado_inicial' => $nuevaCuenta->estadoActual?->nombre ?? 'REV1_SIN',
                'fecha_inicio_ciclo' => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => true,
                'id' => $nuevaCuenta->id,
                'numero_cuenta' => $nuevaCuenta->numero_cuenta,
                'bloque_inicial_id' => $nuevaCuenta->bloque_actual_id,
            ]);
        } catch (\Throwable $e) {
            Contrato::logException($e, 'cuentas_cobro', ['operacion' => 'iniciarSiguienteCuenta', 'cuenta_id' => $cuentaId]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el ciclo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * HELPER: Registra un evento del workflow en la tabla 'auditorias' (log centralizado).
     * Permite ver toda la actividad del workflow desde el Historial de AuditorÃ­a.
     */
    private function logWorkflowAudit(string $accion, CuentaCobro $cuenta, array $payload = []): void
    {
        try {
            Auditoria::create([
                'usuario_id'      => Auth::id(),
                'tabla_afectada'  => 'workflow',
                'registro_id'     => $cuenta->id ?? 0,
                'accion'          => mb_substr($accion, 0, 20),
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
            Log::error('[WorkflowAudit] Fallo al registrar en auditorÃ­as: ' . $ex->getMessage());
        }
    }

    /**
     * Sincroniza el estado del cronÃ³metro desde el frontend.
     */
    public function syncTimer(Request $request)
    {
        $this->timeService->forceWorkflowClosing();
        return response()->json(['success' => true, 'message' => 'Tiempos sincronizados correctamente.']);
    }

    public function exportExcel(Request $request)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        
        $request->validate([
            'usuarios' => 'required|array',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'parametros' => 'nullable|array',
        ]);

        $usuariosIds = $request->usuarios;
        
        // ValidaciÃ³n de seguridad para Perfil Usuario
        if (!$user->isAdmin() && $user->rol?->nombre !== 'Visualizador') {
            // Un usuario regular solo puede exportar su propia data
            if (count($usuariosIds) > 1 || $usuariosIds[0] != $user->id) {
                abort(403, 'No tienes permisos para descargar la informaciÃ³n de otros usuarios.');
            }
        } else {
            // Si es admin y seleccionÃ³ "todos"
            if (in_array('todos', $usuariosIds)) {
                $usuariosIds = Usuario::where('es_activo', true)
                    ->whereHas('rol', function($q) {
                        $q->whereNotIn('nombre', ['Administrador', 'Visualizador']);
                    })->pluck('id')->toArray();
            }
        }

        $parametros = $request->parametros ?? [];

        // Log audit
        Contrato::logManualAudit(null, 'EXPORT', 'El usuario exportÃ³ mÃ©tricas de analÃ­tica en Excel', 'workflow');

        return \App\Exports\AnaliticaExport::download(
            $usuariosIds, 
            $request->fecha_inicio, 
            $request->fecha_fin, 
            $parametros
        );
    }
}


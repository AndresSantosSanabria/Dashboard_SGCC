<?php

namespace App\Http\Controllers;

use App\Models\BloqueWorkflow;
use App\Models\Contrato;
use App\Models\CuentaCobro;
use App\Models\EstadoBloqueCuenta;
use App\Models\EstadoWorkflow;
use App\Models\HistorialWorkflow;
use App\Models\Supervisor;
use App\Models\TransicionPermitida;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowController extends Controller
{
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
            $query->where('responsable_actual_id', $user->id);
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

        $cuentas = $query->get();

        // 5. CONSTRUCCIÓN DE LA MATRIZ DEL WORKFLOW
        // El workflow es dinámico. Consultamos los bloques configurados en BD 
        // y organizamos las cuentas por "Bloque -> Estado".
        $bloquesQuery = BloqueWorkflow::with(['estados' => function ($q) {
            $q->where('es_activo', true)->orderBy('id', 'asc');
        }])->orderBy('orden', 'asc');

        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $bloquesQuery->whereIn('codigo', $bloquesPermitidos);
        }

        $bloques = $bloquesQuery->get();

        // Estructuramos el JSON/Array para que el frontend (Blade/JS) lo procese como columnas.
        $workflow = [];
        foreach ($bloques as $bloque) {
            $columnas = [];
            foreach ($bloque->estados as $estado) {
                $columnas[$estado->id] = [
                    'nombre' => $estado->nombre,
                    'tipo' => $estado->tipo,
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

        // RESPUESTA AJAX: Para el refresco parcial del tablero sin recargar.
        if ($request->ajax()) {
            return view('workflow.componentes.board', compact('workflow', 'canEdit'));
        }

        $supervisores = Supervisor::orderBy('nombres')->get();
        $estados = EstadoWorkflow::where('es_activo', true)->select('nombre')->distinct()->get();

        return view('workflow.workflow', compact('workflow', 'supervisores', 'estados', 'canEdit'));
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
        $estadosDisponibles = TransicionPermitida::where('estado_origen_id', $cuenta->estado_actual_id)
            ->where('es_activa', true)
            ->with(['estadoDestino:id,nombre,tipo,color_hex,bloque_id', 'estadoDestino.bloque:id,nombre'])
            ->get()
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
        $request->validate([
            'estado_destino_id' => 'required|exists:estados_workflow,id',
            'comentario' => 'nullable|string|max:500',
        ]);

        $cuenta = CuentaCobro::with(['estadoActual', 'bloqueActual'])->findOrFail($cuentaId);

        // 1. VALIDACIÓN DE TRANSICIÓN: 
        // No permitimos saltos "al azar"; solo los definidos en la tabla 'transiciones_permitidas'.
        $transicion = TransicionPermitida::where('estado_origen_id', $cuenta->estado_actual_id)
            ->where('estado_destino_id', $request->estado_destino_id)
            ->where('es_activa', true)
            ->first();

        if (! $transicion) {
            return response()->json(['success' => false, 'message' => 'Transición no permitida'], 403);
        }

        $estadoDestino = EstadoWorkflow::findOrFail($request->estado_destino_id);

        // 2. PUNTO DE DECISIÓN (Handoff):
        // Si el estado implica un cambio de área (ej: de Revisión a SAP), 
        // detenemos el flujo para que el usuario elija explícitamente al responsable.
        if ($estadoDestino->codigo === 'REV1_PASA' || $estadoDestino->codigo === 'SAP_OK') {
            return response()->json([
                'success' => true,
                'requires_responsible' => true,
                'cuenta_id' => $cuentaId,
                'estado_destino_id' => $request->estado_destino_id,
                'estado_codigo' => $estadoDestino->codigo, // Added this line
                'message' => 'Se requiere asignar un responsable para la siguiente fase.',
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
            'comentario' => 'nullable|string|max:500',
        ]);

        $cuenta = CuentaCobro::with(['estadoActual', 'bloqueActual'])->findOrFail($cuentaId);
        $estadoOrigenId = $cuenta->estado_actual_id;
        $estadoDestinoId = $request->estado_destino_id;

        // Verify transition is allowed
        $transicion = TransicionPermitida::where('estado_origen_id', $estadoOrigenId)
            ->where('estado_destino_id', $estadoDestinoId)
            ->where('es_activa', true)
            ->first();

        if (! $transicion) {
            return response()->json([
                'success' => false,
                'message' => 'Transición no permitida',
            ], 403);
        }

        // Get destination state
        $estadoDestino = EstadoWorkflow::findOrFail($estadoDestinoId);

        // Start transaction
        DB::beginTransaction();
        try {
            // Execute the transition
            $this->ejecutarTransicion($cuenta, $estadoDestinoId, $request->comentario);

            // Determine which block to update based on the destination state
            $estadoDestino = EstadoWorkflow::findOrFail($estadoDestinoId);
            $bloqueTarget = null;

            if ($estadoDestino->codigo === 'REV1_PASA') {
                // For REV1_PASA, assign to SAP block
                $bloqueTarget = BloqueWorkflow::where('codigo', 'SAP')->first();
            } elseif ($estadoDestino->codigo === 'SAP_OK') {
                // For SAP_OK (con ingreso mercancia), assign to Facturación block
                $bloqueTarget = BloqueWorkflow::where('codigo', 'FAC')->first();
            }

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
    private function ejecutarTransicion($cuenta, $estadoDestinoId, $comentario = null)
    {
        $estadoOrigenId = $cuenta->estado_actual_id;
        $estadoDestino = EstadoWorkflow::findOrFail($estadoDestinoId);

        // A. CRONÓMETRO DE ESTADO: Calculamos cuánto tiempo vivió en el estado anterior.
        $ultimoHistorial = HistorialWorkflow::where('cuenta_cobro_id', $cuenta->id)
            ->orderBy('fecha_transicion', 'desc')->first();

        $tiempoPrevio = 0;
        if ($ultimoHistorial) {
            $tiempoPrevio = (int) abs(now()->diffInMinutes($ultimoHistorial->fecha_transicion));
        } else if ($cuenta->created_at) {
            $tiempoPrevio = (int) abs(now()->diffInMinutes($cuenta->created_at));
        }

        // C. DETECCIÓN DE DEVOLUCIONES:
        // Si el bloque nuevo es "anterior" al actual, restauramos automáticamente 
        // al responsable que lo trabajó antes. UX centrada en la eficiencia.
        $bloqueAnteriorId = $cuenta->bloque_actual_id;
        $esDevolucion = $this->esDevolucionDeBloque($bloqueAnteriorId, $estadoDestino->bloque_id);
        $responsableId = Auth::id() ?? 1;

        if ($esDevolucion) {
            $responsableId = $this->obtenerResponsablePrevio($cuenta->id, $estadoDestino->bloque_id) ?? $responsableId;
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
            'tiempo_en_estado_anterior_minutos' => $tiempoPrevio,
            'comentarios' => $comentario,
        ]);

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
        // Algunos estados son "puentes" que deben pasar automáticamente al siguiente paso.
        $auto = TransicionPermitida::where('estado_origen_id', $estadoDestinoId)
            ->where('accion', 'PASAR_BLOQUE')->where('es_activa', true)->first();

        if ($auto) {
            $this->ejecutarTransicion($cuenta, $auto->estado_destino_id, "Automatismo: {$auto->accion}");
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
            // Actualizar el registro del bloque anterior para marcarlo como devuelto
            EstadoBloqueCuenta::updateOrCreate(
                ['cuenta_cobro_id' => $cuentaId, 'bloque_id' => $bloqueAnteriorId],
                [
                    'estado_actual_id' => $estadoDevuelto->id,
                    'bloque_completado' => true, // El bloque se "completó" pero con devolución
                    'fecha_completado_bloque' => now(),
                    'fecha_ultima_actualizacion' => now(),
                ]
            );

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
            ->orderBy('fecha_transicion', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'historial' => $historial,
            'tiempo_total' => $cuenta->tiempo_total_ejecucion,
            'fecha_inicio' => $historial->count() > 0 ? $historial->first()->fecha_transicion->format('d/m/Y H:i') : null,
        ]);
    }

    /**
     * Get users filtered by block responsibility permissions
     */
    public function getUsuariosResponsables($estadoCodigo)
    {
        $usuarios = [];
        $responsableType = '';

        // Determine which permission to filter by based on state code
        if ($estadoCodigo === 'REV1_PASA') {
            // For REV1_PASA, get users who can be responsible for SAP
            $responsableType = 'sap';
            $usuarios = Usuario::responsablesSap()
                ->orderBy('primer_nombre')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'nombre' => $user->primer_nombre . ' ' . $user->primer_apellido,
                        'tipo_responsable' => 'SAP',
                    ];
                });
        } elseif ($estadoCodigo === 'SAP_OK') {
            // For SAP_OK, get users who can be responsible for Facturación
            $responsableType = 'facturacion';
            $usuarios = Usuario::responsablesFac()
                ->orderBy('primer_nombre')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'nombre' => $user->primer_nombre . ' ' . $user->primer_apellido,
                        'tipo_responsable' => 'Facturación',
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'usuarios' => $usuarios,
            'responsable_type' => $responsableType,
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
            // 1. Aumentar número de cuenta
            $cuenta->numero_cuenta = ($cuenta->numero_cuenta ?? 0) + 1;

            // 2. Reiniciar flags y fecha para el cronómetro
            $cuenta->finalizada = false;
            $cuenta->fecha_radicacion = now(); // REINICIO DEL TIEMPO TOTAL
            $cuenta->ultima_factura_hacienda = null; // Reset para el nuevo ciclo
            $cuenta->save();

            // 3. Buscar el estado inicial del Bloque 1
            $estadoInicialBloque1 = EstadoWorkflow::where('codigo', 'REV1_REV')->first();

            if (! $estadoInicialBloque1) {
                throw new \Exception('No se encontró el estado inicial del Bloque 1 (REV1_REV).');
            }

            // 4. LIMPIEZA: Eliminar registros de progreso de los bloques anteriores para el nuevo ciclo
            EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)->delete();

            // 5. Transicionar al inicio
            $this->ejecutarTransicion($cuenta, $estadoInicialBloque1->id, "Inicio manual del ciclo - Cuenta #{$cuenta->numero_cuenta}.");

            DB::commit();

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
}

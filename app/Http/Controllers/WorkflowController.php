<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class WorkflowController extends Controller
{
    /**
     * Display the workflow management page
     */
    public function index(Request $request)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();

        // 1. Check if user can access workflow
        if (!$user->puedeAccederWorkflow()) {
            abort(403, 'No tienes permiso para acceder al Workflow');
        }

        $query = \App\Models\CuentaCobro::with(['contrato.contratista', 'bloqueActual', 'estadoActual', 'estadosBloques', 'historialWorkflow.usuarioAccion', 'historialWorkflow.estadoOrigen', 'historialWorkflow.estadoDestino', 'contrato.supervisor'])
            ->where('finalizada', false);

        // 2. Filter by "Solo asignados" if applicable
        if ($user->verSoloAsignados()) {
            $query->where('responsable_actual_id', $user->id);
        }

        // 3. Filter by allowed blocks - robust filter
        $bloquesPermitidos = $user->bloquesPermitidos();
        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $query->whereIn('bloque_actual_id', function ($subQuery) use ($bloquesPermitidos) {
                $subQuery->select('id')
                    ->from('bloques_workflow')
                    ->whereIn('codigo', $bloquesPermitidos);
            });
        }

        // Aplicar filtros de búsqueda
        if ($request->filled('supervisor_id')) {
            $query->whereHas('contrato', function ($q) use ($request) {
                $q->where('supervisor_id', $request->supervisor_id);
            });
        }

        if ($request->filled('contratista')) {
            $query->whereHas('contrato.contratista', function ($q) use ($request) {
                $q->where('razon_social', 'like', '%' . $request->contratista . '%')
                    ->orWhere('representante_legal', 'like', '%' . $request->contratista . '%');
            });
        }

        if ($request->filled('estado_nombre')) {
            $query->whereHas('estadoActual', function ($q) use ($request) {
                $q->where('nombre', $request->estado_nombre);
            });
        }

        if ($request->filled('numero_cuenta')) {
            $query->where('numero_cuenta', $request->numero_cuenta);
        }

        $cuentas = $query->get();

        $bloquesPermitidos = $user->bloquesPermitidos();

        $bloquesQuery = \App\Models\BloqueWorkflow::with(['estados' => function ($q) {
            $q->where('es_activo', true)->orderBy('id', 'asc');
        }])->orderBy('orden', 'asc');

        // 3. Apply block filter only if it's a valid array with items
        $bloquesPermitidos = $user->bloquesPermitidos();

        // Debug: Log blocks permitidos (remove in production)
        Log::debug('Workflow blocks permitidos for user ' . $user->id . ': ', ['bloques' => $bloquesPermitidos]);

        if (is_array($bloquesPermitidos) && count($bloquesPermitidos) > 0) {
            $bloquesQuery->whereIn('codigo', $bloquesPermitidos);
        } elseif ($bloquesPermitidos !== true) {
            // If not true (all blocks) and not valid array, show no blocks
            Log::warning('Invalid bloques_permitidos for user ' . $user->id . ': ', ['value' => $bloquesPermitidos]);
            $bloquesQuery->whereRaw('1 = 0');
        }

        // If restricted to assignments, hide blocks that have no accounts assigned to the user
        if ($user->verSoloAsignados()) {
            $bloquesOcupados = $cuentas->pluck('bloque_actual_id')->unique()->toArray();
            $bloquesQuery->whereIn('id', $bloquesOcupados);
        }

        $bloques = $bloquesQuery->get();

        // Data for filters
        $supervisores = \App\Models\Supervisor::orderBy('nombres')->get();
        // Obtener solo nombres únicos para evitar duplicados como "devuelta"
        $estados = \App\Models\EstadoWorkflow::where('es_activo', true)
            ->select('nombre')
            ->distinct()
            ->orderBy('nombre')
            ->get();

        $workflow = [];
        foreach ($bloques as $bloque) {
            $columnas = [];
            foreach ($bloque->estados as $estado) {
                $columnas[$estado->id] = [
                    'nombre' => $estado->nombre,
                    'tipo' => $estado->tipo,
                    'cuentas' => []
                ];
            }

            $workflow[$bloque->id] = [
                'nombre' => $bloque->nombre,
                'color' => $this->getColorPorBloque($bloque->codigo),
                'columnas' => $columnas
            ];
        }

        foreach ($cuentas as $cuenta) {
            $bloqueId = $cuenta->bloque_actual_id;
            $estadoId = $cuenta->estado_actual_id;

            if (isset($workflow[$bloqueId]['columnas'][$estadoId])) {
                $workflow[$bloqueId]['columnas'][$estadoId]['cuentas'][] = $cuenta;
            }
        }

        $canEdit = $user->puedeEditarWorkflow();

        if ($request->ajax()) {
            return view('workflow.componentes.board', compact('workflow', 'canEdit'));
        }

        return view('workflow.workflow', compact('workflow', 'supervisores', 'estados', 'canEdit'));
    }

    private function getColorPorBloque($codigo)
    {
        return match ($codigo) {
            'REV1' => 'morado',
            'SAP' => 'indigo',
            'FAC' => 'verde',
            'FIR' => 'naranja',
            'HAC' => 'rosa',
            'FIN' => 'cian',
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
        $cuenta = \App\Models\CuentaCobro::with(['estadoActual', 'bloqueActual'])->findOrFail($cuentaId);

        // Get allowed transitions from current state
        $transiciones = \App\Models\TransicionPermitida::with('estadoDestino.bloque')
            ->where('estado_origen_id', $cuenta->estado_actual_id)
            ->where('es_activa', true)
            ->get();

        $estadosDisponibles = $transiciones->map(function ($transicion) {
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
    public function cambiarEstado(Request $request, $cuentaId)
    {
        $request->validate([
            'estado_destino_id' => 'required|exists:estados_workflow,id',
            'comentario' => 'nullable|string|max:500',
        ]);

        $cuenta = \App\Models\CuentaCobro::with(['estadoActual', 'bloqueActual'])->findOrFail($cuentaId);
        $estadoOrigenId = $cuenta->estado_actual_id;
        $estadoDestinoId = $request->estado_destino_id;

        // Verify transition is allowed
        $transicion = \App\Models\TransicionPermitida::where('estado_origen_id', $estadoOrigenId)
            ->where('estado_destino_id', $estadoDestinoId)
            ->where('es_activa', true)
            ->first();

        if (!$transicion) {
            return response()->json([
                'success' => false,
                'message' => 'Transición no permitida',
            ], 403);
        }

        // Get destination state
        $estadoDestino = \App\Models\EstadoWorkflow::findOrFail($estadoDestinoId);

        // SPECIAL CHECK: If transitioning to REV1_PASA or SAP_OK, require responsible assignment
        if ($estadoDestino->codigo === 'REV1_PASA' || $estadoDestino->codigo === 'SAP_OK') {
            return response()->json([
                'success' => true,
                'requires_responsible' => true,
                'cuenta_id' => $cuentaId,
                'estado_destino_id' => $estadoDestinoId,
                'estado_codigo' => $estadoDestino->codigo,
                'message' => 'Se requiere asignar responsable'
            ]);
        }

        // Start transaction
        DB::beginTransaction();
        try {
            $this->ejecutarTransicion($cuenta, $estadoDestinoId, $request->comentario);

            DB::commit();

            // Refresh account to get newest state
            $cuenta->refresh();

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
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage(),
            ], 500);
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

        $cuenta = \App\Models\CuentaCobro::with(['estadoActual', 'bloqueActual'])->findOrFail($cuentaId);
        $estadoOrigenId = $cuenta->estado_actual_id;
        $estadoDestinoId = $request->estado_destino_id;

        // Verify transition is allowed
        $transicion = \App\Models\TransicionPermitida::where('estado_origen_id', $estadoOrigenId)
            ->where('estado_destino_id', $estadoDestinoId)
            ->where('es_activa', true)
            ->first();

        if (!$transicion) {
            return response()->json([
                'success' => false,
                'message' => 'Transición no permitida',
            ], 403);
        }

        // Get destination state
        $estadoDestino = \App\Models\EstadoWorkflow::findOrFail($estadoDestinoId);

        // Start transaction
        DB::beginTransaction();
        try {
            // Execute the transition
            $this->ejecutarTransicion($cuenta, $estadoDestinoId, $request->comentario);

            // Determine which block to update based on the destination state
            $estadoDestino = \App\Models\EstadoWorkflow::findOrFail($estadoDestinoId);
            $bloqueTarget = null;

            if ($estadoDestino->codigo === 'REV1_PASA') {
                // For REV1_PASA, assign to SAP block
                $bloqueTarget = \App\Models\BloqueWorkflow::where('codigo', 'SAP')->first();
            } elseif ($estadoDestino->codigo === 'SAP_OK') {
                // For SAP_OK (con ingreso mercancia), assign to Facturación block
                $bloqueTarget = \App\Models\BloqueWorkflow::where('codigo', 'FAC')->first();
            }

            if ($bloqueTarget) {
                // Update or create the block record with the assigned responsible
                \App\Models\EstadoBloqueCuenta::updateOrCreate(
                    ['cuenta_cobro_id' => $cuentaId, 'bloque_id' => $bloqueTarget->id],
                    ['responsable_id' => $request->responsable_id]
                );

                // SYNC: Update the main account's current responsible
                $cuenta->update(['responsable_actual_id' => $request->responsable_id]);

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
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executes a transition and follows any automatic chains
     */
    private function ejecutarTransicion($cuenta, $estadoDestinoId, $comentario = null)
    {
        $estadoOrigenId = $cuenta->estado_actual_id;
        $estadoOrigen = \App\Models\EstadoWorkflow::findOrFail($estadoOrigenId);
        $estadoDestino = \App\Models\EstadoWorkflow::findOrFail($estadoDestinoId);

        // 1. Calculate time in previous state
        $ultimoHistorial = \App\Models\HistorialWorkflow::where('cuenta_cobro_id', $cuenta->id)
            ->orderBy('fecha_transicion', 'desc')
            ->first();

        $tiempoEnEstadoMinutos = null;
        if ($ultimoHistorial) {
            $tiempoEnEstadoMinutos = now()->diffInMinutes($ultimoHistorial->fecha_transicion);
        } else {
            // Si es la primera transición, calcular tiempo desde radicación o creación
            $inicio = $cuenta->fecha_radicacion ?? $cuenta->created_at;
            if ($inicio) {
                $tiempoEnEstadoMinutos = now()->diffInMinutes($inicio);
            }
        }

        // 2. Create history record
        \App\Models\HistorialWorkflow::create([
            'cuenta_cobro_id' => $cuenta->id,
            'bloque_id' => $cuenta->bloque_actual_id,
            'estado_origen_id' => $estadoOrigenId,
            'estado_destino_id' => $estadoDestinoId,
            'usuario_accion_id' => Auth::id() ?? 1,
            'fecha_transicion' => now(),
            'tiempo_en_estado_anterior_minutos' => $tiempoEnEstadoMinutos,
            'comentarios' => $comentario,
        ]);

        // 3. Update account state and observations
        $cuenta->estado_actual_id = $estadoDestinoId;
        if ($comentario && !str_starts_with($comentario, 'Automatismo:')) {
            $cuenta->observaciones = $comentario;
        }

        // **NUEVA LÓGICA**: Determinar si es una devolución comparando bloques
        $bloqueAnteriorId = $cuenta->bloque_actual_id;
        $bloqueNuevoId = $estadoDestino->bloque_id;
        $esDevolucion = $this->esDevolucionDeBloque($bloqueAnteriorId, $bloqueNuevoId);

        // **MARCAR BLOQUE ANTERIOR COMO DEVUELTO SI ES DEVOLUCIÓN**
        if ($esDevolucion) {
            $this->marcarBloqueComoDevuelto($cuenta->id, $bloqueAnteriorId, $comentario);
        }

        // **RESTAURAR RESPONSABLE IF DEVOLUCIÓN**
        $responsableId = Auth::id() ?? 1; // Default: usuario actual

        if ($esDevolucion) {
            // Buscar el responsable que trabajó previamente en este bloque
            $responsablePrevio = $this->obtenerResponsablePrevio($cuenta->id, $bloqueNuevoId);

            if ($responsablePrevio) {
                $responsableId = $responsablePrevio;
                Log::info("🔄 DEVOLUCIÓN detectada para cuenta {$cuenta->id}: Restaurando responsable anterior (User ID: {$responsableId}) del bloque {$bloqueNuevoId}");
            } else {
                Log::warning("⚠️ DEVOLUCIÓN sin responsable previo para cuenta {$cuenta->id} en bloque {$bloqueNuevoId}. Usando usuario actual: {$responsableId}");
            }
        } else {
            Log::info("➡️ AVANCE detectado para cuenta {$cuenta->id}: Asignando a usuario actual (User ID: {$responsableId})");
        }

        // 4. Update/Create entry for the block of the destination state
        $existeRegistro = \App\Models\EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)
            ->where('bloque_id', $estadoDestino->bloque_id)
            ->first();

        $updateData = [
            'estado_actual_id' => $estadoDestinoId,
            'responsable_id' => $responsableId, // ✅ Usar el responsable determinado (previo o actual)
            'fecha_ultima_actualizacion' => now(),
            'bloque_completado' => (bool)$estadoDestino->es_final,
            'fecha_completado_bloque' => $estadoDestino->es_final ? now() : null,
        ];

        // LÓGICA DE CICLO MANUAL:
        // Cuando llega al bloque 6 (FINALIZADO) y es un estado APROBADO, se marca como finalizada
        // para que desaparezca del workflow. El usuario la reactivará desde el dashboard.
        if ($estadoDestino->bloque_id == 6 && $estadoDestino->tipo == 'APROBADO') {
            $cuenta->finalizada = true;

            // ✅ INCREMENTAR facturas radicadas al FINALIZAR el ciclo
            $cuenta->numero_facturas_radicadas = ($cuenta->numero_facturas_radicadas ?? 0) + 1;
            Log::info("Cuenta {$cuenta->id} FINALIZADA. Facturas radicadas incrementadas a: {$cuenta->numero_facturas_radicadas}");
        } else {
            // Si sale de finalizada (vuelve atrás), le quitamos el flag de finalizada
            $cuenta->finalizada = false;
        }

        // Si el bloque cambia o no existía el registro, actualizamos fecha de ingreso (Timer reset)
        if ($estadoDestino->bloque_id != $cuenta->bloque_actual_id || !$existeRegistro) {
            $updateData['fecha_ingreso_bloque'] = now();
            $cuenta->bloque_actual_id = $estadoDestino->bloque_id;
        }

        \App\Models\EstadoBloqueCuenta::updateOrCreate(
            ['cuenta_cobro_id' => $cuenta->id, 'bloque_id' => $estadoDestino->bloque_id],
            $updateData
        );

        // ✅ SYNC: Actualizar responsable actual en la cuenta principal
        $cuenta->responsable_actual_id = $responsableId;
        $cuenta->save();

        // LÓGICA ESPECIAL: Incrementar factura cuando se marca como "Radicada" en Hacienda
        if ($estadoDestino->codigo === 'HAC_OK') {
            // SOLO asignar número de factura si no tiene (IDEMPOTENCIA)
            if (empty($cuenta->ultima_factura_hacienda) || $cuenta->ultima_factura_hacienda === 'N/A') {
                // Obtener el siguiente número de factura para este contrato
                $maxInvoice = \App\Models\CuentaCobro::where('contrato_id', $cuenta->contrato_id)
                    ->whereNotNull('ultima_factura_hacienda')
                    ->where('ultima_factura_hacienda', '!=', 'N/A')
                    ->selectRaw('MAX(CAST(ultima_factura_hacienda AS UNSIGNED)) as max_num')
                    ->value('max_num');

                $nextInvoiceNumber = ($maxInvoice ?? 0) + 1;

                // Solo asignar el número de factura, NO incrementar facturas_radicadas aquí
                // El incremento de facturas_radicadas ocurre al FINALIZAR el ciclo (bloque 6)
                $cuenta->update([
                    'ultima_factura_hacienda' => $nextInvoiceNumber,
                ]);

                Log::info("Invoice number assigned for cuenta {$cuenta->id}: {$nextInvoiceNumber}");
            }
        }

        // 5. AUTO-CHAINING: Check if this new state has an automatic transition
        // We look for transitions from this new state that have 'PASAR_BLOQUE'
        // NOTA: 'DEVOLVER' NO debe ser automático para evitar que una cuenta desaparezca
        $transicionAutomatica = \App\Models\TransicionPermitida::where('estado_origen_id', $estadoDestinoId)
            ->where('accion', 'PASAR_BLOQUE')
            ->where('es_activa', true)
            ->first();

        if ($transicionAutomatica) {
            // Recursive call to follow the chain
            // Note: In a real environment, we'd add recursion depth protection
            $this->ejecutarTransicion($cuenta, $transicionAutomatica->estado_destino_id, "Automatismo: {$transicionAutomatica->accion}");
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

        $bloqueOrigen = \App\Models\BloqueWorkflow::find($bloqueOrigenId);
        $bloqueDestino = \App\Models\BloqueWorkflow::find($bloqueDestinoId);

        if (!$bloqueOrigen || !$bloqueDestino) {
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
        $estadoBloqueAnterior = \App\Models\EstadoBloqueCuenta::where('cuenta_cobro_id', $cuentaId)
            ->where('bloque_id', $bloqueId)
            ->whereNotNull('responsable_id')
            ->orderBy('fecha_ultima_actualizacion', 'desc')
            ->first();

        if ($estadoBloqueAnterior && $estadoBloqueAnterior->responsable_id) {
            // Verificar que el usuario todavía existe y está activo
            $usuario = \App\Models\Usuario::where('id', $estadoBloqueAnterior->responsable_id)
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
        $estadoDevuelto = \App\Models\EstadoWorkflow::where('bloque_id', $bloqueAnteriorId)
            ->where('tipo', 'DEVUELTO')
            ->where('es_activo', true)
            ->first();

        if ($estadoDevuelto) {
            // Actualizar el registro del bloque anterior para marcarlo como devuelto
            \App\Models\EstadoBloqueCuenta::updateOrCreate(
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
        $cuenta = \App\Models\CuentaCobro::findOrFail($cuentaId);
        $historial = \App\Models\HistorialWorkflow::with([
            'bloque',
            'estadoOrigen',
            'estadoDestino',
            'usuarioAccion'
        ])
            ->where('cuenta_cobro_id', $cuentaId)
            ->orderBy('fecha_transicion', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'historial' => $historial,
            'tiempo_total' => $cuenta->tiempo_total_ejecucion,
            'fecha_inicio' => $historial->count() > 0 ? $historial->first()->fecha_transicion->format('d/m/Y H:i') : null
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
            $usuarios = \App\Models\Usuario::responsablesSap()
                ->orderBy('primer_nombre')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'nombre' => $user->primer_nombre . ' ' . $user->primer_apellido,
                        'tipo_responsable' => 'SAP'
                    ];
                });
        } elseif ($estadoCodigo === 'SAP_OK') {
            // For SAP_OK, get users who can be responsible for Facturación
            $responsableType = 'facturacion';
            $usuarios = \App\Models\Usuario::responsablesFac()
                ->orderBy('primer_nombre')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'nombre' => $user->primer_nombre . ' ' . $user->primer_apellido,
                        'tipo_responsable' => 'Facturación'
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'usuarios' => $usuarios,
            'responsable_type' => $responsableType
        ]);
    }

    /**

     * Inicia manualmente la siguiente cuenta de cobro para un contrato finalizado
     */
    public function iniciarSiguienteCuenta(Request $request, $cuentaId)
    {
        $cuenta = \App\Models\CuentaCobro::findOrFail($cuentaId);

        // Validar que esté finalizada y tenga pagos pendientes
        if (!$cuenta->finalizada) {
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
            $estadoInicialBloque1 = \App\Models\EstadoWorkflow::where('codigo', 'REV1_REV')->first();

            if (!$estadoInicialBloque1) {
                throw new \Exception('No se encontró el estado inicial del Bloque 1 (REV1_REV).');
            }

            // 4. LIMPIEZA: Eliminar registros de progreso de los bloques anteriores para el nuevo ciclo
            \App\Models\EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)->delete();

            // 5. Transicionar al inicio
            $this->ejecutarTransicion($cuenta, $estadoInicialBloque1->id, "Inicio manual del ciclo - Cuenta #{$cuenta->numero_cuenta}.");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Se ha iniciado correctamente la cuenta #{$cuenta->numero_cuenta}.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al iniciar siguiente cuenta: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el ciclo: ' . $e->getMessage(),
            ], 500);
        }
    }
}

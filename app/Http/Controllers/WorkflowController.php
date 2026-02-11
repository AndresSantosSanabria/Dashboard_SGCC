<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    /**
     * Display the workflow management page
     */
    public function index(Request $request)
    {
        $query = \App\Models\CuentaCobro::with(['contrato.contratista', 'bloqueActual', 'estadoActual', 'estadosBloques', 'historialWorkflow.usuarioAccion', 'historialWorkflow.estadoOrigen', 'historialWorkflow.estadoDestino', 'contrato.supervisor']);

        // Aplicar filtros
        if ($request->filled('supervisor_id')) {
            $query->whereHas('contrato', function($q) use ($request) {
                $q->where('supervisor_id', $request->supervisor_id);
            });
        }

        if ($request->filled('contratista')) {
            $query->whereHas('contrato.contratista', function($q) use ($request) {
                $q->where('razon_social', 'like', '%' . $request->contratista . '%')
                  ->orWhere('representante_legal', 'like', '%' . $request->contratista . '%');
            });
        }

        if ($request->filled('estado_nombre')) {
            $query->whereHas('estadoActual', function($q) use ($request) {
                $q->where('nombre', $request->estado_nombre);
            });
        }

        $cuentas = $query->get();

        $bloques = \App\Models\BloqueWorkflow::with(['estados' => function($q) {
            $q->where('es_activo', true)->orderBy('id', 'asc');
        }])->orderBy('orden', 'asc')->get();

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

        return view('workflow', compact('workflow', 'supervisores', 'estados'));
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

        // Start transaction
        \DB::beginTransaction();
        try {
            $this->ejecutarTransicion($cuenta, $estadoDestinoId, $request->comentario);
            
            \DB::commit();

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
            \DB::rollBack();
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
        $estadoDestino = \App\Models\EstadoWorkflow::findOrFail($estadoDestinoId);

        // 1. Calculate time in previous state
        $ultimoHistorial = \App\Models\HistorialWorkflow::where('cuenta_cobro_id', $cuenta->id)
            ->orderBy('fecha_transicion', 'desc')
            ->first();

        $tiempoEnEstadoMinutos = null;
        if ($ultimoHistorial) {
            $tiempoEnEstadoMinutos = now()->diffInMinutes($ultimoHistorial->fecha_transicion);
        }

        // 2. Create history record
        \App\Models\HistorialWorkflow::create([
            'cuenta_cobro_id' => $cuenta->id,
            'bloque_id' => $cuenta->bloque_actual_id,
            'estado_origen_id' => $estadoOrigenId,
            'estado_destino_id' => $estadoDestinoId,
            'usuario_accion_id' => auth()->id() ?? 1,
            'fecha_transicion' => now(),
            'tiempo_en_estado_anterior_minutos' => $tiempoEnEstadoMinutos,
            'comentarios' => $comentario,
        ]);

        // 3. Update account state and observations
        $cuenta->estado_actual_id = $estadoDestinoId;
        if ($comentario && !str_starts_with($comentario, 'Automatismo:')) {
            $cuenta->observaciones = $comentario;
        }

        // 4. Update/Create entry for the block of the destination state
        $existeRegistro = \App\Models\EstadoBloqueCuenta::where('cuenta_cobro_id', $cuenta->id)
            ->where('bloque_id', $estadoDestino->bloque_id)
            ->first();

        $updateData = [
            'estado_actual_id' => $estadoDestinoId,
            'responsable_id' => auth()->id() ?? 1,
            'fecha_ultima_actualizacion' => now(),
            // Se marca como finalizada solo si llegamos al último bloque y es un estado final
            'bloque_completado' => (bool)$estadoDestino->es_final,
            'fecha_completado_bloque' => $estadoDestino->es_final ? now() : null,
        ];

        // Si es el bloque 6 (FINALIZADA) y es un estado APROBADO, marcamos la cuenta como finalizada global
        if ($estadoDestino->bloque_id == 6 && $estadoDestino->tipo == 'APROBADO') {
            $cuenta->finalizada = true;
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
        
        $cuenta->save();

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
            'historial' => $historial
        ]);
    }
}

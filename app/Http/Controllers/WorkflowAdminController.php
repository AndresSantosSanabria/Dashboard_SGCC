<?php

namespace App\Http\Controllers;

use App\Models\BloqueWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\Contrato;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\TransicionPermitida;
use Illuminate\Support\Facades\DB;

class WorkflowAdminController extends Controller
{
    public function index()
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }

        $bloques = BloqueWorkflow::with(['estados' => function ($q) {
            $q->withTrashed();
        }])->orderBy('orden')->get();

        return view('configuracion.workflow.index', compact('bloques'));
    }

    public function store(Request $request)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'bloque_id' => 'required|exists:bloques_workflow,id',
            'nombre' => 'required|string|max:100',
            'tipo' => 'required|in:INICIAL,EN_PROCESO,APROBADO,DEVUELTO,FINAL',
            'descripcion' => 'nullable|string',
            'color_hex' => 'nullable|string|max:7',
            'es_inicial' => 'boolean',
            'es_final' => 'boolean',
            'contabiliza_tiempo' => 'boolean',
            'afecta_indicadores' => 'boolean',
        ]);

        try {
            $estado = DB::transaction(function () use ($validated, $request) {
                $bloqueId = $validated['bloque_id'];
                
                // Generar código único
                $bloque = BloqueWorkflow::find($bloqueId);
                $codigo = strtoupper($bloque->codigo . '_' . Str::slug($validated['nombre'], '_'));

                // Asegurar unicidad de código
                $originalCodigo = $codigo;
                $counter = 1;
                while (EstadoWorkflow::withTrashed()->where('codigo', $codigo)->exists()) {
                    $codigo = $originalCodigo . '_' . $counter++;
                }

                $estado = new EstadoWorkflow($validated);
                $estado->codigo = $codigo;

                // REGLAS DE NEGOCIO Y COLORES
                if ($request->boolean('permite_devolucion')) {
                    $estado->permite_devolucion = true;
                    $estado->es_inicial = false;
                    $estado->es_final = false; // Exclusión mutua total
                    $estado->tipo = 'EN_PROCESO';
                    $estado->color_hex = $estado->color_hex ?: '#dc3545'; // Rojo institucional para devolución
                } elseif ($request->boolean('es_inicial')) {
                    $estado->es_inicial = true;
                    $estado->es_final = false; 
                    $estado->permite_devolucion = false;
                    $estado->tipo = 'INICIAL';
                    $estado->color_hex = $estado->color_hex ?: '#0057b8'; // Azul institucional
                    EstadoWorkflow::where('bloque_id', $bloqueId)->update(['es_inicial' => false, 'tipo' => 'EN_PROCESO']);
                } elseif ($request->boolean('es_final')) {
                    $estado->es_final = true;
                    $estado->es_inicial = false; // Exclusión mutua
                    $estado->tipo = 'FINAL';
                    $estado->color_hex = $estado->color_hex ?: '#28a745'; // Verde institucional
                    EstadoWorkflow::where('bloque_id', $bloqueId)->update(['es_final' => false, 'tipo' => 'EN_PROCESO']);
                } else {
                    // Por defecto: Proceso
                    if ($estado->tipo === 'APROBADO') {
                        $estado->color_hex = $estado->color_hex ?: '#20c997'; // Verde medio
                    } else {
                        $estado->tipo = 'EN_PROCESO';
                        $estado->color_hex = $estado->color_hex ?: '#fd7e14'; // Naranja
                    }
                }

                $estado->save();

                // Regenerar transiciones DENTRO de la transacción
                $this->regenerarTransiciones();

                return $estado;
            });

            Contrato::logManualAudit($estado, 'CREATE', "Creado estado de workflow: {$estado->nombre}", 'estados_workflow');

            return response()->json([
                'success' => true,
                'message' => 'Estado creado correctamente y transiciones sincronizadas.',
                'reload'  => true,
            ]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'estados_workflow', ['operacion' => 'store']);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $estado = EstadoWorkflow::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo' => 'required|in:INICIAL,EN_PROCESO,APROBADO,DEVUELTO,FINAL',
            'descripcion' => 'nullable|string',
            'color_hex' => 'nullable|string|max:7',
            'es_inicial' => 'boolean',
            'es_final' => 'boolean',
            'permite_devolucion' => 'boolean',
            'contabiliza_tiempo' => 'boolean',
            'afecta_indicadores' => 'boolean',
            'es_activo' => 'boolean',
        ]);

        try {
            DB::transaction(function () use ($validated, $request, $estado, $id) {
                $bloqueId = $estado->bloque_id;

                // AUTO-SYNC, UNICIDAD Y COLORES
                if ($request->boolean('permite_devolucion')) {
                    $validated['permite_devolucion'] = true;
                    $validated['es_inicial'] = false;
                    $validated['es_final'] = false; // Exclusión mutua total
                    $validated['tipo'] = 'EN_PROCESO';
                    $validated['color_hex'] = $validated['color_hex'] ?: '#dc3545'; // Rojo
                } elseif ($request->boolean('es_inicial')) {
                    $validated['es_inicial'] = true;
                    $validated['es_final'] = false;
                    $validated['permite_devolucion'] = false;
                    $validated['tipo'] = 'INICIAL';
                    $validated['color_hex'] = $validated['color_hex'] ?: '#0057b8'; // Azul
                    EstadoWorkflow::where('bloque_id', $bloqueId)
                        ->where('id', '!=', $id)
                        ->update(['es_inicial' => false, 'tipo' => 'EN_PROCESO']);
                } elseif ($request->boolean('es_final')) {
                    $validated['es_final'] = true;
                    $validated['es_inicial'] = false; // Exclusión mutua
                    $validated['tipo'] = 'FINAL';
                    $validated['color_hex'] = $validated['color_hex'] ?: '#28a745'; // Verde
                    EstadoWorkflow::where('bloque_id', $bloqueId)
                        ->where('id', '!=', $id)
                        ->update(['es_final' => false, 'tipo' => 'EN_PROCESO']);
                } else {
                    // Si no es inicial ni final, permitimos elegir APROBADO o EN_PROCESO
                    if (($validated['tipo'] ?? $request->tipo) === 'APROBADO') {
                        $validated['color_hex'] = $validated['color_hex'] ?: '#20c997'; // Verde suave
                    } else {
                        $validated['tipo'] = 'EN_PROCESO';
                        $validated['color_hex'] = $validated['color_hex'] ?: '#fd7e14'; // Naranja
                    }
                }

                $estado->update($validated);

                // Regenerar transiciones DENTRO de la transacción
                $this->regenerarTransiciones();
            });

            Contrato::logManualAudit($estado, 'UPDATE', "Actualizado estado de workflow: {$estado->nombre}", 'estados_workflow');

            return response()->json(['success' => true, 'message' => 'Estado actualizado correctamente y transiciones sincronizadas.']);
        } catch (\Exception $e) {
            Contrato::logException($e, 'estados_workflow', ['operacion' => 'update', 'id' => $id]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $estado = EstadoWorkflow::withTrashed()->findOrFail($id);

        if ($estado->cuentasCobroActuales()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar este estado porque hay cuentas de cobro actualmente en él.'
            ], 400);
        }

        try {
            if ($estado->trashed()) {
                $estado->restore();
                Contrato::logManualAudit($estado, 'RESTORE', "Restaurado estado de workflow: {$estado->nombre}", 'estados_workflow');
                return response()->json(['success' => true, 'message' => 'Estado restaurado']);
            } else {
                $estado->delete();
                Contrato::logManualAudit($estado, 'DELETE', "Eliminado lógico de estado de workflow: {$estado->nombre}", 'estados_workflow');
                return response()->json(['success' => true, 'message' => 'Estado eliminado (borrado lógico)']);
            }
        } catch (\Exception $e) {
            Contrato::logException($e, 'estados_workflow', ['operacion' => 'destroy', 'id' => $id]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleStatus($id)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        $estado = EstadoWorkflow::withTrashed()->findOrFail($id);
        $estado->es_activo = !$estado->es_activo;
        $estado->save();

        return response()->json([
            'success' => true,
            'message' => $estado->es_activo ? 'Estado activado' : 'Estado desactivado',
            'es_activo' => $estado->es_activo
        ]);
    }

    /**
     * Limpia transiciones huérfanas y regenera todas las transiciones activas.
     * Útil después de borrar estados duplicados.
     */
    public function syncTransiciones()
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        try {
            DB::transaction(function () {
                // 1. Eliminar transiciones que apuntan a estados eliminados (soft-deleted)
                $idsEstadosActivos = EstadoWorkflow::where('es_activo', true)
                    ->whereNull('deleted_at')
                    ->pluck('id');

                TransicionPermitida::whereNotIn('estado_origen_id', $idsEstadosActivos)
                    ->orWhereNotIn('estado_destino_id', $idsEstadosActivos)
                    ->delete();

                // 2. Regenerar transiciones para los estados activos restantes
                $this->regenerarTransiciones();
            });

            return response()->json([
                'success' => true,
                'message' => 'Transiciones sincronizadas correctamente.',
                'reload'  => true,
            ]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'estados_workflow', ['operacion' => 'syncTransiciones']);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Regenera automáticamente las transiciones permitidas entre estados.
     *
     * Reglas:
     *  1. Dentro de un bloque: cada estado activo puede ir a todos los demás
     *     estados activos del mismo bloque (excepto a sí mismo).
     *  2. Entre bloques: los estados marcados como es_final=true (o tipo FINAL)
     *     en un bloque pueden ir al estado inicial (es_inicial=true) del bloque
     *     inmediatamente siguiente según el campo `orden`.
     *  3. Se usa updateOrCreate para preservar configuraciones manuales ya existentes.
     */
    public function regenerarTransiciones(): void
    {
        // 1. DESACTIVAR TODO LO ANTERIOR PARA EMPEZAR DE CERO
        TransicionPermitida::query()->update(['es_activa' => false]);

        // Cargamos todos los bloques ordenados, con sus estados activos (sin soft-deleted)
        $bloques = BloqueWorkflow::with(['estados' => function ($q) {
            $q->where('es_activo', true)->whereNull('deleted_at')->orderBy('id', 'asc');
        }])->orderBy('orden', 'asc')->get();

        foreach ($bloques as $index => $bloque) {
            $estadosBloque = $bloque->estados;
            $isBloque1 = ($index === 0);

            if ($estadosBloque->isEmpty()) {
                continue;
            }

            // REGLA 1 & 2: Transiciones internas del bloque
            // Permitimos movimiento libre entre estados del mismo bloque para no bloquear la operación.
            foreach ($estadosBloque as $origen) {
                foreach ($estadosBloque as $destino) {
                    // Excepción del Primer Bloque: El Bloque 1 es el único que puede devolverse a sí mismo (reinicio).
                    if ($origen->id === $destino->id) {
                        if (!$isBloque1) continue;
                    }

                    // Generamos la transición interna
                    TransicionPermitida::updateOrCreate(
                        [
                            'estado_origen_id'  => $origen->id,
                            'estado_destino_id' => $destino->id,
                        ],
                        [
                            'es_activa'           => true,
                            'requiere_comentario' => ($destino->permite_devolucion ?? false),
                            'requiere_documento'  => false,
                            'accion'              => null,
                        ]
                    );
                }
            }

            // REGLA 2: Transiciones de salida al siguiente bloque
            $bloqueNextIndex = $index + 1;
            if ($bloqueNextIndex < $bloques->count()) {
                $bloqueSiguiente   = $bloques[$bloqueNextIndex];
                
                // Buscamos el estado marcado como INICIAL en el bloque siguiente
                $estadoInicialNext = $bloqueSiguiente->estados
                    ->where('es_inicial', true)
                    ->first();

                // Si el bloque siguiente no tiene estado inicial explícito, usamos el primero
                if (!$estadoInicialNext) {
                    $estadoInicialNext = $bloqueSiguiente->estados->first();
                }

                if ($estadoInicialNext) {
                    // Los estados de salida son: tipo FINAL o es_final = true
                    $estadosSalida = $estadosBloque->filter(function ($e) {
                        return $e->tipo === 'FINAL' || $e->es_final;
                    });

                    foreach ($estadosSalida as $estadoSalida) {
                        TransicionPermitida::updateOrCreate(
                            [
                                'estado_origen_id'  => $estadoSalida->id,
                                'estado_destino_id' => $estadoInicialNext->id,
                            ],
                            [
                                'es_activa'           => true,
                                'requiere_comentario' => false,
                                'requiere_documento'  => false,
                                'accion'              => 'PASAR_BLOQUE',
                            ]
                        );
                    }
                }
            }

            // REGLA 3: Transiciones de Devolución al bloque anterior
            if ($index > 0) {
                // Buscamos estados "rebote" (los rojos de devolución)
                $estadosDevolucion = $estadosBloque->filter(fn($e) => $e->permite_devolucion);
                if ($estadosDevolucion->isNotEmpty()) {
                    $bloqueAnterior = $bloques[$index - 1];

                    // Aterrizaje seguro: Priorizamos un estado con permite_devolucion=true en el bloque anterior
                    $estadoAterrizaje = $bloqueAnterior->estados
                        ->first(fn($e) => ($e->permite_devolucion ?? false));

                    if (!$estadoAterrizaje) {
                        // Aterrizaje alternativo: Estado inicial o el primer estado disponible
                        $estadoAterrizaje = $bloqueAnterior->estados->where('es_inicial', true)->first()
                                         ?? $bloqueAnterior->estados->first();
                    }

                    if ($estadoAterrizaje) {
                        foreach ($estadosDevolucion as $estadoRetorno) {
                            TransicionPermitida::updateOrCreate(
                                [
                                    'estado_origen_id'  => $estadoRetorno->id,
                                    'estado_destino_id' => $estadoAterrizaje->id,
                                ],
                                [
                                    'es_activa'           => true,
                                    'requiere_comentario' => true,
                                    'requiere_documento'  => false,
                                    'accion'              => 'DEVOLVER_BLOQUE',
                                ]
                            );
                        }
                    }
                }
            }

        }

        // 2. LIMPIEZA FINAL: Borrar las transiciones que quedaron inactivas (obsoletas)
        TransicionPermitida::where('es_activa', false)->delete();
    }
}

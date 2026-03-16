<?php

namespace App\Http\Controllers;

use App\Models\BloqueWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\Contrato;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
            // Generar código único
            $bloque = BloqueWorkflow::find($validated['bloque_id']);
            $codigo = strtoupper($bloque->codigo . '_' . Str::slug($validated['nombre'], '_'));

            // Asegurar unicidad de código
            $originalCodigo = $codigo;
            $counter = 1;
            while (EstadoWorkflow::withTrashed()->where('codigo', $codigo)->exists()) {
                $codigo = $originalCodigo . '_' . $counter++;
            }

            $estado = new EstadoWorkflow($validated);
            $estado->codigo = $codigo;

            // Si es inicial, quitar inicial a otros del mismo bloque
            if ($request->boolean('es_inicial')) {
                EstadoWorkflow::where('bloque_id', $validated['bloque_id'])->update(['es_inicial' => false]);
            }

            $estado->save();

            Contrato::logManualAudit($estado, 'CREATE', "Creado estado de workflow: {$estado->nombre}", 'estados_workflow');

            return response()->json([
                'success' => true,
                'message' => 'Estado creado correctamente. Nota: Las transiciones deben ser regeneradas.',
                'reload' => true
            ]);
        } catch (\Exception $e) {
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
            'contabiliza_tiempo' => 'boolean',
            'afecta_indicadores' => 'boolean',
            'es_activo' => 'boolean',
        ]);

        try {
            // Si es inicial, quitar inicial a otros del mismo bloque
            if ($request->boolean('es_inicial')) {
                EstadoWorkflow::where('bloque_id', $estado->bloque_id)
                    ->where('id', '!=', $id)
                    ->update(['es_inicial' => false]);
            }

            $estado->update($validated);

            Contrato::logManualAudit($estado, 'UPDATE', "Actualizado estado de workflow: {$estado->nombre}", 'estados_workflow');

            return response()->json(['success' => true, 'message' => 'Estado actualizado correctamente']);
        } catch (\Exception $e) {
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
}

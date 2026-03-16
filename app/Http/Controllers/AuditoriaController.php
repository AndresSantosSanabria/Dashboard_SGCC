<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403);
        }
        // Registrar lectura de historial (Auditoría)
        Contrato::logManualAudit(null, 'READ', 'El usuario consultó el historial de auditoría', 'auditorias');

        $query = Auditoria::with('usuario')->latest();

        if ($request->filled('tabla')) {
            $query->where('tabla_afectada', 'LIKE', '%'.$request->tabla.'%');
        }

        if ($request->filled('accion')) {
            if ($request->accion === 'FAILURE') {
                $query->where('accion', 'LIKE', 'FAILURE%');
            } elseif (in_array($request->accion, ['FAILURE_DATABASE', 'FAILURE_SERVER'])) {
                $query->where('accion', $request->accion);
            } else {
                $query->where('accion', $request->accion);
            }
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        $auditorias = $query->paginate(20);

        // Obtenemos las tablas que tienen registros de auditoría de forma dinámica
        // pero también podemos sugerir las más importantes si la lista está vacía
        $tablas = Auditoria::select('tabla_afectada')
            ->distinct()
            ->orderBy('tabla_afectada')
            ->pluck('tabla_afectada');

        return view('configuracion.auditoria.index', compact('auditorias', 'tablas'));
    }

    public function show($id)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $auditoria = Auditoria::with('usuario')->findOrFail($id);

        return response()->json($auditoria);
    }
}

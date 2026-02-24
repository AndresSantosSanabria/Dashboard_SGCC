<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Contrato;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        // Registrar lectura de historial (Auditoría)
        Contrato::logManualAudit(null, 'READ', 'El usuario consultó el historial de auditoría', 'auditorias');

        $query = Auditoria::with('usuario')->latest();

        if ($request->filled('tabla')) {
            $query->where('tabla_afectada', 'LIKE', '%' . $request->tabla . '%');
        }

        if ($request->filled('accion')) {
            if ($request->accion === 'FAILURE') {
                $query->where('accion', 'LIKE', 'FAILURE%');
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
        $auditoria = Auditoria::with('usuario')->findOrFail($id);
        return response()->json($auditoria);
    }
}

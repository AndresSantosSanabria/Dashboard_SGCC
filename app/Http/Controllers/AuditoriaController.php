<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Auditoria::with('usuario')->latest();

        if ($request->filled('tabla')) {
            $query->where('tabla_afectada', $request->tabla);
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        $auditorias = $query->paginate(20);
        $tablas = Auditoria::select('tabla_afectada')->distinct()->pluck('tabla_afectada');

        return view('configuracion.auditoria.index', compact('auditorias', 'tablas'));
    }

    public function show($id)
    {
        $auditoria = Auditoria::with('usuario')->findOrFail($id);
        return response()->json($auditoria);
    }
}

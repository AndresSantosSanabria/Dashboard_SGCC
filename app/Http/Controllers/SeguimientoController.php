<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Modalidad;
use App\Models\Supervisor;
use App\Models\Contratista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeguimientoController extends Controller
{
    public function index(Request $request)
    {
        $query = Contrato::with(['contratista', 'supervisor', 'modalidad', 'planta', 'concepto']);

        // Filtros dinámica
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_contrato', 'LIKE', "%{$search}%")
                    ->orWhereHas('contratista', function ($sq) use ($search) {
                        $sq->where('razon_social', 'LIKE', "%{$search}%")
                            ->orWhere('representante_legal', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->has('supervisor_id') && $request->supervisor_id != '') {
            $query->where('supervisor_id', $request->supervisor_id);
        }

        if ($request->has('modalidad_id') && $request->modalidad_id != '') {
            $query->where('modalidad_id', $request->modalidad_id);
        }

        if ($request->has('estado_cumplimiento') && $request->estado_cumplimiento == 'ROJO') {
            $query->where(function ($q) {
                $q->where('estudios_previos_status', 'ROJO')
                    ->orWhere('idoneidad_status', 'ROJO')
                    ->orWhere('clausulado_status', 'ROJO')
                    ->orWhere('rpc_status', 'ROJO')
                    ->orWhere('acta_inicio_status', 'ROJO')
                    ->orWhere('delegacion_status', 'ROJO')
                    ->orWhere('poliza_status', 'ROJO');
            });
        }

        $contratos = $query->latest()->paginate(15);

        $supervisores = Supervisor::all();
        $modalidades = Modalidad::all();
        $contratistas = Contratista::all();

        if ($request->ajax()) {
            return view('seguimiento.partials.table', compact('contratos'))->render();
        }

        return view('seguimiento.index', compact('contratos', 'supervisores', 'modalidades', 'contratistas'));
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:contratos,id',
            'field' => 'required|string',
            'status' => 'required|string|in:OK,PENDIENTE,ROJO,NA'
        ]);

        $contrato = Contrato::findOrFail($request->id);
        $contrato->{$request->field} = $request->status;
        $contrato->save();

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_proceso' => 'nullable|string',
            'numero_contrato' => 'required|string|unique:contratos,numero_contrato',
            'modalidad_id' => 'nullable|exists:modalidades,id',
            'contratista_id' => 'required|exists:contratistas,id',
            'supervisor_id' => 'nullable|exists:supervisores,id',
            'objeto' => 'nullable|string',
            'monto_total' => 'required|numeric',
            'link_secop' => 'nullable|url',
            // Default statuses will be handled by DB defaults, but can be passed here
        ]);

        Contrato::create($validated);

        return redirect()->back()->with('success', 'Contrato registrado exitosamente.');
    }
}

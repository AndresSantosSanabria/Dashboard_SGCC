<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Festivo;
use App\Models\Configuracion;
use App\Models\Usuario;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BusinessTimeService;

class AlertaAdminController extends Controller
{
    // ── Panel principal ──────────────────────────────────────────────
    public function index()
    {
        $claves = [
            'ALERTA_ESTANCAMIENTO_MINUTOS',
            'ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS',
            'ALERTA_ESTANCAMIENTO_ACTIVA',
            'ALERTA_ESTANCAMIENTO_MSG_WARNING',
            'ALERTA_ESTANCAMIENTO_MSG_DANGER',
        ];

        $configuraciones = Configuracion::whereIn('clave', $claves)->get()->keyBy('clave');

        foreach ($claves as $clave) {
            if (!$configuraciones->has($clave)) {
                $configuraciones->put($clave, (object)['valor' => '']);
            }
        }

        $festivos = Festivo::orderBy('fecha', 'asc')->paginate(50);

        $destinatarios = DB::table('alerta_destinatarios')
            ->where('alerta_codigo', 'ALERTA_ESTANCAMIENTO')
            ->get()
            ->map(function ($d) {
                if ($d->tipo_destinatario === 'USUARIO') {
                    $u = Usuario::find($d->destinatario_id);
                    $d->label = $u?->nombre_completo ?? 'Usuario eliminado';
                } else {
                    $r = Role::find($d->destinatario_id);
                    $d->label = 'Rol: ' . ($r?->nombre ?? 'Rol eliminado');
                }
                return $d;
            });

        // Desglosar minutos totales en horas y minutos para la vista
        $limitTotal = (int)($configuraciones['ALERTA_ESTANCAMIENTO_MINUTOS']->valor ?: 0);
        $preLimitTotal = (int)($configuraciones['ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS']->valor ?: 0);

        $limitHours = floor($limitTotal / 60);
        $limitMins = $limitTotal % 60;

        $preLimitHours = floor($preLimitTotal / 60);
        $preLimitMins = $preLimitTotal % 60;

        $usuarios = Usuario::where('es_activo', true)->orderBy('primer_nombre')->get();
        $roles = Role::where('es_activo', true)->get();

        return view('configuracion.alertas.index', compact(
            'configuraciones',
            'festivos',
            'destinatarios',
            'usuarios',
            'roles',
            'limitHours',
            'limitMins',
            'preLimitHours',
            'preLimitMins'
        ));
    }

    public function saveConfig(Request $request)
    {
        $request->validate([
            'limit_hours' => 'required|integer|min:0',
            'limit_mins' => 'required|integer|min:0|max:59',
            'pre_limit_hours' => 'required|integer|min:0',
            'pre_limit_mins' => 'required|integer|min:0|max:59',
            'ALERTA_ESTANCAMIENTO_ACTIVA' => 'nullable|boolean',
            'msg_warning' => 'nullable|string|max:500',
            'msg_danger' => 'nullable|string|max:500',
        ]);

        $totalLimit = ($request->limit_hours * 60) + $request->limit_mins;
        $totalPreLimit = ($request->pre_limit_hours * 60) + $request->pre_limit_mins;

        Configuracion::where('clave', 'ALERTA_ESTANCAMIENTO_MINUTOS')
            ->update(['valor' => $totalLimit]);

        Configuracion::where('clave', 'ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS')
            ->update(['valor' => $totalPreLimit]);

        $activa = $request->has('ALERTA_ESTANCAMIENTO_ACTIVA') ? 'true' : 'false';
        Configuracion::where('clave', 'ALERTA_ESTANCAMIENTO_ACTIVA')->update(['valor' => $activa]);

        if ($request->has('msg_warning')) {
            Configuracion::where('clave', 'ALERTA_ESTANCAMIENTO_MSG_WARNING')->update(['valor' => $request->msg_warning]);
        }
        if ($request->has('msg_danger')) {
            Configuracion::where('clave', 'ALERTA_ESTANCAMIENTO_MSG_DANGER')->update(['valor' => $request->msg_danger]);
        }

        return response()->json(['success' => true, 'message' => 'Configuración guardada correctamente.']);
    }

    // ── Festivos ─────────────────────────────────────────────────────
    public function storeFestivo(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date|unique:festivos,fecha',
            'descripcion' => 'nullable|string|max:200',
        ]);

        Festivo::create($request->only('fecha', 'descripcion'));
        return response()->json(['success' => true, 'message' => 'Festivo registrado.']);
    }

    public function destroyFestivo($id)
    {
        Festivo::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Festivo eliminado.']);
    }

    public function syncFestivos(BusinessTimeService $service)
    {
        $year = date('Y');
        $festivos = $service->getColombianHolidays($year);
        $festivosNext = $service->getColombianHolidays($year + 1);
        $allFestivos = array_merge($festivos, $festivosNext);

        $count = 0;
        foreach ($allFestivos as $fecha) {
            $exists = Festivo::where('fecha', $fecha)->exists();
            if (!$exists) {
                Festivo::create([
                    'fecha' => $fecha,
                    'descripcion' => 'Festivo automático de Colombia'
                ]);
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Se han sincronizado {$count} nuevos festivos correctamente."
        ]);
    }

    // ── Destinatarios ────────────────────────────────────────────────
    public function storeDestinatario(Request $request)
    {
        $request->validate([
            'tipo_destinatario' => 'required|in:USUARIO,ROL',
            'destinatario_id' => 'required|integer',
        ]);

        $existente = DB::table('alerta_destinatarios')
            ->where('alerta_codigo', 'ALERTA_ESTANCAMIENTO')
            ->where('tipo_destinatario', $request->tipo_destinatario)
            ->where('destinatario_id', $request->destinatario_id)
            ->exists();

        if ($existente) {
            return response()->json(['success' => false, 'message' => 'Este destinatario ya está registrado.'], 422);
        }

        DB::table('alerta_destinatarios')->insert([
            'alerta_codigo' => 'ALERTA_ESTANCAMIENTO',
            'tipo_destinatario' => $request->tipo_destinatario,
            'destinatario_id' => $request->destinatario_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Destinatario añadido.']);
    }

    public function destroyDestinatario($id)
    {
        DB::table('alerta_destinatarios')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Destinatario eliminado.']);
    }
}

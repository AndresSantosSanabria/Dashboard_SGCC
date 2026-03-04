<?php

namespace App\Services;

use App\Models\CuentaCobro;
use App\Models\Alerta;
use App\Models\Configuracion;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;

class StagnationService
{
    protected $businessTime;

    public function __construct(BusinessTimeService $businessTime)
    {
        $this->businessTime = $businessTime;
    }

    public function checkAll()
    {
        // SOLO ejecuamos el cálculo pesado una vez cada 5 minutos para no saturar
        $lockKey = 'stagnation_check_active';
        if (Cache::has($lockKey)) {
            return 0;
        }
        
        Cache::put($lockKey, true, now()->addMinutes(5));

        $isActive = Configuracion::where('clave', 'ALERTA_ESTANCAMIENTO_ACTIVA')->first()?->valor_tipado ?? false;
        if (!$isActive) {
            return 0;
        }

        $limitMinutes = Configuracion::where('clave', 'ALERTA_ESTANCAMIENTO_MINUTOS')->first()?->valor_tipado ?? 2880;
        $preLimitMinutes = Configuracion::where('clave', 'ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS')->first()?->valor_tipado ?? 120;

        $cuentas = CuentaCobro::where('finalizada', false)
            ->with(['contrato.contratista', 'estadoActual', 'bloqueActual', 'responsableActual'])
            ->get();

        $count = 0;
        foreach ($cuentas as $cuenta) {
            // EXCLUSIÓN: No notificar si el estado es 'Sin trámite'
            $nombreEstado = strtolower($cuenta->estadoActual?->nombre ?? '');
            if (str_contains($nombreEstado, 'sin tramite') || str_contains($nombreEstado, 'sin trámite')) {
                continue;
            }

            $ultimoMovimiento = DB::table('historial_workflow')
                ->where('cuenta_cobro_id', $cuenta->id)
                ->orderBy('fecha_transicion', 'desc')
                ->first();

            $fechaInicio = $ultimoMovimiento ? Carbon::parse($ultimoMovimiento->fecha_transicion) : $cuenta->created_at;
            $ahora = Carbon::now();

            $segundosTranscurridos = $this->businessTime->getWorkingSecondsBetween($fechaInicio, $ahora);
            $minutosTranscurridos = $segundosTranscurridos / 60;

            if ($minutosTranscurridos >= $limitMinutes) {
                $tiempoFormateado = $this->businessTime->formatInterval($segundosTranscurridos);
                $this->generarAlertas($cuenta, $tiempoFormateado, $limitMinutes, 'DANGER');
                $count++;
            } elseif ($minutosTranscurridos >= ($limitMinutes - $preLimitMinutes)) {
                $tiempoFormateado = $this->businessTime->formatInterval($segundosTranscurridos);
                $this->generarAlertas($cuenta, $tiempoFormateado, $limitMinutes, 'WARNING');
                $count++;
            }
        }

        return $count;
    }

    protected function generarAlertas($cuenta, $tiempoLegible, $limitMinutes, $nivel = 'WARNING')
    {
        $claveMsg = ($nivel === 'DANGER') ? 'ALERTA_ESTANCAMIENTO_MSG_DANGER' : 'ALERTA_ESTANCAMIENTO_MSG_WARNING';
        $plantilla = Configuracion::where('clave', $claveMsg)->first()?->valor 
            ?? (($nivel === 'DANGER') ? "⚠️ ALERTA CRÍTICA: El contrato {numero_contrato} ({contratista}) lleva {tiempo} laborables estancado en {estado}." : "⏳ PRE-AVISO: El contrato {numero_contrato} ({contratista}) lleva {tiempo} laborables estancado en {estado}.");

        $mensaje = str_replace(
            ['{numero_contrato}', '{contratista}', '{tiempo}', '{estado}'],
            [$cuenta->contrato->numero_contrato, $cuenta->contrato->contratista->nombre_completo, $tiempoLegible, $cuenta->estadoActual->nombre],
            $plantilla
        );

        $destinatarios = DB::table('alerta_destinatarios')
            ->where('alerta_codigo', 'ALERTA_ESTANCAMIENTO')
            ->get();

        $userIds = [];
        foreach ($destinatarios as $dest) {
            if ($dest->tipo_destinatario === 'USUARIO') {
                $userIds[] = $dest->destinatario_id;
            } elseif ($dest->tipo_destinatario === 'ROL') {
                $users = Usuario::where('rol_id', $dest->destinatario_id)->pluck('id')->toArray();
                $userIds = array_merge($userIds, $users);
            }
        }

        if (empty($userIds)) {
            $userIds = Usuario::whereHas('rol', function ($q) {
                $q->where('permisos->es_admin', true);
            })->pluck('id')->toArray();
        }

        if ($cuenta->responsable_actual_id) {
            $userIds[] = $cuenta->responsable_actual_id;
        }

        $userIds = array_unique($userIds);

        foreach ($userIds as $userId) {
            $alertaSinLeer = Alerta::where('cuenta_cobro_id', $cuenta->id)
                ->where('usuario_destino_id', $userId)
                ->where('leida', false)
                ->where('tipo_alerta', 'ESTANCAMIENTO')
                ->where('nivel', $nivel)
                ->where('mensaje', 'like', "%'{$cuenta->estadoActual->nombre}'%")
                ->first();

            if ($alertaSinLeer) {
                $alertaSinLeer->update([
                    'mensaje' => $mensaje,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $enviadaRecientemente = Alerta::where('cuenta_cobro_id', $cuenta->id)
                    ->where('usuario_destino_id', $userId)
                    ->where('tipo_alerta', 'ESTANCAMIENTO')
                    ->where('nivel', $nivel)
                    ->where('created_at', '>', Carbon::now()->subMinutes(1)) 
                    ->exists();

                if (!$enviadaRecientemente) {
                    Alerta::create([
                        'cuenta_cobro_id' => $cuenta->id,
                        'nivel' => $nivel,
                        'tipo_alerta' => 'ESTANCAMIENTO',
                        'mensaje' => $mensaje,
                        'leida' => false,
                        'usuario_destino_id' => $userId,
                    ]);
                }
            }
        }
    }
}

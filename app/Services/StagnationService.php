<?php

namespace App\Services;

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
        // SOLO ejecuamos el cálculo pesado una vez cada 5 minutos para no saturar al usuario
        $lockKey = 'stagnation_check_active';
        if (Cache::has($lockKey)) {
            return 0;
        }

        Cache::put($lockKey, true, now()->addMinutes(5));

        // 1. CARGA DE CONFIGURACIONES (Mínima carga en memoria)
        $configs = DB::table('configuraciones')->whereIn('clave', [
            'ALERTA_ESTANCAMIENTO_ACTIVA',
            'ALERTA_ESTANCAMIENTO_MINUTOS',
            'ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS',
            'ALERTA_ESTANCAMIENTO_MSG_DANGER',
            'ALERTA_ESTANCAMIENTO_MSG_WARNING'
        ])->pluck('valor', 'clave');

        $activa = $configs->get('ALERTA_ESTANCAMIENTO_ACTIVA') ?? '0';
        if (!in_array($activa, ['1', 'true', true], true)) return 0;


        $limitMins = (int)($configs->get('ALERTA_ESTANCAMIENTO_MINUTOS') ?? 2880);
        $warningMins = (int)($configs->get('ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS') ?? 0);
        
        // El umbral de activación es el menor entre el aviso y el límite crítico.
        $minThresholdMins = ($warningMins > 0) ? min($limitMins, $warningMins) : $limitMins;

        // 2. DESTINATARIOS BASE (Administradores y Supervisores)
        $baseUserIds = DB::table('alerta_destinatarios')
            ->where('alerta_codigo', 'ALERTA_ESTANCAMIENTO')
            ->get()
            ->flatMap(function ($d) {
                if ($d->tipo_destinatario === 'USUARIO') return [$d->destinatario_id];
                return DB::table('usuarios')->where('rol_id', $d->destinatario_id)->pluck('id')->toArray();
            })->unique()->filter()->toArray();

        // Fallback: si no hay destinatarios configurados, notificar a todos los admins activos
        if (empty($baseUserIds)) {
            $baseUserIds = DB::table('usuarios')
                ->join('roles', 'usuarios.rol_id', '=', 'roles.id')
                ->where('usuarios.es_activo', true)
                ->where('roles.nombre', 'like', '%admin%')
                ->pluck('usuarios.id')
                ->toArray();
        }


        // 3. CONSULTA MAESTRA (Excluir lo que ya está alertado - Filtrado Drástico)
        $ahora = Carbon::now();
        $bufferDate = $ahora->copy()->subMinutes($minThresholdMins);

        $alertasBatch = [];
        $processedCount = 0;

        DB::table('cuentas_cobro')
            ->join('contratos', 'cuentas_cobro.contrato_id', '=', 'contratos.id')
            ->join('contratistas', 'contratos.contratista_id', '=', 'contratistas.id')
            ->join('estados_workflow', 'cuentas_cobro.estado_actual_id', '=', 'estados_workflow.id')
            // JOIN EXCLUYENTE: Si ya hay una alerta activa para este contrato, no hagamos NADA.
            // JOIN EXCLUYENTE: Solo excluimos si ya existe una alerta de nivel DANGER sin leer.
            // Esto permite que el sistema detecte si una cuenta pasó de WARNING a DANGER.
            ->leftJoin('alertas', function ($join) {
                $join->on('alertas.cuenta_cobro_id', '=', 'cuentas_cobro.id')
                    ->where('alertas.tipo_alerta', '=', 'ESTANCAMIENTO')
                    ->where('alertas.leida', '=', false)
                    ->where('alertas.nivel', '=', 'DANGER');
            })
            ->where('cuentas_cobro.finalizada', false)
            ->where('cuentas_cobro.updated_at', '<=', $ahora->copy()->subMinutes($minThresholdMins))
            ->where('estados_workflow.contabiliza_tiempo', true)
            ->whereNull('alertas.id') 
            ->select(
                'cuentas_cobro.id',
                'cuentas_cobro.updated_at',
                'cuentas_cobro.responsable_actual_id',
                'contratos.numero_contrato',
                'contratistas.razon_social',
                'estados_workflow.nombre as estado_nombre'
            )
            ->orderBy('cuentas_cobro.id')
            ->chunk(200, function ($cuentas) use (&$alertasBatch, &$processedCount, $limitMins, $warningMins, $configs, $baseUserIds, $ahora) {
                foreach ($cuentas as $cuenta) {
                    $segundos = $this->businessTime->getWorkingSecondsBetween($cuenta->updated_at, $ahora);
                    $mins = $segundos / 60;

                    // Lógica de umbrales absolutos: DANGER tiene prioridad sobre WARNING.
                    $nivel = ($mins >= $limitMins) ? 'DANGER' : (($warningMins > 0 && $mins >= $warningMins) ? 'WARNING' : null);
                    if (!$nivel) continue;

                    $msgKey = ($nivel === 'DANGER') ? 'ALERTA_ESTANCAMIENTO_MSG_DANGER' : 'ALERTA_ESTANCAMIENTO_MSG_WARNING';
                    $msg = str_replace(
                        ['{numero_contrato}', '{contratista}', '{tiempo}', '{estado}'],
                        [$cuenta->numero_contrato, $cuenta->razon_social, $this->businessTime->formatInterval($segundos), $cuenta->estado_nombre],
                        $configs->get($msgKey) ?? "Alerta de estancamiento: $cuenta->numero_contrato."
                    );

                    $targets = array_unique(array_merge($baseUserIds, [$cuenta->responsable_actual_id]));
                    foreach ($targets as $uid) {
                        if (!$uid) continue;
                        $alertasBatch[] = [
                            'cuenta_cobro_id'    => $cuenta->id,
                            'usuario_destino_id' => $uid,
                            'tipo_alerta'        => 'ESTANCAMIENTO',
                            'nivel'              => $nivel,
                            'mensaje'            => $msg,
                            'leida'              => 0,
                            'created_at'         => $ahora,
                            'updated_at'         => $ahora
                        ];
                    }
                    $processedCount++;

                    // Inserción masiva cada 1000 registros para velocidad de disco
                    if (count($alertasBatch) >= 1000) {
                        DB::table('alertas')->upsert($alertasBatch, ['cuenta_cobro_id', 'usuario_destino_id', 'tipo_alerta', 'nivel'], ['leida', 'mensaje', 'updated_at']);
                        $alertasBatch = [];
                    }
                }
            });

        if (!empty($alertasBatch)) {
            DB::table('alertas')->upsert($alertasBatch, ['cuenta_cobro_id', 'usuario_destino_id', 'tipo_alerta', 'nivel'], ['leida', 'mensaje', 'updated_at']);
        }

        return $processedCount;
    }
}

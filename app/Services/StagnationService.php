<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * SERVICIO DE ESTANCAMIENTO - LÓGICA DE REPOSO POR ESTADO
 *
 * Lógica correcta de cálculo de reposo:
 *
 *   tiempo_en_estado_actual = NOW() - fecha_ultimo_cambio_estado
 *
 * La fuente de verdad ES fecha_ultimo_cambio_estado en la DB.
 * No se usan contadores del cliente. No hay riesgo de reset por refresh.
 *
 * Cada estado puede tener su propio tiempo_limite_horas en la tabla
 * estados_workflow. Si no está configurado, se usa la config global
 * ALERTA_ESTANCAMIENTO_MINUTOS.
 */
class StagnationService
{
    protected BusinessTimeService $businessTime;

    public function __construct(BusinessTimeService $businessTime)
    {
        $this->businessTime = $businessTime;
    }

    /**
     * CHEQUEO MASIVO: Revisa todos los contratos activos.
     *
     * Usa fecha_ultimo_cambio_estado (contador volátil) para calcular el
     * tiempo en el estado actual. NO usa tiempo_total_proceso_segundos
     * (que es el histórico acumulado de todos los estados anteriores).
     */
    public function checkAll(): int
    {
        $lockKey = 'stagnation_check_active';
        if (Cache::has($lockKey)) {
            return 0;
        }
        Cache::put($lockKey, true, now()->addMinutes(5));

        // Cargar configuración global
        $configs = DB::table('configuraciones')
            ->whereIn('clave', [
                'ALERTA_ESTANCAMIENTO_ACTIVA',
                'ALERTA_ESTANCAMIENTO_MINUTOS',
                'ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS',
                'ALERTA_ESTANCAMIENTO_MSG_DANGER',
                'ALERTA_ESTANCAMIENTO_MSG_WARNING',
            ])
            ->pluck('valor', 'clave');

        $activa = $configs->get('ALERTA_ESTANCAMIENTO_ACTIVA') ?? '0';
        if (! in_array($activa, ['1', 'true', true], true)) {
            return 0;
        }

        // Límite global en minutos (fallback si el estado no tiene tiempo_limite_horas)
        $limitGlobalMins   = (int) ($configs->get('ALERTA_ESTANCAMIENTO_MINUTOS') ?? 2880);
        $warningGlobalMins = (int) ($configs->get('ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS') ?? 0);

        // El umbral más bajo determina hasta dónde filtramos en SQL
        $minThreshold = ($warningGlobalMins > 0)
            ? min($limitGlobalMins, $warningGlobalMins)
            : $limitGlobalMins;

        // Destinatarios base
        $baseUserIds = $this->getBaseUserIds();
        $soportaPausaSupervisor = Schema::hasColumn('cuentas_cobro', 'pausa_gestion_supervisor_desde');

        $ahora       = Carbon::now();
        $alertasBatch = [];
        $count        = 0;

        /**
         * CONSULTA MAESTRA:
         * Solo traemos cuentas cuyo fecha_ultimo_cambio_estado es anterior
         * al umbral mínimo (optimización de índice).
         * La comparación exacta por estado se hace en PHP para poder
         * usar el tiempo_limite_horas específico de cada estado.
         */
        DB::table('cuentas_cobro as cc')
            ->join('contratos', 'cc.contrato_id', '=', 'contratos.id')
            ->join('contratistas', 'contratos.contratista_id', '=', 'contratistas.id')
            ->join('estados_workflow as ew', 'cc.estado_actual_id', '=', 'ew.id')
            // Solo excluimos si ya hay DANGER activa (permitimos WARNING → DANGER)
            ->leftJoin('alertas', function ($join) {
                $join->on('alertas.cuenta_cobro_id', '=', 'cc.id')
                     ->where('alertas.tipo_alerta', '=', 'ESTANCAMIENTO')
                     ->where('alertas.leida', '=', false)
                     ->where('alertas.nivel', '=', 'DANGER');
            })
            ->where('cc.finalizada', false)
            ->where('ew.contabiliza_tiempo', true)
            // Pre-filtro SQL: fecha_ultimo_cambio_estado supera el umbral mínimo
            ->where('cc.fecha_ultimo_cambio_estado', '<=',
                $ahora->copy()->subMinutes($minThreshold))
            ->whereNull('alertas.id')
            ->select(
                'cc.id',
                'cc.fecha_ultimo_cambio_estado',
                'cc.responsable_actual_id',
                'contratos.numero_contrato',
                'contratistas.razon_social',
                'ew.nombre as estado_nombre',
                // Límite propio del estado (NULL = usar global)
                'ew.tiempo_limite_horas as limite_estado_horas'
            )
            ->orderBy('cc.id')
            ->chunk(200, function ($cuentas) use (
                &$alertasBatch, &$count,
                $limitGlobalMins, $warningGlobalMins,
                $configs, $baseUserIds, $ahora, $soportaPausaSupervisor
            ) {
                foreach ($cuentas as $cuenta) {

                    // ──────────────────────────────────────────────────────
                    // CÁLCULO CORRECTO: tiempo en el ESTADO ACTUAL
                    // Fuente: fecha_ultimo_cambio_estado (contador volátil)
                    // No contaminado por tiempos de estados anteriores.
                    // ──────────────────────────────────────────────────────
                    if (! $cuenta->fecha_ultimo_cambio_estado) continue;
                    if ($soportaPausaSupervisor && ! empty($cuenta->pausa_gestion_supervisor_desde)) continue;

                    $segundosEnEstado = $this->businessTime->getWorkingSecondsBetween(
                        Carbon::parse($cuenta->fecha_ultimo_cambio_estado),
                        $ahora
                    );
                    $minsEnEstado = $segundosEnEstado / 60;

                    // Límite específico del estado (convierte horas → minutos)
                    $limitMins   = $cuenta->limite_estado_horas !== null
                        ? (float) $cuenta->limite_estado_horas * 60
                        : $limitGlobalMins;

                    $warningMins = $warningGlobalMins; // El aviso siempre es global por ahora

                    // Determinar nivel de alerta
                    $nivel = ($minsEnEstado >= $limitMins)
                        ? 'DANGER'
                        : (($warningMins > 0 && $minsEnEstado >= $warningMins) ? 'WARNING' : null);

                    if (! $nivel) continue;

                    $msgKey = ($nivel === 'DANGER')
                        ? 'ALERTA_ESTANCAMIENTO_MSG_DANGER'
                        : 'ALERTA_ESTANCAMIENTO_MSG_WARNING';

                    $msg = str_replace(
                        ['{numero_contrato}', '{contratista}', '{tiempo}', '{estado}'],
                        [
                            $cuenta->numero_contrato,
                            $cuenta->razon_social,
                            $this->businessTime->formatInterval($segundosEnEstado),
                            $cuenta->estado_nombre,
                        ],
                        $configs->get($msgKey) ?? "Alerta de estancamiento: {$cuenta->numero_contrato}."
                    );

                    $targets = array_unique(array_merge($baseUserIds, [$cuenta->responsable_actual_id]));
                    foreach ($targets as $uid) {
                        if (! $uid) continue;
                        $alertasBatch[] = [
                            'cuenta_cobro_id'    => $cuenta->id,
                            'usuario_destino_id' => $uid,
                            'tipo_alerta'        => 'ESTANCAMIENTO',
                            'nivel'              => $nivel,
                            'mensaje'            => $msg,
                            'leida'              => 0,
                            'created_at'         => $ahora,
                            'updated_at'         => $ahora,
                        ];
                    }
                    $count++;

                    if (count($alertasBatch) >= 1000) {
                        DB::table('alertas')->upsert(
                            $alertasBatch,
                            ['cuenta_cobro_id', 'usuario_destino_id', 'tipo_alerta', 'nivel'],
                            ['leida', 'mensaje', 'updated_at']
                        );
                        $alertasBatch = [];
                    }
                }
            });

        if (! empty($alertasBatch)) {
            DB::table('alertas')->upsert(
                $alertasBatch,
                ['cuenta_cobro_id', 'usuario_destino_id', 'tipo_alerta', 'nivel'],
                ['leida', 'mensaje', 'updated_at']
            );
        }

        return $count;
    }

    /**
     * CHEQUEO INDIVIDUAL: Verifica el estancamiento de una sola cuenta.
     *
     * Llamado por CheckStagnationJob después del delay configurado por estado.
     * Compara fecha_ultimo_cambio_estado con el tiempo_limite_horas del estado.
     */
    public function runCheckOnAccount(int $cuentaId): bool
    {
        $ahora = Carbon::now();

        $configs = DB::table('configuraciones')
            ->whereIn('clave', [
                'ALERTA_ESTANCAMIENTO_ACTIVA',
                'ALERTA_ESTANCAMIENTO_MINUTOS',
                'ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS',
                'ALERTA_ESTANCAMIENTO_MSG_DANGER',
                'ALERTA_ESTANCAMIENTO_MSG_WARNING',
            ])
            ->pluck('valor', 'clave');

        if (! in_array($configs->get('ALERTA_ESTANCAMIENTO_ACTIVA'), ['1', 'true', true], true)) {
            return false;
        }

        $cuenta = DB::table('cuentas_cobro as cc')
            ->join('contratos', 'cc.contrato_id', '=', 'contratos.id')
            ->join('contratistas', 'contratos.contratista_id', '=', 'contratistas.id')
            ->join('estados_workflow as ew', 'cc.estado_actual_id', '=', 'ew.id')
            ->where('cc.id', $cuentaId)
            ->where('cc.finalizada', false)
            ->select(
                'cc.*',
                'contratos.numero_contrato',
                'contratistas.razon_social',
                'ew.nombre as estado_nombre',
                'ew.contabiliza_tiempo',
                'ew.tiempo_limite_horas as limite_estado_horas'
            )
            ->first();

        if (! $cuenta || ! $cuenta->contabiliza_tiempo) {
            return false;
        }

        if (Schema::hasColumn('cuentas_cobro', 'pausa_gestion_supervisor_desde') && ! empty($cuenta->pausa_gestion_supervisor_desde)) {
            return false;
        }

        if (! $cuenta->fecha_ultimo_cambio_estado) {
            return false;
        }

        // ──────────────────────────────────────────────────────────────────
        // FUENTE DE VERDAD: fecha_ultimo_cambio_estado
        // Este campo se actualizó cuando el contrato entró al estado actual.
        // Solo mide el tiempo en el estado actual (no estados anteriores).
        // ──────────────────────────────────────────────────────────────────
        $segundosEnEstado = $this->businessTime->getWorkingSecondsBetween(
            Carbon::parse($cuenta->fecha_ultimo_cambio_estado),
            $ahora
        );
        $minsEnEstado = $segundosEnEstado / 60;

        // Límite específico del estado o fallback global
        $limitGlobalMins = (int) ($configs->get('ALERTA_ESTANCAMIENTO_MINUTOS') ?? 2880);
        $limitMins = $cuenta->limite_estado_horas !== null
            ? (float) $cuenta->limite_estado_horas * 60
            : $limitGlobalMins;

        $warningMins = (int) ($configs->get('ALERTA_ESTANCAMIENTO_PREAVISO_MINUTOS') ?? 0);

        $nivel = ($minsEnEstado >= $limitMins)
            ? 'DANGER'
            : (($warningMins > 0 && $minsEnEstado >= $warningMins) ? 'WARNING' : null);

        if (! $nivel) {
            return false;
        }

        // No duplicar alertas del mismo nivel
        $existe = DB::table('alertas')
            ->where('cuenta_cobro_id', $cuentaId)
            ->where('tipo_alerta', 'ESTANCAMIENTO')
            ->where('nivel', $nivel)
            ->where('leida', false)
            ->exists();

        if ($existe) {
            return false;
        }

        $msgKey = ($nivel === 'DANGER')
            ? 'ALERTA_ESTANCAMIENTO_MSG_DANGER'
            : 'ALERTA_ESTANCAMIENTO_MSG_WARNING';

        $msg = str_replace(
            ['{numero_contrato}', '{contratista}', '{tiempo}', '{estado}'],
            [
                $cuenta->numero_contrato,
                $cuenta->razon_social,
                $this->businessTime->formatInterval($segundosEnEstado),
                $cuenta->estado_nombre,
            ],
            $configs->get($msgKey) ?? "Alerta de estancamiento."
        );

        $baseUserIds = $this->getBaseUserIds();
        $targets     = array_unique(array_merge($baseUserIds, [$cuenta->responsable_actual_id]));

        $alertas = [];
        foreach ($targets as $uid) {
            if (! $uid) continue;
            $alertas[] = [
                'cuenta_cobro_id'    => $cuentaId,
                'usuario_destino_id' => $uid,
                'tipo_alerta'        => 'ESTANCAMIENTO',
                'nivel'              => $nivel,
                'mensaje'            => $msg,
                'leida'              => 0,
                'created_at'         => $ahora,
                'updated_at'         => $ahora,
            ];
        }

        if (! empty($alertas)) {
            DB::table('alertas')->upsert(
                $alertas,
                ['cuenta_cobro_id', 'usuario_destino_id', 'tipo_alerta', 'nivel'],
                ['leida', 'mensaje', 'updated_at']
            );
            Log::info("[Stagnation] Alerta {$nivel} generada. " .
                "CuentaId={$cuentaId}, MinEnEstado={$minsEnEstado}, Limite={$limitMins}");
            return true;
        }

        return false;
    }

    /**
     * Obtiene la lista de IDs de usuarios destinatarios base de alertas.
     */
    private function getBaseUserIds(): array
    {
        $ids = DB::table('alerta_destinatarios')
            ->where('alerta_codigo', 'ALERTA_ESTANCAMIENTO')
            ->get()
            ->flatMap(function ($d) {
                if ($d->tipo_destinatario === 'USUARIO') {
                    return [$d->destinatario_id];
                }
                return DB::table('usuarios')
                    ->where('rol_id', $d->destinatario_id)
                    ->pluck('id')
                    ->toArray();
            })
            ->unique()
            ->filter()
            ->toArray();

        if (empty($ids)) {
            $ids = DB::table('usuarios')
                ->join('roles', 'usuarios.rol_id', '=', 'roles.id')
                ->where('usuarios.es_activo', true)
                ->where('roles.nombre', 'like', '%admin%')
                ->pluck('usuarios.id')
                ->toArray();
        }

        return $ids;
    }
}

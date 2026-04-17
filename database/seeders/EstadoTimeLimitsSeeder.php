<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SEEDER: Configuración de Tiempos Límite por Estado del Workflow
 *
 * Este seeder alimenta el campo tiempo_limite_horas de cada estado en
 * estados_workflow. Elimina los valores "quemados" del código.
 *
 * Los límites aquí definidos son los valores de negocio recomendados.
 * Se pueden cambiar en cualquier momento desde la tabla estados_workflow
 * sin necesidad de tocar el código fuente.
 *
 * NULL = el estado hereda el límite global (ALERTA_ESTANCAMIENTO_MINUTOS)
 */
class EstadoTimeLimitsSeeder extends Seeder
{
    /**
     * Mapa de código_estado => tiempo_limite_horas
     *
     * Criterio de diseño:
     *  - Estados INICIALES (en espera de entrada) → límites más cortos (2h)
     *  - Estados EN_PROCESO (alguien trabajando) → límites medios (4h)
     *  - Estados APROBADO/FINAL → NULL (no generan alerta, ya pasaron)
     *  - Estados DEVUELTO → límites cortos (2h) para reactivar
     *  - REV1_SIN (sin tramite) → NULL (contabiliza_tiempo=0, no aplica)
     */
    private array $limites = [

        // ─── BLOQUE 1: REVISIÓN INICIAL ───────────────────────────────────
        'REV1_SIN'    => null,   // Sin tramite: no contabiliza tiempo
        'REV1_REV'    => 2.0,    // En revisión: 2h de inactividad = alerta
        'REV1_ESP_MON'=> 24.0,   // En espera firma Moncaleano: 24h (depende de agenda)
        'REV1_PASA'   => null,   // Aprobado (ya pasó, no genera alerta)
        'REV1_DEV'    => 2.0,    // Devuelta: 2h para que el responsable la retome

        // ─── BLOQUE 2: SAP ────────────────────────────────────────────────
        'SAP_ESP'     => 4.0,    // En espera ingreso mercancía: 4h
        'SAP_OK'      => null,   // Con ingreso mercancía (completado)
        'SAP_DEV'     => 2.0,    // Devuelta de SAP: 2h

        // ─── BLOQUE 3: FACTURACIÓN ────────────────────────────────────────
        'FAC_ESP'     => 4.0,    // En espera en facturación: 4h
        'FAC_OK'      => null,   // Facturada (completado)
        'FAC_DEV'     => 2.0,    // Devuelta de facturación: 2h

        // ─── BLOQUE 4: FIRMA SECRETARIO ───────────────────────────────────
        'FIR_ESP'     => 24.0,   // En espera firma: 24h (proceso manual)
        'FIR_OK'      => null,   // Firmada (completado)
        'FIR_DEV'     => 2.0,    // Devuelta de firma: 2h

        // ─── BLOQUE 5: HACIENDA ───────────────────────────────────────────
        'HAC_ESP'     => 48.0,   // En espera Hacienda: 48h (proceso externo)
        'HAC_OK'      => null,   // Radicada en Hacienda (completado)
        'HAC_DEV'     => 4.0,    // Devuelta de Hacienda: 4h

        // ─── BLOQUE 6: FINALIZADA ─────────────────────────────────────────
        'FIN_PEND'    => 8.0,    // Por confirmar: 8h para confirmar el pago
        'FIN_OK'      => null,   // Finalizada (proceso cerrado)
    ];

    public function run(): void
    {
        $this->command->info('⏱  Configurando tiempo_limite_horas por estado...');

        $actualizados = 0;
        $omitidos     = 0;

        foreach ($this->limites as $codigo => $horas) {
            $filas = DB::table('estados_workflow')
                ->where('codigo', $codigo)
                ->update(['tiempo_limite_horas' => $horas]);

            if ($filas > 0) {
                $label = $horas !== null ? "{$horas}h" : 'NULL (global)';
                $this->command->line("  ✓ {$codigo} → {$label}");
                $actualizados++;
            } else {
                $this->command->warn("  ⚠ Estado '{$codigo}' no encontrado en BD. Omitido.");
                $omitidos++;
            }
        }

        $this->command->info("\n✅ Completado: {$actualizados} actualizados, {$omitidos} omitidos.");

        // Validación: listar estados sin límite configurado que SÍ contabilizan tiempo
        $sinLimite = DB::table('estados_workflow')
            ->where('contabiliza_tiempo', true)
            ->whereNull('tiempo_limite_horas')
            ->where('es_activo', true)
            ->pluck('codigo');

        if ($sinLimite->isNotEmpty()) {
            $this->command->warn(
                "\n⚠  Los siguientes estados activos no tienen tiempo_limite_horas " .
                "y usarán la config global:\n  " . $sinLimite->implode(', ')
            );
        }
    }
}

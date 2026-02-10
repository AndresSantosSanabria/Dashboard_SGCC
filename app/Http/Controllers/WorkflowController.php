<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    /**
     * Display the workflow management page
     */
    public function index()
    {
        $cuentas = \App\Models\CuentaCobro::with(['contrato.contratista', 'bloqueActual', 'estadoActual', 'estadoBloqueActual', 'historialWorkflow.usuarioAccion', 'historialWorkflow.estadoOrigen', 'historialWorkflow.estadoDestino'])
            ->where('finalizada', false)
            ->get();

        $workflow = [
            'bloque1' => ['nombre' => 'RADICACIÓN / REVISIÓN 1', 'color' => 'morado', 'cuentas' => []],
            'bloque2' => ['nombre' => 'SAP', 'color' => 'indigo', 'cuentas' => []],
            'bloque3' => ['nombre' => 'FACTURACIÓN', 'color' => 'verde', 'cuentas' => []],
            'bloque4' => ['nombre' => 'FIRMA SECRETARIO', 'color' => 'naranja', 'cuentas' => []],
            'bloque5' => ['nombre' => 'RADICADA EN HACIENDA (PEND)', 'color' => 'rosa', 'cuentas' => []],
            'bloque6' => ['nombre' => 'RADICADA EN HACIENDA (PASA)', 'color' => 'cian', 'cuentas' => []],
        ];

        foreach ($cuentas as $cuenta) {
            $key = $this->determinarBloqueVisual($cuenta);
            if ($key) {
                $estadoKey = $this->mapearEstadoInterno($cuenta->estadoActual?->tipo);
                $workflow[$key]['cuentas'][$estadoKey][] = $cuenta;
            }
        }

        return view('workflow', compact('workflow'));
    }

    private function determinarBloqueVisual($cuenta)
    {
        // Bloque 6: Radicada en Hacienda (Pasa)
        if ($cuenta->fecha_radicacion_hacienda && $cuenta->estadoActual?->tipo === 'FINAL' && $cuenta->bloque_actual_id == 5) {
            return 'bloque6';
        }

        // Bloque 5: Radicada en Hacienda (Pendiente)
        if ($cuenta->bloque_actual_id == 5 && $cuenta->estadoActual?->tipo === 'FINAL' && !$cuenta->fecha_radicacion_hacienda) {
            return 'bloque5';
        }

        // Bloque 4: Firma Secretario
        if ($cuenta->bloque_actual_id == 5 && $cuenta->estadoActual?->tipo !== 'FINAL') {
            return 'bloque4';
        }

        // Bloque 3: Facturación
        if ($cuenta->bloque_actual_id == 4) {
            return 'bloque3';
        }

        // Bloque 2: SAP
        if ($cuenta->bloque_actual_id == 3) {
            return 'bloque2';
        }

        // Bloque 1A: Revisión
        if ($cuenta->bloque_actual_id == 2) {
            return 'bloque1';
        }

        // Radicación (Block 1) are treated as pending Revision usually if they move quickly
        if ($cuenta->bloque_actual_id == 1) {
            return 'bloque1';
        }

        return null;
    }

    private function mapearEstadoInterno($tipoEstado)
    {
        return match ($tipoEstado) {
            'INICIAL' => 'revision',
            'EN_PROCESO' => 'proceso',
            'APROBADO', 'FINAL' => 'aprobadas',
            'DEVUELTO' => 'rechazadas',
            default => 'revision',
        };
    }
    
}

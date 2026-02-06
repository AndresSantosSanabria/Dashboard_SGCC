<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Contrato;

#[Layout('components.layouts.app')]
class ContratosReport extends Component
{
    protected $listeners = ['cuentas-actualizadas' => '$refresh'];

    public $contratosReport = [];

    public function render()
    {
        $contratos = Contrato::with([
            'contratista',
            'supervisor',
            'cuentasCobro.transiciones.estadoDestino',
            'cuentasCobro.responsableActual',
            'cuentasCobro.transiciones.usuarioAccion'
        ])->get();

        $report = $contratos->map(function($contrato) {
            $cuentas = $contrato->cuentasCobro;
            $numero_cuentas_proceso = $cuentas->where('finalizada', false)->pluck('numero_cuenta')->implode(', ');
            $numero_pagos_totales = $cuentas->sum('numero_pagos_totales');
            $numero_facturas_radicada_hacienda = $cuentas->sum('numero_facturas_radicadas');
            $ultimaCuenta = $cuentas->sortByDesc('created_at')->first();
            $porcentaje_cuentas = $ultimaCuenta?->porcentaje_cuentas;
            $mes_planilla = $ultimaCuenta?->mes_planilla_seguridad_social;
            $radicado_por = $ultimaCuenta?->radicado_por;

            $fechas_radicacion = $cuentas->pluck('fecha_radicacion')
                ->filter()
                ->map(function($f){ return $f instanceof \Carbon\Carbon ? $f->toDateString() : (string)$f; })
                ->unique()
                ->values()
                ->toArray();

            $fecha_radicacion_inicial = empty($fechas_radicacion) ? null : $fechas_radicacion[0];
            $fecha_radicacion_ultima = empty($fechas_radicacion) ? null : end($fechas_radicacion);
            $observaciones = $ultimaCuenta?->observaciones;

            $estado_primera_revision = null;
            if ($ultimaCuenta && $ultimaCuenta->transiciones->count()) {
                $firstTrans = $ultimaCuenta->transiciones->sortBy('created_at')->first();
                $estado_primera_revision = $firstTrans?->estadoDestino?->nombre;
            }

            $buscarPorNombre = function($keywords) use ($ultimaCuenta) {
                if (!$ultimaCuenta) return null;
                foreach ($ultimaCuenta->transiciones->sortBy('created_at') as $tr) {
                    foreach ((array)$keywords as $kw) {
                        if (stripos($tr->estadoDestino?->nombre ?? '', $kw) !== false) {
                            return [
                                'nombre' => $tr->estadoDestino?->nombre,
                                'fecha' => $tr->created_at?->toDateString(),
                                'responsable' => $tr->usuarioAccion?->nombreCompleto ?? null
                            ];
                        }
                    }
                }
                return null;
            };

            $devueltaOrSap = $buscarPorNombre(['DEVUELTA','SAP','INGRESO','INGRESO MERCANCIA']);
            $enFacturacion = $buscarPorNombre(['FACTURACIÓN','FACTURACION','EN FACTURACIÓN','EN FACTURACION']);
            $firmaSecretario = $buscarPorNombre(['FIRMA','SECRETARIO']);

            $radicadaHacienda = $ultimaCuenta ? ($ultimaCuenta->ultima_factura_hacienda ? true : false) : false;
            $fecha_radicacion = $ultimaCuenta?->fecha_radicacion?->toDateString();

            return [
                'numero_contrato' => $contrato->numero_contrato,
                'contratista' => $contrato->contratista?->razon_social,
                'cedula' => $contrato->contratista?->nit,
                'rp' => $contrato->rp,
                'fecha_rp' => $contrato->fecha_rp?->toDateString(),
                'valor_rp' => $contrato->valor_rp,
                'fecha_inicio' => $contrato->fecha_inicio?->toDateString(),
                'fecha_fin' => $contrato->fecha_fin?->toDateString(),
                'supervisor' => $contrato->supervisor?->nombre_completo,
                'numero_cuenta_en_proceso' => $numero_cuentas_proceso,
                'numero_pagos_totales' => $numero_pagos_totales,
                'numero_facturas_radicada_hacienda' => $numero_facturas_radicada_hacienda,
                'porcentaje_cuentas' => $porcentaje_cuentas,
                'entidad_salud' => $contrato->contratista?->entidad_salud,
                'entidad_pension' => $contrato->contratista?->entidad_pension,
                'entidad_arl' => $contrato->contratista?->entidad_arl,
                'mes_planilla_ultima_cuenta' => $mes_planilla,
                'radicado_por' => $radicado_por,
                'fecha_radicacion_inicial' => $fecha_radicacion_inicial,
                'fecha_radicacion_ultima' => $fecha_radicacion_ultima,
                'observaciones' => $observaciones,
                'estado_primera_revision' => $estado_primera_revision,
                'fecha_devuelta_revision_o_enviada_sap' => $devueltaOrSap['fecha'] ?? null,
                'enviada_ingreso_mercancia_sap' => $devueltaOrSap['nombre'] ?? null,
                // Campos ajustados para la tabla
                'responsable' => $devueltaOrSap['responsable'] ?? null,
                'fecha_envio_facturacion_devuelta' => $enFacturacion['fecha'] ?? null,
                'en_facturacion' => $enFacturacion['nombre'] ?? null,
                'responsable_facturacion' => $enFacturacion['responsable'] ?? null,
                'fecha_generacion_facturacion' => $enFacturacion['fecha'] ?? null,
                'firma_secretario' => $firmaSecretario['nombre'] ?? null,
                'fecha_firma_secretario' => $firmaSecretario['fecha'] ?? null,
                'radicada_en_hacienda' => $radicadaHacienda,
                'fecha_radicacion' => $fecha_radicacion,
                'ultima_factura_radicada_hacienda' => $ultimaCuenta?->ultima_factura_hacienda,
                'observacion_devolucion_hacienda' => $ultimaCuenta?->observaciones,
                'diferencia_cuentas' => $cuentas->sum('diferencia_cuentas'),
            ];
        });

        $this->contratosReport = $report;

        return view('livewire.contratos-report', ['rows' => $report]);
    }
}

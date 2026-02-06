<?php

namespace App\Exports;

use App\Models\Contrato;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class ContratosReportExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        $contratos = Contrato::with([
            'contratista',
            'supervisor',
            'cuentasCobro.transiciones.estadoDestino',
            'cuentasCobro.responsableActual',
            'cuentasCobro.transiciones.usuarioAccion'
        ])->get();

        $rows = $contratos->map(function($contrato) {
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
                ->map(function($f){ return $f instanceof Carbon ? $f->toDateString() : (string)$f; })
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
                $contrato->numero_contrato,
                $contrato->contratista?->razon_social,
                $contrato->contratista?->nit,
                $contrato->rp,
                $contrato->fecha_rp?->toDateString(),
                $contrato->valor_rp,
                $contrato->fecha_inicio?->toDateString(),
                $contrato->fecha_fin?->toDateString(),
                $contrato->supervisor?->nombre_completo,
                $numero_cuentas_proceso,
                $numero_pagos_totales,
                $numero_facturas_radicada_hacienda,
                $porcentaje_cuentas,
                $contrato->contratista?->entidad_salud,
                $contrato->contratista?->entidad_pension,
                $contrato->contratista?->entidad_arl,
                $mes_planilla,
                $radicado_por,
                ($fecha_radicacion_inicial ? $fecha_radicacion_inicial : '') . ' / ' . ($fecha_radicacion_ultima ? $fecha_radicacion_ultima : ''),
                $observaciones,
                $estado_primera_revision,
                $devueltaOrSap['fecha'] ?? null,
                $devueltaOrSap['nombre'] ?? null,
                $devueltaOrSap['responsable'] ?? null,
                $enFacturacion['fecha'] ?? null,
                $enFacturacion['nombre'] ?? null,
                $enFacturacion['responsable'] ?? null,
                $enFacturacion['fecha'] ?? null,
                $firmaSecretario['nombre'] ?? null,
                $firmaSecretario['fecha'] ?? null,
                $radicadaHacienda ? 'Sí' : 'No',
                $fecha_radicacion,
                $ultimaCuenta?->ultima_factura_hacienda,
                $ultimaCuenta?->observaciones,
                $cuentas->sum('diferencia_cuentas'),
            ];
        })->toArray();

        return $rows;
    }

    public function headings(): array
    {
        return [
            'NUMERO DE CONTRATO',
            'CONTRATISTA',
            'CEDULA',
            'RP',
            'FECHA RP',
            'VALOR RP',
            'FECHA DE INICIO',
            'FECHA DE TERMINACIÓN',
            'SUPERVISOR',
            'NUMERO DE CUENTA EN PROCESO DE CUENTAS',
            'NUMERO DE PAGOS TOTALES',
            'N° DE FACTURAS RADICADA HACIENDA',
            '% DE CUENTAS',
            'ENTIDAD SALUD',
            'ENTIDAD PENSIÓN',
            'ENTIDAD ARL',
            'MES PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA',
            'RADICADO POR',
            'FECHA RADIC. INICIAL / ÚLTIMA',
            'OBSERVACIONES',
            'ESTADO TRAS PRIMERA REVISIÓN (SERGIO / CONSUELO)',
            'FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP',
            'ENVIADA A INGRESO MERCANCIA SAP',
            'RESPONSABLE',
            'FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES',
            'EN FACTURACIÓN',
            'RESPONSABLE',
            'FECHA EN QUE SE GENERA FACURACIÓN',
            'FIRMA SECRETARIO',
            'FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO',
            'RADICADA EN HACIENDA',
            'FECHA DE RADICACIÓN',
            'ULTIMA FACTURA RADICADA HACIENDA',
            'OBSERVACIÓN DEVOLUCIÓN HACIENDA',
            'DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS',
        ];
    }
}

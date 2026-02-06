<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PlantillaCuentasExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Fila de ejemplo opcional
        return [
            [
                'CONT-2024-001',                    // NUMERO DE CONTRATO
                'ACME Corporation S.A.S',           // CONTRATISTA
                '900123456',                        // CEDULA
                'RP-2024-001',                      // RP
                '2024-01-05',                       // FECHA RP
                '10000000.00',                      // VALOR RP
                '2024-01-01',                       // FECHA DE INICIO
                '2024-12-31',                       // FECHA DE TERMINACIÓN
                'María González',                   // SUPERVISOR
                'CTA-2024-001',                     // NUMERO DE CUENTA EN PROCESO DE CUENTAS
                '12',                               // NUMERO DE PAGOS TOTALES
                '5',                                // N° DE FACTURAS RADICADA HACIENDA
                '41.67',                            // % DE CUENTAS
                'Sura EPS',                         // ENTIDAD SALUD
                'Porvenir',                         // ENTIDAD PENSIÓN
                'ARL Sura',                         // ENTIDAD ARL
                'Enero 2024',                       // MES PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA
                'Juan Pérez',                       // RADICADO POR
                '2024-01-15 10:30:00',             // FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES
                'Sin observaciones',                // OBSERVACIONES
                'Aprobado',                         // ESTADO TRAS PRIMERA REVISIÓN (SERGIO / CONSUELO)
                '2024-01-16',                       // FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP
                'Si',                               // ENVIADA A INGRESO MERCANCIA SAP
                'Andrea López',                     // RESPONSABLE
                '2024-01-17',                       // FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES
                'Si',                               // EN FACTURACIÓN
                'Carlos Martínez',                  // RESPONSABLE
                '2024-01-18',                       // FECHA EN QUE SE GENERA FACURACIÓN
                'Si',                               // FIRMA SECRETARIO
                '2024-01-19',                       // FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO
                'Si',                               // RADICADA EN HACIENDA
                '2024-01-20',                       // FECHA DE RADICACIÓN
                'FACT-2024-005',                    // ULTIMA FACTURA RADICADA HACIENDA
                'Ninguna',                          // OBSERVACIÓN DEVOLUCIÓN HACIENDA
                '7',                                // DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS
            ],
        ];
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
            'FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES',
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

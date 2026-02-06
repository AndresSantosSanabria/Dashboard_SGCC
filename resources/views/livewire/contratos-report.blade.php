<div class="p-4">
    <!-- Estilos específicos para la tabla de contratos -->
    <link rel="stylesheet" href="/resources/css/contratosReport.css">
    <h2 class="text-xl font-semibold mb-2">Reporte de Contratos</h2>

    <div class="overflow-auto">
        <table class="contratos-table min-w-full divide-y divide-gray-200 table-auto border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-1 text-left">NUMERO DE CONTRATO</th>
                    <th class="px-2 py-1 text-left">CONTRATISTA</th>
                    <th class="px-2 py-1 text-left">CEDULA</th>
                    <th class="px-2 py-1 text-left">RP</th>
                    <th class="px-2 py-1 text-left">FECHA RP</th>
                    <th class="px-2 py-1 text-left">VALOR RP</th>
                    <th class="px-2 py-1 text-left">FECHA DE INICIO</th>
                    <th class="px-2 py-1 text-left">FECHA DE TERMINACIÓN</th>
                    <th class="px-2 py-1 text-left">SUPERVISOR</th>
                    <th class="px-2 py-1 text-left">NUMERO DE CUENTA EN PROCESO DE CUENTAS</th>
                    <th class="px-2 py-1 text-left">NUMERO DE PAGOS TOTALES</th>
                    <th class="px-2 py-1 text-left">N° DE FACTURAS RADICADA HACIENDA</th>
                    <th class="px-2 py-1 text-left">% DE CUENTAS</th>
                    <th class="px-2 py-1 text-left">ENTIDAD SALUD</th>
                    <th class="px-2 py-1 text-left">ENTIDAD PENSIÓN</th>
                    <th class="px-2 py-1 text-left">ENTIDAD ARL</th>
                    <th class="px-2 py-1 text-left">MES PLANILLA SEG. SOCIAL ULTIMA CUENTA</th>
                    <th class="px-2 py-1 text-left">RADICADO POR</th>
                    <th class="px-2 py-1 text-left">FECHA RADIC. INICIAL / ÚLTIMA</th>
                    <th class="px-2 py-1 text-left">OBSERVACIONES</th>
                    <th class="px-2 py-1 text-left">ESTADO TRAS PRIMERA REVISIÓN</th>
                    <th class="px-2 py-1 text-left">FECHA DEVUELTA / ENVIADA A SAP</th>
                    <th class="px-2 py-1 text-left">ENVIADA A INGRESO MERCANCIA SAP</th>
                    <th class="px-2 py-1 text-left">RESPONSABLE</th>
                    <th class="px-2 py-1 text-left">FECHA ENVÍO FACTURACIÓN / DEVUELTA</th>
                    <th class="px-2 py-1 text-left">EN FACTURACIÓN</th>
                    <th class="px-2 py-1 text-left">RESPONSABLE FACTURACIÓN</th>
                    <th class="px-2 py-1 text-left">FECHA GENERACIÓN FACTURACIÓN</th>
                    <th class="px-2 py-1 text-left">FIRMA SECRETARIO</th>
                    <th class="px-2 py-1 text-left">FECHA FIRMA SECRETARIO</th>
                    <th class="px-2 py-1 text-left">RADICADA EN HACIENDA</th>
                    <th class="px-2 py-1 text-left">FECHA DE RADICACIÓN</th>
                    <th class="px-2 py-1 text-left">ULTIMA FACTURA RADICADA HACIENDA</th>
                    <th class="px-2 py-1 text-left">OBSERVACIÓN DEVOLUCIÓN HACIENDA</th>
                    <th class="px-2 py-1 text-left">DIFERENCIA CUENTAS</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($rows as $row)
                <tr>
                    <td class="px-2 py-1">{{ $row['numero_contrato'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['contratista'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['cedula'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['rp'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['fecha_rp'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['valor_rp'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['fecha_inicio'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['fecha_fin'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['supervisor'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['numero_cuenta_en_proceso'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['numero_pagos_totales'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['numero_facturas_radicada_hacienda'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['porcentaje_cuentas'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['entidad_salud'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['entidad_pension'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['entidad_arl'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['mes_planilla_ultima_cuenta'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['radicado_por'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ ($row['fecha_radicacion_inicial'] ?? '') . ' / ' . ($row['fecha_radicacion_ultima'] ?? '') }}</td>
                    <td class="px-2 py-1">{{ $row['observaciones'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['estado_primera_revision'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['fecha_devuelta_revision_o_enviada_sap'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['enviada_ingreso_mercancia_sap'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['responsable'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['fecha_envio_facturacion_devuelta'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['en_facturacion'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['responsable_facturacion'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['fecha_generacion_facturacion'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['firma_secretario'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['fecha_firma_secretario'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['radicada_en_hacienda'] ? 'Sí' : 'No' }}</td>
                    <td class="px-2 py-1">{{ $row['fecha_radicacion'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['ultima_factura_radicada_hacienda'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['observacion_devolucion_hacienda'] ?? '' }}</td>
                    <td class="px-2 py-1">{{ $row['diferencia_cuentas'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="table-responsive" style="max-height: 700px;">
    <table class="table table-hover table-bordered mb-0" id="cuentasTable" style="min-width: 3000px; font-size: 0.85rem;">
        <thead class="table-dark sticky-top">
            <tr>
                <th>NUMERO DE CONTRATO</th>
                <th>CONTRATISTA</th>
                <th>CEDULA</th>
                <th>RP</th>
                <th>FECHA RP</th>
                <th>VALOR RP</th>
                <th>FECHA DE INICIO</th>
                <th>FECHA DE TERMINACIÓN</th>
                <th>SUPERVISOR</th>
                <th>NUMERO DE CUENTA</th>
                <th>PAGOS TOTALES</th>
                <th>FACTURAS RADICADAS</th>
                <th>% CUENTAS</th>
                <th>ENTIDAD SALUD</th>
                <th>ENTIDAD PENSIÓN</th>
                <th>ENTIDAD ARL</th>
                <th>SS ULTIMA CUENTA</th>
                <th>RADICADO POR</th>
                <th>FECHA RADICACIÓN</th>
                <th>OBSERVACIONES</th>
                <th>ESTADO 1ERA REVISIÓN</th>
                <th>FECHA DEVUELTA/SAP</th>
                <th>ENVIADA SAP</th>
                <th>RESPONSABLE</th>
                <th>FECHA ENVIO FACT/CORR</th>
                <th>EN FACTURACIÓN</th>
                <th>RESPONSABLE</th>
                <th>FECHA FACTURACIÓN</th>
                <th>FIRMA SECRETARIO</th>
                <th>FECHA FIRMA</th>
                <th>RADICADA HACIENDA</th>
                <th>FECHA RAD. HACIENDA</th>
                <th>ULTIMA FACTURA HACIENDA</th>
                <th>OBS. DEVOLUCION</th>
                <th>DIFERENCIA CUENTAS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cuentas as $cuenta)
                @php
                    $contrato = $cuenta->contrato;
                    $contratista = $contrato?->contratista;
                    $rp = $contrato?->registrosPresupuestales->first();
                    $ultimaSS = $cuenta->planillasSeguridadSocial->first();
                    $ssVigente = $contratista?->seguridadSocialVigente;

                    // Lógica para bloques específicos
                    $bloqueRevision = $cuenta->estadosBloques->where('bloque.codigo', 'REV')->first();
                    $bloqueSap = $cuenta->estadosBloques->where('bloque.codigo', 'SAP')->first();
                    $bloqueFacturacion = $cuenta->estadosBloques->where('bloque.codigo', 'FAC')->first();
                    $bloqueFirma = $cuenta->estadosBloques->where('bloque.codigo', 'FIR')->first();
                @endphp
                <tr>
                    <td><strong>{{ $contrato->numero_contrato ?? 'N/A' }}</strong></td>
                    <td>{{ $contratista->razon_social ?? ($contratista->representante_legal ?? 'N/A') }}</td>
                    <td>{{ $contratista->nit ?? 'N/A' }}</td>
                    <td>{{ $rp->numero_rp ?? 'N/A' }}</td>
                    <td>{{ $rp && $rp->fecha_rp ? $rp->fecha_rp->format('d/m/Y') : 'N/A' }}</td>
                    <td>${{ number_format($rp->valor_rp ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $contrato && $contrato->fecha_inicio ? $contrato->fecha_inicio->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $contrato && $contrato->fecha_fin ? $contrato->fecha_fin->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $contrato?->supervisor->nombre_completo ?? 'N/A' }}</td>
                    <td>{{ $cuenta->numero_cuenta }}</td>
                    <td>{{ $cuenta->numero_pagos_totales }}</td>
                    <td>{{ $cuenta->numero_facturas_radicadas }}</td>
                    <td>{{ number_format($cuenta->porcentaje_cuentas, 2) }}%</td>

                    {{-- Información de Seguridad Social --}}
                    <td>{{ $ssVigente?->entidadSalud?->nombre ?? 'N/A' }}</td>
                    <td>{{ $ssVigente?->entidadPension?->nombre ?? 'N/A' }}</td>
                    <td>{{ $ssVigente?->entidadArl?->nombre ?? 'N/A' }}</td>

                    <td>{{ $ultimaSS->numero_planilla ?? 'N/A' }}</td>
                    <td>{{ $cuenta->radicado_por }}</td>
                    <td>{{ $cuenta->fecha_radicacion ? $cuenta->fecha_radicacion->format('d/m/Y H:i') : 'N/A' }}
                    </td>
                    <td>
                        <small class="text-truncate d-inline-block" style="max-width: 150px;"
                            title="{{ $cuenta->observaciones }}">
                            {{ $cuenta->observaciones ?? 'Sin observaciones' }}
                        </small>
                    </td>

                    {{-- Workflow: Revisión --}}
                    <td>
                        <span
                            class="badge {{ $bloqueRevision?->bloque_completado ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $bloqueRevision?->estadoActual->nombre ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ $bloqueRevision?->fecha_completado_bloque ? $bloqueRevision->fecha_completado_bloque->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $bloqueSap?->fecha_ingreso_bloque ? $bloqueSap->fecha_ingreso_bloque->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $bloqueRevision?->responsable->primer_nombre ?? 'Sin asignar' }}</td>

                    {{-- Workflow: Facturación --}}
                    <td>{{ $bloqueFacturacion?->fecha_ingreso_bloque ? $bloqueFacturacion->fecha_ingreso_bloque->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>
                        <span class="badge {{ $bloqueFacturacion?->bloque_completado ? 'bg-success' : 'bg-info' }}">
                            {{ $bloqueFacturacion?->estadoActual->nombre ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ $bloqueFacturacion?->responsable->primer_nombre ?? 'Sin asignar' }}</td>
                    <td>{{ $bloqueFacturacion?->fecha_completado_bloque ? $bloqueFacturacion->fecha_completado_bloque->format('d/m/Y') : 'N/A' }}
                    </td>

                    {{-- Firma y Hacienda --}}
                    <td>
                        <span class="badge {{ $bloqueFirma?->bloque_completado ? 'bg-success' : 'bg-secondary' }}">
                            {{ $bloqueFirma?->estadoActual->nombre ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ $bloqueFirma?->fecha_ingreso_bloque ? $bloqueFirma->fecha_ingreso_bloque->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $cuenta->finalizada ? 'SÍ' : 'NO' }}</td>
                    <td>{{ $cuenta->fecha_radicacion_hacienda ? $cuenta->fecha_radicacion_hacienda->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $cuenta->ultima_factura_hacienda ?? 'N/A' }}</td>
                    <td>{{ $cuenta->observacion_hacienda ?? 'N/A' }}</td>
                    <td>{{ number_format($cuenta->diferencia_cuentas ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="35" class="text-center py-4 text-muted">No se encontraron cuentas de cobro
                        radicadas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 d-flex justify-content-center" id="pagination-links">
    {{ $cuentas->links() }}
</div>

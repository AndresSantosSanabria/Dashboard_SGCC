<div class="table-responsive" style="max-height: 75vh;">
    <table class="table mb-0" id="cuentasTable"
        style="min-width: 3000px; font-size: 0.85rem;">
        <thead class="table-dark sticky-top">
            <tr>
                @php
                    $headers = [
                        ['label' => 'NUMERO DE CONTRATO', 'class' => 'sticky-col sticky-col-1'],
                        ['label' => 'CONTRATISTA', 'class' => 'sticky-col sticky-col-2'],
                        ['label' => 'CEDULA', 'class' => 'sticky-col sticky-col-3'],
                        ['label' => 'ESTADO ACTUAL', 'class' => ''],
                        ['label' => 'RP', 'class' => ''],
                        ['label' => 'FECHA RP', 'class' => ''],
                        ['label' => 'VALOR RP', 'class' => ''],
                        ['label' => 'FECHA DE INICIO', 'class' => ''],
                        ['label' => 'FECHA DE TERMINACIÓN', 'class' => ''],
                        ['label' => 'SUPERVISOR', 'class' => ''],
                        ['label' => 'NUMERO DE CUENTA', 'class' => ''],
                        ['label' => 'PAGOS TOTALES', 'class' => ''],
                        ['label' => 'FACTURAS RADICADAS', 'class' => ''],
                        ['label' => '% CUENTAS', 'class' => ''],
                        ['label' => 'ENTIDAD SALUD', 'class' => ''],
                        ['label' => 'ENTIDAD PENSIÓN', 'class' => ''],
                        ['label' => 'ENTIDAD ARL', 'class' => ''],
                        ['label' => 'SS ULTIMA CUENTA', 'class' => ''],
                        ['label' => 'RADICADO POR', 'class' => ''],
                        ['label' => 'FECHA RADICACIÓN', 'class' => ''],
                        ['label' => 'OBSERVACIONES', 'class' => ''],
                        ['label' => 'ESTADO 1ERA REVISIÓN', 'class' => ''],
                        ['label' => 'FECHA DEVUELTA/SAP', 'class' => ''],
                        ['label' => 'ENVIADA SAP', 'class' => ''],
                        ['label' => 'RESPONSABLE', 'class' => ''],
                        ['label' => 'FECHA ENVIO FACT/CORR', 'class' => ''],
                        ['label' => 'EN FACTURACIÓN', 'class' => ''],
                        ['label' => 'RESPONSABLE', 'class' => ''],
                        ['label' => 'FECHA FACTURACIÓN', 'class' => ''],
                        ['label' => 'FIRMA SECRETARIO', 'class' => ''],
                        ['label' => 'FECHA FIRMA', 'class' => ''],
                        ['label' => 'RADICADA HACIENDA', 'class' => ''],
                        ['label' => 'FECHA RAD. HACIENDA', 'class' => ''],
                        ['label' => 'ULTIMA FACTURA HACIENDA', 'class' => ''],
                        ['label' => 'OBS. DEVOLUCION', 'class' => ''],
                        ['label' => 'Diferencia Cuentas Totales - vs Cuentas Radicadas', 'class' => ''],
                        ['label' => 'TRÁMITE SIGUIENTE CUENTA', 'class' => ''],
                    ];
                @endphp
                @foreach($headers as $h)
                    <th class="{{ $h['class'] }}">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <span>{{ $h['label'] }}</span>
                            <button type="button" class="btn btn-sm btn-link text-white p-0 toggle-col-btn" title="Minimizar">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                        </div>
                    </th>
                @endforeach
                @if ($canEditDashboard)
                    <th>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <span>ACCIÓN</span>
                            <button type="button" class="btn btn-sm btn-link text-white p-0 toggle-col-btn" title="Minimizar">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                        </div>
                    </th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($cuentas as $index => $cuenta)
                @php
                    $contrato = $cuenta->contrato;
                    $contratista = $contrato?->contratista;
                    $rp = $contrato?->registrosPresupuestales->first();
                    $ultimaSS = $cuenta->planillasSeguridadSocial->first();
                    $ssVigente = $contratista?->seguridadSocialVigente;

                    // Lógica para bloques específicos usando IDs para mayor confiabilidad
                    $bloqueRevision = $cuenta->estadosBloques->where('bloque_id', 1)->first();
                    $bloqueSap = $cuenta->estadosBloques->where('bloque_id', 2)->first();
                    $bloqueFacturacion = $cuenta->estadosBloques->where('bloque_id', 3)->first();
                    $bloqueFirma = $cuenta->estadosBloques->where('bloque_id', 4)->first();
                    $bloqueHacienda = $cuenta->estadosBloques->where('bloque_id', 5)->first();
                @endphp
                <tr style="--row-index: {{ $index }};">
                    <td class="sticky-col sticky-col-1"><strong>{{ $contrato->numero_contrato ?? 'N/A' }}</strong></td>
                    <td class="sticky-col sticky-col-2">
                        {{ $contratista->razon_social ?? ($contratista->representante_legal ?? 'N/A') }}</td>
                    <td class="sticky-col sticky-col-3">{{ $contratista->nit ?? 'N/A' }}</td>
                    @php
                        $estadoNombre = $cuenta->estadoActual?->nombre ?? 'N/A';
                        $esDevuelta =
                            stripos($estadoNombre, 'devuelta') !== false ||
                            stripos($estadoNombre, 'devolución') !== false ||
                            stripos($estadoNombre, 'devuelto') !== false;
                        $badgeClass = $esDevuelta ? 'bg-danger' : 'bg-primary';
                    @endphp
                    <td><span class="badge {{ $badgeClass }}">{{ $estadoNombre }}</span></td>
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
                        @if ($bloqueRevision && $bloqueRevision->estadoActual)
                            @php
                                $estadoRevisionNombre = $bloqueRevision->estadoActual->nombre;
                                $esDevueltaRevision =
                                    stripos($estadoRevisionNombre, 'devuelta') !== false ||
                                    stripos($estadoRevisionNombre, 'devolución') !== false ||
                                    stripos($estadoRevisionNombre, 'devuelto') !== false;
                            @endphp
                            <span
                                class="badge {{ $esDevueltaRevision ? 'bg-danger' : ($bloqueRevision->bloque_completado ? 'bg-success' : 'bg-warning text-dark') }}">
                                {{ $bloqueRevision->estadoActual->nombre }}
                            </span>
                        @else
                            <span class="text-muted small">N/A</span>
                        @endif
                    </td>
                    <td>{{ $bloqueRevision?->fecha_completado_bloque ? $bloqueRevision->fecha_completado_bloque->format('d/m/Y') : 'N/A' }}
                    </td>
                    {{-- Workflow: SAP --}}
                    <td>
                        @if ($bloqueSap && $bloqueSap->estadoActual)
                            @php
                                $estadoSapNombre = $bloqueSap->estadoActual->nombre;
                                $esDevueltaSap =
                                    stripos($estadoSapNombre, 'devuelta') !== false ||
                                    stripos($estadoSapNombre, 'devolución') !== false ||
                                    stripos($estadoSapNombre, 'devuelto') !== false;
                            @endphp
                            <span
                                class="badge {{ $esDevueltaSap ? 'bg-danger' : ($bloqueSap->bloque_completado ? 'bg-success' : 'bg-secondary') }}">
                                {{ $bloqueSap->estadoActual->nombre }}
                            </span>
                        @else
                            <span class="text-muted small">N/A</span>
                        @endif
                    </td>
                    <td>{{ $bloqueSap?->responsable->primer_nombre ?? 'Sin asignar' }}</td>

                    {{-- Workflow: Facturación --}}
                    <td>{{ $bloqueFacturacion?->fecha_ingreso_bloque ? $bloqueFacturacion->fecha_ingreso_bloque->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>
                        @if ($bloqueFacturacion && $bloqueFacturacion->estadoActual)
                            @php
                                $estadoFactNombre = $bloqueFacturacion->estadoActual->nombre;
                                $esDevueltaFact =
                                    stripos($estadoFactNombre, 'devuelta') !== false ||
                                    stripos($estadoFactNombre, 'devolución') !== false ||
                                    stripos($estadoFactNombre, 'devuelto') !== false;
                            @endphp
                            <span
                                class="badge {{ $esDevueltaFact ? 'bg-danger' : ($bloqueFacturacion->bloque_completado ? 'bg-success' : 'bg-primary') }}">
                                {{ $bloqueFacturacion->estadoActual->nombre }}
                            </span>
                        @else
                            <span class="text-muted small">N/A</span>
                        @endif
                    </td>
                    <td>{{ $bloqueFacturacion?->responsable->primer_nombre ?? 'Sin asignar' }}</td>
                    <td>{{ $bloqueFacturacion?->fecha_completado_bloque ? $bloqueFacturacion->fecha_completado_bloque->format('d/m/Y') : 'N/A' }}
                    </td>

                    {{-- Firma y Hacienda --}}
                    <td>
                        @if ($bloqueFirma && $bloqueFirma->estadoActual)
                            @php
                                $estadoFirmaNombre = $bloqueFirma->estadoActual->nombre;
                                $esDevueltaFirma =
                                    stripos($estadoFirmaNombre, 'devuelta') !== false ||
                                    stripos($estadoFirmaNombre, 'devolución') !== false ||
                                    stripos($estadoFirmaNombre, 'devuelto') !== false;
                            @endphp
                            <span
                                class="badge {{ $esDevueltaFirma ? 'bg-danger' : ($bloqueFirma->bloque_completado ? 'bg-success' : 'bg-secondary') }}">
                                {{ $bloqueFirma->estadoActual->nombre }}
                            </span>
                        @else
                            <span class="text-muted small">N/A</span>
                        @endif
                    </td>
                    <td>{{ $bloqueFirma?->fecha_ingreso_bloque ? $bloqueFirma->fecha_ingreso_bloque->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>
                        @if ($bloqueHacienda && $bloqueHacienda->estadoActual)
                            @php
                                $estadoHaciendaNombre = $bloqueHacienda->estadoActual->nombre;
                                $esDevueltaHacienda =
                                    stripos($estadoHaciendaNombre, 'devuelta') !== false ||
                                    stripos($estadoHaciendaNombre, 'devolución') !== false ||
                                    stripos($estadoHaciendaNombre, 'devuelto') !== false;
                            @endphp
                            <span
                                class="badge {{ $esDevueltaHacienda ? 'bg-danger' : ($bloqueHacienda->bloque_completado ? 'bg-success' : 'bg-secondary') }}">
                                {{ $bloqueHacienda->estadoActual->nombre }}
                            </span>
                        @elseif ($cuenta->finalizada)
                            <span class="badge bg-success">SÍ</span>
                        @else
                            <span class="text-muted small text-uppercase">Pendiente</span>
                        @endif
                    </td>
                    <td>{{ $bloqueHacienda?->fecha_ingreso_bloque ? $bloqueHacienda->fecha_ingreso_bloque->format('d/m/Y') : ($cuenta->fecha_radicacion_hacienda ? $cuenta->fecha_radicacion_hacienda->format('d/m/Y') : 'N/A') }}
                    </td>
                    <td>{{ $cuenta->ultima_factura_hacienda ?? 'N/A' }}</td>
                    <td>{{ $cuenta->observacion_hacienda ?? 'N/A' }}</td>
                    <td class="text-center">{{ $cuenta->diferencia_cuentas }}</td>
                    <td class="text-center">
                        @if ($cuenta->finalizada)
                            @if ($cuenta->numero_cuenta < $cuenta->numero_pagos_totales)
                                <button type="button" class="btn btn-sm btn-govco btn-outline-primary"
                                    onclick="startNextAccount({{ $cuenta->id }}, '{{ $contrato->numero_contrato }}', {{ $cuenta->numero_cuenta + 1 }})"
                                    title="Iniciar Cuenta #{{ $cuenta->numero_cuenta + 1 }}"
                                    style="border-radius: 20px; font-size: 0.75rem; padding: 4px 12px;">
                                    <i class="bi bi-play-fill me-1"></i> Siguiente #{{ $cuenta->numero_cuenta + 1 }}
                                </button>
@else
    <span class="badge bg-success" style="padding: 8px 12px !important;"><i class="bi bi-check-all me-1"></i> Completado</span>
@endif
                        @else
                            <span class="text-muted">En proceso de flujo</span>
                        @endif
                    </td>
                    @if ($canEditDashboard)
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn-action-premium"
                                    onclick="showHistory({{ $cuenta->id }}, '{{ $contrato->numero_contrato ?? 'N/A' }}')"
                                    title="Ver Historial">
                                    <span class="govco-svg govco-clock"></span>
                                </button>
                                <button type="button" class="btn-action-premium"
                                    onclick="editAccount({{ $cuenta->id }})"
                                    title="Editar Cuenta">
                                    <span class="govco-svg govco-edit"></span>
                                </button>
                            </div>
                        </td>
                    @endif
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

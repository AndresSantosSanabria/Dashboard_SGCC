<div class="table-responsive" style="max-height: 75vh;">
    <table class="table mb-0" id="cuentasTable" style="min-width: 3000px; font-size: 0.85rem;">
        <thead class="table-dark sticky-top">
            <tr>
                @php
                    $headers = [
                        ['label' => 'NUMERO DE CONTRATO', 'class' => 'sticky-col sticky-col-1', 'sortable' => true, 'id' => 'numero_contrato'],
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
                    ];
 
                    // BLOQUES DINÁMICOS: Agregamos las columnas de cada etapa del flujograma
                    foreach ($bloques as $b) {
                        $shortName = match($b->codigo) {
                            'REV1' => 'REVISIÓN',
                            'SAP' => 'SAP',
                            'FACT' => 'FACTURACIÓN',
                            'FIRMA' => 'FIRMA',
                            'HACIENDA' => 'HACIENDA',
                            default => strtoupper($b->nombre)
                        };
 
                        $headers[] = ['label' => "ESTADO $shortName", 'class' => ''];
                        $headers[] = ['label' => "FECHA / RESP. $shortName", 'class' => ''];
                    }
 
                    $headers[] = ['label' => 'ULTIMA FACTURA HACIENDA', 'class' => ''];
                    $headers[] = ['label' => 'OBS. DEVOLUCION', 'class' => ''];
                    $headers[] = ['label' => 'Diferencia Cuentas Totales - vs Cuentas Radicadas', 'class' => ''];
                    $headers[] = ['label' => 'TRÁMITE SIGUIENTE CUENTA', 'class' => ''];
                @endphp
                @foreach ($headers as $h)
                    <th class="{{ $h['class'] }}">
                        <div class="d-flex align-items-center justify-content-between gap-2 {{ !empty($h['sortable']) ? 'cursor-pointer' : '' }}" 
                             @if(!empty($h['sortable'])) onclick="toggleDashboardSort('{{ $h['id'] }}')" @endif>
                            <span>{{ $h['label'] }}</span>
                            @if(!empty($h['sortable']))
                                @php
                                    $currentSortBy = request('sort_by', 'numero_contrato');
                                    $currentOrder = request('sort_order', 'asc');
                                    $iconClass = 'bi-sort-alpha-down';
                                    if ($currentSortBy === $h['id']) {
                                        $iconClass = $currentOrder === 'asc' ? 'bi-sort-numeric-up' : 'bi-sort-numeric-down';
                                    }
                                @endphp
                                <i class="bi {{ $iconClass }} ms-1 opacity-75"></i>
                            @endif
                            <button type="button" class="btn btn-sm btn-link text-white p-0 toggle-col-btn"
                                title="Minimizar">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                        </div>
                    </th>
                @endforeach
                @if ($canEditDashboard)
                    <th>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <span>ACCIÓN</span>
                            <button type="button" class="btn btn-sm btn-link text-white p-0 toggle-col-btn"
                                title="Minimizar">
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
                    // CORRECCIÓN: Usamos la colección cargada con eager loading (?-> en toda la cadena)
                    $rp = $contrato?->registrosPresupuestales?->first();
                    // CORRECCIÓN: planillasSeguridadSocial puede estar vacía si no se cargó con with()
                    $ultimaSS = $cuenta->relationLoaded('planillasSeguridadSocial')
                        ? $cuenta->planillasSeguridadSocial->first()
                        : null;
                    $ssVigente = $contratista?->seguridadSocialVigente;

                    // Los bloques se cargan dinámicamente mediante el loop inferior
                @endphp
                <tr style="--row-index: {{ $index }};">
                    <td class="sticky-col sticky-col-1"><strong>{{ $contrato?->numero_contrato ?? 'N/A' }}</strong></td>
                    <td class="sticky-col sticky-col-2">
                        {{ $contratista?->razon_social ?? ($contratista?->representante_legal ?? 'N/A') }}</td>
                    <td class="sticky-col sticky-col-3">{{ $contratista?->nit ?? 'N/A' }}</td>
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
                    <td>{{ $contrato && $contrato?->fecha_inicio ? $contrato?->fecha_inicio->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $contrato && $contrato?->fecha_fin ? $contrato?->fecha_fin->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $contrato?->supervisor?->nombre_completo ?? 'N/A' }}</td>
                    <td>{{ $cuenta->numero_cuenta ?? '0' }}</td>
                    <td>{{ $cuenta->numero_pagos_totales ?? '0' }}</td>
                    <td>{{ $cuenta->numero_facturas_radicadas ?? '0' }}</td>
                    @php
                        $pCuentas = $cuenta->porcentaje_cuentas ?? 0;
                        // Cálculo de color (HSL): 0% = Rojo (0), 100% = Verde (120)
                        $hue = ($pCuentas * 1.2); 
                        $bgProgreso = "hsl($hue, 85%, 45%)";
                    @endphp
                    <td class="text-center">
                        <div style="background-color: {{ $bgProgreso }}; color: white; padding: 4px 8px; border-radius: 12px; font-weight: bold; display: inline-block; min-width: 75px; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            {{ number_format($pCuentas, 2) }}%
                        </div>
                    </td>

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
                    {{-- BLOQUES DINÁMICOS: Renderizado automático de todas las etapas del workflow --}}
                    @foreach($bloques as $b)
                        @php 
                            $histBlock = $cuenta->estadosBloques->where('bloque_id', $b->id)->first();
                        @endphp
                        <td>
                            @if ($histBlock && $histBlock->estadoActual)
                                @php
                                    $estN = $histBlock->estadoActual->nombre;
                                    $esDev = stripos($estN, 'devuelta') !== false || stripos($estN, 'devolución') !== false || stripos($estN, 'devuelto') !== false;
                                @endphp
                                <span class="badge {{ $esDev ? 'bg-danger' : ($histBlock->bloque_completado ? 'bg-success' : 'bg-warning text-dark') }}">
                                    {{ $histBlock->estadoActual->nombre }}
                                </span>
                            @else
                                <span class="text-muted small">Pendiente</span>
                            @endif
                        </td>
                        <td>
                            <div class="small">
                                @if($histBlock)
                                    @if($histBlock->bloque_completado)
                                        <i class="bi bi-calendar-check text-success me-1"></i>{{ $histBlock->fecha_completado_bloque ? $histBlock->fecha_completado_bloque->format('d/m/Y') : 'Finalizado' }}
                                    @else
                                        <i class="bi bi-person text-secondary me-1"></i>{{ $histBlock->responsable->primer_nombre ?? 'Asignado' }}
                                        <br>
                                        <span class="text-muted" style="font-size: 0.7rem;">Ingreso: {{ $histBlock->fecha_ingreso_bloque ? $histBlock->fecha_ingreso_bloque->format('d/m/Y') : '-' }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </div>
                        </td>
                    @endforeach
 
                    <td>{{ $cuenta->ultima_factura_hacienda ?? 'N/A' }}</td>
                    <td>{{ $cuenta->observacion_hacienda ?? 'N/A' }}</td>                   <td class="text-center">{{ $cuenta->diferencia_cuentas }}</td>
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
                                <span class="badge bg-success" style="padding: 8px 12px !important;"><i
                                        class="bi bi-check-all me-1"></i> Completado</span>
                            @endif
                        @else
                            <span class="text-muted">En proceso de flujo</span>
                        @endif
                    </td>
                    @if ($canEditDashboard)
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn-action-premium"
                                    onclick="showHistory({{ $cuenta->id }}, '{{ $contrato?->numero_contrato ?? 'N/A' }}')"
                                    title="Ver Historial">
                                    <span class="govco-svg govco-clock"></span>
                                </button>
                                <button type="button" class="btn-action-premium"
                                    onclick="editAccount({{ $cuenta->id }})" title="Editar Cuenta">
                                    <span class="govco-svg govco-edit"></span>
                                </button>
                                @if ($contrato)
                                    <button type="button" class="btn-action-premium"
                                        onclick="deleteContrato({{ $contrato->id }}, '{{ $contrato->numero_contrato ?? 'N/A' }}')"
                                        title="Eliminar Contrato GLOBALMENTE"
                                        style="color: #dc2626; background: rgba(220, 38, 38, 0.05);">
                                        <i class="bi bi-trash fs-6"></i>
                                    </button>
                                @endif
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

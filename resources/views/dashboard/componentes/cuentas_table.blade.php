<style>
    /* RED DE SEGURIDAD: Oculta cualquier badge transicional que haya escapado */
    .estado-bloque-cell .badge.estado-bloque-badge {
        display: inline-block;
    }
    .badge-transicional-oculto { display: none !important; }
</style>

<div class="table-responsive" style="max-height: 75vh; border-radius: 0 0 20px 20px; border: none;">
    <table class="table mb-0 table-hover" id="cuentasTable" style="min-width: 3200px; font-size: 0.82rem;">
        <thead class="sticky-top">
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
                            <span style="white-space: nowrap;">{{ $h['label'] }}</span>
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
            @forelse ($contratos as $index => $contrato)
                @php
                    $cuentasColl = $contrato->cuentasCobro->sortByDesc('id');
                    $cuentaActiva = $cuentasColl->first(fn($c) => !$c->finalizada);
                    $cuentaRef = $cuentaActiva ?? $cuentasColl->first();
                    $contratista = $contrato->contratista;
                    $rp = $contrato->registrosPresupuestales?->first();
                    $ssVigente = $contratista?->seguridadSocialVigente;
                    $totalCuentas = $cuentasColl->count();
                    $pagosTotales = $contrato->pagos_totales ?? 0;
                    $facturasRadicadas = $contrato->facturas_radicadas ?? 0;
                    $pCuentas = $pagosTotales > 0 ? round(($facturasRadicadas / $pagosTotales) * 100, 2) : 0;
                @endphp
                <tr style="--row-index: {{ $index }};">
                    <td class="sticky-col sticky-col-1"><strong>{{ $contrato->numero_contrato ?? 'N/A' }}</strong></td>
                    <td class="sticky-col sticky-col-2">
                        {{ $contratista?->razon_social ?? ($contratista?->representante_legal ?? 'N/A') }}</td>
                    <td class="sticky-col sticky-col-3">{{ $contratista?->nit ?? 'N/A' }}</td>
                    @php
                        $estadoNombre = $cuentaRef?->estadoActual?->nombre ?? 'N/A';
                        $estCodMain = trim(strtoupper($cuentaRef?->estadoActual?->codigo ?? ''));
                        $estTipoMain = trim(strtoupper($cuentaRef?->estadoActual?->tipo ?? ''));
                        $estadoNombreLower = trim(mb_strtolower($estadoNombre));

                        $esTransMain = (
                            in_array($estCodMain, ['REV1_PASA', 'SAP_OK', 'FAC_OK', 'FIR_OK', 'HAC_OK'], true) ||
                            ($estTipoMain === 'APROBADO' && $estCodMain !== 'FIN_OK') ||
                            $estadoNombreLower === 'pasa' ||
                            $estadoNombreLower === 'con ingreso mercancia' ||
                            $estadoNombreLower === 'facturada' ||
                            $estadoNombreLower === 'firmada' ||
                            $estadoNombreLower === 'radicada'
                        );

                        $claseBadge = match (strtolower($estadoNombre)) {
                            'devuelta' => 'bg-devuelta',
                            'en revision' => 'bg-revision',
                            'en espera' => 'bg-espera',
                            'en espera ingreso mercancia' => 'bg-espera',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <td>
                        @if ($esTransMain)
                            <!-- DUMP-DEBUG: tipo={{ $estTipoMain }}, codigo={{ $estCodMain }}, nombre={{ $estadoNombre }} -->
                            {{-- NO RENDERIZAR NADA PARA EVITAR RUIDO VISUAL TOTALMENTE --}}
                        @else
                            <!-- DUMP-DEBUG: tipo={{ $estTipoMain }}, codigo={{ $estCodMain }}, nombre={{ $estadoNombre }} -->
                            <span class="badge-pill-custom {{ $claseBadge }}" data-codigo="{{ $estCodMain }}" data-tipo="{{ $estTipoMain }}">
                                {{ $estadoNombre }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $rp->numero_rp ?? 'N/A' }}</td>
                    <td>{{ $rp && $rp->fecha_rp ? $rp->fecha_rp->format('d/m/Y') : 'N/A' }}</td>
                    <td>${{ number_format($rp->valor_rp ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $contrato->fecha_inicio ? $contrato->fecha_inicio->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $contrato->fecha_fin ? $contrato->fecha_fin->format('d/m/Y') : 'N/A' }}
                    </td>
                    <td>{{ $contrato->supervisor?->nombre_completo ?? 'N/A' }}</td>
                    <td>
                        @forelse($cuentasColl->sortBy('numero_cuenta') as $c)
                            @php
                                $badgeClass = $c->finalizada
                                    ? 'bg-success'
                                    : ($c->id === $cuentaActiva?->id ? 'bg-warning text-dark' : 'bg-secondary');
                                $badgeTitle = '#' . $c->numero_cuenta . ': ' . ($c->estadoActual?->nombre ?? 'Sin estado');
                            @endphp
                            <span class="badge {{ $badgeClass }}" title="{{ $badgeTitle }}"
                                  style="font-size: 0.65rem; margin: 1px; cursor: default;">
                                #{{ $c->numero_cuenta }}
                            </span>
                        @empty
                            <span class="text-muted">0</span>
                        @endforelse
                    </td>
                    <td>{{ $pagosTotales }}</td>
                    <td>{{ $facturasRadicadas }}</td>
                    <td class="text-center">
                        <div class="progress-pills" style="color: {{ $pCuentas >= 100 ? '#059669' : ($pCuentas > 50 ? '#4f46e5' : '#ef4444') }}; background: {{ $pCuentas >= 100 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(79, 70, 229, 0.1)' }};">
                            <i class="bi {{ $pCuentas >= 100 ? 'bi-check-circle-fill' : 'bi-activity' }}"></i>
                            {{ number_format($pCuentas, 1) }}%
                        </div>
                    </td>

                    {{-- Información de Seguridad Social --}}
                    <td>{{ $ssVigente?->entidadSalud?->nombre ?? 'N/A' }}</td>
                    <td>{{ $ssVigente?->entidadPension?->nombre ?? 'N/A' }}</td>
                    <td>{{ $ssVigente?->entidadArl?->nombre ?? 'N/A' }}</td>

                    <td class="fw-bold">{{ $cuentaRef?->ss_ultima_cuenta ?? 'N/A' }}</td>
                    <td>{{ $cuentaRef?->radicado_por }}</td>
                    <td>{{ $cuentaRef?->fecha_radicacion ? $cuentaRef->fecha_radicacion->format('d/m/Y H:i') : 'N/A' }}
                    </td>
                    <td>
                        <small class="text-truncate d-inline-block" style="max-width: 150px;"
                            title="{{ $cuentaRef?->observaciones }}">
                            {{ $cuentaRef?->observaciones ?? 'Sin observaciones' }}
                        </small>
                    </td>
                    {{-- BLOQUES DINÁMICOS: Renderizado automático de todas las etapas del workflow --}}
                    @foreach($bloques as $b)
                        @php 
                            $histBlock = $cuentaRef?->estadosBloques?->where('bloque_id', $b->id)->first();
                        @endphp
                        <td class="estado-bloque-cell">
                            @if ($histBlock && $histBlock->estadoActual)
                                @php
                                    $estN  = trim($histBlock->estadoActual->nombre ?? '');
                                    $estTipo = trim(strtoupper($histBlock->estadoActual->tipo ?? ''));
                                    $estCod = trim(strtoupper($histBlock->estadoActual->codigo ?? ''));
                                    $estNLower = trim(mb_strtolower($estN));

                                    $esTransicional = (
                                        $b->id != 6 &&
                                        $b->codigo !== 'FIN' &&
                                        (
                                            in_array($estCod, ['REV1_PASA', 'SAP_OK', 'FAC_OK', 'FIR_OK', 'HAC_OK'], true) ||
                                            ($estTipo === 'APROBADO' && $estCod !== 'FIN_OK') ||
                                            $estNLower === 'pasa' ||
                                            $estNLower === 'con ingreso mercancia' ||
                                            $estNLower === 'facturada' ||
                                            $estNLower === 'firmada' ||
                                            $estNLower === 'radicada'
                                        )
                                    );
                                    
                                    $esDev = !$esTransicional && (
                                        str_contains($estNLower, 'devuelta') ||
                                        str_contains($estNLower, 'devolución') ||
                                        str_contains($estNLower, 'devuelto')
                                    );
                                @endphp
                                <!-- DUMP-DEBUG: bloque={{ $b->codigo }}, tipo={{ $estTipo }}, codigo={{ $estCod }}, nombre={{ $estN }}, esTransicional={{ $esTransicional ? 'SI' : 'NO' }} -->
                                @if ($esTransicional)
                                    {{-- TOTALMENTE OCULTO --}}
                                @else
                                    <span class="badge {{ $esDev ? 'bg-danger' : ($histBlock->bloque_completado ? 'bg-success' : 'bg-warning text-dark') }} estado-bloque-badge" data-codigo="{{ $estCod }}" data-tipo="{{ $estTipo }}">
                                        {{ $estN }}
                                    </span>
                                @endif
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
 
                    <td>{{ $cuentaRef?->ultima_factura_hacienda ?? 'N/A' }}</td>
                    <td>{{ $cuentaRef?->observacion_hacienda ?? 'N/A' }}</td>
                    <td class="text-center">{{ $cuentaRef?->diferencia_cuentas ?? 0 }}</td>
                    <td class="text-center">
                        @php $limiteCuentas = max($pagosTotales, 1); @endphp
                        @if ($totalCuentas >= $limiteCuentas)
                            <span class="badge bg-secondary"><i class="bi bi-lock me-1"></i> LÍMITE ({{ $limiteCuentas }})</span>
                        @else
                            @php $cuentaParaIniciar = $cuentaActiva ?? $cuentasColl->first(); @endphp
                            <button type="button" class="btn btn-sm fw-bold px-3 py-1 animate-in"
                                onclick="startParallelAccount({{ $cuentaParaIniciar->id }}, '{{ $contrato->numero_contrato }}', {{ $totalCuentas + 1 }})"
                                title="Iniciar Cuenta #{{ $totalCuentas + 1 }}"
                                style="border-radius: 10px; font-size: 0.7rem; background: #4f46e5; color: white; border: none; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);">
                                <i class="bi bi-play-circle-fill me-1"></i> SIGUIENTE #{{ $totalCuentas + 1 }}
                            </button>
                        @endif
                    </td>
                    @if ($canEditDashboard)
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn-action-premium"
                                    onclick="showHistory({{ $cuentaRef?->id }}, '{{ $contrato->numero_contrato ?? 'N/A' }}')"
                                    title="Ver Historial">
                                    <span class="govco-svg govco-clock"></span>
                                </button>
                                <button type="button" class="btn-action-premium"
                                    onclick="editAccount({{ $cuentaRef?->id }})" title="Editar Cuenta">
                                    <span class="govco-svg govco-edit"></span>
                                </button>
                                <button type="button" class="btn-action-premium"
                                    onclick="deleteContrato({{ $contrato->id }}, '{{ $contrato->numero_contrato ?? 'N/A' }}')"
                                    title="Eliminar Contrato GLOBALMENTE"
                                    style="color: #dc2626; background: rgba(220, 38, 38, 0.05);">
                                    <i class="bi bi-trash fs-6"></i>
                                </button>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="35" class="text-center py-4 text-muted">No se encontraron contratos con cuentas de cobro
                        radicadas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 d-flex justify-content-center" id="pagination-links">
    {{ $contratos->links() }}
</div>

<script>
(function() {
    var TRANSICIONALES = ['pasa', 'ingreso', 'facturada', 'firmada', 'radicada', 'aprobado', 'rev1_pasa', 'sap_ok', 'fac_ok', 'fir_ok', 'hac_ok'];
    
    function ocultarTransicionales() {
        document.querySelectorAll('.estado-bloque-cell .badge.estado-bloque-badge, .badge-pill-custom, .badge').forEach(function(el) {
            var texto = el.textContent.trim().toLowerCase();
            var codigo = (el.getAttribute('data-codigo') || '').toLowerCase();
            var tipo = (el.getAttribute('data-tipo') || '').toLowerCase();
            
            // Si no tiene texto, ignoramos
            if (!texto) return;

            var matchTexto = (
                texto === 'pasa' || 
                texto === 'con ingreso mercancia' || 
                texto === 'facturada' || 
                texto === 'firmada' || 
                texto === 'radicada' || 
                TRANSICIONALES.includes(texto)
            );
            var matchCodigo = TRANSICIONALES.some(t => codigo.includes(t) && t !== 'pasa' && t !== 'ingreso' && t !== 'facturada' && t !== 'firmada' && t !== 'radicada');
            var matchTipo = (tipo === 'aprobado');

            // Asegurarse de no ocultar accidentalmente "sin estado", "pendiente" ni "finalizado"
            if ((matchTexto || matchCodigo || matchTipo) && texto !== 'sin estado' && texto !== 'pendiente' && !texto.includes('finalizado')) {
                // FUERZA BRUTA: Destruir el elemento visualmente
                el.style.setProperty('display', 'none', 'important');
                el.style.setProperty('opacity', '0', 'important');
                el.style.setProperty('visibility', 'hidden', 'important');
                el.style.setProperty('width', '0', 'important');
                el.style.setProperty('height', '0', 'important');
                el.style.setProperty('margin', '0', 'important');
                el.style.setProperty('padding', '0', 'important');
                el.innerHTML = '';
            }
        });
    }

    ocultarTransicionales();
    var obs = new MutationObserver(ocultarTransicionales);
    var target = document.getElementById('tableContainer') || document.querySelector('.table-responsive');
    if (target) {
        obs.observe(target, { childList: true, subtree: true, attributes: true });
    }
    setInterval(ocultarTransicionales, 1500);
})();
</script>
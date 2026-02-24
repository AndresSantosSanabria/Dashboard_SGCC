@foreach ($contratos as $c)
    @php
        $badgeMap = [
            'OK' => ['class' => 'badge-ok', 'icon' => 'bi-check-circle-fill'],
            'PENDIENTE' => ['class' => 'badge-pend', 'icon' => 'bi-hourglass-split'],
            'RECHAZADO' => ['class' => 'badge-crit', 'icon' => 'bi-x-circle-fill'],
            'CRÍTICO' => ['class' => 'badge-crit', 'icon' => 'bi-exclamation-triangle-fill'],
            'N/A' => ['class' => 'badge-na', 'icon' => 'bi-dash-circle'],
            '' => ['class' => 'badge-vacio', 'icon' => 'bi-circle'],
        ];

        $riskClass = match ($c->global_status) {
            'EN PROGRESO' => 'risk-med text-primary',
            'COMPLETO' => 'risk-low',
            'PENDIENTES' => 'risk-med',
            'CRÍTICO' => 'risk-high',
            default => 'text-muted',
        };
    @endphp
    <tr class="contract-row">
        <td class="stk-gest text-center">
            <button class="btn btn-sm btn-light border p-1" onclick='openEditModal({!! json_encode($c) !!})'
                title="Gestionar">
                <i class="bi bi-pencil-square text-primary"></i>
            </button>
        </td>
        <td class="stk-risk text-center">
            <div class="badge-pill-saas {{ $riskClass }} w-100 justify-content-center" style="font-size: 0.6rem;">
                {{ $c->global_status }}
            </div>
            <div class="d-flex align-items-center gap-2 mt-1 mx-auto" style="width: 85%;">
                <div class="progress flex-grow-1" style="height: 4px;">
                    <div class="progress-bar bg-success" style="width: {{ $c->perc_cumplimiento }}%">
                    </div>
                </div>
                <span class="small fw-bold text-muted" style="font-size: 0.6rem;">{{ round($c->perc_cumplimiento, 0) }}%</span>
            </div>
        </td>
        <td class="stk-saas stk-proc text-muted small text-center">{{ $c->numero_proceso ?: '-' }}
        </td>
        <td class="stk-saas stk-num fw-bold text-primary text-center">
            {{ $c->numero_contrato }}</td>
        <td class="stk-saas stk-nom" style="border-right: 2px solid #e2e8f0;">
            <div class="fw-bold small" style="white-space:normal; min-width:180px">
                {{ $c->contratista->nombre_completo ?? 'N/A' }}</div>
        </td>

        <td class="small">{{ $c->tipo_contratista ?: '-' }}</td>
        <td class="small text-muted text-center">{{ $c->supervisor->nombre_completo ?? 'N/A' }}
        </td>
        <td>
            <div class="text-truncate small text-muted" style="max-width:120px" title="{{ $c->objeto }}">
                {{ $c->objeto }}</div>
        </td>
        <td class="fw-bold text-success text-end small">
            ${{ number_format($c->monto_total, 0, ',', '.') }}</td>
        <td class="text-center">
            @if ($c->link_secop)
                <a href="{{ $c->link_secop }}" target="_blank" class="text-primary"><i class="bi bi-link-45deg"></i></a>
            @endif
        </td>
        <td class="text-center small">{{ $c->no_planta }}</td>
        <td class="small">{{ $c->concepto_precontractual }}</td>
        <td class="small text-center" style="border-right: 2px solid #e2e8f0">
            {{ $c->cdp_codigo }}</td>

        {{-- Checklist Técnico --}}
        @php $checklistFields = ['estudios_previos_status', 'soportes_status', 'idoneidad_status', 'acuerdo_confidencialidad_status', 'clausulado_status', 'acta_inicio_status', 'delegacion_status', 'arl_status', 'rpc_status']; @endphp
        @foreach ($checklistFields as $field)
            @php $b = $badgeMap[$c->$field] ?? $badgeMap['']; @endphp
            <td class="text-center">
                <div class="dropdown">
                    <div class="badge-pill-saas {{ $b['class'] }} w-100" data-bs-toggle="dropdown">
                        <i class="bi {{ $b['icon'] }}"></i>
                        {{ $c->$field ?: 'VACÍO' }}
                    </div>
                    <ul class="dropdown-menu shadow-lg border-0" style="font-size: 0.75rem;">
                        <li><a class="dropdown-item py-2" href="#"
                                onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'OK', this)"><i
                                    class="bi bi-check-circle-fill text-success me-2"></i> OK</a>
                        </li>
                        <li><a class="dropdown-item py-2" href="#"
                                onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'PENDIENTE', this)"><i
                                    class="bi bi-hourglass-split text-warning me-2"></i>
                                PENDIENTE</a></li>
                        <li><a class="dropdown-item py-2" href="#"
                                onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'RECHAZADO', this)"><i
                                    class="bi bi-x-circle-fill text-danger me-2"></i> RECHAZADO</a>
                        </li>
                        <li><a class="dropdown-item py-2" href="#"
                                onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'N/A', this)"><i
                                    class="bi bi-dash-circle text-muted me-2"></i> N/A</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item py-2" href="#"
                                onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', '', this)"><i
                                    class="bi bi-circle text-light me-2"></i> VACÍO</a></li>
                    </ul>
                </div>
            </td>
        @endforeach

        {{-- Estado SECOP (Especial con Colores) --}}
        @php
            $secopStatus = strtoupper($c->secop_estado_contrato);
            $secopClass = 'badge-vacio';
            if (in_array($secopStatus, ['CERRADO', 'TERMINADO'])) $secopClass = 'badge-secop-verde';
            elseif ($secopStatus === 'EN EJECUCION') $secopClass = 'badge-secop-amarillo';
        @endphp
        <td class="text-center" style="border-right: 2px solid #e2e8f0">
            <div class="dropdown">
                <div class="badge-pill-saas {{ $secopClass }} w-100" data-bs-toggle="dropdown">
                    {{ $c->secop_estado_contrato ?: 'VACÍO' }}
                </div>
                <ul class="dropdown-menu shadow-lg border-0" style="font-size: 0.75rem;">
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'secop_estado_contrato', 'CERRADO', this)">CERRADO</a></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'secop_estado_contrato', 'TERMINADO', this)">TERMINADO</a></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'secop_estado_contrato', 'EN EJECUCION', this)">EN EJECUCION</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'secop_estado_contrato', '', this)">VACÍO</a></li>
                </ul>
            </div>
        </td>

        {{-- Gestión --}}
        <td class="text-center">
            <div class="dropdown">
                <div class="badge-pill-saas badge-na w-100" data-bs-toggle="dropdown" style="font-size: 0.65rem;">
                    {{ $c->aprobado_y_pagado ?: '-' }}
                </div>
                <ul class="dropdown-menu shadow-lg border-0" style="font-size: 0.75rem;">
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'aprobado_y_pagado', 'Con supervisor', this)">Con supervisor</a></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'aprobado_y_pagado', 'Pagado', this)">Pagado</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'aprobado_y_pagado', '', this)">VACÍO</a></li>
                </ul>
            </div>
        </td>
        <td class="text-center" style="border-right: 2px solid #e2e8f0">
            <div class="dropdown">
                <div class="badge-pill-saas badge-na w-100" data-bs-toggle="dropdown" style="font-size: 0.65rem;">
                    {{ $c->modificaciones_y_cierre ?: '-' }}
                </div>
                <ul class="dropdown-menu shadow-lg border-0" style="font-size: 0.75rem;">
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'modificaciones_y_cierre', 'con supervisor', this)">con supervisor</a></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'modificaciones_y_cierre', 'Cerrado por supervisor', this)">Cerrado por supervisor</a></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'modificaciones_y_cierre', 'Secretaria', this)">Secretaria</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'modificaciones_y_cierre', '', this)">VACÍO</a></li>
                </ul>
            </div>
        </td>

        {{-- Ejecución Mensual --}}
        @for ($i = 1; $i <= 12; $i++)
            @foreach (["cta{$i}_rep_status", "cta{$i}_secop_status", "cta{$i}_sia_status"] as $field)
                @php $b = $badgeMap[$c->$field] ?? $badgeMap['']; @endphp
                <td class="text-center"
                    style="{{ $i == 12 && $field == 'cta12_sia_status' ? 'border-right: 2px solid #e2e8f0' : '' }}">
                    <div class="dropdown">
                        <div class="badge-pill-saas {{ $b['class'] }} w-100" data-bs-toggle="dropdown">
                            {{ $c->$field ?: 'V' }}
                        </div>
                        <ul class="dropdown-menu shadow-lg border-0" style="font-size: 0.75rem;">
                            <li><a class="dropdown-item py-2" href="#"
                                    onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'OK', this)">OK</a>
                            </li>
                            <li><a class="dropdown-item py-2" href="#"
                                    onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'PENDIENTE', this)">PENDIENTE</a>
                            </li>
                            <li><a class="dropdown-item py-2" href="#"
                                    onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'N/A', this)">N/A</a>
                            </li>
                            <li><a class="dropdown-item py-2" href="#"
                                    onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', '', this)">VACÍO</a>
                            </li>
                        </ul>
                    </div>
                </td>
            @endforeach
        @endfor

        {{-- Cierre --}}
        @php $cierreFields = ['evaluacion_proveedor_status', 'acta_cierre_expediente_status', 'requiere_acta_liq_status', 'acta_liq_repositorio_status', 'acta_liq_secop_status', 'acta_liq_sia_status']; @endphp
        @foreach ($cierreFields as $field)
            @php $b = $badgeMap[$c->$field] ?? $badgeMap['']; @endphp
            <td class="text-center" style="{{ $field == 'acta_liq_sia_status' ? 'border-right: 2px solid #e2e8f0' : '' }}">
                <div class="dropdown">
                    <div class="badge-pill-saas {{ $b['class'] }} w-100" data-bs-toggle="dropdown">
                        <i class="bi {{ $b['icon'] }}"></i>
                        {{ $c->$field ?: 'VACÍO' }}
                    </div>
                    <ul class="dropdown-menu shadow-lg border-0" style="font-size: 0.75rem;">
                        <li><a class="dropdown-item py-2" href="#"
                                onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'OK', this)">OK/SI</a>
                        </li>
                        <li><a class="dropdown-item py-2" href="#"
                                onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'PENDIENTE', this)">NO/PEND</a>
                        </li>
                        <li><a class="dropdown-item py-2" href="#"
                                onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'N/A', this)">N/A</a>
                        </li>
                    </ul>
                </div>
            </td>
        @endforeach

        {{-- Resultados --}}
        <td class="fw-bold text-danger text-end small">
            ${{ number_format($c->saldo, 0, ',', '.') }}</td>
        <td class="small text-muted">
            <div class="text-truncate" style="max-width:100px">
                {{ $c->observacion_1_razon ?: '-' }}</div>
        </td>
        <td class="small text-muted">
            <div class="text-truncate" style="max-width:100px">
                {{ $c->observacion_2_accion ?: '-' }}</div>
        </td>
        <td class="small text-muted">
            <div class="text-truncate" style="max-width:100px">
                {{ $c->razon_no_liquidacion ?: '-' }}</div>
        </td>
        <td class="small text-muted text-center">{{ $c->abogado_responsable ?: '-' }}</td>
        <td class="small text-muted text-center">{{ $c->contador_responsable ?: '-' }}</td>
    </tr>
@endforeach

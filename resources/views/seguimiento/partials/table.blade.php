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
            'EN PROGRESO' => 'badge-ok',
            'COMPLETO' => 'badge-ok',
            'PENDIENTES' => 'badge-pend',
            'CRÍTICO' => 'badge-crit',
            default => 'badge-na',
        };
    @endphp
    <tr class="contract-row" data-id="{{ $c->id }}">
        {{-- GESTIÓN --}}
        <td class="stk-saas stk-gest text-center">
            <div class="d-flex flex-column gap-2 align-items-center">
                <button class="btn btn-sm btn-light border shadow-sm rounded-3 p-1" onclick='openEditModal({!! json_encode($c) !!})' title="Gestionar Ficha">
                    <i class="bi bi-pencil-square text-primary"></i>
                </button>
                <button class="btn btn-sm btn-light border shadow-sm rounded-3 p-1" onclick="deleteContrato({{ $c->id }}, '{{ $c->numero_contrato }}')" title="Eliminar definitivamente">
                    <i class="bi bi-trash-fill text-danger"></i>
                </button>
            </div>
        </td>

        {{-- SCORE --}}
        <td class="stk-saas stk-score text-center">
            <div class="badge-pill-saas {{ $riskClass }} w-100 justify-content-center mb-1">
                {{ $c->global_status }}
            </div>
            <div class="progress" style="height: 4px; width: 80%; margin: 0 auto;">
                <div class="progress-bar bg-primary" style="width: {{ $c->perc_cumplimiento }}%"></div>
            </div>
            <span class="extra-small fw-bold text-muted">{{ round($c->perc_cumplimiento) }}%</span>
        </td>

        {{-- PROCESO --}}
        <td class="stk-saas stk-proc text-center text-muted small">
            {{ $c->numero_proceso ?: '-' }}
        </td>

        {{-- Nº CONTRATO --}}
        <td class="stk-saas stk-num fw-800 text-primary text-center">
            {{ $c->numero_contrato }}
        </td>

        {{-- CONTRATISTA --}}
        <td class="stk-saas stk-nom">
            <div class="fw-bold small lh-sm" style="max-width: 280px; white-space: normal;">
                {{ $c->contratista->nombre_completo ?? 'No asignado' }}
            </div>
        </td>

        {{-- INFO BASE --}}
        <td class="text-center">
            <div class="dropdown">
                <div class="badge-pill-saas badge-na w-100" data-bs-toggle="dropdown" 
                     data-original-val="{{ $c->tipo_contratista ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="tipo_contratista">
                    {{ $c->tipo_contratista ?: 'VACÍO' }}
                </div>
                <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn" style="font-size: 0.8rem;">
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'tipo_contratista', 'Natural', this)">Natural</a></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'tipo_contratista', 'Juridica', this)">Juridica</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'tipo_contratista', '', this)">Limpiar</a></li>
                </ul>
            </div>
        </td>
        <td class="small text-muted text-center">
            {{ $c->supervisor->nombre_completo ?? 'S/S' }}
        </td>
        <td>
            <div class="text-truncate small text-muted" style="max-width:200px" title="{{ $c->objeto }}">
                {{ $c->objeto }}
            </div>
        </td>
        <td class="fw-bold text-dark text-end font-monospace" style="border-right: 2px solid #E2E8F0">
            ${{ number_format($c->monto_total, 0, ',', '.') }}
        </td>

        {{-- CHECKLIST --}}
        <td class="text-center">
            <div class="d-flex align-items-center justify-content-center gap-1">
                @if ($c->link_secop)
                    <a href="{{ $c->link_secop }}" target="_blank" class="text-accent fs-5" title="Ver en SECOP"><i class="bi bi-box-arrow-up-right"></i></a>
                    <button class="btn btn-link btn-sm p-0 text-muted" onclick="openLinkModal({{ $c->id }}, '{{ $c->link_secop }}')" title="Editar Link"><i class="bi bi-pencil-square"></i></button>
                @else
                    <button class="btn btn-link btn-sm text-decoration-none text-muted p-0" onclick="openLinkModal({{ $c->id }}, '')" title="Agregar Link"><i class="bi bi-plus-circle-fill fs-5"></i></button>
                @endif
            </div>
        </td>
        <td class="text-center">
            @php $bPlanta = $badgeMap[$c->planta_status] ?? $badgeMap['']; @endphp
            <div class="dropdown">
                <div class="badge-pill-saas {{ $bPlanta['class'] }} w-100" data-bs-toggle="dropdown"
                     data-original-val="{{ $c->planta_status ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="planta_status">
                    {{ $c->planta_status ?: 'V' }}
                </div>
                <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn">
                    @foreach(['OK','PENDIENTE','N/A'] as $st)
                        <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'planta_status', '{{ $st }}', this)">{{ $st }}</a></li>
                    @endforeach
                </ul>
            </div>
        </td>
        <td class="text-center">
            @php $bConcepto = $badgeMap[$c->concepto_status] ?? $badgeMap['']; @endphp
            <div class="dropdown">
                <div class="badge-pill-saas {{ $bConcepto['class'] }} w-100" data-bs-toggle="dropdown"
                     data-original-val="{{ $c->concepto_status ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="concepto_status">
                    {{ $c->concepto_status ?: 'V' }}
                </div>
                <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn">
                    @foreach(['OK','PENDIENTE','N/A'] as $st)
                        <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'concepto_status', '{{ $st }}', this)">{{ $st }}</a></li>
                    @endforeach
                </ul>
            </div>
        </td>
        <td class="text-center">
            @php $bCdp = $badgeMap[$c->cdp_status] ?? $badgeMap['']; @endphp
            <div class="dropdown">
                <div class="badge-pill-saas {{ $bCdp['class'] }} w-100" data-bs-toggle="dropdown"
                     data-original-val="{{ $c->cdp_status ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="cdp_status">
                    {{ $c->cdp_status ?: 'V' }}
                </div>
                <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn">
                    @foreach(['OK','PENDIENTE','N/A'] as $st)
                        <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'cdp_status', '{{ $st }}', this)">{{ $st }}</a></li>
                    @endforeach
                </ul>
            </div>
        </td>

        {{-- Checklist Técnico Iterativo --}}
        @php $checklistFields = ['estudios_previos_status', 'soportes_status', 'idoneidad_status', 'acuerdo_confidencialidad_status', 'clausulado_status', 'acta_inicio_status', 'delegacion_status', 'arl_status', 'rpc_status']; @endphp
        @foreach ($checklistFields as $field)
            @php $b = $badgeMap[$c->$field] ?? $badgeMap['']; @endphp
            <td class="text-center" style="{{ $loop->last ? 'border-right: 2px solid #E2E8F0' : '' }}">
                <div class="dropdown">
                    <div class="badge-pill-saas {{ $b['class'] }} w-100 justify-content-center" data-bs-toggle="dropdown"
                         data-original-val="{{ $c->$field ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="{{ $field }}">
                        <i class="bi {{ $b['icon'] }}"></i>
                    </div>
                    <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn">
                        <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'OK', this)"><i class="bi bi-check-circle-fill text-success me-2"></i> OK</a></li>
                        <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'PENDIENTE', this)"><i class="bi bi-hourglass-split text-warning me-2"></i> PENDIENTE</a></li>
                        <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'RECHAZADO', this)"><i class="bi bi-x-circle-fill text-danger me-2"></i> RECHAZADO</a></li>
                        <li><a class="dropdown-item py-2" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'N/A', this)"><i class="bi bi-dash-circle text-muted me-2"></i> N/A</a></li>
                    </ul>
                </div>
            </td>
        @endforeach

        {{-- Gestión --}}
        @php
            $secopStatus = strtoupper($c->secop_estado_contrato);
            $secopClass = 'badge-vacio';
            if (in_array($secopStatus, ['CERRADO', 'TERMINADO'])) $secopClass = 'badge-secop-verde';
            elseif ($secopStatus === 'EN EJECUCION') $secopClass = 'badge-secop-amarillo';
        @endphp
        <td class="text-center">
            <div class="dropdown">
                <div class="badge-pill-saas {{ $secopClass }} w-100" data-bs-toggle="dropdown"
                     data-original-val="{{ $c->secop_estado_contrato ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="secop_estado_contrato">
                    {{ $c->secop_estado_contrato ?: 'VACÍO' }}
                </div>
                <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn">
                    @foreach(['CERRADO','TERMINADO','EN EJECUCION'] as $st)
                        <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'secop_estado_contrato', '{{ $st }}', this)">{{ $st }}</a></li>
                    @endforeach
                </ul>
            </div>
        </td>
        <td class="text-center">
            <div class="dropdown">
                <div class="badge-pill-saas badge-na w-100" data-bs-toggle="dropdown" 
                     data-original-val="{{ $c->aprobado_y_pagado ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="aprobado_y_pagado">
                    {{ $c->aprobado_y_pagado ?: '-' }}
                </div>
                <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn">
                    <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'aprobado_y_pagado', 'Con supervisor', this)">Con supervisor</a></li>
                    <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'aprobado_y_pagado', 'Pagado', this)">Pagado</a></li>
                </ul>
            </div>
        </td>
        <td class="text-center" style="border-right: 2px solid #E2E8F0">
            <div class="dropdown">
                <div class="badge-pill-saas badge-na w-100" data-bs-toggle="dropdown"
                     data-original-val="{{ $c->modificaciones_y_cierre ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="modificaciones_y_cierre">
                    {{ $c->modificaciones_y_cierre ?: '-' }}
                </div>
                <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn">
                    @foreach(['con supervisor','Cerrado por supervisor','Secretaria'] as $st)
                        <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, 'modificaciones_y_cierre', '{{ $st }}', this)">{{ $st }}</a></li>
                    @endforeach
                </ul>
            </div>
        </td>

        {{-- Ejecución Mensual --}}
        @for ($i = 1; $i <= 12; $i++)
            @foreach (["cta{$i}_rep_status", "cta{$i}_secop_status", "cta{$i}_sia_status"] as $field)
                @php $b = $badgeMap[$c->$field] ?? $badgeMap['']; @endphp
                <td class="text-center" style="{{ $i == 12 && $field == 'cta12_sia_status' ? 'border-right: 2px solid #E2E8F0' : '' }}">
                    <div class="dropdown">
                        <div class="badge-pill-saas {{ $b['class'] }} w-100 justify-content-center" data-bs-toggle="dropdown"
                             data-original-val="{{ $c->$field ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="{{ $field }}">
                            {{ $c->$field ?: 'V' }}
                        </div>
                        <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn">
                            @foreach(['OK','PENDIENTE','N/A'] as $st)
                                <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', '{{ $st }}', this)">{{ $st }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </td>
            @endforeach
        @endfor

        {{-- Cierre --}}
        @php $cierreFields = ['evaluacion_proveedor_status', 'acta_cierre_expediente_status', 'requiere_acta_liq_status', 'acta_liq_repositorio_status', 'acta_liq_secop_status', 'acta_liq_sia_status']; @endphp
        @foreach ($cierreFields as $field)
            @php $b = $badgeMap[$c->$field] ?? $badgeMap['']; @endphp
            <td class="text-center" style="{{ $field == 'acta_liq_sia_status' ? 'border-right: 2px solid #E2E8F0' : '' }}">
                <div class="dropdown">
                    <div class="badge-pill-saas {{ $b['class'] }} w-100 justify-content-center" data-bs-toggle="dropdown"
                         data-original-val="{{ $c->$field ?: '' }}" data-contrato="{{ $c->numero_contrato }}" data-field="{{ $field }}">
                        <i class="bi {{ $b['icon'] }}"></i>
                    </div>
                    <ul class="dropdown-menu shadow-premium border-0 animate-fadeIn">
                        <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'OK', this)">OK/SI</a></li>
                        <li><a class="dropdown-item" href="#" onclick="updateBadgeStatus(event, {{ $c->id }}, '{{ $field }}', 'PENDIENTE', this)">NO/PEND</a></li>
                    </ul>
                </div>
            </td>
        @endforeach

        {{-- Resultados --}}
        <td class="text-center" style="border-right: 2px solid #E2E8F0; background: #fdfdfd;">
            @if($c->es_proceso_completado)
                <div class="d-flex flex-column align-items-center">
                    <span class="badge bg-success text-white border-0 py-2 px-3 rounded-pill extra-small fw-800 shadow-sm">
                        <i class="bi bi-check-all me-1"></i> COMPLETADO
                    </span>
                    <span class="extra-small text-muted mt-1">Todas las cuentas tramitadas</span>
                </div>
            @elseif($c->puede_iniciar_siguiente)
                <button class="btn btn-sm btn-primary w-100 fw-800 rounded-pill shadow-sm py-2 d-flex align-items-center justify-content-center gap-2 animate-pulse-soft" 
                        onclick="crearSiguienteCuenta({{ $c->id }}, {{ $c->siguiente_numero_cuenta }}, '{{ $c->numero_contrato }}')">
                    <i class="bi bi-play-circle-fill"></i> SIGUIENTE #{{ $c->siguiente_numero_cuenta }}
                </button>
            @else
                <div class="d-flex flex-column align-items-center">
                    <span class="badge bg-light text-primary border py-2 px-3 rounded-pill extra-small fw-bold">EN TRÁMITE #{{ $c->cuentaActual->numero_cuenta ?? 1 }}</span>
                    <span class="extra-small text-muted mt-1">{{ $c->cuentaActual->estadoActual->nombre ?? 'N/A' }}</span>
                </div>
            @endif
        </td>

        <td class="fw-bold text-danger text-end font-monospace">
            ${{ number_format($c->saldo, 0, ',', '.') }}
        </td>
        <td class="small text-muted"><div class="text-truncate" style="max-width:120px">{{ $c->observacion_1_razon ?: '-' }}</div></td>
        <td class="small text-muted"><div class="text-truncate" style="max-width:120px">{{ $c->observacion_2_accion ?: '-' }}</div></td>
        <td class="small text-muted"><div class="text-truncate" style="max-width:120px">{{ $c->razon_no_liquidacion ?: '-' }}</div></td>
        <td class="extra-small text-muted text-center">{{ $c->abogado_responsable ?: '-' }}</td>
        <td class="extra-small text-muted text-center">{{ $c->contador_responsable ?: '-' }}</td>
    </tr>
@endforeach

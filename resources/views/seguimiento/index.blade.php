@extends('layouts.app')

@section('title', 'Dashboard Ejecutivo SECOP')

@push('styles')
    @vite(['resources/views/seguimiento/seguimiento.css'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('page-content')
    <div class="dashboard-container animate-fadeIn">

        {{-- HEADER ESTRATÉGICO --}}
        <header class="db-header">
            <div>
                <h1>Dashboard de Seguimiento SECOP</h1>
                <p>Gestión de Contratación — SIA OBSERVA — Panel de Control Ejecutivo</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-saas-secondary" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                </button>
            </div>
        </header>

        {{-- ANALÍTICA RESUMEN SUPERIOR --}}
        <div class="analytics-summary animate-fadeIn">
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #16a34a !important;">
                <span class="label text-success">Contratos OK</span>
                <span class="val">{{ number_format($stats['ok_contratos']) }}</span>
                <span class="sub">Checklist completo</span>
            </div>
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #d97706 !important;">
                <span class="label text-warning">Con Pendientes</span>
                <span class="val">{{ number_format($stats['pend_contratos']) }}</span>
                <span class="sub">Gestión en proceso</span>
            </div>
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #dc2626 !important;">
                <span class="label text-danger">Estado Crítico</span>
                <span class="val">{{ number_format($stats['crit_contratos']) }}</span>
                <span class="sub">Acción inmediata</span>
            </div>
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #2563eb !important;">
                <span class="label text-primary">Cumplimiento Global</span>
                <div class="val">{{ number_format($stats['avg_cumplimiento'], 1) }}%</div>
                <div class="progress mt-1" style="height: 6px; background: #e2e8f0;">
                    <div class="progress-bar bg-primary" style="width: {{ $stats['avg_cumplimiento'] }}%"></div>
                </div>
            </div>
        </div>

        {{-- FILTROS MODERNOS --}}
        <div class="filter-card-saas shadow-sm animate-fadeIn">
            <div class="form-group-saas">
                <label>Nº de Contrato</label>
                <input type="text" id="filterContrato" class="input-saas" placeholder="Buscar Nº...">
            </div>
            <div class="form-group-saas">
                <label>Tipo de Contratista</label>
                <input type="text" id="filterTipo" class="input-saas" placeholder="Persona Natural/Jurídica...">
            </div>
            <div class="form-group-saas">
                <label>Supervisor</label>
                <select class="input-saas" id="filterSup">
                    <option value="">Todos</option>
                    @foreach ($supervisores as $s)
                        <option value="{{ $s->id }}">{{ $s->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group-saas" style="flex: 0 0 120px;">
                <button class="btn btn-saas-primary w-100 py-2" onclick="applyAdvancedFilters()">Filtrar</button>
            </div>
            <div class="form-group-saas" style="flex: 0 0 50px;">
                <button class="btn btn-link text-muted" onclick="window.location.href='{{ route('seguimiento.index') }}'"><i
                        class="bi bi-x-circle"></i></button>
            </div>
        </div>

        {{-- TABLA MAESTRA INTEGRAL --}}
        <div class="table-card-saas shadow-sm animate-fadeIn" style="animation-delay: 0.2s;">
            <div class="table-scroll-container">
                <table class="saas-table" id="masterTable">
                    <thead>
                        <tr style="height: 35px; background: #F8FAFC;">
                            <th class="stk-saas stk-gest" style="z-index: 12 !important;"></th>
                            <th class="stk-saas stk-risk" style="z-index: 12 !important;"></th>
                            <th class="stk-saas stk-proc" style="z-index: 12 !important;"></th>
                            <th class="stk-saas stk-cont" style="z-index: 12 !important; border-right: 2px solid #e2e8f0;">
                                IDENTIFICACIÓN</th>

                            <th colspan="9" style="border-right: 2px solid #e2e8f0; text-align: center;">INFORMACIÓN BASE
                            </th>
                            <th colspan="10"
                                style="border-right: 2px solid #e2e8f0; text-align: center; background: #FEF9C3; color: #854D0E;">
                                CHECKLIST TÉCNICO</th>
                            <th colspan="2"
                                style="border-right: 2px solid #e2e8f0; text-align: center; background: #F0FDF4; color: #166534;">
                                GESTIÓN</th>
                            <th colspan="24"
                                style="border-right: 2px solid #e2e8f0; text-align: center; background: #F1F5F9;">EJECUCIÓN
                                MENSUAL</th>
                            <th colspan="6"
                                style="border-right: 2px solid #e2e8f0; text-align: center; background: #EEF2FF; color: #1E40AF;">
                                CIERRE CONTRACTUAL</th>
                            <th colspan="6" style="text-align: center; background: #F8FAFC;">RESULTADOS Y RESPONSABLES
                            </th>
                        </tr>
                        <tr>
                            <th class="stk-saas stk-gest text-center">GESTIÓN</th>
                            <th class="stk-saas stk-risk text-center">PROGRESO</th>
                            <th class="stk-saas stk-proc text-center">PROCESO</th>
                            <th class="stk-saas stk-cont text-center" style="border-right: 2px solid #e2e8f0;">CONTRATO</th>

                            <th>TIPO CONTRATISTA</th>
                            <th>CONTRATISTA</th>
                            <th>SUPERVISOR</th>
                            <th>OBJETO</th>
                            <th>VALOR CONTRATO</th>
                            <th>LINK SECOP</th>
                            <th>NO PLANTA</th>
                            <th>CONCEPTO PRECON.</th>
                            <th style="border-right: 2px solid #e2e8f0">CDP</th>

                            {{-- Checklist Técnico --}}
                            <th>ESTUDIOS PREVIOS</th>
                            <th>SOPORTES</th>
                            <th>IDONEIDAD</th>
                            <th>CONFIDEN.</th>
                            <th>CLAUSULADO</th>
                            <th>ACTA INICIO</th>
                            <th>DELEGACIÓN</th>
                            <th>ARL</th>
                            <th>RP</th>
                            <th style="border-right: 2px solid #e2e8f0">ESTADO SECOP</th>

                            {{-- Gestión --}}
                            <th>APROBADO Y PAGADO</th>
                            <th style="border-right: 2px solid #e2e8f0">MODIF. Y CIERRE</th>

                            {{-- Ejecución (1 a 12) --}}
                            @for ($i = 1; $i <= 12; $i++)
                                <th title="Mes {{ $i }}">PAGADO SEC {{ $i }}</th>
                                <th title="Mes {{ $i }}"
                                    style="{{ $i == 12 ? 'border-right: 2px solid #e2e8f0' : '' }}">CUENTA SIA
                                    {{ $i }}</th>
                            @endfor

                            {{-- Cierre --}}
                            <th>EVAL. PROV.</th>
                            <th>ACTA CIERRE</th>
                            <th>REQ. ACTA LIQ</th>
                            <th>EN REPOS.</th>
                            <th>LIQ SECOP</th>
                            <th style="border-right: 2px solid #e2e8f0">LIQ SIA</th>

                            {{-- Resultados --}}
                            <th>SALDO</th>
                            <th>OBS 1 RAZON</th>
                            <th>OBS 2 ACCION</th>
                            <th>RAZON NO LIQ</th>
                            <th>ABOGADO</th>
                            <th>CONTADOR</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
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
                                <td class="stk-saas stk-gest text-center">
                                    <button class="btn btn-sm btn-light border p-1"
                                        onclick='openEditModal({!! json_encode($c) !!})' title="Gestionar">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>
                                </td>
                                <td class="stk-saas stk-risk text-center">
                                    <div class="badge-pill-saas {{ $riskClass }} w-100 justify-content-center"
                                        style="font-size: 0.6rem;">
                                        {{ $c->global_status }}
                                    </div>
                                    <div class="progress mt-1 mx-auto" style="height: 3px; width: 80%;">
                                        <div class="progress-bar bg-success" style="width: {{ $c->perc_cumplimiento }}%">
                                        </div>
                                    </div>
                                </td>
                                <td class="stk-saas stk-proc text-muted small text-center">{{ $c->numero_proceso ?: '-' }}
                                </td>
                                <td class="stk-saas stk-cont fw-bold text-primary text-center"
                                    style="border-right: 2px solid #e2e8f0;">{{ $c->numero_contrato }}</td>

                                <td class="small">{{ $c->tipo_contratista ?: '-' }}</td>
                                <td>
                                    <div class="fw-bold small" style="white-space:normal; min-width:180px">
                                        {{ $c->contratista->nombre_completo ?? 'N/A' }}</div>
                                </td>
                                <td class="small text-muted text-center">{{ $c->supervisor->nombre_completo ?? 'N/A' }}
                                </td>
                                <td>
                                    <div class="text-truncate small text-muted" style="max-width:120px"
                                        title="{{ $c->objeto }}">{{ $c->objeto }}</div>
                                </td>
                                <td class="fw-bold text-success text-end small">
                                    ${{ number_format($c->monto_total, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if ($c->link_secop)
                                        <a href="{{ $c->link_secop }}" target="_blank" class="text-primary"><i
                                                class="bi bi-link-45deg"></i></a>
                                    @endif
                                </td>
                                <td class="text-center small">{{ $c->no_planta }}</td>
                                <td class="small">{{ $c->concepto_precontractual }}</td>
                                <td class="small text-center" style="border-right: 2px solid #e2e8f0">
                                    {{ $c->cdp_codigo }}</td>

                                {{-- Checklist Técnico --}}
                                @php $checklistFields = ['estudios_previos_status', 'soportes_status', 'idoneidad_status', 'acuerdo_confidencialidad_status', 'clausulado_status', 'acta_inicio_status', 'delegacion_status', 'arl_status', 'rpc_status', 'secop_estado_contrato']; @endphp
                                @foreach ($checklistFields as $field)
                                    @php $b = $badgeMap[$c->$field] ?? $badgeMap['']; @endphp
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <div class="badge-pill-saas {{ $b['class'] }} w-100"
                                                data-bs-toggle="dropdown">
                                                <i class="bi {{ $b['icon'] }}"></i>
                                                {{ $c->$field ?: 'VACÍO' }}
                                            </div>
                                            <ul class="dropdown-menu shadow-lg border-0" style="font-size: 0.75rem;">
                                                <li><a class="dropdown-item py-2" href="#"
                                                        onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'OK', this)"><i
                                                            class="bi bi-check-circle-fill text-success me-2"></i> OK</a>
                                                </li>
                                                <li><a class="dropdown-item py-2" href="#"
                                                        onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'PENDIENTE', this)"><i
                                                            class="bi bi-hourglass-split text-warning me-2"></i>
                                                        PENDIENTE</a></li>
                                                <li><a class="dropdown-item py-2" href="#"
                                                        onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'RECHAZADO', this)"><i
                                                            class="bi bi-x-circle-fill text-danger me-2"></i> RECHAZADO</a>
                                                </li>
                                                <li><a class="dropdown-item py-2" href="#"
                                                        onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'N/A', this)"><i
                                                            class="bi bi-dash-circle text-muted me-2"></i> N/A</a></li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li><a class="dropdown-item py-2" href="#"
                                                        onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', '', this)"><i
                                                            class="bi bi-circle text-light me-2"></i> VACÍO</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                @endforeach

                                {{-- Gestión --}}
                                <td class="small text-center">{{ $c->aprobado_y_pagado }}</td>
                                <td class="small text-center" style="border-right: 2px solid #e2e8f0">
                                    {{ $c->modificaciones_y_cierre }}</td>

                                {{-- Ejecución Mensual --}}
                                @for ($i = 1; $i <= 12; $i++)
                                    @foreach (["cta{$i}_secop_status", "cta{$i}_sia_status"] as $field)
                                        @php $b = $badgeMap[$c->$field] ?? $badgeMap['']; @endphp
                                        <td class="text-center"
                                            style="{{ $i == 12 && $field == 'cta12_sia_status' ? 'border-right: 2px solid #e2e8f0' : '' }}">
                                            <div class="dropdown">
                                                <div class="badge-pill-saas {{ $b['class'] }} w-100"
                                                    data-bs-toggle="dropdown">
                                                    {{ $c->$field ?: 'V' }}
                                                </div>
                                                <ul class="dropdown-menu shadow-lg border-0" style="font-size: 0.75rem;">
                                                    <li><a class="dropdown-item py-2" href="#"
                                                            onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'OK', this)">OK</a>
                                                    </li>
                                                    <li><a class="dropdown-item py-2" href="#"
                                                            onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'PENDIENTE', this)">PENDIENTE</a>
                                                    </li>
                                                    <li><a class="dropdown-item py-2" href="#"
                                                            onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'N/A', this)">N/A</a>
                                                    </li>
                                                    <li><a class="dropdown-item py-2" href="#"
                                                            onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', '', this)">VACÍO</a>
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
                                    <td class="text-center"
                                        style="{{ $field == 'acta_liq_sia_status' ? 'border-right: 2px solid #e2e8f0' : '' }}">
                                        <div class="dropdown">
                                            <div class="badge-pill-saas {{ $b['class'] }} w-100"
                                                data-bs-toggle="dropdown">
                                                <i class="bi {{ $b['icon'] }}"></i>
                                                {{ $c->$field ?: 'VACÍO' }}
                                            </div>
                                            <ul class="dropdown-menu shadow-lg border-0" style="font-size: 0.75rem;">
                                                <li><a class="dropdown-item py-2" href="#"
                                                        onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'OK', this)">OK/SI</a>
                                                </li>
                                                <li><a class="dropdown-item py-2" href="#"
                                                        onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'PENDIENTE', this)">NO/PEND</a>
                                                </li>
                                                <li><a class="dropdown-item py-2" href="#"
                                                        onclick="updateBadgeStatus({{ $c->id }}, '{{ $field }}', 'N/A', this)">N/A</a>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL GESTIÓN --}}
    <div class="modal fade" id="modalManagement" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="mTitle">Gestión Contractual</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="mainForm" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="mMethod" value="POST">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="small fw-bold">Nº PROCESO</label><input type="text"
                                    name="numero_proceso" id="mProceso" class="form-control"></div>
                            <div class="col-md-4"><label class="small fw-bold">Nº CONTRATO</label><input type="text"
                                    name="numero_contrato" id="mContrato" class="form-control" required></div>
                            <div class="col-md-4"><label class="small fw-bold">TIPO CONTRATISTA</label><input
                                    type="text" name="tipo_contratista" id="mTipoCont" class="form-control"></div>
                            <div class="col-md-6"><label class="small fw-bold">VALOR CONTRATO</label><input
                                    type="number" name="monto_total" id="mValor" class="form-control" required>
                            </div>
                            <div class="col-md-6"><label class="small fw-bold">SUPERVISOR</label>
                                <select name="supervisor_id" id="mSup" class="form-select">
                                    @foreach ($supervisores as $s)
                                        <option value="{{ $s->id }}">{{ $s->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12"><label class="small fw-bold">OBJETO</label>
                                <textarea name="objeto" id="mObjeto" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6"><label class="small fw-bold">NO PLANTA</label><input type="text"
                                    name="no_planta" id="mNoPlanta" class="form-control"></div>
                            <div class="col-md-6"><label class="small fw-bold">CONCEPTO PRECONTRACTUAL</label><input
                                    type="text" name="concepto_precontractual" id="mConcepto" class="form-control">
                            </div>
                            <div class="col-md-4"><label class="small fw-bold">ABOGADO</label><input type="text"
                                    name="abogado_responsable" id="mAbogado" class="form-control"></div>
                            <div class="col-md-4"><label class="small fw-bold">CONTADOR</label><input type="text"
                                    name="contador_responsable" id="mContador" class="form-control"></div>
                            <div class="col-md-4"><label class="small fw-bold">SALDO</label><input type="number"
                                    name="saldo" id="mSaldo" class="form-control"></div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function updateBadgeStatus(id, field, status, element) {
            const badgeMap = {
                'OK': {
                    class: 'badge-ok',
                    icon: 'bi-check-circle-fill'
                },
                'PENDIENTE': {
                    class: 'badge-pend',
                    icon: 'bi-hourglass-split'
                },
                'RECHAZADO': {
                    class: 'badge-crit',
                    icon: 'bi-x-circle-fill'
                },
                'CRÍTICO': {
                    class: 'badge-crit',
                    icon: 'bi-exclamation-triangle-fill'
                },
                'N/A': {
                    class: 'badge-na',
                    icon: 'bi-dash-circle'
                },
                '': {
                    class: 'badge-vacio',
                    icon: 'bi-circle'
                }
            };

            const parentDiv = element.closest('.dropdown').querySelector('.badge-pill-saas');
            const originalHTML = parentDiv.innerHTML;
            const originalClass = parentDiv.className;

            // Feedback visual inmediato (Loading)
            parentDiv.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            try {
                const res = await fetch('{{ route('seguimiento.update-status') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id,
                        field,
                        status
                    })
                });

                if (res.ok) {
                    const b = badgeMap[status] || badgeMap[''];
                    parentDiv.className = `badge-pill-saas ${b.class} w-100`;
                    parentDiv.innerHTML = `<i class="bi ${b.icon}"></i> ${status || 'VACÍO'}`;

                    // Opcional: Notificación silenciosa (Toast) en lugar de alert
                } else {
                    throw new Error('Server error');
                }
            } catch (e) {
                parentDiv.innerHTML = originalHTML;
                parentDiv.className = originalClass;
                alert('Error al actualizar el estado. Intente de nuevo.');
            }
        }

        function applyAdvancedFilters() {
            const params = new URLSearchParams({
                numero_contrato: document.getElementById('filterContrato').value,
                tipo_contratista: document.getElementById('filterTipo').value,
                supervisor_id: document.getElementById('filterSup').value
            });
            window.location.search = params.toString();
        }

        function openEditModal(c) {
            document.getElementById('mTitle').innerText = 'Editar Contrato #' + c.numero_contrato;
            document.getElementById('mMethod').value = 'PUT';
            document.getElementById('mainForm').action = '{{ url('/seguimiento') }}/' + c.id;
            document.getElementById('mProceso').value = c.numero_proceso || '';
            document.getElementById('mContrato').value = c.numero_contrato;
            document.getElementById('mTipoCont').value = c.tipo_contratista || '';
            document.getElementById('mValor').value = c.monto_total || 0;
            document.getElementById('mObjeto').value = c.objeto || '';
            document.getElementById('mSup').value = c.supervisor_id || '';
            document.getElementById('mNoPlanta').value = c.no_planta || '';
            document.getElementById('mConcepto').value = c.concepto_precontractual || '';
            document.getElementById('mAbogado').value = c.abogado_responsable || '';
            document.getElementById('mContador').value = c.contador_responsable || '';
            document.getElementById('mSaldo').value = c.saldo || 0;
            new bootstrap.Modal(document.getElementById('modalManagement')).show();
        }

        function openCreateModal() {
            document.getElementById('mainForm').reset();
            document.getElementById('mMethod').value = 'POST';
            document.getElementById('mainForm').action = '{{ route('seguimiento.store') }}';
            document.getElementById('mTitle').innerText = 'Registrar Nuevo Contrato';
            new bootstrap.Modal(document.getElementById('modalManagement')).show();
        }
    </script>
@endsection

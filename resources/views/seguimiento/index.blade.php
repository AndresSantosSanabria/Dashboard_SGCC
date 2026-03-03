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
                <button class="btn btn-saas-secondary" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
                </button>
                <button class="btn btn-saas-secondary" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                </button>
            </div>
        </header>

        {{-- ANALÍTICA RESUMEN SUPERIOR --}}
        <div class="analytics-summary animate-fadeIn" style="grid-template-columns: repeat(4, 1fr);">
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #16a34a !important;">
                <span class="label text-success">Contratos OK</span>
                <span class="val" id="stat-ok">{{ number_format($stats['ok_contratos']) }}</span>
                <span class="sub">Checklist completo</span>
            </div>
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #d97706 !important;">
                <span class="label text-warning">Con Pendientes</span>
                <span class="val" id="stat-pend">{{ number_format($stats['pend_contratos']) }}</span>
                <span class="sub">Gestión en proceso</span>
            </div>
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #2563eb !important;">
                <span class="label text-primary">Cumplimiento Global</span>
                <div class="val"><span id="stat-avg">{{ number_format($stats['avg_cumplimiento'], 1) }}</span>%</div>
                <div class="progress mt-1" style="height: 6px; background: #e2e8f0;">
                    <div class="progress-bar bg-primary" id="stat-bar" style="width: {{ $stats['avg_cumplimiento'] }}%"></div>
                </div>
            </div>
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #8b5cf6 !important;">
                <span class="label" style="color: #7c3aed;">TOTAL CONTRATOS ACTUALES</span>
                <span class="val" id="stat-val-total">{{ number_format($stats['total']) }}</span>
                <span class="sub">Registros en vista actual</span>
            </div>
        </div>

        {{-- SEGUNDA FILA: ANALÍTICA SECOP --}}
        <div class="analytics-summary animate-fadeIn" style="margin-top: 1rem; margin-bottom: 2rem; grid-template-columns: repeat(4, 1fr);">
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #10b981 !important;">
                <span class="label" style="color: #059669;">Cerrados/Terminados SECOP</span>
                <span class="val" id="stat-sec-cerrado">{{ number_format($stats['sec_cerrado']) }}</span>
                <span class="sub">Contratos con cierre efectivo</span>
            </div>
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #f59e0b !important;">
                <span class="label" style="color: #d97706;">En Ejecución SECOP</span>
                <span class="val" id="stat-sec-ejecucion">{{ number_format($stats['sec_ejecucion']) }}</span>
                <span class="sub">Contratos activos en plataforma</span>
            </div>
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #94a3b8 !important;">
                <span class="label text-muted">Sin Datos SECOP</span>
                <span class="val" id="stat-sec-vacio">{{ number_format($stats['sec_vacio']) }}</span>
                <span class="sub">Pendientes de actualización SECOP</span>
            </div>
            <div class="summary-mini-card shadow-sm border-0" style="border-left: 4px solid #6366f1 !important;">
                <span class="label" style="color: #4f46e5;">ESTADO SECOP TOTAL</span>
                <span class="val"><span id="stat-total-con-seg">{{ number_format($stats['total']) }}</span></span>
                <span class="sub">Consolidado general plataforma</span>
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
                <select id="filterTipo" class="input-saas">
                    <option value="">Todos</option>
                    <option value="Natural">Natural</option>
                    <option value="Juridica">Juridica</option>
                </select>
            </div>
            <div class="form-group-saas">
                <label>Estado SECOP</label>
                <select id="filterSecop" class="input-saas">
                    <option value="">Todos</option>
                    <option value="CERRADO">CERRADO</option>
                    <option value="TERMINADO">TERMINADO</option>
                    <option value="EN EJECUCION">EN EJECUCION</option>
                </select>
            </div>
            <div class="form-group-saas">
                <label>Estado</label>
                <select id="filterEstado" class="input-saas">
                    <option value="">Cualquiera</option>
                    <option value="OK">OK</option>
                    <option value="PENDIENTE">PENDIENTE</option>
                    <option value="EN PROGRESO">EN PROGRESO</option>
                    <option value="N/A">N/A</option>
                    <option value="VACÍO">VACÍO</option>
                </select>
            </div>
            <div class="form-group-saas">
                <label>Cuenta Ejecución</label>
                <select id="filterMes" class="input-saas">
                    <option value="">Cualquiera</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">Cuenta {{ $i }}</option>
                    @endfor
                </select>
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
                            <th class="stk-gest"></th>
                            <th class="stk-risk"></th>
                            <th class="stk-saas stk-proc" style="z-index: 12 !important;"></th>
                            <th class="stk-saas stk-cont-merged" colspan="2" style="z-index: 12 !important; border-right: 2px solid #e2e8f0; text-align: center;">
                                NUMERO DE CONTRATO / CONTRATISTA</th>

                            <th colspan="4" style="border-right: 2px solid #e2e8f0; text-align: center;">INFORMACIÓN BASE
                            </th>
                            <th colspan="13"
                                style="border-right: 2px solid #e2e8f0; text-align: center; background: #FEF9C3; color: #854D0E;">
                                CHECKLIST TÉCNICO</th>
                            <th colspan="3"
                                style="border-right: 2px solid #e2e8f0; text-align: center; background: #F0FDF4; color: #166534;">
                                GESTIÓN</th>
                            <th colspan="36"
                                style="border-right: 2px solid #e2e8f0; text-align: center; background: #F1F5F9;">EJECUCIÓN
                                MENSUAL</th>
                            <th colspan="6"
                                style="border-right: 2px solid #e2e8f0; text-align: center; background: #EEF2FF; color: #1E40AF;">
                                CIERRE CONTRACTUAL</th>
                            <th colspan="6" style="text-align: center; background: #F8FAFC;">RESULTADOS Y RESPONSABLES
                            </th>
                        </tr>
                        <tr>
                            <th class="stk-gest text-center">GESTIÓN</th>
                            <th class="stk-risk text-center">PROGRESO</th>
                            <th class="stk-saas stk-proc text-center">PROCESO</th>
                            <th class="stk-saas stk-num text-center">Nº CONTRATO</th>
                            <th class="stk-saas stk-nom text-center" style="border-right: 2px solid #e2e8f0;">CONTRATISTA</th>

                            <th class="col-md-saas">TIPO CONTRATISTA</th>
                            <th class="col-md-saas">SUPERVISOR</th>
                            <th style="width: 250px; min-width: 250px;">OBJETO</th>
                            <th class="col-md-saas text-end" style="border-right: 2px solid #e2e8f0">VALOR CONTRATO</th>
                            <th class="col-narrow-saas">LINK</th>
                            <th class="col-narrow-saas">PLANTA</th>
                            <th class="col-md-saas">CONCEPTO PRECON.</th>
                            <th class="col-md-saas">CDP</th>

                            {{-- Checklist Técnico --}}
                            <th class="col-narrow-saas">ESTUDIOS PREVIOS</th>
                            <th class="col-narrow-saas">SOPORTES</th>
                            <th class="col-narrow-saas">IDONEIDAD</th>
                            <th class="col-narrow-saas">CONFIDEN.</th>
                            <th class="col-narrow-saas">CLAUSULADO</th>
                            <th class="col-narrow-saas">ACTA INICIO</th>
                            <th class="col-narrow-saas">DELEGACIÓN</th>
                            <th class="col-narrow-saas">ARL</th>
                            <th class="col-narrow-saas" style="border-right: 2px solid #e2e8f0">RP</th>

                            {{-- Gestión --}}
                            <th class="col-narrow-saas">ESTADO SECOP</th>
                            <th class="col-narrow-saas">APROBADO</th>
                            <th class="col-narrow-saas" style="border-right: 2px solid #e2e8f0">CIERRE</th>

                            {{-- Ejecución (1 a 12) --}}
                            @for ($i = 1; $i <= 12; $i++)
                                <th class="col-narrow-saas" title="Cuenta {{ $i }}">REP {{ $i }}</th>
                                <th class="col-narrow-saas" title="Cuenta {{ $i }}">SEC {{ $i }}</th>
                                <th class="col-narrow-saas" title="Cuenta {{ $i }}"
                                    style="{{ $i == 12 ? 'border-right: 2px solid #e2e8f0' : '' }}">SIA {{ $i }}</th>
                            @endfor

                            {{-- Cierre --}}
                            <th class="col-narrow-saas">EVAL. PROV.</th>
                            <th class="col-narrow-saas">ACTA CIERRE</th>
                            <th class="col-narrow-saas">REQ. ACTA LIQ</th>
                            <th class="col-narrow-saas">EN REPOS.</th>
                            <th class="col-narrow-saas">LIQ SECOP</th>
                            <th class="col-narrow-saas" style="border-right: 2px solid #e2e8f0">LIQ SIA</th>

                            {{-- Resultados --}}
                            <th class="col-md-saas">SALDO</th>
                            <th class="col-md-saas">OBS 1 RAZON</th>
                            <th class="col-md-saas">OBS 2 ACCION</th>
                            <th class="col-md-saas">RAZON NO LIQ</th>
                            <th class="col-md-saas">ABOGADO</th>
                            <th class="col-md-saas">CONTADOR</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @include('seguimiento.partials.table')
                    </tbody>
                </table>
            </div>
            <div id="pagination-container" class="d-flex justify-content-center align-items-center p-3 border-top bg-white" style="border-radius: 0 0 12px 12px;">
                {{ $contratos->appends(request()->query())->links('pagination::bootstrap-5') }}
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
                            <!-- Sección: Información Base -->
                            <div class="col-12">
                                <h6 class="fw-bold mb-3" style="color: #1e40af; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">
                                    <i class="bi bi-info-circle me-1"></i> INFORMACIÓN BASE
                                </h6>
                            </div>
                            <div class="col-md-4"><label class="small fw-bold">Nº PROCESO</label><input type="text"
                                    name="numero_proceso" id="mProceso" class="form-control"></div>
                            <div class="col-md-4"><label class="small fw-bold">Nº CONTRATO</label><input type="text"
                                    name="numero_contrato" id="mContrato" class="form-control" required></div>
                            <div class="col-md-4"><label class="small fw-bold">TIPO CONTRATISTA</label>
                                <select name="tipo_contratista" id="mTipoCont" class="form-select">
                                    <option value="">VACÍO</option>
                                    <option value="Natural">Natural</option>
                                    <option value="Juridica">Juridica</option>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="small fw-bold">CONTRATISTA / RAZÓN SOCIAL</label>
                                <input type="text" name="contratista_nombre" id="mContratistaNom" class="form-control" required>
                            </div>
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
                            <div class="col-md-6"><label class="small fw-bold">SALDO</label><input type="number"
                                    name="saldo" id="mSaldo" class="form-control"></div>
                            <div class="col-12"><label class="small fw-bold">OBJETO</label>
                                <textarea name="objeto" id="mObjeto" class="form-control" rows="2"></textarea>
                            </div>

                            <!-- Sección: Checklist Técnico -->
                            <div class="col-12 mt-4">
                                <h6 class="fw-bold mb-3" style="color: #854d0e; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">
                                    <i class="bi bi-check2-square me-1"></i> CHECKLIST TÉCNICO
                                </h6>
                            </div>
                            <div class="col-md-12"><label class="small fw-bold text-warning">LINK SECOP</label><input type="text"
                                    name="link_secop" id="mLink" class="form-control" placeholder="URL del contrato"></div>

                            <!-- Sección: Gestión -->
                            <div class="col-12 mt-4">
                                <h6 class="fw-bold mb-3" style="color: #166534; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">
                                    <i class="bi bi-gear me-1"></i> GESTIÓN Y CIERRE
                                </h6>
                            </div>
                            <div class="col-md-4"><label class="small fw-bold text-success">APROBADO Y PAGADO</label>
                                <select name="aprobado_y_pagado" id="mApagado" class="form-select">
                                    <option value="">VACÍO</option>
                                    <option value="Con supervisor">Con supervisor</option>
                                    <option value="Pagado">Pagado</option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="small fw-bold text-success">MODIFICACIONES Y CIERRE</label>
                                <select name="modificaciones_y_cierre" id="mMcierre" class="form-select">
                                    <option value="">VACÍO</option>
                                    <option value="con supervisor">con supervisor</option>
                                    <option value="Cerrado por supervisor">Cerrado por supervisor</option>
                                    <option value="Secretaria">Secretaria</option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="small fw-bold text-success">ABOGADO RESP.</label><input type="text"
                                    name="abogado_responsable" id="mAbogado" class="form-control"></div>
                            <div class="col-md-4"><label class="small fw-bold">CONTADOR RESP.</label><input type="text"
                                    name="contador_responsable" id="mContador" class="form-control"></div>
                            <div class="col-md-8"><label class="small fw-bold">RAZÓN NO LIQUIDACIÓN</label>
                                <textarea name="razon_no_liquidacion" id="mRazonNoLiq" class="form-control" rows="1"></textarea>
                            </div>
                            <div class="col-md-6"><label class="small fw-bold">OBS 1 RAZÓN</label>
                                <textarea name="observacion_1_razon" id="mObs1" class="form-control" rows="1"></textarea>
                            </div>
                            <div class="col-md-6"><label class="small fw-bold">OBS 2 ACCIÓN</label>
                                <textarea name="observacion_2_accion" id="mObs2" class="form-control" rows="1"></textarea>
                            </div>
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

    {{-- MODAL ESPECÍFICO PARA LINK SECOP --}}
    <div class="modal fade" id="modalLink" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold">Actualizar Link SECOP</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">URL del contrato en SECOP</label>
                        <input type="url" id="linkInput" class="form-control" placeholder="https://www.secop.gov.co/...">
                        <input type="hidden" id="linkContratoId">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4 fw-bold" onclick="saveLink()">Guardar Link</button>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        async function updateBadgeStatus(event, id, field, status, element) {
            if (event) event.preventDefault();
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

                    // Recargar stats y estado global sin perder posición
                    applyAdvancedFilters(); 
                } else {
                    throw new Error('Server error');
                }
            } catch (e) {
                parentDiv.innerHTML = originalHTML;
                parentDiv.className = originalClass;
                alert('Error al actualizar el estado. Intente de nuevo.');
            }
        }

        let filterTimeout;
        function debouncedFilter() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                applyAdvancedFilters();
            }, 300);
        }

        document.getElementById('filterContrato').addEventListener('input', debouncedFilter);
        document.getElementById('filterTipo').addEventListener('change', () => applyAdvancedFilters());
        document.getElementById('filterEstado').addEventListener('change', () => applyAdvancedFilters());
        document.getElementById('filterSecop').addEventListener('change', () => applyAdvancedFilters());
        document.getElementById('filterMes').addEventListener('change', () => applyAdvancedFilters());
        document.getElementById('filterSup').addEventListener('change', () => applyAdvancedFilters());

        document.addEventListener('click', function(e) {
            let pageLink = e.target.closest('#pagination-container a');
            if (pageLink) {
                e.preventDefault();
                applyAdvancedFilters(pageLink.href);
            }
        });

        async function applyAdvancedFilters(url = null) {
            const btn = document.querySelector('.btn-saas-primary');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            let fetchUrl = typeof url === 'string' ? url : null;
            if (!fetchUrl) {
                const params = new URLSearchParams({
                    numero_contrato: document.getElementById('filterContrato').value,
                    tipo_contratista: document.getElementById('filterTipo').value,
                    supervisor_id: document.getElementById('filterSup').value,
                    estado_filtro: document.getElementById('filterEstado') ? document.getElementById('filterEstado').value : '',
                    secop_filtro: document.getElementById('filterSecop').value,
                    mes_filtro: document.getElementById('filterMes').value
                });
                fetchUrl = `{{ route('seguimiento.index') }}?${params.toString()}`;
            }

            // Preservar Scroll
            const scrollPos = window.scrollY;
            const tableScroll = document.querySelector('.table-scroll-container')?.scrollLeft;
            
            // Estabilizar altura para prevenir saltos
            const tableCard = document.querySelector('.table-card-saas');
            if (tableCard) {
                tableCard.style.minHeight = tableCard.offsetHeight + 'px';
            }

            try {
                const res = await fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();

                // Actualizar Tabla
                document.getElementById('tableBody').innerHTML = data.table;

                // Actualizar Paginación
                if (document.getElementById('pagination-container')) {
                    document.getElementById('pagination-container').innerHTML = data.pagination;
                }

                // Restaurar Scroll
                window.scrollTo(0, scrollPos);
                if (tableScroll !== undefined) {
                    const tableContainer = document.querySelector('.table-scroll-container');
                    if(tableContainer) tableContainer.scrollLeft = tableScroll;
                }

                // Actualizar Stats
                document.getElementById('stat-ok').innerText = parseInt(data.stats.ok_contratos).toLocaleString();
                document.getElementById('stat-pend').innerText = parseInt(data.stats.pend_contratos).toLocaleString();
                document.getElementById('stat-avg').innerText = parseFloat(data.stats.avg_cumplimiento).toFixed(1);
                document.getElementById('stat-bar').style.width = data.stats.avg_cumplimiento + '%';
                if(document.getElementById('stat-val-total')){
                    document.getElementById('stat-val-total').innerText = parseInt(data.stats.total).toLocaleString();
                }

                // Actualizar Stats SECOP
                document.getElementById('stat-sec-cerrado').innerText = parseInt(data.stats.sec_cerrado).toLocaleString();
                document.getElementById('stat-sec-ejecucion').innerText = parseInt(data.stats.sec_ejecucion).toLocaleString();
                document.getElementById('stat-sec-vacio').innerText = parseInt(data.stats.sec_vacio).toLocaleString();
                if(document.getElementById('stat-total-con-seg')){
                    document.getElementById('stat-total-con-seg').innerText = parseInt(data.stats.total).toLocaleString();
                }

            } catch (e) {
                console.error(e);
                // Opcional: mostrar error sutil
            } finally {
                // Quitar restricción de altura
                const tableCard = document.querySelector('.table-card-saas');
                if (tableCard) tableCard.style.minHeight = '';
                
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function openEditModal(c) {
            document.getElementById('mTitle').innerText = 'Editar Contrato #' + c.numero_contrato;
            document.getElementById('mMethod').value = 'PUT';
            document.getElementById('mainForm').action = '{{ url('/seguimiento') }}/' + c.id;
            document.getElementById('mProceso').value = c.numero_proceso || '';
            document.getElementById('mContrato').value = c.numero_contrato;
            document.getElementById('mTipoCont').value = c.tipo_contratista || '';
            document.getElementById('mContratistaNom').value = c.contratista ? c.contratista.razon_social : '';
            document.getElementById('mValor').value = c.monto_total || 0;
            document.getElementById('mObjeto').value = c.objeto || '';
            document.getElementById('mSup').value = c.supervisor_id || '';
            document.getElementById('mAbogado').value = c.abogado_responsable || '';
            document.getElementById('mContador').value = c.contador_responsable || '';
            document.getElementById('mSaldo').value = c.saldo || 0;
            document.getElementById('mObs1').value = c.observacion_1_razon || '';
            document.getElementById('mObs2').value = c.observacion_2_accion || '';
            document.getElementById('mRazonNoLiq').value = c.razon_no_liquidacion || '';
            document.getElementById('mApagado').value = c.aprobado_y_pagado || '';
            document.getElementById('mMcierre').value = c.modificaciones_y_cierre || '';
            document.getElementById('mLink').value = c.link_secop || '';
            new bootstrap.Modal(document.getElementById('modalManagement')).show();
        }

        // Guardar/Actualizar vía AJAX
        document.getElementById('mainForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
            btn.disabled = true;

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    const data = await res.json();
                    const modalEl = document.getElementById('modalManagement');
                    bootstrap.Modal.getInstance(modalEl).hide();
                    
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message || 'Operación exitosa', 'success');
                    } else {
                        alert(data.message || 'Operación exitosa');
                    }
                    
                    applyAdvancedFilters(); // Recarga la tabla sin recargar la página
                } else {
                    const errorData = await res.json();
                    alert('Error: ' + (errorData.message || 'No se pudo procesar la solicitud'));
                }
            } catch (err) {
                console.error(err);
                alert('Ocurrió un error inesperado al conectar con el servidor');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        function openCreateModal() {
            document.getElementById('mainForm').reset();
            document.getElementById('mMethod').value = 'POST';
            document.getElementById('mainForm').action = '{{ route('seguimiento.store') }}';
            document.getElementById('mTitle').innerText = 'Registrar Nuevo Contrato';
            new bootstrap.Modal(document.getElementById('modalManagement')).show();
        }
        function editDirectField(id, field, currentVal, element) {
            const newVal = prompt('Ingrese el nuevo valor:', currentVal);
            if (newVal !== null) {
                updateBadgeStatus(null, id, field, newVal, element);
            }
        }

        function exportToExcel() {
            const params = new URLSearchParams({
                numero_contrato: document.getElementById('filterContrato').value,
                tipo_contratista: document.getElementById('filterTipo').value,
                estado_filtro: document.getElementById('filterEstado').value,
                mes_filtro: document.getElementById('filterMes').value,
                secop_filtro: document.getElementById('filterSecop').value,
                supervisor_id: document.getElementById('filterSup').value
            });
            window.location.href = "{{ route('seguimiento.export') }}?" + params.toString();
        }

        function openLinkModal(id, currentLink) {
            document.getElementById('linkContratoId').value = id;
            document.getElementById('linkInput').value = currentLink || '';
            new bootstrap.Modal(document.getElementById('modalLink')).show();
        }

        async function saveLink() {
            const id = document.getElementById('linkContratoId').value;
            const link = document.getElementById('linkInput').value;
            
            try {
                const res = await fetch('{{ route('seguimiento.update-status') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id,
                        field: 'link_secop',
                        status: link
                    })
                });

                if (res.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('modalLink')).hide();
                    if (window.showSnackbar) {
                        window.showSnackbar('Link actualizado correctamente', 'success');
                    } else {
                        alert('Link actualizado correctamente');
                    }
                    applyAdvancedFilters();
                } else {
                    alert('Error al actualizar el link');
                }
            } catch (e) {
                console.error(e);
                alert('Error de conexión');
            }
        }

        async function deleteContrato(id, numero) {
            if (!confirm(`¿Está seguro de eliminar de forma GLOBAL el contrato #${numero}? Esta acción eliminará también sus cuentas de cobro, historial y documentos. No se puede deshacer.`)) {
                return;
            }

            try {
                const res = await fetch(`{{ url('/seguimiento') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (res.ok) {
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message, 'success');
                    } else {
                        alert(data.message);
                    }
                    applyAdvancedFilters();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo eliminar el contrato'));
                }
            } catch (e) {
                console.error(e);
                alert('Error de conexión al intentar eliminar');
            }
        }
    </script>
@endsection

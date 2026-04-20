@extends('layouts.app')

@section('title', 'Dashboard Ejecutivo SECOP — SGCC')

@push('styles')
    @vite(['resources/views/seguimiento/seguimiento.css'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .after-none::after { display: none !important; }
        .premium-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; display: block; text-transform: uppercase; }
        .premium-input, .premium-select { width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid var(--border); background: #F8FAFC; transition: all 0.2s; outline: none; }
        .premium-input:focus, .premium-select:focus { border-color: var(--accent); background: white; box-shadow: 0 0 0 4px var(--accent-soft); }
        .premium-modal .modal-content { border-radius: 24px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .premium-modal .modal-header { background: var(--primary); color: white; padding: 1.5rem 2rem; border: none; }
        .btn-premium { border-radius: 12px; font-weight: 700; transition: all 0.3s; padding: 0.8rem 1.5rem; }
        .btn-premium-primary { background: var(--accent); color: white; border: none; }
        .btn-premium-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4); }
        .btn-premium-secondary { background: #F1F5F9; color: var(--text-main); border: none; }
        .stk-saas { position: sticky; background: white !important; z-index: 5; }
        .table-card-saas { position: relative; }
    </style>
@endpush

@section('page-content')
    <div class="dashboard-container animate-fadeIn">

        {{-- HEADER ESTRATÉGICO --}}
        <header class="db-header">
            <div>
                <h1>Gestión Estratégica SECOP</h1>
                <p>Monitoreo en tiempo real de contratación y cumplimiento normativo.</p>
            </div>
            <div class="d-flex gap-3">
                <button class="btn-saas-secondary" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel-fill text-success me-2"></i> Reporte Excel
                </button>
                <button class="btn-saas-primary" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Sincronizar
                </button>
            </div>
        </header>

        {{-- ANALÍTICA RESUMEN SUPERIOR --}}
        <div class="analytics-summary">
            <div class="summary-mini-card">
                <span class="label text-success">Contratos en Norma</span>
                <span class="val" id="stat-ok">{{ number_format($stats['ok_contratos']) }}</span>
                <span class="sub"><i class="bi bi-check-all"></i> Checklist Validado</span>
            </div>
            <div class="summary-mini-card">
                <span class="label text-warning">Acciones Pendientes</span>
                <span class="val" id="stat-pend">{{ number_format($stats['pend_contratos']) }}</span>
                <span class="sub"><i class="bi bi-activity"></i> Gestión en curso</span>
            </div>
            <div class="summary-mini-card">
                <span class="label text-primary">Cumplimiento Global</span>
                <div class="val"><span id="stat-avg">{{ number_format($stats['avg_cumplimiento'], 1) }}</span>%</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-primary" id="stat-bar" style="width: {{ $stats['avg_cumplimiento'] }}%"></div>
                </div>
            </div>
            <div class="summary-mini-card">
                <span class="label text-indigo">Universo de Registros</span>
                <span class="val" id="stat-val-total">{{ number_format($stats['total']) }}</span>
                <span class="sub"><i class="bi bi-database-fill"></i> Base de datos activa</span>
            </div>
        </div>

        {{-- SEGUNDA FILA: ANALÍTICA SECOP --}}
        <div class="analytics-summary mb-4">
            <div class="summary-mini-card">
                <span class="label text-emerald">Cerrados SECOP</span>
                <span class="val" id="stat-sec-cerrado">{{ number_format($stats['sec_cerrado']) }}</span>
                <span class="sub">Efectividad de cierre</span>
            </div>
            <div class="summary-mini-card">
                <span class="label text-amber">En Ejecución</span>
                <span class="val" id="stat-sec-ejecucion">{{ number_format($stats['sec_ejecucion']) }}</span>
                <span class="sub">Obras y servicios activos</span>
            </div>
            <div class="summary-mini-card">
                <span class="label text-slate">Sin Datos SECOP</span>
                <span class="val" id="stat-sec-vacio">{{ number_format($stats['sec_vacio']) }}</span>
                <span class="sub">Pendientes de plataforma</span>
            </div>
            <div class="summary-mini-card bg-primary text-white after-none">
                <span class="label text-white opacity-75">Estado Consolidado</span>
                <span class="val text-white" id="stat-total-con-seg">{{ number_format($stats['total']) }}</span>
                <span class="sub text-white opacity-75">Total plataforma</span>
            </div>
        </div>

        {{-- FILTROS PREMIUM --}}
        <div class="filter-card-saas animate-fadeIn">
            <div class="form-group-saas">
                <label>Búsqueda de Contrato</label>
                <input type="text" id="filterContrato" class="input-saas" placeholder="Filtro por Nº...">
            </div>
            <div class="form-group-saas">
                <label>Tipo Contratista</label>
                <select id="filterTipo" class="input-saas">
                    <option value="">Todos los tipos</option>
                    <option value="Natural">Natural</option>
                    <option value="Juridica">Juridica</option>
                </select>
            </div>
            <div class="form-group-saas">
                <label>Estatus SECOP</label>
                <select id="filterSecop" class="input-saas">
                    <option value="">Cualquier estado</option>
                    <option value="CERRADO">CERRADO</option>
                    <option value="TERMINADO">TERMINADO</option>
                    <option value="EN EJECUCION">EN EJECUCION</option>
                </select>
            </div>
            <div class="form-group-saas">
                <label>ESTADO</label>
                <div class="dropdown custom-multilevel-dropdown">
                    <button class="dropdown-toggle text-start w-100 d-flex justify-content-between align-items-center"
                        type="button" id="dropdownEstado" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="selectedEstadoLabel" class="text-truncate" style="max-width: 150px;">
                            Todos los estados
                        </span>
                        <i class="bi bi-chevron-down ms-2 opacity-50"></i>
                    </button>
                    <input type="hidden" id="filterEstado" value="">
                    <ul class="dropdown-menu shadow-lg" aria-labelledby="dropdownEstado">
                        <div class="dropdown-header-custom">
                            <i class="bi bi-layers"></i> Todos los estados
                        </div>
                        <div class="accordion-block">
                             <a class="dropdown-item filter-estado-item filter-estado-item-main active" href="#" data-value="">
                                <i class="bi bi-circle-fill me-2 small opacity-50"></i> Todos los estados
                             </a>
                        </div>
                        
                        @foreach ($bloques as $bloque)
                            @php $estadosDelBloque = $todosLosEstados[$bloque->codigo] ?? collect(); @endphp
                            @if ($estadosDelBloque->isNotEmpty())
                                <div class="accordion-block">
                                    <div class="accordion-block-header">
                                        <span><i class="bi bi-folder2 me-2 text-primary"></i>{{ $bloque->nombre }}</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </div>
                                    <div class="accordion-block-content">
                                        @foreach ($estadosDelBloque as $est)
                                            <a class="dropdown-item filter-estado-item" href="#" data-value="{{ $est->nombre }}">
                                                {{ $est->nombre }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="form-group-saas">
                <label>Supervisor Asignado</label>
                <select class="input-saas" id="filterSup">
                    <option value="">Todos los supervisores</option>
                    @foreach ($supervisores as $s)
                        <option value="{{ $s->id }}">{{ $s->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-saas-primary px-4 py-2" onclick="applyAdvancedFilters()" style="height:42px">
                <i class="bi bi-search"></i>
            </button>
            <button class="btn-saas-secondary" onclick="window.location.href='{{ route('seguimiento.index') }}'" style="height:42px">
                <i class="bi bi-x-lg"></i>
            </button>
            <input type="hidden" id="sortOrder" value="{{ request('sort_order', 'asc') }}">
        </div>

        {{-- TABLA MAESTRA --}}
        <div class="table-card-saas animate-fadeIn" style="animation-delay: 0.1s;">
            <div class="table-scroll-container">
                <table class="saas-table" id="masterTable">
                    <thead>
                        <tr>
                            <th class="stk-saas stk-gest"></th>
                            <th class="stk-saas stk-score"></th>
                            <th class="stk-saas stk-proc">PROCESO</th>
                            <th class="stk-saas stk-num text-center">Nº CONTRATO</th>
                            <th class="stk-saas stk-nom">CONTRATISTA / RAZÓN SOCIAL</th>
                            <th colspan="4" class="text-center" style="background:#F8FAFC">INFO BASE</th>
                            <th colspan="13" class="text-center" style="background: #FFFBEB; color: #92400E;">CHECKLIST TÉCNICO</th>
                            <th colspan="3" class="text-center" style="background: #ECFDF5; color: #065F46;">GESTIÓN</th>
                            <th colspan="36" class="text-center" style="background: #F1F5F9;">EJECUCIÓN MENSUAL</th>
                            <th colspan="6" class="text-center" style="background: #EEF2FF; color: #3730A3;">CIERRE CONTRACTUAL</th>
                            <th colspan="6" class="text-center" style="background: #F8FAFC;">EQUIPO RESPONSABLE</th>
                        </tr>
                        <tr>
                            <th class="stk-saas stk-gest text-center">GESTIÓN</th>
                            <th class="stk-saas stk-score text-center">SCORE</th>
                            <th class="stk-saas stk-proc text-center">TIPO</th>
                            <th class="stk-saas stk-num">
                                <div class="d-flex align-items-center gap-2 cursor-pointer" onclick="toggleSort()">
                                    IDENTIFICADOR <i class="bi bi-sort-alpha-{{ request('sort_order', 'desc') === 'desc' ? 'up' : 'down' }}" id="sortIcon"></i>
                                </div>
                            </th>
                            <th class="stk-saas stk-nom">NOMBRE COMPLETO</th>

                            <th class="col-md-saas text-center">TIPO ENTIDAD</th>
                            <th class="col-md-saas text-center">SUPERVISOR</th>
                            <th style="width: 250px; min-width: 250px;">OBJETO CONTRACTUAL</th>
                            <th class="col-md-saas text-end" style="border-right: 2px solid #E2E8F0">VALOR TOTAL</th>
                            
                            <th class="col-narrow-saas text-center">SECOP</th>
                            <th class="col-narrow-saas text-center">PLANTA</th>
                            <th class="col-md-saas text-center">CONCEPTO</th>
                            <th class="col-md-saas text-center">F. CDP</th>

                            @foreach(['E. PREVIOS','SOPORTES','IDONEIDAD','CONFID.','CLAUS.','ACTA I.','DELEG.','ARL','RP'] as $h)
                                <th class="col-narrow-saas text-center" style="{{ $h == 'RP' ? 'border-right: 2px solid #E2E8F0' : '' }}">{{ $h }}</th>
                            @endforeach

                            <th class="col-narrow-saas text-center">STATUS S.</th>
                            <th class="col-narrow-saas text-center">PAGADO</th>
                            <th class="col-narrow-saas text-center" style="border-right: 2px solid #E2E8F0">CIERRE</th>

                            @for ($i = 1; $i <= 12; $i++)
                                <th class="col-narrow-saas text-center text-muted">R{{ $i }}</th>
                                <th class="col-narrow-saas text-center text-muted">S{{ $i }}</th>
                                <th class="col-narrow-saas text-center text-muted" style="{{ $i == 12 ? 'border-right: 2px solid #E2E8F0' : '' }}">I{{ $i }}</th>
                            @endfor

                            <th class="col-narrow-saas text-center">EVAL.</th>
                            <th class="col-narrow-saas text-center">ACTA C.</th>
                            <th class="col-narrow-saas text-center">REQ. LIQ.</th>
                            <th class="col-narrow-saas text-center">REPOS.</th>
                            <th class="col-narrow-saas text-center">LIQ. S.</th>
                            <th class="col-narrow-saas text-center" style="border-right: 2px solid #E2E8F0">LIQ. I.</th>

                            <th class="col-md-saas text-end">SALDO RT.</th>
                            <th class="col-md-saas">OBS. RAZÓN</th>
                            <th class="col-md-saas">OBS. ACCIÓN</th>
                            <th class="col-md-saas">NO LIQ.</th>
                            <th class="col-md-saas">ABOGADO</th>
                            <th class="col-md-saas">CONTADOR</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @include('seguimiento.partials.table')
                    </tbody>
                </table>
            </div>
            <div id="pagination-container" class="d-flex justify-content-center align-items-center p-4 border-top">
                {{ $contratos->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <!-- MODAL GESTIÓN -->
    <div class="modal fade premium-modal" id="modalManagement" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white p-2 rounded-3 text-primary"><i class="bi bi-pencil-square fs-4"></i></div>
                        <div>
                            <h5 class="modal-title fw-bold" id="mTitle">Ficha de Gestión Contractual</h5>
                            <p class="mb-0 extra-small opacity-75">Actualice los parámetros de ejecución y trazabilidad.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-5 bg-white">
                    <form id="mainForm" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="mMethod" value="POST">
                        <div class="row g-4">
                            <div class="col-12"><h6 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle-fill me-2"></i>Información Estratégica</h6><hr class="mt-2 mb-4 opacity-50"></div>
                            <div class="col-md-4">
                                <label class="premium-label">Número de Contrato</label>
                                <input type="text" name="numero_contrato" id="mContrato" class="premium-input fw-bold" required>
                                <input type="hidden" id="mProceso" name="numero_proceso">
                            </div>
                            <div class="col-md-4">
                                <label class="premium-label">Tipo de Contratista</label>
                                <select name="tipo_contratista" id="mTipoCont" class="premium-select">
                                    <option value="">No definido</option>
                                    <option value="Natural">Persona Natural</option>
                                    <option value="Juridica">Persona Jurídica</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="premium-label">Valor Total</label>
                                <input type="number" name="monto_total" id="mValor" class="premium-input" required>
                            </div>
                            <div class="col-md-6">
                                <label class="premium-label">Contratista / Razón Social</label>
                                <input type="text" name="contratista_nombre" id="mContratistaNom" class="premium-input" required>
                            </div>
                            <div class="col-md-6">
                                <label class="premium-label">Supervisor Asignado</label>
                                <select name="supervisor_id" id="mSup" class="premium-select">
                                    @foreach ($supervisores as $s)
                                        <option value="{{ $s->id }}">{{ $s->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="premium-label">Objeto Contractual</label>
                                <textarea name="objeto" id="mObjeto" class="premium-input" rows="3"></textarea>
                            </div>

                            <div class="col-12"><h6 class="fw-bold mb-0 text-success mt-4"><i class="bi bi-gear-fill me-2"></i>Gestión Adicional</h6><hr class="mt-2 mb-4 opacity-50"></div>
                            <div class="col-md-4">
                                <label class="premium-label">Estado Pago</label>
                                <select name="aprobado_y_pagado" id="mApagado" class="premium-select">
                                    <option value="">VACÍO</option>
                                    <option value="Con supervisor">Con supervisor</option>
                                    <option value="Pagado">Pagado</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="premium-label">Modificación / Cierre</label>
                                <select name="modificaciones_y_cierre" id="mMcierre" class="premium-select">
                                    <option value="">VACÍO</option>
                                    <option value="con supervisor">con supervisor</option>
                                    <option value="Cerrado por supervisor">Cerrado por supervisor</option>
                                    <option value="Secretaria">Secretaria</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="premium-label">Saldo</label>
                                <input type="number" name="saldo" id="mSaldo" class="premium-input">
                            </div>
                            <div class="col-md-6">
                                <label class="premium-label">Abogado Resp.</label>
                                <input type="text" name="abogado_responsable" id="mAbogado" class="premium-input">
                            </div>
                            <div class="col-md-6">
                                <label class="premium-label">Contador Resp.</label>
                                <input type="text" name="contador_responsable" id="mContador" class="premium-input">
                            </div>
                            <div class="col-md-12">
                                <label class="premium-label">Link SECOP</label>
                                <input type="text" name="link_secop" id="mLink" class="premium-input" placeholder="https://...">
                            </div>
                            <div class="col-md-4">
                                <label class="premium-label">OBS 1 Razón</label>
                                <textarea name="observacion_1_razon" id="mObs1" class="premium-input" rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="premium-label">OBS 2 Acción</label>
                                <textarea name="observacion_2_accion" id="mObs2" class="premium-input" rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="premium-label">Razón No Liq.</label>
                                <textarea name="razon_no_liquidacion" id="mRazonNoLiq" class="premium-input" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="mt-5 pt-4 border-top d-flex justify-content-end gap-3">
                            <button type="button" class="btn-premium btn-premium-secondary px-5" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn-premium btn-premium-primary px-5 py-3">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- BANNER DE GUARDADO EN LOTE -->
    <div id="batchBanner" class="batch-save-banner">
        <div class="d-flex align-items-center gap-3">
            <div class="banner-dot-pulse"></div>
            <div>
                <h6 class="mb-0 fw-800"><span id="batchCount">0</span> cambios pendientes</h6>
                <p class="mb-0 extra-small opacity-75">En <span id="batchContracts">0</span> contratos diferentes</p>
            </div>
        </div>
        <div class="d-flex gap-3">
            <button class="btn btn-outline-light border-0 btn-sm px-3" onclick="discardChanges()">Descartar</button>
            <button class="btn btn-primary btn-sm px-4 rounded-pill fw-bold" onclick="confirmBatchSave()">Guardar Todo</button>
        </div>
    </div>

    <!-- MODAL CONFIRM BATCH -->
    <div class="modal fade premium-modal" id="modalConfirmBatch" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title fw-bold">Confirmar Guardado Masivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <p class="text-muted small mb-4">Se aplicarán los siguientes cambios en la base de datos:</p>
                    <div class="table-responsive border rounded-3" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light sticky-top">
                                <tr class="extra-small fw-bold">
                                    <th class="p-3">CONTRATO</th>
                                    <th class="p-3">CAMPO</th>
                                    <th class="p-3">ANTERIOR</th>
                                    <th class="p-3 text-primary">NUEVO</th>
                                </tr>
                            </thead>
                            <tbody id="confirmBatchTableBody" class="small"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top p-4">
                    <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Seguir editando</button>
                    <button type="button" class="btn-premium btn-premium-primary d-flex align-items-center justify-content-center" onclick="executeBatchSave()" id="btnSaveBatchFinal">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="spinnerSaveBatch"></span>
                        <span id="textSaveBatch">Confirmar y Aplicar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL ESPECÍFICO PARA LINK SECOP -->
    <div class="modal fade premium-modal" id="modalLink" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="modal-header bg-primary">
                    <h6 class="modal-title fw-bold">Actualizar Link SECOP</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="premium-label mb-1">URL del contrato en SECOP</label>
                        <input type="url" id="linkInput" class="premium-input" placeholder="https://www.secop.gov.co/...">
                        <input type="hidden" id="linkContratoId">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn-premium btn-premium-secondary py-2 px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-premium btn-premium-primary py-2 px-4 shadow-sm d-flex align-items-center justify-content-center" onclick="saveLink()" id="btnSaveLink">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="spinnerSaveLink"></span>
                        <span id="textSaveLink">Guardar Link</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Manejador para el dropdown de estados (Acordeón)
        document.addEventListener('click', function(e) {
            // 1. Manejar Clic en Cabecera de Acordeón
            const accordionHeader = e.target.closest('.accordion-block-header');
            if (accordionHeader) {
                e.stopPropagation(); // Crucial: Evita que el dropdown se cierre
                const block = accordionHeader.closest('.accordion-block');
                const isOpen = block.classList.contains('open');
                
                // Opcional: Cerrar otros bloques para que solo uno esté abierto
                document.querySelectorAll('.accordion-block').forEach(b => b.classList.remove('open'));
                
                if (!isOpen) block.classList.add('open');
                return;
            }

            // 2. Manejar Clic en Item de Estado (Selección)
            const item = e.target.closest('.filter-estado-item');
            if (item) {
                e.preventDefault();
                const val = item.dataset.value;
                const label = item.textContent.trim();
                
                document.getElementById('filterEstado').value = val;
                document.getElementById('selectedEstadoLabel').innerText = label || 'Todos los estados';
                
                // Actualizar clases active
                document.querySelectorAll('.filter-estado-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');
                
                // Cerrar dropdown manualmente
                const dropdownToggle = document.getElementById('dropdownEstado');
                const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle) || new bootstrap.Dropdown(dropdownToggle);
                if (bsDropdown) bsDropdown.hide();
                
                applyAdvancedFilters();
            }
        });

        let changesQueue = {};

        function updateBadgeStatus(event, id, field, status, element) {
            if (event) event.preventDefault();
            const badgeMap = {
                'OK': { class: 'badge-ok', icon: 'bi-check-circle-fill' },
                'PENDIENTE': { class: 'badge-pend', icon: 'bi-hourglass-split' },
                'RECHAZADO': { class: 'badge-crit', icon: 'bi-x-circle-fill' },
                'CRÍTICO': { class: 'badge-crit', icon: 'bi-exclamation-triangle-fill' },
                'N/A': { class: 'badge-na', icon: 'bi-dash-circle' },
                '': { class: 'badge-vacio', icon: 'bi-circle' }
            };

            const targetBadge = element.closest('.dropdown').querySelector('.badge-pill-saas');
            const originalVal = targetBadge.dataset.originalVal || '';
            const contratoNo = targetBadge.dataset.contrato || 'N/A';
            const queueKey = `${id}-${field}`;

            if (status === originalVal) {
                delete changesQueue[queueKey];
            } else {
                changesQueue[queueKey] = { id, field, newValue: status, oldValue: originalVal, contratoNo };
            }

            const b = badgeMap[status] || badgeMap[''];
            targetBadge.className = `badge-pill-saas ${b.class} w-100 ${status !== originalVal ? 'field-modified' : ''}`;
            const iconHtml = b.icon ? `<i class="bi ${b.icon}"></i> ` : '';
            targetBadge.innerHTML = `${iconHtml}${status || 'VACÍO'}`;

            const row = targetBadge.closest('tr');
            if (Array.from(row.querySelectorAll('.badge-pill-saas')).some(b => b.classList.contains('field-modified'))) {
                row.classList.add('row-has-changes');
            } else {
                row.classList.remove('row-has-changes');
            }

            renderBatchBanner();
            const dropdownToggle = element.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]');
            if (dropdownToggle) bootstrap.Dropdown.getInstance(dropdownToggle)?.hide();
        }

        function renderBatchBanner() {
            const banner = document.getElementById('batchBanner');
            const keys = Object.keys(changesQueue);
            if (keys.length === 0) {
                banner.classList.remove('show');
                return;
            }
            document.getElementById('batchCount').innerText = keys.length;
            document.getElementById('batchContracts').innerText = new Set(Object.values(changesQueue).map(c => c.id)).size;
            banner.classList.add('show');
        }

        function discardChanges() {
            Swal.fire({
                title: '¿Descartar cambios?',
                text: "Se perderán todas las modificaciones en cola.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0F172A',
                confirmButtonText: 'Sí, descartar'
            }).then((result) => {
                if (result.isConfirmed) {
                    changesQueue = {};
                    applyAdvancedFilters();
                    renderBatchBanner();
                }
            });
        }

        function renderBatchTable() {
            const tableBody = document.getElementById('confirmBatchTableBody');
            tableBody.innerHTML = Object.values(changesQueue).map(c => `
                <tr class="align-middle">
                    <td class="p-3 fw-bold">${c.contratoNo}</td>
                    <td class="p-3 text-muted">${c.field.replace('_status', '').toUpperCase()}</td>
                    <td class="p-3"><span class="badge bg-light text-dark border">${c.oldValue || 'VACÍO'}</span></td>
                    <td class="p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="badge bg-primary px-3">${c.newValue || 'VACÍO'}</span>
                            <button class="btn btn-link text-danger p-0 border-0 ms-3" onclick="discardSingleChange('${c.id}', '${c.field}')" title="Descartar este cambio">
                                <i class="bi bi-x-circle-fill fs-5"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function confirmBatchSave() {
            renderBatchTable();
            const modalEl = document.getElementById('modalConfirmBatch');
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.show();
        }

        function discardSingleChange(id, field) {
            const queueKey = `${id}-${field}`;
            const change = changesQueue[queueKey];
            if (!change) return;

            // 1. Revertir UI en la tabla principal
            const row = Array.from(document.querySelectorAll('.contract-row')).find(tr => {
                const idCell = tr.querySelector('.stk-num');
                return idCell && idCell.innerText.trim() === change.contratoNo;
            });

            if (row) {
                const triggerItem = row.querySelector(`[onclick*="'${field}'"]`);
                const badge = triggerItem ? triggerItem.closest('.dropdown').querySelector('.badge-pill-saas') : null;
                
                if (badge) {
                    const badgeMap = {
                        'OK': { class: 'badge-ok', icon: 'bi-check-circle-fill' },
                        'PENDIENTE': { class: 'badge-pend', icon: 'bi-hourglass-split' },
                        'RECHAZADO': { class: 'badge-crit', icon: 'bi-x-circle-fill' },
                        'CRÍTICO': { class: 'badge-crit', icon: 'bi-exclamation-triangle-fill' },
                        'N/A': { class: 'badge-na', icon: 'bi-dash-circle' },
                        '': { class: 'badge-vacio', icon: 'bi-circle' }
                    };
                    const originalVal = change.oldValue || '';
                    const b = badgeMap[originalVal] || badgeMap[''];
                    
                    badge.className = `badge-pill-saas ${b.class} w-100`;
                    const iconHtml = b.icon ? `<i class="bi ${b.icon}"></i> ` : '';
                    badge.innerHTML = `${iconHtml}${originalVal || 'VACÍO'}`;
                    
                    // Quitar clase de modificado si no hay más cambios en la fila
                    setTimeout(() => {
                        const hasOtherChangesInRow = Array.from(row.querySelectorAll('.badge-pill-saas')).some(b => b.classList.contains('field-modified'));
                        if (!hasOtherChangesInRow) row.classList.remove('row-has-changes');
                    }, 10);
                }
            }

            // 2. Eliminar de la cola y actualizar modales/banners
            delete changesQueue[queueKey];
            renderBatchBanner();
            
            if (Object.keys(changesQueue).length === 0) {
                bootstrap.Modal.getInstance(document.getElementById('modalConfirmBatch')).hide();
            } else {
                renderBatchTable(); // Solo actualiza el contenido, no reabre el modal
            }
        }

        async function executeBatchSave() {
            const btn = document.getElementById('btnSaveBatchFinal');
            const spinner = document.getElementById('spinnerSaveBatch');
            const btnText = document.getElementById('textSaveBatch');
            
            btn.disabled = true;
            spinner.classList.remove('d-none');
            btnText.innerText = 'Procesando...';

            try {
                const res = await window.apiFetch('{{ route('seguimiento.batch-update') }}', {
                    method: 'POST',
                    body: JSON.stringify({ changes: Object.values(changesQueue) })
                });
                
                if (res.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('modalConfirmBatch')).hide();
                    changesQueue = {};
                    renderBatchBanner();
                    applyAdvancedFilters();
                    window.showSnackbar('Cambios aplicados exitosamente', 'success');
                } else {
                    const data = await res.json();
                    window.showSnackbar('Error: ' + (data.message || 'No se pudo completar la operación'), 'error');
                }
            } catch (e) {
                window.showSnackbar('Error de conexión al guardar: ' + e.message, 'error');
            } finally {
                btn.disabled = false;
                spinner.classList.add('d-none');
                btnText.innerText = 'Confirmar y Aplicar';
            }
        }

        async function applyAdvancedFilters(url = null) {
            let fetchUrl = url || "{{ route('seguimiento.index') }}?" + new URLSearchParams({
                numero_contrato: document.getElementById('filterContrato').value,
                tipo_contratista: document.getElementById('filterTipo').value,
                supervisor_id: document.getElementById('filterSup').value,
                estado_filtro: document.getElementById('filterEstado').value,
                secop_filtro: document.getElementById('filterSecop').value,
                sort_order: document.getElementById('sortOrder').value
            }).toString();

            try {
                const res = await window.apiFetch(fetchUrl);
                const data = await res.json();
                document.getElementById('tableBody').innerHTML = data.table;
                document.getElementById('pagination-container').innerHTML = data.pagination;
                // Update stats
                document.getElementById('stat-ok').innerText = parseInt(data.stats.ok_contratos).toLocaleString();
                document.getElementById('stat-pend').innerText = parseInt(data.stats.pend_contratos).toLocaleString();
                document.getElementById('stat-avg').innerText = parseFloat(data.stats.avg_cumplimiento).toFixed(1);
                document.getElementById('stat-bar').style.width = data.stats.avg_cumplimiento + '%';
                document.getElementById('stat-val-total').innerText = parseInt(data.stats.total).toLocaleString();
                document.getElementById('stat-sec-cerrado').innerText = parseInt(data.stats.sec_cerrado).toLocaleString();
                document.getElementById('stat-sec-ejecucion').innerText = parseInt(data.stats.sec_ejecucion).toLocaleString();
                document.getElementById('stat-sec-vacio').innerText = parseInt(data.stats.sec_vacio).toLocaleString();
                document.getElementById('stat-total-con-seg').innerText = parseInt(data.stats.total).toLocaleString();
            } catch (e) { console.error(e); }
        }

        function openEditModal(c) {
            document.getElementById('mTitle').innerText = 'Ficha de Contrato #' + c.numero_contrato;
            document.getElementById('mMethod').value = 'PUT';
            document.getElementById('mainForm').action = '{{ route('seguimiento.index') }}/' + c.id;
            document.getElementById('mContrato').value = c.numero_contrato;
            document.getElementById('mProceso').value = c.numero_proceso || '';
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

        document.getElementById('mainForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            try {
                const res = await window.apiFetch(this.action, { method: 'POST', body: new FormData(this) });
                if (res.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('modalManagement')).hide();
                    window.showSnackbar('Cambios guardados', 'success');
                    applyAdvancedFilters();
                }
            } catch (e) { window.showSnackbar('Error al guardar', 'error'); }
            finally { btn.disabled = false; }
        });

        function toggleSort() {
            const current = document.getElementById('sortOrder').value;
            const next = current === 'asc' ? 'desc' : 'asc';
            document.getElementById('sortOrder').value = next;
            document.getElementById('sortIcon').className = `bi bi-sort-alpha-${next === 'asc' ? 'down' : 'up'}`;
            applyAdvancedFilters();
        }

        async function exportToExcel() {
            const btn = document.querySelector('button[onclick="exportToExcel()"]');
            const originalHTML = btn ? btn.innerHTML : '';
            if (btn) {
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Descargando...';
                btn.disabled = true;
            }

            const params = new URLSearchParams({
                numero_contrato: document.getElementById('filterContrato').value,
                tipo_contratista: document.getElementById('filterTipo').value,
                estado_filtro: document.getElementById('filterEstado').value,
                secop_filtro: document.getElementById('filterSecop').value,
                supervisor_id: document.getElementById('filterSup').value,
                sort_order: document.getElementById('sortOrder') ? document.getElementById('sortOrder').value : 'desc'
            });

            try {
                // apiFetch o fetch con los headers correctos
                const response = await fetch("{{ route('seguimiento.export') }}?" + params.toString(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/json'
                    }
                });

                if (!response.ok) {
                    let errMsg = 'Error en el servidor al generar el Excel';
                    try { 
                        const errObj = await response.json(); 
                        errMsg = errObj.message || errMsg; 
                    } catch(e) { }
                    window.showSnackbar(errMsg, 'error');
                    return;
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                
                let filename = 'reporte_secop.xlsx';
                const disposition = response.headers.get('content-disposition');
                if (disposition && disposition.indexOf('filename=') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                window.showSnackbar('Descarga completada con éxito', 'success');
            } catch (error) {
                console.error('Error al exportar a Excel:', error);
                window.showSnackbar('Ocurrió un problema de red o permisos.', 'error');
            } finally {
                if (btn) {
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }
            }
        }

        function openLinkModal(id, currentLink) {
            document.getElementById('linkContratoId').value = id;
            document.getElementById('linkInput').value = currentLink || '';
            new bootstrap.Modal(document.getElementById('modalLink')).show();
        }

        async function saveLink() {
            const id = document.getElementById('linkContratoId').value;
            const link = document.getElementById('linkInput').value;
            const btn = document.getElementById('btnSaveLink');
            const spinner = document.getElementById('spinnerSaveLink');
            const btnText = document.getElementById('textSaveLink');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            btnText.innerText = 'Guardando...';

            try {
                const res = await window.apiFetch('{{ route('seguimiento.update-status') }}', {
                    method: 'POST',
                    body: JSON.stringify({
                        id,
                        field: 'link_secop',
                        status: link
                    })
                });

                if (res.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('modalLink')).hide();
                    window.showSnackbar('Link actualizado correctamente', 'success');
                    applyAdvancedFilters();
                } else {
                    const data = await res.json();
                    window.showSnackbar('Error: ' + (data.message || 'No se pudo actualizar el link'), 'error');
                }
            } catch (e) {
                console.error(e);
                window.showSnackbar('Error de conexión', 'error');
            } finally {
                btn.disabled = false;
                spinner.classList.add('d-none');
                btnText.innerText = 'Guardar Link';
            }
        }

        async function deleteContrato(id, numero) {
            const res = await Swal.fire({
                title: '¿Confirmar eliminación?',
                text: `Se eliminará el contrato #${numero} de forma permanente.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Sí, eliminar'
            });
            if (res.isConfirmed) {
                try {
                    const r = await window.apiFetch(`{{ route('seguimiento.index') }}/${id}`, { method: 'POST', body: JSON.stringify({ _method: 'DELETE' }) });
                    if (r.ok) { window.showSnackbar('Contrato eliminado', 'success'); applyAdvancedFilters(); }
                } catch (e) { window.showSnackbar('Error al eliminar', 'error'); }
            }
        }

        // AUTO-HIDE BANNER WHEN MODAL OPENS
        document.addEventListener('show.bs.modal', function () {
            const banner = document.getElementById('batchBanner');
            if(banner) {
                banner.style.opacity = '0';
                banner.style.pointerEvents = 'none';
                banner.style.transform = 'translateX(-50%) translateY(150%)';
            }
        });

        document.addEventListener('hidden.bs.modal', function () {
            const banner = document.getElementById('batchBanner');
            if(banner) {
                banner.style.opacity = '1';
                banner.style.pointerEvents = 'auto';
                renderBatchBanner();
            }
        });
    </script>
@endsection

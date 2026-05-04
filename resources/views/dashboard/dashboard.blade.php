@extends('layouts.app')

@section('title', 'Dashboard - Inicio')

@push('styles')
    @vite(['resources/views/dashboard/dashboard.css'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('page-content')
        <h1 class="main-dashboard-title">
            Vista Consolidada de Cuentas
        </h1>
        <p class="text-muted">Bienvenido, <strong>{{ auth()->user()?->primer_nombre ?? 'Usuario' }}</strong>.
        </p>


        @if ($canEditDashboard)
            <div class="carga-archivo-govco animate-in delay-2 shadow-lg mb-4" style="padding: 24px; border-radius: 20px;">
                <div class="row align-items-center">
                    <div class="col-lg-7">
                        <form id="importForm" enctype="multipart/form-data" class="m-0"
                            data-url="{{ route('dashboard.importar') }}">
                            @csrf
                            <div class="d-flex align-items-center gap-3">
                                <div class="all-input-carga-archivo-govco m-0" style="flex: 1;">
                                    <input type="file" id="inputId" name="inputFile"
                                        class="input-carga-archivo-govco active" data-error="false" data-action="uploadFile"
                                        data-action-delete="deleteFile" accept=".xlsx,.xls,.xlsm,.csv" />
                                    <label for="inputId" class="container-input-carga-archivo-govco m-0"
                                        style="display: inline-flex; align-items: center; width: 100%; height: 50px;">
                                        <span class="button-file-carga-archivo-govco h-100 d-flex align-items-center">Seleccionar Excel</span>
                                        <span class="file-name-carga-archivo-govco text-muted ps-3" id="fileNameDisplay">Esperando archivo...</span>
                                    </label>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div id="importSpinner" style="display: none;">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                    <button type="submit" id="btnImport" class="btn btn-dark fw-bold px-4"
                                        style="height: 50px; border-radius: 12px;" disabled>Cargar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-5 text-end d-flex gap-3 justify-content-end align-items-center">
                        <button type="button" class="btn btn-outline-primary fw-bold" data-bs-toggle="modal"
                            data-bs-target="#manualEntryModal" style="height: 50px; border-radius: 12px; border-width: 2px;">
                            <i class="bi bi-plus-circle me-2"></i>Carga Manual
                        </button>
                        <a href="{{ route('dashboard.exportar', request()->all()) }}"
                            class="btn btn-success fw-bold d-inline-flex align-items-center"
                            style="text-decoration: none; height: 50px; border-radius: 12px; background: #15803d; border: none;">
                            <i class="bi bi-file-earmark-excel me-2"></i>Exportar
                        </a>
                        <a href="{{ route('dashboard.plantilla') }}"
                            class="btn btn-primary fw-bold d-inline-flex align-items-center"
                            style="text-decoration: none; height: 50px; border-radius: 12px; background: #1e1e1e; border: none;">
                            <i class="bi bi-file-earmark-arrow-down me-2"></i>Plantilla
                        </a>
                    </div>
                </div>
            </div>
        @endif

        @include('dashboard.componentes.manual_entry_modal')
        {{-- El script para el modal manual ahora está en dashboard.js --}}

        <form id="filtersForm" method="GET" action="{{ route('dashboard') }}">
            {{-- Nivel 1: Barra de Búsqueda Superior --}}
            <div class="filters-container-glass mb-4 shadow-sm">
                <div class="card-body p-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-muted small uppercase">No. Contrato</label>
                            <input type="text" name="searchContrato" value="{{ request('searchContrato') }}"
                                class="form-control filter-input" placeholder="Ej: 2026-001">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-muted small uppercase">Contratista</label>
                            <input type="text" name="searchContratista" value="{{ request('searchContratista') }}"
                                class="form-control filter-input" placeholder="Nombre...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-muted small uppercase">Cédula / NIT</label>
                            <input type="text" name="searchCedula" value="{{ request('searchCedula') }}"
                                class="form-control filter-input" placeholder="Identificación...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-muted small uppercase">Estado</label>
                            <div class="dropdown custom-multilevel-dropdown">
                                <button
                                    class="dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center shadow-none"
                                    type="button" id="dropdownEstado" data-bs-toggle="dropdown" aria-expanded="false" 
                                    style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 15px; background: rgba(248, 250, 252, 0.8);">
                                    <span id="selectedEstadoLabel" class="text-truncate">{{ request('searchEstado') ?: 'Todos' }}</span>
                                    <i class="bi bi-chevron-down small opacity-50 ms-2"></i>
                                </button>
                                <input type="hidden" name="searchEstado" id="hiddenSearchEstado"
                                    value="{{ request('searchEstado') }}">

                                <ul class="dropdown-menu w-100 shadow-2xl border-0" aria-labelledby="dropdownEstado" 
                                    style="border-radius: 12px; z-index: 2000;">
                                    {{-- HEADER --}}
                                    <li class="dropdown-header-premium">
                                        <i class="bi bi-layers"></i>
                                        <span>Todos los estados</span>
                                    </li>

                                    {{-- ALL OPTION --}}
                                    <li>
                                        <a class="dropdown-item-all filter-estado-item" href="#" data-value="">
                                            <i class="bi bi-circle-fill" style="font-size: 0.85rem; color: #475569;"></i>
                                            <span>Todos los estados</span>
                                        </a>
                                    </li>

                                    {{-- SECTIONS (ACCORDION STYLE) --}}
                                    @foreach ($bloques as $bloque)
                                        @php
                                            $estadosDelBloque = $todosLosEstados[$bloque->codigo] ?? collect();
                                        @endphp

                                        @if ($estadosDelBloque->isNotEmpty())
                                            <li>
                                                <div class="accordion-trigger-item" onclick="event.stopPropagation(); this.nextElementSibling.classList.toggle('d-none');">
                                                    <div class="title-container">
                                                        <i class="bi bi-folder"></i>
                                                        <span>{{ strtoupper($bloque->nombre) }}</span>
                                                    </div>
                                                    <i class="bi bi-chevron-down"></i>
                                                </div>
                                                <div class="dropdown-submenu-list d-none pt-1 pb-2" style="background: #fafbfc;">
                                                    @foreach ($estadosDelBloque as $est)
                                                        <a class="dropdown-item dropdown-item-state filter-estado-item {{ request('searchEstado') == $est->nombre ? 'active' : '' }}"
                                                            href="#" data-value="{{ $est->nombre }}">
                                                            {{ $est->nombre }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-bold text-muted small uppercase">N° Cuenta</label>
                            <input type="number" name="searchNumeroCuenta" value="{{ request('searchNumeroCuenta') }}"
                                class="form-control filter-input" placeholder="Ej: 3" min="1">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-primary fw-bold" type="submit" style="flex: 2; border-radius: 10px; background-color: var(--corp-blue) !important; border: none;">
                                <i class="bi bi-funnel-fill me-1"></i> Filtrar
                            </button>
                            <button class="btn btn-outline-secondary fw-bold" type="button" id="btnResetAllFilters"
                                style="flex: 1; border-radius: 10px;">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <button class="btn btn-dark fw-bold" type="button" data-bs-toggle="offcanvas"
                                data-bs-target="#offcanvasAdvancedFilters" style="flex: 1.2; border-radius: 10px; background-color: #1e293b !important; border: none;">
                                <i class="bi bi-gear-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Indicadores de Filtros Activos --}}
            @php
                $activeFilters = collect(
                    request()->only([
                        'searchContrato',
                        'searchContratista',
                        'searchCedula',
                        'searchEstado',
                        'searchNumeroCuenta',
                        'filterContrato',
                        'filterSupervisor',
                        'filterEstadosRevision',
                        'filterRadicadaHacienda',
                        'filterEnFacturacion',
                        'numero_cuenta',
                    ]),
                )->filter();
            @endphp

            @if ($activeFilters->isNotEmpty())
                <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted small fw-bold">Filtros activos:</span>
                    @foreach ($activeFilters as $key => $value)
                        @if ($key === 'filterEstadosRevision')
                            @foreach ((array) $value as $estId)
                                @php $estNombre = $estadosRevision?->firstWhere('id', $estId)?->nombre; @endphp
                                @if ($estNombre)
                                    <span class="badge bg-light text-dark border">Estado: {{ $estNombre }}</span>
                                @endif
                            @endforeach
                        @else
                            <span class="badge bg-light text-dark border">{{ ucfirst($key) }}: {{ $value }}</span>
                        @endif
                    @endforeach
                    <a href="{{ route('dashboard') }}" class="btn btn-link btn-sm text-danger p-0 ms-2">Limpiar todos</a>
                </div>
            @endif

            @include('dashboard.componentes.advanced_filters_offcanvas')
        </form>

        <div class="table-card-premium shadow-lg mt-4 border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-white d-flex align-items-center">
                    <i class="bi bi-list me-2"></i>
                    Control Operativo de Pagos
                </h6>
                <div class="d-flex align-items-center gap-3">
                    <button id="btnResetColumns" class="btn btn-sm btn-link text-white text-decoration-none"
                        style="display: none; font-size: 0.7rem; font-weight: 700;" onclick="resetColumns()">
                        RESTAURAR COLUMNAS
                    </button>
                    <div id="tableSpinner" style="display: none;" class="spinner-border spinner-border-sm text-primary"
                        role="status">
                    </div>
                    <span class="badge badge-pill shadow-sm" id="resultsCount">
                        {{ $cuentas->total() }} registros
                    </span>
                </div>
            </div>
            <div class="card-body p-0" id="tableContainer">
                @include('dashboard.componentes.cuentas_table')
            </div>
        </div>


        @include('dashboard.componentes.history_modal')

        @push('scripts')
            @vite(['resources/views/dashboard/dashboard.js'])
        @endpush
    </div>
@endsection

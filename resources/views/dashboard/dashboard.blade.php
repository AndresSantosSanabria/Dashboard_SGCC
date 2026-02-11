@extends('layouts.app')

@section('title', 'Dashboard - Inicio')

@push('styles')
    @vite(['resources/views/dashboard/dashboard.css'])
@endpush

@section('page-content')
    <h1 style="margin-top: 20px; margin-bottom: 30px; color: #000000; text-align: center; font-weight: bold;">
        Vista consolidada de cuentas de cobro
    </h1>
    <p class="text-muted mt-3">Bienvenido, <strong>{{ auth()->user()?->primer_nombre ?? 'Usuario' }}</strong>.</p>


    <div class="carga-archivo-govco" style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <form id="importForm" enctype="multipart/form-data" class="m-0" data-url="{{ route('dashboard.importar') }}">
                    @csrf
                    <div class="d-flex align-items-center gap-3">
                        <div class="all-input-carga-archivo-govco m-0" style="flex: 1;">
                            <input type="file" id="inputId" name="inputFile" class="input-carga-archivo-govco active"
                                data-error="false" data-action="uploadFile" data-action-delete="deleteFile"
                                accept=".xlsx,.xls,.xlsm,.csv" />
                            <label for="inputId" class="container-input-carga-archivo-govco m-0"
                                style="display: inline-flex; align-items: center; width: 100%;">
                                <span class="button-file-carga-archivo-govco">Seleccionar archivo Excel</span>
                                <span class="file-name-carga-archivo-govco" id="fileNameDisplay">Sin archivo
                                    seleccionado</span>
                            </label>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div id="importSpinner" style="display: none;">
                                <div class="spinner-indicador-de-carga-govco" role="status"></div>
                            </div>
                            <button type="submit" id="btnImport" class="button-loader-carga-archivo-govco m-0"
                                disabled>Cargar archivo</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-lg-5 text-end d-flex gap-2 justify-content-end align-items-center">
                <button type="button" class="btn-govco fill-btn-govco m-0" data-bs-toggle="modal"
                    data-bs-target="#manualEntryModal" style="height: fit-content;">
                    Cargar Manual
                </button>
                <a href="{{ route('dashboard.plantilla') }}"
                    class="btn-govco outline-btn-govco d-inline-flex align-items-center m-0"
                    style="text-decoration: none; height: fit-content;">
                    Plantilla
                </a>
            </div>
        </div>
    </div>

    @include('dashboard.componentes.manual_entry_modal')
    {{-- El script para el modal manual ahora está en dashboard.js --}}

    <form id="filtersForm" method="GET" action="{{ route('dashboard') }}">
        {{-- Nivel 1: Barra de Búsqueda Superior --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Número de Contrato</label>
                        <input type="text" name="searchContrato" value="{{ request('searchContrato') }}"
                            class="form-control filter-input" placeholder="Buscar contrato...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Contratista</label>
                        <input type="text" name="searchContratista" value="{{ request('searchContratista') }}"
                            class="form-control filter-input" placeholder="Nombre del contratista...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cédula / NIT</label>
                        <input type="text" name="searchCedula" value="{{ request('searchCedula') }}"
                            class="form-control filter-input" placeholder="Número de identificación...">
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-outline-primary w-100" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasAdvancedFilters">
                            Filtros Avanzados
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
                    'filterContrato',
                    'filterSupervisor',
                    'filterEstadosRevision',
                    'filterRadicadaHacienda',
                    'filterEnFacturacion',
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

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Gestión de Cuentas de Cobro</h5>
            <div class="d-flex align-items-center gap-3">
                <div id="tableSpinner" style="display: none;" class="spinner-border spinner-border-sm text-white"
                    role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <span class="badge bg-white text-primary" id="resultsCount">Resultados: {{ $cuentas->total() }}</span>
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
@endsection

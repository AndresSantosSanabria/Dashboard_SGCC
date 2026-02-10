@extends('layouts.sidebar')

@section('title', 'Dashboard - Inicio')

@section('page-content')
    <style>
        .sticky-col {
            position: sticky !important;
            background-color: white !important;
            z-index: 5;
            box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1);
            border-right: 1px solid #dee2e6 !important;
        }

        /* En el header, fondo oscuro y z-index mayor */
        thead th.sticky-col {
            background-color: #343a40 !important;
            z-index: 15; /* Por encima de celdas sticky del body */
        }

        .sticky-col-1 { left: 0; min-width: 140px; max-width: 140px; }
        .sticky-col-2 { left: 140px; min-width: 210px; max-width: 210px; }
        .sticky-col-3 { left: 350px; min-width: 120px; max-width: 120px; }

        /* Estilo para filas en hover para que las celdas sticky no tapen el efecto */
        .table-hover tbody tr:hover td.sticky-col {
            background-color: #f2f2f2 !important;
        }

        /* Timeline Styles */
        .timeline-container { position: relative; padding: 20px 0; }
        .timeline-item { display: flex; position: relative; margin-bottom: 25px; align-items: flex-start; }
        .timeline-marker-wrapper { position: relative; display: flex; flex-direction: column; align-items: center; margin-right: 20px; min-width: 50px; }
        .timeline-marker { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem; z-index: 2; box-shadow: 0 4px 6px rgba(0,0,0,0.1); flex-shrink: 0; }
        .timeline-line { position: absolute; top: 40px; left: 50%; transform: translateX(-50%); width: 2px; height: calc(100% + 25px); background: #e9ecef; }
        .timeline-item:last-child .timeline-line { display: none; }
        .timeline-content { flex: 1; background: #f8f9fe; border-radius: 10px; padding: 15px; border: 1px solid #e9ecef; }
        .timeline-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .timeline-title { font-weight: 700; font-size: 0.9rem; color: #172b4d; }
        .timeline-date { font-size: 0.7rem; color: #8898aa; }
        .timeline-transition { font-size: 0.8rem; color: #525f7f; margin-bottom: 5px; }
        .timeline-meta { display: flex; gap: 15px; font-size: 0.75rem; color: #8898aa; margin-top: 8px; }
        .timeline-comment { background: #fff; border-radius: 6px; padding: 10px; font-size: 0.8rem; color: #525f7f; border-left: 3px solid #5e72e4; margin-top: 10px; font-style: italic; }
    </style>
    <h1 style="margin-top: 20px; margin-bottom: 30px; color: #000000; text-align: center; font-weight: bold;">
        Vista consolidada de cuentas de cobro
    </h1>
    <p class="text-muted mt-3">Bienvenido, <strong>{{ auth()->user()?->primer_nombre ?? 'Usuario' }}</strong>.</p>


    <div class="carga-archivo-govco" style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <form id="importForm" enctype="multipart/form-data" class="m-0">
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

    <!-- Modal para Carga Manual -->
    <div class="modal fade" id="manualEntryModal" tabindex="-1" aria-labelledby="manualEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="manualEntryModalLabel">Cargar Información Manualmente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="manualForm">
                        @csrf
                        <div class="row g-3">
                            {{-- Fila 1 --}}
                            <div class="col-md-4">
                                <label class="form-label fw-bold">NÚMERO DE CONTRATO *</label>
                                <input type="text" name="NUMERO DE CONTRATO" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CONTRATISTA</label>
                                <input type="text" name="CONTRATISTA" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CÉDULA / NIT</label>
                                <input type="text" name="CEDULA" class="form-control">
                            </div>

                            {{-- Fila 2 --}}
                            <div class="col-md-4">
                                <label class="form-label">RP</label>
                                <input type="text" name="RP" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">FECHA RP</label>
                                <input type="date" name="FECHA RP" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">VALOR RP</label>
                                <input type="number" step="0.01" name="VALOR RP" class="form-control">
                            </div>

                            {{-- Fila 3 --}}
                            <div class="col-md-4">
                                <label class="form-label">FECHA DE INICIO</label>
                                <input type="date" name="FECHA DE INICIO" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">FECHA DE TERMINACIÓN</label>
                                <input type="date" name="FECHA DE TERMINACIÓN" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SUPERVISOR</label>
                                <input type="text" name="SUPERVISOR" class="form-control">
                            </div>

                            {{-- Fila 4 --}}
                            <div class="col-md-4">
                                <label class="form-label">N° CUENTA PROCESO</label>
                                <input type="number" name="NUMERO DE CUENTA EN PROCESO DE CUENTAS" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N° PAGOS TOTALES</label>
                                <input type="number" name="NUMERO DE PAGOS TOTALES" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">N° FACTURAS RADICADAS</label>
                                <input type="number" name="N° DE FACTURAS RADICADA HACIENDA" class="form-control">
                            </div>

                            {{-- Fila 5 --}}
                            <div class="col-md-4">
                                <label class="form-label">PORCENTAJE CUENTAS</label>
                                <input type="number" step="0.01" name="PORCENTAJE DE CUENTAS" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ENTIDAD SALUD</label>
                                <input type="text" name="ENTIDAD SALUD" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ENTIDAD PENSIÓN</label>
                                <input type="text" name="ENTIDAD PENSIÓN" class="form-control">
                            </div>

                            {{-- Fila 6 --}}
                            <div class="col-md-4">
                                <label class="form-label">ENTIDAD ARL</label>
                                <input type="text" name="ENTIDAD ARL" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ULTIMA PLANILLA SS</label>
                                <input type="text" name="PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA"
                                    class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">RADICADO POR</label>
                                <input type="text" name="RADICADO POR" class="form-control">
                            </div>

                            {{-- Fila 7 --}}
                            <div class="col-md-6">
                                <label class="form-label">FECHA RADICACIÓN (INICIAL/CORREC)</label>
                                <input type="date" name="FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES"
                                    class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">OBSERVACIONES</label>
                                <textarea name="OBSERVACIONES" class="form-control" rows="1"></textarea>
                            </div>

                            <hr>
                            <h6 class="text-primary mt-0">Campos Adicionales (Revisiones / SAP / Hacienda)</h6>

                            {{-- Fila 8 --}}
                            <div class="col-md-3">
                                <label class="form-label small">ESTADO REVISIÓN 1</label>
                                <select name="ESTADO TRAS PRIMERA REVISIÓN" class="form-select form-select-sm">
                                    <option value="">Seleccione estado...</option>
                                    @foreach ($todosLosEstados['REV1'] ?? [] as $estado)
                                        <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">FECHA DEVUELTA/SAP</label>
                                <input type="date" name="FECHA DEVUELTA DE REVISIÓN O ENVIADA A SAP"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">ENVIADA SAP</label>
                                <select name="ENVIADA A INGRESO MERCANCIA SAP" class="form-select form-select-sm">
                                    <option value="">Seleccione estado...</option>
                                    @foreach ($todosLosEstados['SAP'] ?? [] as $estado)
                                        <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">RESPONSABLE REV</label>
                                <input type="text" name="RESPONSABLE_REV" class="form-control form-control-sm">
                            </div>

                            {{-- Fila 9 --}}
                            <div class="col-md-3">
                                <label class="form-label small">FECHA FACTURACION</label>
                                <input type="date" name="FECHA DE ENVIO A FACTURACIÓN O DEVUELTA A CORRECIONES"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">EN FACTURACIÓN</label>
                                <select name="EN FACTURACIÓN" class="form-select form-select-sm">
                                    <option value="">Seleccione estado...</option>
                                    @foreach ($todosLosEstados['FAC'] ?? [] as $estado)
                                        <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">RESPONSABLE FAC</label>
                                <input type="text" name="RESPONSABLE_FAC" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">FECHA GEN. FACT.</label>
                                <input type="date" name="FECHA EN QUE SE GENERA FACURACIÓN"
                                    class="form-control form-control-sm">
                            </div>

                            {{-- Fila 10 --}}
                            <div class="col-md-3">
                                <label class="form-label small">FIRMA SECRETARIO</label>
                                <select name="FIRMA SECRETARIO" class="form-select form-select-sm">
                                    <option value="">Seleccione estado...</option>
                                    @foreach ($todosLosEstados['FIR'] ?? [] as $estado)
                                        <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">FECHA PARA FIRMA</label>
                                <input type="date" name="FECHA EN QUE SE DEJAN PARA FIRMA DEL SECRETARIO"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">ESTADO HACIENDA</label>
                                <select name="RADICADA EN HACIENDA" class="form-select form-select-sm">
                                    <option value="">Seleccione estado...</option>
                                    @foreach ($todosLosEstados['HAC'] ?? [] as $estado)
                                        <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">FECHA RAD HACIENDA</label>
                                <input type="date" name="FECHA DE RADICACIÓN" class="form-control form-control-sm">
                            </div>

                            {{-- Fila 11 --}}
                            <div class="col-md-4">
                                <label class="form-label small">ULTIMA FACTURA HACIENDA</label>
                                <input type="text" name="ULTIMA FACTURA RADICADA HACIENDA"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">OBS. DEVOL. HACIENDA</label>
                                <input type="text" name="OBSERVACIÓN DEVOLUCIÓN HACIENDA"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">DIFERENCIA CUENTAS</label>
                                <input type="number" name="DIFERENCIA CUENTAS TOTALES - VS CUENTAS RADICADAS"
                                    class="form-control form-control-sm">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnSaveManual" class="btn btn-primary">Cargar Registro</button>
                </div>
            </div>
        </div>
    </div>
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

        {{-- Nivel 2: Panel de Filtros Avanzados (Offcanvas) --}}
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAdvancedFilters"
            aria-labelledby="offcanvasAdvancedFiltersLabel">
            <div class="offcanvas-header bg-primary text-white">
                <h5 class="offcanvas-title" id="offcanvasAdvancedFiltersLabel">Filtros Avanzados</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Número de Contrato (Exacto)</label>
                    <input type="text" name="filterContrato" value="{{ request('filterContrato') }}"
                        class="form-control filter-input" placeholder="Búsqueda exacta...">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Supervisor</label>
                    <select name="filterSupervisor" class="form-select filter-input">
                        <option value="">Todos los supervisores</option>
                        @foreach ($supervisores as $sup)
                            <option value="{{ $sup->id }}"
                                {{ request('filterSupervisor') == $sup->id ? 'selected' : '' }}>
                                {{ $sup->nombre_completo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Estado tras Primera Revisión</label>
                    <div class="p-2 border rounded" style="max-height: 200px; overflow-y: auto;">
                        @foreach ($estadosRevision as $estado)
                            <div class="form-check">
                                <input class="form-check-input filter-input" type="checkbox"
                                    name="filterEstadosRevision[]" value="{{ $estado->id }}"
                                    id="est_{{ $estado->id }}"
                                    {{ in_array($estado->id, (array) request('filterEstadosRevision')) ? 'checked' : '' }}>
                                <label class="form-check-label" for="est_{{ $estado->id }}">
                                    {{ $estado->nombre }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Radicada en Hacienda</label>
                    <select name="filterRadicadaHacienda" class="form-select filter-input">
                        <option value="">Cualquiera</option>
                        <option value="SI" {{ request('filterRadicadaHacienda') === 'SI' ? 'selected' : '' }}>Sí
                        </option>
                        <option value="NO" {{ request('filterRadicadaHacienda') === 'NO' ? 'selected' : '' }}>No
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">En Facturación</label>
                    <select name="filterEnFacturacion" class="form-select filter-input">
                        <option value="">Cualquiera</option>
                        <option value="SI" {{ request('filterEnFacturacion') === 'SI' ? 'selected' : '' }}>Sí</option>
                        <option value="NO" {{ request('filterEnFacturacion') === 'NO' ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Limpiar filtros</a>
                </div>
            </div>
        </div>
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
            @include('partials.cuentas_table')
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('inputId');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const btnImport = document.getElementById('btnImport');
            const importForm = document.getElementById('importForm');
            const importSpinner = document.getElementById('importSpinner');

            const btnSaveManual = document.getElementById('btnSaveManual');
            const manualForm = document.getElementById('manualForm');
            const manualModal = new bootstrap.Modal(document.getElementById('manualEntryModal'));

            // Manejo de selección de archivo
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    fileNameDisplay.textContent = file.name;
                    btnImport.disabled = false;
                } else {
                    fileNameDisplay.textContent = 'Sin archivo seleccionado';
                    btnImport.disabled = true;
                }
            });

            // AJAX Import Excel
            importForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                btnImport.disabled = true;
                importSpinner.style.display = 'block';

                fetch("{{ route('dashboard.importar') }}", {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json().catch(() => ({
                        success: false,
                        message: 'Respuesta no válida del servidor'
                    })))
                    .then(data => {
                        importSpinner.style.display = 'none';
                        if (data.success) {
                            showSnackbar('✅ ' + data.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showSnackbar('⚠️ ' + data.message, 'error');
                            btnImport.disabled = false;
                        }
                    })
                    .catch(error => {
                        importSpinner.style.display = 'none';
                        showSnackbar('❌ Error: ' + error.message, 'error');
                        btnImport.disabled = false;
                    });
            });

            // AJAX Manual Entry
            btnSaveManual.addEventListener('click', function() {
                const formData = new FormData(manualForm);
                btnSaveManual.disabled = true;
                btnSaveManual.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status"></span> Cargando...';

                fetch("{{ route('dashboard.manual') }}", {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json().catch(() => ({
                        success: false,
                        message: 'Respuesta no válida del servidor'
                    })))
                    .then(data => {
                        if (data.success) {
                            showSnackbar('✅ ' + data.message, 'success');
                            manualModal.hide();
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showSnackbar('⚠️ ' + data.message, 'error');
                            btnSaveManual.disabled = false;
                            btnSaveManual.textContent = 'Cargar Registro';
                        }
                    })
                    .catch(error => {
                        showSnackbar('❌ Error: ' + error.message, 'error');
                        btnSaveManual.disabled = false;
                        btnSaveManual.textContent = 'Cargar Registro';
                    });
            });

        });

        // --- Lógica de Filtros Instantáneos (Live Search) ---
        const filtersForm = document.getElementById('filtersForm');
        const tableContainer = document.getElementById('tableContainer');
        const resultsCountEl = document.getElementById('resultsCount');
        const tableSpinner = document.getElementById('tableSpinner');

        let abortController = null;

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        const fetchFilteredData = () => {
            // Cancelar petición previa si existe
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            const formData = new FormData(filtersForm);
            const params = new URLSearchParams(formData).toString();
            const url = `${filtersForm.action}?${params}`;

            tableSpinner.style.display = 'inline-block';
            tableContainer.style.opacity = '0.5';
            tableContainer.style.pointerEvents = 'none';

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: abortController.signal
                })
                .then(response => {
                    const total = response.headers.get('X-Total-Count');
                    if (total !== null) {
                        resultsCountEl.textContent = `Resultados: ${total}`;
                    }
                    return response.text();
                })
                .then(html => {
                    tableContainer.innerHTML = html;
                    tableSpinner.style.display = 'none';
                    tableContainer.style.opacity = '1';
                    tableContainer.style.pointerEvents = 'auto';

                    // Re-vincular eventos de paginación AJAX
                    bindPagination();
                })
                .catch(error => {
                    if (error.name === 'AbortError') return;
                    console.error('Error fetching filtered data:', error);
                    tableSpinner.style.display = 'none';
                    tableContainer.style.opacity = '1';
                    tableContainer.style.pointerEvents = 'auto';
                });
        };

        const debouncedSearch = debounce(fetchFilteredData, 250);

        // Prevenir envío tradicional del formulario
        filtersForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchFilteredData();
        });

        // Delegación de eventos para los inputs de filtro
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('filter-input')) {
                debouncedSearch();
            }
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('filter-input') && e.target.tagName !== 'INPUT') {
                fetchFilteredData();
            }
        });

        // Manejo de paginación AJAX
        function bindPagination() {
            const links = document.querySelectorAll('#pagination-links a');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Cancelar búsquedas en curso si se cambia de página
                    if (abortController) abortController.abort();

                    const url = this.href;
                    tableSpinner.style.display = 'inline-block';
                    tableContainer.style.opacity = '0.5';

                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.text())
                        .then(html => {
                            tableContainer.innerHTML = html;
                            tableSpinner.style.display = 'none';
                            tableContainer.style.opacity = '1';
                            bindPagination();

                            // Scroll top suave hacia la tabla
                            tableContainer.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        })
                        .catch(err => console.error('Error pagination:', err));
                });
            });
        }

        bindPagination();
    </script>

    <!-- Modal de Historial de Workflow -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="historyModalLabel">
                        <i class="fas fa-history me-2"></i>Historial de Movimientos - Contrato <span id="historyContratoNum"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light" style="max-height: 70vh; overflow-y: auto;">
                    <div id="historySpinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Obteniendo línea de tiempo...</p>
                    </div>
                    <div id="timelineContent" class="timeline-container px-3" style="display: none;">
                        <!-- El contenido se cargará dinámicamente -->
                    </div>
                    <div id="historyEmpty" class="text-center py-5" style="display: none;">
                        <i class="fas fa-info-circle text-muted mb-3" style="font-size: 3rem;"></i>
                        <p class="text-muted">No hay registros de movimientos para esta cuenta.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showHistory(cuentaId, contratoNum) {
            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            document.getElementById('historyContratoNum').textContent = contratoNum;
            
            const spinner = document.getElementById('historySpinner');
            const content = document.getElementById('timelineContent');
            const empty = document.getElementById('historyEmpty');

            spinner.style.display = 'block';
            content.style.display = 'none';
            empty.style.display = 'none';
            content.innerHTML = '';

            modal.show();

            fetch(`/workflow/historial/${cuentaId}`)
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = 'none';
                    
                    if (data.success && data.historial.length > 0) {
                        content.style.display = 'block';
                        
                        data.historial.forEach(h => {
                            const date = new Date(h.fecha_transicion).toLocaleString('es-ES', {
                                day: '2-digit', month: '2-digit', year: 'numeric',
                                hour: '2-digit', minute: '2-digit', hour12: true
                            });

                            const item = document.createElement('div');
                            item.className = 'timeline-item';
                            
                            // Determinar color de badge por tipo de estado destino
                            let badgeClass = 'bg-info';
                            if (h.estado_destino?.tipo === 'DEVUELTO') badgeClass = 'bg-danger';
                            if (h.estado_destino?.tipo === 'APROBADO' || h.estado_destino?.tipo === 'FINAL') badgeClass = 'bg-success';
                            
                            item.innerHTML = `
                                <div class="timeline-marker-wrapper">
                                    <div class="timeline-marker bg-primary">
                                        <i class="fas fa-exchange-alt"></i>
                                    </div>
                                    <div class="timeline-line"></div>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <div class="timeline-title">${h.bloque?.nombre ?? 'Bloque'}</div>
                                        <div class="timeline-date font-weight-bold">${date}</div>
                                    </div>
                                    <div class="timeline-transition">
                                        <strong>${h.estado_origen?.nombre ?? 'Inicio'}</strong> 
                                        <i class="fas fa-arrow-right mx-2 text-muted" style="font-size: 0.7rem;"></i> 
                                        <span class="badge ${badgeClass}">${h.estado_destino?.nombre ?? 'N/A'}</span>
                                    </div>
                                    <div class="timeline-meta">
                                        <span><i class="fas fa-user me-1"></i> ${h.usuario_accion?.primer_nombre ?? 'Sistema'}</span>
                                        ${h.accion ? `<span><i class="fas fa-tag me-1"></i> ${h.accion}</span>` : ''}
                                    </div>
                                    ${h.comentarios ? `
                                        <div class="timeline-comment">
                                            "${h.comentarios}"
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                            content.appendChild(item);
                        });
                    } else {
                        empty.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Error fetching history:', err);
                    spinner.style.display = 'none';
                    empty.style.display = 'block';
                    empty.querySelector('p').textContent = 'Error al cargar el historial.';
                });
        }
    </script>
@endsection

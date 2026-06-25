@extends('layouts.app')

@section('title', 'Tablero Analítico BI - SGCC')

@push('styles')
    @vite(['resources/views/Analitica/analitica.css'])
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('page-content')
    <div class="dashboard-wrapper">
        {{-- Mobile Header --}}
        <div class="mobile-header d-lg-none">
            <i class="bi bi-list"></i>
            <span class="m-title">Analítica</span>
            <div class="d-flex gap-2">
                <span id="mobileGlobalProgressVal" class="badge bg-primary rounded-pill">0%</span>
                <i class="bi bi-bell"></i>
            </div>
            <div class="progress position-absolute bottom-0 start-0 w-100" style="height: 2px;">
                <div id="mobileGlobalProgressBar" class="progress-bar" style="width: 0%"></div>
            </div>
        </div>

        <div class="header-bg"></div>

        <div class="container-fluid py-4 contents-container">

            {{-- MOBILE HERO --}}
            <div id="mobile-analytics-hero" class="d-lg-none">
                <p class="mhero-label">VALOR TOTAL RP</p>
                <h3 class="mhero-amount">${{ number_format($montoTotal, 0, ',', '.') }}</h3>
                <div class="row g-2 mt-2">
                    <div class="col-4"><div class="small opacity-75">Total</div><div id="m-kpi-total" class="fw-bold">--</div></div>
                    <div class="col-4"><div class="small opacity-75">Lenta</div><div id="m-kpi-lenta" class="fw-bold">--</div><div id="m-kpi-lenta-sub" class="x-small"></div></div>
                    <div class="col-4"><div class="small opacity-75">Rápida</div><div id="m-kpi-rapida" class="fw-bold">--</div><div id="m-kpi-rapida-sub" class="x-small"></div></div>
                </div>
            </div>

            {{-- DESKTOP HEADER --}}
            <div class="d-none d-lg-flex justify-content-between align-items-center mb-3 header-content flex-wrap gap-4">
                <div>
                    <h2 class="animate-in mb-1">Tablero Analítico</h2>
                    <p class="header-subtitle animate-in m-0 text-muted">Inteligencia de Negocio — Gestión de Cuentas de Cobro</p>
                </div>
                <div class="d-flex gap-3 align-items-center animate-in">
                    <div class="hero-indicator-mini me-2">
                        <div class="mini-label">Valor total RP</div>
                        <div class="mini-value text-primary">${{ number_format($montoTotal, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- FILTER BAR --}}
            <div id="filterSection" class="filter-bar mb-3 animate-in" style="position:relative;z-index:1100;">
                <form action="{{ route('analitica') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap m-0" id="filterForm">
                    <input type="hidden" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}">
                    <input type="hidden" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}">

                    <div class="header-date-selector" id="dateRangePicker" style="cursor:pointer">
                        <i class="bi bi-calendar3 text-muted"></i>
                        <span id="dateDisplay" class="small">
                            @if (request('fecha_desde') && request('fecha_hasta'))
                                {{ \Carbon\Carbon::parse(request('fecha_desde'))->translatedFormat('d M, Y') }} - {{ \Carbon\Carbon::parse(request('fecha_hasta'))->translatedFormat('d M, Y') }}
                            @else
                                {{ \Carbon\Carbon::now()->translatedFormat('d M, Y') }}
                            @endif
                        </span>
                    </div>

                    <div class="smart-dropdown" id="smartDropdownContrato">
                        <button class="smart-dropdown-trigger" type="button">
                            <span class="trigger-label">Contrato...</span>
                            <i class="bi bi-chevron-down" style="font-size:0.625rem;opacity:0.6"></i>
                        </button>
                        <div class="smart-dropdown-menu" data-field="contrato">
                            <input type="text" class="smart-dropdown-search" placeholder="Buscar contrato...">
                            <div class="smart-dropdown-optgroup">Contratos disponibles</div>
                            <div class="smart-dropdown-item is-active" data-value="">Todos</div>
                        </div>
                    </div>

                    <select name="supervisor" class="form-select-sm" style="min-width:150px;">
                        <option value="">Supervisor...</option>
                        @foreach ($supervisores as $sup)
                            <option value="{{ $sup->id }}" {{ request('supervisor') == $sup->id ? 'selected' : '' }}>{{ $sup->nombre_completo }}</option>
                        @endforeach
                    </select>

                    <select name="responsable" id="filterUsuario" class="form-select-sm" style="min-width:150px;">
                        <option value="">Responsable...</option>
                        @foreach ($responsables as $resp)
                            <option value="{{ $resp->id }}" {{ request('responsable') == $resp->id ? 'selected' : '' }}>{{ $resp->nombre_completo }}</option>
                        @endforeach
                    </select>

                    <select name="estado" class="form-select-sm" style="min-width:170px;">
                        <option value="">Todos los estados</option>
                        @foreach ($bloques as $bloque)
                            <optgroup label="{{ $bloque->nombre }}">
                            @foreach ($todosLosEstados[$bloque->codigo] ?? [] as $est)
                                <option value="{{ $est->nombre }}" {{ request('estado') == $est->nombre ? 'selected' : '' }}>{{ $est->nombre }}</option>
                            @endforeach
                            </optgroup>
                        @endforeach
                    </select>

                    <button type="submit" class="btn-filter"><i class="bi bi-funnel"></i> Filtrar</button>
                    <button type="button" id="btnReset" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i></button>

                    <div class="export-dropdown dropdown ms-auto">
                        <button type="button"
                                id="dropdownExportar"
                                data-bs-toggle="dropdown"
                                data-bs-offset="0,8"
                                data-bs-auto-close="true"
                                aria-expanded="false">
                            <i class="bi bi-download"></i> Exportar <i class="bi bi-chevron-down" style="font-size:0.65rem;"></i>
                        </button>
                        <ul class="dropdown-menu" style="z-index:99999 !important;position:absolute !important;">
                            <li><a class="dropdown-item" href="#" id="btnExportPDF"><i class="bi bi-filetype-pdf"></i> PDF</a></li>
                            <li><a class="dropdown-item" href="#" id="btnExportExcel"><i class="bi bi-filetype-xls"></i> Excel</a></li>
                        </ul>
                    </div>
                </form>
                <div class="active-filters" id="activeFilters"></div>
            </div>

            <div id="captureArea" class="position-relative" style="z-index:0;">
                @include('layouts.partials._premium_loader')

                {{-- KPIs --}}
                <div class="kpi-row mb-3">
                    <div class="kpi-card kpi-sapphire animate-in">
                        <div class="kpi-header">
                            <span class="kpi-label">Contratistas</span>
                            <div class="kpi-icon-wrap"><i class="bi bi-people"></i></div>
                        </div>
                        <div class="kpi-value">{{ number_format($contratistasUnicos) }}</div>
                        <div class="sparkline-container" id="sparkline-1"></div>
                    </div>
                    <div class="kpi-card kpi-amber animate-in">
                        <div class="kpi-header">
                            <span class="kpi-label">En Proceso</span>
                            <div class="kpi-icon-wrap"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                        <div class="kpi-value">{{ number_format($cuentasTramite) }}</div>
                        <div class="sparkline-container" id="sparkline-2"></div>
                    </div>
                    <div class="kpi-card kpi-emerald animate-in">
                        <div class="kpi-header">
                            <span class="kpi-label">Finalizadas</span>
                            <div class="kpi-icon-wrap"><i class="bi bi-check2-circle"></i></div>
                        </div>
                        <div class="kpi-value">{{ number_format($cuentasRadicadas) }}</div>
                        <div class="sparkline-container" id="sparkline-3"></div>
                    </div>
                    <div class="kpi-card kpi-ruby animate-in">
                        <div class="kpi-header">
                            <span class="kpi-label">Faltantes</span>
                            <div class="kpi-icon-wrap"><i class="bi bi-exclamation-circle"></i></div>
                        </div>
                        <div class="kpi-value">{{ number_format(max(0, $pagosTotales - $cuentasRadicadas)) }}</div>
                        <div class="sparkline-container" id="sparkline-4"></div>
                    </div>
                    <div class="kpi-card kpi-indigo animate-in">
                        <div class="kpi-header">
                            <span class="kpi-label">Meta Total</span>
                            <div class="kpi-icon-wrap"><i class="bi bi-bullseye"></i></div>
                        </div>
                        <div class="kpi-value">{{ number_format($pagosTotales) }}</div>
                        <div class="sparkline-container" id="sparkline-5"></div>
                    </div>
                    <div class="kpi-card kpi-teal animate-in">
                        <div class="kpi-header">
                            <span class="kpi-label">Progreso Global</span>
                            <div class="kpi-icon-wrap"><i class="bi bi-activity"></i></div>
                        </div>
                        <div class="kpi-value">{{ number_format($avanceGlobal, 1) }}%</div>
                        <div class="sparkline-container" id="sparkline-6"></div>
                    </div>
                </div>

                {{-- CHARTS ROW 1 --}}
                <div class="row g-3 mb-3">
                    <div class="col-lg-4 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header"><h6>Estado de Cuentas</h6></div>
                            <div class="card-body">
                                <div id="donutChart"></div>
                                <div id="donutLegend" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header"><h6>Estado del Pipeline</h6></div>
                            <div class="card-body"><div id="gapChart"></div></div>
                        </div>
                    </div>
                </div>

                {{-- CHARTS ROW 2 --}}
                <div class="row g-3 mb-3">
                    <div class="col-lg-7 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h6>Tiempo Real por Etapa</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <select id="filterResponsableEtapa" class="form-select-sm" style="width:150px;">
                                        <option value="">Todos los usuarios</option>
                                        @foreach ($responsables as $resp)
                                            <option value="{{ $resp->id }}">{{ $resp->primer_nombre }} {{ $resp->primer_apellido }}</option>
                                        @endforeach
                                    </select>
                                    <select id="filterEtapa" class="form-select-sm" style="width:150px;">
                                        <option value="">Todas las etapas</option>
                                        @foreach($etapasDisponibles as $etapa) <option value="{{ $etapa }}">{{ $etapa }}</option> @endforeach
                                    </select>
                                    <select id="filterEstadoEtapa" class="form-select-sm" style="width:170px;">
                                        <option value="">Todos los estados</option>
                                        @foreach($todosLosEstados as $bloqueEstados)
                                            @foreach($bloqueEstados as $est)
                                                <option value="{{ $est->nombre }}" data-bloque="{{ $est->bloque->nombre ?? '' }}">{{ $est->nombre }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <button type="button" id="btnToggleView" class="btn btn-sm btn-outline-primary d-none" title="Volver al resumen de bloques">
                                        <i class="bi bi-arrow-left-circle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <div class="small text-muted" id="delayDrilldownLabel">Vista general por bloques</div>
                                    <span class="badge bg-sapphire-soft text-sapphire border-0 d-none" id="delayDrilldownBadge"></span>
                                </div>
                                <div class="row g-0 mb-3">
                                    <div class="col-4 text-center"><div class="small text-muted">Total</div><div id="kpi-general" class="fw-bold">--</div></div>
                                    <div class="col-4 text-center"><div class="small text-ruby">Crítica</div><div id="kpi-lenta" class="fw-bold small">--</div></div>
                                    <div class="col-4 text-center"><div class="small text-emerald">Rápida</div><div id="kpi-rapida" class="fw-bold small">--</div></div>
                                </div>
                                <div id="delayChart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h6>Actividad Reciente</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <select id="filterActividadResponsable" class="form-select-sm" style="width:120px;">
                                        <option value="">Responsable</option>
                                        @foreach ($responsables as $resp)
                                            <option value="{{ $resp->id }}">{{ $resp->primer_nombre }} {{ $resp->primer_apellido }}</option>
                                        @endforeach
                                    </select>
                                    <select id="filterActividadEstado" class="form-select-sm" style="width:120px;">
                                        <option value="">Estado</option>
                                        @foreach($todosLosEstados as $bloqueEstados)
                                            @foreach($bloqueEstados as $est)
                                                <option value="{{ $est->nombre }}">{{ $est->nombre }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div id="timelineChart" class="mb-2"></div>
                                <div class="timeline-vertical" id="timelineHtml">
                                    <div class="tl-empty">
                                        <i class="bi bi-activity"></i>
                                        Cargando actividad reciente...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TIME ANALYSIS --}}
                <div class="d-flex justify-content-between align-items-center mt-4 mb-3 animate-in">
                    <div class="section-title mb-0 mt-0">ANÁLISIS DE TIEMPOS POR ETAPA</div>
                </div>

                <div class="row mb-4 animate-in">
                    <div class="col-12">
                        <div id="bottleneckContainer">
                            @include('Analitica.componentes.alerta_bottleneck', ['bottleneck' => $chartData['demora_usuario_etapa']['bottleneck']])
                        </div>

                        <div class="card card-premium">
                            <div class="card-body p-0" id="timeTableContainer">
                                @include('Analitica.componentes.tabla_tiempos', ['datos' => $chartData['demora_usuario_etapa']])
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CONTRACT LIST --}}
                <div class="section-title animate-in">LISTADO DE CONTRATOS</div>
                <div class="card card-premium mb-5 animate-in">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="alertTable" class="table table-premium table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>N° Contrato</th>
                                        <th>Contratista</th>
                                        <th>Etapa Actual</th>
                                        <th>Estado</th>
                                        <th class="text-center">Meta</th>
                                        <th class="text-center">Radicadas</th>
                                        <th class="text-center">Pendientes</th>
                                        <th class="text-center">Avance</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    @include('Analitica.componentes.tabla_contratos', ['cuentas' => $cuentas])
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="mobileContractList" class="d-lg-none"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <script>
        window.chartData = @json($chartData);
        window.avanceGlobal = {{ $avanceGlobal }};
        window.responsables = @json($responsables);
    </script>
    @vite(['resources/views/Analitica/analitica.js'])
@endsection

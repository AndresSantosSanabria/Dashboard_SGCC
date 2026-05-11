@extends('layouts.app')

@section('title', 'Tablero Analítico BI - SGCC')

@push('styles')
    @vite(['resources/views/Analitica/analitica.css'])
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider/dist/nouislider.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('page-content')
    <div class="dashboard-wrapper">
        {{-- 📱 Mobile Header (Essential for analitica.js) --}}
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

            {{-- ═══ MOBILE HERO ═══ --}}
            <div id="mobile-analytics-hero" class="d-lg-none">
                <p class="mhero-label">VALOR TOTAL RP</p>
                <h3 class="mhero-amount">${{ number_format($montoTotal, 0, ',', '.') }}</h3>
                <div class="row g-2 mt-2">
                    <div class="col-4"><div class="small opacity-75">Total</div><div id="m-kpi-total" class="fw-bold">--</div></div>
                    <div class="col-4"><div class="small opacity-75">Lenta</div><div id="m-kpi-lenta" class="fw-bold">--</div><div id="m-kpi-lenta-sub" class="x-small"></div></div>
                    <div class="col-4"><div class="small opacity-75">Rápida</div><div id="m-kpi-rapida" class="fw-bold">--</div><div id="m-kpi-rapida-sub" class="x-small"></div></div>
                </div>
            </div>

            {{-- ═══ DESKTOP HEADER ═══ --}}
            <div class="d-none d-lg-flex justify-content-between align-items-end mb-5 header-content flex-wrap gap-4">
                <div>
                    <h2 class="animate-in">Tablero Analítico</h2>
                    <p class="header-subtitle animate-in">Inteligencia de Negocio — Gestión de Cuentas de Cobro</p>
                    <div class="header-date-selector animate-in mt-3" id="dateRangePicker">
                        <i class="bi bi-calendar3"></i>
                        <span id="dateDisplay">
                            @if (request('fecha_desde') && request('fecha_hasta'))
                                {{ \Carbon\Carbon::parse(request('fecha_desde'))->translatedFormat('d F, Y') }} - {{ \Carbon\Carbon::parse(request('fecha_hasta'))->translatedFormat('d F, Y') }}
                            @else
                                {{ \Carbon\Carbon::now()->translatedFormat('d \\d\\e F, Y') }}
                            @endif
                        </span>
                        <i class="bi bi-chevron-down ms-1" style="font-size: 0.7rem;"></i>
                    </div>
                </div>

                <div class="d-flex gap-3 animate-in">
                    <div class="hero-indicator-mini">
                        <div class="mini-label">Valor total RP</div>
                        <div class="mini-value">${{ number_format($montoTotal, 0, ',', '.') }}</div>
                    </div>
                    <button id="btnExportPDF" class="btn btn-glass px-4 py-3"><i class="bi bi-file-earmark-pdf me-2"></i>PDF</button>
                    <button id="btnExportExcel" class="btn btn-glass px-4 py-3 ms-2"><i class="bi bi-file-earmark-excel me-2"></i>Excel</button>
                </div>
            </div>

            {{-- ═══ FILTERS ═══ --}}
            <div id="filterSection" class="filter-bar mb-4 animate-in">
                <form action="{{ route('analitica') }}" method="GET" class="row g-3 align-items-end" id="filterForm">
                    <input type="hidden" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}">
                    <input type="hidden" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}">
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">N° Contrato</label>
                        <input type="text" name="contrato" class="form-control" placeholder="Ej: 119-2025" value="{{ request('contrato') }}">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">N° Cuenta</label>
                        <input type="text" name="numero_cuenta" class="form-control" placeholder="Ej: 1" value="{{ request('numero_cuenta') }}">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Supervisor</label>
                        <select name="supervisor" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($supervisores as $sup)
                                <option value="{{ $sup->id }}" {{ request('supervisor') == $sup->id ? 'selected' : '' }}>{{ $sup->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Responsable</label>
                        <select name="responsable" id="filterUsuario" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($responsables as $resp)
                                <option value="{{ $resp->id }}" {{ request('responsable') == $resp->id ? 'selected' : '' }}>{{ $resp->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            @foreach ($bloques as $bloque)
                                @foreach ($todosLosEstados[$bloque->codigo] ?? [] as $est)
                                    <option value="{{ $est->nombre }}" {{ request('estado') == $est->nombre ? 'selected' : '' }}>{{ $est->nombre }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="d-flex align-items-end gap-2">
                            <div class="flex-grow-1">
                                <label class="form-label">% Avance</label>
                                <div id="rangeSlider" class="mt-2"></div>
                                <input type="hidden" name="porcentaje_min" id="minVal" value="{{ request('porcentaje_min', 0) }}">
                                <input type="hidden" name="porcentaje_max" id="maxVal" value="{{ request('porcentaje_max', 100) }}">
                            </div>
                            <button type="submit" class="btn btn-filter"><i class="bi bi-funnel"></i></button>
                            <button type="button" id="btnReset" class="btn btn-reset"><i class="bi bi-arrow-counterclockwise"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <div id="captureArea" class="position-relative">
                @include('layouts.partials._premium_loader')

                {{-- ═══ KPIs ═══ --}}
                <div class="kpi-row mb-4">
                    <div class="kpi-card kpi-blue animate-in">
                        <div class="kpi-icon-wrap"><i class="bi bi-people-fill"></i></div>
                        <div class="kpi-value">{{ number_format($contratistasUnicos) }}</div>
                        <div class="kpi-label">Contratistas</div>
                    </div>
                    <div class="kpi-card kpi-amber animate-in">
                        <div class="kpi-icon-wrap"><i class="bi bi-clock-fill"></i></div>
                        <div class="kpi-value">{{ number_format($cuentasTramite) }}</div>
                        <div class="kpi-label">En proceso</div>
                    </div>
                    <div class="kpi-card kpi-green animate-in">
                        <div class="kpi-icon-wrap"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="kpi-value">{{ number_format($cuentasRadicadas) }}</div>
                        <div class="kpi-label">Finalizadas</div>
                    </div>
                    <div class="kpi-card kpi-red animate-in">
                        <div class="kpi-icon-wrap"><i class="bi bi-record-circle"></i></div>
                        <div class="kpi-value">{{ number_format(max(0, $pagosTotales - $cuentasRadicadas)) }}</div>
                        <div class="kpi-label">Faltantes</div>
                    </div>
                    <div class="kpi-card kpi-indigo animate-in">
                        <div class="kpi-icon-wrap"><i class="bi bi-clock"></i></div>
                        <div class="kpi-value">{{ number_format($pagosTotales) }}</div>
                        <div class="kpi-label">Meta total</div>
                    </div>
                    <div class="kpi-card kpi-teal animate-in">
                        <div class="kpi-icon-wrap"><i class="bi bi-activity"></i></div>
                        <div class="kpi-value">{{ number_format($avanceGlobal, 1) }}%</div>
                        <div class="kpi-label">Progreso global</div>
                    </div>
                </div>

                {{-- ═══ CHARTS ═══ --}}
                <div class="row g-3 mb-4">
                    <div class="col-lg-4 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header bg-transparent border-0"><h6>Estado de Cuentas</h6></div>
                            <div class="card-body"><div id="donutChart"></div></div>
                        </div>
                    </div>
                    <div class="col-lg-8 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header bg-transparent border-0"><h6>Estado del Pipeline</h6></div>
                            <div class="card-body"><div id="gapChart"></div></div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-lg-7 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header bg-transparent border-0 d-flex justify-content-between">
                                <h6>Tiempo Real por Etapa</h6>
                                <div class="d-flex gap-2">
                                    <select id="filterEtapa" class="form-select form-select-sm" style="width: 150px;">
                                        <option value="">Todas las etapas</option>
                                        @foreach($etapasDisponibles as $etapa) <option value="{{ $etapa }}">{{ $etapa }}</option> @endforeach
                                    </select>
                                    <button type="button" id="btnToggleView" class="btn btn-sm btn-outline-primary"><i class="bi bi-people"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 mb-3">
                                    <div class="col-4 text-center border-end"><div class="small text-muted">Total</div><div id="kpi-general" class="fw-bold">--</div></div>
                                    <div class="col-4 text-center border-end"><div class="small text-danger">Crítica</div><div id="kpi-lenta" class="fw-bold small">--</div></div>
                                    <div class="col-4 text-center"><div class="small text-success">Rápida</div><div id="kpi-rapida" class="fw-bold small">--</div></div>
                                </div>
                                <div id="delayChart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header bg-transparent border-0"><h6>Actividad Reciente</h6></div>
                            <div class="card-body"><div id="timelineChart"></div></div>
                        </div>
                    </div>
                </div>

                {{-- ═══ ANALISIS DETALLADO (PREMIUM INTEGRATION) ═══ --}}
                <div class="section-title animate-in mt-5">ANÁLISIS DE TIEMPOS POR ETAPA (NUEVO)</div>
                
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

                {{-- ═══ LISTADO DETALLADO ═══ --}}
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

                {{-- Mobile List (Hidden on desktop, needed by analitica.js) --}}
                <div id="mobileContractList" class="d-lg-none"></div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/nouislider/dist/nouislider.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    
    <script>
        window.chartData = @json($chartData);
        window.avanceGlobal = {{ $avanceGlobal }};
    </script>
    @vite(['resources/views/Analitica/analitica.js'])
@endsection

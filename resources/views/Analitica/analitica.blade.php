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
        <!-- Header Decor -->
        <div class="header-bg"></div>

        <div class="container-fluid py-4 contents-container">
            <!-- Dashboard Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 header-content flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1 animate-in">Tablero Analítico</h2>
                    <p class="header-subtitle mb-2 animate-in">Inteligencia de Negocio — Gestión de Cuentas de Cobro</p>
                    <div class="header-date-selector animate-in" id="dateRangePicker">
                        <i class="bi bi-calendar3"></i>
                        <span id="dateDisplay">
                            @if (request('fecha_desde') && request('fecha_hasta'))
                                {{ \Carbon\Carbon::parse(request('fecha_desde'))->translatedFormat('d F, Y') }} -
                                {{ \Carbon\Carbon::parse(request('fecha_hasta'))->translatedFormat('d F, Y') }}
                            @else
                                {{ \Carbon\Carbon::now()->translatedFormat('d \\d\\e F, Y') }}
                            @endif
                        </span>
                        <i class="bi bi-chevron-down ms-1" style="font-size: 0.7rem;"></i>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <div class="hero-indicator-mini animate-in">
                        <div class="mini-label">Valor total RP</div>
                        <div class="mini-value">${{ number_format($indicadorTotalUnico, 0, ',', '.') }}</div>
                    </div>
                    <button id="btnExportPDF" class="btn btn-glass h-100">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </button>
                </div>
            </div>

            <div id="captureArea" class="position-relative premium-loading-container">
                @include('layouts.partials._premium_loader')

                <!-- Header para el PDF (Oculto en web) -->
                <div id="pdfHeader" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3"
                        style="border-bottom: 2px solid #004884 !important;">
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ asset('assets/img/logo-gobernacion.png') }}" alt="Logo" style="height: 60px;">
                            <div>
                                <h3 style="margin:0; color:#004884; font-weight:800;">REPORTE ANALÍTICO BI</h3>
                                <p style="margin:0; font-size:12px; color:#666;">Sistema de Gestión de Cuentas de Cobro —
                                    SGCC</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-4">
                            <div id="pdfIndicators" class="d-flex gap-2"></div>
                            <div class="text-end">
                                <div class="mb-2">
                                    <p
                                        style="margin:0; font-size:9px; font-weight:700; color:#004884; letter-spacing: 0.5px; text-transform: uppercase;">
                                        GENERADO POR</p>
                                    <p style="margin:0; font-size:12px; color:#333; font-weight: 600;">
                                        {{ Auth::user()->nombre_completo }}</p>
                                </div>
                                <div>
                                    <p
                                        style="margin:0; font-size:9px; font-weight:700; color:#004884; letter-spacing: 0.5px; text-transform: uppercase;">
                                        FECHA DE GENERACIÓN</p>
                                    <p style="margin:0; font-size:11px; color:#666;">
                                        {{ \Carbon\Carbon::now()->translatedFormat('d F, Y h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== FILTERS ===== -->
                <div class="filter-bar mb-4 no-print animate-in">
                    <form action="{{ route('analitica') }}" method="GET" class="row g-3 align-items-end" id="filterForm">
                        <input type="hidden" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}">
                        <input type="hidden" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}">
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label">N° Contrato</label>
                            <input type="text" name="contrato" class="form-control" placeholder="Ej: 119-2025"
                                value="{{ request('contrato') }}">
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label">N° de Cuenta</label>
                            <input type="text" name="numero_cuenta" class="form-control" placeholder="Ej: 1"
                                value="{{ request('numero_cuenta') }}">
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label">Supervisor</label>
                            <select name="supervisor" class="form-select">
                                <option value="">Todos</option>
                                @foreach ($supervisores as $sup)
                                    <option value="{{ $sup->id }}"
                                        {{ request('supervisor') == $sup->id ? 'selected' : '' }}>
                                        {{ $sup->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label">Responsable</label>
                            <select name="responsable" class="form-select">
                                <option value="">Todos</option>
                                @foreach ($responsables as $resp)
                                    <option value="{{ $resp->id }}"
                                        {{ request('responsable') == $resp->id ? 'selected' : '' }}>
                                        {{ $resp->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <label class="form-label">% Avance</label>
                            <div class="px-1">
                                <div id="rangeSlider" class="mt-2"></div>
                                <input type="hidden" name="porcentaje_min" id="minVal"
                                    value="{{ request('porcentaje_min', 0) }}">
                                <input type="hidden" name="porcentaje_max" id="maxVal"
                                    value="{{ request('porcentaje_max', 100) }}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-filter flex-grow-1">
                                    <i class="bi bi-funnel me-1"></i>Filtrar
                                </button>
                                <button type="button" id="btnReset" class="btn btn-reset" title="Limpiar filtros">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ===== KPI CARDS ===== -->
                <div class="kpi-row mb-4">
                    <div class="kpi-card kpi-blue animate-in">
                        <div class="kpi-accent"></div>
                        <div class="kpi-icon-wrap"><i class="bi bi-people-fill"></i></div>
                        <div class="kpi-value">{{ number_format($contratistasUnicos) }}</div>
                        <div class="kpi-label">Contratistas</div>
                        <div class="kpi-trend text-govco-blue">
                            <i class="bi bi-receipt-cutoff"></i> {{ number_format($totalCuentas) }} cuentas
                        </div>
                    </div>
                    <div class="kpi-card kpi-amber animate-in">
                        <div class="kpi-accent"></div>
                        <div class="kpi-icon-wrap"><i class="bi bi-hourglass-split"></i></div>
                        <div class="kpi-value">{{ number_format($cuentasTramite) }}</div>
                        <div class="kpi-label">En Proceso</div>
                        <div class="kpi-trend" style="color: #b77e00;">
                            <i class="bi bi-arrow-right-circle"></i> Trámite activo
                        </div>
                    </div>
                    <div class="kpi-card kpi-green animate-in">
                        <div class="kpi-accent"></div>
                        <div class="kpi-icon-wrap"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="kpi-value">{{ number_format($cuentasRadicadas) }}</div>
                        <div class="kpi-label">Finalizadas</div>
                        <div class="kpi-trend text-govco-green">
                            <i class="bi bi-info-circle"></i> Pagos radicados
                        </div>
                    </div>
                    <div class="kpi-card kpi-red animate-in">
                        <div class="kpi-accent"></div>
                        <div class="kpi-icon-wrap"><i class="bi bi-exclamation-circle-fill"></i></div>
                        <div class="kpi-value">{{ number_format(max(0, $pagosTotales - $cuentasRadicadas)) }}</div>
                        <div class="kpi-label">Faltantes por radicar</div>
                        <div class="kpi-trend text-govco-red">
                            <i class="bi bi-info-circle"></i> Brecha de ejecución
                        </div>
                    </div>
                    <div class="kpi-card kpi-indigo animate-in">
                        <div class="kpi-accent"></div>
                        <div class="kpi-icon-wrap"><i class="bi bi-bullseye"></i></div>
                        <div class="kpi-value">{{ number_format($pagosTotales) }}</div>
                        <div class="kpi-label">Meta Total</div>
                    </div>
                    <div class="kpi-card kpi-teal animate-in">
                        <div class="kpi-accent"></div>
                        <div class="kpi-icon-wrap"><i class="bi bi-speedometer"></i></div>
                        <div class="kpi-value">{{ number_format($avanceGlobal, 1) }}%</div>
                        <div class="kpi-label">Progreso Global</div>
                        <div class="mt-2">
                            <div class="progress-slim">
                                <div class="progress-bar" style="width: {{ min($avanceGlobal, 100) }}%"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ===== ROW 1: Distribución + Cumplimiento ===== -->
                <div class="section-title animate-in">Resumen General</div>
                <div class="row g-3 mb-4">
                    <div class="col-lg-4 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header bg-transparent border-0">
                                <h6>
                                    <span class="chart-icon"
                                        style="background:rgba(10,135,84,.08); color:var(--govco-green);">
                                        <i class="bi bi-pie-chart-fill"></i>
                                    </span>
                                    Estado de Cuentas
                                </h6>
                            </div>
                            <div class="card-body d-flex align-items-center">
                                <div id="donutChart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header bg-transparent border-0">
                                <h6>
                                    <span class="chart-icon" style="background:rgba(79,70,229,.08); color:#4f46e5;">
                                        <i class="bi bi-funnel"></i>
                                    </span>
                                    Estado del Pipeline (Carga por Etapa)
                                </h6>
                                <span class="chart-badge"
                                    style="background:rgba(0,72,132,.06); color:var(--govco-blue);">VOLUMEN DE
                                    CONTRATOS</span>
                            </div>
                            <div class="card-body">
                                <div id="gapChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== ROW 2: Demora + Actividad ===== -->
                <div class="section-title animate-in">Análisis de Tiempos y Operación</div>
                <div class="row g-3 mb-4">
                    <div class="col-lg-6 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header bg-transparent border-0">
                                <h6>
                                    <span class="chart-icon" style="background:rgba(245,158,11,.1); color:#d97706;">
                                        <i class="bi bi-clock-history"></i>
                                    </span>
                                    Demora Promedio por Etapa
                                    <i class="bi bi-info-circle text-muted ms-1" style="font-size:.75rem"
                                        data-bs-toggle="tooltip"
                                        title="Promedio de horas que permanecen los contratos en cada bloque antes de avanzar."></i>
                                </h6>
                                <span class="chart-badge" style="background:rgba(245,158,11,.06); color:#d97706;">TIEMPO
                                    EN HORAS Y MINUTOS</span>
                            </div>
                            <div class="card-body">
                                <div id="delayChart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 animate-in">
                        <div class="card h-100 card-premium">
                            <div class="card-header bg-transparent border-0">
                                <h6>
                                    <span class="chart-icon" style="background:rgba(79,70,229,.08); color:#4f46e5;">
                                        <i class="bi bi-graph-up-arrow"></i>
                                    </span>
                                    Actividad Reciente (30 días)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="timelineChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== ALERT TABLE ===== -->
                <div class="section-title animate-in">Detalle por Contrato</div>
                <div class="card card-premium mb-4 animate-in">
                    <div class="card-header bg-transparent border-0">
                        <h6>
                            <span class="chart-icon" style="background:rgba(0,72,132,.08); color:var(--govco-blue);">
                                <i class="bi bi-table"></i>
                            </span>
                            Contratos — Avance y Pendientes
                        </h6>
                        @php $alertCount = $cuentas->filter(fn($c) => ($c->numero_pagos_totales ?? 0) - ($c->radicadas_bi ?? 0) > 0)->count(); @endphp
                        @if ($alertCount > 0)
                            <span class="chart-badge badge-danger-soft">{{ $alertCount }} con brecha</span>
                        @else
                            <span class="chart-badge badge-success-soft">Todo al día</span>
                        @endif
                        <button id="btnResetColumns" class="btn btn-sm btn-outline-primary ms-auto btn-reset-columns"
                            style="display: none;" onclick="resetColumns()">
                            <i class="bi bi-layout-three-columns me-1"></i> Mostrar Todo
                        </button>
                    </div>
                    <div class="card-body px-3 pb-3 pt-0">
                        <div class="table-responsive">
                            <table id="alertTable" class="table table-premium table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        @foreach (['N° Contrato', 'Contratista', 'Etapa Actual', 'Estado', 'Meta', 'Radicadas', 'Pendientes', 'Avance'] as $h)
                                            <th
                                                class="{{ in_array($h, ['Meta', 'Radicadas', 'Pendientes']) ? 'text-center' : '' }}">
                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                    <span>{{ $h }}</span>
                                                    <button type="button" class="btn btn-sm btn-link p-0 toggle-col-btn"
                                                        title="Minimizar">
                                                        <i class="bi bi-dash-lg"></i>
                                                    </button>
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    @include('Analitica.componentes.tabla_contratos', [
                                        'cuentas' => $cuentas,
                                    ])
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.chartData = @json($chartData);
        window.avanceGlobal = {{ $avanceGlobal }};
    </script>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/nouislider/dist/nouislider.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    @vite(['resources/views/Analitica/analitica.js'])
@endpush

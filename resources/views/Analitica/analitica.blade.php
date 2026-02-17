@extends('layouts.app')

@section('title', 'Tablero Analítico BI - SGCC')

@push('styles')
    @vite(['resources/views/Analitica/analitica.css'])
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider/dist/nouislider.min.css">
@endpush

@section('page-content')
<div class="dashboard-wrapper">
    <!-- Header Decor -->
    <div class="header-bg"></div>

    <div class="container-fluid py-4 contents-container">
        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-start mb-4 header-content flex-wrap gap-3">
            <div>
                <h2 class="fw-bold mb-1">Tablero Analítico</h2>
                <p class="header-subtitle mb-2">Inteligencia de Negocio — Gestión de Cuentas de Cobro</p>
                <span class="header-date-badge">
                    <i class="bi bi-calendar3"></i> {{ \Carbon\Carbon::now()->translatedFormat('d \\d\\e F, Y') }}
                </span>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button id="btnExportPDF" class="btn btn-glass">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
                </button>
            </div>
        </div>

        <div id="captureArea">
            <!-- ===== FILTERS ===== -->
            <div class="filter-bar mb-4 no-print">
                <form action="{{ route('analitica') }}" method="GET" class="row g-3 align-items-end" id="filterForm">
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label">N° Contrato</label>
                        <input type="text" name="contrato" class="form-control" placeholder="Ej: 119-2025" value="{{ request('contrato') }}">
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label">N° de Cuenta</label>
                        <input type="text" name="numero_cuenta" class="form-control" placeholder="Ej: 1" value="{{ request('numero_cuenta') }}">
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label">Supervisor</label>
                        <select name="supervisor" class="form-select">
                            <option value="">Todos</option>
                            @foreach($supervisores as $sup)
                                <option value="{{ $sup->id }}" {{ request('supervisor') == $sup->id ? 'selected' : '' }}>{{ $sup->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label">Responsable</label>
                        <select name="responsable" class="form-select">
                            <option value="">Todos</option>
                            @foreach($responsables as $resp)
                                <option value="{{ $resp->id }}" {{ request('responsable') == $resp->id ? 'selected' : '' }}>{{ $resp->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <label class="form-label">% Avance</label>
                        <div class="px-1">
                            <div id="rangeSlider" class="mt-2"></div>
                            <input type="hidden" name="porcentaje_min" id="minVal" value="{{ request('porcentaje_min', 0) }}">
                            <input type="hidden" name="porcentaje_max" id="maxVal" value="{{ request('porcentaje_max', 100) }}">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-filter flex-grow-1">
                                <i class="bi bi-funnel me-1"></i>Filtrar
                            </button>
                            <a href="{{ route('analitica') }}" class="btn btn-reset" title="Limpiar filtros">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
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
                        <i class="bi bi-receipt-cutoff"></i> {{ number_format($totalCuentas) }} cuentas activas
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
                    <div class="kpi-value">{{ number_format($cuentasFinalizadas) }}</div>
                    <div class="kpi-label">Finalizadas</div>
                    <div class="kpi-trend text-govco-green">
                        <i class="bi bi-bank2"></i> {{ number_format($cuentasRadicadas) }} radicadas
                    </div>
                </div>
                <div class="kpi-card kpi-indigo animate-in">
                    <div class="kpi-accent"></div>
                    <div class="kpi-icon-wrap"><i class="bi bi-bullseye"></i></div>
                    <div class="kpi-value">{{ number_format($pagosTotales) }}</div>
                    <div class="kpi-label">Meta Total</div>
                    <div class="kpi-trend" style="color: #4f46e5;">
                        <i class="bi bi-cash-stack"></i> ${{ number_format($montoTotal / 1000000, 1) }}M gestionados
                    </div>
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

            <!-- ===== ROW 1: Gauge + Gap + Donut ===== -->
            <div class="section-title">Visión General</div>
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-lg-4 animate-in">
                    <div class="card h-100 card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(0,72,132,.08); color:var(--govco-blue);">
                                    <i class="bi bi-speedometer2"></i>
                                </span>
                                Eficiencia Global
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="gaugeChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-8 animate-in">
                    <div class="card h-100 card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(79,70,229,.08); color:#4f46e5;">
                                    <i class="bi bi-bar-chart-steps"></i>
                                </span>
                                Cumplimiento de Metas
                            </h6>
                            <span class="chart-badge" style="background:rgba(0,72,132,.06); color:var(--govco-blue);">META vs REAL</span>
                        </div>
                        <div class="card-body">
                            <div id="gapChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 animate-in">
                    <div class="card h-100 card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(10,135,84,.08); color:var(--govco-green);">
                                    <i class="bi bi-pie-chart-fill"></i>
                                </span>
                                Distribución
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="donutChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== ROW 2: Demora + Estados ===== -->
            <div class="section-title">Análisis Operativo</div>
            <div class="row g-3 mb-4">
                <div class="col-lg-6 animate-in">
                    <div class="card card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(245,158,11,.1); color:#d97706;">
                                    <i class="bi bi-clock-history"></i>
                                </span>
                                Demora por Etapa
                                <i class="bi bi-info-circle text-muted ms-1" style="font-size:.75rem" data-bs-toggle="tooltip" title="Tiempo promedio en horas que permanecen los contratos en cada fase del proceso."></i>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="delayChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 animate-in">
                    <div class="card card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(0,72,132,.08); color:var(--govco-blue);">
                                    <i class="bi bi-diagram-3-fill"></i>
                                </span>
                                Estados más Frecuentes
                                <i class="bi bi-info-circle text-muted ms-1" style="font-size:.75rem" data-bs-toggle="tooltip" title="Muestra en qué estados se concentran más cuentas actualmente."></i>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="statesChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== ROW 3: Heatmap + Diferencia ===== -->
            <div class="section-title">Rendimiento y Brechas</div>
            <div class="row g-3 mb-4">
                <div class="col-lg-6 animate-in">
                    <div class="card card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(13,148,136,.08); color:#0d9488;">
                                    <i class="bi bi-person-lines-fill"></i>
                                </span>
                                Carga por Responsable
                                <i class="bi bi-info-circle text-muted ms-1" style="font-size:.75rem" data-bs-toggle="tooltip" title="Cantidad de cuentas asignadas por responsable, incluyendo devueltas."></i>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="heatmapChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 animate-in">
                    <div class="card card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(214,13,13,.08); color:var(--govco-red);">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </span>
                                Contratos con Mayor Brecha
                                <i class="bi bi-info-circle text-muted ms-1" style="font-size:.75rem" data-bs-toggle="tooltip" title="Diferencia entre pagos pactados y cuentas radicadas. Mayor barra = mayor rezago."></i>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="barChartDiferencia"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== ROW 4: Timeline + SLA ===== -->
            <div class="section-title">Tendencias y Cumplimiento</div>
            <div class="row g-3 mb-4">
                <div class="col-lg-6 animate-in">
                    <div class="card card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(79,70,229,.08); color:#4f46e5;">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </span>
                                Actividad (Últimos 30 días)
                            </h6>
                            <span class="chart-badge" style="background:rgba(79,70,229,.06); color:#4f46e5;">TENDENCIA</span>
                        </div>
                        <div class="card-body">
                            <div id="timelineChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 animate-in">
                    <div class="card card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(10,135,84,.08); color:var(--govco-green);">
                                    <i class="bi bi-shield-check"></i>
                                </span>
                                Cumplimiento SLA
                                <i class="bi bi-info-circle text-muted ms-1" style="font-size:.75rem" data-bs-toggle="tooltip" title="Porcentaje de transiciones que se completaron dentro del SLA definido por bloque."></i>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="slaChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== ROW 5: Supervisor + Distribución Bloques ===== -->
            <div class="section-title">Gestión por Equipo</div>
            <div class="row g-3 mb-4">
                <div class="col-lg-7 animate-in">
                    <div class="card card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(0,72,132,.08); color:var(--govco-blue);">
                                    <i class="bi bi-person-workspace"></i>
                                </span>
                                Rendimiento por Supervisor
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="supervisorChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 animate-in">
                    <div class="card card-premium">
                        <div class="card-header bg-transparent border-0">
                            <h6>
                                <span class="chart-icon" style="background:rgba(245,158,11,.1); color:#d97706;">
                                    <i class="bi bi-layers-fill"></i>
                                </span>
                                Distribución por Bloque
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="bloqueChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== ALERT TABLE ===== -->
            <div class="section-title">Alertas del Sistema</div>
            <div class="card card-premium mb-4 animate-in">
                <div class="card-header bg-transparent border-0">
                    <h6>
                        <span class="chart-icon" style="background:rgba(214,13,13,.08); color:var(--govco-red);">
                            <i class="bi bi-bell-fill"></i>
                        </span>
                        Contratos con Brecha Pendiente
                    </h6>
                    <span class="chart-badge badge-danger-soft">{{ $cuentas->filter(fn($c) => ($c->numero_pagos_totales ?? 0) - ($c->radicadas_bi ?? 0) > 0)->count() }} alertas</span>
                </div>
                <div class="card-body px-3 pb-3 pt-0">
                    <div class="table-responsive">
                        <table id="alertTable" class="table table-premium table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>N° Contrato</th>
                                    <th>Contratista</th>
                                    <th>Bloque</th>
                                    <th>Estado</th>
                                    <th class="text-center">Meta</th>
                                    <th class="text-center">Radicadas</th>
                                    <th class="text-center">Diferencia</th>
                                    <th>Avance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cuentas as $c)
                                    @php $diff = ($c->numero_pagos_totales ?? 0) - ($c->radicadas_bi ?? 0); @endphp
                                    <tr class="{{ $diff > 0 ? 'table-alert' : '' }}">
                                        <td class="fw-bold text-govco-blue">{{ $c->contrato->numero_contrato ?? 'N/A' }}</td>
                                        <td><small>{{ $c->contrato->contratista->nombre_completo ?? 'N/A' }}</small></td>
                                        <td><small class="badge" style="background:rgba(0,72,132,.08); color:var(--govco-blue); font-weight:600;">{{ $c->bloqueActual->nombre ?? 'N/A' }}</small></td>
                                        <td><small>{{ $c->estadoActual->nombre ?? 'N/A' }}</small></td>
                                        <td class="text-center fw-semibold">{{ $c->numero_pagos_totales ?? 0 }}</td>
                                        <td class="text-center fw-semibold">{{ $c->radicadas_bi ?? 0 }}</td>
                                        <td class="text-center">
                                            @if($diff > 0)
                                                <span class="badge badge-danger-soft">-{{ $diff }}</span>
                                            @else
                                                <span class="badge badge-success-soft"><i class="bi bi-check2"></i> OK</span>
                                            @endif
                                        </td>
                                        <td style="min-width: 120px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress-slim flex-grow-1">
                                                    <div class="progress-bar" style="width: {{ min($c->avance_bi, 100) }}%"></div>
                                                </div>
                                                <small class="text-muted fw-semibold" style="font-size:.72rem;">{{ number_format($c->avance_bi, 1) }}%</small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
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

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    @vite(['resources/views/Analitica/analitica.js'])
@endpush

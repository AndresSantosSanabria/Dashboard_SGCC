@extends('layouts.sidebar')

@section('title', 'Workflow')

@section('page-content')
    <style>
        :root {
            --morado: #6f42c1;
            --indigo: #6610f2;
            --verde: #28a745;
            --naranja: #fd7e14;
            --rosa: #e83e8c;
            --cian: #17a2b8;
            --rojo-alerta: #dc3545;
            --amarillo-alerta: #ffc107;
            --verde-alerta: #28a745;
        }

        .workflow-container {
            padding: 20px 0;
        }

        .workflow-block {
            margin-bottom: 40px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .block-header {
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            margin-bottom: 20px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .block-morado {
            background-color: var(--morado);
        }

        .block-indigo {
            background-color: var(--indigo);
        }

        .block-verde {
            background-color: var(--verde);
        }

        .block-naranja {
            background-color: var(--naranja);
        }

        .block-rosa {
            background-color: var(--rosa);
        }

        .block-cian {
            background-color: var(--cian);
        }

        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
            transition: all 0.3s ease;
        }

        .workflow-block.collapsed {
            padding-bottom: 10px;
        }

        .workflow-block.collapsed .kanban-board {
            display: none;
        }

        .btn-collapse {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            border-radius: 4px;
            padding: 2px 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-collapse:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-collapse i {
            transition: transform 0.3s;
        }

        .workflow-block.collapsed .btn-collapse i {
            transform: rotate(-180deg);
        }

        .kanban-column {
            flex: 1;
            min-width: 280px;
            background: #eaeff2;
            border-radius: 8px;
            padding: 15px;
        }

        .column-title {
            font-size: 0.9rem;
            font-weight: bold;
            text-transform: uppercase;
            color: #495057;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
        }

        .account-card {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            border-left: 5px solid transparent;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
        }

        .account-card:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .alert-rojo {
            border-left-color: var(--rojo-alerta);
        }

        .alert-amarillo {
            border-left-color: var(--amarillo-alerta);
        }

        .alert-verde {
            border-left-color: var(--verde-alerta);
        }

        .card-id {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: bold;
        }

        .card-contractor {
            font-size: 0.85rem;
            font-weight: 600;
            margin: 5px 0;
        }

        .card-footer-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: #adb5bd;
            margin-top: 10px;
        }

        .timer-badge {
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
        }

        /* Filter Bar Styles */
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #8898aa;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }

        .filter-input-group {
            position: relative;
        }

        .filter-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }

        .filter-control {
            padding-left: 35px !important;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .filter-control:focus {
            border-color: #5e72e4;
            box-shadow: 0 0 0 2px rgba(94, 114, 228, 0.1);
        }

        .modal-xl {
            max-width: 1000px;
        }

        /* Modal Header with Status Buttons */
        .modal-header-custom {
            background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);
            padding: 20px 30px;
            border-radius: 8px 8px 0 0;
            color: white;
        }

        .modal-title-custom {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        /* Status Change Buttons */
        .status-buttons-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-estado {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .btn-estado:hover:not(.disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-estado.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-aprobado {
            background-color: #2dce89;
        }

        .btn-proceso {
            background-color: #fb6340;
        }

        .btn-devuelto {
            background-color: #f5365c;
        }

        /* Info Cards Section */
        .info-cards-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .info-card {
            background: #f7fafc;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #5e72e4;
        }

        .info-card-icon {
            font-size: 1.5rem;
            color: #5e72e4;
            margin-bottom: 10px;
        }

        .info-card-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #8898aa;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .info-card-content {
            font-size: 0.875rem;
            color: #32325d;
            line-height: 1.6;
        }

        .info-card-content strong {
            color: #172b4d;
            font-weight: 600;
        }

        /* Timeline Section */
        .timeline-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #32325d;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .timeline-container {
            position: relative;
            padding: 10px 0;
        }

        .timeline-item {
            display: flex;
            position: relative;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-marker-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-right: 20px;
            min-width: 50px;
        }

        .timeline-marker {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            z-index: 2;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .timeline-line {
            position: absolute;
            top: 50px;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: calc(100% + 20px);
            background: #e9ecef;
        }

        .timeline-item:last-child .timeline-line {
            display: none;
        }

        .timeline-content {
            flex: 1;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .timeline-title {
            font-weight: 700;
            font-size: 1rem;
            color: #172b4d;
        }

        .timeline-date {
            font-size: 0.75rem;
            color: #8898aa;
        }

        .timeline-transition {
            font-size: 0.875rem;
            color: #525f7f;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeline-meta {
            display: flex;
            gap: 20px;
            font-size: 0.8125rem;
            color: #8898aa;
            margin-top: 10px;
        }

        .timeline-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .timeline-comment {
            margin-top: 12px;
            padding: 12px;
            background: #f7fafc;
            border-left: 3px solid #5e72e4;
            border-radius: 4px;
        }

        .timeline-comment-text {
            font-size: 0.875rem;
            color: #525f7f;
            line-height: 1.5;
        }

        .bg-success-timeline {
            background-color: #2dce89;
        }

        .bg-warning-timeline {
            background-color: #fb6340;
        }

        .bg-danger-timeline {
            background-color: #f5365c;
        }

        .bg-info-timeline {
            background-color: #11cdef;
        }

        .bg-secondary-timeline {
            background-color: #8898aa;
        }
    </style>

    <div class="container-fluid workflow-container">
        <h1 class="mb-4 text-center fw-bold">Gestión de Flujo de Trabajo (Kanban)</h1>

        <!-- Filtros -->
        <form action="{{ route('workflow') }}" method="GET" class="filter-bar">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="filter-label">Supervisor</label>
                    <div class="filter-input-group">
                        <i class="fas fa-user-tie filter-icon"></i>
                        <select name="supervisor_id" class="form-select filter-control">
                            <option value="">Todos los supervisores</option>
                            @foreach($supervisores as $sup)
                                <option value="{{ $sup->id }}" {{ request('supervisor_id') == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Contratista</label>
                    <div class="filter-input-group">
                        <i class="fas fa-search filter-icon"></i>
                        <input type="text" name="contratista" class="form-control filter-control" 
                               placeholder="Nombre o NIT..." value="{{ request('contratista') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Estado Específico</label>
                    <div class="filter-input-group">
                        <i class="fas fa-tag filter-icon"></i>
                        <select name="estado_nombre" class="form-select filter-control">
                            <option value="">Todos los estados</option>
                            @foreach($estados as $est)
                                <option value="{{ $est->nombre }}" {{ request('estado_nombre') == $est->nombre ? 'selected' : '' }}>
                                    {{ $est->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary shadow-sm w-100" style="border-radius: 8px;">
                        <i class="fas fa-filter me-2"></i>Filtrar
                    </button>
                    <a href="{{ route('workflow') }}" class="btn btn-outline-secondary w-100" style="border-radius: 8px;">
                        <i class="fas fa-undo me-2"></i>Limpiar
                    </a>
                </div>
            </div>
        </form>

        @foreach ($workflow as $key => $block)
            <div class="workflow-block">
                <div class="block-header block-{{ $block['color'] }}">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-layer-group me-2"></i>
                        <span>{{ $block['nombre'] }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark shadow-sm">{{ count(collect($block['columnas'])->pluck('cuentas')->flatten(1)) }} Cuentas</span>
                        <button class="btn-collapse" onclick="toggleBlockVisibility(this, '{{ $key }}')">
                            <i class="fas fa-chevron-up"></i>
                        </button>
                    </div>
                </div>
                <div class="kanban-board">
                    @foreach ($block['columnas'] as $estadoId => $columna)
                        <div class="kanban-column">
                            <div class="column-title">
                                <span class="text-capitalize">{{ $columna['nombre'] }}</span>
                                <span class="badge rounded-pill bg-white text-dark shadow-sm">{{ count($columna['cuentas']) }}</span>
                            </div>
                            <div class="column-content">
                                @forelse($columna['cuentas'] as $cuenta)
                                    @php
                                        $alertClass = 'alert-amarillo';
                                        if ($columna['tipo'] === 'DEVUELTO') {
                                            $alertClass = 'alert-rojo';
                                        }
                                        if ($columna['tipo'] === 'APROBADO' || $columna['tipo'] === 'FINAL') {
                                            $alertClass = 'alert-verde';
                                        }
                                        $estadoBloqueActual = $cuenta->estadosBloques->where('bloque_id', $cuenta->bloque_actual_id)->first();
                                        $fechaIngreso = $estadoBloqueActual?->fecha_ultima_actualizacion;
                                    @endphp
                                    <div class="account-card {{ $alertClass }}" data-bs-toggle="modal"
                                        data-bs-target="#modalCuenta{{ $cuenta->id }}">
                                        <div class="card-id">CONTRATO: {{ $cuenta->contrato?->numero_contrato ?? 'N/A' }}
                                        </div>
                                        <div class="card-contractor text-truncate"
                                            title="{{ $cuenta->contrato?->contratista?->nombre_completo }}">
                                            {{ Str::limit($cuenta->contrato?->contratista?->nombre_completo ?? 'N/A', 35) }}
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="small text-muted" style="font-size: 0.75rem;">Cuenta
                                                #{{ $cuenta->numero_cuenta }}</div>
                                            <span class="badge border text-dark bg-light" style="font-size: 0.65rem; padding: 2px 5px;">
                                                {{ $cuenta->estadoActual?->nombre }}
                                            </span>
                                        </div>
                                        <div class="card-footer-info"><span><i
                                                    class="far fa-calendar-alt"></i>{{ $cuenta->created_at->format('d/m/Y') }}</span><span
                                                class="timer-badge"
                                                data-start="{{ $fechaIngreso ? $fechaIngreso->toIso8601String() : '' }}"><i
                                                    class="far fa-clock"></i><span
                                                    class="elapsed-time">{{ $fechaIngreso ? $fechaIngreso->diffForHumans(null, true) : 'N/A' }}</span></span>
                                        </div>
                                    </div>
                                    <div class="modal fade" id="modalCuenta{{ $cuenta->id }}" tabindex="-1"
                                            aria-hidden="true">
                                            <div
                                                class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                                                <div class="modal-content" style="border: none; border-radius: 12px;">
                                                        <div class="modal-header-custom">
                                                            <div class="modal-title-custom">Cambiar Estado de la Cuenta
                                                            </div>
                                                            <div class="status-buttons-row"
                                                                id="statusButtons{{ $cuenta->id }}">
                                                                <div class="spinner-border spinner-border-sm text-light"
                                                                    role="status"><span
                                                                        class="visually-hidden">Cargando...</span></div>
                                                            </div><button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal" aria-label="Close"
                                                                style="position: absolute; top: 20px; right: 20px;"></button>
                                                        </div>
                                                        <div class="modal-body" style="padding: 30px;">
                                                                <div class="info-cards-row">
                                                                        <div class="info-card">
                                                                            <div class="info-card-icon"><i
                                                                                    class="fas fa-file-contract"></i></div>
                                                                            <div class="info-card-title">Detalles del
                                                                                Contrato</div>
                                                                            <div class="info-card-content">
                                                                                <div><strong>Contrato
                                                                                        #</strong>{{ $cuenta->contrato?->numero_contrato ?? 'N/A' }}
                                                                                </div>
                                                                                <div>
                                                                                    <strong>Cliente:</strong>{{ Str::limit($cuenta->contrato?->contratista?->nombre_completo ?? 'N/A', 30) }}
                                                                                </div>
                                                                                <div>
                                                                                    <strong>Valor:</strong>${{ number_format($cuenta->valor_cobro, 2) }}
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                            <div class="info-card">
                                                                                <div class="info-card-icon"><i
                                                                                        class="fas fa-tasks"></i></div>
                                                                                <div class="info-card-title">Estado Actual
                                                                                </div>
                                                                                <div class="info-card-content">
                                                                                    <div><strong>Estado:</strong><span
                                                                                            id="estadoActual{{ $cuenta->id }}">{{ $cuenta->estadoActual?->nombre ?? 'N/A' }}</span>
                                                                                    </div>
                                                                                    <div>
                                                                                        <strong>Fecha:</strong>{{ $cuenta->estadosBloques->where('bloque_id', $cuenta->bloque_actual_id)->first()?->fecha_ingreso_bloque?->format('d/m/Y') ?? 'N/A' }}
                                                                                    </div>
                                                                                    <div>
                                                                                        <strong>Responsable:</strong>{{ $cuenta->responsableActual?->primer_nombre ?? 'N/A' }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="info-card">
                                                                                    <div class="info-card-icon"><i
                                                                                            class="fas fa-money-check-alt"></i>
                                                                                    </div>
                                                                                    <div class="info-card-title">Tesorería
                                                                                    </div>
                                                                                    <div class="info-card-content">
                                                                                        <div><strong>Factura
                                                                                                Pendiente</strong></div>
                                                                                        <div><strong>Monto
                                                                                                Aprobado:</strong>${{ number_format($cuenta->valor_cobro, 2) }}
                                                                                        </div>
                                                                                        <div><strong>Última
                                                                                                Actividad:</strong>{{ $cuenta->updated_at->diffForHumans() }}
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                </div>
                                                                    <div class="timeline-section-title"><i
                                                                            class="fas fa-history me-2"></i>Línea de Tiempo
                                                                    </div>
                                                                    <div class="timeline-container"
                                                                        id="timeline{{ $cuenta->id }}">
                                                                        @foreach ($cuenta->historialWorkflow->sortByDesc('fecha_transicion') as $index => $hist)
                                                                            @php
                                                                                $tipoDestino =
                                                                                    $hist->estadoDestino?->tipo ??
                                                                                    'INICIAL';
                                                                                $colorClass = match ($tipoDestino) {
                                                                                    'APROBADO',
                                                                                    'FINAL'
                                                                                        => 'bg-success-timeline',
                                                                                    'DEVUELTO' => 'bg-danger-timeline',
                                                                                    'EN_PROCESO'
                                                                                        => 'bg-warning-timeline',
                                                                                    'INICIAL' => 'bg-info-timeline',
                                                                                    default => 'bg-secondary-timeline',
                                                                                };
                                                                                $icon = match ($tipoDestino) {
                                                                                    'APROBADO', 'FINAL' => 'fa-check',
                                                                                    'DEVUELTO' => 'fa-times',
                                                                                    'EN_PROCESO' => 'fa-sync',
                                                                                    'INICIAL' => 'fa-play',
                                                                                    default => 'fa-circle',
                                                                                };
                                                                            @endphp
                                                                            <div class="timeline-item">
                                                                                <div class="timeline-marker-wrapper">
                                                                                    <div
                                                                                        class="timeline-marker {{ $colorClass }}">
                                                                                        <i
                                                                                            class="fas {{ $icon }}"></i>
                                                                                    </div>
                                                                                    @if (!$loop->last)
                                                                                        <div class="timeline-line"></div>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="timeline-content">
                                                                                    <div class="timeline-header">
                                                                                        <div class="timeline-title">
                                                                                            {{ $hist->estadoDestino?->nombre }}
                                                                                        </div>
                                                                                        <div class="timeline-date">Fecha:
                                                                                            {{ $hist->fecha_transicion->format('d/m/Y - H:i A') }}
                                                                                        </div>
                                                                                    </div>
                                                                                    @if ($hist->estadoOrigen)
                                                                                        <div class="timeline-transition">
                                                                                            <span>From:
                                                                                                {{ $hist->estadoOrigen->nombre }}</span><i
                                                                                                class="fas fa-arrow-right"></i><span>To:
                                                                                                {{ $hist->estadoDestino->nombre }}</span>
                                                                                        </div>
                                                                                    @endif
                                                                                    <div class="timeline-meta">
                                                                                        <div class="timeline-meta-item"><img
                                                                                                src="https://ui-avatars.com/api/?name={{ urlencode($hist->usuarioAccion?->primer_nombre ?? 'S') }}&size=24&background=random"
                                                                                                alt="avatar"
                                                                                                style="width: 24px; height: 24px; border-radius: 50%; margin-right: 5px;"><span>{{ $hist->usuarioAccion?->primer_nombre ?? 'Sistema' }}
                                                                                                (Usuario) </span></div>
                                                                                        @if ($hist->tiempo_formateado)
                                                                                            <div class="timeline-meta-item">
                                                                                                <i
                                                                                                    class="far fa-clock"></i><span>Tiempo:
                                                                                                    {{ $hist->tiempo_formateado }}</span>
                                                                                            </div>
                                                                                        @endif
                                                                                    </div>
                                                                                    @if ($hist->comentarios)
                                                                                        <div class="timeline-comment"><i
                                                                                                class="fas fa-comment me-2"></i><span
                                                                                                class="timeline-comment-text">Comentario:
                                                                                                {{ $hist->comentarios }}</span>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                        </div>
                                                        <div class="modal-footer"><button type="button"
                                                                class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Cerrar</button><a
                                                                href="{{ route('dashboard') }}?searchContrato={{ $cuenta->contrato?->numero_contrato }}"
                                                                class="btn btn-primary">Ver Dashboard</a></div>
                                                </div>
                                            </div>
                                    </div>@empty <div class="text-center py-4 text-muted small border rounded bg-white">
                                            Sin cuentas </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
<script>
    // Update timers
    function updateTimers() {
        document.querySelectorAll('.timer-badge[data-start]').forEach(badge => {
            const startTimeStr = badge.getAttribute('data-start');
            if (!startTimeStr) return;
            const startTime = new Date(startTimeStr);
            const now = new Date();
            const diffMs = now - startTime;
            const diffSecs = Math.floor(diffMs / 1000);
            const diffMins = Math.floor(diffSecs / 60);
            const diffHours = Math.floor(diffMins / 60);
            const diffDays = Math.floor(diffHours / 24);
            let display = "";
            if (diffDays > 0) display = `${diffDays}d ${diffHours % 24}h`;
            else if (diffHours > 0) display = `${diffHours}h ${diffMins % 60}m`;
            else if (diffMins > 0) display = `${diffMins}m ${diffSecs % 60}s`;
            else display = `${diffSecs}s`;
            badge.querySelector('.elapsed-time').textContent = display;
        });
    }
    setInterval(updateTimers, 1000);
    updateTimers();

    // Function to toggle block visibility
    function toggleBlockVisibility(btn, blockKey) {
        const block = btn.closest('.workflow-block');
        block.classList.toggle('collapsed');
        
        // Save state to localStorage
        const collapsedBlocks = JSON.parse(localStorage.getItem('collapsedBlocks') || '{}');
        collapsedBlocks[blockKey] = block.classList.contains('collapsed');
        localStorage.setItem('collapsedBlocks', JSON.stringify(collapsedBlocks));
    }

    // Restore collapsed blocks on load
    document.addEventListener('DOMContentLoaded', function() {
        const collapsedBlocks = JSON.parse(localStorage.getItem('collapsedBlocks') || '{}');
        document.querySelectorAll('.workflow-block').forEach((block) => {
            const btn = block.querySelector('.btn-collapse');
            if (!btn) return;
            
            const keyMatch = btn.getAttribute('onclick').match(/'([^']+)'/);
            if (keyMatch && collapsedBlocks[keyMatch[1]]) {
                block.classList.add('collapsed');
            }
        });
    });

    // Load available states when modal opens
    document.addEventListener('DOMContentLoaded', function() {
        // Listen for modal show events
        document.querySelectorAll('[id^="modalCuenta"]').forEach(modal => {
            modal.addEventListener('show.bs.modal', function() {
                const cuentaId = this.id.replace('modalCuenta', '');
                loadAvailableStates(cuentaId);
            });
        });
    });

    // Load available states for a cuenta
    function loadAvailableStates(cuentaId) {
        const container = document.getElementById(`statusButtons${cuentaId}`);
        if (!container) return;

        // Show loading
        container.innerHTML =
            '<div class="spinner-border spinner-border-sm text-light" role="status"><span class="visually-hidden">Cargando...</span></div>';

        fetch(`/workflow/estados-disponibles/${cuentaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderStateButtons(cuentaId, data.estados_disponibles);
                } else {
                    container.innerHTML = '<small class="text-white-50">No hay estados disponibles</small>';
                }
            })
            .catch(error => {
                console.error('Error loading states:', error);
                container.innerHTML = '<small class="text-danger">Error al cargar estados</small>';
            });
    }

    // Render state buttons
    function renderStateButtons(cuentaId, estados) {
        const container = document.getElementById(`statusButtons${cuentaId}`);
        if (!container) return;

        if (estados.length === 0) {
            container.innerHTML =
                '<small class="text-white-50">No hay transiciones disponibles desde el estado actual</small>';
            return;
        }

        container.innerHTML = '';
        estados.forEach(estado => {
            const button = document.createElement('button');

            // Determine button class based on state type
            let btnClass = 'btn-proceso'; // default
            if (estado.tipo === 'APROBADO' || estado.tipo === 'FINAL') {
                btnClass = 'btn-aprobado';
            } else if (estado.tipo === 'DEVUELTO') {
                btnClass = 'btn-devuelto';
            }

            button.className = `btn-estado ${btnClass}`;
            button.textContent = estado.nombre;
            button.onclick = () => cambiarEstado(cuentaId, estado.id, estado.nombre, estado
                .requiere_comentario);
            container.appendChild(button);
        });
    }


    // Change state
    function cambiarEstado(cuentaId, estadoDestinoId, estadoNombre, requiereComentario) {
        let comentario = null;

        if (requiereComentario) {
            comentario = prompt(`Ingrese un comentario para cambiar a "${estadoNombre}":`);
            if (comentario === null) return; // User cancelled
        } else {
            if (!confirm(`¿Está seguro de cambiar el estado a "${estadoNombre}"?`)) {
                return;
            }
        }

        // Disable all buttons
        const container = document.getElementById(`statusButtons${cuentaId}`);
        if (!container) return;
        const buttons = container.querySelectorAll('.btn-estado');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('disabled');
        });

        // Prepare data
        const formData = new FormData();
        formData.append('estado_destino_id', estadoDestinoId);
        if (comentario) {
            formData.append('comentario', comentario);
        }

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!csrfToken) {
            showSnackbar("Error: No se encontro el token CSRF. Recargue la pagina.", "error");
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('disabled');
            });
            return;
        }

        fetch(`/workflow/cambiar-estado/${cuentaId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(async response => {
                // Intentar obtener JSON, si falla (ej. error 500 con HTML), devolver error generico
                const data = await response.json().catch(() => ({
                    success: false,
                    message: 'Error interno del servidor. No se pudo completar la accion.'
                }));

                if (!response.ok) {
                    throw new Error(data.message || 'Error desconocido en el servidor.');
                }
                return data;
            })
            .then(data => {
                if (data.success) {
                    showSnackbar(`✓ ${data.message}`, "success");

                    // Update estado actual display
                    const estadoActualSpan = document.getElementById(`estadoActual${cuentaId}`);
                    if (estadoActualSpan) {
                        estadoActualSpan.textContent = data.nuevo_estado.nombre;
                    }

                    // Reload page after a short delay
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showSnackbar(`✗ Error: ${data.message}`, "error");
                    // Re-enable buttons
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        btn.classList.remove('disabled');
                    });
                }
            })
            .catch(error => {
                console.error('Error changing state:', error);
                // Mostrar solo el mensaje del error sanitizado
                showSnackbar(`✗ ${error.message}`, "error");
                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('disabled');
                });
            });
    }
</script>@endsection

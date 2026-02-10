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

    .workflow-container { padding: 20px 0; }
    .workflow-block {
        margin-bottom: 40px;
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
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

    .block-morado { background-color: var(--morado); }
    .block-indigo { background-color: var(--indigo); }
    .block-verde { background-color: var(--verde); }
    .block-naranja { background-color: var(--naranja); }
    .block-rosa { background-color: var(--rosa); }
    .block-cian { background-color: var(--cian); }

    .kanban-board {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        padding-bottom: 10px;
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
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        border-left: 5px solid transparent;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
    }

    .account-card:hover {
        transform: scale(1.02);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        z-index: 10;
    }

    .alert-rojo { border-left-color: var(--rojo-alerta); }
    .alert-amarillo { border-left-color: var(--amarillo-alerta); }
    .alert-verde { border-left-color: var(--verde-alerta); }

    .card-id { font-size: 0.75rem; color: #6c757d; font-weight: bold; }
    .card-contractor { font-size: 0.85rem; font-weight: 600; margin: 5px 0; }
    .card-footer-info {
        display: flex;
        justify-content: space-between;
        font-size: 0.7rem;
        color: #adb5bd;
        margin-top: 10px;
    }

    .timer-badge { background: #f1f3f5; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; }
    
    .modal-xl { max-width: 90%; }
</style>

<div class="container-fluid workflow-container">
    <h1 class="mb-4 text-center fw-bold">Gestión de Flujo de Trabajo (Kanban)</h1>

    @foreach($workflow as $key => $block)
        @if($key === 'bloque4')
            <div class="text-center my-5">
                <hr style="border-top: 3px double #adb5bd;">
                <h2 class="fw-bold text-uppercase" style="color: #495057; letter-spacing: 2px;">RADICADA EN HACIENDA</h2>
                <hr style="border-top: 3px double #adb5bd;">
            </div>
        @endif

        <div class="workflow-block">
            <div class="block-header block-{{ $block['color'] }}">
                <span><i class="fas fa-layer-group me-2"></i>{{ $block['nombre'] }}</span>
                <span class="badge bg-light text-dark shadow-sm">{{ count(collect($block['cuentas'])->flatten(1)) }} Cuentas</span>
            </div>

            <div class="kanban-board">
                @php
                    $columnas = ['revision' => 'Revisión', 'proceso' => 'En Proceso', 'aprobadas' => 'Aprobadas', 'rechazadas' => 'Rechazadas'];
                @endphp

                @foreach($columnas as $colKey => $colLabel)
                <div class="kanban-column">
                    <div class="column-title">
                        <span>{{ $colLabel }}</span>
                        <span class="badge rounded-pill bg-white text-dark shadow-sm">{{ count($block['cuentas'][$colKey] ?? []) }}</span>
                    </div>

                    <div class="column-content">
                        @forelse($block['cuentas'][$colKey] ?? [] as $cuenta)
                            @php
                                $alertClass = 'alert-amarillo';
                                if ($cuenta->estadoActual?->tipo === 'DEVUELTO') $alertClass = 'alert-rojo';
                                if ($cuenta->estadoActual?->tipo === 'APROBADO' || $cuenta->estadoActual?->tipo === 'FINAL') $alertClass = 'alert-verde';
                                $fechaIngreso = $cuenta->estadoBloqueActual?->fecha_ingreso_bloque;
                            @endphp
                            <div class="account-card {{ $alertClass }}" data-bs-toggle="modal" data-bs-target="#modalCuenta{{ $cuenta->id }}">
                                <div class="card-id">CONTRATO: {{ $cuenta->contrato?->numero_contrato ?? 'N/A' }}</div>
                                <div class="card-contractor text-truncate" title="{{ $cuenta->contrato?->contratista?->nombre_completo }}">
                                    {{ Str::limit($cuenta->contrato?->contratista?->nombre_completo ?? 'N/A', 35) }}
                                </div>
                                <div class="small text-muted" style="font-size: 0.75rem;">Cuenta #{{ $cuenta->numero_cuenta }}</div>
                                <div class="card-footer-info">
                                    <span><i class="far fa-calendar-alt"></i> {{ $cuenta->created_at->format('d/m/Y') }}</span>
                                    <span class="timer-badge" data-start="{{ $fechaIngreso ? $fechaIngreso->toIso8601String() : '' }}">
                                        <i class="far fa-clock"></i> 
                                        <span class="elapsed-time">{{ $fechaIngreso ? $fechaIngreso->diffForHumans(null, true) : 'N/A' }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Modal Detallado -->
                            <div class="modal fade" id="modalCuenta{{ $cuenta->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Cuenta: {{ $cuenta->numero_cuenta }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3 mb-4">
                                                <div class="col-md-4">
                                                    <div class="p-3 border rounded h-100 bg-white">
                                                        <h6 class="text-primary border-bottom pb-2">Contrato</h6>
                                                        <p class="mb-1 small"><strong>N°:</strong> {{ $cuenta->contrato?->numero_contrato }}</p>
                                                        <p class="mb-1 small"><strong>Contratista:</strong> {{ $cuenta->contrato?->contratista?->nombre_completo }}</p>
                                                        <p class="mb-1 small"><strong>Valor:</strong> ${{ number_format($cuenta->valor_cobro, 2) }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="p-3 border rounded h-100 bg-white">
                                                        <h6 class="text-primary border-bottom pb-2">Estado</h6>
                                                        <p class="mb-1 small"><strong>Bloque:</strong> {{ $cuenta->bloqueActual?->nombre }}</p>
                                                        <p class="mb-1 small"><strong>Estado:</strong> {{ $cuenta->estadoActual?->nombre }}</p>
                                                        <p class="mb-1 small"><strong>Responsable:</strong> {{ $cuenta->responsableActual?->primer_nombre ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="p-3 border rounded h-100 bg-white">
                                                        <h6 class="text-primary border-bottom pb-2">Hacienda</h6>
                                                        <p class="mb-1 small"><strong>Factura:</strong> {{ $cuenta->ultima_factura_hacienda }}</p>
                                                        <p class="mb-1 small"><strong>Fecha Rad:</strong> {{ $cuenta->fecha_radicacion_hacienda?->format('d/m/Y') ?? 'PENDIENTE' }}</p>
                                                        <p class="mb-1 small text-truncate"><strong>Obs:</strong> {{ $cuenta->observacion_hacienda }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <h6 class="text-primary border-bottom pb-2">Historial de Movimientos</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped">
                                                    <thead class="table-dark">
                                                        <tr><th>Fecha</th><th>Origen</th><th>Destino</th><th>Usuario</th><th>Tiempo</th></tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($cuenta->historialWorkflow->sortByDesc('fecha_transicion') as $hist)
                                                        <tr>
                                                            <td class="small">{{ $hist->fecha_transicion->format('d/m/Y H:i') }}</td>
                                                            <td class="small text-muted">{{ $hist->estadoOrigen?->nombre ?? 'Inicio' }}</td>
                                                            <td class="small fw-bold text-primary">{{ $hist->estadoDestino?->nombre }}</td>
                                                            <td class="small">{{ $hist->usuarioAccion?->primer_nombre ?? 'Sistema' }}</td>
                                                            <td class="small text-end"><span class="badge bg-light text-dark">{{ $hist->tiempo_formateado ?: '-' }}</span></td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                            <a href="{{ route('dashboard') }}?searchContrato={{ $cuenta->contrato?->numero_contrato }}" class="btn btn-primary">Ver Dashboard</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted small border rounded bg-white">Sin cuentas</div>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<script>
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
</script>
@endsection

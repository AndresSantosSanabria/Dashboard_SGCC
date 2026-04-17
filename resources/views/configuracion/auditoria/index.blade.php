@extends('layouts.app')

@section('title', 'Trazabilidad y Auditoría — SGCC')

@push('styles')
    @vite(['resources/views/configuracion/configuracion.css'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .filter-glass {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .audit-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 800;
            background: #F1F5F9;
            color: #475569;
            border: 1px solid #E2E8F0;
        }
        .json-viewer {
            padding: 1rem;
            background: #1E293B;
            color: #94A3B8;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.75rem;
            border-radius: 12px;
            overflow: auto;
            max-height: 400px;
        }
        .json-viewer .key { color: #818CF8; }
        .json-viewer .string { color: #34D399; }
        .json-viewer .number { color: #FBBF24; }
    </style>
@endpush

@section('page-content')
    <div class="config-container">
        {{-- HEADER --}}
        <header class="config-header">
            <div>
                <h1>
                    <i class="bi bi-shield-check-fill text-primary"></i>
                    Bitácora de Auditoría
                </h1>
                <p class="text-muted mb-0">Seguimiento detallado de operaciones, cambios de estado y registros técnicos.</p>
            </div>
            <div>
                <a href="{{ route('configuracion.index') }}" class="btn-premium btn-premium-dark">
                    <i class="bi bi-chevron-left"></i> Volver
                </a>
            </div>
        </header>

        {{-- FILTROS PREMIUM --}}
        <div class="filter-glass animate-fadeInDown">
            <form action="{{ route('configuracion.auditoria.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="premium-label small">Entidad / Tabla Afectada</label>
                    <select name="tabla" class="premium-select">
                        <option value="">Todas las entidades</option>
                        @foreach($tablas as $tabla)
                            <option value="{{ $tabla }}" {{ request('tabla') == $tabla ? 'selected' : '' }}>
                                {{ strtoupper($tabla) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="premium-label small">Tipo de Acción o Evento</label>
                    <select name="accion" class="premium-select">
                        <option value="">Cualquier tipo de acción</option>
                        <optgroup label="💾 Operaciones de Persistencia">
                            <option value="INSERT" {{ request('accion') == 'INSERT' ? 'selected' : '' }}>📥 INSERT</option>
                            <option value="UPDATE" {{ request('accion') == 'UPDATE' ? 'selected' : '' }}>📝 UPDATE</option>
                            <option value="DELETE" {{ request('accion') == 'DELETE' ? 'selected' : '' }}>🗑️ DELETE</option>
                        </optgroup>
                        <optgroup label="⚙️ Lógica de Workflow">
                            <option value="WORKFLOW_TRANSICION" {{ request('accion') == 'WORKFLOW_TRANSICION' ? 'selected' : '' }}>🔄 Transición</option>
                            <option value="WORKFLOW_DEVOLUCION" {{ request('accion') == 'WORKFLOW_DEVOLUCION' ? 'selected' : '' }}>↩️ Devolución</option>
                            <option value="WORKFLOW_AUTO" {{ request('accion') == 'WORKFLOW_AUTO' ? 'selected' : '' }}>⚡ Paso Automático</option>
                        </optgroup>
                        <optgroup label="⚠️ Diagnóstico de Fallos">
                            <option value="FAILURE" {{ request('accion') == 'FAILURE' ? 'selected' : '' }}>🆘 TODOS LOS FALLOS</option>
                            <option value="FAILURE_DATABASE" {{ request('FAILURE_DATABASE') == 'FAILURE_DATABASE' ? 'selected' : '' }}>🗄️ FALLOS BD</option>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-premium btn-premium-primary flex-grow-1">
                            <i class="bi bi-funnel-fill"></i> Aplicar Filtros
                        </button>
                        <a href="{{ route('configuracion.auditoria.index') }}" class="btn-premium btn-premium-secondary" title="Limpiar Filtros">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLA DE AUDITORÍA --}}
        <div class="premium-card animate-fadeIn">
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 180px">Timestamp</th>
                            <th>Agente Ejecutor</th>
                            <th>Contexto Operativo</th>
                            <th>Registro</th>
                            <th>Acción</th>
                            <th class="text-center">IP</th>
                            <th class="text-end pe-4">Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditorias as $auditoria)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-main" style="font-size: 0.85rem;">{{ $auditoria->created_at->format('d/m/Y') }}</span>
                                        <span class="text-muted extra-small">{{ $auditoria->created_at->format('H:i:s') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="audit-avatar">
                                            {{ strtoupper(substr($auditoria->usuario->primer_nombre ?? 'S', 0, 1)) }}{{ strtoupper(substr($auditoria->usuario->primer_apellido ?? 'I', 0, 1)) }}
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-main small">{{ $auditoria->usuario->nombre_completo ?? 'SYSTEM ENGINE' }}</span>
                                            <span class="text-muted extra-small">{{ $auditoria->usuario->user ?? 'kernel-task' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-muted border extra-small px-2 py-1">
                                        {{ strtoupper($auditoria->tabla_afectada) }}
                                    </span>
                                </td>
                                <td>
                                    <code class="text-primary font-monospace extra-small">ID #{{ $auditoria->registro_id }}</code>
                                </td>
                                <td>
                                    @php
                                        $labelClass = match($auditoria->accion) {
                                            'INSERT' => 'wf-badge-final',
                                            'UPDATE' => 'wf-badge-proceso',
                                            'DELETE' => 'bg-danger text-white',
                                            'WORKFLOW_TRANSICION' => 'wf-badge-aprobado',
                                            'WORKFLOW_DEVOLUCION' => 'bg-warning text-dark',
                                            default => str_contains($auditoria->accion, 'FAILURE') ? 'bg-danger text-white' : 'bg-light text-muted'
                                        };
                                        $icon = match(true) {
                                            $auditoria->accion === 'INSERT' => 'bi-plus-circle',
                                            $auditoria->accion === 'UPDATE' => 'bi-pencil-square',
                                            $auditoria->accion === 'DELETE' => 'bi-trash3',
                                            str_contains($auditoria->accion, 'WORKFLOW') => 'bi-diagram-2',
                                            str_contains($auditoria->accion, 'FAILURE') => 'bi-exclamation-octagon-fill',
                                            default => 'bi-info-circle'
                                        };
                                    @endphp
                                    <span class="premium-badge {{ $labelClass }} extra-small" style="font-size: 0.65rem;">
                                        <i class="bi {{ $icon }}"></i>
                                        {{ $auditoria->accion }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="text-muted extra-small font-monospace">{{ $auditoria->ip_origen ?? '0.0.0.0' }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="action-btn" onclick="verDetalles({{ $auditoria->id }})">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                                        No se encontraron registros de auditoría bajo estos filtros.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($auditorias->hasPages())
                <div class="p-3 border-top">
                    {{ $auditorias->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL DETALLE PREMIUM --}}
    <div class="modal fade premium-modal" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content overflow-hidden">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white p-2 rounded-3 text-primary">
                            <i class="bi bi-search fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold" id="modalTitle">Detalle de Transacción</h5>
                            <p class="mb-0 extra-small opacity-75" id="det_timestamp"></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 p-md-5 bg-white">
                    <div class="row g-4 mb-5">
                        <div class="col-md-3">
                            <label class="premium-label small">Origen IP</label>
                            <div class="fw-bold text-main" id="det_ip"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="premium-label small">Acción Ejecutada</label>
                            <div id="det_accion_badge"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="premium-label small">User Agent (Browser Info)</label>
                            <div class="extra-small text-muted text-truncate" id="det_ua" title=""></div>
                        </div>
                    </div>

                    {{-- PANEL DE ERROR --}}
                    <div id="det_error_panel" class="mb-5 animate-fadeIn" style="display:none;">
                        <div class="card border-danger shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-danger text-white py-3 border-0">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-bug-fill me-2"></i>Excepción de Sistema Detectada</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="alert alert-danger bg-danger-subtle border-0 mb-4 fw-bold small" id="det_error_msg"></div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="premium-label extra-small text-danger">Clase Exception</label>
                                        <code id="det_error_clase" class="extra-small d-block py-1"></code>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="premium-label extra-small text-danger">Ruta/Fichero de origen</label>
                                        <code id="det_error_ubicacion" class="extra-small d-block py-1"></code>
                                    </div>
                                </div>
                                <label class="premium-label extra-small">Stack Trace Técnico</label>
                                <pre class="json-viewer bg-dark p-3 rounded-4 extra-small" id="det_error_trace" style="height: 250px; color: #FDA4AF;"></pre>
                            </div>
                        </div>
                    </div>

                    {{-- COMPARATIVA DE DATOS --}}
                    <div id="det_data_panels">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="bi bi-skip-backward-fill text-muted"></i>
                                    <h6 class="fw-bold mb-0 text-main">Estado Previo</h6>
                                </div>
                                <pre class="json-viewer" id="det_anterior"></pre>
                            </div>
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="bi bi-play-fill text-success"></i>
                                    <h6 class="fw-bold mb-0 text-main">Estado Posterior</h6>
                                </div>
                                <pre class="json-viewer" id="det_nuevo" style="background: #0F172A; border-left: 4px solid #10B981;"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function verDetalles(id) {
            window.apiFetch(`{{ url('configuracion/auditoria') }}/${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('det_ip').textContent = data.ip_origen || 'N/A';
                    document.getElementById('det_ua').textContent = data.user_agent || 'N/A';
                    document.getElementById('det_ua').title = data.user_agent || '';
                    document.getElementById('det_timestamp').textContent = `Iniciado: ${new Date(data.created_at).toLocaleString()}`;
                    
                    const isFailure = data.accion.includes('FAILURE');
                    const badgeClass = isFailure ? 'bg-danger text-white' : 'bg-primary text-white';
                    document.getElementById('det_accion_badge').innerHTML = `<span class="premium-badge ${badgeClass} extra-small">${data.accion}</span>`;

                    const errorPanel = document.getElementById('det_error_panel');
                    const dataPanels = document.getElementById('det_data_panels');
                    const payload = data.payload_nuevo;

                    if (isFailure) {
                        errorPanel.style.display = 'block';
                        dataPanels.style.display = 'none';
                        if (payload) {
                            document.getElementById('det_error_msg').textContent = payload.error || payload.detalle || 'Fallo de ejecución sin descripción.';
                            document.getElementById('det_error_clase').textContent = payload.clase || 'N/A';
                            document.getElementById('det_error_ubicacion').textContent = payload.ubicacion || 'Fichero desconocido';
                            document.getElementById('det_error_trace').textContent = payload.trace || 'Trace no disponible';
                        }
                    } else {
                        errorPanel.style.display = 'none';
                        dataPanels.style.display = 'block';
                        document.getElementById('det_anterior').textContent = data.payload_anterior ? JSON.stringify(data.payload_anterior, null, 4) : '// Sin datos previos';
                        document.getElementById('det_nuevo').textContent = payload ? JSON.stringify(payload, null, 4) : '// Sin cambios registrados';
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
                    modal.show();
                });
        }
    </script>
    @endpush
@endsection

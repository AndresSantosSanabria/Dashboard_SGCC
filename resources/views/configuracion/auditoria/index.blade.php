@extends('layouts.app')

@section('title', 'Historial de Auditoría')

@section('page-content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-history me-2"></i>Historial de Auditoría
            </h1>
            <a href="{{ route('configuracion.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Usuarios
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Filtros --}}
        <div class="card shadow mb-4">
            <div class="card-body">
                <form action="{{ route('configuracion.auditoria.index') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Tabla</label>
                        <select name="tabla" class="form-select">
                            <option value="">Todas las tablas</option>
                            @foreach($tablas as $tabla)
                                <option value="{{ $tabla }}" {{ request('tabla') == $tabla ? 'selected' : '' }}>
                                    {{ ucfirst($tabla) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Acción</label>
                        <select name="accion" class="form-select">
                            <option value="">Todas las acciones</option>
                            <option value="INSERT" {{ request('accion') == 'INSERT' ? 'selected' : '' }}>INSERT</option>
                            <option value="UPDATE" {{ request('accion') == 'UPDATE' ? 'selected' : '' }}>UPDATE</option>
                            <option value="DELETE" {{ request('accion') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                            <option value="READ" {{ request('accion') == 'READ' ? 'selected' : '' }}>READ</option>
                            <option value="FAILURE" {{ request('accion') == 'FAILURE' ? 'selected' : '' }}>⚠️ TODOS LOS FALLOS</option>
                            <option value="FAILURE_DATABASE" {{ request('accion') == 'FAILURE_DATABASE' ? 'selected' : '' }}>🔴 FALLOS BD (QueryException)</option>
                            <option value="FAILURE_SERVER" {{ request('accion') == 'FAILURE_SERVER' ? 'selected' : '' }}>🟠 FALLOS SERVIDOR (500)</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-2"></i>Filtrar
                        </button>
                        <a href="{{ route('configuracion.auditoria.index') }}" class="btn btn-light">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabla de Auditoría --}}
        <div class="card shadow mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Fecha / Hora</th>
                                <th>Usuario</th>
                                <th>Tabla</th>
                                <th>ID Registro</th>
                                <th>Acción</th>
                                <th>IP</th>
                                <th class="text-end pe-4">Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditorias as $auditoria)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $auditoria->created_at->format('d/m/Y') }}</div>
                                        <div class="small text-muted">{{ $auditoria->created_at->format('H:i:s') }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                {{ strtoupper(substr($auditoria->usuario->primer_nombre ?? 'S', 0, 1)) }}{{ strtoupper(substr($auditoria->usuario->primer_apellido ?? 'I', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold small">{{ $auditoria->usuario->nombre_completo ?? 'Sistema' }}</div>
                                                <div class="text-muted tiny" style="font-size: 0.7rem;">{{ $auditoria->usuario->user ?? 'cron' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $auditoria->tabla_afectada }}</span>
                                    </td>
                                    <td>
                                        <code>#{{ $auditoria->registro_id }}</code>
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match(true) {
                                                $auditoria->accion === 'INSERT' => 'bg-success',
                                                $auditoria->accion === 'UPDATE' => 'bg-info',
                                                $auditoria->accion === 'DELETE' => 'bg-danger',
                                                $auditoria->accion === 'READ' => 'bg-secondary',
                                                $auditoria->accion === 'FAILURE_DATABASE' => 'bg-danger',
                                                $auditoria->accion === 'FAILURE_SERVER' => 'bg-warning text-dark',
                                                str_contains($auditoria->accion, 'FAILURE') => 'bg-danger',
                                                str_contains($auditoria->accion, 'IMPORT') => 'bg-primary',
                                                default => 'bg-secondary'
                                            };
                                            $iconClass = match(true) {
                                                $auditoria->accion === 'FAILURE_DATABASE' => 'fas fa-database',
                                                $auditoria->accion === 'FAILURE_SERVER' => 'fas fa-server',
                                                str_contains($auditoria->accion, 'FAILURE') => 'fas fa-exclamation-triangle',
                                                default => ''
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            @if($iconClass)<i class="{{ $iconClass }} me-1"></i>@endif
                                            {{ $auditoria->accion }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small text-muted">{{ $auditoria->ip_origen }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalles({{ $auditoria->id }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        @if(request('accion') === 'FAILURE')
                                            <i class="fas fa-check-circle text-success mb-2 d-block" style="font-size: 2rem;"></i>
                                            No se encontró fallo
                                        @else
                                            No se encontraron registros de auditoría
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($auditorias->hasPages())
                <div class="card-footer bg-white border-top-0">
                    {{ $auditorias->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal de Detalles --}}
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detalles de la Transacción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="small text-muted mb-1 d-block">IP Origen</label>
                            <div class="fw-bold" id="det_ip"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted mb-1 d-block">Acción</label>
                            <div id="det_accion"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1 d-block">User Agent</label>
                            <div class="small fw-bold text-truncate" id="det_ua"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="small text-muted mb-1 d-block">Estado de la Operación</label>
                        <div id="det_status_badge"></div>
                    </div>

                    {{-- Panel de Error (solo visible para fallos) --}}
                    <div id="det_error_panel" class="mb-4" style="display:none;">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white fw-bold small">
                                <i class="fas fa-exclamation-triangle me-2"></i>Descripción del Fallo
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3">
                                    <label class="small text-muted d-block mb-1">Mensaje de Error</label>
                                    <div class="alert alert-danger mb-0 small" id="det_error_msg" style="word-break: break-all;"></div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <label class="small text-muted d-block mb-1">Tipo de Excepción</label>
                                        <code class="small" id="det_error_clase"></code>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted d-block mb-1">Código SQL</label>
                                        <code class="small" id="det_error_codigo"></code>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted d-block mb-1">URL Afectada</label>
                                        <code class="small text-truncate d-block" id="det_error_url"></code>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted d-block mb-1">Ubicación en Código</label>
                                    <code class="small" id="det_error_ubicacion"></code>
                                </div>
                                <div id="det_error_contexto_wrap" style="display:none;">
                                    <label class="small text-muted d-block mb-1">Datos de Contexto</label>
                                    <pre class="bg-white border rounded p-2 small mb-3" id="det_error_contexto" style="max-height: 150px; overflow-y: auto;"></pre>
                                </div>
                                <div>
                                    <label class="small text-muted d-block mb-1">Stack Trace</label>
                                    <pre class="bg-dark text-success rounded p-2 small" id="det_error_trace" style="max-height: 200px; overflow-y: auto; font-size: 0.65rem;"></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Paneles de datos (para operaciones normales) --}}
                    <div class="row g-4" id="det_data_panels">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-white fw-bold small text-danger">Estado Anterior</div>
                                <div class="card-body p-0">
                                    <pre class="m-0 p-3 bg-white" id="det_anterior" style="font-size: 0.75rem; max-height: 400px; overflow-y: auto;"></pre>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-white fw-bold small text-success">Estado Nuevo</div>
                                <div class="card-body p-0">
                                    <pre class="m-0 p-3 bg-white" id="det_nuevo" style="font-size: 0.75rem; max-height: 400px; overflow-y: auto;"></pre>
                                </div>
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
                    document.getElementById('det_accion').innerHTML = `<span class="badge ${data.accion.includes('FAILURE') ? 'bg-danger' : 'bg-primary'}">${data.accion}</span>`;

                    const statusBadge = document.getElementById('det_status_badge');
                    const errorPanel = document.getElementById('det_error_panel');
                    const dataPanels = document.getElementById('det_data_panels');
                    const payload = data.payload_nuevo;
                    const isFailure = data.accion.includes('FAILURE');

                    if (isFailure) {
                        statusBadge.innerHTML = '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i> Fallo Detectado</span>';
                        
                        // Mostrar panel de error y ocultar datos normales
                        errorPanel.style.display = 'block';
                        dataPanels.style.display = 'none';

                        if (payload) {
                            // Llenar campos del error
                            document.getElementById('det_error_msg').textContent = payload.error || payload.detalle || 'Error desconocido';
                            document.getElementById('det_error_clase').textContent = payload.clase || 'N/A';
                            document.getElementById('det_error_codigo').textContent = payload.codigo_sql || 'N/A';
                            document.getElementById('det_error_url').textContent = payload.url || 'N/A';
                            document.getElementById('det_error_ubicacion').textContent = payload.ubicacion || 'N/A';
                            document.getElementById('det_error_trace').textContent = payload.trace || 'No disponible';

                            // Contexto (datos intentados)
                            const contextoWrap = document.getElementById('det_error_contexto_wrap');
                            if (payload.contexto && Object.keys(payload.contexto).length > 0) {
                                contextoWrap.style.display = 'block';
                                document.getElementById('det_error_contexto').textContent = JSON.stringify(payload.contexto, null, 2);
                            } else if (payload.intentado) {
                                contextoWrap.style.display = 'block';
                                document.getElementById('det_error_contexto').textContent = JSON.stringify(payload.intentado, null, 2);
                            } else {
                                contextoWrap.style.display = 'none';
                            }
                        } else {
                            document.getElementById('det_error_msg').textContent = 'Sin información de error disponible';
                            document.getElementById('det_error_clase').textContent = 'N/A';
                            document.getElementById('det_error_codigo').textContent = 'N/A';
                            document.getElementById('det_error_url').textContent = 'N/A';
                            document.getElementById('det_error_ubicacion').textContent = 'N/A';
                            document.getElementById('det_error_trace').textContent = 'N/A';
                        }
                    } else {
                        statusBadge.innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Operación Correcta (Sin fallos)</span>';
                        
                        // Ocultar panel de error y mostrar datos normales
                        errorPanel.style.display = 'none';
                        dataPanels.style.display = '';

                        document.getElementById('det_anterior').textContent = data.payload_anterior ? JSON.stringify(data.payload_anterior, null, 4) : 'Ninguno';
                        document.getElementById('det_nuevo').textContent = payload ? JSON.stringify(payload, null, 4) : 'Ninguno';
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
                    modal.show();
                });
        }
    </script>
    @endpush

    @push('styles')
    <style>
        .tiny { font-size: 0.7rem; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        .bg-light { background-color: #f8f9fc !important; }
    </style>
    @endpush
@endsection

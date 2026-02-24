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
                            <option value="FAILURE" {{ request('accion') == 'FAILURE' ? 'selected' : '' }}>FALLOS (FAILURE)</option>
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
                                                str_contains($auditoria->accion, 'FAILURE') => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $auditoria->accion }}</span>
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
                        <div class="col-md-4">
                            <label class="small text-muted mb-1 d-block">IP Origen</label>
                            <div class="fw-bold" id="det_ip"></div>
                        </div>
                        <div class="col-md-8">
                            <label class="small text-muted mb-1 d-block">User Agent</label>
                            <div class="small fw-bold text-truncate" id="det_ua"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="small text-muted mb-1 d-block">Estado de la Operación</label>
                        <div id="det_status_badge"></div>
                    </div>

                    <div class="row g-4">
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
            fetch(`{{ url('configuracion/auditoria') }}/${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('det_ip').textContent = data.ip_origen || 'N/A';
                    document.getElementById('det_ua').textContent = data.user_agent || 'N/A';
                    
                    let anterior = data.payload_anterior ? JSON.stringify(data.payload_anterior, null, 4) : 'Ninguno';
                    let nuevo = data.payload_nuevo ? JSON.stringify(data.payload_nuevo, null, 4) : 'Ninguno';

                    const statusBadge = document.getElementById('det_status_badge');
                    if (data.accion.includes('FAILURE')) {
                        statusBadge.innerHTML = '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i> Fallo Detectado</span>';
                        if (!data.payload_nuevo || !data.payload_nuevo.detalle) {
                            nuevo = "No se encontró fallo específico";
                        }
                    } else {
                        statusBadge.innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Operación Correcta (Sin fallos)</span>';
                    }
                    
                    document.getElementById('det_anterior').textContent = anterior;
                    document.getElementById('det_nuevo').textContent = nuevo;
                    
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

@extends('layouts.app')

@section('title', 'Gestión de Usuarios — Sistema de Control')

@push('styles')
    @vite(['resources/views/configuracion/configuracion.css'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('page-content')
    <div class="config-container">
        {{-- HEADER PREMIUM --}}
        <header class="config-header">
            <div>
                <h1>
                    <i class="bi bi-people-fill"></i> Gestión de Usuarios
                </h1>
                <p class="text-muted mb-0">Control de acceso, roles y estados del personal del sistema</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('configuracion.alertas.index') }}" class="btn-premium btn-premium-warning">
                    <i class="bi bi-bell-fill"></i> Alertas
                </a>
                <a href="{{ route('configuracion.auditoria.index') }}" class="btn-premium btn-premium-dark">
                    <i class="bi bi-shield-check"></i> Auditoría
                </a>
                <a href="{{ route('configuracion.roles.index') }}" class="btn-premium btn-premium-secondary">
                    <i class="bi bi-person-badge"></i> Roles
                </a>
                <a href="{{ route('configuracion.workflow.index') }}" class="btn-premium btn-premium-info">
                    <i class="bi bi-diagram-3-fill"></i> Workflow
                </a>
                <a href="{{ route('configuracion.create') }}" class="btn-premium btn-premium-primary">
                    <i class="bi bi-person-plus-fill"></i> Crear Usuario
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible bg-white border-0 shadow-sm rounded-4 fade show" role="alert">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success text-white p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- TABLA MAESTRA --}}
        <div class="premium-card">
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th>Nombre Completo</th>
                            <th>Usuario (@)</th>
                            <th>Rol</th>
                            <th class="text-center">Estado</th>
                            <th>Último Acceso</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuarios as $usuario)
                            <tr>
                                <td class="text-center fw-bold text-muted" style="font-size: 0.8rem;">#{{ $usuario->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3 text-primary fw-bold" style="width: 38px; height: 38px; border: 1px solid #e2e8f0;">
                                            {{ strtoupper(substr($usuario->nombre_completo, 0, 1)) }}
                                        </div>
                                        <span class="fw-semibold">{{ $usuario->nombre_completo }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted font-monospace">@</span>{{ $usuario->user }}
                                </td>
                                <td>
                                    <span class="premium-badge badge-role">
                                        {{ $usuario->rol->nombre }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="premium-badge {{ $usuario->es_activo ? 'badge-active' : 'badge-inactive' }}"
                                        id="status-badge-{{ $usuario->id }}">
                                        <i class="bi bi-{{ $usuario->es_activo ? 'check-circle' : 'x-circle' }}-fill me-1"></i>
                                        {{ $usuario->es_activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size: 0.85rem;">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ $usuario->ultimo_login ? $usuario->ultimo_login->format('d/m/Y H:i') : 'Sin registros' }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('configuracion.edit', $usuario->id) }}" class="action-btn"
                                            title="Editar Perfil">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        @if ($usuario->id !== auth()->id())
                                            <button onclick="toggleStatus({{ $usuario->id }})"
                                                class="action-btn {{ $usuario->es_activo ? 'btn-toggle-on' : 'btn-toggle-off' }}"
                                                id="toggle-btn-{{ $usuario->id }}"
                                                title="{{ $usuario->es_activo ? 'Desactivar Usuario' : 'Activar Usuario' }}">
                                                <i class="bi bi-toggle-{{ $usuario->es_activo ? 'on' : 'off' }} fs-5" id="toggle-icon-{{ $usuario->id }}"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-3 opacity-25"></i>
                                        No se encontraron usuarios registrados
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleStatus(userId) {
            const btn = document.getElementById(`toggle-btn-${userId}`);
            const originalIconClass = btn.querySelector('i').className;
            
            // Loading state
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            btn.disabled = true;

            window.apiFetch(`/configuracion/toggle-status/${userId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById(`status-badge-${userId}`);
                        const icon = document.createElement('i');
                        
                        if (data.es_activo) {
                            badge.className = 'premium-badge badge-active';
                            badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Activo';
                            btn.className = 'action-btn btn-toggle-on';
                            btn.title = 'Desactivar Usuario';
                            btn.innerHTML = '<i class="bi bi-toggle-on fs-5"></i>';
                        } else {
                            badge.className = 'premium-badge badge-inactive';
                            badge.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> Inactivo';
                            btn.className = 'action-btn btn-toggle-off';
                            btn.title = 'Activar Usuario';
                            btn.innerHTML = '<i class="bi bi-toggle-off fs-5"></i>';
                        }

                        window.showSnackbar('✨ ' + data.message, 'success');
                    } else {
                        btn.innerHTML = `<i class="${originalIconClass}"></i>`;
                        window.showSnackbar('❌ ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    btn.innerHTML = `<i class="${originalIconClass}"></i>`;
                    window.showSnackbar('❌ Error de conexión al servidor', 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                });
        }
    </script>
@endsection


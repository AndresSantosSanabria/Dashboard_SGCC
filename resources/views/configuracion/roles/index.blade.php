@extends('layouts.app')

@section('title', 'Gestión de Roles — SGCC')

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
                    <i class="bi bi-shield-lock-fill"></i>
                    Gestión de Roles
                </h1>
                <p class="text-muted mb-0">Administre los niveles de acceso y perfiles de seguridad del sistema</p>
            </div>
            <div>
                <a href="{{ route('configuracion.roles.create') }}" class="btn-premium btn-premium-primary">
                    <i class="bi bi-plus-circle"></i> Nuevo Rol Personalizado
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm animate-fadeIn" role="alert" style="border-radius: 12px; border-left: 5px solid #22c55e !important; background: white;">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                    <div>
                        <strong class="d-block">¡Operación exitosa!</strong>
                        <span class="text-muted small">{{ session('success') }}</span>
                    </div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- TABLE CARD --}}
        <div class="premium-card animate-fadeInUp">
            <div class="card-header bg-white border-0 py-4 ps-4">
                <h6 class="m-0 fw-bold text-main d-flex align-items-center">
                    <i class="bi bi-list-stars me-2 text-primary"></i> Perfiles de Usuario Configurados
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="premium-table mb-0" id="rolesTable">
                        <thead>
                            <tr>
                                <th class="ps-4">Nombre del Rol</th>
                                <th>Descripción</th>
                                <th>Tipo de Perfil</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $rol)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                                                <i class="bi bi-shield-shaded text-primary"></i>
                                            </div>
                                            <div>
                                                <span class="d-block fw-bold text-main">{{ $rol->nombre }}</span>
                                                <span class="text-muted extra-small">ID: #{{ $rol->id }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $rol->descripcion ?: 'Sin descripción' }}</span>
                                    </td>
                                    <td>
                                        <span class="role-type role-type-{{ strtolower($rol->tipo) }}">
                                            <i class="bi bi-{{ $rol->tipo === 'SISTEMA' ? 'cpu' : 'person-gear' }}"></i>
                                            {{ ucfirst(strtolower($rol->tipo)) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="premium-badge {{ $rol->es_activo ? 'status-activo' : 'status-inactivo' }}">
                                            {{ $rol->es_activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="d-flex justify-content-center gap-1">
                                            @if ($rol->tipo === 'PERSONALIZADO' || in_array($rol->nombre, ['Administrador', 'Visualizador']))
                                                <a href="{{ route('configuracion.roles.edit', $rol->id) }}"
                                                    class="action-btn" title="Editar privilegios">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <button
                                                    class="action-btn {{ $rol->es_activo ? 'btn-toggle-on' : 'btn-toggle-off' }} toggle-status-btn"
                                                    data-role-id="{{ $rol->id }}"
                                                    data-url="{{ route('configuracion.roles.toggle-status', $rol->id) }}"
                                                    title="{{ $rol->es_activo ? 'Desactivar' : 'Activar' }} rol">
                                                    <i class="bi bi-{{ $rol->es_activo ? 'toggle-on' : 'toggle-off' }}"></i>
                                                </button>
                                            @else
                                                <span class="text-muted small"><i class="bi bi-lock-fill"></i> Bloqueado</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('configuracion.index') }}" class="btn btn-link text-muted text-decoration-none">
                <i class="bi bi-arrow-left"></i> Volver a Configuración de Usuarios
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-status-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.dataset.url;
                    const isActive = this.classList.contains('btn-toggle-on');

                    const action = isActive ? 'desactivar' : 'activar';
                    
                    Swal.fire({
                        title: '¿Confirmar cambio?',
                        text: `¿Desea ${action} este rol y sus permisos asociados?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#4f46e5',
                        cancelButtonColor: '#94a3b8',
                        confirmButtonText: 'Confirmar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        window.apiFetch(url, { method: 'POST' })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.showSnackbar(data.message, 'success');
                                    setTimeout(() => location.reload(), 800);
                                } else {
                                    window.showSnackbar(data.message || 'Error al cambiar estado', 'error');
                                }
                            });
                    });
                });
            });
        });
    </script>
@endsection


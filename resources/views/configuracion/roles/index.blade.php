@extends('layouts.app')

@section('title', 'Gestión de Roles')

@section('page-content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-shield me-2"></i>Gestión de Roles
            </h1>
            <a href="{{ route('configuracion.roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nuevo Rol Personalizado
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Roles del Sistema</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="rolesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Tipo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $rol)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $rol->nombre }}</td>
                                    <td>{{ $rol->descripcion }}</td>
                                    <td>
                                        @if ($rol->tipo === 'SISTEMA')
                                            <span class="badge bg-secondary">Sistema</span>
                                        @else
                                            <span class="badge bg-info">Personalizado</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $rol->es_activo ? 'bg-success' : 'bg-danger' }}">
                                            {{ $rol->es_activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($rol->tipo === 'PERSONALIZADO')
                                            <a href="{{ route('configuracion.roles.edit', $rol->id) }}" class="btn btn-sm btn-outline-primary me-2"
                                                title="Editar rol">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-{{ $rol->es_activo ? 'danger' : 'success' }} toggle-status-btn"
                                                data-role-id="{{ $rol->id }}"
                                                data-url="{{ route('configuracion.roles.toggle-status', $rol->id) }}"
                                                title="{{ $rol->es_activo ? 'Desactivar' : 'Activar' }} rol">
                                                <i class="fas fa-{{ $rol->es_activo ? 'ban' : 'check' }}"></i>
                                            </button>
                                        @else
                                            <span class="text-muted"><i class="fas fa-lock"></i> Sistema</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('configuracion.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Configuración de Usuarios
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-status-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const roleId = this.dataset.roleId;
                    const url = this.dataset.url;
                    const isActive = this.classList.contains('btn-outline-danger'); // danger = active, so clicking it deactivates

                    if (confirm(`¿Estás seguro de que quieres ${isActive ? 'desactivar' : 'activar'} este rol?`)) {
                        window.apiFetch(url, {
                            method: 'POST'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.showSnackbar(data.message, 'success');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                window.showSnackbar(data.message || 'Error al cambiar estado', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            window.showSnackbar('Error al cambiar estado', 'error');
                        });
                    }
                });
            });
        });
    </script>
@endsection

@extends('layouts.app')

@section('title', 'Configuración de Usuarios')

@section('page-content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users-cog me-2"></i>Gestión de Usuarios
            </h1>
            <a href="{{ route('configuracion.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Crear Usuario
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Último Login</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usuarios as $usuario)
                                <tr>
                                    <td>{{ $usuario->id }}</td>
                                    <td>{{ $usuario->nombre_completo }}</td>
                                    <td>{{ $usuario->user }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $usuario->rol->nombre }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $usuario->es_activo ? 'bg-success' : 'bg-danger' }}"
                                            id="status-badge-{{ $usuario->id }}">
                                            {{ $usuario->es_activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>{{ $usuario->ultimo_login ? $usuario->ultimo_login->format('d/m/Y H:i') : 'Nunca' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('configuracion.edit', $usuario->id) }}"
                                            class="btn btn-sm btn-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if ($usuario->id !== auth()->id())
                                            <button onclick="toggleStatus({{ $usuario->id }})"
                                                class="btn btn-sm {{ $usuario->es_activo ? 'btn-danger' : 'btn-success' }}"
                                                id="toggle-btn-{{ $usuario->id }}"
                                                title="{{ $usuario->es_activo ? 'Desactivar' : 'Activar' }}">
                                                <i class="fas {{ $usuario->es_activo ? 'fa-ban' : 'fa-check' }}"
                                                    id="toggle-icon-{{ $usuario->id }}"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay usuarios registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleStatus(userId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/configuracion/toggle-status/${userId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById(`status-badge-${userId}`);
                        const btn = document.getElementById(`toggle-btn-${userId}`);
                        const icon = document.getElementById(`toggle-icon-${userId}`);

                        if (data.es_activo) {
                            badge.className = 'badge bg-success';
                            badge.textContent = 'Activo';
                            btn.className = 'btn btn-sm btn-danger';
                            btn.title = 'Desactivar';
                            icon.className = 'fas fa-ban';
                        } else {
                            badge.className = 'badge bg-danger';
                            badge.textContent = 'Inactivo';
                            btn.className = 'btn btn-sm btn-success';
                            btn.title = 'Activar';
                            icon.className = 'fas fa-check';
                        }

                        window.showSnackbar('✅ ' + data.message, 'success');
                    } else {
                        window.showSnackbar('❌ ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    window.showSnackbar('❌ Error al cambiar estado', 'error');
                });
        }
    </script>
@endsection

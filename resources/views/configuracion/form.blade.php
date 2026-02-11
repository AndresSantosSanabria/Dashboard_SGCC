@extends('layouts.app')

@section('title', isset($usuario) ? 'Editar Usuario' : 'Crear Usuario')

@section('page-content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-{{ isset($usuario) ? 'edit' : 'plus' }} me-2"></i>
                {{ isset($usuario) ? 'Editar Usuario' : 'Crear Usuario' }}
            </h1>
            <a href="{{ route('configuracion.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <form
                    action="{{ isset($usuario) ? route('configuracion.update', $usuario->id) : route('configuracion.store') }}"
                    method="POST">
                    @csrf
                    @if (isset($usuario))
                        @method('PUT')
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="primer_nombre" class="form-label">Primer Nombre <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('primer_nombre') is-invalid @enderror"
                                id="primer_nombre" name="primer_nombre"
                                value="{{ old('primer_nombre', $usuario->primer_nombre ?? '') }}" required>
                            @error('primer_nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
                            <input type="text" class="form-control @error('segundo_nombre') is-invalid @enderror"
                                id="segundo_nombre" name="segundo_nombre"
                                value="{{ old('segundo_nombre', $usuario->segundo_nombre ?? '') }}">
                            @error('segundo_nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="primer_apellido" class="form-label">Primer Apellido <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('primer_apellido') is-invalid @enderror"
                                id="primer_apellido" name="primer_apellido"
                                value="{{ old('primer_apellido', $usuario->primer_apellido ?? '') }}" required>
                            @error('primer_apellido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="segundo_apellido" class="form-label">Segundo Apellido</label>
                            <input type="text" class="form-control @error('segundo_apellido') is-invalid @enderror"
                                id="segundo_apellido" name="segundo_apellido"
                                value="{{ old('segundo_apellido', $usuario->segundo_apellido ?? '') }}">
                            @error('segundo_apellido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="user" class="form-label">Usuario (Login) <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('user') is-invalid @enderror" id="user"
                                name="user" value="{{ old('user', $usuario->user ?? '') }}" required>
                            @error('user')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="rol_id" class="form-label">Rol <span class="text-danger">*</span></label>
                            <select class="form-select @error('rol_id') is-invalid @enderror" id="rol_id" name="rol_id"
                                required onchange="showRolePermissions()">
                                <option value="">-- Seleccione un rol --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" data-permisos="{{ json_encode($role->permisos) }}"
                                        {{ old('rol_id', $usuario->rol_id ?? '') == $role->id ? 'selected' : '' }}>
                                        {{ $role->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('rol_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3" id="permissions-display" style="display: none;">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Permisos del Rol</h6>
                                <div id="permissions-list"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                Contraseña
                                @if (!isset($usuario))
                                    <span class="text-danger">*</span>
                                @else
                                    <small class="text-muted">(Dejar en blanco para mantener la actual)</small>
                                @endif
                            </label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password" {{ !isset($usuario) ? 'required' : '' }}>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">
                                Confirmar Contraseña
                                @if (!isset($usuario))
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation" {{ !isset($usuario) ? 'required' : '' }}>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('configuracion.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>{{ isset($usuario) ? 'Actualizar' : 'Crear' }} Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showRolePermissions() {
            const select = document.getElementById('rol_id');
            const selectedOption = select.options[select.selectedIndex];
            const permisosDisplay = document.getElementById('permissions-display');
            const permisosList = document.getElementById('permissions-list');

            if (selectedOption.value) {
                const permisos = JSON.parse(selectedOption.dataset.permisos || '{}');
                let html = '<ul class="mb-0">';

                const permissionLabels = {
                    'es_admin': 'Administrador del Sistema',
                    'acceder_dashboard': 'Acceso al Dashboard',
                    'acceder_workflow': 'Acceso al Workflow',
                    'responsable_sap': 'Puede ser Responsable SAP',
                    'responsable_facturacion': 'Puede ser Responsable Facturación',
                    'contratos_ver': 'Ver Contratos',
                    'contratos_editar': 'Editar Contratos',
                    'cuentas_ver': 'Ver Cuentas',
                    'cuentas_editar': 'Editar Cuentas',
                    'usuarios_gestionar': 'Gestionar Usuarios',
                    'configuracion_sistema': 'Configuración del Sistema'
                };

                for (const [key, value] of Object.entries(permisos)) {
                    if (value) {
                        html += `<li><i class="fas fa-check text-success me-2"></i>${permissionLabels[key] || key}</li>`;
                    }
                }

                html += '</ul>';
                permisosList.innerHTML = html;
                permisosDisplay.style.display = 'block';
            } else {
                permisosDisplay.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            showRolePermissions();
        });
    </script>
@endsection

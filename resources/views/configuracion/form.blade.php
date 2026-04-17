@extends('layouts.app')

@section('title', isset($usuario) ? 'Editar Usuario — SGCC' : 'Crear Usuario — SGCC')

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
                    <i class="bi bi-person-{{ isset($usuario) ? 'gear' : 'plus' }}-fill"></i>
                    {{ isset($usuario) ? 'Editar Usuario' : 'Crear Usuario' }}
                </h1>
                <p class="text-muted mb-0">Configure los datos básicos y privilegios de acceso al sistema</p>
            </div>
            <div>
                <a href="{{ route('configuracion.index') }}" class="btn-premium btn-premium-dark">
                    <i class="bi bi-arrow-left"></i> Volver al Listado
                </a>
            </div>
        </header>

        <div class="premium-card">
            <div class="card-body p-4 p-md-5">
                <form
                    action="{{ isset($usuario) ? route('configuracion.update', $usuario->id) : route('configuracion.store') }}"
                    method="POST">
                    @csrf
                    @if (isset($usuario))
                        @method('PUT')
                    @endif

                    <div class="row g-4">
                        <div class="col-md-3">
                            <label for="primer_nombre" class="premium-label">Primer Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="premium-input @error('primer_nombre') is-invalid @enderror"
                                id="primer_nombre" name="primer_nombre"
                                value="{{ old('primer_nombre', $usuario->primer_nombre ?? '') }}" required placeholder="Ej: Juan">
                            @error('primer_nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="segundo_nombre" class="premium-label">Segundo Nombre</label>
                            <input type="text" class="premium-input @error('segundo_nombre') is-invalid @enderror"
                                id="segundo_nombre" name="segundo_nombre"
                                value="{{ old('segundo_nombre', $usuario->segundo_nombre ?? '') }}" placeholder="Opcional">
                            @error('segundo_nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="primer_apellido" class="premium-label">Primer Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="premium-input @error('primer_apellido') is-invalid @enderror"
                                id="primer_apellido" name="primer_apellido"
                                value="{{ old('primer_apellido', $usuario->primer_apellido ?? '') }}" required placeholder="Ej: Pérez">
                            @error('primer_apellido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="segundo_apellido" class="premium-label">Segundo Apellido</label>
                            <input type="text" class="premium-input @error('segundo_apellido') is-invalid @enderror"
                                id="segundo_apellido" name="segundo_apellido"
                                value="{{ old('segundo_apellido', $usuario->segundo_apellido ?? '') }}" placeholder="Opcional">
                            @error('segundo_apellido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-md-6">
                            <label for="user" class="premium-label">Nombre de Usuario (Login) <span class="text-danger">*</span></label>
                            <input type="text" class="premium-input @error('user') is-invalid @enderror" id="user"
                                name="user" value="{{ old('user', $usuario->user ?? '') }}" required 
                                placeholder="nombre.apellido">
                            @error('user')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="rol_id" class="premium-label">Rol Asignado <span class="text-danger">*</span></label>
                            <select class="premium-select @error('rol_id') is-invalid @enderror" id="rol_id" name="rol_id"
                                required onchange="showRolePermissions()">
                                <option value="">-- Seleccione un rol --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" data-permisos="{{ json_encode($role->lista_permisos) }}"
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

                    {{-- PERMISOS DEL ROL (INFO BOX) --}}
                    <div class="mt-5" id="permissions-display" style="display: none;">
                        <div class="premium-perm-box animate-fadeIn">
                            <h6><i class="bi bi-shield-lock-fill me-2"></i> Privilegios Heredados del Rol</h6>
                            <div id="permissions-list" class="premium-perm-list"></div>
                        </div>
                    </div>

                    <!-- NEW: Modules Access Matrix -->
                    <div class="mt-5 mb-5" id="modules-matrix-row" style="display: none;">
                        <hr class="mb-4 opacity-25">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary text-white p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">Módulos y Acciones del Usuario</h5>
                                <p class="text-muted small mb-0">Personalice los permisos específicos para este usuario</p>
                            </div>
                        </div>
                        
                        <div class="matrix-table shadow-sm">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr class="text-center">
                                        <th class="text-start ps-4" style="width: 35%">Módulo</th>
                                        <th style="width: 22%">Visualizar</th>
                                        <th style="width: 22%">Gestionar / Editar</th>
                                        <th style="width: 21%">Restricciones Especiales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dashboard -->
                                    <tr>
                                        <td class="fw-bold ps-4"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard Ejecutivo</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox"
                                                    id="mod_dashboard_view" onchange="togglePermission('acceder_dashboard', this.checked)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox"
                                                    id="mod_dashboard_edit" onchange="togglePermission('editar_dashboard', this.checked)">
                                            </div>
                                        </td>
                                        <td class="text-center small">
                                            <div class="form-check d-inline-block">
                                                <input type="checkbox" class="form-check-input"
                                                    id="res_consolidado" name="permisos[acceder_consolidado]" value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['acceder_consolidado'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="res_consolidado">Solo Lectura</label>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Workflow -->
                                    <tr>
                                        <td class="fw-bold ps-4"><i class="bi bi-diagram-3 me-2 text-primary"></i>Flujo de Trabajo (Workflow)</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox"
                                                    id="mod_workflow_view" onchange="togglePermission('acceder_workflow', this.checked)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox"
                                                    id="mod_workflow_edit" onchange="togglePermission('editar_workflow', this.checked)">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="px-3">
                                                <div class="form-check mb-1">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="res_solo_asignados" name="permisos[ver_solo_asignados]" value="1"
                                                        {{ isset($usuario) && ($usuario->permisos['ver_solo_asignados'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="res_solo_asignados">Solo asignados</label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="res_solo_bloques" name="permisos[ver_solo_bloques_con_asignacion]" value="1"
                                                        {{ isset($usuario) && ($usuario->permisos['ver_solo_bloques_con_asignacion'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="res_solo_bloques">Solo bloques con cargue</label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Contracts -->
                                    <tr>
                                        <td class="fw-bold ps-4"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Contratos SECOP</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox"
                                                    id="mod_contracts_view" onchange="togglePermission('contratos_ver', this.checked)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox"
                                                    id="mod_contracts_edit" onchange="togglePermission('contratos_editar', this.checked)">
                                            </div>
                                        </td>
                                        <td class="text-center small">-</td>
                                    </tr>

                                    <!-- Accounts -->
                                    <tr>
                                        <td class="fw-bold ps-4"><i class="bi bi-currency-dollar me-2 text-primary"></i>Cuentas de Cobro</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox"
                                                    id="mod_accounts_view" onchange="togglePermission('cuentas_ver', this.checked)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox"
                                                    id="mod_accounts_edit" onchange="togglePermission('cuentas_editar', this.checked)">
                                            </div>
                                        </td>
                                        <td class="text-center small">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- PASSWORD SECTION --}}
                    <div class="row g-4 mt-4">
                        <div class="col-md-6">
                            <label for="password" class="premium-label">
                                Contraseña
                                @if (!isset($usuario))
                                    <span class="text-danger">*</span>
                                @else
                                    <small class="text-muted">(Dejar en blanco para mantener la actual)</small>
                                @endif
                            </label>
                            <input type="password" class="premium-input @error('password') is-invalid @enderror"
                                id="password" name="password" {{ !isset($usuario) ? 'required' : '' }} placeholder="********">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="premium-label">
                                Confirmar Contraseña
                                @if (!isset($usuario))
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <input type="password" class="premium-input" id="password_confirmation"
                                name="password_confirmation" {{ !isset($usuario) ? 'required' : '' }} placeholder="Reingrese contraseña">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-5 border-top pt-4">
                        <a href="{{ route('configuracion.index') }}" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 12px;">
                            Cancelar
                        </a>
                        <button type="submit" class="btn-premium btn-premium-primary px-5">
                            <i class="bi bi-{{ isset($usuario) ? 'save' : 'check-circle' }}"></i>
                            {{ isset($usuario) ? 'Actualizar Usuario' : 'Crear Usuario' }}
                        </button>
                    </div>

                    {{-- HIDDEN PERMISSIONS FOR PERSONALIZED --}}
                    <div id="individual-permissions-row" style="display: none;">
                        @foreach(['acceder_dashboard', 'editar_dashboard', 'acceder_consolidado', 'acceder_workflow', 'editar_workflow', 'contratos_ver', 'contratos_editar', 'cuentas_ver', 'cuentas_editar'] as $perm)
                            <input type="checkbox" name="permisos[{{$perm}}]" id="permiso_{{$perm}}" value="1" 
                            style="display:none;" {{ isset($usuario) && ($usuario->permisos[$perm] ?? false) ? 'checked' : '' }}>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const permissionMap = {
            'acceder_dashboard': 'permiso_dashboard',
            'editar_dashboard': 'permiso_editar_dashboard',
            'acceder_consolidado': 'permiso_consolidado',
            'acceder_workflow': 'permiso_workflow',
            'editar_workflow': 'permiso_editar_workflow',
            'contratos_ver': 'permiso_contratos_ver',
            'contratos_editar': 'permiso_contratos_editar',
            'cuentas_ver': 'permiso_cuentas_ver',
            'cuentas_editar': 'permiso_cuentas_editar'
        };

        function togglePermission(permission, checked) {
            const checkboxId = permissionMap[permission];
            if (checkboxId) {
                const checkbox = document.getElementById(checkboxId);
                if (checkbox) {
                    checkbox.checked = checked;
                    if (checked && permission.includes('editar')) {
                        const viewPerm = permission.replace('editar_', 'acceder_').replace('_editar', '_ver');
                        const viewCheckboxId = permissionMap[viewPerm];
                        if (viewCheckboxId) {
                            const vCheckbox = document.getElementById(viewCheckboxId);
                            if (vCheckbox) {
                                vCheckbox.checked = true;
                                syncToMatrix();
                            }
                        }
                    }
                }
            }
        }

        function syncToMatrix() {
            document.getElementById('mod_dashboard_view').checked = document.getElementById('permiso_acceder_dashboard').checked;
            document.getElementById('mod_dashboard_edit').checked = document.getElementById('permiso_editar_dashboard').checked;
            document.getElementById('mod_workflow_view').checked = document.getElementById('permiso_acceder_workflow').checked;
            document.getElementById('mod_workflow_edit').checked = document.getElementById('permiso_editar_workflow').checked;
            document.getElementById('mod_contracts_view').checked = document.getElementById('permiso_contratos_ver').checked;
            document.getElementById('mod_contracts_edit').checked = document.getElementById('permiso_contratos_editar').checked;
            document.getElementById('mod_accounts_view').checked = document.getElementById('permiso_cuentas_ver').checked;
            document.getElementById('mod_accounts_edit').checked = document.getElementById('permiso_cuentas_editar').checked;
        }

        function showRolePermissions() {
            const select = document.getElementById('rol_id');
            const selectedOption = select.options[select.selectedIndex];
            const permissionsDisplay = document.getElementById('permissions-display');
            const permissionsList = document.getElementById('permissions-list');
            const modulesMatrixRow = document.getElementById('modules-matrix-row');

            if (selectedOption.value) {
                const isPersonalizado = selectedOption.text.trim().toLowerCase().includes('personalizado');
                permissionsDisplay.style.display = isPersonalizado ? 'none' : 'block';
                modulesMatrixRow.style.display = isPersonalizado ? 'block' : 'none';

                if (!isPersonalizado) {
                    const permisos = JSON.parse(selectedOption.dataset.permisos || '{}');
                    let html = '';
                    const labels = {
                        'es_admin': 'Administrador del Sistema',
                        'acceder_dashboard': 'Acceso al Dashboard',
                        'acceder_workflow': 'Acceso al Workflow',
                        'acceder_consolidado': 'Consultar Consolidado',
                        'editar_dashboard': 'Editar Registros',
                        'editar_workflow': 'Mover en Workflow',
                        'contratos_ver': 'Ver Contratos',
                        'contratos_editar': 'Editar Contratos',
                        'cuentas_ver': 'Ver Cuentas',
                        'cuentas_editar': 'Editar Cuentas',
                        'usuarios_gestionar': 'Gestionar Usuarios'
                    };

                    for (const [key, value] of Object.entries(permisos)) {
                        if (value) {
                            html += `<div class="premium-perm-item"><i class="bi bi-check2-circle text-success"></i> ${labels[key] || key}</div>`;
                        }
                    }
                    permissionsList.innerHTML = html;
                } else {
                    syncToMatrix();
                }
            } else {
                permissionsDisplay.style.display = 'none';
                modulesMatrixRow.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            showRolePermissions();
            
            @if(isset($usuario))
                syncToMatrix();
                // Special for consolidado matrix link
                document.getElementById('mod_consolidado_view').checked = document.getElementById('permiso_acceder_consolidado').checked;
            @endif
        });
    </script>
@endsection


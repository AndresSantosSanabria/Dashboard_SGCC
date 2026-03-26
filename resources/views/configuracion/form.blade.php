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

                    <!-- NEW: Modules Access Matrix -->
                    <div class="row mb-4" id="modules-matrix-row" style="display: none;">
                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3 text-primary"><i class="fas fa-th me-2"></i>Módulos y Acciones Disponibles</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th style="width: 35%">Módulo</th>
                                            <th style="width: 22%">Ver</th>
                                            <th style="width: 22%">Editar</th>
                                            <th style="width: 21%">Restricciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dashboard -->
                                        <tr>
                                            <td class="fw-bold"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</td>
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
                                                <div class="form-check mb-1">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="res_consolidado" name="permisos[acceder_consolidado]" value="1"
                                                        {{ isset($usuario) && ($usuario->permisos['acceder_consolidado'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="res_consolidado">Solo lectura</label>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Workflow -->
                                        <tr>
                                            <td class="fw-bold"><i class="fas fa-project-diagram me-2"></i>Workflow</td>
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
                                            <td class="text-center small">
                                                <div class="form-check mb-1">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="res_solo_asignados" name="permisos[ver_solo_asignados]" value="1"
                                                        {{ isset($usuario) && ($usuario->permisos['ver_solo_asignados'] ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="res_solo_asignados">Solo asignados</label>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Consolidado View -->
                                        <tr>
                                            <td class="fw-bold"><i class="fas fa-eye me-2"></i>Consolidado</td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="mod_consolidado_view" onchange="togglePermission('acceder_consolidado', this.checked)">
                                                </div>
                                            </td>
                                            <td class="text-center bg-light">
                                                <small class="text-muted">N/A</small>
                                            </td>
                                            <td class="text-center small">-</td>
                                        </tr>

                                        <!-- Contracts -->
                                        <tr>
                                            <td class="fw-bold"><i class="fas fa-file-contract me-2"></i>Contratos</td>
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
                                            <td class="fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Cuentas de Cobro</td>
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

                                        <!-- Users & Roles -->
                                        <tr>
                                            <td class="fw-bold"><i class="fas fa-users me-2"></i>Usuarios y Roles</td>
                                            <td class="text-center bg-light">
                                                <small class="text-muted">Admin</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="mod_users_edit" name="permisos[usuarios_gestionar]" value="1"
                                                        {{ isset($usuario) && ($usuario->permisos['usuarios_gestionar'] ?? false) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center small">-</td>
                                        </tr>

                                        <!-- Reports -->
                                        <tr>
                                            <td class="fw-bold"><i class="fas fa-chart-line me-2"></i>Reportes</td>
                                            <td class="text-center">
                                                <small class="text-muted">Visualizar</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="mod_reports_export" name="permisos[reportes_exportar]" value="1"
                                                        {{ isset($usuario) && ($usuario->permisos['reportes_exportar'] ?? false) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center small">Exportar</td>
                                        </tr>

                                        <!-- Responsables -->
                                        <tr class="table-info">
                                            <td class="fw-bold"><i class="fas fa-user-check me-2"></i>Responsables</td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="mod_resp_sap" name="permisos[responsable_sap]" value="1"
                                                        {{ isset($usuario) && ($usuario->permisos['responsable_sap'] ?? false) ? 'checked' : '' }}
                                                        title="Puede ser asignado como responsable en bloque SAP">
                                                </div>
                                                <small class="text-muted d-block">SAP</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="mod_resp_fac" name="permisos[responsable_facturacion]" value="1"
                                                        {{ isset($usuario) && ($usuario->permisos['responsable_facturacion'] ?? false) ? 'checked' : '' }}
                                                        title="Puede ser asignado como responsable en bloque de Facturación">
                                                </div>
                                                <small class="text-muted d-block">Facturación</small>
                                            </td>
                                            <td class="text-center small">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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

                    <div class="row mb-4" id="individual-permissions-row" style="display: none;">
                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3 text-primary"><i class="fas fa-user-shield me-2"></i>Permisos Individuales
                                Configurados</h5>

                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Accesos Generales -->
                                        <div class="col-md-4 mb-3">
                                            <h6 class="fw-bold border-bottom pb-2">Accesos Generales</h6>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="permisos[acceder_dashboard]" id="permiso_dashboard" value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['acceder_dashboard'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="permiso_dashboard"
                                                    title="Permite acceder a la vista general de cuentas y realizar acciones de gestión (importar, crear, editar) si no tiene restricciones adicionales.">Ver
                                                    Dashboard (Gesti&oacute;n)</label>
                                            </div>
                                            <div class="form-check mb-2 ps-4">
                                                <input class="form-check-input" type="checkbox"
                                                    name="permisos[editar_dashboard]" id="permiso_editar_dashboard"
                                                    value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['editar_dashboard'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="permiso_editar_dashboard"
                                                    title="Permite realizar cargas masivas, manuales y editar registros existentes en el dashboard.">Puede
                                                    Editar Dashboard (Gestión)</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="permisos[acceder_consolidado]" id="permiso_consolidado"
                                                    value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['acceder_consolidado'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="permiso_consolidado"
                                                    title="Vista de solo lectura del dashboard. Útil para usuarios que solo necesitan consultar información sin modificarla.">Ver
                                                    Consolidado (Solo lectura)</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="permisos[acceder_workflow]" id="permiso_workflow"
                                                    value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['acceder_workflow'] ?? false) ? 'checked' : '' }}
                                                    onchange="toggleWorkflowSettings()">
                                                <label class="form-check-label" for="permiso_workflow"
                                                    title="Permite visualizar el tablero Kanban del flujo de trabajo.">Ver
                                                    Workflow</label>
                                            </div>
                                            <div class="form-check mb-2 ps-4 workflow-setting">
                                                <input class="form-check-input" type="checkbox"
                                                    name="permisos[editar_workflow]" id="permiso_editar_workflow"
                                                    value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['editar_workflow'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="permiso_editar_workflow"
                                                    title="Habilita la capacidad de mover cuentas entre estados y realizar transiciones en el workflow.">Puede
                                                    mover cuentas (Editar)</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="permisos[es_admin]"
                                                    id="permiso_admin" value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['es_admin'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="permiso_admin"
                                                    title="Otorga acceso total al sistema, incluyendo configuración de usuarios y roles, saltando cualquier restricción individual.">Administrador</label>
                                            </div>
                                        </div>

                                        <!-- Restricciones Workflow -->
                                        <div class="col-md-4 mb-3 workflow-setting">
                                            <h6 class="fw-bold border-bottom pb-2">Restricciones Workflow</h6>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="permisos[ver_solo_asignados]" id="permiso_solo_asignados"
                                                    value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['ver_solo_asignados'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label text-danger fw-bold"
                                                    for="permiso_solo_asignados"
                                                    title="Restringe la visibilidad en Dashboard y Workflow para que el usuario solo vea las cuentas donde figura como responsable.">Ver
                                                    solo cuentas asignadas</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="permisos[responsable_sap]" id="permiso_sap" value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['responsable_sap'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="permiso_sap"
                                                    title="Permite que el usuario sea seleccionado como responsable en el bloque de Ingreso a Mercancía (SAP).">Puede
                                                    ser Responsable
                                                    SAP</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    name="permisos[responsable_facturacion]" id="permiso_facturacion"
                                                    value="1"
                                                    {{ isset($usuario) && ($usuario->permisos['responsable_facturacion'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="permiso_facturacion"
                                                    title="Permite que el usuario sea seleccionado como responsable en el bloque de Facturación.">Puede
                                                    ser
                                                    Responsable Facturación</label>
                                            </div>
                                        </div>

                                        <!-- Bloques Permitidos -->
                                        <div class="col-md-4 mb-3 workflow-setting">
                                            <h6 class="fw-bold border-bottom pb-2">Bloques Visibles/Editables</h6>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="bloques_all"
                                                    id="bloques_all" value="1"
                                                    {{ !isset($usuario) || ($usuario->permisos['bloques_permitidos'] ?? true) === true ? 'checked' : '' }}
                                                    onchange="toggleBloquesSelection()">
                                                <label class="form-check-label fw-bold" for="bloques_all">Todos los
                                                    bloques</label>
                                            </div>
                                            <div id="bloques_selection"
                                                style="{{ !isset($usuario) || ($usuario->permisos['bloques_permitidos'] ?? true) === true ? 'display: none;' : '' }}">
                                                <p class="small text-muted mb-2">Seleccione los bloques específicos:</p>
                                                @foreach ($bloques as $bloque)
                                                    <div class="form-check mb-1 ms-3">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="bloques_permitidos[]" id="bloque_{{ $bloque->codigo }}"
                                                            value="{{ $bloque->codigo }}"
                                                            {{ isset($usuario) && is_array($usuario->permisos['bloques_permitidos'] ?? null) && in_array($bloque->codigo, $usuario->permisos['bloques_permitidos']) ? 'checked' : '' }}>
                                                        <label class="form-check-label small"
                                                            for="bloque_{{ $bloque->codigo }}">
                                                            {{ $bloque->nombre }} ({{ $bloque->codigo }})
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="mb-4">
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

                    <!-- Hidden fields for module matrix permisos -->
                    <input type="hidden" id="hidden_contratos_ver" name="permisos[contratos_ver]" value="0">
                    <input type="hidden" id="hidden_contratos_editar" name="permisos[contratos_editar]" value="0">
                    <input type="hidden" id="hidden_cuentas_ver" name="permisos[cuentas_ver]" value="0">
                    <input type="hidden" id="hidden_cuentas_editar" name="permisos[cuentas_editar]" value="0">
                </form>
            </div>
        </div>
    </div>

    <script>
        // Map module permissions to actual permission checkboxes
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
                    
                    // Update hidden fields for module matrix
                    updateModuleHiddenFields();
                    
                    // If checking edit, ensure view is checked too
                    if (checked && permission.includes('editar')) {
                        const viewPerm = permission.replace('editar_', 'acceder_');
                        const viewCheckboxId = permissionMap[viewPerm];
                        if (viewCheckboxId) {
                            const viewCheckbox = document.getElementById(viewCheckboxId);
                            if (viewCheckbox) viewCheckbox.checked = true;
                        }
                    }
                }
            }
        }

        function updateModuleHiddenFields() {
            // Update hidden fields based on module matrix checkboxes
            document.getElementById('hidden_contratos_ver').value = document.getElementById('mod_contracts_view').checked ? '1' : '0';
            document.getElementById('hidden_contratos_editar').value = document.getElementById('mod_contracts_edit').checked ? '1' : '0';
            document.getElementById('hidden_cuentas_ver').value = document.getElementById('mod_accounts_view').checked ? '1' : '0';
            document.getElementById('hidden_cuentas_editar').value = document.getElementById('mod_accounts_edit').checked ? '1' : '0';
        }

        function showRolePermissions() {
            const select = document.getElementById('rol_id');
            const selectedOption = select.options[select.selectedIndex];
            const permissionsDisplay = document.getElementById('permissions-display');
            const permissionsList = document.getElementById('permissions-list');
            const individualRow = document.getElementById('individual-permissions-row');
            const modulesMatrixRow = document.getElementById('modules-matrix-row');

            if (selectedOption.value) {
                const isPersonalizado = selectedOption.text.trim().toLowerCase().includes('personalizado');

                // Show blue box ONLY if NOT personalizado
                permissionsDisplay.style.display = isPersonalizado ? 'none' : 'block';

                // Show individual permissions and matrix ONLY if personalizado
                individualRow.style.display = isPersonalizado ? 'block' : 'none';
                modulesMatrixRow.style.display = isPersonalizado ? 'block' : 'none';

                if (!isPersonalizado) {
                    const permisos = JSON.parse(selectedOption.dataset.permisos || '{}');
                    let html = '<ul class="mb-0">';

                    const permissionLabels = {
                        'es_admin': 'Administrador del Sistema',
                        'acceder_dashboard': 'Acceso al Dashboard',
                        'acceder_workflow': 'Acceso al Workflow',
                        'acceder_consolidado': 'Acceso al Consolidado',
                        'editar_dashboard': 'Editar Dashboard',
                        'editar_workflow': 'Editar Workflow',
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
                            html +=
                                `<li><i class="fas fa-check text-success me-2"></i>${permissionLabels[key] || key}</li>`;
                        }
                    }

                    html += '</ul>';
                    permissionsList.innerHTML = html;
                } else {
                    // Load module matrix for personalizado roles
                    loadModuleMatrix(selectedOption.dataset.permisos);
                }
            } else {
                permissionsDisplay.style.display = 'none';
                individualRow.style.display = 'none';
                modulesMatrixRow.style.display = 'none';
            }
        }

        function loadModuleMatrix(permisosJson) {
            const permisos = JSON.parse(permisosJson || '{}');
            
            // Dashboard
            document.getElementById('mod_dashboard_view').checked = permisos.acceder_dashboard || false;
            document.getElementById('mod_dashboard_edit').checked = permisos.editar_dashboard || false;
            
            // Workflow
            document.getElementById('mod_workflow_view').checked = permisos.acceder_workflow || false;
            document.getElementById('mod_workflow_edit').checked = permisos.editar_workflow || false;
            
            // Consolidado
            document.getElementById('mod_consolidado_view').checked = permisos.acceder_consolidado || false;
            
            // Contracts
            document.getElementById('mod_contracts_view').checked = permisos.contratos_ver || false;
            document.getElementById('mod_contracts_edit').checked = permisos.contratos_editar || false;
            
            // Accounts
            document.getElementById('mod_accounts_view').checked = permisos.cuentas_ver || false;
            document.getElementById('mod_accounts_edit').checked = permisos.cuentas_editar || false;
        }

        function toggleWorkflowSettings() {
            const workflowCheckbox = document.getElementById('permiso_workflow');
            const workflowSettings = document.querySelectorAll('.workflow-setting');

            workflowSettings.forEach(el => {
                el.style.display = workflowCheckbox.checked ? 'block' : 'none';
            });
        }

        function toggleBloquesSelection() {
            const allCheckbox = document.getElementById('bloques_all');
            const selectionDiv = document.getElementById('bloques_selection');
            if (selectionDiv) {
                selectionDiv.style.display = allCheckbox.checked ? 'none' : 'block';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            showRolePermissions();
            toggleWorkflowSettings();
            toggleBloquesSelection();

            // Intercept form submission to sync module matrix
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    updateModuleHiddenFields();
                });
            }

            // Sync module matrix with individual permissions on page load for existing users
            @if(isset($usuario))
                const usuarioPermisos = {!! json_encode($usuario->permisos ?? []) !!};
                
                // Update module matrix from user permissions
                if (Object.keys(usuarioPermisos).length > 0) {
                    document.getElementById('mod_dashboard_view').checked = usuarioPermisos.acceder_dashboard || false;
                    document.getElementById('mod_dashboard_edit').checked = usuarioPermisos.editar_dashboard || false;
                    document.getElementById('mod_workflow_view').checked = usuarioPermisos.acceder_workflow || false;
                    document.getElementById('mod_workflow_edit').checked = usuarioPermisos.editar_workflow || false;
                    document.getElementById('mod_consolidado_view').checked = usuarioPermisos.acceder_consolidado || false;
                    document.getElementById('mod_contracts_view').checked = usuarioPermisos.contratos_ver || false;
                    document.getElementById('mod_contracts_edit').checked = usuarioPermisos.contratos_editar || false;
                    document.getElementById('mod_accounts_view').checked = usuarioPermisos.cuentas_ver || false;
                    document.getElementById('mod_accounts_edit').checked = usuarioPermisos.cuentas_editar || false;
                    
                    // Sync hidden fields
                    updateModuleHiddenFields();
                }
            @endif
        });
    </script>
@endsection

@extends('layouts.app')

@section('title', 'Crear Rol Personalizado')

@section('page-content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-tag me-2"></i>Crear Rol Personalizado
            </h1>
            <a href="{{ route('configuracion.roles.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Por favor corrige los siguientes errores:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-body">
                <form action="{{ route('configuracion.roles.store') }}" method="POST">
                    @csrf

                    <!-- Basic Info -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre del Rol <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre"
                                name="nombre" value="{{ old('nombre') }}" required placeholder="Ej: Auditor Financiero">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="1"
                                placeholder="Breve descripción del propósito del rol">{{ old('descripcion') }}</textarea>
                        </div>
                    </div>

                    <!-- Data Restrictions -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-filter me-2"></i>Restricciones de Datos y
                                Workflow</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="ver_solo_asignados"
                                            id="ver_solo_asignados" value="1">
                                        <label class="form-check-label fw-bold" for="ver_solo_asignados">
                                            Ver solo mis asignaciones
                                        </label>
                                        <small class="d-block text-muted">
                                            Si se activa, el usuario solo verá las cuentas donde es responsable actual.
                                        </small>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="es_responsable_sap"
                                            id="es_responsable_sap" value="1">
                                        <label class="form-check-label fw-bold" for="es_responsable_sap">
                                            Es Responsable SAP
                                        </label>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="es_responsable_facturacion"
                                            id="es_responsable_facturacion" value="1">
                                        <label class="form-check-label fw-bold" for="es_responsable_facturacion">
                                            Es Responsable Facturación
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold mb-2">Bloques del Workflow permitidos</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="bloques_all" id="bloques_all"
                                            value="1" onchange="toggleBloquesSelection()">
                                        <label class="form-check-label" for="bloques_all">Todos los bloques (Sin
                                            restricción)</label>
                                    </div>

                                    <div id="bloques_selection" class="card card-body p-2"
                                        style="max-height: 150px; overflow-y: auto;">
                                        @foreach ($bloques as $bloque)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="bloques_permitidos[]"
                                                    value="{{ $bloque->codigo }}" id="bloque_{{ $bloque->codigo }}">
                                                <label class="form-check-label small" for="bloque_{{ $bloque->codigo }}">
                                                    {{ $bloque->nombre }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Matrix -->
                    <h5 class="mb-3 text-primary"><i class="fas fa-shield-alt me-2"></i>Matriz de Permisos</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light text-center">
                                <tr>
                                    <th class="text-start" style="width: 30%">Módulo / Vista</th>
                                    <th style="width: 15%">Ver <br><small class="text-muted">(Lectura)</small></th>
                                    <th style="width: 15%">Crear <br><small class="text-muted">(Insertar)</small></th>
                                    <th style="width: 15%">Editar <br><small class="text-muted">(Actualizar)</small></th>
                                    <th style="width: 15%">Eliminar <br><small class="text-muted">(Borrar)</small></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dashboard -->
                                <tr data-module="dashboard">
                                    <td class="fw-bold">
                                        <i class="fas fa-tachometer-alt me-2 text-info"></i>Panel de Control (Dashboard)
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-view" type="checkbox"
                                                name="permisos_matrix[dashboard][view]" value="1">
                                        </div>
                                        <small class="d-block text-muted mt-1">Gestión</small>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-action" type="checkbox"
                                                name="permisos_matrix[dashboard][edit]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                </tr>
                                <!-- Consolidated View Extra Row -->
                                <tr data-module="dashboard_consolidado">
                                    <td class="ps-4 text-muted">
                                        <i class="fas fa-eye me-2"></i>Vista Consolidada (Solo Lectura)
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input" type="checkbox"
                                                name="permisos_matrix[dashboard][readonly]" value="1">
                                        </div>
                                    </td>
                                    <td colspan="3" class="bg-light"></td>
                                </tr>


                                <!-- Users -->
                                <tr data-module="users">
                                    <td class="fw-bold">
                                        <i class="fas fa-users me-2 text-info"></i>Usuarios y Roles
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-view" type="checkbox"
                                                name="permisos_matrix[users][view]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-action" type="checkbox"
                                                name="permisos_matrix[users][create]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-action" type="checkbox"
                                                name="permisos_matrix[users][edit]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-action" type="checkbox"
                                                name="permisos_matrix[users][delete]" value="1">
                                        </div>
                                    </td>
                                </tr>

                                <!-- Workflow -->
                                <tr data-module="workflow">
                                    <td class="fw-bold">
                                        <i class="fas fa-project-diagram me-2 text-info"></i>Workflow
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-view" type="checkbox"
                                                name="permisos_matrix[workflow][view]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-action" type="checkbox"
                                                name="permisos_matrix[workflow][edit]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                </tr>

                                <!-- Contracts / Expedientes -->
                                <tr data-module="seguimiento">
                                    <td class="fw-bold">
                                         <i class="fas fa-file-contract me-2 text-info"></i>Gestión de Expedientes y Cuentas de Cobro
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-view" type="checkbox"
                                                name="permisos_matrix[seguimiento][view]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-action" type="checkbox"
                                                name="permisos_matrix[seguimiento][edit]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                </tr>

                                <!-- Seguimiento SECOP (SIA OBSERVA) -->
                                <tr data-module="seguimiento_secop">
                                    <td class="fw-bold">
                                        Seguimiento SECOP (SIA OBSERVA)
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-view" type="checkbox"
                                                name="permisos_matrix[seguimiento_secop][view]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-action" type="checkbox"
                                                name="permisos_matrix[seguimiento_secop][edit]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                </tr>

                                <!-- Reports -->
                                <tr data-module="reports">
                                    <td class="fw-bold">
                                        <i class="fas fa-chart-line me-2 text-info"></i>Reportes
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input" type="checkbox"
                                                name="permisos_matrix[reports][view]" value="1" disabled checked>
                                        </div>
                                        <small class="text-muted">Visualizar</small>
                                    </td>
                                    <td colspan="3" class="text-start ps-5">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox"
                                                name="permisos_matrix[reports][export]" value="1"
                                                id="export_reports">
                                            <label class="form-check-label" for="export_reports">Puede Exportar
                                                Excel</label>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Analitica -->
                                <tr data-module="analitica">
                                    <td class="fw-bold">
                                        <i class="fas fa-chart-bar me-2 text-info"></i>Analítica / Estadísticas
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-view" type="checkbox"
                                                name="permisos_matrix[analitica][view]" value="1">
                                        </div>
                                    </td>
                                    <td colspan="3" class="bg-light"></td>
                                </tr>

                                <!-- Configuración -->
                                <tr data-module="config">
                                    <td class="fw-bold">
                                        <i class="fas fa-cogs me-2 text-info"></i>Configuración del Sistema
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-view" type="checkbox"
                                                name="permisos_matrix[config][view]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input perm-action" type="checkbox"
                                                name="permisos_matrix[config][edit]" value="1">
                                        </div>
                                    </td>
                                    <td class="text-center bg-light">
                                        <small class="text-muted">N/A</small>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('configuracion.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear Rol
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleBloquesSelection() {
            const allCheckbox = document.getElementById('bloques_all');
            const selectionDiv = document.getElementById('bloques_selection');
            if (allCheckbox && selectionDiv) {
                if (allCheckbox.checked) {
                    selectionDiv.classList.add('d-none');
                } else {
                    selectionDiv.classList.remove('d-none');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tr[data-module]');

            rows.forEach(row => {
                const viewCheckbox = row.querySelector('.perm-view');
                const actionCheckboxes = row.querySelectorAll('.perm-action');

                if (viewCheckbox && actionCheckboxes.length > 0) {

                    // Initial State: Disable actions if view is unchecked
                    updateState(viewCheckbox, actionCheckboxes);

                    // Change on View Click
                    viewCheckbox.addEventListener('change', function() {
                        updateState(this, actionCheckboxes);
                        if (!this.checked) {
                            // Uncheck all actions if view is disabled
                            actionCheckboxes.forEach(cb => cb.checked = false);
                        }
                    });

                    // Change on Action Click
                    actionCheckboxes.forEach(actionCb => {
                        actionCb.addEventListener('change', function() {
                            if (this.checked) {
                                // If action checked, force view checked
                                viewCheckbox.checked = true;
                                updateState(viewCheckbox, actionCheckboxes);
                            }
                        });
                    });
                }
            });

            function updateState(viewCb, actionCbs) {
                if (!viewCb.checked) {
                    actionCbs.forEach(cb => {
                        // cb.disabled = true; // User requested "visual block", actually disabling is safer logic
                        // Provide visual feedback
                        cb.closest('.form-check').style.opacity = '0.5';
                    });
                    // Optional: Add a visual indication that the row is "off"
                    viewCb.closest('tr').classList.add('table-light'); // gray out slightly
                } else {
                    actionCbs.forEach(cb => {
                        // cb.disabled = false;
                        cb.closest('.form-check').style.opacity = '1';
                    });
                    viewCb.closest('tr').classList.remove('table-light');
                }
            }
        });

        // Trigger Snackbar for server-side messages
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success'))
                window.showSnackbar("{{ session('success') }}", 'success');
            @endif

            @if ($errors->any())
                let errorMsg = "Por favor corrija los errores en el formulario.";
                window.showSnackbar(errorMsg, 'error');
            @endif
        });
    </script>
@endsection

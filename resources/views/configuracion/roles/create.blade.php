@extends('layouts.app')

@section('title', 'Crear Rol Personalizado — SGCC')

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
                    <i class="bi bi-shield-plus"></i>
                    Crear Rol Personalizado
                </h1>
                <p class="text-muted mb-0">Defina un nuevo perfil de seguridad con permisos granulares</p>
            </div>
            <div>
                <a href="{{ route('configuracion.roles.index') }}" class="btn-premium btn-premium-dark">
                    <i class="bi bi-arrow-left"></i> Volver al Listado
                </a>
            </div>
        </header>

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4 animate-fadeIn" style="border-radius: 12px; border-left: 5px solid #ef4444 !important; background: white;">
                <div class="d-flex">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-4 me-3"></i>
                    <div>
                        <strong class="d-block text-danger">Revise los siguientes errores:</strong>
                        <ul class="mb-0 small text-muted ps-3 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="premium-card">
            <div class="card-body p-4 p-md-5">
                <form action="{{ route('configuracion.roles.store') }}" method="POST">
                    @csrf
                    
                    <input type="hidden" name="_update_restricciones" value="1">
                    <input type="hidden" name="_update_workflow" value="1">
                    <input type="hidden" name="_update_responsabilidades" value="1">
                    <input type="hidden" name="_update_matrix" value="1">

                    <!-- 1. INFORMACIÓN BÁSICA -->
                    <div class="row g-4 mb-5">
                        <div class="col-md-5">
                            <label for="nombre" class="premium-label">Nombre del Rol <span class="text-danger">*</span></label>
                            <input type="text" class="premium-input @error('nombre') is-invalid @enderror" id="nombre"
                                name="nombre" value="{{ old('nombre') }}" required placeholder="Ej: Auditor de Cuentas">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-7">
                            <label for="descripcion" class="premium-label">Descripción del Perfil</label>
                            <textarea class="premium-input" id="descripcion" name="descripcion" rows="1"
                                placeholder="Describa brevemente las responsabilidades de este rol" style="height: 46px;">{{ old('descripcion') }}</textarea>
                        </div>
                    </div>

                    <!-- 2. RESTRICCIONES Y WORKFLOW -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary text-white p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-filter-circle-fill"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Restricciones de Datos y Workflow</h5>
                        </div>

                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="bg-light p-4 rounded-4 h-100">
                                    <h6 class="fw-bold mb-3 text-main">Visibilidad de Información</h6>
                                    
                                    <div class="form-check mb-4 custom-check">
                                        <input class="form-check-input" type="checkbox" name="ver_solo_asignados"
                                            id="ver_solo_asignados" value="1" {{ old('ver_solo_asignados') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold d-block" for="ver_solo_asignados">
                                            Privacidad Estricta (Solo Asignados)
                                            <span class="d-block text-muted fw-normal extra-small">El usuario solo verá las cuentas donde es el responsable actual.</span>
                                        </label>
                                    </div>

                                    <div class="form-check mb-4 custom-check">
                                        <input class="form-check-input" type="checkbox" name="ver_solo_bloques_con_asignacion"
                                            id="ver_solo_bloques_con_asignacion" value="1"
                                            {{ old('ver_solo_bloques_con_asignacion') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold d-block" for="ver_solo_bloques_con_asignacion">
                                            Filtrado Automático de Columnas
                                            <span class="d-block text-muted fw-normal extra-small">Solo se mostrarán los bloques en los que el usuario tenga carga activa.</span>
                                        </label>
                                    </div>

                                    <div class="form-check mb-4 custom-check">
                                        <input class="form-check-input" type="checkbox" name="receptor_automatico_bloque_6"
                                            id="receptor_automatico_bloque_6" value="1"
                                            {{ old('receptor_automatico_bloque_6') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold d-block" for="receptor_automatico_bloque_6">
                                            Balanceo Automático (Bloque 6)
                                            <span class="d-block text-muted fw-normal extra-small">Incluir a este perfil en la distribución automática de cuentas.</span>
                                        </label>
                                    </div>

                                    <div class="form-check mb-4 custom-check">
                                        <input class="form-check-input" type="checkbox" name="acceder_notificaciones"
                                            id="acceder_notificaciones" value="1"
                                            {{ old('acceder_notificaciones', '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold d-block" for="acceder_notificaciones">
                                            Panel de Notificaciones Activo
                                            <span class="d-block text-muted fw-normal extra-small">Habilita la campana de alertas y el centro de mensajes.</span>
                                        </label>
                                    </div>

                                    <div class="form-check custom-check">
                                        <input class="form-check-input" type="checkbox" name="mover_todo_workflow"
                                            id="mover_todo_workflow" value="1" {{ old('mover_todo_workflow') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold d-block" for="mover_todo_workflow">
                                            Mano de Dios (Control Total)
                                            <span class="d-block text-danger fw-bold extra-small">PERMISO CRÍTICO: Permite mover cualquier cuenta aunque no esté asignada al usuario.</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="bg-light p-4 rounded-4 h-100">
                                    <h6 class="fw-bold mb-3 text-main">Acceso a Bloques del Workflow</h6>
                                    
                                    <div class="form-check mb-3 custom-check">
                                        <input class="form-check-input" type="checkbox" name="bloques_all" id="bloques_all"
                                            value="1" onchange="toggleBloquesSelection()">
                                        <label class="form-check-label fw-bold" for="bloques_all">Acceso Total (Todos los Bloques)</label>
                                    </div>

                                    <div id="bloques_selection" class="mt-3 ps-4 border-start border-2 border-primary-subtle vstack gap-2" style="max-height: 200px; overflow-y: auto;">
                                        @foreach ($bloques as $bloque)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="bloques_permitidos[]"
                                                    value="{{ $bloque->codigo }}" id="bloque_{{ $bloque->codigo }}">
                                                <label class="form-check-label small" for="bloque_{{ $bloque->codigo }}">
                                                    {{ $bloque->nombre }} <span class="text-muted extra-small">({{ $bloque->codigo }})</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. RESPONSABLE DE BLOQUES -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary text-white p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-person-badge-fill"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">Responsabilidades de Procesamiento</h5>
                                <p class="text-muted extra-small mb-0">Seleccione los bloques donde este rol actuará como operador directo</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            @foreach ($bloques->where('orden', '<', 6) as $bloque)
                                <div class="col-md-4 col-xl-3">
                                    <div class="card border-0 shadow-sm rounded-4 h-100 p-2 role-block-card">
                                        <div class="card-body py-2">
                                            <div class="form-check custom-check m-0">
                                                <input class="form-check-input" type="checkbox" name="responsables_bloque[]"
                                                    value="{{ $bloque->codigo }}" id="resp_bloque_{{ $bloque->codigo }}">
                                                <label class="form-check-label fw-bold small ms-2" for="resp_bloque_{{ $bloque->codigo }}">
                                                    {{ $bloque->nombre }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- 4. PERMISSIONS MATRIX -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary text-white p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Matriz de Privilegios del Sistema</h5>
                        </div>

                        <div class="matrix-table shadow-sm">
                            <table class="premium-table mb-0">
                                <thead>
                                    <tr class="text-center">
                                        <th class="text-start ps-4" style="width: 35%">Módulo / Funcionalidad</th>
                                        <th style="width: 16%">Visualizar</th>
                                        <th style="width: 16%">Crear</th>
                                        <th style="width: 16%">Editar</th>
                                        <th style="width: 17%">Especiales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dashboard -->
                                    <tr data-module="dashboard">
                                        <td class="ps-4 fw-bold"><i class="bi bi-speedometer2 me-2 text-primary"></i>Panel de Control (Dashboard)</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-view" type="checkbox" name="permisos_matrix[dashboard][view]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center bg-light-subtle small text-muted">N/A</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-action" type="checkbox" name="permisos_matrix[dashboard][edit]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center align-items-center gap-2">
                                                <input class="form-check-input" type="checkbox" name="permisos_matrix[dashboard][readonly]" id="cons_readonly" value="1">
                                                <label class="small extra-small m-0 text-muted" for="cons_readonly">Solo Consolidado</label>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Users -->
                                    <tr data-module="users">
                                        <td class="ps-4 fw-bold"><i class="bi bi-people me-2 text-primary"></i>Usuarios y Seguridad</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-view" type="checkbox" name="permisos_matrix[users][view]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-action" type="checkbox" name="permisos_matrix[users][create]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-action" type="checkbox" name="permisos_matrix[users][edit]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center align-items-center gap-2">
                                                <input class="form-check-input" type="checkbox" name="permisos_matrix[users][admin]" id="full_admin" value="1">
                                                <label class="small extra-small m-0 text-danger fw-bold" for="full_admin">Acceso Admin</label>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Workflow -->
                                    <tr data-module="workflow">
                                        <td class="ps-4 fw-bold"><i class="bi bi-diagram-3 me-2 text-primary"></i>Flujo de Trabajo (Workflow)</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-view" type="checkbox" name="permisos_matrix[workflow][view]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center bg-light-subtle small text-muted">N/A</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-action" type="checkbox" name="permisos_matrix[workflow][edit]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center small text-muted">-</td>
                                    </tr>

                                    <!-- Seguimiento -->
                                    <tr data-module="seguimiento">
                                        <td class="ps-4 fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>Expedientes y Cuentas</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-view" type="checkbox" name="permisos_matrix[seguimiento][view]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center bg-light-subtle small text-muted">N/A</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-action" type="checkbox" name="permisos_matrix[seguimiento][edit]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center small text-muted">-</td>
                                    </tr>

                                    <!-- Seguimiento SECOP -->
                                    <tr data-module="seguimiento_secop">
                                        <td class="ps-4 fw-bold"><i class="bi bi-search me-2 text-primary"></i>Seguimiento SECOP</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-view" type="checkbox" name="permisos_matrix[seguimiento_secop][view]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-action" type="checkbox" name="permisos_matrix[seguimiento_secop][create]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-action" type="checkbox" name="permisos_matrix[seguimiento_secop][edit]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center align-items-center gap-2">
                                                <input class="form-check-input perm-action" type="checkbox" name="permisos_matrix[seguimiento_secop][especiales]" id="secop_especiales" value="1">
                                                <label class="small extra-small m-0 text-muted" for="secop_especiales">Opciones Especiales</label>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Reports -->
                                    <tr data-module="reports">
                                        <td class="ps-4 fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Reportes y Estadísticas</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-view" type="checkbox" name="permisos_matrix[reports][view]" value="1" checked>
                                            </div>
                                        </td>
                                        <td colspan="2" class="text-center border-start border-end">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input" type="checkbox" name="permisos_matrix[reports][export]" id="e_reports" value="1">
                                                <label class="small m-0 text-muted" for="e_reports">Exportar Data (Excel/PDF)</label>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input perm-view" type="checkbox" name="permisos_matrix[analitica][view]" id="v_analitica" value="1">
                                                <label class="small m-0 text-muted" for="v_analitica">Analítica</label>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Config -->
                                    <tr data-module="config">
                                        <td class="ps-4 fw-bold"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Configuración Global</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-view" type="checkbox" name="permisos_matrix[config][view]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center bg-light-subtle small text-muted">N/A</td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input perm-action" type="checkbox" name="permisos_matrix[config][edit]" value="1">
                                            </div>
                                        </td>
                                        <td class="text-center small text-muted">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-5 border-top pt-4">
                        <a href="{{ route('configuracion.roles.index') }}" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 12px;">
                            Cancelar
                        </a>
                        <button type="submit" class="btn-premium btn-premium-primary px-5">
                            <i class="bi bi-check-circle"></i> Guardar Nuevo Rol
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
                selectionDiv.style.opacity = allCheckbox.checked ? '0.3' : '1';
                selectionDiv.style.pointerEvents = allCheckbox.checked ? 'none' : 'auto';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tr[data-module]');

            rows.forEach(row => {
                const viewCheckbox = row.querySelector('.perm-view');
                const actionCheckboxes = row.querySelectorAll('.perm-action');

                if (viewCheckbox && actionCheckboxes.length > 0) {
                    updateState(viewCheckbox, actionCheckboxes);

                    viewCheckbox.addEventListener('change', function() {
                        updateState(this, actionCheckboxes);
                        if (!this.checked) {
                            actionCheckboxes.forEach(cb => cb.checked = false);
                        }
                    });

                    actionCheckboxes.forEach(actionCb => {
                        actionCb.addEventListener('change', function() {
                            if (this.checked) {
                                viewCheckbox.checked = true;
                                updateState(viewCheckbox, actionCheckboxes);
                            }
                        });
                    });
                }
            });

            function updateState(viewCb, actionCbs) {
                const row = viewCb.closest('tr');
                if (!viewCb.checked) {
                    actionCbs.forEach(cb => {
                        cb.closest('.form-check').style.opacity = '0.3';
                    });
                    row.style.background = '#F8FAFC';
                } else {
                    actionCbs.forEach(cb => {
                        cb.closest('.form-check').style.opacity = '1';
                    });
                    row.style.background = 'white';
                }
            }
            
            toggleBloquesSelection();
        });
    </script>
    
    <style>
        .role-block-card {
            transition: all 0.2s;
            border: 1px solid transparent !important;
        }
        .role-block-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary) !important;
        }
        .custom-check .form-check-input {
            width: 1.25em;
            height: 1.25em;
            margin-top: 0.15em;
            cursor: pointer;
        }
    </style>
@endsection


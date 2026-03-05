@extends('layouts.app')

@section('title', 'Configuración de Workflow')

@section('page-content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-diagram-3-fill me-2"></i>Gestión de Workflow
            </h1>
            <a href="{{ route('configuracion.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-chevron-left"></i> Volver
            </a>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            Desde aquí puedes gestionar los estados de cada bloque del workflow.
            <strong>Importante:</strong> Al crear o modificar estados, asegúrate de que las transiciones sigan permitiendo
            el flujo de las cuentas.
        </div>

        @foreach ($bloques as $bloque)
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">
                        BLOQUE {{ $bloque->orden }}: {{ $bloque->nombre }}
                        <span class="badge bg-secondary ms-2">{{ $bloque->codigo }}</span>
                    </h6>
                    <button class="btn btn-sm btn-primary shadow-sm"
                        onclick="openCreateModal({{ $bloque->id }}, '{{ $bloque->nombre }}')">
                        <i class="bi bi-plus-lg me-1"></i> Agregar Estado
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Color</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Configuración</th>
                                    <th>ID / Código</th>
                                    <th>Estado BD</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bloque->estados as $estado)
                                    <tr class="{{ $estado->trashed() ? 'table-light text-muted opacity-50' : '' }}">
                                        <td>
                                            <div
                                                style="width: 25px; height: 25px; border-radius: 50%; background-color: {{ $estado->color_hex ?? '#6c757d' }}; border: 1px solid #ccc;">
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ $estado->nombre }}</strong>
                                            @if ($estado->es_inicial)
                                                <span class="badge bg-primary ms-1">INICIAL</span>
                                            @endif
                                            @if ($estado->es_final)
                                                <span class="badge bg-success ms-1">FINAL</span>
                                            @endif
                                            @if ($estado->trashed())
                                                <span class="badge bg-danger ms-1">ELIMINADO</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-outline-{{ strtolower($estado->tipo) }}"
                                                style="border: 1px solid;">
                                                {{ $estado->tipo }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1 small text-muted">
                                                <span>
                                                    <i
                                                        class="bi {{ $estado->contabiliza_tiempo ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} me-1"></i>
                                                    Cuenta tiempo
                                                </span>
                                                <span>
                                                    <i
                                                        class="bi {{ $estado->afecta_indicadores ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} me-1"></i>
                                                    Afecta gráficas
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="small text-muted">{{ $estado->codigo }}</code>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                    {{ $estado->es_activo ? 'checked' : '' }}
                                                    onchange="toggleEstadoStatus({{ $estado->id }})"
                                                    {{ $estado->trashed() ? 'disabled' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary shadow-sm"
                                                    onclick="openEditModal(this)" data-estado='@json($estado)'
                                                    title="Editar">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                @if ($estado->trashed())
                                                    <button class="btn btn-sm btn-outline-success shadow-sm"
                                                        onclick="deleteEstado({{ $estado->id }}, true)"
                                                        title="Restaurar">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline-danger shadow-sm"
                                                        onclick="deleteEstado({{ $estado->id }})"
                                                        title="Eliminar (Lógico)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No hay estados configurados
                                            para este bloque.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal para Crear/Editar -->
    <div class="modal fade" id="estadoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form id="estadoForm">
                    @csrf
                    <input type="hidden" id="estado_id" name="id">
                    <input type="hidden" id="bloque_id" name="bloque_id">

                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title fw-bold" id="modalTitle">Nuevo Estado</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold font-size-sm">Bloque Seleccionado</label>
                                <input type="text" class="form-control bg-light border-0" id="bloque_nombre_display"
                                    readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Nombre del Estado <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" id="form_nombre"
                                    placeholder="Ej: En revisión administrativa" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo" id="form_tipo" required>
                                    <option value="INICIAL">🟡 INICIAL</option>
                                    <option value="EN_PROCESO">🔵 EN PROCESO</option>
                                    <option value="APROBADO">🟢 APROBADO</option>
                                    <option value="DEVUELTO">🔴 DEVUELTO</option>
                                    <option value="FINAL">🏁 FINAL</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Color Identificador</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_hex"
                                        id="form_color" value="#6c757d" title="Elegir color">
                                    <input type="text" class="form-control" id="form_color_text"
                                        placeholder="#6c757d">
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="p-3 bg-light rounded-3 border">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2"><i
                                            class="bi bi-gear-fill me-2"></i>Comportamiento del Flujo</h6>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch custom-switch">
                                                <input class="form-check-input" type="checkbox" name="es_inicial"
                                                    id="form_es_inicial" value="1">
                                                <label class="form-check-label fw-600" for="form_es_inicial">Estado
                                                    Inicial</label>
                                                <p class="small text-muted mb-0">Donde caen las cuentas al entrar al
                                                    bloque.</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch custom-switch">
                                                <input class="form-check-input" type="checkbox" name="es_final"
                                                    id="form_es_final" value="1">
                                                <label class="form-check-label fw-600" for="form_es_final">Estado de
                                                    Salida</label>
                                                <p class="small text-muted mb-0">Permite avanzar al siguiente bloque.</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch custom-switch">
                                                <input class="form-check-input" type="checkbox" name="contabiliza_tiempo"
                                                    id="form_contabiliza_tiempo" value="1" checked>
                                                <label class="form-check-label fw-600 text-primary"
                                                    for="form_contabiliza_tiempo">Contabilizar Tiempo</label>
                                                <p class="small text-muted mb-0">Afecta alertas de estancamiento (SLA).</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch custom-switch">
                                                <input class="form-check-input" type="checkbox" name="afecta_indicadores"
                                                    id="form_afecta_indicadores" value="1" checked>
                                                <label class="form-check-label fw-600 text-primary"
                                                    for="form_afecta_indicadores">Afectar Indicadores</label>
                                                <p class="small text-muted mb-0">Incluye en gráficas de producción.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Descripción / Notas Internas</label>
                                <textarea class="form-control" name="descripcion" id="form_descripcion" rows="2"
                                    placeholder="Opcional: Detalles sobre este estado..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 py-3 px-4">
                        <button type="button" class="btn btn-link text-decoration-none text-muted fw-bold me-auto"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnSubmit">
                            <i class="bi bi-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('estadoModal');
            const bsModal = new bootstrap.Modal(modalEl);
            const form = document.getElementById('estadoForm');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Vincular inputs de color
            document.getElementById('form_color').addEventListener('input', (e) => {
                document.getElementById('form_color_text').value = e.target.value;
            });
            document.getElementById('form_color_text').addEventListener('input', (e) => {
                document.getElementById('form_color').value = e.target.value;
            });

            window.openCreateModal = function(bloqueId, bloqueNombre) {
                form.reset();
                document.getElementById('estado_id').value = '';
                document.getElementById('bloque_id').value = bloqueId;
                document.getElementById('bloque_nombre_display').value = bloqueNombre;
                document.getElementById('modalTitle').textContent = '🔨 Nuevo Estado: ' + bloqueNombre;
                bsModal.show();
            };

            window.openEditModal = function(btn) {
                try {
                    const estado = JSON.parse(btn.getAttribute('data-estado'));
                    form.reset();

                    document.getElementById('estado_id').value = estado.id;
                    document.getElementById('bloque_id').value = estado.bloque_id;
                    document.getElementById('bloque_nombre_display').value = 'Editando Estado Existente';
                    document.getElementById('form_nombre').value = estado.nombre;
                    document.getElementById('form_tipo').value = estado.tipo;
                    document.getElementById('form_color').value = estado.color_hex || '#6c757d';
                    document.getElementById('form_color_text').value = estado.color_hex || '#6c757d';
                    document.getElementById('form_descripcion').value = estado.descripcion || '';

                    // Checkboxes behavior
                    document.getElementById('form_es_inicial').checked = !!estado.es_inicial;
                    document.getElementById('form_es_final').checked = !!estado.es_final;
                    document.getElementById('form_contabiliza_tiempo').checked = !!estado.contabiliza_tiempo;
                    document.getElementById('form_afecta_indicadores').checked = !!estado.afecta_indicadores;

                    document.getElementById('modalTitle').textContent = '📝 Editar Estado: ' + estado.nombre;
                    bsModal.show();
                } catch (e) {
                    console.error("Error al parsear estado:", e);
                    window.showSnackbar('❌ Error al cargar datos del estado', 'error');
                }
            };

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('btnSubmit');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

                const formData = new FormData(form);
                const id = formData.get('id');
                const url = id ? `/configuracion/workflow-estados/actualizar/${id}` :
                    '/configuracion/workflow-estados/store';
                const method = id ? 'PUT' : 'POST';

                // Transform form data to JSON for PUT/POST consistency
                const data = {};
                formData.forEach((value, key) => {
                    data[key] = value;
                });

                // Special handling for checkboxes since FormData only includes checked ones
                ['es_inicial', 'es_final', 'contabiliza_tiempo', 'afecta_indicadores'].forEach(key => {
                    data[key] = document.getElementById(`form_${key}`).checked ? 1 : 0;
                });

                fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Error en el servidor');
                        return data;
                    })
                    .then(res => {
                        if (res.success) {
                            window.showSnackbar('✅ ' + res.message, 'success');
                            bsModal.hide();
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            throw new Error(res.message || 'Error desconocido');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        window.showSnackbar('❌ ' + err.message, 'error');
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-save me-2"></i>Guardar Cambios';
                    });
            });

            window.deleteEstado = function(id, restore = false) {
                Swal.fire({
                    title: restore ? '¿Restaurar estado?' : '¿Eliminar estado?',
                    text: restore ? 'El estado volverá a estar disponible.' :
                        'Se realizará un borrado lógico del estado.',
                    icon: restore ? 'info' : 'warning',
                    showCancelButton: true,
                    confirmButtonColor: restore ? '#28a745' : '#dc3545',
                    confirmButtonText: restore ? 'Sí, restaurar' : 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'premium-swal-popup'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/configuracion/workflow-estados/eliminar/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken
                                }
                            })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success) {
                                    window.showSnackbar('✅ ' + res.message, 'success');
                                    setTimeout(() => location.reload(), 1000);
                                } else {
                                    window.showSnackbar('❌ ' + res.message, 'error');
                                }
                            })
                            .catch(() => window.showSnackbar('❌ Error de red', 'error'));
                    }
                });
            };

            window.toggleEstadoStatus = function(id) {
                fetch(`/configuracion/workflow-estados/toggle-status/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            window.showSnackbar('✅ ' + res.message, 'success');
                        } else {
                            window.showSnackbar('❌ ' + res.message, 'error');
                            setTimeout(() => location.reload(), 1500);
                        }
                    })
                    .catch(() => {
                        window.showSnackbar('❌ Error de red', 'error');
                        setTimeout(() => location.reload(), 1500);
                    });
            };
        });
    </script>

    <style>
        .custom-switch .form-check-input {
            width: 3rem;
            height: 1.5rem;
        }

        .fw-600 {
            font-weight: 600;
        }

        .font-size-sm {
            font-size: 0.85rem;
        }

        .badge.bg-outline-inicial {
            color: #007bff;
            border-color: #007bff;
            background-color: rgba(0, 123, 255, 0.05);
        }

        .badge.bg-outline-en_proceso {
            color: #ffc107;
            border-color: #ffc107;
            background-color: rgba(255, 193, 7, 0.05);
        }

        .badge.bg-outline-aprobado {
            color: #28a745;
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }

        .badge.bg-outline-devuelto {
            color: #dc3545;
            border-color: #dc3545;
            background-color: rgba(220, 53, 69, 0.05);
        }

        .badge.bg-outline-final {
            color: #20c997;
            border-color: #20c997;
            background-color: rgba(32, 201, 151, 0.05);
        }
    </style>
@endsection

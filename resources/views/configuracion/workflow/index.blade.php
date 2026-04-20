@extends('layouts.app')

@section('title', 'Configuración Workflow — SGCC')

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
                    <i class="bi bi-diagram-3-fill"></i>
                    Configuración de Workflow
                </h1>
                <p class="text-muted mb-0">Gestione el flujo de estados, transiciones y reglas de negocio del sistema.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('configuracion.index') }}" class="btn-premium btn-premium-dark">
                    <i class="bi bi-chevron-left"></i> Volver a Configuración
                </a>
            </div>
        </header>

        <div class="alert alert-info border-0 shadow-sm mb-5 animate-fadeIn" style="border-radius: 12px; border-left: 5px solid #0ea5e9 !important; background: white;">
            <div class="d-flex align-items-center">
                <div class="bg-info-subtle p-3 rounded-circle me-3">
                    <i class="bi bi-info-circle-fill text-info fs-4"></i>
                </div>
                <div>
                    <strong class="text-info d-block mb-1">Guía Rápida</strong>
                    <p class="mb-0 small text-muted">Cada bloque representa una etapa del proceso. Los estados definen el comportamiento de una cuenta dentro de esa etapa. Un estado <strong>FINAL</strong> permite el avance automático al siguiente bloque.</p>
                </div>
            </div>
        </div>

        @foreach ($bloques as $bloque)
            <div class="block-card animate-fadeInUp" style="animation-delay: {{ $loop->index * 0.1 }}s">
                <div class="block-card-header">
                    <h5 class="block-title">
                        <span class="block-number">{{ $bloque->orden }}</span>
                        {{ $bloque->nombre }}
                        <small class="text-muted extra-small fw-normal ms-2">Código: [{{ $bloque->codigo }}]</small>
                    </h5>
                    <button class="btn-premium btn-premium-primary py-2 btn-sm"
                        onclick="openCreateModal({{ $bloque->id }}, '{{ $bloque->nombre }}')">
                        <i class="bi bi-plus-lg"></i> Agregar Estado
                    </button>
                </div>
                <div class="p-0">
                    <div class="table-responsive">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th class="ps-4" style="width: 80px">Identif.</th>
                                    <th>Nombre del Estado</th>
                                    <th>Tipo de Fase</th>
                                    <th>Comportamiento</th>
                                    <th class="text-center">Estado (BD)</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bloque->estados as $estado)
                                    <tr class="{{ $estado->trashed() ? 'opacity-50' : '' }}">
                                        <td class="ps-4">
                                            <span class="state-dot" style="background-color: {{ $estado->color_hex ?? '#6c757d' }};" title="{{ $estado->color_hex }}"></span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-main">{{ $estado->nombre }}</span>
                                                <div class="d-flex gap-1 mt-1">
                                                    @if ($estado->es_inicial)
                                                        <span class="workflow-badge wf-badge-inicial">Inicial</span>
                                                    @endif
                                                    @if ($estado->es_final)
                                                        <span class="workflow-badge wf-badge-final">Salida</span>
                                                    @endif
                                                    @if ($estado->trashed())
                                                        <span class="workflow-badge bg-danger text-white">Eliminado</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $tipoClass = match(strtolower($estado->tipo)) {
                                                    'final' => 'wf-badge-final',
                                                    'aprobado' => 'wf-badge-aprobado',
                                                    'en_proceso' => 'wf-badge-proceso',
                                                    default => 'bg-light text-muted'
                                                };
                                            @endphp
                                            <span class="workflow-badge {{ $tipoClass }}">
                                                {{ str_replace('_', ' ', $estado->tipo) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="vstack gap-1">
                                                <div class="d-flex align-items-center gap-2 extra-small mb-1">
                                                    <i class="bi {{ $estado->contabiliza_tiempo ? 'bi-clock-history text-success' : 'bi-clock text-muted' }}"></i>
                                                    <span class="{{ $estado->contabiliza_tiempo ? 'text-dark fw-bold' : 'text-muted' }}">SLA Activo</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 extra-small">
                                                    <i class="bi {{ $estado->afecta_indicadores ? 'bi-graph-up text-primary' : 'bi-graph-down text-muted' }}"></i>
                                                    <span class="{{ $estado->afecta_indicadores ? 'text-dark fw-bold' : 'text-muted' }}">Kpis</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                    {{ $estado->es_activo ? 'checked' : '' }}
                                                    onchange="toggleEstadoStatus({{ $estado->id }})"
                                                    {{ $estado->trashed() ? 'disabled' : '' }}>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button class="action-btn"
                                                    onclick="openEditModal(this)" data-estado='@json($estado)'
                                                    title="Editar Detalle">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                @if ($estado->trashed())
                                                    <button class="action-btn btn-toggle-on"
                                                        onclick="deleteEstado({{ $estado->id }}, true)"
                                                        title="Restaurar">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                @else
                                                    <button class="action-btn btn-toggle-off"
                                                        onclick="deleteEstado({{ $estado->id }})"
                                                        title="Eliminar">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-layers text-light fs-1 d-block mb-3"></i>
                                            Sin estados configurados para este nivel.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal Premium para Crear/Editar -->
    <div class="modal fade premium-modal" id="estadoModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="estadoForm">
                    @csrf
                    <input type="hidden" id="estado_id" name="id">
                    <input type="hidden" id="bloque_id" name="bloque_id">

                    <div class="modal-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white p-2 rounded-3 text-primary">
                                <i class="bi bi-gear-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold" id="modalTitle">Nuevo Estado</h5>
                                <p class="mb-0 extra-small opacity-75">Configure las reglas del nodo de workflow</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 p-md-5">
                        <div class="row g-4 mb-4">
                            <div class="col-12">
                                <div class="bg-light p-3 rounded-4 border-start border-4 border-primary">
                                    <label class="premium-label mb-1">Nivel Operacional</label>
                                    <input type="text" class="form-control bg-transparent border-0 fw-bold p-0" id="bloque_nombre_display"
                                        readonly disabled>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="premium-label">Nombre del Estado <span class="text-danger">*</span></label>
                                <input type="text" class="premium-input" name="nombre" id="form_nombre"
                                    placeholder="Ej: En revisión por Líder" required>
                            </div>
                            <div class="col-md-6">
                                <label class="premium-label">Tipo de Acción <span class="text-danger">*</span></label>
                                <select class="premium-select" name="tipo" id="form_tipo" required>
                                    <option value="EN_PROCESO">🔵 EN PROCESO (Activo)</option>
                                    <option value="APROBADO">🟢 APROBADO (Hito intermedio)</option>
                                    <option value="FINAL">🏁 FINAL (Termina bloque)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="premium-label">Estilo Visual (Color)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;">
                                        <input type="color" class="form-control form-control-color border-0 p-0" name="color_hex"
                                            id="form_color" value="#6c757d" title="Elegir color" style="width: 24px; height: 24px;">
                                    </span>
                                    <input type="text" class="premium-input border-start-0" id="form_color_text"
                                        placeholder="#6c757d" style="border-radius: 0 12px 12px 0;">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-4 rounded-4 border bg-light">
                                    <h6 class="fw-bold mb-4 text-main d-flex align-items-center">
                                        <i class="bi bi-cpu-fill me-2 text-primary"></i> Automatas y Comportamientos
                                    </h6>

                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                                <label class="form-check-label fw-bold small" for="form_es_inicial">
                                                    Punto de Entrada
                                                    <span class="d-block text-muted fw-normal extra-small">Las cuentas ingresan aquí al llegar al bloque.</span>
                                                </label>
                                                <input class="form-check-input ms-0" type="checkbox" name="es_inicial"
                                                    id="form_es_inicial" value="1" style="width: 2.5rem; height: 1.25rem;">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                                <label class="form-check-label fw-bold small" for="form_es_final">
                                                    Punto de Salida
                                                    <span class="d-block text-muted fw-normal extra-small">Habilita el tránsito al bloque siguiente.</span>
                                                </label>
                                                <input class="form-check-input ms-0" type="checkbox" name="es_final"
                                                    id="form_es_final" value="1" style="width: 2.5rem; height: 1.25rem;">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                                <label class="form-check-label fw-bold small text-danger" for="form_permite_devolucion">
                                                    Nodo de Reversa
                                                    <span class="d-block text-muted fw-normal extra-small">Retorna el flujo al bloque anterior.</span>
                                                </label>
                                                <input class="form-check-input ms-0" type="checkbox" name="permite_devolucion"
                                                    id="form_permite_devolucion" value="1" style="width: 2.5rem; height: 1.25rem;">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                                <label class="form-check-label fw-bold small text-primary" for="form_contabiliza_tiempo">
                                                    Métricas de Tiempo
                                                    <span class="d-block text-muted fw-normal extra-small">Contabiliza horas en este estado para SLAs.</span>
                                                </label>
                                                <input class="form-check-input ms-0" type="checkbox" name="contabiliza_tiempo"
                                                    id="form_contabiliza_tiempo" value="1" checked style="width: 2.5rem; height: 1.25rem;">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                                <label class="form-check-label fw-bold small text-primary" for="form_afecta_indicadores">
                                                    Visibilidad en Analítica
                                                    <span class="d-block text-muted fw-normal extra-small">Incluye cuentas de este estado en reportes de gestión y dashboards.</span>
                                                </label>
                                                <input class="form-check-input ms-0" type="checkbox" name="afecta_indicadores"
                                                    id="form_afecta_indicadores" value="1" checked style="width: 2.5rem; height: 1.25rem;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="premium-label">Notas Adicionales</label>
                                <textarea class="premium-input" name="descripcion" id="form_descripcion" rows="2"
                                    placeholder="Describa el propósito de este estado..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-4">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 12px;">Descartar</button>
                        <button type="submit" class="btn-premium btn-premium-primary px-5" id="btnSubmit">
                            <i class="bi bi-save me-2"></i>Aplicar Cambios
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

            // Sincronización de switches y tipos
            const switchInicial = document.getElementById('form_es_inicial');
            const switchFinal = document.getElementById('form_es_final');
            const switchDevuelto = document.getElementById('form_permite_devolucion');
            const selectTipo = document.getElementById('form_tipo');

            switchInicial.addEventListener('change', function() {
                if (this.checked) {
                    switchFinal.checked = false;
                    switchDevuelto.checked = false;
                }
            });

            switchFinal.addEventListener('change', function() {
                if (this.checked) {
                    switchInicial.checked = false;
                    switchDevuelto.checked = false;
                    selectTipo.value = 'FINAL';
                } else if (selectTipo.value === 'FINAL') {
                    selectTipo.value = 'EN_PROCESO';
                }
            });

            switchDevuelto.addEventListener('change', function() {
                if (this.checked) {
                    switchInicial.checked = false;
                    switchFinal.checked = false;
                    selectTipo.value = 'EN_PROCESO';
                }
            });

            selectTipo.addEventListener('change', function() {
                if (this.value === 'FINAL') {
                    switchFinal.checked = true;
                    switchInicial.checked = false;
                }
            });

            document.getElementById('form_color').addEventListener('input', (e) => {
                document.getElementById('form_color_text').value = e.target.value.toUpperCase();
            });
            document.getElementById('form_color_text').addEventListener('input', (e) => {
                document.getElementById('form_color').value = e.target.value;
            });


            window.openCreateModal = function(bloqueId, bloqueNombre) {
                form.reset();
                document.getElementById('estado_id').value = '';
                document.getElementById('bloque_id').value = bloqueId;
                document.getElementById('bloque_nombre_display').value = bloqueNombre;
                document.getElementById('modalTitle').textContent = '🔨 Nuevo Estado';
                bsModal.show();
            };

            window.openEditModal = function(btn) {
                try {
                    const estado = JSON.parse(btn.getAttribute('data-estado'));
                    form.reset();

                    document.getElementById('estado_id').value = estado.id;
                    document.getElementById('bloque_id').value = estado.bloque_id;
                    document.getElementById('bloque_nombre_display').value = 'Módulo: SGCC — Editar Nivel';
                    document.getElementById('form_nombre').value = estado.nombre;
                    document.getElementById('form_tipo').value = estado.tipo;
                    document.getElementById('form_color').value = estado.color_hex || '#6c757d';
                    document.getElementById('form_color_text').value = estado.color_hex || '#6c757d';
                    document.getElementById('form_descripcion').value = estado.descripcion || '';

                    document.getElementById('form_es_inicial').checked = !!estado.es_inicial;
                    document.getElementById('form_es_final').checked = !!estado.es_final;
                    document.getElementById('form_permite_devolucion').checked = !!estado.permite_devolucion;
                    document.getElementById('form_contabiliza_tiempo').checked = !!estado.contabiliza_tiempo;
                    document.getElementById('form_afecta_indicadores').checked = !!estado.afecta_indicadores;

                    document.getElementById('modalTitle').textContent = '📝 Editar Estado';
                    bsModal.show();
                } catch (e) {
                    window.showSnackbar('❌ Error al cargar datos', 'error');
                }
            };

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('btnSubmit');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Inyectando cambios...';

                const formData = new FormData(form);
                const id = formData.get('id');
                const url = id ? `/configuracion/workflow-estados/actualizar/${id}` :
                    '/configuracion/workflow-estados/store';
                const bodyMethod = id ? 'PUT' : 'POST';

                const data = {};
                formData.forEach((value, key) => { data[key] = value; });
                ['es_inicial', 'es_final', 'permite_devolucion', 'contabiliza_tiempo', 'afecta_indicadores'].forEach(key => {
                    data[key] = document.getElementById(`form_${key}`).checked ? 1 : 0;
                });
                data['_method'] = bodyMethod;

                window.apiFetch(url, {
                        method: 'POST',
                        body: JSON.stringify(data)
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            window.showSnackbar('✅ ' + res.message, 'success');
                            bsModal.hide();
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            throw new Error(res.message);
                        }
                    })
                    .catch(err => {
                        window.showSnackbar('❌ ' + err.message, 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-save me-2"></i>Aplicar Cambios';
                    });
            });

            window.deleteEstado = function(id, restore = false) {
                Swal.fire({
                    title: restore ? '¿Restaurar estado?' : '¿Eliminar estado?',
                    text: restore ? 'El estado volverá a estar operativo.' : 'Esta acción ocultará el estado del flujo activo.',
                    icon: restore ? 'info' : 'warning',
                    showCancelButton: true,
                    confirmButtonColor: restore ? '#10B981' : '#EF4444',
                    confirmButtonText: restore ? 'Sí, restaurar' : 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    customClass: { popup: 'premium-swal-popup' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.apiFetch(`/configuracion/workflow-estados/eliminar/${id}`, {
                                method: 'POST',
                                body: JSON.stringify({ _method: 'DELETE' })
                            })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success) {
                                    window.showSnackbar('✅ ' + res.message, 'success');
                                    setTimeout(() => location.reload(), 800);
                                }
                            });
                    }
                });
            };

            window.toggleEstadoStatus = function(id) {
                window.apiFetch(`{{ route('configuracion.workflow.toggle-status') }}/${id}`, {
                        method: 'POST'
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            window.showSnackbar('✅ ' + res.message, 'success');
                        }
                    });
            };
        });
    </script>
@endsection

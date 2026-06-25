@extends('layouts.app')

@section('title', 'Workflow')

@push('styles')
    @vite(['resources/views/workflow/workflow.css'])
@endpush

@section('page-content')
    <div class="container-fluid workflow-container position-relative premium-loading-container"
        data-can-edit="{{ $canEdit ? 'true' : 'false' }}">
        @include('layouts.partials._premium_loader', ['text' => 'Gestionando Procesos'])

        <div class="d-flex justify-content-between align-items-center mb-4 animate-in">
            <div>
                <h1 class="fw-bold mb-1" style="color: var(--text-main); font-size: 1.75rem;">Centro de Control Operativo</h1>
                <p class="text-muted mb-0 small">Monitoreo y gestión del flujo de cuentas en tiempo real.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-white shadow-sm border" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Sincronizar
                </button>
                <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalExportarExcel" style="background-color: #10b981; border-color: #10b981; color: white; border-radius: 8px;">
                    <i class="bi bi-file-earmark-excel me-1"></i> Exportar
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <form action="{{ route('workflow') }}" method="GET" class="filter-bar animate-in">
            <div class="d-flex flex-wrap align-items-end gap-2">
                <div style="flex: 1 1 150px;">
                    <label class="filter-label">Supervisor Asignado</label>
                    <div class="filter-input-group">
                        <select name="supervisor_id" class="form-select filter-control">
                            <option value="">Todos los supervisores</option>
                            @foreach ($supervisores as $sup)
                                <option value="{{ $sup->id }}"
                                    {{ request('supervisor_id') == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="flex: 1 1 150px;">
                    <label class="filter-label">Responsable Actual</label>
                    <div class="filter-input-group">
                        <select name="responsable_id" class="form-select filter-control">
                            <option value="">Todos los responsables</option>
                            @foreach ($responsables as $resp)
                                <option value="{{ $resp->id }}"
                                    {{ request('responsable_id') == $resp->id ? 'selected' : '' }}>
                                    {{ $resp->primer_nombre }} {{ $resp->primer_apellido }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="flex: 1 1 200px;">
                    <label class="filter-label">Contratista / Identificación</label>
                    <div class="filter-input-group">
                        <input type="text" name="contratista" class="form-control filter-control"
                            placeholder="Nombre o NIT..." value="{{ request('contratista') }}">
                    </div>
                </div>
                <div style="flex: 1 1 110px;">
                    <label class="filter-label">N° Contrato</label>
                    <div class="filter-input-group">
                        <input type="text" name="numero_contrato" class="form-control filter-control"
                            placeholder="STIC-CPS..." value="{{ request('numero_contrato') }}">
                    </div>
                </div>
                <div style="flex: 0.5 1 50px;">
                    <label class="filter-label">N° Cuenta</label>
                    <div class="filter-input-group">
                        <input type="number" name="numero_cuenta" class="form-control filter-control"
                            placeholder="Ej: 1" value="{{ request('numero_cuenta') }}">
                    </div>
                </div>
                <div style="flex: 1 1 130px;">
                    <label class="filter-label">Estado</label>
                    <div class="dropdown custom-multilevel-dropdown">
                        <button
                            class="dropdown-toggle filter-control text-start w-100 d-flex justify-content-between align-items-center"
                            type="button" id="dropdownEstadoWorkflow" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="selectedEstadoLabelWorkflow" class="text-truncate" style="max-width: 120px;">
                                {{ request('estado_nombre') ?: 'Todos los estados' }}
                            </span>
                        </button>
                        <input type="hidden" name="estado_nombre" id="hiddenSearchEstadoWorkflow"
                            value="{{ request('estado_nombre') }}">
                        <div class="dropdown-menu shadow-lg p-0" aria-labelledby="dropdownEstadoWorkflow">
                            <div class="wf-dropdown-header">
                                <i class="bi bi-layers"></i> Todos los estados
                            </div>
                            <div class="wf-accordion-block">
                                <a class="wf-dropdown-item filter-estado-item-workflow {{ !request('estado_nombre') ? 'active' : '' }}" href="#" data-value="">
                                    <i class="bi bi-circle-fill me-2 small opacity-50"></i> Todos los estados
                                </a>
                            </div>
                            @foreach ($bloques as $bloque)
                                @php $estadosDelBloque = $todosLosEstados[$bloque->codigo] ?? collect(); @endphp
                                @if ($estadosDelBloque->isNotEmpty())
                                    <div class="wf-accordion-block">
                                        <div class="wf-accordion-header">
                                            <span><i class="bi bi-folder2 me-2"></i>{{ $bloque->nombre }}</span>
                                            <i class="bi bi-chevron-down"></i>
                                        </div>
                                        <div class="wf-accordion-content">
                                            @foreach ($estadosDelBloque as $est)
                                                <a class="wf-dropdown-item filter-estado-item-workflow {{ request('estado_nombre') == $est->nombre ? 'active' : '' }}" 
                                                   href="#" 
                                                   data-value="{{ $est->nombre }}"
                                                   data-block-id="{{ $bloque->id }}">
                                                    {{ $est->nombre }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-3" style="height: 38px; border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-search me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('workflow') }}" class="btn btn-light border px-2" style="height: 38px; border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </form>

        <div id="kanban-container">
            @include('workflow.componentes.board')
        </div>
    </div>

    @include('workflow.componentes.responsible_modal')
    @include('workflow.componentes.modal_exportar')

    @push('scripts')
        <script>
            window.WORK_START_TIME = "{{ \Carbon\Carbon::parse(\App\Models\Configuracion::getValor('HORARIO_LABORAL_INICIO', '06:00'))->format('H:i') }}";
            window.WORK_END_TIME = "{{ \Carbon\Carbon::parse(\App\Models\Configuracion::getValor('HORARIO_LABORAL_FIN', '18:00'))->format('H:i') }}";
            window.WORKFLOW_SUPERVISOR_PAUSE_SUPPORTED = {{ $soportaPausaGestionSupervisor ? 'true' : 'false' }};
        </script>
        @vite(['resources/views/workflow/workflow.js'])
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const filterForm = document.querySelector('.filter-bar');
                let debounceTimer = null;

                window.recargarKanban = function(forcedBlockId = null, options = {}) {
                    const limpiarFiltrosBusqueda = () => {
                        const contratoInput = document.querySelector('input[name="numero_contrato"]');
                        const cuentaInput = document.querySelector('input[name="numero_cuenta"]');
                        const contratistaInput = document.querySelector('input[name="contratista"]');
                        const responsableSelect = document.querySelector('select[name="responsable_id"]');
                        const supervisorSelect = document.querySelector('select[name="supervisor_id"]');
                        const estadoInput = document.getElementById('hiddenSearchEstadoWorkflow');
                        const estadoLabel = document.getElementById('selectedEstadoLabelWorkflow');

                        if (contratoInput) contratoInput.value = '';
                        if (cuentaInput) cuentaInput.value = '';
                        if (contratistaInput) contratistaInput.value = '';
                        if (responsableSelect) responsableSelect.value = '';
                        if (supervisorSelect) supervisorSelect.value = '';
                        if (estadoInput) estadoInput.value = '';
                        if (estadoLabel) estadoLabel.textContent = 'Todos los estados';
                    };

                    if (options.resetFilters !== false) {
                        limpiarFiltrosBusqueda();
                    }

                    const formData = new FormData(filterForm);
                    const params = new URLSearchParams(formData).toString();
                    const url = `{{ route('workflow') }}?${params}`;

                    window.history.replaceState(null, '', url);

                    const kanbanContainer = document.getElementById('kanban-container');
                    kanbanContainer.style.opacity = '0.5';
                    kanbanContainer.style.pointerEvents = 'none';

                    const scrollY = window.scrollY;
                    const scrollX = window.scrollX;

                    const abrirCuenta = (cuentaId, bannerText) => {
                        if (!cuentaId) return false;
                        const target = document.querySelector(`[data-cuenta-id="${cuentaId}"]`);
                        if (target) {
                            target.click();
                            const modal = document.getElementById(`modalCuenta${cuentaId}`);
                            if (modal) {
                                const bannerId = `newCuentaBanner${cuentaId}`;
                                let banner = document.getElementById(bannerId);
                                if (!banner) {
                                    banner = document.createElement('div');
                                    banner.id = bannerId;
                                    banner.className = 'alert alert-warning fw-semibold mb-3';
                                    banner.textContent = bannerText;
                                    modal.querySelector('.modal-body')?.prepend(banner);
                                } else {
                                    banner.textContent = bannerText;
                                    banner.classList.remove('d-none');
                                }
                                setTimeout(() => banner?.classList.add('d-none'), 5000);
                            }
                            return true;
                        }

                        const directModal = document.getElementById(`modalCuenta${cuentaId}`);
                        if (directModal && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(directModal).show();
                            return true;
                        }

                        return false;
                    };

                    window.apiFetch(url)
                        .then(r => r.text())
                        .then(html => {
                            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                            document.body.classList.remove('modal-open');
                            document.body.style.paddingRight = '';
                            document.body.style.overflow = '';

                            kanbanContainer.innerHTML = html;
                            
                            if (forcedBlockId) {
                                if (typeof window.selectWorkflowBlock === 'function') {
                                    window.selectWorkflowBlock(forcedBlockId);
                                }
                            } else {
                                if (typeof window.restoreSelectedBlock === 'function') window.restoreSelectedBlock();
                            }

                            const pendingCuentaId = options.openCuentaId || window.pendingOpenCuentaId;
                            if (pendingCuentaId) {
                                const bannerText = window.pendingOpenCuentaBanner || '⚠️ Esta es una cuenta paralela recién creada';
                                const tryOpen = () => abrirCuenta(pendingCuentaId, bannerText);

                                requestAnimationFrame(() => {
                                    if (tryOpen()) {
                                        window.pendingOpenCuentaId = null;
                                        window.pendingOpenCuentaBanner = null;
                                        kanbanContainer.style.opacity = '1';
                                        kanbanContainer.style.pointerEvents = 'auto';
                                        window.scrollTo(scrollX, scrollY);
                                        return;
                                    }

                                    const observer = new MutationObserver(() => {
                                        if (tryOpen()) {
                                            observer.disconnect();
                                            window.pendingOpenCuentaId = null;
                                            window.pendingOpenCuentaBanner = null;
                                            kanbanContainer.style.opacity = '1';
                                            kanbanContainer.style.pointerEvents = 'auto';
                                            window.scrollTo(scrollX, scrollY);
                                        }
                                    });

                                    observer.observe(kanbanContainer, { childList: true, subtree: true });
                                    setTimeout(() => observer.disconnect(), 3000);
                                });
                            } else {
                                kanbanContainer.style.opacity = '1';
                                kanbanContainer.style.pointerEvents = 'auto';
                                window.scrollTo(scrollX, scrollY);
                            }
                        })
                        .catch(err => {
                            console.error('Error al filtrar:', err);
                            kanbanContainer.style.opacity = '1';
                            kanbanContainer.style.pointerEvents = 'auto';
                        });
                };

                function debouncedReload() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => recargarKanban(), 400);
                }

                // Selects → recarga inmediata
                filterForm.querySelectorAll('select').forEach(select => {
                    select.addEventListener('change', () => recargarKanban());
                });

                // Inputs de texto/número → debounce 400ms
                filterForm.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
                    input.addEventListener('input', debouncedReload);
                });

                // Submit del formulario → interceptar y usar AJAX
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    recargarKanban();
                });

                // Botón Limpiar → resetear form y recargar
                const btnLimpiar = filterForm.querySelector('a.btn-outline-secondary');
                if (btnLimpiar) {
                    btnLimpiar.addEventListener('click', function(e) {
                        e.preventDefault();
                        filterForm.reset();

                        // RESET DROPDOWN PERSONALIZADO
                        const labelSpan = document.getElementById('selectedEstadoLabelWorkflow');
                        const hiddenInput = document.getElementById('hiddenSearchEstadoWorkflow');
                        if (labelSpan && hiddenInput) {
                            labelSpan.textContent = "Todos los estados";
                            hiddenInput.value = "";
                            document.querySelectorAll('.filter-estado-item-workflow').forEach(el => el.classList.remove('active'));
                            const allStatesItem = document.querySelector('.filter-estado-item-workflow[data-value=""]');
                            if (allStatesItem) allStatesItem.classList.add('active');
                        }

                        recargarKanban();
                    });
                }

                // Manejo de dropdown personalizado de estados (Workflow - Acordeón)
                document.body.addEventListener('click', function(e) {
                    // 1. Cabecera del acordeón
                    const accordionHeader = e.target.closest('.wf-accordion-header');
                    if (accordionHeader) {
                        e.stopPropagation();
                        const block = accordionHeader.closest('.wf-accordion-block');
                        const isOpen = block.classList.contains('open');
                        document.querySelectorAll('.wf-accordion-block').forEach(b => b.classList.remove('open'));
                        if (!isOpen) block.classList.add('open');
                        return;
                    }

                    // 2. Item de estado seleccionado
                    const item = e.target.closest('.filter-estado-item-workflow');
                    if (item) {
                        e.preventDefault();
                        const value = item.dataset.value;
                        const label = item.textContent.trim();

                        const hiddenInput = document.getElementById('hiddenSearchEstadoWorkflow');
                        const labelSpan = document.getElementById('selectedEstadoLabelWorkflow');

                        if (hiddenInput && labelSpan) {
                            hiddenInput.value = value;
                            labelSpan.textContent = label || 'Todos los estados';

                            document.querySelectorAll('.filter-estado-item-workflow').forEach(el => el.classList.remove('active'));
                            item.classList.add('active');

                            // Redireccionar al bloque correspondiente si existe el ID
                            const targetBlockId = item.dataset.blockId;
                            
                            const dropdownBtn = document.getElementById('dropdownEstadoWorkflow');
                            if (dropdownBtn) {
                                const bsDropdown = bootstrap.Dropdown.getInstance(dropdownBtn) || new bootstrap.Dropdown(dropdownBtn);
                                if (bsDropdown) bsDropdown.hide();
                            }

                            recargarKanban(targetBlockId);
                        }
                    }
                });
            });
        </script>
    @endpush
@endsection

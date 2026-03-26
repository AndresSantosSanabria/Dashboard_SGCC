@extends('layouts.app')

@section('title', 'Workflow')

@push('styles')
    @vite(['resources/views/workflow/workflow.css'])
@endpush

@section('page-content')
    <div class="container-fluid workflow-container position-relative premium-loading-container"
        data-can-edit="{{ $canEdit ? 'true' : 'false' }}">
        @include('layouts.partials._premium_loader', ['text' => 'Gestionando Procesos'])

        <h1 class="mb-4 text-center fw-bold animate-in">Gestión de Flujo de Trabajo (Kanban)</h1>

        <!-- Filtros -->
        <form action="{{ route('workflow') }}" method="GET" class="filter-bar animate-in">
            <div class="d-flex flex-wrap align-items-end gap-2">
                <div style="flex: 1 1 150px; min-width: 130px;">
                    <label class="filter-label">Supervisor</label>
                    <div class="filter-input-group">
                        <i class="fas fa-user-tie filter-icon"></i>
                        <select name="supervisor_id" class="form-select filter-control">
                            <option value="">Todos</option>
                            @foreach ($supervisores as $sup)
                                <option value="{{ $sup->id }}"
                                    {{ request('supervisor_id') == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="flex: 1 1 150px; min-width: 130px;">
                    <label class="filter-label">Responsable</label>
                    <div class="filter-input-group">
                        <i class="fas fa-user filter-icon"></i>
                        <select name="responsable_id" class="form-select filter-control">
                            <option value="">Todos</option>
                            @foreach ($responsables as $resp)
                                <option value="{{ $resp->id }}"
                                    {{ request('responsable_id') == $resp->id ? 'selected' : '' }}>
                                    {{ $resp->primer_nombre }} {{ $resp->primer_apellido }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="flex: 2 1 180px; min-width: 150px;">
                    <label class="filter-label">Contratista</label>
                    <div class="filter-input-group">
                        <i class="fas fa-search filter-icon"></i>
                        <input type="text" name="contratista" class="form-control filter-control"
                            placeholder="Nombre o NIT..." value="{{ request('contratista') }}">
                    </div>
                </div>
                <div style="flex: 2 1 160px; min-width: 140px;">
                    <label class="filter-label">N° Contrato</label>
                    <div class="filter-input-group">
                        <i class="fas fa-file-contract filter-icon"></i>
                        <input type="text" name="numero_contrato" id="filtro_numero_contrato"
                            class="form-control filter-control" placeholder="Ej: STIC-CPS-001..."
                            value="{{ request('numero_contrato') }}" autocomplete="off">
                    </div>
                </div>
                <div style="flex: 1.5 1 150px; min-width: 130px;">
                    <label class="filter-label">Estado</label>
                    <div class="dropdown custom-multilevel-dropdown">
                        <button
                            class="dropdown-toggle filter-control text-start w-100 d-flex justify-content-between align-items-center"
                            type="button" id="dropdownEstadoWorkflow" data-bs-toggle="dropdown" aria-expanded="false">
                            <span
                                id="selectedEstadoLabelWorkflow">{{ request('estado_nombre') ?: 'Todos los estados' }}</span>
                        </button>
                        <input type="hidden" name="estado_nombre" id="hiddenSearchEstadoWorkflow"
                            value="{{ request('estado_nombre') }}">
                        <ul class="dropdown-menu w-100 shadow-lg" aria-labelledby="dropdownEstadoWorkflow">
                            <li>
                                <a class="dropdown-item filter-estado-item-workflow {{ !request('estado_nombre') ? 'active' : '' }}"
                                    href="#" data-value="">
                                    Todos los estados
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            @foreach ($bloques as $bloque)
                                @php
                                    $estadosDelBloque = $todosLosEstados[$bloque->codigo] ?? collect();
                                @endphp
                                @if ($estadosDelBloque->isNotEmpty())
                                    <li class="dropdown-submenu">
                                        <a class="dropdown-item dropdown-toggle d-flex justify-content-between align-items-center"
                                            href="#">
                                            <span>{{ $bloque->nombre }}</span>
                                            <i class="fas fa-chevron-right small opacity-50"></i>
                                        </a>
                                        <ul class="dropdown-menu shadow-lg">
                                            @foreach ($estadosDelBloque as $est)
                                                <li>
                                                    <a class="dropdown-item filter-estado-item-workflow {{ request('estado_nombre') == $est->nombre ? 'active' : '' }}"
                                                        href="#" data-value="{{ $est->nombre }}">
                                                        {{ $est->nombre }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div style="flex: 0.8 1 100px; min-width: 90px;">
                    <label class="filter-label">N° Cuenta</label>
                    <div class="filter-input-group">
                        <i class="fas fa-list-ol filter-icon"></i>
                        <input type="number" name="numero_cuenta" class="form-control filter-control" placeholder="Ej: 3"
                            value="{{ request('numero_cuenta') }}">
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-end" style="flex: 0 0 auto;">
                    <button type="submit" class="btn btn-primary shadow-sm"
                        style="border-radius: 8px; white-space: nowrap;">
                        <i class="fas fa-filter me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('workflow') }}" class="btn btn-outline-secondary"
                        style="border-radius: 8px; white-space: nowrap;">
                        <i class="fas fa-undo me-1"></i>Limpiar
                    </a>
                </div>
            </div>
        </form>

        <div id="kanban-container">
            @include('workflow.componentes.board')
        </div>
    </div>

    @include('workflow.componentes.responsible_modal')

    @push('scripts')
        @vite(['resources/views/workflow/workflow.js'])
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const filterForm = document.querySelector('.filter-bar');
                let debounceTimer = null;

                window.recargarKanban = function() {
                    const formData = new FormData(filterForm);
                    const params = new URLSearchParams(formData).toString();
                    const url = `{{ route('workflow') }}?${params}`;

                    window.history.replaceState(null, '', url);

                    const kanbanContainer = document.getElementById('kanban-container');
                    kanbanContainer.style.opacity = '0.5';
                    kanbanContainer.style.pointerEvents = 'none';

                    const scrollY = window.scrollY;
                    const scrollX = window.scrollX;

                    window.apiFetch(url)
                        .then(r => r.text())
                        .then(html => {
                            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                            document.body.classList.remove('modal-open');
                            document.body.style.paddingRight = '';
                            document.body.style.overflow = '';

                            kanbanContainer.innerHTML = html;
                            kanbanContainer.style.opacity = '1';
                            kanbanContainer.style.pointerEvents = 'auto';
                            window.scrollTo(scrollX, scrollY);
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

                // Manejo de dropdown personalizado de estados (Workflow)
                document.body.addEventListener('click', function(e) {
                    const item = e.target.closest('.filter-estado-item-workflow');
                    if (item) {
                        e.preventDefault();
                        const value = item.dataset.value;
                        const label = item.textContent.trim();

                        const hiddenInput = document.getElementById('hiddenSearchEstadoWorkflow');
                        const labelSpan = document.getElementById('selectedEstadoLabelWorkflow');

                        if (hiddenInput && labelSpan) {
                            hiddenInput.value = value;
                            labelSpan.textContent = label;

                            document.querySelectorAll('.filter-estado-item-workflow').forEach(el => el.classList.remove('active'));
                            item.classList.add('active');

                            const dropdownBtn = document.getElementById('dropdownEstadoWorkflow');
                            if (dropdownBtn) {
                                const bsDropdown = bootstrap.Dropdown.getInstance(dropdownBtn) || new bootstrap.Dropdown(dropdownBtn);
                                if (bsDropdown) bsDropdown.hide();
                            }

                            recargarKanban();
                        }
                    }

                    if (e.target.closest('.dropdown-submenu > .dropdown-toggle')) {
                        e.stopPropagation();
                        e.preventDefault();
                    }
                });
            });
        </script>
    @endpush
@endsection

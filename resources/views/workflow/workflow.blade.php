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
                    <div class="filter-input-group">
                        <i class="fas fa-tag filter-icon"></i>
                        <select name="estado_nombre" class="form-select filter-control">
                            <option value="">Todos los estados</option>
                            @foreach ($estados as $est)
                                <option value="{{ $est->nombre }}"
                                    {{ request('estado_nombre') == $est->nombre ? 'selected' : '' }}>
                                    {{ $est->nombre }}
                                </option>
                            @endforeach
                        </select>
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
                        recargarKanban();
                    });
                }
            });
        </script>
    @endpush
@endsection

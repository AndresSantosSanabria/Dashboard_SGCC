@extends('layouts.app')

@section('title', 'Workflow')

@push('styles')
    @vite(['resources/views/workflow/workflow.css'])
@endpush

@section('page-content')
    <div class="container-fluid workflow-container" data-can-edit="{{ $canEdit ? 'true' : 'false' }}">
        <h1 class="mb-4 text-center fw-bold">Gestión de Flujo de Trabajo (Kanban)</h1>

        <!-- Filtros -->
        <form action="{{ route('workflow') }}" method="GET" class="filter-bar">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="filter-label">Supervisor</label>
                    <div class="filter-input-group">
                        <i class="fas fa-user-tie filter-icon"></i>
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
                <div class="col-md-3">
                    <label class="filter-label">Contratista</label>
                    <div class="filter-input-group">
                        <i class="fas fa-search filter-icon"></i>
                        <input type="text" name="contratista" class="form-control filter-control"
                            placeholder="Nombre o NIT..." value="{{ request('contratista') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Estado Específico</label>
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
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary shadow-sm w-100" style="border-radius: 8px;">
                        <i class="fas fa-filter me-2"></i>Filtrar
                    </button>
                    <a href="{{ route('workflow') }}" class="btn btn-outline-secondary w-100" style="border-radius: 8px;">
                        <i class="fas fa-undo me-2"></i>Limpiar
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
                // Auto-refresh logic
                setInterval(function() {
                    // Check if any modal is open to avoid refreshing while user is interacting
                    if (document.querySelector('.modal.show')) {
                        console.log('Skipping refresh: Modal is open');
                        return;
                    }

                    const currentUrl = window.location.href;
                    fetch(currentUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('kanban-container').innerHTML = html;
                            // Re-initialize any necessary plugins or event listeners here if needed
                            // For example, if you use tooltips or popovers, re-init them.
                        })
                        .catch(error => console.error('Error auto-refreshing workflow:', error));
                }, 30000); // 30 seconds
            });
        </script>
    @endpush
@endsection

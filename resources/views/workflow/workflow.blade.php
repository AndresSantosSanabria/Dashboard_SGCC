@extends('layouts.app')

@section('title', 'Workflow')

@push('styles')
    @vite(['resources/views/workflow/workflow.css'])
@endpush

@section('page-content')
    <div class="container-fluid workflow-container">
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

        @foreach ($workflow as $key => $block)
            <div class="workflow-block">
                <div class="block-header block-{{ $block['color'] }}">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-layer-group me-2"></i>
                        <span>{{ $block['nombre'] }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span
                            class="badge bg-light text-dark shadow-sm">{{ count(collect($block['columnas'])->pluck('cuentas')->flatten(1)) }}
                            Cuentas</span>
                        <button class="btn-collapse" onclick="toggleBlockVisibility(this, '{{ $key }}')">
                            <i class="fas fa-chevron-up"></i>
                        </button>
                    </div>
                </div>
                <div class="kanban-board">
                    @foreach ($block['columnas'] as $estadoId => $columna)
                        <div class="kanban-column">
                            <div class="column-title">
                                <span class="text-capitalize">{{ $columna['nombre'] }}</span>
                                <span
                                    class="badge rounded-pill bg-white text-dark shadow-sm">{{ count($columna['cuentas']) }}</span>
                            </div>
                            <div class="column-content">
                                @forelse($columna['cuentas'] as $cuenta)
                                    @php
                                        $alertClass = 'alert-amarillo';
                                        if ($columna['tipo'] === 'DEVUELTO') {
                                            $alertClass = 'alert-rojo';
                                        }
                                        if ($columna['tipo'] === 'APROBADO' || $columna['tipo'] === 'FINAL') {
                                            $alertClass = 'alert-verde';
                                        }
                                        $estadoBloqueActual = $cuenta->estadosBloques
                                            ->where('bloque_id', $cuenta->bloque_actual_id)
                                            ->first();
                                        $fechaIngreso = $estadoBloqueActual?->fecha_ultima_actualizacion;
                                    @endphp
                                    <div class="account-card {{ $alertClass }}" data-bs-toggle="modal"
                                        data-bs-target="#modalCuenta{{ $cuenta->id }}">
                                        <div class="card-id">CONTRATO: {{ $cuenta->contrato?->numero_contrato ?? 'N/A' }}
                                        </div>
                                        <div class="card-contractor text-truncate"
                                            title="{{ $cuenta->contrato?->contratista?->nombre_completo }}">
                                            {{ Str::limit($cuenta->contrato?->contratista?->nombre_completo ?? 'N/A', 35) }}
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="small text-muted" style="font-size: 0.75rem;">Cuenta
                                                #{{ $cuenta->numero_cuenta }}</div>
                                            <span class="badge border text-dark bg-light"
                                                style="font-size: 0.65rem; padding: 2px 5px;">
                                                {{ $cuenta->estadoActual?->nombre }}
                                            </span>
                                        </div>
                                        <div class="card-footer-info"><span><i
                                                    class="far fa-calendar-alt"></i>{{ $cuenta->created_at->format('d/m/Y') }}</span><span
                                                class="timer-badge"
                                                data-start="{{ $fechaIngreso ? $fechaIngreso->toIso8601String() : '' }}"><i
                                                    class="far fa-clock"></i><span
                                                    class="elapsed-time">{{ $fechaIngreso ? $fechaIngreso->diffForHumans(null, true) : 'N/A' }}</span></span>
                                        </div>
                                    </div>
                                    @include('workflow.componentes.account_modal')
                                @empty <div class="text-center py-4 text-muted small border rounded bg-white">
                                        Sin cuentas </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @include('workflow.componentes.responsible_modal')

    @push('scripts')
        @vite(['resources/views/workflow/workflow.js'])
    @endpush
@endsection

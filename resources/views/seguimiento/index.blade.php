@extends('layouts.app')

@section('title', 'Seguimiento SECOP - SIA OBSERVA')

@push('styles')
    <style>
        :root {
            --secop-primary: #004884;
            --secop-secondary: #06916F;
            --secop-bg: #f4f7f9;
            --status-ok-bg: #d1e7dd;
            --status-ok-text: #0f5132;
            --status-pen-bg: #fff3cd;
            --status-pen-text: #856404;
            --status-err-bg: #f8d7da;
            --status-err-text: #842029;
            --status-na-bg: #e2e3e5;
            --status-na-text: #41464b;
        }

        body {
            background-color: var(--secop-bg);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .container-fluid {
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--secop-primary) 0%, #002d52 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 0 0 2rem 2rem;
            margin-bottom: -1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 25;
        }

        .page-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            pointer-events: none;
        }

        .filters-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .filters-panel:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .table-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .table-responsive {
            max-height: calc(100vh - 350px);
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }

        .table-seguimiento thead th {
            background: #f8fafc;
            color: #4a5568;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem;
            border: none;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .table-seguimiento tbody tr {
            transition: background 0.2s;
        }

        .table-seguimiento tbody tr:hover {
            background-color: #f1f5f9;
        }

        .table-seguimiento td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .sticky-col {
            position: sticky;
            left: 0;
            background: white !important;
            z-index: 10;
            border-right: 2px solid #edf2f7 !important;
        }

        .status-dropdown {
            border-radius: 8px;
            border: none;
            padding: 0.5rem;
            font-weight: 700;
            font-size: 0.65rem;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-align: center;
        }

        .status-dropdown:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .status-ok {
            background-color: var(--status-ok-bg) !important;
            color: var(--status-ok-text) !important;
        }

        .status-pendiente {
            background-color: var(--status-pen-bg) !important;
            color: var(--status-pen-text) !important;
        }

        .status-rojo {
            background-color: var(--status-err-bg) !important;
            color: var(--status-err-text) !important;
        }

        .status-na {
            background-color: var(--status-na-bg) !important;
            color: var(--status-na-text) !important;
        }

        .btn-govco-primary {
            background: #ffffff;
            color: #004884;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            padding: 0.8rem 1.5rem;
            transition: all 0.3s;
        }

        .btn-govco-primary:hover {
            background: #f8f9fa;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 0.6rem 1rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0, 72, 132, 0.1);
            border-color: #004884;
        }
    </style>
@endpush

@section('page-content')
    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">Seguimiento SECOP - SIA OBSERVA</h2>
                <p class="mb-0 opacity-75">Control documental inteligente y gestión estratégica de cumplimiento</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-govco-primary py-2 px-4 shadow-sm fw-bold">
                    <i class="bi bi-house-door me-2"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Panel de Filtros -->
        <div class="filters-panel py-4 px-4 bg-white shadow-sm rounded-4 mb-4 border-0">
            <form id="filterForm" class="row align-items-end g-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold text-dark mb-2">
                        <i class="bi bi-search me-2 text-primary"></i> Búsqueda Directa
                    </label>
                    <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border">
                        <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="searchInput" class="form-control border-0 bg-white"
                            placeholder="Buscar por número de contrato, contratista o representante...">
                    </div>
                </div>
                <div class="col-md-4 d-flex justify-content-end">
                    <button type="button" id="resetFilters"
                        class="btn btn-light btn-lg rounded-3 px-4 fw-semibold border shadow-sm transition-all hover-bg-light">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Reiniciar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de Datos -->
        <div class="card table-card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive" id="tableContainer">
                    @include('seguimiento.partials.table')
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario Lateral (Offcanvas) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNuevoContrato"
        aria-labelledby="offcanvasNuevoContratoLabel">
        <div class="offcanvas-header bg-govco-navbar text-white">
            <h5 class="offcanvas-title fw-bold" id="offcanvasNuevoContratoLabel"><i
                    class="bi bi-file-earmark-plus me-2"></i> Registrar Nuevo Contrato</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('seguimiento.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Número de Proceso</label>
                    <input type="text" name="numero_proceso" class="form-control" placeholder="Ej: SED-LP-001-2024">
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Número de Contrato *</label>
                    <input type="text" name="numero_contrato" class="form-control" required placeholder="Ej: 1234-2024">
                </div>
                <div class="mb-3">
                    <label class="form-label">Modalidad de Contratación</label>
                    <select name="modalidad_id" class="form-select">
                        <option value="">Seleccione modalidad</option>
                        @foreach ($modalidades as $m)
                            <option value="{{ $m->id }}">{{ $m->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Contratista *</label>
                    <select name="contratista_id" class="form-select" required>
                        <option value="">Seleccione contratista</option>
                        @foreach ($contratistas as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre_completo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Supervisor</label>
                    <select name="supervisor_id" class="form-select">
                        <option value="">Seleccione supervisor</option>
                        @foreach ($supervisores as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre_completo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Objeto del Contrato</label>
                    <textarea name="objeto" class="form-control" rows="3" placeholder="Descripción breve del contrato..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Valor del Contrato *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" name="monto_total" class="form-control" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Link SECOP</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                        <input type="url" name="link_secop" class="form-control"
                            placeholder="https://www.secop.gov.co/...">
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-govco-primary py-3">
                        <i class="bi bi-save me-2"></i> Guardar Contrato
                    </button>
                    <button type="button" class="btn btn-light py-3" data-bs-dismiss="offcanvas">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableContainer = document.getElementById('tableContainer');
            const filterForm = document.getElementById('filterForm');
            const searchInput = document.getElementById('searchInput');
            const resetBtn = document.getElementById('resetFilters');

            // Función para actualizar la tabla por AJAX
            const updateTable = (url = null) => {
                const formData = new FormData(filterForm);
                const params = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (value) params.append(key, value);
                }

                const fetchUrl = url || `{{ route('seguimiento.index') }}?${params.toString()}`;

                fetch(fetchUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        tableContainer.innerHTML = html;
                        attachStatusListeners();
                        attachPaginationListeners();
                    });
            };

            const attachPaginationListeners = () => {
                document.querySelectorAll('#tableContainer .pagination a').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        updateTable(this.href);
                    });
                });
            };

            // Event listeners
            let timeout = null;
            if (searchInput) {
                searchInput.addEventListener('keyup', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(updateTable, 500);
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    filterForm.reset();
                    updateTable();
                });
            }

            // Función para manejar cambios de estado
            const attachStatusListeners = () => {
                document.querySelectorAll('.status-dropdown').forEach(select => {
                    select.addEventListener('change', function() {
                        const id = this.dataset.id;
                        const field = this.dataset.field;
                        const status = this.value;

                        // Update UI class
                        this.classList.remove('status-verde', 'status-amarillo', 'status-rojo',
                            'status-ok', 'status-pendiente', 'status-na');
                        this.classList.add('status-' + status.toLowerCase());

                        // Save to DB via AJAX
                        fetch('{{ route('seguimiento.update-status') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    id,
                                    field,
                                    status
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showSnackbar('Estado actualizado automáticamente',
                                        'success');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showSnackbar('Error al guardar el estado', 'danger');
                            });
                    });
                });
            };

            // Inicializar listeners
            attachStatusListeners();
            attachPaginationListeners();
        });
    </script>
@endpush

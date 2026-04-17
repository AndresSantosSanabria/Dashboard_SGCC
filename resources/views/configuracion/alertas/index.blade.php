@extends('layouts.app')

@section('title', 'Configuración de Alertas — SGCC')

@push('styles')
    @vite(['resources/views/configuracion/configuracion.css'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .alert-param-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: white;
            height: 100%;
            transition: all 0.3s;
        }
        .alert-param-card:hover { border-color: var(--primary); }
        .alert-param-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .input-group-premium {
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
        }
        .input-group-premium .input-group-text {
            background: #F1F5F9;
            border: none;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            padding: 0 1rem;
        }
        .input-group-premium input {
            border: none;
            background: transparent;
            padding: 0.75rem;
            width: 100%;
        }
        .input-group-premium input:focus { outline: none; background: white; }
    </style>
@endpush

@section('page-content')
<div class="config-container">
    {{-- HEADER --}}
    <header class="config-header">
        <div>
            <h1>
                <i class="bi bi-bell-fill text-warning"></i>
                Sistema de Alertas (SLA)
            </h1>
            <p class="text-muted mb-0">Gestión de umbrales de estancamiento, calendario de festivos y notificaciones.</p>
        </div>
        <div>
            <a href="{{ route('configuracion.index') }}" class="btn-premium btn-premium-dark">
                <i class="bi bi-chevron-left"></i> Volver
            </a>
        </div>
    </header>

    <div class="row g-4">
        {{-- ── 1. PARÁMETROS GENERALES ── --}}
        <div class="col-lg-5">
            <div class="alert-param-card shadow-sm animate-fadeInLeft">
                <div class="alert-param-header">
                    <div class="bg-primary-subtle p-2 rounded-3 text-primary">
                        <i class="bi bi-sliders fs-5"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Umbrales de Gestión</h5>
                </div>
                <div class="p-4">
                    <form id="formConfig">
                        @csrf
                        <div class="mb-4">
                            <label class="premium-label"><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Nivel Crítico (Rojo)</label>
                            <div class="row g-2">
                                <div class="col-7">
                                    <div class="input-group-premium">
                                        <span class="input-group-text">Horas</span>
                                        <input type="number" name="limit_hours" value="{{ $limitHours }}" min="0" required>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="input-group-premium">
                                        <span class="input-group-text">Min</span>
                                        <input type="number" name="limit_mins" value="{{ $limitMins }}" min="0" max="59" required>
                                    </div>
                                </div>
                            </div>
                            <p class="extra-small text-muted mt-2">La alerta visual se activará al superar este tiempo acumulado.</p>
                        </div>

                        <div class="mb-4">
                            <label class="premium-label"><i class="bi bi-hourglass-split text-warning me-1"></i>Nivel Informativo (Naranja)</label>
                            <div class="row g-2">
                                <div class="col-7">
                                    <div class="input-group-premium">
                                        <span class="input-group-text">Horas</span>
                                        <input type="number" name="pre_limit_hours" value="{{ $preLimitHours }}" min="0" required>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="input-group-premium">
                                        <span class="input-group-text">Min</span>
                                        <input type="number" name="pre_limit_mins" value="{{ $preLimitMins }}" min="0" max="59" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="premium-label"><i class="bi bi-clock-fill text-info me-1"></i>Horario de Operación (Hábiles)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="input-group-premium">
                                        <span class="input-group-text">De</span>
                                        <input type="time" name="HORARIO_LABORAL_INICIO" value="{{ $configuraciones['HORARIO_LABORAL_INICIO']?->valor ?? '06:00' }}" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group-premium">
                                        <span class="input-group-text">A</span>
                                        <input type="time" name="HORARIO_LABORAL_FIN" value="{{ $configuraciones['HORARIO_LABORAL_FIN']?->valor ?? '18:00' }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-check form-switch custom-switch mb-4 p-0 d-flex justify-content-between align-items-center">
                            <label class="fw-bold small" for="switchActiva">Activación Global del Sistema</label>
                            <input class="form-check-input ms-0" type="checkbox" name="ALERTA_ESTANCAMIENTO_ACTIVA" 
                                id="switchActiva" value="1" style="width: 2.8rem; height: 1.4rem;"
                                @checked(($configuraciones['ALERTA_ESTANCAMIENTO_ACTIVA']?->valor ?? 'true') === 'true')>
                        </div>

                        <div class="mb-3">
                            <label class="premium-label small"><i class="bi bi-chat-left-dots me-1"></i>Plantilla Pre-aviso</label>
                            <textarea name="msg_warning" class="premium-input py-2" rows="2" placeholder="Plantilla mensaje naranja...">{{ $configuraciones['ALERTA_ESTANCAMIENTO_MSG_WARNING']?->valor ?? '' }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="premium-label small"><i class="bi bi-chat-right-dots-fill me-1"></i>Plantilla Crítica</label>
                            <textarea name="msg_danger" class="premium-input py-2" rows="2" placeholder="Plantilla mensaje rojo...">{{ $configuraciones['ALERTA_ESTANCAMIENTO_MSG_DANGER']?->valor ?? '' }}</textarea>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="badge bg-light text-muted border extra-small">{numero_contrato}</span>
                                <span class="badge bg-light text-muted border extra-small">{contratista}</span>
                                <span class="badge bg-light text-muted border extra-small">{tiempo}</span>
                                <span class="badge bg-light text-muted border extra-small">{estado}</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-premium btn-premium-primary w-100 py-3">
                            <i class="bi bi-save2-fill me-2"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── 2. DESTINATARIOS Y CALENDARIO ── --}}
        <div class="col-lg-7">
            <div class="vstack gap-4">
                {{-- DESTINATARIOS --}}
                <div class="alert-param-card shadow-sm">
                    <div class="alert-param-header justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-success-subtle p-2 rounded-3 text-success">
                                <i class="bi bi-people-fill fs-5"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Panel de Notificaciones</h5>
                        </div>
                        <button class="btn-premium btn-premium-info btn-sm py-1" data-bs-toggle="modal" data-bs-target="#modalDestinatario">
                            <i class="bi bi-plus-lg"></i> Añadir Destinatario
                        </button>
                    </div>
                    <div class="p-0">
                        <div class="table-responsive">
                            <table class="premium-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Tipo</th>
                                        <th>Identidad / Nombre</th>
                                        <th class="text-end pe-4">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($destinatarios as $dest)
                                        <tr>
                                            <td class="ps-4">
                                                <span class="workflow-badge {{ $dest->tipo_destinatario === 'ROL' ? 'wf-badge-inicial' : 'wf-badge-aprobado' }}">
                                                    {{ $dest->tipo_destinatario }}
                                                </span>
                                            </td>
                                            <td><span class="fw-bold text-main">{{ $dest->label }}</span></td>
                                            <td class="text-end pe-4">
                                                <button class="action-btn btn-toggle-off" onclick="eliminarDestinatario({{ $dest->id }})">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted small">Sin destinatarios manuales configurados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- FESTIVOS --}}
                <div class="alert-param-card shadow-sm">
                    <div class="alert-param-header justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-danger-subtle p-2 rounded-3 text-danger">
                                <i class="bi bi-calendar-event-fill fs-5"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Excepciones de Calendario (Festivos)</h5>
                        </div>
                        <div class="d-flex gap-2">
                            <button id="btnSyncFestivos" class="btn btn-outline-primary btn-sm rounded-3">
                                <i class="bi bi-arrow-repeat"></i> Sync Colombia
                            </button>
                            <button class="btn btn-danger btn-sm rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#modalFestivo">
                                <i class="bi bi-plus-lg"></i> Manual
                            </button>
                        </div>
                    </div>
                    <div class="p-0">
                        <div class="table-responsive" style="max-height: 350px;">
                            <table class="premium-table mb-0">
                                <thead class="sticky-top bg-light">
                                    <tr>
                                        <th class="ps-4">Fecha</th>
                                        <th>Día de la semana</th>
                                        <th>Descripción</th>
                                        <th class="text-end pe-4">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($festivos as $festivo)
                                        <tr>
                                            <td class="ps-4 fw-bold text-main">{{ $festivo->fecha->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="badge bg-light text-danger-emphasis border border-danger-subtle rounded-3">
                                                    {{ ucfirst($festivo->fecha->locale('es')->dayName) }}
                                                </span>
                                            </td>
                                            <td class="text-muted extra-small">{{ $festivo->descripcion ?? 'Feriado Nacional' }}</td>
                                            <td class="text-end pe-4">
                                                <button class="action-btn btn-toggle-off" onclick="eliminarFestivo({{ $festivo->id }})">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5">No hay festivos en el radar.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODALS --}}
<div class="modal fade premium-modal" id="modalFestivo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Registrar Día Festivo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formFestivo">
                    @csrf
                    <div class="mb-3">
                        <label class="premium-label">Fecha del Feriado</label>
                        <input type="date" name="fecha" class="premium-input" required>
                    </div>
                    <div class="mb-4">
                        <label class="premium-label">Nombre del Evento</label>
                        <input type="text" name="descripcion" class="premium-input" placeholder="Ej: Independencia">
                    </div>
                    <button type="submit" class="btn-premium btn-premium-primary w-100 py-3">
                        <i class="bi bi-cloud-upload-fill me-2"></i> Guardar en Calendario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade premium-modal" id="modalDestinatario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Añadir Destinatario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formDestinatario">
                    @csrf
                    <div class="mb-4">
                        <label class="premium-label">Tipo de Suscripción</label>
                        <select name="tipo_destinatario" id="tipoDestinatario" class="premium-select" required>
                            <option value="USUARIO">👤 Usuario Individual</option>
                            <option value="ROL">🛡️ Grupo por Rol</option>
                        </select>
                    </div>
                    <div class="mb-4 animate-fadeIn" id="wrapperUsuario">
                        <label class="premium-label">Seleccione Usuario</label>
                        <select name="destinatario_id" class="premium-select">
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id }}">{{ $u->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4 d-none animate-fadeIn" id="wrapperRol">
                        <label class="premium-label">Seleccione Rol</label>
                        <select name="destinatario_id_rol" class="premium-select">
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-premium btn-premium-primary w-100 py-3">
                        <i class="bi bi-check-lg me-2"></i> Confirmar Destinatario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // FORM CONFIG
    document.getElementById('formConfig').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sincronizando...';
        
        window.apiFetch('{{ route("configuracion.alertas.config") }}', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(data => {
            window.showSnackbar(data.success ? '✅ ' + data.message : '⚠️ ' + data.message, data.success ? 'success' : 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save2-fill me-2"></i> Guardar Cambios';
        });
    });

    // SYNC FESTIVOS
    document.getElementById('btnSyncFestivos').addEventListener('click', function() {
        const btn = this;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sync...';
        btn.disabled = true;

        window.apiFetch('{{ route("configuracion.alertas.festivos.sync") }}', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showSnackbar('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 1200);
            }
        });
    });

    document.getElementById('formFestivo').addEventListener('submit', function(e) {
        e.preventDefault();
        window.apiFetch('{{ route("configuracion.alertas.festivos.store") }}', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showSnackbar('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 800);
            }
        });
    });

    window.eliminarFestivo = function(id) {
        if (!confirm('¿Retirar este festivo del sistema?')) return;
        window.apiFetch(`/configuracion/alertas/festivos/${id}`, { method: 'DELETE' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('festivo-row-' + id)?.remove();
                window.showSnackbar('✅ Eliminado', 'success');
            }
        });
    };

    // DESTINATARIOS
    document.getElementById('tipoDestinatario').addEventListener('change', function() {
        const esRol = this.value === 'ROL';
        document.getElementById('wrapperUsuario').classList.toggle('d-none', esRol);
        document.getElementById('wrapperRol').classList.toggle('d-none', !esRol);
    });

    document.getElementById('formDestinatario').addEventListener('submit', function(e) {
        e.preventDefault();
        const tipo = document.getElementById('tipoDestinatario').value;
        const id = tipo === 'ROL' 
            ? this.querySelector('select[name="destinatario_id_rol"]').value 
            : this.querySelector('select[name="destinatario_id"]').value;

        const fd = new FormData();
        fd.append('tipo_destinatario', tipo);
        fd.append('destinatario_id', id);

        window.apiFetch('{{ route("configuracion.alertas.destinatarios.store") }}', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showSnackbar('✅ Destinatario añadido', 'success');
                setTimeout(() => location.reload(), 800);
            }
        });
    });

    window.eliminarDestinatario = function(id) {
        if (!confirm('¿Remover este destinatario de las notificaciones?')) return;
        window.apiFetch(`/configuracion/alertas/destinatarios/${id}`, { method: 'DELETE' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    };
</script>
@endpush
@endsection

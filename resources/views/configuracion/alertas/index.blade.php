@extends('layouts.app')

@section('title', 'Sistema de Alertas — SGCC')

@section('page-content')
<div class="container-fluid py-4 px-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-bell-fill text-warning me-2"></i>Sistema de Alertas</h2>
            <p class="text-muted mb-0">Configuración de umbrales, festivos y destinatarios para alertas de estancamiento.</p>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── 1. Configuración General ─────────────────── --}}
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100" style="border-radius:16px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Parámetros Generales</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <form id="formConfig">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Tiempo Límite de Estancamiento</label>
                            <div class="row g-2">
                                <div class="col-7">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Hr</span>
                                        <input type="number" name="limit_hours" class="form-control" value="{{ $limitHours }}" min="0" required>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Min</span>
                                        <input type="number" name="limit_mins" class="form-control" value="{{ $limitMins }}" min="0" max="59" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text text-danger-emphasis small mt-1">Nivel Crítico (Color Rojo) se activa al cumplir este tiempo.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-hourglass-split text-warning me-1"></i>Tiempo de Pre-aviso</label>
                            <div class="row g-2">
                                <div class="col-7">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Hr</span>
                                        <input type="number" name="pre_limit_hours" class="form-control" value="{{ $preLimitHours }}" min="0" required>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Min</span>
                                        <input type="number" name="pre_limit_mins" class="form-control" value="{{ $preLimitMins }}" min="0" max="59" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text text-warning-emphasis small mt-1">Nivel Informativo (Color Naranja) se activa antes de llegar al límite.</div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="ALERTA_ESTANCAMIENTO_ACTIVA"
                                id="switchActiva" value="1"
                                @checked(($configuraciones['ALERTA_ESTANCAMIENTO_ACTIVA']?->valor ?? 'true') === 'true')>
                            <label class="form-check-label fw-semibold" for="switchActiva">Sistema de alertas activo</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small"><i class="bi bi-chat-left-dots me-1"></i>Mensaje Pre-aviso</label>
                            <textarea name="msg_warning" class="form-control form-control-sm" rows="2" placeholder="Plantilla mensaje naranja...">{{ $configuraciones['ALERTA_ESTANCAMIENTO_MSG_WARNING']?->valor ?? '' }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small"><i class="bi bi-chat-right-dots-fill me-1"></i>Mensaje Crítico</label>
                            <textarea name="msg_danger" class="form-control form-control-sm" rows="2" placeholder="Plantilla mensaje rojo...">{{ $configuraciones['ALERTA_ESTANCAMIENTO_MSG_DANGER']?->valor ?? '' }}</textarea>
                            <div class="form-text small" style="font-size: 0.7rem;">
                                Usa: {numero_contrato}, {contratista}, {tiempo}, {estado}
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i> Guardar configuración
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── 2. Destinatarios ────────────────────────── --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100" style="border-radius:16px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-people me-2 text-success"></i>Destinatarios de Alertas</h5>
                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalDestinatario">
                        <i class="bi bi-plus-lg me-1"></i> Añadir
                    </button>
                </div>
                <div class="card-body px-4 pb-4">
                    @if($destinatarios->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-person-x fs-1 d-block mb-2 opacity-25"></i>
                            Sin destinatarios. Las alertas se enviarán a los administradores.
                        </div>
                    @else
                        <div class="list-group list-group-flush" id="listaDestinatarios">
                            @foreach($destinatarios as $dest)
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center border-0 border-bottom py-2"
                                id="dest-row-{{ $dest->id }}">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill {{ $dest->tipo_destinatario === 'ROL' ? 'bg-indigo' : 'bg-primary' }}"
                                        style="{{ $dest->tipo_destinatario === 'ROL' ? 'background:#4f46e5!important' : '' }}">
                                        <i class="bi {{ $dest->tipo_destinatario === 'ROL' ? 'bi-shield' : 'bi-person' }} me-1"></i>
                                        {{ $dest->tipo_destinatario }}
                                    </span>
                                    <span class="fw-semibold small">{{ $dest->label }}</span>
                                </div>
                                <button class="btn btn-sm btn-link text-danger p-0" title="Eliminar"
                                    onclick="eliminarDestinatario({{ $dest->id }})">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── 3. Festivos ─────────────────────────────── --}}
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius:16px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-calendar-x me-2 text-danger"></i>Días Festivos</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" id="btnSyncFestivos">
                            <i class="bi bi-arrow-repeat me-1"></i> Sincronizar Colombia
                        </button>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalFestivo">
                            <i class="bi bi-plus-lg me-1"></i> Registrar Manual
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    @if($festivos->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-check fs-1 d-block mb-2 opacity-25"></i>
                            No hay festivos registrados. Solo se excluyen sábados y domingos.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Día</th>
                                        <th>Descripción</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaFestivos">
                                    @foreach($festivos as $festivo)
                                    <tr id="festivo-row-{{ $festivo->id }}">
                                        <td class="fw-bold">{{ $festivo->fecha->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger">
                                                {{ ucfirst($festivo->fecha->locale('es')->dayName) }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">{{ $festivo->descripcion ?? '—' }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-link text-danger p-0"
                                                onclick="eliminarFestivo({{ $festivo->id }})">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $festivos->links() }}</div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal: Añadir Festivo --}}
<div class="modal fade" id="modalFestivo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Registrar Día Festivo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formFestivo">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha</label>
                        <input type="date" name="fecha" id="inputFechaFestivo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción (opcional)</label>
                        <input type="text" name="descripcion" class="form-control" placeholder="Ej: Día de la Independencia">
                    </div>
                    <button type="submit" class="btn btn-premium-confirm w-100">
                        <i class="bi bi-save me-1"></i> Guardar Festivo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Añadir Destinatario --}}
<div class="modal fade" id="modalDestinatario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Añadir Destinatario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formDestinatario">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select name="tipo_destinatario" id="tipoDestinatario" class="form-select" required>
                            <option value="USUARIO">Usuario</option>
                            <option value="ROL">Rol</option>
                        </select>
                    </div>
                    <div class="mb-4" id="wrapperUsuario">
                        <label class="form-label fw-semibold">Usuario</label>
                        <select name="destinatario_id" id="selectUsuario" class="form-select">
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id }}">{{ $u->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4 d-none" id="wrapperRol">
                        <label class="form-label fw-semibold">Rol</label>
                        <select name="destinatario_id_rol" id="selectRol" class="form-select">
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-premium-confirm w-100">
                        <i class="bi bi-check-circle me-1"></i> Añadir Destinatario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// ── Configuración ─────────────────────────────────────────────────────
document.getElementById('formConfig').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('{{ route("configuracion.alertas.config") }}', {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) showSnackbar('✅ ' + data.message, 'success');
        else showSnackbar('⚠️ ' + data.message, 'error');
    });
});

// ── Festivos ──────────────────────────────────────────────────────────
document.getElementById('btnSyncFestivos').addEventListener('click', function() {
    const btn = this;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sincronizando...';
    btn.disabled = true;

    fetch('{{ route("configuracion.alertas.festivos.sync") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showSnackbar('✅ ' + data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showSnackbar('⚠️ ' + data.message, 'error');
            btn.innerHTML = oldHtml;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        showSnackbar('❌ Error en la conexión', 'error');
        btn.innerHTML = oldHtml;
        btn.disabled = false;
    });
});

document.getElementById('formFestivo').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('{{ route("configuracion.alertas.festivos.store") }}', {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showSnackbar('✅ ' + data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalFestivo')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showSnackbar('⚠️ ' + data.message, 'error');
        }
    });
});

window.eliminarFestivo = function(id) {
    if (!confirm('¿Eliminar este festivo?')) return;
    fetch(`/configuracion/alertas/festivos/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('festivo-row-' + id)?.remove();
            showSnackbar('✅ ' + data.message, 'success');
        }
    });
};

// ── Destinatarios ─────────────────────────────────────────────────────
document.getElementById('tipoDestinatario').addEventListener('change', function() {
    const esRol = this.value === 'ROL';
    document.getElementById('wrapperUsuario').classList.toggle('d-none', esRol);
    document.getElementById('wrapperRol').classList.toggle('d-none', !esRol);
});

document.getElementById('formDestinatario').addEventListener('submit', function(e) {
    e.preventDefault();
    const tipo = document.getElementById('tipoDestinatario').value;
    const id = tipo === 'ROL'
        ? document.getElementById('selectRol').value
        : document.getElementById('selectUsuario').value;

    const fd = new FormData();
    fd.append('tipo_destinatario', tipo);
    fd.append('destinatario_id', id);

    fetch('{{ route("configuracion.alertas.destinatarios.store") }}', {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showSnackbar('✅ ' + data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalDestinatario')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showSnackbar('⚠️ ' + data.message, 'error');
        }
    });
});

window.eliminarDestinatario = function(id) {
    if (!confirm('¿Eliminar este destinatario?')) return;
    fetch(`/configuracion/alertas/destinatarios/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('dest-row-' + id)?.remove();
            showSnackbar('✅ ' + data.message, 'success');
        }
    });
};
</script>
@endpush

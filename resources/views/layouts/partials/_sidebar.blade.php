<div id="sidebar" class="d-flex flex-column flex-shrink-0 p-3 text-white bg-govco-navbar"
    style="width: 210px; height: 100vh; position: sticky; top: 0; z-index: 1000;">
    <div
        class="sidebar-header d-flex align-items-center justify-content-between mb-3 mb-md-0 me-md-auto text-white text-decoration-none w-100">
        <a href="/" class="d-flex align-items-center text-decoration-none sidebar-logo-link">
            <img src="{{ asset('assets/img/logo-gobernacion.png') }}" alt="Logo Gobernación" class="sidebar-logo"
                style="max-width: 160px; height: auto;">
        </a>
        <button id="sidebarToggle" class="btn btn-link text-white p-0">
            <i class="bi bi-list"></i>
        </button>
    </div>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        @if (auth()->user()->puedeAccederConsolidado())
            <li class="nav-item">
                <a href="{{ route('dashboard') }}"
                    class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active bg-primary' : '' }}"
                    aria-current="page">
                    <i class="bi bi-speedometer2 me-2"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
        @endif
        @if (auth()->user()->puedeAccederWorkflow())
            <li>
                <a href="{{ route('workflow') }}"
                    class="nav-link text-white {{ request()->routeIs('workflow') ? 'active bg-primary' : '' }}">
                    <i class="bi bi-grid me-2"></i>
                    <span class="sidebar-text">Workflow</span>
                </a>
            </li>
        @endif
        @if (auth()->user()->puedeAccederSeguimiento())
            <li>
                <a href="{{ route('seguimiento.index') }}"
                    class="nav-link text-white {{ request()->routeIs('seguimiento.*') ? 'active bg-primary' : '' }}">
                    <i class="bi bi-file-earmark-check me-2"></i>
                    <span class="sidebar-text">Seguimiento SECOP</span>
                </a>
            </li>
        @endif
        @if (auth()->user()->puedeAccederAnalitica())
            <li>
                <a href="{{ route('analitica') }}"
                    class="nav-link text-white {{ request()->routeIs('analitica') ? 'active bg-primary' : '' }}">
                    <i class="bi bi-graph-up me-2"></i>
                    <span class="sidebar-text">Analitica</span>
                </a>
            </li>
        @endif
        @if (auth()->user()->isAdmin())
            <li>
                <a href="{{ route('configuracion.index') }}"
                    class="nav-link text-white {{ request()->routeIs('configuracion.*') ? 'active bg-primary' : '' }}">
                    <i class="bi bi-gear me-2"></i>
                    <span class="sidebar-text">Configuración</span>
                </a>
            </li>
        @endif
    </ul>
    <div class="">
        <hr>
        {{-- ── Campana de Notificaciones ── --}}
        <div class="d-flex justify-content-center mb-2">
            <button id="btnCampana" class="btn btn-link text-white p-1 position-relative" data-bs-toggle="modal"
                data-bs-target="#modalNotificaciones" title="Notificaciones" style="font-size: 1.3rem;">
                <i class="bi bi-bell-fill"></i>
                <span id="badgeNotif"
                    class="position-absolute top-0 start-75 translate-middle badge rounded-pill bg-danger d-none"
                    style="font-size: 0.6rem; padding: 3px 5px; min-width: 18px;">0</span>
            </button>
        </div>
        <div class="user-sidebar-section d-flex align-items-center justify-content-between px-1">
            <div class="d-flex align-items-center text-white overflow-hidden user-profile-info">
                <div class="rounded-circle bg-secondary d-flex justify-content-center align-items-center me-2"
                    style="width: 34px; height: 34px; min-width: 34px;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <strong class="sidebar-text text-truncate">{{ auth()->user()->primer_nombre ?? 'Usuario' }}</strong>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="m-0 logout-form-sidebar">
                @csrf
                <button type="submit"
                    class="btn btn-link text-white p-0 border-0 shadow-none d-flex align-items-center justify-content-center"
                    title="Cerrar sesión" style="width: 32px; height: 32px;">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal de Notificaciones ────────────────────────────────────── --}}
<div class="modal fade" id="modalNotificaciones" tabindex="-1" aria-labelledby="modalNotifLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="modal-title text-white mb-0" id="modalNotifLabel">
                        <i class="bi bi-bell-fill me-2 text-warning"></i>Notificaciones
                    </h5>
                    <p class="text-white-50 small mb-0">Gestión de alertas de estancamiento</p>
                </div>
                <div class="d-flex gap-2">
                    <button id="btnMarcarTodas" class="btn btn-sm btn-outline-light"
                        style="font-size: 0.7rem; border-radius: 8px;">
                        <i class="bi bi-check2-all me-1"></i>Leer Todas
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="background: #f8fafc; max-height: 500px;">
                <div id="notifListContainer">
                    <div class="d-flex align-items-center justify-content-center py-5 text-muted" id="notifLoading">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Cargando alertas...
                    </div>
                    <div id="notifList"></div>
                    <div class="text-center py-5 text-muted d-none" id="notifEmpty">
                        <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-25"></i>
                        <p class="mb-0">No tienes alertas pendientes</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-white px-4 py-2">
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Las alertas se actualizan manualmente al abrir este panel.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');

        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            const icon = toggleBtn.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.replace('bi-list', 'bi-chevron-right');
            } else {
                icon.classList.replace('bi-chevron-right', 'bi-list');
            }
        });

        // ── Notificaciones ────────────────────────────────────────────
        const badge = document.getElementById('badgeNotif');
        const notifList = document.getElementById('notifList');
        const notifLoading = document.getElementById('notifLoading');
        const notifEmpty = document.getElementById('notifEmpty');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        function fetchNotificaciones(showLoading = false) {
            // Solo mostrar carga la primera vez que se pide explícitamente y no hay contenido previo
            if (showLoading && notifList.innerHTML.trim() === '') {
                notifLoading.classList.remove('d-none');
                notifList.innerHTML = '';
                notifEmpty.classList.add('d-none');
            }

            window.apiFetch('/notificaciones/latest')
                .then(r => r.json())
                .then(data => {
                    notifLoading.classList.add('d-none');

                    if (!data.success) return;

                    const count = data.count || 0;

                    // Actualizar badge
                    if (count > 0) {
                        badge.classList.remove('d-none');
                        badge.textContent = count;
                    } else {
                        badge.classList.add('d-none');
                    }

                    // Renderizar lista
                    if (count === 0) {
                        notifEmpty.classList.remove('d-none');
                        notifList.innerHTML = '';
                        return;
                    }

                    notifList.innerHTML = '';
                    data.alertas.forEach(a => {
                        const nivelColor = {
                            'WARNING': '#f59e0b', // Naranja (Amber 500)
                            'DANGER': '#dc2626', // Rojo (Red 600)
                            'CRITICAL': '#dc2626',
                            'INFO': '#0284c7',
                            'ERROR': '#dc2626'
                        } [a.nivel] || '#64748b';

                        const nivelIcon = {
                            'WARNING': 'bi-hourglass-split',
                            'DANGER': 'bi-exclamation-triangle-fill',
                            'CRITICAL': 'bi-x-octagon-fill',
                            'INFO': 'bi-info-circle-fill',
                            'ERROR': 'bi-x-circle-fill'
                        } [a.nivel] || 'bi-bell-fill';

                        const fecha = new Date(a.created_at);
                        const fechaStr = fecha.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                        const horaStr = fecha.toLocaleTimeString('es-ES', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const item = document.createElement('div');
                        item.className = 'notif-item px-4 py-3 border-bottom';
                        item.id = 'notif-' + a.id;
                        item.style.cssText =
                            'background:#fff; transition: background 0.2s; cursor:default;';
                        item.innerHTML = `
                    <div class="d-flex gap-3 align-items-start">
                        <div class="notif-icon-wrap mt-1" style="
                            width: 36px; height: 36px; min-width: 36px; border-radius: 10px;
                            background: ${nivelColor}18; display: flex; align-items: center;
                            justify-content: center;">
                            <i class="bi ${nivelIcon}" style="color: ${nivelColor}; font-size: 1rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1 small fw-semibold" style="line-height:1.4;">${a.mensaje}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge rounded-pill" style="background:${nivelColor}18; color:${nivelColor}; font-size:0.65rem; font-weight:700; border: 1px solid ${nivelColor}40;">
                                    ${a.nivel === 'DANGER' ? 'TIEMPO CUMPLIDO' : (a.nivel === 'WARNING' ? 'PRE-AVISO' : a.nivel)}
                                </span>
                                <span class="text-muted" style="font-size:0.7rem;">${fechaStr} &bull; ${horaStr}</span>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-link text-muted p-0 mt-1" title="Marcar como leída"
                            onclick="marcarLeida(${a.id})" style="font-size:1rem; line-height:1;">
                            <i class="bi bi-check2"></i>
                        </button>
                    </div>
                `;
                        notifList.appendChild(item);
                    });
                })
                .catch(err => {
                    notifLoading.classList.add('d-none');
                    console.error('Error cargando notificaciones:', err);
                });
        }

        // Cargar al abrir el modal
        document.getElementById('modalNotificaciones').addEventListener('show.bs.modal', function() {
            fetchNotificaciones(true);
        });

        // Cargar inicialmente al entrar para que el badge "salte" si hay algo
        fetchNotificaciones();


        // Marcar una como leída
        window.marcarLeida = function(id) {
            window.apiFetch(`/notificaciones/leer/${id}`, {
                    method: 'POST'
                })
                .then(r => {
                    if (!r.ok) throw new Error('Error en el servidor');
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        const el = document.getElementById('notif-' + id);
                        if (el) {
                            el.style.opacity = '0';
                            el.style.transition = 'opacity 0.3s';
                            setTimeout(() => {
                                el.remove();
                                // Si no quedan alertas
                                if (notifList.children.length === 0) {
                                    notifEmpty.classList.remove('d-none');
                                    badge.classList.add('d-none');
                                    badge.textContent = '0';
                                } else {
                                    const current = parseInt(badge.textContent) || 1;
                                    const next = Math.max(0, current - 1);
                                    badge.textContent = next;
                                    if (next === 0) badge.classList.add('d-none');
                                }
                            }, 300);
                        }
                    } else {
                        throw new Error(data.message || 'Error al marcar como leída');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    if (window.showSnackbar) window.showSnackbar('Error al marcar notificación: ' + err
                        .message, 'error');
                });
        };

        // Marcar todas como leídas
        const btnMarcarTodas = document.getElementById('btnMarcarTodas');
        if (btnMarcarTodas) {
            btnMarcarTodas.addEventListener('click', function() {
                const originalHtml = btnMarcarTodas.innerHTML;
                btnMarcarTodas.disabled = true;
                btnMarcarTodas.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>Procesando...';

                window.apiFetch('/notificaciones/leer-todas', {
                        method: 'POST'
                    })
                    .then(r => {
                        if (!r.ok) throw new Error('Error en el servidor');
                        return r.json();
                    })
                    .then(data => {
                        if (data.success) {
                            notifList.innerHTML = '';
                            notifEmpty.classList.remove('d-none');
                            badge.classList.add('d-none');
                            badge.textContent = '0';
                            if (window.showSnackbar) window.showSnackbar(
                                'Todas las notificaciones marcadas como leídas');
                        } else {
                            throw new Error(data.message || 'Error al marcar como leídas');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        if (window.showSnackbar) window.showSnackbar(
                            'No se pudieron marcar las notificaciones: ' + err.message, 'error');
                    })
                    .finally(() => {
                        btnMarcarTodas.disabled = false;
                        btnMarcarTodas.innerHTML = originalHtml;
                    });
            });
        }
    });
</script>

@extends('layouts.base')

@section('content')

    {{-- ─── OVERLAY: Fondo oscuro al abrir sidebar en móvil ──────── --}}
    <div id="mobile-sidebar-overlay" role="presentation" aria-hidden="true"></div>

    {{-- ─── TOP BAR MÓVIL (Hamburguesa + Título + Campana) ──────── --}}
    <header id="mobile-top-bar" role="banner" aria-label="Barra de navegación móvil">
        <button id="mobile-hamburger-btn"
                aria-label="Abrir menú de navegación"
                aria-expanded="false"
                aria-controls="sidebar">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <span id="mobile-page-title" aria-live="polite">SGCC</span>

        @if (auth()->user()->puedeVerNotificaciones())
            <button id="mobile-top-notif-btn"
                    aria-label="Ver notificaciones"
                    data-bs-toggle="modal"
                    data-bs-target="#modalNotificaciones">
                <i class="bi bi-bell-fill" aria-hidden="true"></i>
                <span id="badgeNotifMobile"
                      class="position-absolute top-0 start-75 translate-middle badge rounded-pill bg-danger d-none"
                      style="font-size:0.55rem;padding:2px 4px;min-width:16px;"
                      aria-label="Notificaciones no leídas">0</span>
            </button>
        @else
            <div style="width:44px;"></div>
        @endif
    </header>

    {{-- ─── LAYOUT PRINCIPAL (Sidebar + Contenido) ──────────────── --}}
    <div class="container-fluid p-0">
        <div class="d-flex" style="min-height: 100vh;">
            @include('layouts.partials._sidebar')

            <main class="flex-grow-1 p-3 bg-light" style="min-width: 0;">
                @yield('page-content')
            </main>
        </div>
    </div>

    {{-- ─── TAB BAR INFERIOR MÓVIL ───────────────────────────────── --}}
    <nav id="mobile-tab-bar" role="navigation" aria-label="Navegación principal">

        @if (auth()->user()->puedeAccederConsolidado())
        <a href="{{ route('dashboard') }}"
           class="mobile-tab-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           aria-label="Ir al Dashboard"
           aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">
            <i class="bi bi-speedometer2 tab-icon" aria-hidden="true"></i>
            <span class="tab-label">Dashboard</span>
        </a>
        @endif

        @if (auth()->user()->puedeAccederWorkflow())
        <a href="{{ route('workflow') }}"
           class="mobile-tab-item {{ request()->routeIs('workflow') ? 'active' : '' }}"
           aria-label="Ir al Workflow"
           aria-current="{{ request()->routeIs('workflow') ? 'page' : 'false' }}">
            <i class="bi bi-grid tab-icon" aria-hidden="true"></i>
            <span class="tab-label">Workflow</span>
        </a>
        @endif

        @if (auth()->user()->puedeAccederSeguimiento())
        <a href="{{ route('seguimiento.index') }}"
           class="mobile-tab-item {{ request()->routeIs('seguimiento.*') ? 'active' : '' }}"
           aria-label="Ir a Seguimiento SECOP"
           aria-current="{{ request()->routeIs('seguimiento.*') ? 'page' : 'false' }}">
            <i class="bi bi-file-earmark-check tab-icon" aria-hidden="true"></i>
            <span class="tab-label">SECOP</span>
        </a>
        @endif

        @if (auth()->user()->puedeAccederAnalitica())
        <a href="{{ route('analitica') }}"
           class="mobile-tab-item {{ request()->routeIs('analitica') ? 'active' : '' }}"
           aria-label="Ir a Analítica"
           aria-current="{{ request()->routeIs('analitica') ? 'page' : 'false' }}">
            <i class="bi bi-graph-up tab-icon" aria-hidden="true"></i>
            <span class="tab-label">Analítica</span>
        </a>
        @endif

        @if (auth()->user()->puedeVerConfiguracion())
        <a href="{{ route('configuracion.index') }}"
           class="mobile-tab-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}"
           aria-label="Ir a Configuración"
           aria-current="{{ request()->routeIs('configuracion.*') ? 'page' : 'false' }}">
            <i class="bi bi-gear tab-icon" aria-hidden="true"></i>
            <span class="tab-label">Config.</span>
        </a>
        @endif

    </nav>

    {{-- ─── JAVASCRIPT MÓVIL ─────────────────────────────────────── --}}
    @push('scripts')
    <script>
    (function () {
        'use strict';

        // ── Referencias DOM ──────────────────────────────────────────
        const sidebar         = document.getElementById('sidebar');
        const overlay         = document.getElementById('mobile-sidebar-overlay');
        const hamburgerBtn    = document.getElementById('mobile-hamburger-btn');
        const hamburgerIcon   = hamburgerBtn ? hamburgerBtn.querySelector('i') : null;
        const mobilePageTitle = document.getElementById('mobile-page-title');

        // ── Sincronizar badge de notificaciones con el del sidebar ───
        const mainBadge       = document.getElementById('badgeNotif');
        const mobileBadge     = document.getElementById('badgeNotifMobile');

        if (mainBadge && mobileBadge) {
            const syncBadge = () => {
                mobileBadge.textContent  = mainBadge.textContent;
                mobileBadge.className    = mainBadge.className
                    .replace('start-75', 'start-75') // Keep position classes
                    .replace('d-none', '');
                if (mainBadge.classList.contains('d-none')) {
                    mobileBadge.classList.add('d-none');
                } else {
                    mobileBadge.classList.remove('d-none');
                }
            };
            // Observe changes on mainBadge
            new MutationObserver(syncBadge).observe(mainBadge, { attributes: true, characterData: true, childList: true, subtree: true });
        }

        // ── Detectar título de la página activa ──────────────────────
        if (mobilePageTitle) {
            const activeTabLabel = document.querySelector('#mobile-tab-bar .mobile-tab-item.active .tab-label');
            if (activeTabLabel) {
                mobilePageTitle.textContent = activeTabLabel.textContent.trim();
            } else {
                // Fallback: leer el <h1> de la página
                const h1 = document.querySelector('main h1, main .main-dashboard-title');
                if (h1) mobilePageTitle.textContent = h1.textContent.trim().substring(0, 30);
            }
        }

        // ── Open / Close Sidebar ─────────────────────────────────────
        function openSidebar() {
            if (!sidebar) return;
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
            if (hamburgerIcon) {
                hamburgerIcon.classList.replace('bi-list', 'bi-x-lg');
            }
            if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden'; // Prevent background scroll
        }

        function closeSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            if (hamburgerIcon) {
                hamburgerIcon.classList.replace('bi-x-lg', 'bi-list');
            }
            if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        // Hamburguesa → toggle
        if (hamburgerBtn) {
            hamburgerBtn.addEventListener('click', function () {
                if (sidebar.classList.contains('mobile-open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        // Overlay → cerrar al hacer clic fuera
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        // Tecla Escape → cerrar
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('mobile-open')) {
                closeSidebar();
            }
        });

        // Al hacer clic en un enlace del sidebar en móvil → cerrar
        if (sidebar) {
            sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    // Solo cerrar en viewports móviles
                    if (window.innerWidth < 992) {
                        closeSidebar();
                    }
                });
            });
        }

        // ── Resize: limpiar estado abierto al volver a escritorio ────
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) {
                closeSidebar();
                document.body.style.overflow = '';
            }
        });

    }());
    </script>
    @endpush

@endsection

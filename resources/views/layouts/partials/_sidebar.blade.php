<div id="sidebar" class="d-flex flex-column flex-shrink-0 p-3 text-white bg-govco-navbar"
    style="width: 280px; height: 100vh; position: sticky; top: 0; z-index: 1000;">
    <div
        class="sidebar-header d-flex align-items-center justify-content-between mb-3 mb-md-0 me-md-auto text-white text-decoration-none w-100">
        <a href="/" class="d-flex align-items-center text-decoration-none sidebar-logo-link">
            <img src="{{ asset('assets/img/logo-gobernacion.png') }}" alt="Logo Gobernación" class="sidebar-logo"
                style="max-width: 180px; height: auto;">
        </a>
        <button id="sidebarToggle" class="btn btn-link text-white p-0">
            <i class="bi bi-list fs-4"></i>
        </button>
    </div>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        @if (auth()->user()->puedeAccederConsolidado())
            <li class="nav-item">
                <a href="{{ route('dashboard') }}"
                    class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active bg-primary' : '' }}"
                    aria-current="page">
                    <i class="bi bi-speedometer2 me-2 fs-5"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
        @endif
        @if (auth()->user()->puedeAccederWorkflow())
            <li>
                <a href="{{ route('workflow') }}"
                    class="nav-link text-white {{ request()->routeIs('workflow') ? 'active bg-primary' : '' }}">
                    <i class="bi bi-grid me-2 fs-5"></i>
                    <span class="sidebar-text">Workflow</span>
                </a>
            </li>
        @endif
        @if (auth()->user()->puedeAccederAnalitica())
            <li>
                <a href="{{ route('analitica') }}"
                    class="nav-link text-white {{ request()->routeIs('analitica') ? 'active bg-primary' : '' }}">
                    <i class="bi bi-graph-up me-2 fs-5"></i>
                    <span class="sidebar-text">Analitica</span>
                </a>
            </li>
        @endif
        @if (auth()->user()->isAdmin())
            <li>
                <a href="{{ route('configuracion.index') }}"
                    class="nav-link text-white {{ request()->routeIs('configuracion.*') ? 'active bg-primary' : '' }}">
                    <i class="bi bi-gear me-2 fs-5"></i>
                    <span class="sidebar-text">Configuración</span>
                </a>
            </li>
        @endif
    </ul>
    <div class="">
        <hr>
        <div class="user-sidebar-section d-flex align-items-center justify-content-between px-1">
            <div class="d-flex align-items-center text-white overflow-hidden user-profile-info">
                <div class="rounded-circle bg-secondary d-flex justify-content-center align-items-center me-2"
                    style="width: 38px; height: 38px; min-width: 38px;">
                    <i class="bi bi-person-fill fs-5"></i>
                </div>
                <strong class="sidebar-text text-truncate">{{ auth()->user()->primer_nombre ?? 'Usuario' }}</strong>
            </div>
            
            <form method="POST" action="{{ route('logout') }}" class="m-0 logout-form-sidebar">
                @csrf
                <button type="submit" class="btn btn-link text-white p-0 border-0 shadow-none d-flex align-items-center justify-content-center" 
                    title="Cerrar sesión" style="width: 32px; height: 32px;">
                    <i class="bi bi-box-arrow-right fs-4"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const headerText = document.querySelector('.sidebar-header-text');
        const linkTexts = document.querySelectorAll('.sidebar-text');

        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');

            // Adjust icon rotation or state if needed
            const icon = toggleBtn.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('bi-list');
                icon.classList.add('bi-chevron-right');
            } else {
                icon.classList.remove('bi-chevron-right');
                icon.classList.add('bi-list');
            }
        });
    });
</script>

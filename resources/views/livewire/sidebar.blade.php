<aside class="sidebar">
    <div class="logo-area">
        SGCC
    </div>

    <nav class="nav-menu">
        <a href="{{ route('dashboard') }}" class="nav-link" wire:navigate>
            Dashboard
        </a>
        <a href="{{ route('workflow') }}" class="nav-link" wire:navigate>
            Workflows
        </a>
        <a href="{{ route('Analitica') }}" class="nav-link" wire:navigate>
            Analitica
        </a>
    </nav>

    <div class="user-area">
        <div class="user-info">
            <div class="avatar">
                {{ substr(auth()->user()->usuario ?? 'U', 0, 1) }}
            </div>
            <div>
                <p>{{ auth()->user()->usuario ?? 'Usuario' }}</p>
            </div>
        </div>
        
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
                Cerrar Sesión
            </button>
        </form>
    </div>
</aside>

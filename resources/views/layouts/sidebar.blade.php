@extends('layouts.consolidado')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">
            <!-- Sidebar - SIEMPRE VISIBLE -->
            <nav class="header" role="banner">
                <h1 class="logo">
                    <a class="text-muted">Bienvenido</a>
                    <a class="text-muted" href="#">
                        <strong>{{ auth()->user()?->primer_nombre ?? 'Usuario' }}</strong>
                        <span>{{ auth()->user()?->primer_apellido ?? 'Usuario' }}</span>
                    </a>
                </h1>
                <div class="nav-wrap">
                    <nav class="main-nav" role="navigation">
                        <ul class="unstyled list-hover-slide">
                            <li><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li><a class="nav-link" href="{{ route('workflow') }}">Workflow</a></li>
                        </ul>
                    </nav>
                </div>
                <hr>
                <footer class="footer">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm w-100">Cerrar sesión</button>
                    </form>
                </footer>
            </nav>

            <main class="main-content">
                @yield('page-content')
            </main>
        </div>
    </div>
@endsection

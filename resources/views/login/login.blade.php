@extends('layouts.guest')

@section('title', 'Iniciar Sesión - SGCC')

@push('styles')
    @vite(['resources/views/login/login.css'])
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
@endpush

@section('page-content')
    <div class="barra-superior-govco">
        <a href="https://www.gov.co/" target="_blank" rel=noopener aria-label="Portal del Estado Colombiano - GOV.CO"></a>
        <button class="idioma-btn-barra-superior-govco" aria-label="Button to change the language of the page to English">
        </button>
    </div>

    <div class="login-full-screen">
        <div class="premium-card">
            <!-- Izquierda: Bienvenida Institucional -->
            <div class="premium-left">
                <div class="premium-left-card" id="consultation-card">
                    <h2>Conoce el estado de tu cuenta</h2>
                    <p>Ingresa tu cédula y valida en qué estado se encuentra tu cuenta de manera rápida y segura.</p>

                    <div class="search-field-prem">
                        <label>Número de Cédula / NIT</label>
                        <input type="text" id="consult-nit" class="search-input-prem" placeholder="Ej: 1234567890">
                    </div>

                    <button type="button" id="btn-consultar" class="btn-search-prem">
                        <span id="btn-text">Consultar Estado</span>
                        <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>

                    <div id="results-area" class="results-container d-none">
                        <!-- AJAX Results will appear here -->
                    </div>
                </div>
            </div>

            <!-- Derecha: Formulario White Card -->
            <div class="premium-right">
                <div class="white-login-box">
                    <div class="premium-logo">
                        <img src="{{ asset('assets/img/logo-gobernacion.png') }}" alt="Logo Gobernación"
                            style="width: 100%;">
                    </div>
                    <h1>Iniciar Sesión</h1>
                    <p class="subtitle">Ingresa tus credenciales para continuar</p>

                    @if ($errors->any())
                        <div class="alert alert-danger"
                            style="font-size: 0.75rem; border-radius: 10px; margin-bottom: 20px;">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.attempt') }}">
                        @csrf
                        <div class="form-group-prem">
                            <label for="user">Usuario</label>
                            <input type="text" name="user" value="{{ old('user') }}" class="input-prem"
                                id="user" placeholder="Ej: admin" required autofocus>
                        </div>

                        <div class="form-group-prem">
                            <label for="password">Contraseña</label>
                            <input type="password" name="password" class="input-prem" id="password" placeholder="••••••••"
                                required>
                        </div>

                        <button type="submit" class="btn-prem-login">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('btn-consultar').addEventListener('click', function() {
            const nit = document.getElementById('consult-nit').value;
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            const resultsArea = document.getElementById('results-area');

            if (!nit) {
                alert('Por favor ingresa un NIT o Cédula');
                return;
            }

            // UI Loading state
            btnText.textContent = 'Consultando...';
            btnSpinner.classList.remove('d-none');
            resultsArea.classList.add('d-none');

            fetch("{{ route('public.consultation') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        nit: nit
                    })
                })
                .then(response => response.json())
                .then(data => {
                    btnText.textContent = 'Consultar Estado';
                    btnSpinner.classList.add('d-none');

                    if (data.error) {
                        resultsArea.innerHTML = `<div class="text-warning small">${data.error}</div>`;
                    } else {
                        resultsArea.innerHTML = `
                        <div class="result-item">
                            <span class="result-label">Contratista</span>
                            <span class="result-value">${data.contratista}</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">Estado Actual</span>
                            <span class="status-badge">${data.estado}</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">Bloque</span>
                            <span class="result-value">${data.bloque}</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">Última Actualización</span>
                            <span class="result-value" style="font-size: 0.8rem; opacity: 0.7;">${data.ultima_actualizacion}</span>
                        </div>
                    `;
                    }
                    resultsArea.classList.remove('d-none');
                })
                .catch(error => {
                    btnText.textContent = 'Consultar Estado';
                    btnSpinner.classList.add('d-none');
                    resultsArea.innerHTML =
                        `<div class="text-danger small">Error al conectar con el servidor.</div>`;
                    resultsArea.classList.remove('d-none');
                });
        });
    </script>
@endsection

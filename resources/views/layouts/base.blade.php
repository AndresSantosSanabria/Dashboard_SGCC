<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap (gov.co v5 depende de Bootstrap 5) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- GOV.CO v5 CDN - Fuente de verdad para estilos institucionales --}}
    <link rel="stylesheet" href="https://cdn.www.gov.co/layout-govco-v5/all.css">

    {{-- SweetAlert2 para diálogos premium --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Assets locales procesados por Vite --}}
    @vite(['resources/css/app.css', 'resources/css/snackbar.css'])
    <link rel="stylesheet" href="{{ asset('css/premium-bi.css') }}">

    @stack('styles')

</head>

<body style="overflow-x: hidden;">

    @yield('content')

    {{-- Snackbar Global --}}
    <div id="snackbar"></div>

    <script>
        window.showSnackbar = function(message, type = 'success') {
            const snackbar = document.getElementById("snackbar");
            if (!snackbar) return;

            // Limpiar mensajes técnicos para el usuario
            let cleanMessage = message;
            
            if (message.includes("SQLSTATE") || message.includes("Integrity constraint") || message.includes(
                    "column")) {
                cleanMessage = "Error técnico en la base de datos. Por favor contacte al administrador.";
            } else if (message.includes("CSRF") || message.includes("mismatch")) {
                cleanMessage = "Sesión expirada o error de seguridad. Por favor recargue la página.";
            } else if (message.includes("configuration") || message.includes("RAD")) {
                cleanMessage = "Error de configuración de flujo. Por favor informe al administrador.";
            }
            

            snackbar.textContent = cleanMessage;
            snackbar.className = "show " + type;

            const duration = cleanMessage.length > 50 ? 5000 : 3000;
            setTimeout(function() {
                snackbar.className = snackbar.className.replace("show", "");
            }, duration);
        };
    </script>


    {{-- Preferir script local copiado a public/assets o public/build/assets; si no existe, usar CDN gov.co --}}
    @if (file_exists(public_path('assets/js/script.js')))
        <script src="{{ asset('assets/js/script.js') }}"></script>
    @elseif (file_exists(public_path('build/assets/js/script.js')))
        <script src="{{ asset('build/assets/js/script.js') }}"></script>
    @else
        <script src="https://cdn.www.gov.co/layout-govco-v5/script.js"></script>
    @endif

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    {{-- App JS (vite) --}}
    @if (class_exists('\Laravel\Vite\Vite'))
        @vite(['resources/js/app.js'])
    @endif

    @stack('scripts')
</body>

</html>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    /**
    * LAYOUT BASE - SGCC
    *
    * Este es el contenedor principal de la aplicación.
    * Sigue los estándares de la Guía de Diseño Digital GOV.CO v5 para
    * garantizar la accesibilidad y la identidad institucional.
    */
    --}}

    {{-- GOV.CO v5 depende de Bootstrap 5 para el sistema de rejilla y utilidades --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Fuente de verdad para estilos institucionales --}}
    <link rel="stylesheet" href="https://cdn.www.gov.co/layout-govco-v5/all.css">

    {{-- Herramientas de Feedback de Usuario: SweetAlert2 para modales y snackbar para notificaciones leves --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Assets locales: Usamos Vite para un hot-reload rápido en desarrollo y builds ligeros --}}
    @vite(['resources/css/app.css', 'resources/css/snackbar.css'])
    <link rel="stylesheet" href="{{ asset('css/premium-bi.css') }}">

    @stack('styles')

</head>

<body style="overflow-x: hidden;">

    @yield('content')

    {{-- SNACKBAR GLOBAL: 
         Mantiene al usuario informado sobre el éxito o fracaso de sus acciones 
         sin interrumpir el flujo con modales intrusivos.
    --}}
    <div id="snackbar"></div>

    <script>
        /**
         * Lógica de Notificaciones Dinámicas
         * Procesa y limpia mensajes de error para que sean legibles por humanos, 
         * ocultando detalles técnicos sensibles (SQL, etc).
         */
        window.showSnackbar = function(message, type = 'success') {
            const snackbar = document.getElementById("snackbar");
            if (!snackbar) return;

            let cleanMessage = message;

            // Filtro de "Mensajes Amigables": Evitamos que el usuario vea excepciones de BD directamente
            if (message.includes("SQLSTATE") || message.includes("Integrity constraint") || message.includes(
                "column")) {
                cleanMessage = "Error técnico en la base de datos. Por favor contacte al administrador.";
            } else if (message.includes("CSRF") || message.includes("mismatch")) {
                cleanMessage = "Sesión expirada o error de seguridad. Por favor recargue la página.";
            }

            snackbar.textContent = cleanMessage;
            snackbar.className = "show " + type;

            const duration = cleanMessage.length > 50 ? 5000 : 3000;
            setTimeout(function() {
                snackbar.className = snackbar.className.replace("show", "");
            }, duration);
        };
    </script>

    {{-- Estrategia de Carga de Scripts:
         Intentamos cargar el script compilado local (Vite/Mix) para velocidad; 
         si falla o no existe, recurrimos al CDN oficial de GOV.CO como respaldo.
    --}}
    @if (file_exists(public_path('assets/js/script.js')))
        <script src="{{ asset('assets/js/script.js') }}"></script>
    @elseif (file_exists(public_path('build/assets/js/script.js')))
        <script src="{{ asset('build/assets/js/script.js') }}"></script>
    @else
        <script src="https://cdn.www.gov.co/layout-govco-v5/script.js"></script>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>

</html>

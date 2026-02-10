<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>

    {{-- Preferir assets locales: public/assets o public/build/assets (Vite). Si no existen, usar CDN gov.co --}}
    @if (file_exists(public_path('assets/css/all.css')))
        <link rel="stylesheet" href="{{ asset('resources/css/all.css') }}">
    @elseif (file_exists(public_path('build/assets/css/all.css')))
        <link rel="stylesheet" href="{{ asset('build/assets/css/all.css') }}">
    @else
        <link rel="stylesheet" href="https://cdn.www.gov.co/layout-govco-v5/all.css">
    @endif

    {{-- Bootstrap (gov.co v5 depende de Bootstrap 5) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- CSS de Vite: all.css (gov.co) y sidebar.css --}}
    @vite(['resources/css/all.css', 'resources/css/sidebar.css'])

</head>

<body style="overflow-x: hidden;">


    @yield('content')


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

</body>

</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard SGCC' }}</title>
    @vite(['resources/css/app.css', 'resources/css/login.css', 'resources/css/Dashboard.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    {{ $slot }}
    @livewireScripts
</body>

</html>

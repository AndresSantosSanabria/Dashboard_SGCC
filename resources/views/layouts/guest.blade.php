@extends('layouts.base')

@section('content')
    <div class="barra-superior-govco">
        <a href="https://www.gov.co/" target="_blank" rel=noopener aria-label="Portal del Estado Colombiano - GOV.CO"></a>
        <button class="idioma-btn-barra-superior-govco" aria-label="Button to change the language of the page to English">
        </button>
    </div>

    <main class="py-4">
        @yield('page-content')
    </main>
@endsection

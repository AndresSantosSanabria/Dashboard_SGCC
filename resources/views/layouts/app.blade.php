@extends('layouts.base')

@section('content')
    <div class="container-fluid p-0">
        <div class="d-flex" style="min-height: 100vh;">
            @include('layouts.partials._sidebar')

            <main class="flex-grow-1 p-3 bg-light w-100" style="overflow-x: hidden;">
                @yield('page-content')
            </main>
        </div>
    </div>
@endsection

@extends('layouts.guest')

@section('title', 'Iniciar Sesión - SGCC')

@push('styles')
    @vite(['resources/views/login/login.css'])
@endpush

@section('page-content')
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-5 offset-lg-3">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Iniciar Sesión</h4>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.attempt') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="user" class="form-label">Usuario</label>
                                <input type="text" name="user" value="{{ old('user') }}" class="form-control"
                                    id="user" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" id="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn-govco outline-btn-govco"
                                    style="width: 165px; height: 42px;">
                                    Ingresar
                                </button>
                            </div>

                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <small><a href="/">Volver al inicio</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

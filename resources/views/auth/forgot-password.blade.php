@extends('layouts.auth', ['titulo' => 'Recuperar contraseña'])

@section('contenido')
    <div class="text-center">
        <h4 class="text-1000">¿Olvidaste tu contraseña?</h4>
        <p class="text-700 mb-4">Escribe tu correo y te enviaremos un enlace para crear una nueva.</p>

        @include('partials.alertas')

        <form method="POST" action="{{ route('password.email') }}" class="d-flex align-items-center mb-4">
            @csrf
            <div class="form-icon-container flex-1">
                <input class="form-control form-icon-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="tu@correo.com" required autofocus maxlength="150" />
                <span class="fas fa-envelope text-900 fs--1 form-icon"></span>
            </div>
            <button class="btn btn-primary ms-2 text-nowrap" type="submit">Enviar enlace<span class="fas fa-chevron-right ms-2"></span></button>
        </form>
        <a class="fs--1 fw-bold" href="{{ route('login') }}"><span class="fas fa-arrow-left me-1"></span>Volver a iniciar sesión</a>
    </div>
@endsection

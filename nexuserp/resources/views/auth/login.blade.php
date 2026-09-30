@extends('layouts.auth', ['titulo' => 'Iniciar sesión'])

@php $cfg = \App\Support\Sistema::config(); @endphp

@section('contenido')
    <div class="text-center mb-6">
        <h3 class="text-1000">{{ $cfg->loginTitulo ?: 'Iniciar sesión' }}</h3>
        <p class="text-700">{{ $cfg->loginMensajeBienve ?: 'Ingresa tus credenciales para continuar' }}</p>
    </div>

    @include('partials.alertas')

    <form method="POST" action="{{ route('login') }}" autocomplete="on">
        @csrf
        <div class="mb-3 text-start">
            <label class="form-label" for="login">{{ $cfg->loginLabelUsuario ?: 'Usuario o correo electrónico' }}</label>
            <div class="form-icon-container">
                <input class="form-control form-icon-input @error('login') is-invalid @enderror" id="login" name="login" type="text"
                       value="{{ old('login') }}" placeholder="{{ $cfg->loginPlaceholderUs ?: 'usuario o correo@empresa.com' }}"
                       required autofocus autocomplete="username" maxlength="150" />
                <span class="fas fa-user text-900 fs--1 form-icon"></span>
            </div>
        </div>
        <div class="mb-3 text-start">
            <label class="form-label" for="password">{{ $cfg->loginLabelPassword ?: 'Contraseña' }}</label>
            <div class="form-icon-container">
                <input class="form-control form-icon-input" id="password" name="password" type="password" placeholder="••••••••" required autocomplete="current-password" />
                <span class="fas fa-key text-900 fs--1 form-icon"></span>
            </div>
        </div>
        <div class="row flex-between-center mb-6">
            <div class="col-auto">
                <div class="form-check mb-0">
                    <input class="form-check-input" id="remember" name="remember" type="checkbox" @checked(old('remember')) />
                    <label class="form-check-label mb-0" for="remember">{{ $cfg->loginLabelRecordar ?: 'Recordar sesión' }}</label>
                </div>
            </div>
            <div class="col-auto">
                <a class="fs--1 fw-semi-bold" href="{{ route('password.request') }}">{{ $cfg->loginLinkOlvide ?: '¿Olvidaste tu contraseña?' }}</a>
            </div>
        </div>
        <button class="btn btn-primary w-100 mb-3" type="submit">{{ $cfg->loginTextBoton ?: 'Iniciar sesión' }}</button>
    </form>
@endsection

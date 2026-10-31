@extends('layouts.auth', ['titulo' => 'Nueva contraseña'])

@section('contenido')
    <div class="text-center mb-5">
        <h4 class="text-1000">Restablecer nueva contraseña</h4>
        <p class="text-700 mb-0">Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos.</p>
    </div>

    @include('partials.alertas')

    <form method="POST" action="{{ route('password.update') }}" class="mt-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3 text-start">
            <label class="form-label" for="email">Correo electrónico</label>
            <div class="form-icon-container">
                <input class="form-control form-icon-input" id="email" name="email" type="email" value="{{ old('email', $request->email) }}" placeholder="tu@correo.com" required autocomplete="username" />
                <span class="fas fa-envelope text-900 fs--1 form-icon"></span>
            </div>
        </div>
        <div class="mb-3 text-start">
            <label class="form-label" for="password">Nueva contraseña</label>
            <div class="form-icon-container">
                <input class="form-control form-icon-input" id="password" name="password" type="password" placeholder="Nueva contraseña" required autocomplete="new-password" />
                <span class="fas fa-key text-900 fs--1 form-icon"></span>
            </div>
        </div>
        <div class="mb-4 text-start">
            <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
            <div class="form-icon-container">
                <input class="form-control form-icon-input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Confirmar nueva contraseña" required autocomplete="new-password" />
                <span class="fas fa-lock text-900 fs--1 form-icon"></span>
            </div>
        </div>
        <button class="btn btn-primary w-100" type="submit">Guardar contraseña</button>
    </form>
@endsection

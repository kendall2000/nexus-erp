@extends('layouts.auth', ['titulo' => 'Confirmar contraseña'])

@section('contenido')
    @php
        $u = auth()->user();
        $avatar = $u?->avatar_url ?: \App\Support\Sistema::config()->imgAvatarDefault;
    @endphp
    <div class="text-center mb-4">
        @if ($u)
            <div class="avatar avatar-4xl mb-3">
                @if ($avatar)
                    <img class="rounded-circle" src="{{ $avatar }}" alt="{{ $u->nombre_completo }}" />
                @else
                    <div class="avatar-name rounded-circle"><span>{{ mb_strtoupper(mb_substr($u->nombre_completo ?: $u->username, 0, 1)) }}</span></div>
                @endif
            </div>
            <h2 class="text-1000"><span class="fw-normal">Hola, </span>{{ $u->nombre_completo ?: $u->username }}</h2>
        @else
            <h4 class="text-1000">Confirma tu contraseña</h4>
        @endif
        <p class="text-700 mb-0">Es una zona protegida: vuelve a escribir tu contraseña para continuar.</p>
    </div>

    @include('partials.alertas')

    <form method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        <div class="mb-3 text-start">
            <label class="form-label visually-hidden" for="password">Contraseña</label>
            <div class="form-icon-container">
                <input class="form-control form-icon-input" id="password" name="password" type="password" placeholder="Escribe tu contraseña" required autofocus autocomplete="current-password" />
                <span class="fas fa-key text-900 fs--1 form-icon"></span>
            </div>
        </div>
        <button class="btn btn-primary w-100" type="submit">Confirmar</button>
    </form>
@endsection

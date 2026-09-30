{{-- Mensajes de estado (Fortify y controladores) y errores de validación. --}}
@if (session('status'))
    <div class="alert alert-soft-success py-2 fs--1" role="alert">{{ session('status') }}</div>
@endif

@if (session('aviso'))
    <div class="alert alert-soft-warning py-2 fs--1" role="alert">{{ session('aviso') }}</div>
@endif

{{-- Todas las bolsas de errores. --}}
@php $listaErrores = collect($errors->getBags())->flatMap(fn ($bolsa) => $bolsa->all()); @endphp
@if ($listaErrores->isNotEmpty())
    <div class="alert alert-soft-danger py-2 fs--1" role="alert">
        @foreach ($listaErrores as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

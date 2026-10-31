@props([
    'etiqueta',
    'valor',
    'subtexto' => null,
    'icono' => 'fas fa-chart-line',
    'color' => 'primary',
    'alerta' => false,
])

<div class="card h-100">
    <div class="card-body py-3 d-flex align-items-center justify-content-between">
        <div>
            <p class="fs--1 text-700 fw-semi-bold mb-1">{{ $etiqueta }}</p>
            <h3 class="mb-0 {{ $alerta ? 'text-danger' : 'text-1100' }}">{{ $valor }}</h3>
            @if ($subtexto)
                <p class="fs--2 {{ $alerta ? 'text-danger fw-semi-bold' : 'text-600' }} mb-0 mt-1">{{ $subtexto }}</p>
            @endif
        </div>
        <div class="icon-wrapper-sm bg-{{ $color }}-100 rounded-3 d-flex align-items-center justify-content-center" style="width:2.5rem;height:2.5rem">
            <span class="{{ $icono }} text-{{ $color }} fs-0"></span>
        </div>
    </div>
</div>

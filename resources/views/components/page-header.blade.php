@props([
    'titulo',
    'subtitulo' => null,
    'icono' => null,
    'breadcrumbs' => [],
])

@if (!empty($breadcrumbs))
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            @foreach ($breadcrumbs as $label => $url)
                @if ($url && !$loop->last)
                    <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">{{ is_int($label) ? $url : $label }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif

<div class="row align-items-center justify-content-between g-3 mb-4">
    <div class="col-auto">
        <h2 class="text-bold text-1100 mb-1">
            @if ($icono)
                <span class="{{ $icono }} text-primary me-2 fs-0"></span>
            @endif
            {{ $titulo }}
            @isset($badge)
                {{ $badge }}
            @endisset
        </h2>
        @if ($subtitulo)
            <p class="text-700 fw-semi-bold lh-sm mb-0">{{ $subtitulo }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="col-auto">
            <div class="d-flex flex-wrap align-items-center gap-2">
                {{ $slot }}
            </div>
        </div>
    @endif
</div>

@props([
    'titulo' => null,
    'title' => null,
    'subtitulo' => null,
    'subtitle' => null,
    'icono' => null,
    'icon' => null,
    'breadcrumbs' => [],
    'migas' => [],
])

@php
    $tituloFinal = $titulo ?? $title ?? '';
    $subtituloFinal = $subtitulo ?? $subtitle;
    $iconoFinal = $icono ?? $icon;
    $migasFinal = !empty($breadcrumbs) ? $breadcrumbs : $migas;
@endphp

@if (!empty($migasFinal))
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            @foreach ($migasFinal as $key => $item)
                @php
                    if (is_array($item)) {
                        $itemLabel = $item['label'] ?? $item['titulo'] ?? '';
                        $itemUrl = $item['url'] ?? null;
                    } elseif (is_int($key)) {
                        $itemLabel = $item;
                        $itemUrl = null;
                    } else {
                        $itemLabel = $key;
                        $itemUrl = $item;
                    }
                @endphp
                @if ($itemUrl && !$loop->last)
                    <li class="breadcrumb-item"><a href="{{ $itemUrl }}">{{ $itemLabel }}</a></li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">{{ $itemLabel }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif

<div class="row align-items-center justify-content-between g-3 mb-4">
    <div class="col-auto">
        <h2 class="text-bold text-1100 mb-1">
            @if ($iconoFinal)
                <span class="{{ $iconoFinal }} text-primary me-2 fs-0"></span>
            @endif
            {{ $tituloFinal }}
            @isset($badge)
                {{ $badge }}
            @endisset
            @isset($insignia)
                {{ $insignia }}
            @endisset
        </h2>
        @if (isset($subtituloSlot) && $subtituloSlot->isNotEmpty())
            <div class="text-700 fw-semi-bold lh-sm mb-0">{{ $subtituloSlot }}</div>
        @elseif ($subtituloFinal)
            <p class="text-700 fw-semi-bold lh-sm mb-0">{{ $subtituloFinal }}</p>
        @endif
    </div>
    @php
        $tieneAcciones = (isset($acciones) && $acciones->isNotEmpty()) || $slot->isNotEmpty();
    @endphp
    @if ($tieneAcciones)
        <div class="col-auto">
            <div class="d-flex flex-wrap align-items-center gap-2">
                @isset($acciones)
                    {{ $acciones }}
                @endisset
                {{ $slot }}
            </div>
        </div>
    @endif
</div>

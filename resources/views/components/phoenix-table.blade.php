@props([
    'paginador' => null,
    'paginator' => null,
    'filtros' => null,
    'sinMargenNegativo' => false,
    'valueNames' => null,
    'id' => null,
])

@php
    $paginadorFinal = $paginador ?? $paginator;
@endphp

<div {{ $attributes->merge(['class' => '']) }} @if($id) id="{{ $id }}" @endif>
    @if (isset($toolbar))
        <div class="mb-4">
            {{ $toolbar }}
        </div>
    @endif
    @if (isset($filtros) && $filtros instanceof \Illuminate\View\ComponentSlot && $filtros->isNotEmpty())
        <div class="mb-4">
            {{ $filtros }}
        </div>
    @endif

    <div class="{{ $sinMargenNegativo ? 'bg-white border rounded-3 border-200' : 'mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1' }}">
        <div class="table-responsive scrollbar mx-n1 px-1">
            @if (isset($head))
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead>
                        @if (str_contains((string) $head, '<tr'))
                            {{ $head }}
                        @else
                            <tr>{{ $head }}</tr>
                        @endif
                    </thead>
                    <tbody class="list">
                        {{ $slot }}
                    </tbody>
                </table>
            @else
                {{ $slot }}
            @endif
        </div>
        @if ($paginadorFinal && method_exists($paginadorFinal, 'hasPages') && $paginadorFinal->hasPages())
            <div class="row align-items-center justify-content-between py-2 pe-0 fs--1 border-top border-200">
                <div class="col-auto d-flex">
                    <p class="mb-0 d-none d-sm-block me-3 fw-semi-bold text-900">
                        Mostrando {{ $paginadorFinal->firstItem() ?? 0 }} a {{ $paginadorFinal->lastItem() ?? 0 }}
                        <span class="text-600">de</span> {{ $paginadorFinal->total() }} registros
                    </p>
                </div>
                <div class="col-auto d-flex">
                    {{ $paginadorFinal->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

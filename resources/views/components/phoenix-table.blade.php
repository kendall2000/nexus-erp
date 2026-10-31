@props([
    'paginador' => null,
    'filtros' => null,
    'sinMargenNegativo' => false,
])

<div {{ $attributes->merge(['class' => '']) }}>
    @if (isset($toolbar))
        <div class="mb-4">
            {{ $toolbar }}
        </div>
    @endif

    <div class="{{ $sinMargenNegativo ? 'bg-white border rounded-3 border-200' : 'mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1' }}">
        <div class="table-responsive scrollbar mx-n1 px-1">
            {{ $slot }}
        </div>
        @if ($paginador && $paginador->hasPages())
            <div class="row align-items-center justify-content-between py-2 pe-0 fs--1 border-top border-200">
                <div class="col-auto d-flex">
                    <p class="mb-0 d-none d-sm-block me-3 fw-semi-bold text-900">
                        Mostrando {{ $paginador->firstItem() ?? 0 }} a {{ $paginador->lastItem() ?? 0 }}
                        <span class="text-600">de</span> {{ $paginador->total() }} registros
                    </p>
                </div>
                <div class="col-auto d-flex">
                    {{ $paginador->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

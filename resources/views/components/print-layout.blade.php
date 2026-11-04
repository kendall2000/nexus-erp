@props([
    'titulo',
    'empresa' => null,
    'volverUrl' => null,
])
@php
    $cfg = \App\Support\Sistema::config();
    $nombreEmpresa = $empresa?->nombre_legal ?: ($empresa?->nombre_comercial ?: \App\Support\Sistema::nombre());
    // Cada empresa imprime con sus propios datos; lo que le falte sale de los del consorcio.
    $logo = $empresa ? $empresa->logoDocumentos() : ($cfg->imgLogoReporte ?: $cfg->imgLogo);
    $direccion = $empresa?->direccion ?: $cfg->direccion;
    $contacto = $empresa
        ? collect([$empresa->telefono, $empresa->email_corporativo, $empresa->sitio_web])->filter()->implode(' · ')
        : collect([$cfg->telefono, $cfg->correoContacto, $cfg->sitioWeb])->filter()->implode(' · ');
@endphp
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head', ['titulo' => $titulo])
    <style>
        @media print {
            .no-imprimir { display: none !important; }
            body { background: #fff !important; }
            .print-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-100">
    <main class="main py-4">
        <div class="container-small">
            <div class="d-flex justify-content-between align-items-center mb-4 no-imprimir">
                <div>
                    @if ($volverUrl)
                        <a class="btn btn-phoenix-secondary btn-sm" href="{{ $volverUrl }}">
                            <span class="fas fa-arrow-left me-2"></span>Volver
                        </a>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" onclick="window.print()">
                        <span class="fa-solid fa-print me-2"></span>Imprimir
                    </button>
                </div>
            </div>

            <div class="card print-card shadow-sm border border-200">
                <div class="card-body p-4 p-md-6">
                    <div class="bg-soft dark__bg-1000 rounded-2 p-4 mb-4">
                        <div class="row g-3 justify-content-between align-items-start">
                            <div class="col-12 col-sm-6">
                                @if ($logo)
                                    <img src="{{ $logo }}" alt="" class="mb-2" style="max-height: 48px" />
                                @endif
                                <h4 class="text-1100 fw-bolder mb-1">{{ $nombreEmpresa }}</h4>
                                @if ($empresa?->nombre_comercial && $empresa->nombre_comercial !== $nombreEmpresa)
                                    <p class="fs--1 text-800 mb-0">{{ $empresa->nombre_comercial }}</p>
                                @endif
                                @if ($empresa?->nit || $cfg->nit)
                                    <p class="fs--1 text-800 fw-semi-bold mb-0">NIT: {{ $empresa?->nit ?: $cfg->nit }}</p>
                                @endif
                                @if ($empresa?->regimen_fiscal)
                                    <p class="fs--2 text-700 mb-0">{{ $empresa->regimen_fiscal }}</p>
                                @endif
                                @if ($direccion)
                                    <p class="fs--2 text-600 mb-0">{{ $direccion }}</p>
                                @endif
                                @if ($contacto)
                                    <p class="fs--2 text-600 mb-0">{{ $contacto }}</p>
                                @endif
                            </div>
                            <div class="col-12 col-sm-6 text-sm-end">
                                {{ $encabezado ?? '' }}
                            </div>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </main>
</body>
</html>

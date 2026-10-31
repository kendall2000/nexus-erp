@php
    $cfg = \App\Support\Sistema::config();
    $nombreEmpresa = $empresa?->nombre_legal ?: ($empresa?->nombre_comercial ?: \App\Support\Sistema::nombre());
@endphp
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    @include('partials.head', ['titulo' => 'Boletas — '.$p->nombre])
    <style>
        .boleta-card { page-break-inside: avoid; }
        @media print {
            .no-imprimir { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-100">
    <main class="main py-4">
        <div class="container-small">
            <div class="d-flex justify-content-between align-items-center mb-4 no-imprimir">
                <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('nomina.show', $p->id_periodo) }}">
                    <span class="fas fa-arrow-left me-2"></span>Volver al periodo
                </a>
                <button class="btn btn-primary btn-sm" onclick="window.print()">
                    <span class="fa-solid fa-print me-2"></span>Imprimir
                </button>
            </div>

            @foreach ($detalles as $d)
                @php
                    $ingresos = $d->conceptos->where('tipo', 'INGRESO');
                    $deducciones = $d->conceptos->where('tipo', 'DEDUCCION');
                @endphp
                <div class="card boleta-card shadow-sm border border-200 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start border-bottom border-300 pb-3 mb-3">
                            <div>
                                <h5 class="text-1100 fw-bolder mb-0">{{ $nombreEmpresa }}</h5>
                                <span class="badge badge-phoenix badge-phoenix-primary mt-1">BOLETA DE PAGO · {{ $p->nombre }}</span>
                            </div>
                            <div class="text-sm-end fs--1">
                                <strong class="text-1100">{{ $d->empleado?->nombre_completo }}</strong><br>
                                <span class="text-700">{{ $d->empleado?->codigo_empleado }} · {{ $d->cargo_snapshot }}</span><br>
                                <span class="text-600">{{ $p->fecha_inicio?->format('d/m/Y') }} – {{ $p->fecha_fin?->format('d/m/Y') }} · {{ rtrim(rtrim(number_format((float) $d->dias_trabajados, 2), '0'), '.') }} días</span>
                            </div>
                        </div>

                        <div class="row g-4 fs--1">
                            <div class="col-md-6">
                                <table class="table table-sm mb-0">
                                    <thead class="bg-200">
                                        <tr><th colspan="2" class="ps-2">Ingresos</th></tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($ingresos as $l)
                                        <tr>
                                            <td class="ps-2">{{ $l->concepto?->nombre }} {{ $l->descripcion ? '('.$l->descripcion.')' : '' }}</td>
                                            <td class="pe-2 text-end">{{ number_format((float) $l->monto, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot class="border-top border-300 fw-bold">
                                        <tr>
                                            <td class="ps-2">Total ingresos</td>
                                            <td class="pe-2 text-end">{{ number_format((float) $d->total_ingresos, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm mb-0">
                                    <thead class="bg-200">
                                        <tr><th colspan="2" class="ps-2">Deducciones</th></tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($deducciones as $l)
                                        <tr>
                                            <td class="ps-2">{{ $l->concepto?->nombre }} {{ $l->descripcion ? '('.$l->descripcion.')' : '' }}</td>
                                            <td class="pe-2 text-end text-danger">{{ number_format((float) $l->monto, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot class="border-top border-300 fw-bold">
                                        <tr>
                                            <td class="ps-2">Total deducciones</td>
                                            <td class="pe-2 text-end">{{ number_format((float) $d->total_deducciones, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="bg-soft rounded-2 p-2 px-3 mt-3 d-flex justify-content-between align-items-center">
                            <span class="fs--1 fw-semi-bold text-800">Líquido a recibir</span>
                            <span class="fs-0 fw-bolder text-1100">{{ $p->moneda }} {{ number_format((float) $d->liquido_pagar, 2) }}</span>
                        </div>

                        <div class="row g-5 mt-4 pt-3 text-center fs--2 text-700">
                            <div class="col-6">
                                <div class="border-top border-300 pt-1">Recibí conforme: {{ $d->empleado?->nombre_completo }}</div>
                            </div>
                            <div class="col-6">
                                <div class="border-top border-300 pt-1">Recursos humanos</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </main>
</body>
</html>

@extends('layouts.app', ['titulo' => 'Reporte consolidado'])

@php
    use App\Http\Controllers\ConsolidadoController as R;
    $dinero = fn ($n) => number_format((float) $n, 2);
    $empresa = fn ($id) => $datos['empresas'][$id]->nombre_comercial ?? "Empresa {$id}";
    $meses = R::MESES;
@endphp

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Reporte consolidado</li>
        </ol>
    </nav>

    <div class="row align-items-center justify-content-between g-3 mb-4">
        <div class="col-12 col-md-auto">
            <h2 class="mb-1 text-1100">Reporte consolidado {{ $anio }}</h2>
            <h5 class="text-700 fw-semi-bold mb-0">
                {{ $datos['empresas']->pluck('nombre_comercial')->join(', ', ' y ') }}
            </h5>
        </div>
        <div class="col-12 col-md-auto">
            <form method="GET" action="{{ route('consolidado.index') }}" class="d-flex gap-2 align-items-center">
                <label class="text-800 fw-semi-bold fs--1 text-nowrap" for="anio">Año</label>
                <select class="form-select form-select-sm" id="anio" name="anio" onchange="this.form.submit()">
                    @foreach ($anios as $a)
                        <option value="{{ $a }}" @selected($a === $anio)>{{ $a }}</option>
                    @endforeach
                </select>
                @if ($puedeExportar)
                    <a class="btn btn-phoenix-secondary btn-sm text-nowrap" href="{{ route('consolidado.exportar', ['anio' => $anio]) }}">
                        <span class="fas fa-file-export me-1"></span>Exportar
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="alert alert-soft-info fs--1 mb-4">
        Cada sección suma solo las empresas en las que tienes permiso para ese módulo. Los montos se separan por moneda: no se suman monedas distintas.
        El saldo por cobrar y la cartera son al día de hoy; lo demás corresponde al año elegido.
    </div>

    {{-- ── Ventas y facturación ─────────────────────────────────────────── --}}
    @if ($datos['ventas'])
        @php $t = $datos['ventas']; @endphp
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-body-emphasis border-bottom border-200 py-3">
                <h5 class="mb-0 text-1000"><span class="fas fa-file-invoice-dollar text-primary me-2"></span>Ventas y facturación</h5>
                <p class="mb-0 fs--2 text-600">Facturas emitidas en {{ $anio }} (sin borradores ni anuladas) menos notas de crédito.</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead class="bg-100">
                            <tr>
                                <th class="ps-3">EMPRESA</th>
                                <th>MONEDA</th>
                                <th class="text-end">FACTURADO</th>
                                <th class="text-end">NOTAS DE CRÉDITO</th>
                                <th class="text-end">VENTA NETA</th>
                                <th class="text-end pe-3">SALDO POR COBRAR (HOY)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($t['filas'] as $f)
                                <tr>
                                    <td class="ps-3 fw-semi-bold text-1000">{{ $empresa($f['id_empresa']) }}</td>
                                    <td>{{ $f['moneda'] }}</td>
                                    <td class="text-end">{{ $dinero($f['facturado']) }}</td>
                                    <td class="text-end {{ $f['notas_credito'] > 0 ? 'text-danger' : '' }}">{{ $f['notas_credito'] > 0 ? '−'.$dinero($f['notas_credito']) : '0.00' }}</td>
                                    <td class="text-end fw-bold">{{ $dinero($f['neto']) }}</td>
                                    <td class="text-end pe-3">{{ $dinero($f['saldo']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-600 py-4">Sin facturación en {{ $anio }}.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($t['filas'])
                            <tfoot class="bg-100 fw-bold">
                                @foreach ($t['totales'] as $tot)
                                    <tr>
                                        <td class="ps-3">Total</td>
                                        <td>{{ $tot['moneda'] }}</td>
                                        <td class="text-end">{{ $dinero($tot['facturado']) }}</td>
                                        <td class="text-end {{ $tot['notas_credito'] > 0 ? 'text-danger' : '' }}">{{ $tot['notas_credito'] > 0 ? '−'.$dinero($tot['notas_credito']) : '0.00' }}</td>
                                        <td class="text-end">{{ $dinero($tot['neto']) }}</td>
                                        <td class="text-end pe-3">{{ $dinero($tot['saldo']) }}</td>
                                    </tr>
                                @endforeach
                            </tfoot>
                        @endif
                    </table>
                </div>
                @include('consolidado.meses', ['tabla' => $t, 'titulo' => 'Venta neta por mes'])
            </div>
        </div>
    @endif

    {{-- ── Compras ──────────────────────────────────────────────────────── --}}
    @if ($datos['compras'])
        @php $t = $datos['compras']; @endphp
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-body-emphasis border-bottom border-200 py-3">
                <h5 class="mb-0 text-1000"><span class="fas fa-shopping-cart text-primary me-2"></span>Compras</h5>
                <p class="mb-0 fs--2 text-600">Órdenes de compra aprobadas en {{ $anio }} (enviadas, parciales y recibidas).</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead class="bg-100">
                            <tr>
                                <th class="ps-3">EMPRESA</th>
                                <th>MONEDA</th>
                                <th class="text-end">ÓRDENES</th>
                                <th class="text-end">TOTAL APROBADO</th>
                                <th class="text-end pe-3">RECIBIDO COMPLETO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($t['filas'] as $f)
                                <tr>
                                    <td class="ps-3 fw-semi-bold text-1000">{{ $empresa($f['id_empresa']) }}</td>
                                    <td>{{ $f['moneda'] }}</td>
                                    <td class="text-end">{{ (int) $f['ordenes'] }}</td>
                                    <td class="text-end fw-bold">{{ $dinero($f['total']) }}</td>
                                    <td class="text-end pe-3">{{ $dinero($f['recibido']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-600 py-4">Sin órdenes aprobadas en {{ $anio }}.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($t['filas'])
                            <tfoot class="bg-100 fw-bold">
                                @foreach ($t['totales'] as $tot)
                                    <tr>
                                        <td class="ps-3">Total</td>
                                        <td>{{ $tot['moneda'] }}</td>
                                        <td class="text-end">{{ (int) $tot['ordenes'] }}</td>
                                        <td class="text-end">{{ $dinero($tot['total']) }}</td>
                                        <td class="text-end pe-3">{{ $dinero($tot['recibido']) }}</td>
                                    </tr>
                                @endforeach
                            </tfoot>
                        @endif
                    </table>
                </div>
                @include('consolidado.meses', ['tabla' => $t, 'titulo' => 'Compras aprobadas por mes'])
            </div>
        </div>
    @endif

    {{-- ── Cobros y cartera ─────────────────────────────────────────────── --}}
    @if ($datos['cobros'])
        @php $cobros = $datos['cobros']['cobros']; $cartera = $datos['cobros']['cartera']; @endphp
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-body-emphasis border-bottom border-200 py-3">
                <h5 class="mb-0 text-1000"><span class="fas fa-hand-holding-usd text-primary me-2"></span>Cobros y cartera</h5>
                <p class="mb-0 fs--2 text-600">Pagos aplicados en {{ $anio }} y antigüedad del saldo pendiente hoy (días de atraso desde el vencimiento).</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead class="bg-100">
                            <tr>
                                <th class="ps-3">EMPRESA</th>
                                <th>MONEDA</th>
                                @foreach (R::TRAMOS as $etiqueta)
                                    <th class="text-end">{{ mb_strtoupper($etiqueta) }}</th>
                                @endforeach
                                <th class="text-end pe-3">TOTAL CARTERA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cartera['filas'] as $f)
                                <tr>
                                    <td class="ps-3 fw-semi-bold text-1000">{{ $empresa($f['id_empresa']) }}</td>
                                    <td>{{ $f['moneda'] }}</td>
                                    @foreach (array_keys(R::TRAMOS) as $tramo)
                                        <td class="text-end {{ $tramo !== 'al_dia' && $f[$tramo] > 0 ? 'text-danger' : '' }}">{{ $dinero($f[$tramo]) }}</td>
                                    @endforeach
                                    <td class="text-end fw-bold pe-3">{{ $dinero($f['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ count(R::TRAMOS) + 3 }}" class="text-center text-600 py-4">No hay saldo pendiente de cobro.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($cartera['filas'])
                            <tfoot class="bg-100 fw-bold">
                                @foreach ($cartera['totales'] as $tot)
                                    <tr>
                                        <td class="ps-3">Total</td>
                                        <td>{{ $tot['moneda'] }}</td>
                                        @foreach (array_keys(R::TRAMOS) as $tramo)
                                            <td class="text-end">{{ $dinero($tot[$tramo]) }}</td>
                                        @endforeach
                                        <td class="text-end pe-3">{{ $dinero($tot['total']) }}</td>
                                    </tr>
                                @endforeach
                            </tfoot>
                        @endif
                    </table>
                </div>
                @include('consolidado.meses', ['tabla' => $cobros, 'titulo' => 'Cobrado por mes', 'total' => 'cobrado'])
            </div>
        </div>
    @endif

    {{-- ── Presupuesto ──────────────────────────────────────────────────── --}}
    @if ($datos['presupuesto'])
        @php $t = $datos['presupuesto']['empresas']; $centros = $datos['presupuesto']['centros']; @endphp
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-body-emphasis border-bottom border-200 py-3">
                <h5 class="mb-0 text-1000"><span class="fas fa-chart-pie text-primary me-2"></span>Presupuesto</h5>
                <p class="mb-0 fs--2 text-600">Presupuestos {{ $anio }} aprobados o cerrados: presupuestado contra ejecutado (base sin IVA).</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead class="bg-100">
                            <tr>
                                <th class="ps-3">EMPRESA / CENTRO DE COSTO</th>
                                <th>MONEDA</th>
                                <th class="text-end">PRESUPUESTADO</th>
                                <th class="text-end">EJECUTADO</th>
                                <th class="text-end">DISPONIBLE</th>
                                <th class="text-end pe-3" style="width: 12rem;">EJECUCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($t['filas'] as $f)
                                @php $pct = $f['presupuestado'] > 0 ? round($f['ejecutado'] / $f['presupuestado'] * 100, 1) : 0; @endphp
                                <tr class="bg-soft-primary">
                                    <td class="ps-3 fw-bold text-1000">{{ $empresa($f['id_empresa']) }}</td>
                                    <td>{{ $f['moneda'] }}</td>
                                    <td class="text-end fw-bold">{{ $dinero($f['presupuestado']) }}</td>
                                    <td class="text-end fw-bold">{{ $dinero($f['ejecutado']) }}</td>
                                    <td class="text-end fw-bold {{ $f['disponible'] < 0 ? 'text-danger' : '' }}">{{ $dinero($f['disponible']) }}</td>
                                    <td class="text-end pe-3">@include('consolidado.barra', ['pct' => $pct])</td>
                                </tr>
                                @foreach (collect($centros)->where('id_empresa', $f['id_empresa'])->where('moneda', $f['moneda']) as $c)
                                    @php $pctC = $c['presupuestado'] > 0 ? round($c['ejecutado'] / $c['presupuestado'] * 100, 1) : 0; @endphp
                                    <tr>
                                        <td class="ps-5 text-700">{{ $c['nombre'] }}</td>
                                        <td class="text-600">{{ $c['moneda'] }}</td>
                                        <td class="text-end">{{ $dinero($c['presupuestado']) }}</td>
                                        <td class="text-end">{{ $dinero($c['ejecutado']) }}</td>
                                        <td class="text-end {{ $c['disponible'] < 0 ? 'text-danger' : '' }}">{{ $dinero($c['disponible']) }}</td>
                                        <td class="text-end pe-3">@include('consolidado.barra', ['pct' => $pctC])</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="6" class="text-center text-600 py-4">No hay presupuestos aprobados para {{ $anio }}.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($t['filas'])
                            <tfoot class="bg-100 fw-bold">
                                @foreach ($t['totales'] as $tot)
                                    @php $pct = $tot['presupuestado'] > 0 ? round($tot['ejecutado'] / $tot['presupuestado'] * 100, 1) : 0; @endphp
                                    <tr>
                                        <td class="ps-3">Total</td>
                                        <td>{{ $tot['moneda'] }}</td>
                                        <td class="text-end">{{ $dinero($tot['presupuestado']) }}</td>
                                        <td class="text-end">{{ $dinero($tot['ejecutado']) }}</td>
                                        <td class="text-end {{ $tot['disponible'] < 0 ? 'text-danger' : '' }}">{{ $dinero($tot['disponible']) }}</td>
                                        <td class="text-end pe-3">@include('consolidado.barra', ['pct' => $pct])</td>
                                    </tr>
                                @endforeach
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

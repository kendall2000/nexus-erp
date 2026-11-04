@extends('layouts.app', ['titulo' => 'Resumen por sucursal'])

@php
    use App\Http\Controllers\ReporteSucursalesController as R;
    $dinero = fn ($n) => number_format((float) $n, 2);
    $sedes = $datos['sedes'];
    $hayVentas = $datos['ventas'] !== null;
    $hayCompras = $datos['compras'] !== null;
@endphp

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Resumen por sucursal</li>
        </ol>
    </nav>

    <div class="row align-items-center justify-content-between g-3 mb-4">
        <div class="col-12 col-md-auto">
            <h2 class="mb-1 text-1100">Resumen por sucursal</h2>
            <h5 class="text-700 fw-semi-bold mb-0">{{ $datos['empresa'] }} · del {{ \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') }}</h5>
        </div>
        <div class="col-12 col-md-auto">
            <form method="GET" action="{{ route('por-sucursal.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
                <label class="text-800 fw-semi-bold fs--1" for="desde">Del</label>
                <input class="form-control form-control-sm" style="width: 150px" type="date" id="desde" name="desde" value="{{ $desde }}" />
                <label class="text-800 fw-semi-bold fs--1" for="hasta">al</label>
                <input class="form-control form-control-sm" style="width: 150px" type="date" id="hasta" name="hasta" value="{{ $hasta }}" />
                <button class="btn btn-primary btn-sm" type="submit">Ver</button>
                @if ($puedeExportar)
                    <a class="btn btn-phoenix-secondary btn-sm text-nowrap" href="{{ route('por-sucursal.exportar', ['desde' => $desde, 'hasta' => $hasta]) }}">
                        <span class="fas fa-file-export me-1"></span>Exportar
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="alert alert-soft-info fs--1 mb-4">
        Cada agencia tiene su propia fila (no se suma en la de su sucursal). Los montos se separan por moneda.
        «Sin sucursal» son documentos registrados antes de que se pidiera la sede. Ves solo las secciones de los módulos en los que tienes permiso.
    </div>

    @if (! $sedes)
        <div class="card border border-200 shadow-none"><div class="card-body text-center text-700 py-5">No hay sucursales ni documentos en este periodo.</div></div>
    @endif

    {{-- ── Ventas y compras ─────────────────────────────────────────────── --}}
    @if (($hayVentas || $hayCompras) && $sedes)
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-body-emphasis border-bottom border-200 py-3">
                <h5 class="mb-0 text-1000"><span class="fas fa-file-invoice-dollar text-primary me-2"></span>Ventas y compras</h5>
                <p class="mb-0 fs--2 text-600">
                    @if ($hayVentas)Facturas emitidas en el periodo (sin borradores ni anuladas) menos notas de crédito; el saldo por cobrar es a hoy.@endif
                    @if ($hayCompras)Compras: órdenes aprobadas emitidas en el periodo.@endif
                </p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead class="bg-100">
                            <tr>
                                <th class="ps-3">SUCURSAL</th>
                                <th>MONEDA</th>
                                @if ($hayVentas)
                                    <th class="text-end">DOCUMENTOS</th>
                                    <th class="text-end">FACTURADO</th>
                                    <th class="text-end">NOTAS DE CRÉDITO</th>
                                    <th class="text-end">VENTA NETA</th>
                                    <th class="text-end">SALDO POR COBRAR</th>
                                @endif
                                @if ($hayCompras)
                                    <th class="text-end">ÓRDENES</th>
                                    <th class="text-end pe-3">COMPRAS</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sedes as $clave => $sede)
                                @php
                                    $ventas = $datos['ventas'][$clave] ?? [];
                                    $compras = $datos['compras'][$clave] ?? [];
                                    $monedas = collect(array_keys($ventas))->merge(array_keys($compras))->unique()->sort()->values();
                                @endphp
                                @forelse ($monedas as $i => $moneda)
                                    <tr>
                                        <td class="ps-3 {{ $sede['agencia'] ? 'ps-5' : '' }}">
                                            @if ($i === 0)@include('reporte-sucursales.sede', ['sede' => $sede])@endif
                                        </td>
                                        <td class="text-800">{{ $moneda }}</td>
                                        @if ($hayVentas)
                                            @php $v = $ventas[$moneda] ?? null; @endphp
                                            <td class="text-end">{{ $v['documentos'] ?? '—' }}</td>
                                            <td class="text-end">{{ $v ? $dinero($v['facturado']) : '—' }}</td>
                                            <td class="text-end text-danger">{{ $v && $v['notas_credito'] ? '−'.$dinero($v['notas_credito']) : '—' }}</td>
                                            <td class="text-end fw-bold text-1000">{{ $v ? $dinero($v['neto']) : '—' }}</td>
                                            <td class="text-end">{{ $v ? $dinero($v['saldo']) : '—' }}</td>
                                        @endif
                                        @if ($hayCompras)
                                            @php $c = $compras[$moneda] ?? null; @endphp
                                            <td class="text-end">{{ $c['ordenes'] ?? '—' }}</td>
                                            <td class="text-end pe-3 fw-bold text-1000">{{ $c ? $dinero($c['total']) : '—' }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="ps-3 {{ $sede['agencia'] ? 'ps-5' : '' }}">@include('reporte-sucursales.sede', ['sede' => $sede])</td>
                                        <td class="text-500" colspan="{{ 1 + ($hayVentas ? 5 : 0) + ($hayCompras ? 2 : 0) }}">Sin movimientos en el periodo.</td>
                                    </tr>
                                @endforelse
                            @endforeach
                        </tbody>
                        <tfoot class="bg-100 fw-bold">
                            @php
                                $tv = R::totales($datos['ventas'], ['documentos', 'facturado', 'notas_credito', 'neto', 'saldo']);
                                $tc = R::totales($datos['compras'], ['ordenes', 'total']);
                            @endphp
                            @foreach ($tv->keys()->merge($tc->keys())->unique()->sort() as $moneda)
                                <tr>
                                    <td class="ps-3">Total de la empresa</td>
                                    <td>{{ $moneda }}</td>
                                    @if ($hayVentas)
                                        @php $v = $tv[$moneda] ?? null; @endphp
                                        <td class="text-end">{{ $v['documentos'] ?? '—' }}</td>
                                        <td class="text-end">{{ $v ? $dinero($v['facturado']) : '—' }}</td>
                                        <td class="text-end">{{ $v && $v['notas_credito'] ? '−'.$dinero($v['notas_credito']) : '—' }}</td>
                                        <td class="text-end">{{ $v ? $dinero($v['neto']) : '—' }}</td>
                                        <td class="text-end">{{ $v ? $dinero($v['saldo']) : '—' }}</td>
                                    @endif
                                    @if ($hayCompras)
                                        @php $c = $tc[$moneda] ?? null; @endphp
                                        <td class="text-end">{{ $c['ordenes'] ?? '—' }}</td>
                                        <td class="text-end pe-3">{{ $c ? $dinero($c['total']) : '—' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Tickets y personal ───────────────────────────────────────────── --}}
    @if (($datos['tickets'] !== null || $datos['personal'] !== null) && $sedes)
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-body-emphasis border-bottom border-200 py-3">
                <h5 class="mb-0 text-1000"><span class="fas fa-headset text-primary me-2"></span>Tickets y personal</h5>
                <p class="mb-0 fs--2 text-600">Abiertos, vencidos y empleados son a hoy; nuevos y resueltos, del periodo.</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead class="bg-100">
                            <tr>
                                <th class="ps-3">SUCURSAL</th>
                                @if ($datos['tickets'] !== null)
                                    <th class="text-end">TICKETS ABIERTOS</th>
                                    <th class="text-end">VENCIDOS</th>
                                    <th class="text-end">NUEVOS</th>
                                    <th class="text-end">RESUELTOS</th>
                                @endif
                                @if ($datos['personal'] !== null)
                                    <th class="text-end pe-3">EMPLEADOS</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sedes as $clave => $sede)
                                @php $t = $datos['tickets'][$clave] ?? null; @endphp
                                <tr>
                                    <td class="ps-3 {{ $sede['agencia'] ? 'ps-5' : '' }}">@include('reporte-sucursales.sede', ['sede' => $sede])</td>
                                    @if ($datos['tickets'] !== null)
                                        <td class="text-end fw-bold text-1000">{{ $t['abiertos'] ?? 0 }}</td>
                                        <td class="text-end {{ ($t['vencidos'] ?? 0) > 0 ? 'text-danger fw-bold' : '' }}">{{ $t['vencidos'] ?? 0 }}</td>
                                        <td class="text-end">{{ $t['nuevos'] ?? 0 }}</td>
                                        <td class="text-end">{{ $t['resueltos'] ?? 0 }}</td>
                                    @endif
                                    @if ($datos['personal'] !== null)
                                        <td class="text-end pe-3 fw-bold text-1000">{{ $datos['personal'][$clave] ?? 0 }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection

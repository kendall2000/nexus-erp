@extends('layouts.app', ['titulo' => 'Presupuesto '.$p->anio])

@section('contenido')
    @php
        $yo = auth()->user();
        [$nombreEstado, $colorEstado] = $estados[$p->estado] ?? [$p->estado, 'secondary'];
        [$nombreEje, $colorEje] = $ejecucion[$p->estado_ejecucion];
        $dinero = fn ($n) => number_format((float) $n, 2);
        $mesActual = (int) now()->year === (int) $p->anio ? (int) now()->month : null;
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('presupuesto.index', ['anio' => $p->anio]) }}">Presupuesto {{ $p->anio }}</a></li>
            <li class="breadcrumb-item active">{{ $p->cuentaContable?->codigo }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $p->cuentaContable?->etiqueta }} <span class="badge badge-phoenix badge-phoenix-{{ $colorEstado }} fs--1 align-middle">{{ $nombreEstado }}</span></h2>
            <p class="text-700 mb-0">{{ $p->centroCosto?->etiqueta }} · {{ $p->anio }} · {{ $p->moneda }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($p->estado === 'BORRADOR' && $yo->puede('presupuesto.editar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('presupuesto.edit', $p->id_presupuesto) }}"><span class="fas fa-pen me-2"></span>Editar</a>
            @endif
            @if ($p->estado === 'BORRADOR' && $yo->puede('presupuesto.aprobar'))
                <form method="POST" action="{{ route('presupuesto.aprobar', $p->id_presupuesto) }}" onsubmit="return confirm('Al aprobarlo ya no se podrá editar y las órdenes de compra empezarán a ejecutarlo. ¿Aprobar?')">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-success" type="submit"><span class="fas fa-check me-2"></span>Aprobar</button>
                </form>
            @endif
            @if ($p->estado === 'APROBADO' && $yo->puede('presupuesto.cerrar'))
                <form method="POST" action="{{ route('presupuesto.cerrar', $p->id_presupuesto) }}" onsubmit="return confirm('Un presupuesto cerrado ya no recibe ejecución de las órdenes. ¿Cerrar?')">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-phoenix-secondary" type="submit"><span class="fas fa-lock me-2"></span>Cerrar</button>
                </form>
            @endif
            @if ($p->estado === 'CERRADO' && $yo->puede('presupuesto.reabrir'))
                <form method="POST" action="{{ route('presupuesto.reabrir', $p->id_presupuesto) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-phoenix-warning" type="submit"><span class="fas fa-lock-open me-2"></span>Reabrir</button>
                </form>
            @endif
            @if ($p->estado === 'BORRADOR' && $yo->puede('presupuesto.editar'))
                <form method="POST" action="{{ route('presupuesto.destroy', $p->id_presupuesto) }}" onsubmit="return confirm('¿Eliminar este presupuesto en borrador?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger" type="submit" title="Eliminar"><span class="fas fa-trash"></span></button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Presupuestado</p><h3 class="mb-0">{{ $dinero($p->total_presupuestado) }}</h3></div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Ejecutado</p><h3 class="mb-0">{{ $dinero($p->total_ejecutado) }}</h3></div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
            <p class="fs--1 text-700 mb-1">Disponible</p><h3 class="mb-1 {{ $p->saldo_disponible < 0 ? 'text-danger' : '' }}">{{ $dinero($p->saldo_disponible) }}</h3>
            <div class="progress" style="height: 6px"><div class="progress-bar bg-{{ $colorEje }}" style="width: {{ min(100, $p->porcentaje_ejecucion) }}%"></div></div>
            <span class="fs--2 text-700">{{ $p->porcentaje_ejecucion }} % · {{ $nombreEje }}</span>
        </div></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Por mes</h5>
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th>Mes</th><th class="text-end">Presupuestado</th><th class="text-end">Ejecutado</th><th class="text-end">Disponible</th><th class="text-end">%</th></tr></thead>
                            <tbody>
                            @foreach (\App\Models\Finanzas\PresupuestoAnual::MESES as $num => $mes)
                                @php $pre = (float) $p->{'pre_'.$mes}; $eje = (float) $p->{'eje_'.$mes}; @endphp
                                <tr class="{{ $mesActual === $num ? 'table-active fw-semi-bold' : '' }}">
                                    <td>{{ ucfirst($mes) }}</td>
                                    <td class="text-end">{{ $dinero($pre) }}</td>
                                    <td class="text-end">{{ $dinero($eje) }}</td>
                                    <td class="text-end {{ $pre - $eje < 0 ? 'text-danger' : '' }}">{{ $dinero($pre - $eje) }}</td>
                                    <td class="text-end">{{ $pre > 0 ? round($eje / $pre * 100, 1).' %' : '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Detalles</h5>
                    <dl class="row mb-0">
                        <dt class="col-5 text-700">Centro</dt><dd class="col-7">{{ $p->centroCosto?->etiqueta }}</dd>
                        <dt class="col-5 text-700">Cuenta</dt><dd class="col-7">{{ $p->cuentaContable?->etiqueta }}<br><span class="text-600">{{ strtolower($p->cuentaContable?->tipo) }}</span></dd>
                        <dt class="col-5 text-700">Aprobado</dt><dd class="col-7">{{ $p->aprobadoPor?->nombre_completo ?? '—' }}@if ($p->fecha_aprobacion)<br><span class="text-600">{{ $p->fecha_aprobacion->format('d/m/Y H:i') }}</span>@endif</dd>
                        @if ($p->estado === 'CERRADO')
                            <dt class="col-5 text-700">Cerrado</dt><dd class="col-7">{{ $p->cerradoPor?->nombre_completo ?? '—' }}@if ($p->fecha_cierre)<br><span class="text-600">{{ $p->fecha_cierre->format('d/m/Y H:i') }}</span>@endif</dd>
                        @endif
                    </dl>
                    @if ($p->estado === 'BORRADOR')
                        <p class="text-700 mt-3 mb-0"><span class="fas fa-info-circle me-1"></span>En borrador no recibe ejecución: las órdenes aprobadas antes de aprobarlo no se le suman.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-1">Órdenes de compra de esta partida</h5>
            <p class="fs--1 text-700">Líneas de órdenes aprobadas de {{ $p->anio }} con este centro y cuenta (propios de la línea o heredados del producto). Montos sin IVA.</p>
            @if ($ordenes->isEmpty())
                <p class="text-700 fs--1 mb-0">Ninguna orden de compra ha usado esta partida.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead><tr><th>Orden</th><th>Emisión</th><th>Producto</th><th class="text-end">Monto</th></tr></thead>
                        <tbody>
                        @foreach ($ordenes as $d)
                            <tr>
                                <td>
                                    @if ($yo->puede('ordenes_compra.ver'))<a href="{{ route('ordenes-compra.show', $d->id_oc) }}">{{ $d->ordenCompra->numero_oc }}</a>@else{{ $d->ordenCompra->numero_oc }}@endif
                                </td>
                                <td>{{ $d->ordenCompra->fecha_emision?->format('d/m/Y') }}</td>
                                <td>{{ $d->producto?->nombre }}</td>
                                <td class="text-end">{{ $d->ordenCompra->moneda }} {{ $dinero($d->monto_neto) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

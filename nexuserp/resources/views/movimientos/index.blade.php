@extends('layouts.app', ['titulo' => 'Kardex'])

@section('contenido')
    @php
        $yo = auth()->user();
        $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 4), '0'), '.');
        $saldo = $saldoInicial;
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Kardex</h2>
            <p class="text-700 fw-semi-bold mb-0">Movimientos de inventario. No se editan ni se borran: un error se corrige con otro movimiento.</p>
        </div>
        @if ($yo->puede('movimientos.crear'))
            <a class="btn btn-primary" href="{{ route('movimientos.create', array_filter(['producto' => $filtros['producto'] ?? null, 'bodega' => $filtros['bodega'] ?? null])) }}"><span class="fas fa-plus me-2"></span>Registrar movimiento</a>
        @endif
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <select class="form-select form-select-sm" name="producto">
                        <option value="">Todos los productos</option>
                        @foreach ($productos as $p)<option value="{{ $p->id_producto }}" @selected((int) ($filtros['producto'] ?? 0) === $p->id_producto)>{{ $p->codigo }} — {{ $p->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="bodega">
                        <option value="">Todas las bodegas</option>
                        @foreach ($bodegas as $b)<option value="{{ $b->id_bodega }}" @selected((int) ($filtros['bodega'] ?? 0) === $b->id_bodega)>{{ $b->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="tipo">
                        <option value="">Todo tipo</option>
                        @foreach ($tipos as $codigo => $nombre)<option value="{{ $codigo }}" @selected(($filtros['tipo'] ?? '') === $codigo)>{{ $nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Desde" /></div>
                <div class="col-md-1"><input class="form-control form-control-sm" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Hasta" /></div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-phoenix-secondary btn-sm w-100" type="submit">Filtrar</button>
                    @if (array_filter($filtros))<a class="btn btn-link btn-sm px-1" href="{{ route('movimientos.index') }}" title="Limpiar"><span class="fas fa-times"></span></a>@endif
                </div>
            </form>
            @unless ($esKardex)
                <p class="fs--1 text-700 mt-2 mb-0"><span class="fas fa-info-circle me-1"></span>Elige un producto y una bodega para ver el kardex con su saldo acumulado.</p>
            @endunless
        </div>
    </div>

    @if ($esKardex)
        <div class="row g-3 mb-4">
            <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
                <p class="fs--1 text-700 mb-1">Existencia en {{ $bodega->nombre }}</p>
                <h3 class="mb-0">{{ $cantidad($existencia?->cantidad_actual ?? 0) }} <span class="fs--1 text-600">{{ $producto->unidad_medida }}</span></h3>
            </div></div></div>
            <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
                <p class="fs--1 text-700 mb-1">Costo promedio</p><h3 class="mb-0">{{ number_format((float) ($existencia?->costo_promedio ?? 0), 2) }}</h3>
            </div></div></div>
            <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3">
                <p class="fs--1 text-700 mb-1">Valor</p><h3 class="mb-0">{{ number_format((float) ($existencia?->valor_total ?? 0), 2) }}</h3>
            </div></div></div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-3 align-middle">
                    <thead>
                    <tr>
                        <th>Fecha</th><th>Movimiento</th>
                        @unless ($esKardex)<th>Producto</th><th>Bodega</th>@endunless
                        <th class="text-end">Entrada</th><th class="text-end">Salida</th>
                        @if ($esKardex)<th class="text-end">Saldo</th>@endif
                        <th class="text-end">Costo unit.</th><th>Detalle</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if ($esKardex)
                        <tr class="text-600"><td colspan="4" class="text-end fst-italic">Saldo anterior</td><td class="text-end fw-semi-bold">{{ $cantidad($saldo) }}</td><td colspan="2"></td></tr>
                    @endif
                    @forelse ($movimientos as $m)
                        @php
                            $signo = \App\Support\Kardex::conSigno($m);
                            if ($esKardex) { $saldo += $signo; }
                        @endphp
                        <tr>
                            <td class="text-nowrap">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-nowrap">
                                <span class="badge badge-phoenix badge-phoenix-{{ $signo > 0 ? 'success' : ($signo < 0 ? 'danger' : 'secondary') }}">{{ \App\Support\Kardex::etiqueta($m) }}</span>
                            </td>
                            @unless ($esKardex)
                                <td><a href="{{ route('movimientos.index', ['producto' => $m->id_producto, 'bodega' => $m->id_bodega]) }}">{{ $m->producto?->nombre }}</a></td>
                                <td>{{ $m->bodega?->nombre }}</td>
                            @endunless
                            <td class="text-end text-success">{{ $signo > 0 ? $cantidad($m->cantidad) : '' }}</td>
                            <td class="text-end text-danger">{{ $signo < 0 ? $cantidad($m->cantidad) : '' }}</td>
                            @if ($esKardex)<td class="text-end fw-semi-bold">{{ $cantidad($saldo) }}</td>@endif
                            <td class="text-end">{{ $m->costo_unitario !== null ? number_format((float) $m->costo_unitario, 2) : '—' }}</td>
                            <td class="fs--2 text-700">
                                @if ($m->referencia_tipo === 'COMPRA' && $m->referencia_id && $yo->puede('recepciones.ver'))
                                    <a href="{{ route('recepciones.show', $m->referencia_id) }}">{{ $m->observaciones ?: 'Recepción' }}</a>
                                @else
                                    {{ $m->observaciones }}
                                @endif
                                @if ($m->numero_lote)<span class="d-block">Lote {{ $m->numero_lote }}{{ $m->fecha_vencimiento ? ' · vence '.$m->fecha_vencimiento->format('d/m/Y') : '' }}</span>@endif
                                <span class="d-block text-500">{{ $m->creadoPor?->nombre_completo }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-700 py-4">{{ array_filter($filtros) ? 'Ningún movimiento coincide con los filtros.' : 'Todavía no hay movimientos.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movimientos->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection

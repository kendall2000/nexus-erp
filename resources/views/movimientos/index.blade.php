@extends('layouts.app', ['titulo' => 'Kardex de Inventario'])

@section('contenido')
    @php
        $yo = auth()->user();
        $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 4), '0'), '.');
        $saldo = $saldoInicial;
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item">Inventario y Compras</li>
            <li class="breadcrumb-item active">Kardex de Movimientos</li>
        </ol>
    </nav>

    <div class="mb-9">
        {{-- Encabezado idéntico a Phoenix --}}
        <div class="row align-items-center justify-content-between g-3 mb-4">
            <div class="col-12 col-md-auto">
                <h2 class="mb-1 text-1100">Kardex & Movimientos de Inventario</h2>
                <h5 class="text-700 fw-semi-bold mb-0">Trazabilidad inmutable de entradas, salidas y ajustes de existencias</h5>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                @if ($yo->puede('movimientos.crear'))
                    <a class="btn btn-primary" href="{{ route('movimientos.create', array_filter(['producto' => $filtros['producto'] ?? null, 'bodega' => $filtros['bodega'] ?? null])) }}">
                        <span class="fas fa-plus me-2"></span>Registrar movimiento
                    </a>
                @endif
            </div>
        </div>

        {{-- Métricas cuando se consulta un producto y bodega específicos --}}
        @if ($esKardex)
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="card h-100 border border-200">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Existencia en {{ $bodega->nombre }}</h6>
                                    <h3 class="text-primary mb-0 fw-bolder">{{ $cantidad($existencia?->cantidad_actual ?? 0) }} {{ $producto->unidad_medida }}</h3>
                                    <p class="fs--2 text-600 mb-0 mt-1">Stock físico disponible</p>
                                </div>
                                <div class="d-flex align-items-center icon-wrapper-sm shadow-primary-100" style="transform: rotate(-7.45deg);">
                                    <span class="fas fa-boxes text-primary fs-1 z-index-1 ms-2"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="card h-100 border border-200">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Costo Promedio Ponderado</h6>
                                    <h3 class="text-info mb-0 fw-bolder">Q {{ number_format((float) ($existencia?->costo_promedio ?? 0), 2) }}</h3>
                                    <p class="fs--2 text-600 mb-0 mt-1">Por {{ $producto->unidad_medida }}</p>
                                </div>
                                <div class="d-flex align-items-center icon-wrapper-sm shadow-info-100" style="transform: rotate(-7.45deg);">
                                    <span class="fas fa-calculator text-info fs-1 z-index-1 ms-2"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="card h-100 border border-200">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-700 fs--1 mb-1 fw-semi-bold">Valorización Total</h6>
                                    <h3 class="text-success mb-0 fw-bolder">Q {{ number_format((float) ($existencia?->valor_total ?? 0), 2) }}</h3>
                                    <p class="fs--2 text-600 mb-0 mt-1">Costo total en almacén</p>
                                </div>
                                <div class="d-flex align-items-center icon-wrapper-sm shadow-success-100" style="transform: rotate(-7.45deg);">
                                    <span class="fas fa-coins text-success fs-1 z-index-1 ms-2"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div id="kardexTable">
            {{-- Toolbar idéntico a Phoenix --}}
            <div class="mb-4">
                <form method="GET" action="{{ route('movimientos.index') }}" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="producto" onchange="this.form.submit()">
                            <option value="">Todos los productos</option>
                            @foreach ($productos as $p)
                                <option value="{{ $p->id_producto }}" @selected((int) ($filtros['producto'] ?? 0) === $p->id_producto)>{{ $p->codigo }} — {{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="bodega" onchange="this.form.submit()">
                            <option value="">Todas las bodegas</option>
                            @foreach ($bodegas as $b)
                                <option value="{{ $b->id_bodega }}" @selected((int) ($filtros['bodega'] ?? 0) === $b->id_bodega)>{{ $b->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select btn-phoenix-secondary px-4 flex-shrink-0" name="tipo" onchange="this.form.submit()">
                            <option value="">Todo tipo de movimiento</option>
                            @foreach ($tipos as $codigo => $nombre)
                                <option value="{{ $codigo }}" @selected(($filtros['tipo'] ?? '') === $codigo)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <input class="form-control btn-phoenix-secondary px-3" style="width: 140px;" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" title="Desde" onchange="this.form.submit()" />
                    </div>
                    <div class="col-auto">
                        <input class="form-control btn-phoenix-secondary px-3" style="width: 140px;" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" title="Hasta" onchange="this.form.submit()" />
                    </div>
                    @if (array_filter($filtros))
                        <div class="col-auto">
                            <a class="btn btn-phoenix-danger px-3 flex-shrink-0" href="{{ route('movimientos.index') }}" title="Limpiar filtros">
                                <span class="fas fa-times me-1"></span>Limpiar
                            </a>
                        </div>
                    @endif
                </form>
                @unless ($esKardex)
                    <p class="fs--1 text-700 mt-2 mb-0"><span class="fas fa-info-circle me-1"></span>Elige un producto y una bodega para ver el kardex con su saldo acumulado.</p>
                @endunless
            </div>

            {{-- Tabla con estructura visual exacta de Phoenix --}}
            <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-white border-top border-bottom border-200 position-relative top-1">
                <div class="table-responsive scrollbar mx-n1 px-1">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="sort white-space-nowrap align-middle ps-3" scope="col" style="width:140px;">FECHA Y HORA</th>
                                <th class="sort align-middle ps-4" scope="col" style="width:150px;">TIPO MOVIMIENTO</th>
                                @unless ($esKardex)
                                    <th class="sort align-middle ps-4" scope="col" style="min-width:220px;">PRODUCTO</th>
                                    <th class="sort align-middle ps-4" scope="col" style="width:160px;">BODEGA</th>
                                @endunless
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:110px;">ENTRADA</th>
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:110px;">SALIDA</th>
                                @if ($esKardex)
                                    <th class="sort align-middle text-end ps-4" scope="col" style="width:120px;">SALDO FÍSICO</th>
                                @endif
                                <th class="sort align-middle text-end ps-4" scope="col" style="width:130px;">COSTO UNIT.</th>
                                <th class="sort align-middle pe-4 ps-4" scope="col">OBSERVACIONES / LOTE</th>
                            </tr>
                        </thead>
                        <tbody class="list" id="kardex-table-body">
                            @if ($esKardex)
                                <tr class="bg-100">
                                    <td colspan="2" class="ps-3 py-2 fw-semi-bold text-700">Saldo anterior</td>
                                    <td colspan="2"></td>
                                    <td class="text-end fw-bold text-1000 ps-4 fs-0">{{ $cantidad($saldo) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            @endif
                            @forelse ($movimientos as $m)
                                @php
                                    $signo = \App\Support\Kardex::conSigno($m);
                                    if ($esKardex) { $saldo += $signo; }
                                @endphp
                                <tr class="hover-actions-trigger position-static">
                                    <td class="ps-3 py-3 text-700 text-nowrap">
                                        {{ $m->created_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="ps-4">
                                        <span class="badge badge-phoenix fs--2 badge-phoenix-{{ $signo > 0 ? 'success' : ($signo < 0 ? 'danger' : 'secondary') }}">
                                            {{ \App\Support\Kardex::etiqueta($m) }}
                                        </span>
                                    </td>
                                    @unless ($esKardex)
                                        <td class="ps-4">
                                            <a class="fw-bold text-primary" href="{{ route('movimientos.index', ['producto' => $m->id_producto, 'bodega' => $m->id_bodega]) }}">
                                                {{ $m->producto?->nombre }}
                                            </a>
                                            <span class="d-block fs--2 text-600"><code>{{ $m->producto?->codigo }}</code></span>
                                        </td>
                                        <td class="ps-4 text-800 fw-semi-bold">
                                            {{ $m->bodega?->nombre }}
                                        </td>
                                    @endunless
                                    <td class="text-end ps-4 fw-bold text-success">
                                        {{ $signo > 0 ? '+'.$cantidad($m->cantidad) : '' }}
                                    </td>
                                    <td class="text-end ps-4 fw-bold text-danger">
                                        {{ $signo < 0 ? '−'.$cantidad($m->cantidad) : '' }}
                                    </td>
                                    @if ($esKardex)
                                        <td class="text-end ps-4 fw-bolder text-1100 fs-0">
                                            {{ $cantidad($saldo) }}
                                        </td>
                                    @endif
                                    <td class="text-end ps-4 text-900">
                                        {{ $m->costo_unitario !== null ? 'Q '.number_format((float) $m->costo_unitario, 2) : '—' }}
                                    </td>
                                    <td class="pe-4 ps-4 fs--1">
                                        @if ($m->referencia_tipo === 'COMPRA' && $m->referencia_id && $yo->puede('recepciones.ver'))
                                            <a class="fw-semi-bold text-primary" href="{{ route('recepciones.show', $m->referencia_id) }}">{{ $m->observaciones ?: 'Recepción de OC' }}</a>
                                        @else
                                            <span class="text-800">{{ $m->observaciones ?: '—' }}</span>
                                        @endif
                                        @if ($m->numero_lote)
                                            <span class="d-block fs--2 text-600">Lote: <strong>{{ $m->numero_lote }}</strong>{{ $m->fecha_vencimiento ? ' · vence '.$m->fecha_vencimiento->format('d/m/Y') : '' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-700 py-5">
                                        <span class="fas fa-boxes fs-2 text-300 d-block mb-2"></span>
                                        {{ array_filter($filtros) ? 'Ningún movimiento coincide con los filtros aplicados.' : 'Aún no hay movimientos de kardex registrados.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer de paginación --}}
                <div class="row align-items-center justify-content-between py-2 pe-0 fs--1 border-top border-200">
                    <div class="col-auto d-flex">
                        <p class="mb-0 d-none d-sm-block me-3 fw-semi-bold text-900">
                            Mostrando {{ $movimientos->firstItem() ?? 0 }} a {{ $movimientos->lastItem() ?? 0 }}
                            <span class="text-600">de</span> {{ $movimientos->total() }} movimientos
                        </p>
                    </div>
                    <div class="col-auto d-flex">
                        {{ $movimientos->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app', ['titulo' => $producto->exists ? 'Editar producto' : 'Nuevo producto'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $producto->{$campo}); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
            <li class="breadcrumb-item active">{{ $producto->exists ? $producto->nombre : 'Nuevo' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $producto->exists ? 'Editar producto' : 'Nuevo producto' }}</h2>

    <form method="POST" action="{{ $producto->exists ? route('productos.update', $producto->id_producto) : route('productos.store') }}" class="mb-9">
        @csrf
        @if ($producto->exists)
            @method('PUT')
        @endif
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Datos generales</h4>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label" for="codigo">Código</label><input class="form-control text-uppercase" id="codigo" name="codigo" value="{{ $v('codigo') }}" required maxlength="50" /></div>
                            <div class="col-md-8"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" value="{{ $v('nombre') }}" required maxlength="200" /></div>
                            <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="2" maxlength="2000">{{ $v('descripcion') }}</textarea></div>
                            <div class="col-md-6">
                                <label class="form-label" for="id_categoria">Categoría</label>
                                <select class="form-select" id="id_categoria" name="id_categoria">
                                    <option value="">Sin categoría</option>
                                    @foreach ($categorias as $c)<option value="{{ $c->id_categoria }}" @selected((int) $v('id_categoria') === $c->id_categoria)>{{ $c->nombre }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="unidad_medida">Unidad de medida</label>
                                <select class="form-select" id="unidad_medida" name="unidad_medida" required>
                                    @foreach ($unidades as $codigo => $nombre)<option value="{{ $codigo }}" @selected($v('unidad_medida') === $codigo)>{{ $nombre }} ({{ $codigo }})</option>@endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Precios e inventario</h4>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label" for="precio_compra">Precio de compra</label><input class="form-control" id="precio_compra" name="precio_compra" type="number" step="0.0001" min="0" value="{{ $v('precio_compra') }}" /></div>
                            <div class="col-md-4"><label class="form-label" for="precio_venta">Precio de venta</label><input class="form-control" id="precio_venta" name="precio_venta" type="number" step="0.0001" min="0" value="{{ $v('precio_venta') }}" /></div>
                            <div class="col-md-4">
                                <label class="form-label" for="moneda">Moneda</label>
                                <select class="form-select" id="moneda" name="moneda" required>
                                    @foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }} — {{ $m->nombre }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label" for="stock_minimo">Stock mínimo</label><input class="form-control" id="stock_minimo" name="stock_minimo" type="number" step="0.01" min="0" value="{{ $v('stock_minimo') }}" required /><div class="form-text">Debajo de este nivel se marca «Reponer».</div></div>
                            <div class="col-md-4"><label class="form-label" for="stock_maximo">Stock máximo</label><input class="form-control" id="stock_maximo" name="stock_maximo" type="number" step="0.01" min="0" value="{{ $v('stock_maximo') }}" /></div>
                            <div class="col-md-4 d-flex flex-column justify-content-end">
                                <div class="form-check mb-1"><input class="form-check-input" id="requiere_lote" name="requiere_lote" type="checkbox" value="1" @checked($v('requiere_lote')) /><label class="form-check-label" for="requiere_lote">Maneja lotes</label></div>
                                <div class="form-check mb-0"><input class="form-check-input" id="es_perecedero" name="es_perecedero" type="checkbox" value="1" @checked($v('es_perecedero')) /><label class="form-check-label" for="es_perecedero">Perecedero (vence)</label></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-1">Contabilidad</h4>
                        <p class="text-700 fs--1">Opcional: las líneas de las órdenes de compra toman esta cuenta y centro de costo si no se indican otros (para el presupuesto).</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="id_cuenta_gasto">Cuenta de gasto</label>
                                <select class="form-select" id="id_cuenta_gasto" name="id_cuenta_gasto">
                                    <option value="">Ninguna</option>
                                    @foreach ($cuentas as $c)<option value="{{ $c->id_cuenta }}" @selected((int) $v('id_cuenta_gasto') === $c->id_cuenta)>{{ $c->codigo }} — {{ $c->nombre }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="id_centro_default">Centro de costo</label>
                                <select class="form-select" id="id_centro_default" name="id_centro_default">
                                    <option value="">Ninguno</option>
                                    @foreach ($centros as $c)<option value="{{ $c->id_centro }}" @selected((int) $v('id_centro_default') === $c->id_centro)>{{ $c->codigo }} — {{ $c->nombre }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Estado</h4>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked($v('activo')) />
                            <label class="form-check-label" for="activo">Activo</label>
                            <div class="form-text mt-0">Un producto inactivo no aparece en nuevas compras.</div>
                        </div>
                    </div>
                </div>
                @if ($producto->exists)
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-3">Existencia por bodega</h4>
                            @forelse ($producto->stocks as $s)
                                <div class="d-flex justify-content-between fs--1 py-1 border-bottom border-200">
                                    <span>{{ $s->bodega?->nombre ?? 'Bodega #'.$s->id_bodega }}</span>
                                    <span class="fw-semi-bold">{{ rtrim(rtrim(number_format((float) $s->cantidad_actual, 2), '0'), '.') }} {{ $producto->unidad_medida }}</span>
                                </div>
                            @empty
                                <p class="text-600 fs--1 mb-0">Sin existencia registrada.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-phoenix-secondary" href="{{ route('productos.index') }}">Cancelar</a>
        </div>
    </form>
@endsection

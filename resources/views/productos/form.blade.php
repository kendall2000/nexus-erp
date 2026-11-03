@extends('layouts.app', ['titulo' => $producto->exists ? 'Editar producto' : 'Nuevo producto'])

@section('contenido')
    @php $v = fn (string $campo) => old($campo, $producto->{$campo}); @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
            <li class="breadcrumb-item active">{{ $producto->exists ? 'Editar' : 'Nuevo' }}</li>
        </ol>
    </nav>

    <form method="POST" action="{{ $producto->exists ? route('productos.update', $producto->id_producto) : route('productos.store') }}" class="mb-9">
        @csrf
        @if ($producto->exists)
            @method('PUT')
        @endif

        {{-- Encabezado idéntico a apps/e-commerce/admin/add-product.html --}}
        <div class="row g-3 flex-between-end mb-5">
            <div class="col-auto">
                <h2 class="mb-2">{{ $producto->exists ? 'Editar producto: '.$producto->nombre : 'Agregar un nuevo producto' }}</h2>
                <h5 class="text-700 fw-semi-bold">Configuración de inventario, precios y detalles contables</h5>
            </div>
            <div class="col-auto">
                <a class="btn btn-phoenix-secondary me-2 mb-2 mb-sm-0" href="{{ route('productos.index') }}">Descartar</a>
                <button class="btn btn-primary mb-2 mb-sm-0" type="submit">
                    <span class="fas fa-save me-2"></span>{{ $producto->exists ? 'Actualizar producto' : 'Guardar producto' }}
                </button>
            </div>
        </div>

        <div class="row g-5">
            {{-- Columna Principal Izquierda (8 columnas) --}}
            <div class="col-12 col-xl-8">
                <h4 class="mb-3">Nombre del Producto</h4>
                <input class="form-control mb-5" type="text" id="nombre" name="nombre" value="{{ $v('nombre') }}" placeholder="Escribe el nombre del producto..." required maxlength="200" />

                <div class="mb-6">
                    <h4 class="mb-3">Descripción</h4>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4" maxlength="2000" placeholder="Escribe una descripción completa del producto...">{{ $v('descripcion') }}</textarea>
                </div>

                {{-- Sección de Precios e Inventario con pestañas verticales idénticas a Phoenix --}}
                <h4 class="mb-3">Inventario y Precios</h4>
                <div class="row g-0 border-top border-bottom border-300 mb-5">
                    <div class="col-sm-4">
                        <div class="nav flex-sm-column border-bottom border-bottom-sm-0 border-end-sm border-300 fs--1 vertical-tab h-100 justify-content-between" role="tablist" aria-orientation="vertical">
                            <a class="nav-link border-end border-end-sm-0 border-bottom-sm border-300 text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center active" id="pricingTab" data-bs-toggle="tab" data-bs-target="#pricingTabContent" role="tab">
                                <span class="me-sm-2 fas fa-tag"></span><span class="d-none d-sm-inline">Precios y Moneda</span>
                            </a>
                            <a class="nav-link border-end border-end-sm-0 border-bottom-sm border-300 text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center" id="restockTab" data-bs-toggle="tab" data-bs-target="#restockTabContent" role="tab">
                                <span class="me-sm-2 fas fa-boxes-stacked"></span><span class="d-none d-sm-inline">Control de Stock</span>
                            </a>
                            <a class="nav-link text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center" id="accountingTab" data-bs-toggle="tab" data-bs-target="#accountingTabContent" role="tab">
                                <span class="me-sm-2 fas fa-calculator"></span><span class="d-none d-sm-inline">Contabilidad</span>
                            </a>
                        </div>
                    </div>

                    <div class="col-sm-8">
                        <div class="tab-content py-3 ps-sm-4 h-100">
                            {{-- Tab 1: Precios --}}
                            <div class="tab-pane fade show active" id="pricingTabContent" role="tabpanel">
                                <h5 class="mb-3 text-1000">Precios de compra y venta</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="precio_compra">Precio de compra</label>
                                        <input class="form-control" id="precio_compra" name="precio_compra" type="number" step="0.0001" min="0" value="{{ $v('precio_compra') }}" placeholder="0.00" />
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="precio_venta">Precio de venta</label>
                                        <input class="form-control" id="precio_venta" name="precio_venta" type="number" step="0.0001" min="0" value="{{ $v('precio_venta') }}" placeholder="0.00" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="moneda">Moneda</label>
                                        <select data-choices="data-choices" class="form-select" id="moneda" name="moneda" required>
                                            @foreach ($monedas as $m)
                                                <option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }} — {{ $m->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 2: Control de Stock --}}
                            <div class="tab-pane fade" id="restockTabContent" role="tabpanel">
                                <h5 class="mb-3 text-1000">Parámetros de existencias</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="stock_minimo">Stock mínimo</label>
                                        <input class="form-control" id="stock_minimo" name="stock_minimo" type="number" step="0.01" min="0" value="{{ $v('stock_minimo') }}" required />
                                        <div class="form-text">Bajo este nivel se marcará en advertencia "Reponer".</div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label" for="stock_maximo">Stock máximo</label>
                                        <input class="form-control" id="stock_maximo" name="stock_maximo" type="number" step="0.01" min="0" value="{{ $v('stock_maximo') }}" />
                                        <div class="form-text">Límite para alertas de sobreabastecimiento.</div>
                                    </div>
                                </div>
                                <div class="border-top border-200 pt-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" id="requiere_lote" name="requiere_lote" type="checkbox" value="1" @checked($v('requiere_lote')) />
                                        <label class="form-check-label fw-semi-bold text-900" for="requiere_lote">Requiere trazabilidad por lotes</label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" id="es_perecedero" name="es_perecedero" type="checkbox" value="1" @checked($v('es_perecedero')) />
                                        <label class="form-check-label fw-semi-bold text-900" for="es_perecedero">Producto perecedero (fecha de caducidad)</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 3: Contabilidad --}}
                            <div class="tab-pane fade" id="accountingTabContent" role="tabpanel">
                                <h5 class="mb-3 text-1000">Imputación Contable</h5>
                                <p class="text-700 fs--1">Las órdenes de compra asignan automáticamente esta cuenta y centro de costo.</p>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label" for="id_cuenta_gasto">Cuenta contable de gasto</label>
                                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_cuenta_gasto" name="id_cuenta_gasto">
                                            <option value="">Ninguna</option>
                                            @foreach ($cuentas as $c)
                                                <option value="{{ $c->id_cuenta }}" @selected((int) $v('id_cuenta_gasto') === $c->id_cuenta)>{{ $c->codigo }} — {{ $c->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="id_centro_default">Centro de costo predeterminado</label>
                                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_centro_default" name="id_centro_default">
                                            <option value="">Ninguno</option>
                                            @foreach ($centros as $c)
                                                <option value="{{ $c->id_centro }}" @selected((int) $v('id_centro_default') === $c->id_centro)>{{ $c->codigo }} — {{ $c->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna Lateral Derecha (4 columnas) estilo Phoenix --}}
            <div class="col-12 col-xl-4">
                <div class="row g-2">
                    <div class="col-12 col-xl-12">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Organización</h4>
                                <div class="row gx-3">
                                    <div class="col-12 mb-3">
                                        <label class="form-label" for="codigo">Código / SKU</label>
                                        <input class="form-control text-uppercase" id="codigo" name="codigo" value="{{ $v('codigo') }}" placeholder="Ej: PRD-001" required maxlength="50" />
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label" for="id_categoria">Categoría</label>
                                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select" id="id_categoria" name="id_categoria">
                                            <option value="">Sin categoría</option>
                                            @foreach ($categorias as $c)
                                                <option value="{{ $c->id_categoria }}" @selected((int) $v('id_categoria') === $c->id_categoria)>{{ $c->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label" for="unidad_medida">Unidad de Medida</label>
                                        <select data-choices="data-choices" class="form-select" id="unidad_medida" name="unidad_medida" required>
                                            @foreach ($unidades as $codigo => $nombre)
                                                <option value="{{ $codigo }}" @selected($v('unidad_medida') === $codigo)>{{ $nombre }} ({{ $codigo }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Estado de publicación</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" id="activo" name="activo" type="checkbox" value="1" @checked($v('activo')) />
                                            <label class="form-check-label fw-semi-bold" for="activo">Producto Activo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($producto->exists)
                        <div class="col-12 col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-3"><span class="fas fa-warehouse text-primary me-2"></span>Existencias actuales</h4>
                                    @forelse ($producto->stocks as $s)
                                        <div class="d-flex justify-content-between align-items-center fs--1 py-2 border-bottom border-200">
                                            <span class="fw-semi-bold">{{ $s->bodega?->nombre ?? 'Bodega #'.$s->id_bodega }}</span>
                                            <span class="badge badge-phoenix badge-phoenix-primary">{{ rtrim(rtrim(number_format((float) $s->cantidad_actual, 2), '0'), '.') }} {{ $producto->unidad_medida }}</span>
                                        </div>
                                    @empty
                                        <p class="text-600 fs--1 mb-0 py-2">Sin existencias registradas en bodegas.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
@endsection

@extends('layouts.app', ['titulo' => $oc->exists ? 'Editar orden '.$oc->numero_oc : 'Nueva orden de compra'])

@section('contenido')
    @php
        $v = fn (string $campo) => old($campo, $oc->{$campo});
        $lineas = old('lineas', $oc->exists
            ? $oc->detalles->map(fn ($d) => $d->only(['id_producto', 'descripcion', 'cantidad_pedida', 'precio_unitario', 'descuento', 'id_centro', 'id_cuenta']))->all()
            : []);
        if (! $lineas) {
            $lineas = [['id_producto' => '', 'cantidad_pedida' => 1, 'precio_unitario' => '', 'descuento' => 0]];
        }
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('ordenes-compra.index') }}">Órdenes de Compra</a></li>
            <li class="breadcrumb-item active">{{ $oc->exists ? 'Editar '.$oc->numero_oc : 'Nueva Orden' }}</li>
        </ol>
    </nav>

    <form method="POST" action="{{ $oc->exists ? route('ordenes-compra.update', $oc->id_oc) : route('ordenes-compra.store') }}" class="mb-9" id="form-oc">
        @csrf
        @if ($oc->exists)
            @method('PUT')
        @endif

        {{-- Encabezado idéntico a apps/e-commerce/admin/add-product.html --}}
        <div class="row g-3 flex-between-end mb-5">
            <div class="col-auto">
                <h2 class="mb-2">{{ $oc->exists ? 'Editar Orden de Compra #'.$oc->numero_oc : 'Generar Nueva Orden de Compra' }}</h2>
                <h5 class="text-700 fw-semi-bold">Adquisición de productos e insumos con imputación a centros de costo</h5>
            </div>
            <div class="col-auto">
                <a class="btn btn-phoenix-secondary me-2 mb-2 mb-sm-0" href="{{ $oc->exists ? route('ordenes-compra.show', $oc->id_oc) : route('ordenes-compra.index') }}">Descartar</a>
                <button class="btn btn-primary mb-2 mb-sm-0" type="submit">
                    <span class="fas fa-save me-2"></span>{{ $oc->exists ? 'Actualizar orden' : 'Guardar borrador de orden' }}
                </button>
            </div>
        </div>

        {{-- Datos de la Orden (Card superior) --}}
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-white border-bottom border-200 py-3 px-4">
                <h5 class="mb-0 text-1100 fw-bold"><span class="fas fa-file-contract text-primary me-2"></span>Datos Principales de la Adquisición</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="numero_oc">Número Correlativo</label>
                        <input class="form-control text-uppercase" id="numero_oc" name="numero_oc" value="{{ old('numero_oc', $oc->numero_oc) }}" maxlength="30" placeholder="{{ $siguienteNumero }}" />
                        @unless ($oc->exists)
                            <div class="form-text">Dejar en blanco para asignar {{ $siguienteNumero }}</div>
                        @endunless
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="id_proveedor">Proveedor</label>
                        <select class="form-select" id="id_proveedor" name="id_proveedor" required>
                            <option value="">Selecciona proveedor…</option>
                            @foreach ($proveedores as $p)
                                <option value="{{ $p->id_proveedor }}" data-moneda="{{ $p->moneda_pago }}" @selected((int) $v('id_proveedor') === $p->id_proveedor)>
                                    {{ $p->razon_social }}{{ $p->nit ? ' · NIT '.$p->nit : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_bodega">Bodega de Destino</label>
                        <select class="form-select" id="id_bodega" name="id_bodega">
                            <option value="">Sin definir…</option>
                            @foreach ($bodegas as $b)
                                <option value="{{ $b->id_bodega }}" @selected((int) $v('id_bodega') === $b->id_bodega)>{{ $b->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="fecha_emision">Fecha de Emisión</label>
                        <input class="form-control" id="fecha_emision" name="fecha_emision" type="date" value="{{ old('fecha_emision', $oc->fecha_emision?->format('Y-m-d')) ?: today()->toDateString() }}" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="fecha_entrega_esperada">Fecha Entrega Esperada</label>
                        <input class="form-control" id="fecha_entrega_esperada" name="fecha_entrega_esperada" type="date" value="{{ old('fecha_entrega_esperada', $oc->fecha_entrega_esperada?->format('Y-m-d')) }}" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="moneda">Moneda</label>
                        <select class="form-select" id="moneda" name="moneda" required>
                            @foreach ($monedas as $m)
                                <option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="notas">Notas o Términos Especiales</label>
                        <input class="form-control" id="notas" name="notas" value="{{ $v('notas') }}" maxlength="2000" placeholder="Lugar de descarga, condiciones de pago..." />
                    </div>
                </div>
            </div>
        </div>

        {{-- Partidas de Productos estilo Phoenix --}}
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-white border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-1100 fw-bold">Artículos & Materiales Solicitados</h5>
                <button class="btn btn-phoenix-primary btn-sm" type="button" id="agregar-linea">
                    <span class="fas fa-plus me-1"></span>Agregar producto
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm fs--1 mb-0 align-middle" id="tabla-lineas">
                        <thead class="bg-100">
                            <tr>
                                <th class="ps-4 text-700" style="min-width: 250px;">PRODUCTO</th>
                                <th class="text-700" style="min-width: 200px;">DETALLE ADICIONAL</th>
                                <th class="text-end text-700" style="width: 100px;">CANT.</th>
                                <th class="text-end text-700" style="width: 130px;">PRECIO COMPRA</th>
                                <th class="text-end text-700" style="width: 110px;">DESCUENTO</th>
                                <th class="text-end text-700" style="width: 140px;">SUBTOTAL</th>
                                <th class="text-center pe-4 text-700" style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lineas as $i => $l)
                                @include('ordenes-compra.linea', ['i' => $i, 'l' => $l])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Totales de Compra estilo Phoenix --}}
        <div class="row justify-content-end mb-4">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="card border border-200 shadow-none">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2 fs--1">
                            <span class="text-700">Subtotal Neto:</span>
                            <span class="fw-semi-bold text-900" id="t-subtotal">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 fs--1">
                            <span class="text-700">IVA Estimado:</span>
                            <span class="fw-semi-bold text-900" id="t-iva">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between border-top border-200 pt-3 fs-1">
                            <span class="fw-bold text-1100">Total de la Orden:</span>
                            <span class="fw-bolder text-primary" id="t-total">0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template id="plantilla-linea">@include('ordenes-compra.linea', ['i' => '__I__', 'l' => []])</template>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <span class="fas fa-save me-2"></span>Guardar orden de compra
            </button>
            <a class="btn btn-phoenix-secondary" href="{{ $oc->exists ? route('ordenes-compra.show', $oc->id_oc) : route('ordenes-compra.index') }}">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var productos = @json($productos->keyBy('id_producto'));
            var centros = @json($centros->pluck('nombre', 'id_centro'));
            var cuentas = @json($cuentas->mapWithKeys(fn ($c) => [$c->id_cuenta => $c->codigo.' '.$c->nombre]));
            var fiscal = @json($fiscal);
            var cuerpo = document.querySelector('#tabla-lineas tbody');
            var siguiente = cuerpo.querySelectorAll('tr').length;
            var numero = function (v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; };
            var dinero = function (n) { return n.toLocaleString('es-GT', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };

            function recalcular() {
                var subtotal = 0;
                cuerpo.querySelectorAll('tr').forEach(function (fila) {
                    var cantInput = fila.querySelector('.cantidad');
                    var precioInput = fila.querySelector('.precio');
                    var descInput = fila.querySelector('.descuento');
                    if (!cantInput || !precioInput) return;
                    var importe = Math.max(0, numero(cantInput.value) * numero(precioInput.value) - (descInput ? numero(descInput.value) : 0));
                    var impEl = fila.querySelector('.importe');
                    if (impEl) impEl.textContent = dinero(importe);
                    subtotal += importe;
                });
                var iva = fiscal.incluido ? subtotal - subtotal / (1 + fiscal.tasa) : subtotal * fiscal.tasa;
                document.getElementById('t-subtotal').textContent = dinero(subtotal);
                document.getElementById('t-iva').textContent = dinero(iva);
                document.getElementById('t-total').textContent = dinero(fiscal.incluido ? subtotal : subtotal + iva);
            }

            function heredados(fila) {
                var pSelect = fila.querySelector('.producto');
                if (!pSelect) return;
                var p = productos[pSelect.value];
                var ayuda = fila.querySelector('.heredado');
                if (!p || !ayuda) return;
                var partes = [];
                if (p.id_centro_default && centros[p.id_centro_default]) partes.push(centros[p.id_centro_default]);
                if (p.id_cuenta_gasto && cuentas[p.id_cuenta_gasto]) partes.push(cuentas[p.id_cuenta_gasto]);
                ayuda.textContent = partes.length ? 'Imputa a: ' + partes.join(' / ') : '';
            }

            cuerpo.addEventListener('change', function (e) {
                var fila = e.target.closest('tr');
                if (e.target.classList.contains('producto')) {
                    var p = productos[e.target.value];
                    if (p && !numero(fila.querySelector('.precio').value)) fila.querySelector('.precio').value = p.precio_compra ?? '';
                    heredados(fila);
                }
                recalcular();
            });
            cuerpo.addEventListener('input', recalcular);
            cuerpo.addEventListener('click', function (e) {
                var boton = e.target.closest('.quitar-linea');
                if (!boton) return;
                if (cuerpo.querySelectorAll('tr').length > 1) boton.closest('tr').remove();
                else boton.closest('tr').querySelectorAll('input, select').forEach(function (c) { c.value = c.classList.contains('cantidad') ? 1 : ''; });
                recalcular();
            });
            document.getElementById('agregar-linea').addEventListener('click', function () {
                var html = document.getElementById('plantilla-linea').innerHTML.replace(/__I__/g, siguiente++);
                cuerpo.insertAdjacentHTML('beforeend', html);
                recalcular();
            });
            document.getElementById('id_proveedor').addEventListener('change', function () {
                var moneda = this.selectedOptions[0] && this.selectedOptions[0].dataset.moneda;
                if (moneda) document.getElementById('moneda').value = moneda;
            });

            cuerpo.querySelectorAll('tr').forEach(heredados);
            recalcular();
        })();
    </script>
@endpush

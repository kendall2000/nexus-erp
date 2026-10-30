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
            <li class="breadcrumb-item"><a href="{{ route('ordenes-compra.index') }}">Órdenes de compra</a></li>
            <li class="breadcrumb-item active">{{ $oc->exists ? $oc->numero_oc : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $oc->exists ? 'Editar orden '.$oc->numero_oc : 'Nueva orden de compra' }}</h2>

    <form method="POST" action="{{ $oc->exists ? route('ordenes-compra.update', $oc->id_oc) : route('ordenes-compra.store') }}" class="mb-9" id="form-oc">
        @csrf
        @if ($oc->exists)
            @method('PUT')
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="numero_oc">Número</label>
                        <input class="form-control text-uppercase" id="numero_oc" name="numero_oc" value="{{ old('numero_oc', $oc->numero_oc) }}" maxlength="30" placeholder="{{ $siguienteNumero }}" />
                        @unless ($oc->exists)<div class="form-text">Vacío = {{ $siguienteNumero }}</div>@endunless
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="id_proveedor">Proveedor</label>
                        <select class="form-select" id="id_proveedor" name="id_proveedor" required>
                            <option value="">Selecciona…</option>
                            @foreach ($proveedores as $p)<option value="{{ $p->id_proveedor }}" data-moneda="{{ $p->moneda_pago }}" @selected((int) $v('id_proveedor') === $p->id_proveedor)>{{ $p->razon_social }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_bodega">Bodega de entrega</label>
                        <select class="form-select" id="id_bodega" name="id_bodega">
                            <option value="">Sin definir</option>
                            @foreach ($bodegas as $b)<option value="{{ $b->id_bodega }}" @selected((int) $v('id_bodega') === $b->id_bodega)>{{ $b->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label" for="fecha_emision">Fecha de emisión</label><input class="form-control" id="fecha_emision" name="fecha_emision" type="date" value="{{ old('fecha_emision', $oc->fecha_emision?->format('Y-m-d')) }}" required /></div>
                    <div class="col-md-3"><label class="form-label" for="fecha_entrega_esperada">Entrega esperada</label><input class="form-control" id="fecha_entrega_esperada" name="fecha_entrega_esperada" type="date" value="{{ old('fecha_entrega_esperada', $oc->fecha_entrega_esperada?->format('Y-m-d')) }}" /></div>
                    <div class="col-md-2">
                        <label class="form-label" for="moneda">Moneda</label>
                        <select class="form-select" id="moneda" name="moneda" required>
                            @foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label" for="notas">Notas</label><input class="form-control" id="notas" name="notas" value="{{ $v('notas') }}" maxlength="2000" /></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Productos</h4>
                    <button class="btn btn-phoenix-primary btn-sm" type="button" id="agregar-linea"><span class="fas fa-plus me-1"></span>Agregar línea</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm fs--1 align-middle mb-0" id="tabla-lineas">
                        <thead>
                        <tr>
                            <th style="min-width: 16rem">Producto</th>
                            <th style="width: 7rem" class="text-end">Cantidad</th>
                            <th style="width: 8rem" class="text-end">Precio</th>
                            <th style="width: 7rem" class="text-end">Descuento</th>
                            <th style="min-width: 11rem">Centro / cuenta (presupuesto)</th>
                            <th style="width: 8rem" class="text-end">Importe</th>
                            <th style="width: 2rem"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($lineas as $i => $l)
                            @include('ordenes-compra.linea', ['i' => $i, 'l' => $l])
                        @endforeach
                        </tbody>
                        <tfoot class="fw-semi-bold">
                        <tr><td colspan="5" class="text-end">Subtotal</td><td class="text-end" id="t-subtotal">0.00</td><td></td></tr>
                        <tr><td colspan="5" class="text-end">IVA ({{ rtrim(rtrim(number_format($fiscal['tasa'] * 100, 2), '0'), '.') }} %{{ $fiscal['incluido'] ? ', incluido en precios' : '' }})</td><td class="text-end" id="t-iva">0.00</td><td></td></tr>
                        <tr class="fs-0"><td colspan="5" class="text-end">Total</td><td class="text-end" id="t-total">0.00</td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <template id="plantilla-linea">@include('ordenes-compra.linea', ['i' => '__I__', 'l' => []])</template>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar borrador</button>
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
                    var importe = Math.max(0, numero(fila.querySelector('.cantidad').value) * numero(fila.querySelector('.precio').value) - numero(fila.querySelector('.descuento').value));
                    fila.querySelector('.importe').textContent = dinero(importe);
                    subtotal += importe;
                });
                var iva = fiscal.incluido ? subtotal - subtotal / (1 + fiscal.tasa) : subtotal * fiscal.tasa;
                document.getElementById('t-subtotal').textContent = dinero(subtotal);
                document.getElementById('t-iva').textContent = dinero(iva);
                document.getElementById('t-total').textContent = dinero(fiscal.incluido ? subtotal : subtotal + iva);
            }

            // Muestra de dónde saldrán centro y cuenta si la línea no indica otros.
            function heredados(fila) {
                var p = productos[fila.querySelector('.producto').value];
                var ayuda = fila.querySelector('.heredado');
                if (!p) { ayuda.textContent = ''; return; }
                var partes = [];
                if (p.id_centro_default && centros[p.id_centro_default]) partes.push(centros[p.id_centro_default]);
                if (p.id_cuenta_gasto && cuentas[p.id_cuenta_gasto]) partes.push(cuentas[p.id_cuenta_gasto]);
                ayuda.textContent = partes.length ? 'Del producto: ' + partes.join(' / ') : 'El producto no tiene cuenta ni centro';
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
                if (cuerpo.querySelectorAll('tr').length > 1) boton.closest('tr').remove(); else boton.closest('tr').querySelectorAll('input, select').forEach(function (c) { c.value = c.classList.contains('cantidad') ? 1 : ''; });
                recalcular();
            });
            document.getElementById('agregar-linea').addEventListener('click', function () {
                var html = document.getElementById('plantilla-linea').innerHTML.replace(/__I__/g, siguiente++);
                cuerpo.insertAdjacentHTML('beforeend', html);
                feather.replace();
            });
            // La moneda sigue al proveedor si todavía no se cambió a mano.
            document.getElementById('id_proveedor').addEventListener('change', function () {
                var moneda = this.selectedOptions[0] && this.selectedOptions[0].dataset.moneda;
                if (moneda) document.getElementById('moneda').value = moneda;
            });

            cuerpo.querySelectorAll('tr').forEach(heredados);
            recalcular();
        })();
    </script>
@endpush

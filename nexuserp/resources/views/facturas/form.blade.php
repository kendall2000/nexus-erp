@extends('layouts.app', ['titulo' => $f->exists ? 'Editar '.$f->numero_completo : 'Nueva factura'])

@section('contenido')
    @php
        $v = fn (string $campo) => old($campo, $f->{$campo});
        $fecha = fn (string $campo) => old($campo, $f->{$campo}?->format('Y-m-d'));
        $lineas = old('lineas', $f->exists
            ? $f->detalles->map(fn ($d) => $d->only(['id_tipo_servicio', 'descripcion', 'cantidad', 'precio_unitario', 'descuento', 'es_afecto_iva', 'id_centro', 'id_cuenta']))->all()
            : []);
        if (! $lineas) {
            $lineas = [['cantidad' => 1, 'descuento' => 0, 'es_afecto_iva' => true]];
        }
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('facturas.index') }}">Facturas</a></li>
            <li class="breadcrumb-item active">{{ $f->exists ? $f->numero_completo : 'Nueva' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $f->exists ? 'Editar '.($tipos[$f->tipo] ?? '').' '.$f->numero_completo : 'Nueva factura' }}</h2>

    <form method="POST" action="{{ $f->exists ? route('facturas.update', $f->id_factura) : route('facturas.store') }}" class="mb-9">
        @csrf
        @if ($f->exists)
            @method('PUT')
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="id_serie">Serie / tipo</label>
                        @if ($f->exists)
                            <input class="form-control" value="{{ $f->serie?->codigo_serie }} — {{ $tipos[$f->tipo] ?? $f->tipo }}" disabled />
                            <div class="form-text">La serie y el número ya están asignados.</div>
                        @else
                            <select class="form-select @error('id_serie') is-invalid @enderror" id="id_serie" name="id_serie" required>
                                <option value="">Selecciona…</option>
                                @foreach ($series as $s)<option value="{{ $s->id_serie }}" @selected((int) old('id_serie', $series->firstWhere('tipo', 'FACTURA')?->id_serie) === $s->id_serie)>{{ $s->codigo_serie }} — {{ $tipos[$s->tipo] }} (sigue {{ $s->formatearNumero($s->ultimo_numero + 1) }})</option>@endforeach
                            </select>
                            @error('id_serie')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @endif
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="id_cliente">Cliente</label>
                        <select class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente" required>
                            <option value="">Selecciona…</option>
                            @foreach ($clientes as $c)<option value="{{ $c->id_cliente }}" data-moneda="{{ $c->moneda_facturacion }}" data-dias="{{ $c->dias_credito }}" @selected((int) $v('id_cliente') === $c->id_cliente)>{{ $c->razon_social }}{{ $c->nit ? ' · NIT '.$c->nit : '' }}</option>@endforeach
                        </select>
                        @error('id_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="moneda">Moneda</label>
                        <select class="form-select" id="moneda" name="moneda" required>
                            @foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="fecha_emision">Emisión</label>
                        <input class="form-control" id="fecha_emision" name="fecha_emision" type="date" value="{{ $fecha('fecha_emision') }}" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="fecha_vencimiento">Vencimiento</label>
                        <input class="form-control @error('fecha_vencimiento') is-invalid @enderror" id="fecha_vencimiento" name="fecha_vencimiento" type="date" value="{{ $fecha('fecha_vencimiento') }}" required />
                        @error('fecha_vencimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2"><label class="form-label" for="periodo_servicio_inicio">Periodo desde</label><input class="form-control" id="periodo_servicio_inicio" name="periodo_servicio_inicio" type="date" value="{{ $fecha('periodo_servicio_inicio') }}" /></div>
                    <div class="col-md-2">
                        <label class="form-label" for="periodo_servicio_fin">Periodo hasta</label>
                        <input class="form-control @error('periodo_servicio_fin') is-invalid @enderror" id="periodo_servicio_fin" name="periodo_servicio_fin" type="date" value="{{ $fecha('periodo_servicio_fin') }}" />
                        @error('periodo_servicio_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6"><label class="form-label" for="notas">Notas</label><input class="form-control" id="notas" name="notas" value="{{ $v('notas') }}" maxlength="2000" /></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Detalle</h4>
                    <button class="btn btn-phoenix-primary btn-sm" type="button" id="agregar-linea"><span class="fas fa-plus me-1"></span>Agregar línea</button>
                </div>
                @error('lineas')<div class="alert alert-soft-danger fs--1">{{ $message }}</div>@enderror
                <div class="table-responsive">
                    <table class="table table-sm fs--1 align-middle mb-0" id="tabla-lineas">
                        <thead>
                        <tr>
                            <th style="min-width: 16rem">Servicio / descripción</th>
                            <th style="width: 6rem" class="text-end">Cantidad</th>
                            <th style="width: 8rem" class="text-end">Precio</th>
                            <th style="width: 7rem" class="text-end">Descuento</th>
                            <th style="width: 3rem" class="text-center" title="Afecta a IVA">IVA</th>
                            <th style="min-width: 11rem">Centro / cuenta (presupuesto)</th>
                            <th style="width: 8rem" class="text-end">Importe</th>
                            <th style="width: 2rem"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($lineas as $i => $l)
                            @include('facturas.linea', ['i' => $i, 'l' => $l])
                        @endforeach
                        </tbody>
                        <tfoot class="fw-semi-bold">
                        <tr><td colspan="6" class="text-end">Subtotal</td><td class="text-end" id="t-subtotal">0.00</td><td></td></tr>
                        <tr>
                            <td colspan="6" class="text-end align-middle">Descuento global</td>
                            <td><input class="form-control form-control-sm text-end @error('descuento') is-invalid @enderror" id="descuento" name="descuento" type="number" step="0.01" min="0" value="{{ (float) $v('descuento') }}" /></td><td></td>
                        </tr>
                        <tr><td colspan="6" class="text-end">IVA ({{ rtrim(rtrim(number_format($fiscal['tasa'] * 100, 2), '0'), '.') }} %{{ $fiscal['incluido'] ? ', incluido en precios' : '' }})</td><td class="text-end" id="t-iva">0.00</td><td></td></tr>
                        <tr class="fs-0"><td colspan="6" class="text-end">Total</td><td class="text-end" id="t-total">0.00</td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
                @error('descuento')<div class="text-danger fs--1 text-end">{{ $message }}</div>@enderror
                @foreach ($errors->get('lineas.*') as $mensajes)<div class="text-danger fs--1">{{ $mensajes[0] }}</div>@endforeach
            </div>
        </div>

        <template id="plantilla-linea">@include('facturas.linea', ['i' => '__I__', 'l' => []])</template>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar borrador</button>
            <a class="btn btn-phoenix-secondary" href="{{ $f->exists ? route('facturas.show', $f->id_factura) : route('facturas.index') }}">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var servicios = @json($servicios->keyBy('id_tipo_servicio')->map(fn ($s) => ['nombre' => $s->nombre, 'precio' => $s->precio_base]));
            var fiscal = @json($fiscal);
            var cuerpo = document.querySelector('#tabla-lineas tbody');
            var siguiente = cuerpo.querySelectorAll('tr').length;
            var numero = function (v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; };
            var dinero = function (n) { return n.toLocaleString('es-GT', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };

            // Mismo cálculo que Factura::recalcularTotales: el descuento global se reparte antes del IVA.
            function recalcular() {
                var afecto = 0, exento = 0;
                cuerpo.querySelectorAll('tr').forEach(function (fila) {
                    var importe = Math.max(0, numero(fila.querySelector('.cantidad').value) * numero(fila.querySelector('.precio').value) - numero(fila.querySelector('.descuento').value));
                    fila.querySelector('.importe').textContent = dinero(importe);
                    if (fila.querySelector('.afecto').checked) afecto += importe; else exento += importe;
                });
                var subtotal = afecto + exento;
                var descuento = Math.min(numero(document.getElementById('descuento').value), subtotal);
                var descAfecto = subtotal > 0 ? descuento * afecto / subtotal : 0;
                var afectoNeto = afecto - descAfecto, exentoNeto = exento - (descuento - descAfecto);
                var iva = fiscal.incluido ? afectoNeto - afectoNeto / (1 + fiscal.tasa) : afectoNeto * fiscal.tasa;
                document.getElementById('t-subtotal').textContent = dinero(subtotal);
                document.getElementById('t-iva').textContent = dinero(iva);
                document.getElementById('t-total').textContent = dinero(fiscal.incluido ? afectoNeto + exentoNeto : afectoNeto + iva + exentoNeto);
            }

            cuerpo.addEventListener('change', function (e) {
                var fila = e.target.closest('tr');
                if (e.target.classList.contains('servicio')) {
                    var s = servicios[e.target.value];
                    if (s && !fila.querySelector('.descripcion').value) fila.querySelector('.descripcion').value = s.nombre;
                    if (s && !numero(fila.querySelector('.precio').value) && s.precio) fila.querySelector('.precio').value = s.precio;
                }
                recalcular();
            });
            cuerpo.addEventListener('input', recalcular);
            document.getElementById('descuento').addEventListener('input', recalcular);
            cuerpo.addEventListener('click', function (e) {
                var boton = e.target.closest('.quitar-linea');
                if (!boton) return;
                if (cuerpo.querySelectorAll('tr').length > 1) boton.closest('tr').remove();
                else boton.closest('tr').querySelectorAll('input:not([type=checkbox]), select').forEach(function (c) { c.value = c.classList.contains('cantidad') ? 1 : ''; });
                recalcular();
            });
            document.getElementById('agregar-linea').addEventListener('click', function () {
                cuerpo.insertAdjacentHTML('beforeend', document.getElementById('plantilla-linea').innerHTML.replace(/__I__/g, siguiente++));
                recalcular();
            });
            // Moneda y vencimiento siguen al cliente (días de crédito).
            document.getElementById('id_cliente').addEventListener('change', function () {
                var o = this.selectedOptions[0];
                if (!o || !o.dataset.moneda) return;
                document.getElementById('moneda').value = o.dataset.moneda;
                var emision = document.getElementById('fecha_emision').value;
                if (emision) {
                    var d = new Date(emision + 'T00:00:00');
                    d.setDate(d.getDate() + parseInt(o.dataset.dias || '0', 10));
                    var dos = function (n) { return String(n).padStart(2, '0'); };
                    document.getElementById('fecha_vencimiento').value = d.getFullYear() + '-' + dos(d.getMonth() + 1) + '-' + dos(d.getDate());
                }
            });
            recalcular();
        })();
    </script>
@endpush

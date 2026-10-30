@extends('layouts.app', ['titulo' => $c->exists ? 'Editar contrato' : 'Nuevo contrato'])

@section('contenido')
    @php
        $v = fn (string $campo) => old($campo, $c->{$campo});
        $fecha = fn (string $campo) => old($campo, $c->{$campo}?->format('Y-m-d'));
        $clienteFijo = $c->exists && $c->estado !== 'BORRADOR';
        $lineas = old('lineas', $c->exists ? $c->detalles->map(fn ($d) => $d->only(['id_tipo_servicio', 'id_sitio', 'descripcion', 'cantidad', 'precio_unitario', 'descuento_pct']))->all() : []);
        if (! $lineas) { $lineas = [['cantidad' => 1, 'descuento_pct' => 0]]; }
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('contratos.index') }}">Contratos</a></li>
            <li class="breadcrumb-item active">{{ $c->exists ? $c->numero_contrato : 'Nuevo' }}</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">{{ $c->exists ? 'Editar contrato '.$c->numero_contrato : 'Nuevo contrato' }}</h2>

    <form method="POST" action="{{ $c->exists ? route('contratos.update', $c->id_contrato) : route('contratos.store') }}" class="mb-9">
        @csrf
        @if ($c->exists)
            @method('PUT')
        @endif
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="numero_contrato">Número</label>
                        <input class="form-control text-uppercase @error('numero_contrato') is-invalid @enderror" id="numero_contrato" name="numero_contrato" value="{{ $v('numero_contrato') }}" maxlength="60" placeholder="{{ $siguienteNumero }}" />
                        @error('numero_contrato')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="id_cliente">Cliente</label>
                        <select class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente" @disabled($clienteFijo) required>
                            <option value="">Selecciona…</option>
                            @foreach ($clientes as $cl)<option value="{{ $cl->id_cliente }}" data-moneda="{{ $cl->moneda_facturacion }}" @selected((int) $v('id_cliente') === $cl->id_cliente)>{{ $cl->razon_social }}</option>@endforeach
                        </select>
                        @error('id_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($clienteFijo)<div class="form-text">El cliente no cambia una vez activado el contrato.</div>@endif
                    </div>
                    <div class="col-md-4"><label class="form-label" for="nombre_proyecto">Proyecto</label><input class="form-control" id="nombre_proyecto" name="nombre_proyecto" value="{{ $v('nombre_proyecto') }}" maxlength="200" /></div>
                    <div class="col-md-3"><label class="form-label" for="fecha_inicio">Inicio</label><input class="form-control" id="fecha_inicio" name="fecha_inicio" type="date" value="{{ $fecha('fecha_inicio') }}" required /></div>
                    <div class="col-md-3">
                        <label class="form-label" for="fecha_fin">Fin</label>
                        <input class="form-control @error('fecha_fin') is-invalid @enderror" id="fecha_fin" name="fecha_fin" type="date" value="{{ $fecha('fecha_fin') }}" />
                        @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Vacío = indefinido.</div>
                    </div>
                    <div class="col-md-3"><label class="form-label" for="fecha_firma">Firma</label><input class="form-control" id="fecha_firma" name="fecha_firma" type="date" value="{{ $fecha('fecha_firma') }}" /></div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_vendedor">Vendedor</label>
                        <select class="form-select" id="id_vendedor" name="id_vendedor">
                            <option value="">—</option>
                            @foreach ($vendedores as $e)<option value="{{ $e->id_empleado }}" @selected((int) $v('id_vendedor') === $e->id_empleado)>{{ $e->nombre_completo }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="moneda">Moneda</label>
                        <select class="form-select" id="moneda" name="moneda">@foreach ($monedas as $m)<option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="periodicidad_factura">Se factura</label>
                        <select class="form-select" id="periodicidad_factura" name="periodicidad_factura">@foreach ($periodicidades as $k => $n)<option value="{{ $k }}" @selected($v('periodicidad_factura') === $k)>{{ $n }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="dia_facturacion">Día</label>
                        <input class="form-control @error('dia_facturacion') is-invalid @enderror" id="dia_facturacion" name="dia_facturacion" type="number" min="1" max="28" value="{{ $v('dia_facturacion') }}" />
                        @error('dia_facturacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5"><label class="form-label" for="notas">Notas</label><input class="form-control" id="notas" name="notas" value="{{ $v('notas') }}" maxlength="5000" /></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Servicios (valor mensual)</h4>
                    <button class="btn btn-phoenix-primary btn-sm" type="button" id="agregar-linea"><span class="fas fa-plus me-1"></span>Agregar servicio</button>
                </div>
                @error('lineas')<div class="alert alert-soft-danger fs--1">{{ $message }}</div>@enderror
                @foreach ($errors->get('lineas.*') as $mensajes)<div class="text-danger fs--1">{{ $mensajes[0] }}</div>@endforeach
                <div class="table-responsive">
                    <table class="table table-sm fs--1 align-middle mb-0" id="tabla-lineas">
                        <thead><tr><th style="min-width: 16rem">Servicio</th><th style="min-width: 10rem">Sitio</th><th style="width: 6rem" class="text-end">Cantidad</th><th style="width: 8rem" class="text-end">Precio</th><th style="width: 6rem" class="text-end">Desc. %</th><th style="width: 8rem" class="text-end">Importe</th><th style="width: 2rem"></th></tr></thead>
                        <tbody>
                        @foreach ($lineas as $i => $l)
                            @include('contratos.linea', ['i' => $i, 'l' => $l])
                        @endforeach
                        </tbody>
                        <tfoot class="fw-semi-bold"><tr class="fs-0"><td colspan="5" class="text-end">Valor mensual</td><td class="text-end" id="t-total">0.00</td><td></td></tr></tfoot>
                    </table>
                </div>
                <p class="fs--2 text-600 mt-2 mb-0">Los sitios son los del cliente elegido; se registran en la ficha del contrato.</p>
            </div>
        </div>

        <template id="plantilla-linea">@include('contratos.linea', ['i' => '__I__', 'l' => []])</template>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-phoenix-secondary" href="{{ $c->exists ? route('contratos.show', $c->id_contrato) : route('contratos.index') }}">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var servicios = @json($servicios->keyBy('id_tipo_servicio')->map(fn ($s) => ['precio' => (float) $s->precio_base]));
            var sitios = @json($sitios);
            var cliente = document.getElementById('id_cliente');
            var cuerpo = document.querySelector('#tabla-lineas tbody');
            var siguiente = cuerpo.querySelectorAll('tr').length;
            var numero = function (v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; };
            var dinero = function (n) { return n.toLocaleString('es-GT', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };

            function llenarSitios(select) {
                var valor = select.value || select.dataset.valor;
                select.innerHTML = '<option value="">Sin sitio</option>';
                sitios.filter(function (s) { return String(s.id_cliente) === cliente.value; }).forEach(function (s) {
                    var o = new Option(s.nombre, s.id_sitio);
                    if (String(s.id_sitio) === String(valor)) o.selected = true;
                    select.appendChild(o);
                });
            }
            function recalcular() {
                var total = 0;
                cuerpo.querySelectorAll('tr').forEach(function (f) {
                    var importe = numero(f.querySelector('.cantidad').value) * numero(f.querySelector('.precio').value) * (1 - numero(f.querySelector('.descuento').value) / 100);
                    f.querySelector('.importe').textContent = dinero(importe);
                    total += importe;
                });
                document.getElementById('t-total').textContent = dinero(total);
            }
            cuerpo.addEventListener('change', function (e) {
                if (e.target.classList.contains('servicio')) {
                    var s = servicios[e.target.value], precio = e.target.closest('tr').querySelector('.precio');
                    if (s && s.precio && !numero(precio.value)) precio.value = s.precio;
                }
                recalcular();
            });
            cuerpo.addEventListener('input', recalcular);
            cuerpo.addEventListener('click', function (e) {
                var b = e.target.closest('.quitar-linea');
                if (!b) return;
                if (cuerpo.querySelectorAll('tr').length > 1) b.closest('tr').remove();
                recalcular();
            });
            document.getElementById('agregar-linea').addEventListener('click', function () {
                cuerpo.insertAdjacentHTML('beforeend', document.getElementById('plantilla-linea').innerHTML.replace(/__I__/g, siguiente++));
                llenarSitios(cuerpo.lastElementChild.querySelector('.sitio'));
                recalcular();
            });
            cliente.addEventListener('change', function () {
                cuerpo.querySelectorAll('.sitio').forEach(function (s) { s.dataset.valor = ''; llenarSitios(s); });
                var o = cliente.selectedOptions[0];
                if (o && o.dataset.moneda) document.getElementById('moneda').value = o.dataset.moneda;
            });
            cuerpo.querySelectorAll('.sitio').forEach(llenarSitios);
            recalcular();
        })();
    </script>
@endpush

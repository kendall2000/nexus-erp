@extends('layouts.app', ['titulo' => $f->exists ? 'Editar '.$f->numero_completo : 'Nueva factura'])

@section('contenido')
    @php
        $v = fn (string $campo) => old($campo, $f->{$campo});
        $fecha = fn (string $campo) => old($campo, $f->{$campo}?->format('Y-m-d'));
        $lineas = old('lineas', $f->exists || $f->relationLoaded('detalles')
            ? $f->detalles->map(fn ($d) => $d->only(['id_tipo_servicio', 'descripcion', 'cantidad', 'precio_unitario', 'descuento', 'es_afecto_iva', 'id_centro', 'id_cuenta']))->all()
            : []);
        if (! $lineas) {
            $lineas = [['cantidad' => 1, 'descuento' => 0, 'es_afecto_iva' => true]];
        }
    @endphp

    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('facturas.index') }}">Facturación</a></li>
            <li class="breadcrumb-item active">{{ $f->exists ? 'Editar '.$f->numero_completo : 'Nueva Factura' }}</li>
        </ol>
    </nav>

    <form method="POST" action="{{ $f->exists ? route('facturas.update', $f->id_factura) : route('facturas.store') }}" class="mb-9">
        @csrf
        @if ($f->exists)
            @method('PUT')
        @endif
        <input type="hidden" name="id_contrato" value="{{ old('id_contrato', $f->id_contrato) }}" />

        {{-- Encabezado idéntico a apps/e-commerce/admin/add-product.html --}}
        <div class="row g-3 flex-between-end mb-5">
            <div class="col-auto">
                <h2 class="mb-2">{{ $f->exists ? 'Editar Documento: '.$f->numero_completo : 'Crear Nueva Factura de Venta' }}</h2>
                <h5 class="text-700 fw-semi-bold">Emisión de documentos comerciales, afectación de inventario y ejecución presupuestaria</h5>
            </div>
            <div class="col-auto">
                <a class="btn btn-phoenix-secondary me-2 mb-2 mb-sm-0" href="{{ $f->exists ? route('facturas.show', $f->id_factura) : route('facturas.index') }}">Descartar</a>
                <button class="btn btn-primary mb-2 mb-sm-0" type="submit">
                    <span class="fas fa-save me-2"></span>{{ $f->exists ? 'Actualizar borrador' : 'Guardar borrador de factura' }}
                </button>
            </div>
        </div>

        @if (old('id_contrato', $f->id_contrato))
            <div class="alert alert-subtle-info border-0 fs--1 mb-4">
                <span class="fas fa-file-contract me-2"></span>Factura del contrato <strong>{{ \App\Models\Clientes\ContratoServicio::find(old('id_contrato', $f->id_contrato))?->numero_contrato }}</strong>.
            </div>
        @endif

        {{-- Datos del Comprobante (Card superior) --}}
        <div class="card border border-200 shadow-none mb-4">
            <div class="card-header bg-white border-bottom border-200 py-3 px-4">
                <h5 class="mb-0 text-1100 fw-bold"><span class="fas fa-file-invoice text-primary me-2"></span>Encabezado del Documento Fiscal</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="id_serie">Serie y Tipo</label>
                        @if ($f->exists)
                            <input class="form-control" value="{{ $f->serie?->codigo_serie }} — {{ $tipos[$f->tipo] ?? $f->tipo }}" disabled />
                        @else
                            <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select @error('id_serie') is-invalid @enderror" id="id_serie" name="id_serie" required>
                                <option value="">Selecciona serie…</option>
                                @foreach ($series as $s)
                                    <option value="{{ $s->id_serie }}" @selected((int) old('id_serie', $series->firstWhere('tipo', 'FACTURA')?->id_serie) === $s->id_serie)>
                                        {{ $s->codigo_serie }} — {{ $tipos[$s->tipo] }} (sigue {{ $s->formatearNumero($s->ultimo_numero + 1) }}){{ $s->sucursal ? ' · '.$s->sucursal->nombre : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_serie')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @endif
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="id_cliente">Cliente Facturado</label>
                        <select data-choices="data-choices" data-options='{"removeItemButton":true,"placeholder":true}' class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente" required>
                            <option value="">Selecciona cliente…</option>
                            @foreach ($clientes as $c)
                                <option value="{{ $c->id_cliente }}" data-moneda="{{ $c->moneda_facturacion }}" data-dias="{{ $c->dias_credito }}" @selected((int) $v('id_cliente') === $c->id_cliente)>
                                    {{ $c->razon_social }}{{ $c->nit ? ' · NIT '.$c->nit : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_cliente')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="moneda">Moneda</label>
                        <select class="form-select" id="moneda" name="moneda" required>
                            @foreach ($monedas as $m)
                                <option value="{{ $m->codigo }}" @selected($v('moneda') === $m->codigo)>{{ $m->codigo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="fecha_emision">Fecha Emisión</label>
                        <input class="form-control" id="fecha_emision" name="fecha_emision" type="date" value="{{ $fecha('fecha_emision') ?: today()->toDateString() }}" required />
                    </div>
                    <div class="col-md-4">
                        @include('partials.campo-sede', ['sedes' => $sedes, 'valor' => $v('id_sucursal'),
                            'ayuda' => 'Si la serie es de un establecimiento, la factura queda en esa sede.'])
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="fecha_vencimiento">Fecha Vencimiento</label>
                        <input class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" type="date" value="{{ $fecha('fecha_vencimiento') ?: today()->toDateString() }}" required />
                    </div>
                    <div class="col-md-9">
                        <label class="form-label" for="notas">Observaciones o Notas al Pie de Factura</label>
                        <input class="form-control" id="notas" name="notas" value="{{ $v('notas') }}" maxlength="1000" placeholder="Condiciones de entrega, pago en cheque, etc..." />
                    </div>
                </div>
            </div>
        </div>

        {{-- Detalle de Líneas de Factura montado en Vue 3 con fallback --}}
        <div data-vue-component="FacturaLineas" data-vue-props="{{ json_encode([
            'initialLineas' => $lineas,
            'servicios' => $servicios->map(fn ($s) => [
                'id_tipo_servicio' => $s->id_tipo_servicio,
                'nombre' => $s->nombre,
                'precio_base' => $s->precio_base,
                'linea_negocio' => $s->lineaNegocio ? ['nombre' => $s->lineaNegocio->nombre] : null
            ]),
            'centros' => $centros->map(fn ($c) => ['id_centro' => $c->id_centro, 'etiqueta' => $c->etiqueta]),
            'cuentas' => $cuentas->map(fn ($c) => ['id_cuenta' => $c->id_cuenta, 'etiqueta' => $c->etiqueta]),
            'fiscal' => $fiscal,
            'initialDescuento' => (float) $v('descuento') ?: 0
        ]) }}">
            {{-- Fallback estático en caso de pruebas sin JS o renderizado inicial --}}
            <div class="card border border-200 shadow-none mb-4">
                <div class="card-header bg-white border-bottom border-200 py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-1100 fw-bold">Partidas & Conceptos Facturados</h5>
                    <button class="btn btn-phoenix-primary btn-sm" type="button" id="agregar-linea">
                        <span class="fas fa-plus me-1"></span>Agregar línea
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-sm fs--1 mb-0 align-middle" id="tabla-lineas">
                            <thead class="bg-100">
                                <tr>
                                    <th class="ps-4 text-700" style="min-width: 200px;">SERVICIO / CONCEPTO</th>
                                    <th class="text-700" style="min-width: 250px;">DESCRIPCIÓN</th>
                                    <th class="text-end text-700" style="width: 100px;">CANT.</th>
                                    <th class="text-end text-700" style="width: 130px;">PRECIO UNIT.</th>
                                    <th class="text-end text-700" style="width: 120px;">DESCUENTO</th>
                                    <th class="text-center text-700" style="width: 80px;">IVA</th>
                                    <th class="text-end text-700" style="width: 140px;">SUBTOTAL</th>
                                    <th class="text-center pe-4 text-700" style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lineas as $i => $l)
                                    @include('facturas.linea', ['i' => $i, 'l' => $l])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Descuentos Globales y Totales estilo Phoenix --}}
            <div class="row justify-content-end mb-4">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="card border border-200 shadow-none">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label fs--1" for="descuento">Descuento Global Adicional (Monto)</label>
                                <input class="form-control form-control-sm text-end" id="descuento" name="descuento" type="number" step="0.01" min="0" value="{{ (float) $v('descuento') ?: '' }}" placeholder="0.00" />
                            </div>
                            <div class="d-flex justify-content-between mb-2 fs--1">
                                <span class="text-700">Subtotal Neto:</span>
                                <span class="fw-semi-bold text-900" id="t-subtotal">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 fs--1">
                                <span class="text-700">IVA (12%):</span>
                                <span class="fw-semi-bold text-900" id="t-iva">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between border-top border-200 pt-3 fs-1">
                                <span class="fw-bold text-1100">Total Liquidado:</span>
                                <span class="fw-bolder text-primary" id="t-total">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template id="plantilla-linea">@include('facturas.linea', ['i' => '__I__', 'l' => []])</template>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <span class="fas fa-save me-2"></span>Guardar documento
            </button>
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

            function recalcular() {
                var afecto = 0, exento = 0;
                cuerpo.querySelectorAll('tr').forEach(function (fila) {
                    var cantInput = fila.querySelector('.cantidad');
                    var precioInput = fila.querySelector('.precio');
                    var descInput = fila.querySelector('.descuento');
                    if (!cantInput || !precioInput) return;
                    var importe = Math.max(0, numero(cantInput.value) * numero(precioInput.value) - (descInput ? numero(descInput.value) : 0));
                    var impEl = fila.querySelector('.importe');
                    if (impEl) impEl.textContent = dinero(importe);
                    var afectoEl = fila.querySelector('.afecto');
                    if (afectoEl && afectoEl.checked) afecto += importe; else exento += importe;
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

@extends('layouts.app', ['titulo' => 'Registrar movimiento'])

@section('contenido')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('movimientos.index') }}">Kardex</a></li>
            <li class="breadcrumb-item active">Registrar movimiento</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">Registrar movimiento</h2>

    <form method="POST" action="{{ route('movimientos.store') }}" class="mb-9" style="max-width: 48rem">
        @csrf
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label d-block">Tipo de movimiento</label>
                        @foreach ($tipos as $codigo => [$nombre])
                            <div class="form-check form-check-inline">
                                <input class="form-check-input tipo" type="radio" name="tipo" id="tipo-{{ $codigo }}" value="{{ $codigo }}" @checked(old('tipo', 'AJUSTE_ENTRADA') === $codigo) />
                                <label class="form-check-label" for="tipo-{{ $codigo }}">{{ $nombre }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="id_producto">Producto</label>
                        <select class="form-select @error('id_producto') is-invalid @enderror" id="id_producto" name="id_producto" required>
                            <option value="">Selecciona…</option>
                            @foreach ($productos as $p)<option value="{{ $p->id_producto }}" data-unidad="{{ $p->unidad_medida }}" data-lote="{{ (int) $p->requiere_lote }}" data-perecedero="{{ (int) $p->es_perecedero }}" @selected((int) old('id_producto', $elegido['producto'] ?? 0) === $p->id_producto)>{{ $p->codigo }} — {{ $p->nombre }}</option>@endforeach
                        </select>
                        @error('id_producto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_bodega"><span id="etiqueta-bodega">Bodega</span></label>
                        <select class="form-select @error('id_bodega') is-invalid @enderror" id="id_bodega" name="id_bodega" required>
                            <option value="">Selecciona…</option>
                            @foreach ($bodegas as $b)<option value="{{ $b->id_bodega }}" @selected((int) old('id_bodega', $elegido['bodega'] ?? 0) === $b->id_bodega)>{{ $b->nombre }}</option>@endforeach
                        </select>
                        @error('id_bodega')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 solo-traslado">
                        <label class="form-label" for="id_bodega_destino">Bodega destino</label>
                        <select class="form-select @error('id_bodega_destino') is-invalid @enderror" id="id_bodega_destino" name="id_bodega_destino">
                            <option value="">Selecciona…</option>
                            @foreach ($bodegas as $b)<option value="{{ $b->id_bodega }}" @selected((int) old('id_bodega_destino') === $b->id_bodega)>{{ $b->nombre }}</option>@endforeach
                        </select>
                        @error('id_bodega_destino')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="cantidad">Cantidad <span class="text-600" id="unidad"></span></label>
                        <input class="form-control @error('cantidad') is-invalid @enderror" id="cantidad" name="cantidad" type="number" step="any" min="0" value="{{ old('cantidad') }}" required />
                        @error('cantidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" id="disponible"></div>
                    </div>
                    <div class="col-md-4 solo-entrada">
                        <label class="form-label" for="costo_unitario">Costo unitario</label>
                        <input class="form-control" id="costo_unitario" name="costo_unitario" type="number" step="any" min="0" value="{{ old('costo_unitario') }}" />
                        <div class="form-text">Vacío = costo promedio actual.</div>
                    </div>
                    <div class="col-md-4 solo-entrada">
                        <label class="form-label" for="numero_lote">Lote</label>
                        <input class="form-control @error('numero_lote') is-invalid @enderror" id="numero_lote" name="numero_lote" value="{{ old('numero_lote') }}" maxlength="50" />
                        @error('numero_lote')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 solo-entrada">
                        <label class="form-label" for="fecha_vencimiento">Vencimiento</label>
                        <input class="form-control @error('fecha_vencimiento') is-invalid @enderror" id="fecha_vencimiento" name="fecha_vencimiento" type="date" value="{{ old('fecha_vencimiento') }}" />
                        @error('fecha_vencimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="motivo">Motivo</label>
                        <input class="form-control @error('motivo') is-invalid @enderror" id="motivo" name="motivo" value="{{ old('motivo') }}" maxlength="250" required placeholder="Ej.: conteo físico, producto dañado, consumo del área de limpieza" />
                        @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit" onclick="return confirm('Los movimientos no se pueden editar ni borrar. ¿Registrar?')">Registrar</button>
            <a class="btn btn-phoenix-secondary" href="{{ route('movimientos.index') }}">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var existencias = @json($existencias);
            var producto = document.getElementById('id_producto');
            var bodega = document.getElementById('id_bodega');
            var cantidad = document.getElementById('cantidad');
            function tipo() { var t = document.querySelector('input.tipo:checked'); return t ? t.value : ''; }
            function actualizar() {
                var t = tipo(), entrada = t === 'AJUSTE_ENTRADA', traslado = t === 'TRASLADO';
                document.querySelectorAll('.solo-entrada').forEach(function (e) { e.style.display = entrada ? '' : 'none'; });
                document.querySelectorAll('.solo-traslado').forEach(function (e) { e.style.display = traslado ? '' : 'none'; });
                document.getElementById('etiqueta-bodega').textContent = traslado ? 'Bodega de origen' : 'Bodega';
                document.getElementById('id_bodega_destino').required = traslado;
                var o = producto.selectedOptions[0];
                document.getElementById('unidad').textContent = o && o.dataset.unidad ? '(' + o.dataset.unidad + ')' : '';
                document.getElementById('numero_lote').required = entrada && o && o.dataset.lote === '1';
                document.getElementById('fecha_vencimiento').required = entrada && o && o.dataset.perecedero === '1';
                var e = existencias[producto.value + '-' + bodega.value];
                var disponible = e ? e.cantidad : 0;
                document.getElementById('disponible').textContent = producto.value && bodega.value
                    ? 'Existencia actual: ' + disponible.toLocaleString('es-GT') + (e ? ' · costo promedio ' + e.costo.toLocaleString('es-GT', {minimumFractionDigits: 2}) : '') : '';
                if (entrada) cantidad.removeAttribute('max'); else cantidad.max = disponible;
            }
            document.querySelectorAll('input.tipo').forEach(function (r) { r.addEventListener('change', actualizar); });
            producto.addEventListener('change', actualizar);
            bodega.addEventListener('change', actualizar);
            actualizar();
        })();
    </script>
@endpush

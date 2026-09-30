@extends('layouts.app', ['titulo' => 'Nueva recepción'])

@section('contenido')
    @php $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 4), '0'), '.'); @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('recepciones.index') }}">Recepciones</a></li>
            <li class="breadcrumb-item active">Nueva</li>
        </ol>
    </nav>
    <h2 class="mb-4 text-1100">Nueva recepción</h2>

    {{-- Paso 1: elegir la orden --}}
    <div class="card mb-4">
        <div class="card-body">
            @if ($ordenes->isEmpty())
                <p class="mb-0 text-700"><span class="fas fa-info-circle me-2"></span>No hay órdenes aprobadas con mercadería pendiente. Primero aprueba una orden de compra.</p>
            @else
                <form method="GET" action="{{ route('recepciones.create') }}" class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label" for="oc">Orden de compra</label>
                        <select class="form-select" id="oc" name="oc" onchange="this.form.submit()" required>
                            <option value="">Selecciona la orden que llegó…</option>
                            @foreach ($ordenes as $o)
                                <option value="{{ $o->id_oc }}" @selected($oc?->id_oc === $o->id_oc)>{{ $o->numero_oc }} — {{ $o->proveedor?->razon_social }}{{ $o->estado === 'PARCIAL' ? ' (recibida en parte)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><noscript><button class="btn btn-phoenix-secondary" type="submit">Continuar</button></noscript></div>
                </form>
                @if ($ocInvalida)<div class="text-danger fs--1 mt-2">Esa orden no existe o ya no admite recepciones.</div>@endif
            @endif
        </div>
    </div>

    {{-- Paso 2: lo que llegó --}}
    @if ($oc)
        <form method="POST" action="{{ route('recepciones.store') }}" class="mb-9">
            @csrf
            <input type="hidden" name="id_oc" value="{{ $oc->id_oc }}" />
            @error('id_oc')<div class="alert alert-soft-danger fs--1">{{ $message }}</div>@enderror

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="numero_recepcion">Número</label>
                            <input class="form-control text-uppercase @error('numero_recepcion') is-invalid @enderror" id="numero_recepcion" name="numero_recepcion" value="{{ old('numero_recepcion') }}" maxlength="30" placeholder="{{ $siguienteNumero }}" />
                            <div class="form-text">Vacío = {{ $siguienteNumero }}</div>
                            @error('numero_recepcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="fecha_recepcion">Fecha de recepción</label>
                            <input class="form-control @error('fecha_recepcion') is-invalid @enderror" id="fecha_recepcion" name="fecha_recepcion" type="date" value="{{ old('fecha_recepcion', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required />
                            @error('fecha_recepcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="id_bodega">Bodega que recibe</label>
                            <select class="form-select @error('id_bodega') is-invalid @enderror" id="id_bodega" name="id_bodega" required>
                                <option value="">Selecciona…</option>
                                @foreach ($bodegas as $b)<option value="{{ $b->id_bodega }}" @selected((int) old('id_bodega', $oc->id_bodega) === $b->id_bodega)>{{ $b->nombre }}</option>@endforeach
                            </select>
                            @error('id_bodega')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3"><label class="form-label" for="notas">Notas</label><input class="form-control" id="notas" name="notas" value="{{ old('notas') }}" maxlength="2000" placeholder="Ej.: guía o factura del proveedor" /></div>
                    </div>
                    <p class="fs--1 text-700 mt-3 mb-0">Orden <strong>{{ $oc->numero_oc }}</strong> · {{ $oc->proveedor?->razon_social }} · moneda {{ $oc->moneda }}</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    @error('lineas')<div class="alert alert-soft-danger fs--1">{{ $message }}</div>@enderror
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th>Producto</th><th class="text-end">Pedido</th><th class="text-end">Ya recibido</th><th class="text-end">Pendiente</th><th style="width: 9rem">Recibir ahora</th><th style="width: 9rem">Costo unitario</th></tr></thead>
                            <tbody>
                            @foreach ($oc->detalles as $d)
                                @php $pendiente = max(0, (float) $d->cantidad_pedida - (float) $d->cantidad_recibida); @endphp
                                <tr class="{{ $pendiente <= 0 ? 'text-500' : '' }}">
                                    <td>
                                        <span class="fw-semi-bold">{{ $d->producto?->nombre ?? 'Producto #'.$d->id_producto }}</span>
                                        <span class="text-600 fs--2 d-block"><code>{{ $d->producto?->codigo }}</code> {{ $d->descripcion }}</span>
                                    </td>
                                    <td class="text-end">{{ $cantidad($d->cantidad_pedida) }} {{ $d->producto?->unidad_medida }}</td>
                                    <td class="text-end">{{ $cantidad($d->cantidad_recibida) }}</td>
                                    <td class="text-end fw-semi-bold">{{ $cantidad($pendiente) }}</td>
                                    @if ($pendiente > 0)
                                        <td>
                                            <input class="form-control form-control-sm text-end @error('lineas.'.$d->id_linea.'.cantidad') is-invalid @enderror" type="number" step="any" min="0" max="{{ $pendiente }}"
                                                   name="lineas[{{ $d->id_linea }}][cantidad]" value="{{ old('lineas.'.$d->id_linea.'.cantidad', $cantidad($pendiente)) }}" />
                                            @error('lineas.'.$d->id_linea.'.cantidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </td>
                                        <td><input class="form-control form-control-sm text-end" type="number" step="any" min="0" name="lineas[{{ $d->id_linea }}][costo]" value="{{ old('lineas.'.$d->id_linea.'.costo', \App\Http\Controllers\RecepcionController::costoLinea($d)) }}" /></td>
                                    @else
                                        <td colspan="2" class="text-success"><span class="fas fa-check me-1"></span>Completa</td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="fs--2 text-600 mt-2 mb-0">Deja en 0 lo que no llegó; quedará pendiente para otra recepción. El costo sugerido es el precio de la orden con su descuento.</p>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit" onclick="return confirm('Se registrará la entrada al inventario. Las recepciones no se pueden editar ni anular. ¿Continuar?')"><span class="fas fa-check me-2"></span>Registrar recepción</button>
                <a class="btn btn-phoenix-secondary" href="{{ route('recepciones.index') }}">Cancelar</a>
            </div>
        </form>
    @endif
@endsection

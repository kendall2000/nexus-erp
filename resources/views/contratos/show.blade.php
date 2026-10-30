@extends('layouts.app', ['titulo' => 'Contrato '.$c->numero_contrato])

@section('contenido')
    @php
        $yo = auth()->user();
        $puedeEditar = $yo->puede('contratos.editar');
        [$nombreEstado, $color] = $estados[$c->estado] ?? [$c->estado, 'secondary'];
        $cerrado = in_array($c->estado, ['VENCIDO', 'CANCELADO', 'RENOVADO'], true);
        $dinero = fn ($n) => number_format((float) $n, 2);
        $boton = function (string $accion, string $texto, string $clase, ?string $confirmar = null) use ($c) {
            return view('contratos.boton-estado', compact('c', 'accion', 'texto', 'clase', 'confirmar'))->render();
        };
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('contratos.index') }}">Contratos</a></li>
            <li class="breadcrumb-item active">{{ $c->numero_contrato }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Contrato {{ $c->numero_contrato }} <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1 align-middle">{{ $nombreEstado }}</span></h2>
            <p class="text-700 mb-0">
                @if ($yo->puede('clientes.ver'))<a href="{{ route('clientes.show', $c->id_cliente) }}">{{ $c->cliente?->razon_social }}</a>@else{{ $c->cliente?->razon_social }}@endif
                {{ $c->nombre_proyecto ? '· '.$c->nombre_proyecto : '' }} · {{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? 'indefinido' }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($c->estado === 'VIGENTE' && $yo->puede('facturas.crear'))
                <a class="btn btn-success" href="{{ route('facturas.create', ['contrato' => $c->id_contrato]) }}"><span class="fas fa-file-invoice-dollar me-2"></span>Facturar</a>
            @endif
            @if ($yo->puede('contratos.imprimir'))
                <a class="btn btn-phoenix-secondary" href="{{ route('contratos.imprimir', $c->id_contrato) }}" target="_blank" rel="noopener"><span class="fas fa-print me-2"></span>Imprimir</a>
            @endif
            @if ($puedeEditar && ! $cerrado)
                <a class="btn btn-phoenix-secondary" href="{{ route('contratos.edit', $c->id_contrato) }}"><span class="fas fa-pen me-2"></span>Editar</a>
            @endif
            @if ($puedeEditar && $c->estado === 'BORRADOR'){!! $boton('activar', 'Activar', 'btn-success', '¿Activar el contrato? Desde ahora se puede facturar.') !!}@endif
            @if ($puedeEditar && $c->estado === 'VIGENTE'){!! $boton('suspender', 'Suspender', 'btn-phoenix-warning') !!}@endif
            @if ($puedeEditar && $c->estado === 'SUSPENDIDO'){!! $boton('reanudar', 'Reanudar', 'btn-phoenix-success') !!}@endif
            @if ($yo->puede('contratos.cerrar') && in_array($c->estado, ['VIGENTE', 'SUSPENDIDO'], true))
                <button class="btn btn-phoenix-danger" type="button" data-bs-toggle="collapse" data-bs-target="#cerrar">Cerrar</button>
            @endif
            @if ($yo->puede('contratos.reabrir') && in_array($c->estado, ['VENCIDO', 'CANCELADO'], true)){!! $boton('reabrir', 'Reabrir', 'btn-phoenix-warning') !!}@endif
        </div>
    </div>

    @if ($yo->puede('contratos.cerrar') && in_array($c->estado, ['VIGENTE', 'SUSPENDIDO'], true))
        <div class="collapse {{ $errors->has('motivo') ? 'show' : '' }} mb-4" id="cerrar">
            <div class="card border-danger"><div class="card-body">
                <p class="fs--1 mb-2">Queda {{ $c->fecha_fin && $c->fecha_fin->lte(today()) ? 'vencido' : 'cancelado' }} y el personal asignado se libera.</p>
                <form method="POST" action="{{ route('contratos.estado', [$c->id_contrato, 'cerrar']) }}" class="row g-2">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-9">
                        <input class="form-control @error('motivo') is-invalid @enderror" name="motivo" value="{{ old('motivo') }}" maxlength="300" placeholder="Motivo del cierre" required />
                        @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3"><button class="btn btn-danger w-100" type="submit">Confirmar cierre</button></div>
                </form>
            </div></div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Valor mensual</p><h3 class="mb-0">{{ $c->moneda }} {{ $dinero($c->valor_mensual) }}</h3></div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Valor total estimado</p><h3 class="mb-0">{{ $c->valor_total_estimado !== null ? $c->moneda.' '.$dinero($c->valor_total_estimado) : 'Indefinido' }}</h3></div></div></div>
        <div class="col-sm-4"><div class="card h-100"><div class="card-body py-3"><p class="fs--1 text-700 mb-1">Facturación</p><h3 class="mb-0">{{ $periodicidades[$c->periodicidad_factura] ?? $c->periodicidad_factura }}</h3><p class="fs--2 text-700 mb-0">{{ $c->dia_facturacion ? 'el día '.$c->dia_facturacion : '' }}{{ $c->vendedor ? ' · vendedor '.$c->vendedor->nombre_completo : '' }}</p></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">Servicios</h5>
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead><tr><th>Servicio</th><th>Sitio</th><th class="text-end">Cantidad</th><th class="text-end">Precio</th><th class="text-end">Desc.</th><th class="text-end">Importe</th></tr></thead>
                    <tbody>
                    @foreach ($c->detalles as $d)
                        <tr>
                            <td><span class="fw-semi-bold">{{ $d->tipoServicio?->nombre }}</span>@if ($d->descripcion)<span class="d-block fs--2 text-600">{{ $d->descripcion }}</span>@endif</td>
                            <td>{{ $d->sitio?->nombre ?? '—' }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format((float) $d->cantidad, 2), '0'), '.') }}</td>
                            <td class="text-end">{{ $dinero($d->precio_unitario) }}</td>
                            <td class="text-end">{{ (float) $d->descuento_pct ? rtrim(rtrim(number_format((float) $d->descuento_pct, 2), '0'), '.').' %' : '—' }}</td>
                            <td class="text-end fw-semi-bold">{{ $dinero($d->subtotal) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($c->notas)<p class="fs--1 text-700 mt-3 mb-0" style="white-space: pre-line">{{ $c->notas }}</p>@endif
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Sitios de trabajo del cliente</h5>
                    @if ($errors->sitio->any())<div class="alert alert-soft-danger">{{ $errors->sitio->first() }}</div>@endif
                    @forelse ($sitios as $s)
                        <div class="border-bottom border-200 py-2">
                            <span class="fw-semi-bold">{{ $s->nombre }}</span>
                            <span class="d-block text-700">{{ $s->direccion }}{{ $s->municipio ? ', '.$s->municipio->nombre : '' }}</span>
                            @if ($s->responsable_cliente)<span class="d-block text-600">Responsable: {{ $s->responsable_cliente }} {{ $s->tel_responsable }}</span>@endif
                        </div>
                    @empty
                        <p class="text-700">El cliente no tiene sitios registrados.</p>
                    @endforelse
                    @if ($puedeEditar && ! $cerrado)
                        <details class="mt-3" @if ($errors->sitio->any()) open @endif>
                            <summary class="btn btn-phoenix-primary btn-sm"><span class="fas fa-plus me-1"></span>Agregar sitio</summary>
                            <form method="POST" action="{{ route('contratos.sitios.store', $c->id_contrato) }}" class="row g-2 mt-1">
                                @csrf
                                <div class="col-12"><input class="form-control form-control-sm" name="nombre" value="{{ old('nombre') }}" placeholder="Nombre del sitio" required maxlength="200" /></div>
                                <div class="col-12"><input class="form-control form-control-sm" name="direccion" value="{{ old('direccion') }}" placeholder="Dirección" required maxlength="300" /></div>
                                <div class="col-md-6">
                                    <select class="form-select form-select-sm" name="id_municipio"><option value="">Municipio…</option>@foreach ($municipios as $m)<option value="{{ $m->id_municipio }}" @selected((int) old('id_municipio') === $m->id_municipio)>{{ $m->nombre }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-6"><input class="form-control form-control-sm" name="responsable_cliente" value="{{ old('responsable_cliente') }}" placeholder="Responsable del cliente" maxlength="200" /></div>
                                <div class="col-md-6"><input class="form-control form-control-sm" name="tel_responsable" value="{{ old('tel_responsable') }}" placeholder="Teléfono" maxlength="20" /></div>
                                <div class="col-md-6"><button class="btn btn-primary btn-sm w-100" type="submit">Guardar sitio</button></div>
                            </form>
                        </details>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Personal asignado</h5>
                    @if ($errors->asignacion->any())<div class="alert alert-soft-danger">{{ $errors->asignacion->first() }}</div>@endif
                    @forelse ($asignaciones as $a)
                        <div class="d-flex justify-content-between align-items-start border-bottom border-200 py-2 {{ $a->activo ? '' : 'text-500' }}">
                            <div>
                                <span class="fw-semi-bold">{{ $a->empleado?->nombre_completo }}</span>
                                <span class="d-block text-700">{{ collect([$a->rol_en_sitio, $a->sitio?->nombre, $turnos[$a->turno] ?? null])->filter()->implode(' · ') }}</span>
                                <span class="d-block text-600">{{ $a->fecha_inicio?->format('d/m/Y') }} – {{ $a->fecha_fin?->format('d/m/Y') ?? '…' }}{{ $a->activo ? '' : ' (finalizada)' }}</span>
                            </div>
                            @if ($a->activo && $puedeEditar)
                                <form method="POST" action="{{ route('contratos.asignaciones.finalizar', [$c->id_contrato, $a->id_asignacion]) }}" onsubmit="return confirm('¿Finalizar esta asignación?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-link btn-sm p-0" type="submit">Finalizar</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-700">Sin personal asignado.</p>
                    @endforelse
                    @if ($puedeEditar && ! $cerrado)
                        <details class="mt-3" @if ($errors->asignacion->any()) open @endif>
                            <summary class="btn btn-phoenix-primary btn-sm"><span class="fas fa-user-plus me-1"></span>Asignar empleado</summary>
                            <form method="POST" action="{{ route('contratos.asignaciones.store', $c->id_contrato) }}" class="row g-2 mt-1">
                                @csrf
                                <div class="col-md-6">
                                    <select class="form-select form-select-sm" name="id_empleado" required><option value="">Empleado…</option>@foreach ($empleados as $e)<option value="{{ $e->id_empleado }}" @selected((int) old('id_empleado') === $e->id_empleado)>{{ $e->nombre_completo }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select form-select-sm" name="id_sitio"><option value="">Sin sitio</option>@foreach ($sitios as $s)<option value="{{ $s->id_sitio }}" @selected((int) old('id_sitio') === $s->id_sitio)>{{ $s->nombre }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-6"><input class="form-control form-control-sm" name="rol_en_sitio" value="{{ old('rol_en_sitio') }}" placeholder="Rol (ej.: agente, supervisor)" maxlength="150" /></div>
                                <div class="col-md-6">
                                    <select class="form-select form-select-sm" name="turno"><option value="">Turno…</option>@foreach ($turnos as $k => $n)<option value="{{ $k }}" @selected(old('turno') === $k)>{{ $n }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-4"><input class="form-control form-control-sm" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', ($c->fecha_inicio->gt(today()) ? $c->fecha_inicio : today())->format('Y-m-d')) }}" required /></div>
                                <div class="col-md-4"><input class="form-control form-control-sm" name="fecha_fin" type="date" value="{{ old('fecha_fin') }}" /></div>
                                <div class="col-md-4"><button class="btn btn-primary btn-sm w-100" type="submit">Asignar</button></div>
                            </form>
                        </details>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Facturas del contrato</h5>
            @if ($facturas->isEmpty())
                <p class="fs--1 text-700 mb-0">Todavía no se ha facturado este contrato.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead><tr><th>Factura</th><th>Emisión</th><th>Periodo</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th>Estado</th></tr></thead>
                        <tbody>
                        @foreach ($facturas as $f)
                            <tr>
                                <td>@if ($yo->puede('facturas.ver'))<a href="{{ route('facturas.show', $f->id_factura) }}">{{ $f->numero_completo }}</a>@else{{ $f->numero_completo }}@endif</td>
                                <td>{{ $f->fecha_emision?->format('d/m/Y') }}</td>
                                <td>{{ $f->periodo_servicio_inicio?->format('d/m/Y') }} – {{ $f->periodo_servicio_fin?->format('d/m/Y') }}</td>
                                <td class="text-end">{{ $f->moneda }} {{ $dinero($f->total) }}</td>
                                <td class="text-end">{{ $dinero($f->saldo_pendiente) }}</td>
                                <td>{{ \App\Http\Controllers\FacturaController::ESTADOS[$f->estado][0] ?? $f->estado }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

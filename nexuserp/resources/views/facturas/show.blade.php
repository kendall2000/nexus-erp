@extends('layouts.app', ['titulo' => $f->numero_completo])

@section('contenido')
    @php
        $yo = auth()->user();
        [$nombreEstado, $color] = $estados[$f->estado] ?? [$f->estado, 'secondary'];
        $tipo = $tipos[$f->tipo] ?? $f->tipo;
        $vencida = in_array($f->estado, \App\Models\Clientes\Cliente::ESTADOS_CON_SALDO, true) && (float) $f->saldo_pendiente > 0 && $f->fecha_vencimiento?->lt(today());
        $anulable = in_array($f->estado, ['BORRADOR', 'EMITIDA', 'ENVIADA', 'VENCIDA'], true) && (float) $f->total_pagado <= 0
            && $f->pagos->where('estado', 'APLICADO')->isEmpty() && (float) $f->monto_condonado <= 0;
        $conSaldo = in_array($f->estado, \App\Models\Clientes\Cliente::ESTADOS_CON_SALDO, true) && (float) $f->saldo_pendiente > 0;
        $puedeCobrar = $yo->puede('pagos.crear') || $yo->puede('facturas.cobrar');
        $dinero = fn ($n) => number_format((float) $n, 2);
    @endphp
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('facturas.index') }}">Facturas</a></li>
            <li class="breadcrumb-item active">{{ $f->numero_completo }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">{{ $tipo }} {{ $f->numero_completo }}
                <span class="badge badge-phoenix badge-phoenix-{{ $color }} fs--1 align-middle">{{ $nombreEstado }}</span>
                @if ($vencida)<span class="badge badge-phoenix badge-phoenix-danger fs--1 align-middle">Vencida hace {{ (int) $f->fecha_vencimiento->diffInDays(today()) }} días</span>@endif
            </h2>
            <p class="text-700 mb-0">
                @if ($f->cliente && $yo->puede('clientes.ver'))<a href="{{ route('clientes.show', $f->id_cliente) }}">{{ $f->cliente->razon_social }}</a>@else{{ $f->cliente?->razon_social }}@endif
                · emitida el {{ $f->fecha_emision?->format('d/m/Y') }} · vence el {{ $f->fecha_vencimiento?->format('d/m/Y') }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($conSaldo && $puedeCobrar)
                <a class="btn btn-success" href="{{ route('pagos.create', ['factura' => $f->id_factura]) }}"><span class="fas fa-hand-holding-usd me-2"></span>Registrar cobro</a>
            @endif
            @if ($conSaldo && $yo->puede('facturas.condonar'))
                <button class="btn btn-phoenix-warning" type="button" data-bs-toggle="collapse" data-bs-target="#condonar" aria-expanded="{{ $errors->has('monto') ? 'true' : 'false' }}">Condonar</button>
            @endif
            @if ($f->estado !== 'BORRADOR' && $yo->puede('facturas.imprimir'))
                <a class="btn btn-phoenix-secondary" href="{{ route('facturas.imprimir', $f->id_factura) }}" target="_blank" rel="noopener"><span class="fas fa-print me-2"></span>Imprimir</a>
            @endif
            @if ($f->estado === 'BORRADOR' && $yo->puede('facturas.editar'))
                <a class="btn btn-phoenix-secondary" href="{{ route('facturas.edit', $f->id_factura) }}"><span class="fas fa-pen me-2"></span>Editar</a>
                <form method="POST" action="{{ route('facturas.emitir', $f->id_factura) }}" onsubmit="return confirm(@js('¿Emitir '.$tipo.' '.$f->numero_completo.' por '.$f->moneda.' '.$dinero($f->total).'? Ya no se podrá editar.'))">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-success" type="submit"><span class="fas fa-check me-2"></span>Emitir</button>
                </form>
            @endif
            @if ($f->estado === 'EMITIDA' && $yo->puede('facturas.editar'))
                <form method="POST" action="{{ route('facturas.enviar', $f->id_factura) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-phoenix-primary" type="submit"><span class="fas fa-paper-plane me-2"></span>Marcar como enviada</button>
                </form>
            @endif
            @if ($anulable && $yo->puede('facturas.anular'))
                <button class="btn btn-phoenix-danger" type="button" data-bs-toggle="collapse" data-bs-target="#anular" aria-expanded="{{ $errors->has('motivo') && ! old('monto') ? 'true' : 'false' }}">Anular</button>
            @endif
            @if ($eliminable && $yo->puede('facturas.editar'))
                <form method="POST" action="{{ route('facturas.destroy', $f->id_factura) }}" onsubmit="return confirm(@js('¿Eliminar el borrador '.$f->numero_completo.'? Su número quedará libre.'))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger" type="submit" title="Eliminar borrador"><span class="fas fa-trash"></span></button>
                </form>
            @endif
        </div>
    </div>

    @if ($anulable && $yo->puede('facturas.anular'))
        <div class="collapse {{ $errors->has('motivo') && ! old('monto') ? 'show' : '' }} mb-4" id="anular">
            <div class="card border-danger"><div class="card-body">
                <form method="POST" action="{{ route('facturas.anular', $f->id_factura) }}" class="row g-2 align-items-start">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-9">
                        <input class="form-control @error('motivo') is-invalid @enderror" name="motivo" value="{{ old('motivo') }}" maxlength="300" placeholder="Motivo de la anulación" required />
                        @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($f->estado !== 'BORRADOR')<div class="form-text">Se revertirá lo que sumó al presupuesto.</div>@endif
                    </div>
                    <div class="col-md-3"><button class="btn btn-danger w-100" type="submit">Confirmar anulación</button></div>
                </form>
            </div></div>
        </div>
    @endif

    @if ($conSaldo && $yo->puede('facturas.condonar'))
        <div class="collapse {{ $errors->has('monto') || ($errors->has('motivo') && old('monto')) ? 'show' : '' }} mb-4" id="condonar">
            <div class="card border-warning"><div class="card-body">
                <form method="POST" action="{{ route('facturas.condonar', $f->id_factura) }}" class="row g-2 align-items-start">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-3">
                        <input class="form-control @error('monto') is-invalid @enderror" name="monto" type="number" step="0.01" min="0.01" max="{{ round((float) $f->saldo_pendiente, 2) }}" value="{{ old('monto', round((float) $f->saldo_pendiente, 2)) }}" required title="Monto a condonar" />
                        @error('monto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <input class="form-control" name="motivo" value="{{ old('motivo') }}" maxlength="300" placeholder="Motivo (ej.: acuerdo comercial, incobrable)" required />
                    </div>
                    <div class="col-md-3"><button class="btn btn-warning w-100" type="submit" onclick="return confirm('El monto condonado ya no se cobrará. ¿Continuar?')">Condonar saldo</button></div>
                </form>
            </div></div>
        </div>
    @endif

    @if ($f->tipo === 'NOTA_CREDITO')
        <div class="alert alert-soft-info fs--1"><span class="fas fa-info-circle me-2"></span>Una nota de crédito no genera saldo por cobrar y al emitirse resta del presupuesto de ingresos.</div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm fs--1 mb-0 align-middle">
                            <thead><tr><th>Descripción</th><th class="text-end">Cantidad</th><th class="text-end">Precio</th><th class="text-end">Descuento</th><th class="text-end">Importe</th><th>Centro / cuenta</th></tr></thead>
                            <tbody>
                            @foreach ($f->detalles as $d)
                                <tr>
                                    <td>
                                        <span class="fw-semi-bold">{{ $d->descripcion }}</span>
                                        @if (! $d->es_afecto_iva)<span class="badge badge-phoenix badge-phoenix-secondary ms-1">Exento</span>@endif
                                        @if ($d->tipoServicio && $d->tipoServicio->nombre !== $d->descripcion)<span class="d-block text-600 fs--2">{{ $d->tipoServicio->nombre }}</span>@endif
                                    </td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $d->cantidad, 2), '0'), '.') }}</td>
                                    <td class="text-end">{{ $dinero($d->precio_unitario) }}</td>
                                    <td class="text-end">{{ (float) $d->descuento ? $dinero($d->descuento) : '—' }}</td>
                                    <td class="text-end fw-semi-bold">{{ $dinero($d->subtotal) }}</td>
                                    <td class="fs--2 text-700">
                                        {{ $d->centroCosto?->nombre ?? ($d->tipoServicio?->centroDefault?->nombre ? $d->tipoServicio->centroDefault->nombre.' (servicio)' : '—') }}<br>
                                        {{ $d->cuentaContable?->nombre ?? ($d->tipoServicio?->cuentaIngreso?->nombre ? $d->tipoServicio->cuentaIngreso->nombre.' (servicio)' : '—') }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot class="fw-semi-bold">
                            <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end">{{ $dinero($f->subtotal) }}</td><td></td></tr>
                            @if ((float) $f->descuento)<tr><td colspan="4" class="text-end">Descuento</td><td class="text-end">−{{ $dinero($f->descuento) }}</td><td></td></tr>@endif
                            <tr><td colspan="4" class="text-end">Base imponible</td><td class="text-end">{{ $dinero($f->base_imponible) }}</td><td></td></tr>
                            <tr><td colspan="4" class="text-end">IVA</td><td class="text-end">{{ $dinero($f->iva) }}</td><td></td></tr>
                            <tr class="fs-0"><td colspan="4" class="text-end">Total</td><td class="text-end text-nowrap">{{ $f->moneda }} {{ $dinero($f->total) }}</td><td></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Cobro</h5>
                    <dl class="row mb-0">
                        <dt class="col-6 text-700">Total</dt><dd class="col-6 text-end">{{ $f->moneda }} {{ $dinero($f->total) }}</dd>
                        <dt class="col-6 text-700">Pagado</dt><dd class="col-6 text-end">{{ $dinero($f->total_pagado) }}</dd>
                        @if ((float) $f->monto_condonado > 0)
                            <dt class="col-6 text-700">Condonado</dt><dd class="col-6 text-end" title="{{ $f->condonadoPor?->nombre_completo }} · {{ $f->fecha_condonacion?->format('d/m/Y') }}">{{ $dinero($f->monto_condonado) }}</dd>
                        @endif
                        <dt class="col-6 text-700">Saldo</dt><dd class="col-6 text-end fw-bold {{ $vencida ? 'text-danger' : '' }}">{{ $f->estado === 'BORRADOR' ? '—' : $dinero($f->saldo_pendiente) }}</dd>
                    </dl>
                    @if ($f->pagos->isNotEmpty())
                        <h6 class="mt-3 mb-2">Pagos</h6>
                        @foreach ($f->pagos as $p)
                            <div class="d-flex justify-content-between border-bottom border-200 py-1 {{ $p->estado !== 'APLICADO' ? 'text-500' : '' }}">
                                <span>
                                    @if ($yo->puede('pagos.ver'))<a href="{{ route('pagos.show', $p->id_pago) }}">{{ $p->fecha_pago?->format('d/m/Y') }}</a>@else{{ $p->fecha_pago?->format('d/m/Y') }}@endif
                                    · {{ \App\Models\Finanzas\Pago::FORMAS[$p->forma_pago] ?? $p->forma_pago }} <span class="text-600">{{ $p->referencia }}</span>
                                    @if ($p->estado !== 'APLICADO')<span class="badge badge-phoenix badge-phoenix-{{ \App\Models\Finanzas\Pago::ESTADOS[$p->estado][1] }}">{{ \App\Models\Finanzas\Pago::ESTADOS[$p->estado][0] }}</span>@endif
                                </span>
                                <span class="fw-semi-bold {{ $p->estado !== 'APLICADO' ? 'text-decoration-line-through' : '' }}">{{ $dinero($p->monto) }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-body fs--1">
                    <h5 class="mb-3">Detalles</h5>
                    <dl class="row mb-0">
                        <dt class="col-5 text-700">NIT cliente</dt><dd class="col-7">{{ $f->cliente?->nit ?? '—' }}</dd>
                        <dt class="col-5 text-700">Serie</dt><dd class="col-7">{{ $f->serie?->codigo_serie }} · n.º {{ $f->numero_factura }}</dd>
                        @if ($f->periodo_servicio_inicio)<dt class="col-5 text-700">Periodo</dt><dd class="col-7">{{ $f->periodo_servicio_inicio->format('d/m/Y') }} – {{ $f->periodo_servicio_fin?->format('d/m/Y') }}</dd>@endif
                        @if ($f->estado === 'ANULADA')<dt class="col-5 text-700">Anulada por</dt><dd class="col-7">{{ $f->anuladaPor?->nombre_completo ?? '—' }}<br><span class="text-600">{{ $f->fecha_anulacion?->format('d/m/Y H:i') }}</span></dd>@endif
                        @if ($f->notas)<dt class="col-5 text-700">Notas</dt><dd class="col-7" style="white-space: pre-line">{{ $f->notas }}</dd>@endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection

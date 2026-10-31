<x-print-layout
    :titulo="'Estado de cuenta — '.$c->razon_social"
    :empresa="$empresa"
    :volverUrl="route('clientes.show', $c->id_cliente)"
>
    <x-slot:encabezado>
        <h3 class="text-1100 fw-bolder mb-1">ESTADO DE CUENTA</h3>
        <p class="fs--1 text-800 mb-0">Fecha: <span class="fw-semi-bold">{{ now()->format('d/m/Y') }}</span></p>
    </x-slot:encabezado>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6">
            <h6 class="text-600 text-uppercase fs--2 mb-1">Cliente</h6>
            <h5 class="text-1100 mb-1">{{ $c->razon_social }}</h5>
            <p class="fs--1 text-800 mb-0">
                NIT: {{ $c->nit ?? '—' }}<br>
                {{ $c->direccion_fiscal }}<br>
                {{ $c->telefono_principal }} {{ $c->email_principal }}
            </p>
        </div>
        <div class="col-12 col-sm-6 text-sm-end">
            <h6 class="text-600 text-uppercase fs--2 mb-1">Condiciones</h6>
            <p class="fs--1 text-800 mb-2">
                Crédito: {{ $c->dias_credito }} días<br>
                Límite: {{ $c->limite_credito !== null ? $c->moneda_facturacion.' '.number_format((float) $c->limite_credito, 2) : 'Sin límite' }}
            </p>
            <h6 class="text-600 text-uppercase fs--2 mb-1">Saldo total</h6>
            <h4 class="text-1100 fw-bolder mb-0">{{ $c->moneda_facturacion }} {{ number_format($saldo, 2) }}</h4>
            @if ($vencido > 0)
                <p class="fs--1 text-danger fw-bold mb-0">Vencido: {{ number_format($vencido, 2) }}</p>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="table fs--1 text-900 mb-0 align-middle">
            <thead class="bg-200">
                <tr>
                    <th class="ps-3">Factura</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th class="text-end">Días vencida</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Pagado</th>
                    <th class="pe-3 text-end">Saldo</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($facturas as $f)
                @php $vencida = $f->fecha_vencimiento?->isPast(); @endphp
                <tr>
                    <td class="ps-3 fw-semi-bold">{{ $f->numero_completo }}</td>
                    <td>{{ $f->fecha_emision?->format('d/m/Y') }}</td>
                    <td class="{{ $vencida ? 'text-danger fw-semi-bold' : '' }}">{{ $f->fecha_vencimiento?->format('d/m/Y') }}</td>
                    <td class="text-end">{{ $vencida ? (int) $f->fecha_vencimiento->diffInDays(now()) : '' }}</td>
                    <td class="text-end">{{ number_format((float) $f->total, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $f->total_pagado, 2) }}</td>
                    <td class="pe-3 text-end fw-semi-bold">{{ number_format((float) $f->saldo_pendiente, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-700 py-4">Sin saldo pendiente.</td></tr>
            @endforelse
            </tbody>
            <tfoot class="bg-200">
                <tr class="fs-0">
                    <td colspan="6" class="ps-3 text-end fw-bolder text-1100">Total {{ $c->moneda_facturacion }}</td>
                    <td class="pe-3 text-end fw-bolder text-1100">{{ number_format($saldo, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-print-layout>

<x-print-layout
    :titulo="'Contrato '.$c->numero_contrato"
    :empresa="$empresa"
    :volverUrl="route('contratos.show', $c->id_contrato)"
>
    <x-slot:encabezado>
        <h3 class="text-1100 fw-bolder mb-1">CONTRATO DE SERVICIOS</h3>
        <p class="fs-0 fw-bold text-primary mb-1">{{ $c->numero_contrato }}</p>
        <p class="fs--1 text-800 mb-0">
            Vigencia: <span class="fw-semi-bold">{{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? 'indefinida' }}</span>
        </p>
    </x-slot:encabezado>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6">
            <h6 class="text-600 text-uppercase fs--2 mb-1">Cliente</h6>
            <h5 class="text-1100 mb-1">{{ $c->cliente?->razon_social }}</h5>
            <p class="fs--1 text-800 mb-0">
                NIT: {{ $c->cliente?->nit ?: '—' }}<br>
                {{ $c->cliente?->direccion_fiscal }}
            </p>
        </div>
        <div class="col-12 col-sm-6 text-sm-end">
            @if ($c->nombre_proyecto)
                <h6 class="text-600 text-uppercase fs--2 mb-1">Proyecto</h6>
                <p class="fs--1 text-900 fw-semi-bold mb-2">{{ $c->nombre_proyecto }}</p>
            @endif
            <h6 class="text-600 text-uppercase fs--2 mb-1">Facturación</h6>
            <p class="fs--1 text-900 fw-semi-bold mb-0">
                {{ $periodicidades[$c->periodicidad_factura] ?? $c->periodicidad_factura }}{{ $c->dia_facturacion ? ', día '.$c->dia_facturacion : '' }} · {{ $c->moneda }}
            </p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table fs--1 text-900 mb-0 align-middle">
            <thead class="bg-200">
                <tr>
                    <th class="ps-3" style="width: 48px;">#</th>
                    <th>Servicio</th>
                    <th>Sitio</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Precio</th>
                    <th class="text-end">Desc.</th>
                    <th class="pe-3 text-end">Mensual</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($c->detalles as $i => $d)
                <tr>
                    <td class="ps-3">{{ $i + 1 }}</td>
                    <td>
                        <span class="fw-semi-bold">{{ $d->tipoServicio?->nombre }}</span>
                        @if ($d->descripcion)<br><small class="text-600">{{ $d->descripcion }}</small>@endif
                    </td>
                    <td>{{ $d->sitio?->nombre }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $d->cantidad, 2), '0'), '.') }}</td>
                    <td class="text-end">{{ number_format((float) $d->precio_unitario, 2) }}</td>
                    <td class="text-end">{{ (float) $d->descuento_pct ? rtrim(rtrim(number_format((float) $d->descuento_pct, 2), '0'), '.').' %' : '' }}</td>
                    <td class="pe-3 text-end fw-semi-bold">{{ number_format((float) $d->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="bg-200">
                <tr class="fs-0">
                    <td colspan="6" class="ps-3 text-end fw-bolder text-1100">Valor mensual {{ $c->moneda }}</td>
                    <td class="pe-3 text-end fw-bolder text-1100">{{ number_format((float) $c->valor_mensual, 2) }}</td>
                </tr>
                @if ($c->valor_total_estimado !== null)
                    <tr>
                        <td colspan="6" class="ps-3 text-end fw-semi-bold">Valor total estimado</td>
                        <td class="pe-3 text-end fw-semi-bold">{{ number_format((float) $c->valor_total_estimado, 2) }}</td>
                    </tr>
                @endif
            </tfoot>
        </table>
    </div>

    <div class="row g-5 mt-5 pt-4 text-center fs--1">
        <div class="col-6">
            <div class="border-top border-300 pt-2">
                Por {{ $empresa?->nombre_comercial ?: \App\Support\Sistema::nombre() }}
            </div>
        </div>
        <div class="col-6">
            <div class="border-top border-300 pt-2">
                Por el cliente
            </div>
        </div>
    </div>
</x-print-layout>

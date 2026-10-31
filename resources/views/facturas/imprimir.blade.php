<x-print-layout
    :titulo="($tipos[$f->tipo] ?? '').' '.$f->numero_completo"
    :empresa="$empresa"
    :volverUrl="route('facturas.show', $f->id_factura)"
>
    <x-slot:encabezado>
        <h3 class="text-1100 fw-bolder mb-1">{{ mb_strtoupper($tipos[$f->tipo] ?? 'Documento') }}</h3>
        <p class="fs-0 fw-bold text-primary mb-1">{{ $f->numero_completo }}</p>
        <p class="fs--1 text-800 mb-0">
            Emisión: <span class="fw-semi-bold">{{ $f->fecha_emision?->format('d/m/Y') }}</span><br>
            Vencimiento: <span class="fw-semi-bold">{{ $f->fecha_vencimiento?->format('d/m/Y') }}</span>
        </p>
        @if ($f->estado === 'ANULADA')
            <span class="badge badge-phoenix badge-phoenix-danger fs--1 mt-2">ANULADA</span>
        @endif
    </x-slot:encabezado>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-7">
            <h6 class="text-600 text-uppercase fs--2 mb-1">Facturar a</h6>
            <h5 class="text-1100 mb-1">{{ $f->cliente?->razon_social }}</h5>
            <p class="fs--1 text-800 mb-0">
                NIT: <strong>{{ $f->cliente?->nit ?: 'CF' }}</strong><br>
                {{ $f->cliente?->direccion_fiscal }}
            </p>
        </div>
        <div class="col-12 col-sm-5 text-sm-end">
            @if ($f->periodo_servicio_inicio)
                <h6 class="text-600 text-uppercase fs--2 mb-1">Periodo del servicio</h6>
                <p class="fs--1 text-900 fw-semi-bold mb-2">
                    {{ $f->periodo_servicio_inicio->format('d/m/Y') }} – {{ $f->periodo_servicio_fin?->format('d/m/Y') }}
                </p>
            @endif
            <h6 class="text-600 text-uppercase fs--2 mb-1">Moneda</h6>
            <p class="fs--1 text-900 fw-bold mb-0">{{ $f->moneda }}</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table fs--1 text-900 mb-0 align-middle">
            <thead class="bg-200">
                <tr>
                    <th class="ps-3" style="width: 48px;">#</th>
                    <th>Descripción</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Precio</th>
                    <th class="text-end">Descuento</th>
                    <th class="pe-3 text-end">Importe</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($f->detalles as $i => $d)
                <tr>
                    <td class="ps-3">{{ $i + 1 }}</td>
                    <td class="fw-semi-bold">{{ $d->descripcion }}{{ $d->es_afecto_iva ? '' : ' (exento)' }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $d->cantidad, 2), '0'), '.') }}</td>
                    <td class="text-end">{{ number_format((float) $d->precio_unitario, 2) }}</td>
                    <td class="text-end">{{ (float) $d->descuento ? number_format((float) $d->descuento, 2) : '' }}</td>
                    <td class="pe-3 text-end fw-semi-bold">{{ number_format((float) $d->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="border-top border-300">
                <tr>
                    <td colspan="5" class="text-end fw-semi-bold">Subtotal</td>
                    <td class="pe-3 text-end">{{ number_format((float) $f->subtotal, 2) }}</td>
                </tr>
                @if ((float) $f->descuento)
                    <tr>
                        <td colspan="5" class="text-end fw-semi-bold">Descuento</td>
                        <td class="pe-3 text-end text-danger">−{{ number_format((float) $f->descuento, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="5" class="text-end fw-semi-bold">Base imponible</td>
                    <td class="pe-3 text-end">{{ number_format((float) $f->base_imponible, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end fw-semi-bold">IVA</td>
                    <td class="pe-3 text-end">{{ number_format((float) $f->iva, 2) }}</td>
                </tr>
                <tr class="bg-200 fs-0">
                    <td colspan="5" class="ps-3 text-end fw-bolder text-1100">Total {{ $f->moneda }}</td>
                    <td class="pe-3 text-end fw-bolder text-1100">{{ number_format((float) $f->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="fs--2 text-600 mt-4 mb-0 border-top border-200 pt-3">
        Representación interna del documento. {{ $f->uuid_fel ? 'Autorización FEL: '.$f->numero_autorizacion_fel.' · UUID '.$f->uuid_fel : 'Sin certificación FEL.' }}
    </p>
</x-print-layout>

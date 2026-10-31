<x-print-layout
    :titulo="'Orden de compra '.$oc->numero_oc"
    :empresa="$empresa"
    :volverUrl="route('ordenes-compra.show', $oc->id_oc)"
>
    <x-slot:encabezado>
        <h3 class="text-1100 fw-bolder mb-1">ORDEN DE COMPRA</h3>
        <p class="fs-0 fw-bold text-primary mb-1">{{ $oc->numero_oc }}</p>
        <p class="fs--1 text-800 mb-0">
            Emisión: <span class="fw-semi-bold">{{ $oc->fecha_emision?->format('d/m/Y') }}</span><br>
            Estado: <span class="fw-semi-bold">{{ $estados[$oc->estado][0] ?? $oc->estado }}</span>
        </p>
    </x-slot:encabezado>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6">
            <h6 class="text-600 text-uppercase fs--2 mb-1">Proveedor</h6>
            <h5 class="text-1100 mb-1">{{ $oc->proveedor?->razon_social }}</h5>
            <p class="fs--1 text-800 mb-0">
                NIT: {{ $oc->proveedor?->nit ?: '—' }}<br>
                {{ $oc->proveedor?->direccion }}<br>
                {{ $oc->proveedor?->telefono }} {{ $oc->proveedor?->email }}
            </p>
        </div>
        <div class="col-12 col-sm-6 text-sm-end">
            <h6 class="text-600 text-uppercase fs--2 mb-1">Entregar en</h6>
            <p class="fs--1 text-900 fw-semi-bold mb-2">
                {{ $oc->bodega?->nombre ?? 'Por definir' }}<br>
                <span class="fw-normal text-700">{{ $oc->bodega?->ubicacion }}</span>
            </p>
            <h6 class="text-600 text-uppercase fs--2 mb-1">Entrega esperada</h6>
            <p class="fs--1 text-900 fw-semi-bold mb-0">{{ $oc->fecha_entrega_esperada?->format('d/m/Y') ?? '—' }}</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table fs--1 text-900 mb-0 align-middle">
            <thead class="bg-200">
                <tr>
                    <th class="ps-3" style="width: 48px;">#</th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Precio</th>
                    <th class="text-end">Descuento</th>
                    <th class="pe-3 text-end">Importe</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($oc->detalles as $i => $d)
                <tr>
                    <td class="ps-3">{{ $i + 1 }}</td>
                    <td><code>{{ $d->producto?->codigo }}</code></td>
                    <td>
                        <span class="fw-semi-bold">{{ $d->producto?->nombre }}</span>
                        @if ($d->descripcion)<br><small class="text-600">{{ $d->descripcion }}</small>@endif
                    </td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $d->cantidad_pedida, 4), '0'), '.') }} {{ $d->producto?->unidad_medida }}</td>
                    <td class="text-end">{{ number_format((float) $d->precio_unitario, 2) }}</td>
                    <td class="text-end">{{ (float) $d->descuento ? number_format((float) $d->descuento, 2) : '' }}</td>
                    <td class="pe-3 text-end fw-semi-bold">{{ number_format((float) $d->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="border-top border-300">
                <tr>
                    <td colspan="6" class="text-end fw-semi-bold">Subtotal</td>
                    <td class="pe-3 text-end">{{ number_format((float) $oc->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end fw-semi-bold">IVA</td>
                    <td class="pe-3 text-end">{{ number_format((float) $oc->iva, 2) }}</td>
                </tr>
                <tr class="bg-200 fs-0">
                    <td colspan="6" class="ps-3 text-end fw-bolder text-1100">Total {{ $oc->moneda }}</td>
                    <td class="pe-3 text-end fw-bolder text-1100">{{ number_format((float) $oc->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($oc->notas)
        <p class="fs--1 text-800 mt-3 mb-0"><strong>Notas:</strong> {{ $oc->notas }}</p>
    @endif

    <div class="row g-5 mt-5 pt-4 text-center fs--1">
        <div class="col-6">
            <div class="border-top border-300 pt-2">
                Elaborado por<br><strong>{{ $oc->creadoPor?->nombre_completo }}</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top border-300 pt-2">
                Aprobado por<br><strong>{{ $oc->aprobadoPor?->nombre_completo }}</strong>
            </div>
        </div>
    </div>
</x-print-layout>

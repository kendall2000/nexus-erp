<x-print-layout
    :titulo="'Recepción '.$r->numero_recepcion"
    :empresa="$empresa"
    :volverUrl="route('recepciones.show', $r->id_recepcion)"
>
    <x-slot:encabezado>
        <h3 class="text-1100 fw-bolder mb-1">RECEPCIÓN DE MERCADERÍA</h3>
        <p class="fs-0 fw-bold text-primary mb-1">{{ $r->numero_recepcion }}</p>
        <p class="fs--1 text-800 mb-0">
            Fecha: <span class="fw-semi-bold">{{ $r->fecha_recepcion?->format('d/m/Y') }}</span><br>
            Orden de compra: <span class="fw-semi-bold">{{ $r->ordenCompra?->numero_oc }}</span>
        </p>
    </x-slot:encabezado>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6">
            <h6 class="text-600 text-uppercase fs--2 mb-1">Proveedor</h6>
            <h5 class="text-1100 mb-1">{{ $r->ordenCompra?->proveedor?->razon_social }}</h5>
            <p class="fs--1 text-800 mb-0">NIT: {{ $r->ordenCompra?->proveedor?->nit ?: '—' }}</p>
        </div>
        <div class="col-12 col-sm-6 text-sm-end">
            <h6 class="text-600 text-uppercase fs--2 mb-1">Recibido en</h6>
            <p class="fs--1 text-900 fw-semi-bold mb-0">
                {{ $r->bodega?->nombre }}<br>
                <span class="fw-normal text-700">{{ $r->bodega?->ubicacion }}</span>
            </p>
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
                    <th class="text-end">Costo unitario</th>
                    <th class="pe-3 text-end">Importe</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($r->detalles as $i => $d)
                <tr>
                    <td class="ps-3">{{ $i + 1 }}</td>
                    <td><code>{{ $d->producto?->codigo }}</code></td>
                    <td class="fw-semi-bold">{{ $d->producto?->nombre }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $d->cantidad_recibida, 4), '0'), '.') }} {{ $d->producto?->unidad_medida }}</td>
                    <td class="text-end">{{ number_format((float) $d->costo_unitario, 2) }}</td>
                    <td class="pe-3 text-end fw-semi-bold">{{ number_format((float) $d->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="bg-200">
                <tr class="fs-0">
                    <td colspan="5" class="ps-3 text-end fw-bolder text-1100">Valor recibido {{ $r->ordenCompra?->moneda }} (sin IVA)</td>
                    <td class="pe-3 text-end fw-bolder text-1100">{{ number_format((float) $r->detalles->sum('subtotal'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($r->notas)
        <p class="fs--1 text-800 mt-3 mb-0"><strong>Notas:</strong> {{ $r->notas }}</p>
    @endif

    <div class="row g-5 mt-5 pt-4 text-center fs--1">
        <div class="col-6">
            <div class="border-top border-300 pt-2">
                Recibido por<br><strong>{{ $r->creadoPor?->nombre_completo }}</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top border-300 pt-2">
                Entregado por (proveedor)
            </div>
        </div>
    </div>
</x-print-layout>

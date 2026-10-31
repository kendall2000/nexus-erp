<x-print-layout
    :titulo="'Recibo #'.$p->id_pago"
    :empresa="$empresa"
    :volverUrl="route('pagos.show', $p->id_pago)"
>
    <x-slot:encabezado>
        <h3 class="text-1100 fw-bolder mb-1">RECIBO DE CAJA</h3>
        <p class="fs-0 fw-bold text-primary mb-1">N.º {{ str_pad((string) $p->id_pago, 6, '0', STR_PAD_LEFT) }}</p>
        <p class="fs--1 text-800 mb-0">Fecha: <span class="fw-semi-bold">{{ $p->fecha_pago?->format('d/m/Y') }}</span></p>
        @if ($p->estado !== 'APLICADO')
            <span class="badge badge-phoenix badge-phoenix-danger fs--1 mt-2">{{ $p->estado === 'DEVUELTO' ? 'DEVUELTO' : 'REVERTIDO' }}</span>
        @endif
    </x-slot:encabezado>

    <div class="table-responsive">
        <table class="table fs--1 text-900 mb-0 align-middle">
            <tbody>
                <tr>
                    <th class="text-700" style="width: 35%;">Recibimos de</th>
                    <td><strong>{{ $p->cliente?->razon_social }}</strong> · NIT {{ $p->cliente?->nit ?: 'CF' }}</td>
                </tr>
                <tr>
                    <th class="text-700">La cantidad de</th>
                    <td class="fs-2 fw-bolder text-1100">{{ $p->moneda }} {{ number_format((float) $p->monto, 2) }}</td>
                </tr>
                <tr>
                    <th class="text-700">Por concepto de</th>
                    <td>Abono a {{ $p->factura?->numero_completo ? 'la factura '.$p->factura->numero_completo : 'cuenta' }}</td>
                </tr>
                <tr>
                    <th class="text-700">Forma de pago</th>
                    <td>{{ $formas[$p->forma_pago] ?? $p->forma_pago }}{{ $p->referencia ? ' · ref. '.$p->referencia : '' }}{{ $p->banco_origen ? ' · '.$p->banco_origen : '' }}</td>
                </tr>
                @if ($p->factura)
                    <tr>
                        <th class="text-700">Saldo de la factura</th>
                        <td class="fw-semi-bold">{{ $p->factura->moneda }} {{ number_format((float) $p->factura->saldo_pendiente, 2) }}</td>
                    </tr>
                @endif
                @if ($p->notas)
                    <tr>
                        <th class="text-700">Notas</th>
                        <td>{{ $p->notas }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="row g-5 mt-5 pt-4 text-center fs--1">
        <div class="col-6">
            <div class="border-top border-300 pt-2">
                Recibido por<br><strong>{{ $p->creadoPor?->nombre_completo }}</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top border-300 pt-2">
                Cliente
            </div>
        </div>
    </div>
</x-print-layout>

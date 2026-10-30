<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recepción {{ $r->numero_recepcion }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 24px; }
        h1 { font-size: 20px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #ddd; padding: 6px 4px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
        .der { text-align: right; }
        .encabezado { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 8px; }
        .caja { display: flex; gap: 24px; margin-top: 12px; }
        .caja div { flex: 1; }
        .etiqueta { color: #666; font-size: 10px; text-transform: uppercase; }
        .firmas { display: flex; gap: 48px; margin-top: 64px; }
        .firmas div { flex: 1; border-top: 1px solid #222; padding-top: 4px; text-align: center; }
        @media print { .no-imprimir { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <p class="no-imprimir"><button onclick="window.print()">Imprimir</button></p>
    <div class="encabezado">
        <div>
            @if (\App\Support\Sistema::config()->imgLogo)<img src="{{ \App\Support\Sistema::config()->imgLogo }}" alt="" style="max-height: 50px"><br>@endif
            <strong>{{ $empresa?->nombre_legal ?: ($empresa?->nombre_comercial ?: \App\Support\Sistema::nombre()) }}</strong><br>
            @if ($empresa?->nit) NIT: {{ $empresa->nit }}<br>@endif
        </div>
        <div class="der">
            <h1>RECEPCIÓN DE MERCADERÍA</h1>
            <strong>{{ $r->numero_recepcion }}</strong><br>
            Fecha: {{ $r->fecha_recepcion?->format('d/m/Y') }}<br>
            Orden de compra: {{ $r->ordenCompra?->numero_oc }}
        </div>
    </div>
    <div class="caja">
        <div>
            <div class="etiqueta">Proveedor</div>
            <strong>{{ $r->ordenCompra?->proveedor?->razon_social }}</strong><br>
            NIT: {{ $r->ordenCompra?->proveedor?->nit ?: '—' }}
        </div>
        <div>
            <div class="etiqueta">Recibido en</div>
            {{ $r->bodega?->nombre }}<br>
            {{ $r->bodega?->ubicacion }}
        </div>
    </div>
    <table>
        <thead><tr><th>#</th><th>Código</th><th>Descripción</th><th class="der">Cantidad</th><th class="der">Costo unitario</th><th class="der">Importe</th></tr></thead>
        <tbody>
        @foreach ($r->detalles as $i => $d)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $d->producto?->codigo }}</td>
                <td>{{ $d->producto?->nombre }}</td>
                <td class="der">{{ rtrim(rtrim(number_format((float) $d->cantidad_recibida, 4), '0'), '.') }} {{ $d->producto?->unidad_medida }}</td>
                <td class="der">{{ number_format((float) $d->costo_unitario, 2) }}</td>
                <td class="der">{{ number_format((float) $d->subtotal, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="der"><strong>Valor recibido {{ $r->ordenCompra?->moneda }} (sin IVA)</strong></td><td class="der"><strong>{{ number_format((float) $r->detalles->sum('subtotal'), 2) }}</strong></td></tr>
        </tfoot>
    </table>
    @if ($r->notas)<p><strong>Notas:</strong> {{ $r->notas }}</p>@endif
    <div class="firmas">
        <div>Recibido por<br>{{ $r->creadoPor?->nombre_completo }}</div>
        <div>Entregado por (proveedor)</div>
    </div>
</body>
</html>

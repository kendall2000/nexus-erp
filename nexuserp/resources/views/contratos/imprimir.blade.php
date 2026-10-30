<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Contrato {{ $c->numero_contrato }}</title>
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
        .firmas { display: flex; gap: 48px; margin-top: 72px; }
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
            <h1>CONTRATO DE SERVICIOS</h1>
            <strong>{{ $c->numero_contrato }}</strong><br>
            Vigencia: {{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? 'indefinida' }}
        </div>
    </div>
    <div class="caja">
        <div>
            <div class="etiqueta">Cliente</div>
            <strong>{{ $c->cliente?->razon_social }}</strong><br>
            NIT: {{ $c->cliente?->nit ?: '—' }}<br>
            {{ $c->cliente?->direccion_fiscal }}
        </div>
        <div>
            @if ($c->nombre_proyecto)<div class="etiqueta">Proyecto</div>{{ $c->nombre_proyecto }}<br>@endif
            <div class="etiqueta" style="margin-top: 6px">Facturación</div>
            {{ $periodicidades[$c->periodicidad_factura] ?? $c->periodicidad_factura }}{{ $c->dia_facturacion ? ', día '.$c->dia_facturacion : '' }} · {{ $c->moneda }}
        </div>
    </div>
    <table>
        <thead><tr><th>#</th><th>Servicio</th><th>Sitio</th><th class="der">Cantidad</th><th class="der">Precio</th><th class="der">Desc.</th><th class="der">Mensual</th></tr></thead>
        <tbody>
        @foreach ($c->detalles as $i => $d)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $d->tipoServicio?->nombre }}@if ($d->descripcion)<br><small>{{ $d->descripcion }}</small>@endif</td>
                <td>{{ $d->sitio?->nombre }}</td>
                <td class="der">{{ rtrim(rtrim(number_format((float) $d->cantidad, 2), '0'), '.') }}</td>
                <td class="der">{{ number_format((float) $d->precio_unitario, 2) }}</td>
                <td class="der">{{ (float) $d->descuento_pct ? rtrim(rtrim(number_format((float) $d->descuento_pct, 2), '0'), '.').' %' : '' }}</td>
                <td class="der">{{ number_format((float) $d->subtotal, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="6" class="der"><strong>Valor mensual {{ $c->moneda }}</strong></td><td class="der"><strong>{{ number_format((float) $c->valor_mensual, 2) }}</strong></td></tr>
            @if ($c->valor_total_estimado !== null)<tr><td colspan="6" class="der">Valor total estimado</td><td class="der">{{ number_format((float) $c->valor_total_estimado, 2) }}</td></tr>@endif
        </tfoot>
    </table>
    <div class="firmas">
        <div>Por {{ $empresa?->nombre_comercial ?: \App\Support\Sistema::nombre() }}</div>
        <div>Por el cliente</div>
    </div>
</body>
</html>

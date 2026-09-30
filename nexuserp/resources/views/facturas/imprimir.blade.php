<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $tipos[$f->tipo] ?? '' }} {{ $f->numero_completo }}</title>
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
        .sello { border: 2px solid #b91c1c; color: #b91c1c; display: inline-block; padding: 4px 12px; font-weight: bold; margin-top: 8px; }
        .aviso { margin-top: 24px; font-size: 10px; color: #666; }
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
            <h1>{{ mb_strtoupper($tipos[$f->tipo] ?? 'Documento') }}</h1>
            <strong>{{ $f->numero_completo }}</strong><br>
            Emisión: {{ $f->fecha_emision?->format('d/m/Y') }}<br>
            Vencimiento: {{ $f->fecha_vencimiento?->format('d/m/Y') }}
            @if ($f->estado === 'ANULADA')<br><span class="sello">ANULADA</span>@endif
        </div>
    </div>
    <div class="caja">
        <div>
            <div class="etiqueta">Cliente</div>
            <strong>{{ $f->cliente?->razon_social }}</strong><br>
            NIT: {{ $f->cliente?->nit ?: 'CF' }}<br>
            {{ $f->cliente?->direccion_fiscal }}
        </div>
        <div>
            @if ($f->periodo_servicio_inicio)
                <div class="etiqueta">Periodo del servicio</div>
                {{ $f->periodo_servicio_inicio->format('d/m/Y') }} – {{ $f->periodo_servicio_fin?->format('d/m/Y') }}<br>
            @endif
            <div class="etiqueta" style="margin-top: 6px">Moneda</div>
            {{ $f->moneda }}
        </div>
    </div>
    <table>
        <thead><tr><th>#</th><th>Descripción</th><th class="der">Cantidad</th><th class="der">Precio</th><th class="der">Descuento</th><th class="der">Importe</th></tr></thead>
        <tbody>
        @foreach ($f->detalles as $i => $d)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $d->descripcion }}{{ $d->es_afecto_iva ? '' : ' (exento)' }}</td>
                <td class="der">{{ rtrim(rtrim(number_format((float) $d->cantidad, 2), '0'), '.') }}</td>
                <td class="der">{{ number_format((float) $d->precio_unitario, 2) }}</td>
                <td class="der">{{ (float) $d->descuento ? number_format((float) $d->descuento, 2) : '' }}</td>
                <td class="der">{{ number_format((float) $d->subtotal, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="der">Subtotal</td><td class="der">{{ number_format((float) $f->subtotal, 2) }}</td></tr>
            @if ((float) $f->descuento)<tr><td colspan="5" class="der">Descuento</td><td class="der">−{{ number_format((float) $f->descuento, 2) }}</td></tr>@endif
            <tr><td colspan="5" class="der">Base imponible</td><td class="der">{{ number_format((float) $f->base_imponible, 2) }}</td></tr>
            <tr><td colspan="5" class="der">IVA</td><td class="der">{{ number_format((float) $f->iva, 2) }}</td></tr>
            <tr><td colspan="5" class="der"><strong>Total {{ $f->moneda }}</strong></td><td class="der"><strong>{{ number_format((float) $f->total, 2) }}</strong></td></tr>
        </tfoot>
    </table>
    <p class="aviso">Representación interna del documento. {{ $f->uuid_fel ? 'Autorización FEL: '.$f->numero_autorizacion_fel.' · UUID '.$f->uuid_fel : 'Sin certificación FEL.' }}</p>
</body>
</html>

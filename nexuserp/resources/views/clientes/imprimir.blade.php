<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta — {{ $c->razon_social }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 24px; }
        h1 { font-size: 20px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #ddd; padding: 6px 4px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
        .der { text-align: right; }
        .rojo { color: #b91c1c; }
        .encabezado { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 8px; }
        .caja { display: flex; gap: 24px; margin-top: 12px; }
        .caja div { flex: 1; }
        .etiqueta { color: #666; font-size: 10px; text-transform: uppercase; }
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
            <h1>ESTADO DE CUENTA</h1>
            Fecha: {{ now()->format('d/m/Y') }}
        </div>
    </div>
    <div class="caja">
        <div>
            <div class="etiqueta">Cliente</div>
            <strong>{{ $c->razon_social }}</strong><br>
            NIT: {{ $c->nit ?? '—' }}<br>
            {{ $c->direccion_fiscal }}<br>
            {{ $c->telefono_principal }} {{ $c->email_principal }}
        </div>
        <div>
            <div class="etiqueta">Condiciones</div>
            Crédito: {{ $c->dias_credito }} días<br>
            Límite: {{ $c->limite_credito !== null ? $c->moneda_facturacion.' '.number_format((float) $c->limite_credito, 2) : 'Sin límite' }}<br>
            <div class="etiqueta" style="margin-top: 6px">Saldo total</div>
            <strong>{{ $c->moneda_facturacion }} {{ number_format($saldo, 2) }}</strong>
            @if ($vencido > 0)<br><span class="rojo">Vencido: {{ number_format($vencido, 2) }}</span>@endif
        </div>
    </div>
    <table>
        <thead><tr><th>Factura</th><th>Emisión</th><th>Vencimiento</th><th class="der">Días vencida</th><th class="der">Total</th><th class="der">Pagado</th><th class="der">Saldo</th></tr></thead>
        <tbody>
        @forelse ($facturas as $f)
            @php $vencida = $f->fecha_vencimiento?->isPast(); @endphp
            <tr>
                <td>{{ $f->numero_completo }}</td>
                <td>{{ $f->fecha_emision?->format('d/m/Y') }}</td>
                <td class="{{ $vencida ? 'rojo' : '' }}">{{ $f->fecha_vencimiento?->format('d/m/Y') }}</td>
                <td class="der">{{ $vencida ? (int) $f->fecha_vencimiento->diffInDays(now()) : '' }}</td>
                <td class="der">{{ number_format((float) $f->total, 2) }}</td>
                <td class="der">{{ number_format((float) $f->total_pagado, 2) }}</td>
                <td class="der">{{ number_format((float) $f->saldo_pendiente, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7">Sin saldo pendiente.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="6" class="der"><strong>Total {{ $c->moneda_facturacion }}</strong></td><td class="der"><strong>{{ number_format($saldo, 2) }}</strong></td></tr>
        </tfoot>
    </table>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo #{{ $p->id_pago }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 24px; max-width: 720px; }
        h1 { font-size: 20px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #ddd; padding: 6px 4px; text-align: left; vertical-align: top; }
        th { width: 35%; color: #666; font-weight: normal; }
        .der { text-align: right; }
        .monto { font-size: 22px; font-weight: bold; }
        .encabezado { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 8px; }
        .sello { border: 2px solid #b91c1c; color: #b91c1c; display: inline-block; padding: 4px 12px; font-weight: bold; margin-top: 8px; }
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
            <h1>RECIBO DE CAJA</h1>
            <strong>N.º {{ str_pad((string) $p->id_pago, 6, '0', STR_PAD_LEFT) }}</strong><br>
            Fecha: {{ $p->fecha_pago?->format('d/m/Y') }}
            @if ($p->estado !== 'APLICADO')<br><span class="sello">{{ $p->estado === 'DEVUELTO' ? 'DEVUELTO' : 'REVERTIDO' }}</span>@endif
        </div>
    </div>
    <table>
        <tr><th>Recibimos de</th><td><strong>{{ $p->cliente?->razon_social }}</strong> · NIT {{ $p->cliente?->nit ?: 'CF' }}</td></tr>
        <tr><th>La cantidad de</th><td class="monto">{{ $p->moneda }} {{ number_format((float) $p->monto, 2) }}</td></tr>
        <tr><th>Por concepto de</th><td>Abono a {{ $p->factura?->numero_completo ? 'la factura '.$p->factura->numero_completo : 'cuenta' }}</td></tr>
        <tr><th>Forma de pago</th><td>{{ $formas[$p->forma_pago] ?? $p->forma_pago }}{{ $p->referencia ? ' · ref. '.$p->referencia : '' }}{{ $p->banco_origen ? ' · '.$p->banco_origen : '' }}</td></tr>
        @if ($p->factura)<tr><th>Saldo de la factura</th><td>{{ $p->factura->moneda }} {{ number_format((float) $p->factura->saldo_pendiente, 2) }}</td></tr>@endif
        @if ($p->notas)<tr><th>Notas</th><td>{{ $p->notas }}</td></tr>@endif
    </table>
    <div class="firmas">
        <div>Recibido por<br>{{ $p->creadoPor?->nombre_completo }}</div>
        <div>Cliente</div>
    </div>
</body>
</html>

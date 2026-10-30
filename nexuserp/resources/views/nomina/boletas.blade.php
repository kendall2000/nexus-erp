<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Boletas — {{ $p->nombre }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 24px; }
        .boleta { border: 1px solid #999; padding: 12px 16px; margin-bottom: 18px; page-break-inside: avoid; }
        .encabezado { display: flex; justify-content: space-between; border-bottom: 1px solid #222; padding-bottom: 6px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 4px; vertical-align: top; }
        .der { text-align: right; }
        .total td { border-top: 1px solid #222; font-weight: bold; }
        .columnas { display: flex; gap: 24px; }
        .columnas > div { flex: 1; }
        .firmas { display: flex; gap: 48px; margin-top: 36px; }
        .firmas div { flex: 1; border-top: 1px solid #222; padding-top: 4px; text-align: center; font-size: 11px; }
        @media print { .no-imprimir { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <p class="no-imprimir"><button onclick="window.print()">Imprimir</button></p>
    @foreach ($detalles as $d)
        @php
            $ingresos = $d->conceptos->where('tipo', 'INGRESO');
            $deducciones = $d->conceptos->where('tipo', 'DEDUCCION');
        @endphp
        <div class="boleta">
            <div class="encabezado">
                <div>
                    <strong>{{ $empresa?->nombre_legal ?: ($empresa?->nombre_comercial ?: \App\Support\Sistema::nombre()) }}</strong><br>
                    BOLETA DE PAGO · {{ $p->nombre }}
                </div>
                <div class="der">
                    {{ $d->empleado?->nombre_completo }}<br>
                    {{ $d->empleado?->codigo_empleado }} · {{ $d->cargo_snapshot }}<br>
                    {{ $p->fecha_inicio?->format('d/m/Y') }} – {{ $p->fecha_fin?->format('d/m/Y') }} · {{ rtrim(rtrim(number_format((float) $d->dias_trabajados, 2), '0'), '.') }} días
                </div>
            </div>
            <div class="columnas">
                <div>
                    <table>
                        <tr><td colspan="2"><strong>Ingresos</strong></td></tr>
                        @foreach ($ingresos as $l)<tr><td>{{ $l->concepto?->nombre }} {{ $l->descripcion ? '('.$l->descripcion.')' : '' }}</td><td class="der">{{ number_format((float) $l->monto, 2) }}</td></tr>@endforeach
                        <tr class="total"><td>Total ingresos</td><td class="der">{{ number_format((float) $d->total_ingresos, 2) }}</td></tr>
                    </table>
                </div>
                <div>
                    <table>
                        <tr><td colspan="2"><strong>Deducciones</strong></td></tr>
                        @foreach ($deducciones as $l)<tr><td>{{ $l->concepto?->nombre }} {{ $l->descripcion ? '('.$l->descripcion.')' : '' }}</td><td class="der">{{ number_format((float) $l->monto, 2) }}</td></tr>@endforeach
                        <tr class="total"><td>Total deducciones</td><td class="der">{{ number_format((float) $d->total_deducciones, 2) }}</td></tr>
                    </table>
                </div>
            </div>
            <p class="der" style="font-size: 14px; margin: 8px 0 0"><strong>Líquido a recibir: {{ $p->moneda }} {{ number_format((float) $d->liquido_pagar, 2) }}</strong></p>
            <div class="firmas"><div>Recibí conforme: {{ $d->empleado?->nombre_completo }}</div><div>Recursos humanos</div></div>
        </div>
    @endforeach
</body>
</html>

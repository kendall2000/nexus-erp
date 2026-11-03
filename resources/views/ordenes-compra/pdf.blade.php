{{-- PDF de la orden de compra (dompdf). Sin flexbox ni grid: dompdf solo maqueta bien con tablas. --}}
@php
    $nombreEmpresa = $empresa?->nombre_legal ?: ($empresa?->nombre_comercial ?: \App\Support\Sistema::nombre());
    $estado = $estados[$oc->estado][0] ?? $oc->estado;
    // Una orden en borrador o cancelada no es válida para el proveedor: se marca con una marca de agua.
    $marcaAgua = ['BORRADOR' => 'BORRADOR', 'CANCELADA' => 'CANCELADA'][$oc->estado] ?? null;
    $cantidad = fn ($n) => rtrim(rtrim(number_format((float) $n, 4), '0'), '.');
    $dinero = fn ($n) => number_format((float) $n, 2);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de compra {{ $oc->numero_oc }}</title>
    <style>
        @page { margin: 34px 36px 60px 36px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1f2937; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .texto-derecha { text-align: right; }
        .texto-centro { text-align: center; }
        .gris { color: #6b7280; }
        .negrita { font-weight: bold; }

        .franja { height: 5px; background: {{ $color }}; margin-bottom: 16px; }
        .encabezado td { padding: 0; }
        .logo { max-height: 58px; max-width: 190px; margin-bottom: 6px; }
        .empresa-nombre { font-size: 14px; font-weight: bold; color: #111827; margin: 0 0 3px; }
        .empresa-dato { font-size: 8.5px; color: #4b5563; line-height: 1.45; }
        .doc-titulo { font-size: 18px; font-weight: bold; color: {{ $color }}; letter-spacing: 1px; margin: 0; }
        .doc-numero { font-size: 12px; font-weight: bold; color: #111827; margin: 2px 0 8px; }
        .doc-meta td { padding: 2px 0; font-size: 8.5px; }
        .doc-meta td.etiqueta { color: #6b7280; padding-right: 8px; text-align: right; }
        .doc-meta td.valor { text-align: right; font-weight: bold; white-space: nowrap; }
        .estado { display: inline-block; white-space: nowrap; padding: 2px 7px; border-radius: 3px; font-size: 8px; font-weight: bold; border: 1px solid {{ $color }}; color: {{ $color }}; }

        .cajas { margin-top: 18px; }
        .caja { border: 1px solid #e5e7eb; border-radius: 4px; padding: 9px 11px; }
        .caja-titulo { font-size: 7.5px; font-weight: bold; color: {{ $color }}; text-transform: uppercase; letter-spacing: .6px; margin: 0 0 5px; }
        .caja-principal { font-size: 11px; font-weight: bold; color: #111827; margin: 0 0 3px; }
        .caja-dato { font-size: 8.5px; color: #4b5563; line-height: 1.5; }

        .lineas { margin-top: 18px; }
        .lineas th { background: {{ $color }}; color: #ffffff; font-size: 8px; text-transform: uppercase; letter-spacing: .4px; padding: 7px 6px; text-align: left; }
        .lineas td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; }
        .lineas tr.par td { background: #f9fafb; }
        .lineas .codigo { font-family: 'DejaVu Sans Mono', monospace; font-size: 8px; color: #374151; }
        .lineas .detalle { font-size: 8px; color: #6b7280; }

        .resumen { margin-top: 14px; }
        .totales td { padding: 5px 8px; font-size: 9.5px; }
        .totales td.etiqueta { color: #4b5563; text-align: right; }
        .totales td.valor { text-align: right; width: 110px; }
        .totales tr.total td { background: {{ $color }}; color: #ffffff; font-weight: bold; font-size: 11.5px; }
        .notas { border-left: 3px solid {{ $color }}; background: #f9fafb; padding: 8px 10px; font-size: 8.5px; color: #374151; line-height: 1.5; }

        .firmas { margin-top: 60px; page-break-inside: avoid; }
        .resumen, .totales, .notas { page-break-inside: avoid; }
        .firmas td { width: 33.33%; padding: 0 14px; text-align: center; font-size: 8.5px; }
        .firma-linea { border-top: 1px solid #9ca3af; padding-top: 5px; }

        .pie { position: fixed; bottom: -40px; left: 0; right: 0; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 7.5px; color: #9ca3af; }

        .marca-agua { position: fixed; top: 300px; left: 0; right: 0; text-align: center; font-size: 92px; font-weight: bold; color: #ef4444; opacity: .09; transform: rotate(-30deg); }
    </style>
</head>
<body>
    @if ($marcaAgua)
        <div class="marca-agua">{{ $marcaAgua }}</div>
    @endif

    <div class="pie">
        <table>
            <tr>
                <td>{{ $nombreEmpresa }} · Orden de compra {{ $oc->numero_oc }} · Generado el {{ now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="franja"></div>

    <table class="encabezado">
        <tr>
            <td style="width: 58%;">
                @if ($logo)
                    <img class="logo" src="{{ $logo }}" alt="">
                @endif
                <p class="empresa-nombre">{{ $nombreEmpresa }}</p>
                <div class="empresa-dato">
                    @if ($empresa?->nombre_comercial && $empresa->nombre_comercial !== $nombreEmpresa){{ $empresa->nombre_comercial }}<br>@endif
                    @if ($empresa?->nit)NIT: {{ $empresa->nit }}<br>@endif
                    @if ($empresa?->direccion){{ $empresa->direccion }}<br>@endif
                    {{ collect([$empresa?->telefono, $empresa?->email_corporativo])->filter()->implode(' · ') }}
                </div>
            </td>
            <td class="texto-derecha">
                <p class="doc-titulo">ORDEN DE COMPRA</p>
                <p class="doc-numero">No. {{ $oc->numero_oc }}</p>
                <table class="doc-meta" style="width: auto; margin-left: auto;">
                    <tr><td class="etiqueta">Fecha de emisión</td><td class="valor">{{ $oc->fecha_emision?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="etiqueta">Entrega esperada</td><td class="valor">{{ $oc->fecha_entrega_esperada?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><td class="etiqueta">Moneda</td><td class="valor">{{ $oc->moneda }}</td></tr>
                    <tr><td class="etiqueta">Estado</td><td class="valor"><span class="estado">{{ mb_strtoupper($estado) }}</span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="cajas">
        <tr>
            <td style="width: 50%; padding-right: 7px;">
                <div class="caja">
                    <p class="caja-titulo">Proveedor</p>
                    <p class="caja-principal">{{ $oc->proveedor?->razon_social ?? '—' }}</p>
                    <div class="caja-dato">
                        NIT: {{ $oc->proveedor?->nit ?: '—' }}<br>
                        @if ($oc->proveedor?->direccion){{ $oc->proveedor->direccion }}<br>@endif
                        {{ collect([$oc->proveedor?->telefono, $oc->proveedor?->email])->filter()->implode(' · ') }}
                    </div>
                </div>
            </td>
            <td style="width: 50%; padding-left: 7px;">
                <div class="caja">
                    <p class="caja-titulo">Entregar en</p>
                    <p class="caja-principal">{{ $oc->bodega?->nombre ?? 'Por definir' }}</p>
                    <div class="caja-dato">
                        @if ($oc->bodega?->ubicacion){{ $oc->bodega->ubicacion }}<br>@endif
                        Fecha de entrega: {{ $oc->fecha_entrega_esperada?->format('d/m/Y') ?? 'Por definir' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="lineas">
        <thead>
            <tr>
                <th style="width: 22px;">#</th>
                <th style="width: 70px;">Código</th>
                <th>Descripción</th>
                <th class="texto-derecha" style="width: 70px;">Cantidad</th>
                <th class="texto-derecha" style="width: 72px;">Precio unit.</th>
                <th class="texto-derecha" style="width: 62px;">Descuento</th>
                <th class="texto-derecha" style="width: 80px;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($oc->detalles as $i => $d)
                <tr class="{{ $i % 2 ? 'par' : '' }}">
                    <td class="gris">{{ $i + 1 }}</td>
                    <td class="codigo">{{ $d->producto?->codigo }}</td>
                    <td>
                        <span class="negrita">{{ $d->producto?->nombre }}</span>
                        @if ($d->descripcion)<br><span class="detalle">{{ $d->descripcion }}</span>@endif
                    </td>
                    <td class="texto-derecha">{{ $cantidad($d->cantidad_pedida) }} <span class="gris">{{ $d->producto?->unidad_medida }}</span></td>
                    <td class="texto-derecha">{{ $dinero($d->precio_unitario) }}</td>
                    <td class="texto-derecha">{{ (float) $d->descuento ? $dinero($d->descuento) : '—' }}</td>
                    <td class="texto-derecha negrita">{{ $dinero($d->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="resumen">
        <tr>
            <td style="width: 58%; padding-right: 14px;">
                @if ($oc->notas)
                    <div class="notas"><span class="negrita">Notas y condiciones:</span><br>{{ $oc->notas }}</div>
                @endif
            </td>
            <td>
                <table class="totales">
                    <tr><td class="etiqueta">Subtotal</td><td class="valor">{{ $oc->moneda }} {{ $dinero($oc->subtotal) }}</td></tr>
                    <tr><td class="etiqueta">IVA</td><td class="valor">{{ $oc->moneda }} {{ $dinero($oc->iva) }}</td></tr>
                    <tr class="total"><td class="texto-derecha">TOTAL</td><td class="valor">{{ $oc->moneda }} {{ $dinero($oc->total) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="firmas">
        <tr>
            <td><div class="firma-linea">Elaborado por<br><span class="negrita">{{ $oc->creadoPor?->nombre_completo ?? '—' }}</span></div></td>
            <td><div class="firma-linea">Aprobado por<br><span class="negrita">{{ $oc->aprobadoPor?->nombre_completo ?? '—' }}</span></div></td>
            <td><div class="firma-linea">Recibido por el proveedor<br><span class="gris">Nombre, firma y fecha</span></div></td>
        </tr>
    </table>
</body>
</html>

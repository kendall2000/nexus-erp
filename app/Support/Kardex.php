<?php

namespace App\Support;

use App\Models\Inventario\MovimientoInventario;
use App\Models\Inventario\StockBodega;
use Illuminate\Validation\ValidationException;

/**
 * Movimientos manuales del kardex (el de compras lo registra RecepcionController).
 *
 * La existencia se actualiza en el hook de MovimientoInventario (ENTRADA suma y promedia el
 * costo; SALIDA y BAJA restan). Aquí se bloquea la fila de stock_bodega antes de mover para que
 * dos salidas simultáneas no dejen la existencia en negativo. Debe llamarse dentro de una transacción.
 */
class Kardex
{
    /** Tipos manuales: código => [etiqueta, tipo_movimiento, referencia_tipo, ¿suma?]. */
    public const MANUALES = [
        'AJUSTE_ENTRADA' => ['Entrada por ajuste', 'ENTRADA', 'AJUSTE', true],
        'SALIDA' => ['Salida / consumo', 'SALIDA', 'OTRO', false],
        'BAJA' => ['Baja por merma o daño', 'BAJA', 'AJUSTE', false],
        'TRASLADO' => ['Traslado entre bodegas', 'SALIDA', 'TRASLADO', false],
    ];

    /** Etiqueta de cada movimiento según tipo y referencia (para listados). */
    public static function etiqueta(MovimientoInventario $m): string
    {
        return match (true) {
            $m->referencia_tipo === 'COMPRA' => 'Compra (recepción)',
            $m->referencia_tipo === 'TRASLADO' => $m->tipo_movimiento === 'ENTRADA' ? 'Traslado recibido' : 'Traslado enviado',
            $m->tipo_movimiento === 'ENTRADA' => 'Entrada por ajuste',
            $m->tipo_movimiento === 'BAJA' => 'Baja',
            $m->tipo_movimiento === 'SALIDA' => 'Salida',
            default => ucfirst(strtolower($m->tipo_movimiento)),
        };
    }

    /** Cantidad con signo: positiva si entra, negativa si sale. */
    public static function conSigno(MovimientoInventario $m): float
    {
        return $m->esEntrada() ? (float) $m->cantidad : ($m->esSalida() ? -(float) $m->cantidad : 0.0);
    }

    /**
     * Registra un movimiento manual. Para TRASLADO crea la salida en el origen y la entrada en
     * el destino, al costo promedio del origen.
     *
     * @param  array{tipo: string, id_producto: int, id_bodega: int, id_bodega_destino?: ?int, cantidad: float,
     *               costo_unitario?: ?float, numero_lote?: ?string, fecha_vencimiento?: ?string, motivo: string}  $d
     * @return list<MovimientoInventario>
     */
    public static function registrar(int $idEmpresa, int $idUsuario, string $moneda, array $d): array
    {
        [, $tipoMovimiento, $referencia, $suma] = self::MANUALES[$d['tipo']];
        $origen = self::stockBloqueado($d['id_producto'], $d['id_bodega']);
        $cantidad = round((float) $d['cantidad'], 4);
        $costoActual = (float) $origen->costo_promedio;

        if (! $suma && $cantidad > (float) $origen->cantidad_actual + 0.00001) {
            throw ValidationException::withMessages(['cantidad' => 'No hay suficiente existencia en la bodega: disponible '
                .rtrim(rtrim(number_format((float) $origen->cantidad_actual, 4), '0'), '.').'.']);
        }

        $base = [
            'id_empresa' => $idEmpresa, 'id_producto' => $d['id_producto'], 'moneda' => $moneda,
            'numero_lote' => $d['numero_lote'] ?? null, 'fecha_vencimiento' => $d['fecha_vencimiento'] ?? null,
            'observaciones' => mb_substr($d['motivo'], 0, 300), 'created_by' => $idUsuario,
        ];
        $costo = $suma ? round((float) ($d['costo_unitario'] ?? $costoActual), 4) : $costoActual;

        $movimiento = MovimientoInventario::create($base + [
            'id_bodega' => $d['id_bodega'], 'tipo_movimiento' => $tipoMovimiento, 'referencia_tipo' => $referencia,
            'cantidad' => $cantidad, 'costo_unitario' => $costo, 'costo_total' => round($cantidad * $costo, 4),
        ]);
        if ($d['tipo'] !== 'TRASLADO') {
            return [$movimiento];
        }

        // La entrada del traslado se enlaza con la salida por referencia_id.
        $movimiento->update(['referencia_id' => $movimiento->id_movimiento]);
        self::stockBloqueado($d['id_producto'], $d['id_bodega_destino']);
        $entrada = MovimientoInventario::create($base + [
            'id_bodega' => $d['id_bodega_destino'], 'tipo_movimiento' => 'ENTRADA', 'referencia_tipo' => 'TRASLADO',
            'referencia_id' => $movimiento->id_movimiento, 'cantidad' => $cantidad, 'costo_unitario' => $costo, 'costo_total' => round($cantidad * $costo, 4),
        ]);

        return [$movimiento, $entrada];
    }

    private static function stockBloqueado(int $idProducto, int $idBodega): StockBodega
    {
        StockBodega::query()->firstOrCreate(['id_producto' => $idProducto, 'id_bodega' => $idBodega], ['cantidad_actual' => 0, 'costo_promedio' => 0]);

        return StockBodega::query()->where(['id_producto' => $idProducto, 'id_bodega' => $idBodega])->lockForUpdate()->firstOrFail();
    }
}

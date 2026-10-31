<?php

namespace App\Services\Inventario;

use App\Models\Inventario\MovimientoInventario;
use App\Support\Kardex;

/**
 * Servicio de dominio de Inventario y Kardex.
 *
 * Provee una interfaz inyectable para el registro de movimientos y valoración de stock.
 */
class KardexService
{
    /**
     * @param  array<string, mixed>  $datos
     * @return list<MovimientoInventario>
     */
    public function registrarMovimiento(int $idEmpresa, int $idUsuario, string $moneda, array $datos): array
    {
        return Kardex::registrar($idEmpresa, $idUsuario, $moneda, $datos);
    }

    public function etiqueta(MovimientoInventario $movimiento): string
    {
        return Kardex::etiqueta($movimiento);
    }

    public function cantidadConSigno(MovimientoInventario $movimiento): float
    {
        return Kardex::conSigno($movimiento);
    }
}

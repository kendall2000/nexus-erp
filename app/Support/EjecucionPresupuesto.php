<?php

namespace App\Support;

use App\Models\Core\Empresa;
use App\Models\Finanzas\Factura;
use App\Models\Finanzas\PresupuestoAnual;
use App\Models\Inventario\OrdenCompra;
use Illuminate\Support\Facades\DB;

/**
 * Ejecución del presupuesto por órdenes de compra (gasto) y facturas (ingreso), en un solo lugar.
 *
 * Reglas (simétricas):
 *  - Orden: se ejecuta al APROBARLA y se revierte al CANCELARLA.
 *  - Factura: se ejecuta al EMITIRLA y se revierte al ANULARLA (solo si se había emitido);
 *    una nota de crédito resta.
 *  - Solo cuenta en presupuestos APROBADOS del mismo centro de costo, cuenta y año.
 *  - Va la base sin IVA (el IVA no es gasto) al mes de la fecha de emisión.
 *  - Se actualizan juntos el mes (eje_*) y total_ejecutado.
 */
class EjecucionPresupuesto
{
    /** Registra la orden en el presupuesto (al aprobarla). */
    public static function registrarOrden(OrdenCompra $oc): void
    {
        self::aplicar($oc, 1);
    }

    /** Revierte lo que la orden sumó (al cancelarla). */
    public static function revertirOrden(OrdenCompra $oc): void
    {
        self::aplicar($oc, -1);
    }

    /** Registra la factura en el presupuesto (al emitirla). */
    public static function registrarFactura(Factura $factura): void
    {
        self::aplicarFactura($factura, 1);
    }

    /** Revierte lo que la factura sumó (al anular una factura que ya se había emitido). */
    public static function revertirFactura(Factura $factura): void
    {
        self::aplicarFactura($factura, -1);
    }

    /**
     * Líneas que se pasarían del saldo disponible del presupuesto.
     *
     * @return list<array{presupuesto: PresupuestoAnual, requerido: float, disponible: float, sobregiro: float}>
     */
    public static function sobregiros(OrdenCompra $oc): array
    {
        $resultado = [];
        foreach (self::montosPorPartida($oc) as [$idCentro, $idCuenta, $monto]) {
            $presupuesto = self::presupuesto($oc, $idCentro, $idCuenta);
            if (! $presupuesto) {
                continue; // sin presupuesto aprobado no se valida
            }
            $disponible = (float) $presupuesto->total_presupuestado - (float) $presupuesto->total_ejecutado;
            if ($monto > $disponible + 0.00001) {
                $resultado[] = [
                    'presupuesto' => $presupuesto,
                    'requerido' => round($monto, 2),
                    'disponible' => round($disponible, 2),
                    'sobregiro' => round($monto - $disponible, 2),
                ];
            }
        }

        return $resultado;
    }

    /** Base sin IVA de un monto según la configuración fiscal de la empresa. */
    public static function montoNeto(float $subtotal, bool $afectoIva, float $tasaIva, bool $ivaIncluido): float
    {
        if (! $afectoIva || ! $ivaIncluido) {
            return round($subtotal, 4);
        }

        return round($subtotal / (1 + $tasaIva), 4);
    }

    private static function aplicar(OrdenCompra $oc, int $signo): void
    {
        foreach (self::montosPorPartida($oc) as [$idCentro, $idCuenta, $monto]) {
            self::sumar(self::presupuesto($oc, $idCentro, $idCuenta), $oc->fecha_emision->month, $signo * $monto);
        }
    }

    private static function aplicarFactura(Factura $factura, int $signo): void
    {
        $factura->loadMissing('detalles.tipoServicio');
        $empresa = Empresa::find($factura->id_empresa);
        $tasaIva = $empresa ? (float) $empresa->tasa_iva_decimal : 0.12;
        $ivaIncluido = $empresa ? (bool) $empresa->iva_incluido_en_precio : true;
        $signo *= $factura->tipo === 'NOTA_CREDITO' ? -1 : 1;
        // El descuento global se reparte en proporción al importe de cada línea.
        $subtotal = (float) $factura->detalles->sum('subtotal');
        $factor = $subtotal > 0 ? 1 - min((float) $factura->descuento, $subtotal) / $subtotal : 1;

        $partidas = [];
        foreach ($factura->detalles as $linea) {
            [$idCentro, $idCuenta] = [$linea->centro_efectivo, $linea->cuenta_efectiva];
            if (! $idCentro || ! $idCuenta) {
                continue;
            }
            $partidas["{$idCentro}-{$idCuenta}"] ??= [(int) $idCentro, (int) $idCuenta, 0.0];
            $partidas["{$idCentro}-{$idCuenta}"][2] += self::montoNeto((float) $linea->subtotal * $factor, (bool) $linea->es_afecto_iva, $tasaIva, $ivaIncluido);
        }
        foreach ($partidas as [$idCentro, $idCuenta, $monto]) {
            $presupuesto = PresupuestoAnual::query()->where('id_empresa', $factura->id_empresa)->where('id_centro', $idCentro)->where('id_cuenta', $idCuenta)
                ->where('anio', $factura->fecha_emision->year)->where('estado', PresupuestoAnual::ESTADO_APROBADO)->first();
            self::sumar($presupuesto, $factura->fecha_emision->month, $signo * round($monto, 4));
        }
    }

    /** Suma (o resta) al mes y al total_ejecutado juntos. */
    private static function sumar(?PresupuestoAnual $presupuesto, int $mes, float $monto): void
    {
        if (! $presupuesto) {
            return;
        }
        $columnaMes = 'eje_'.PresupuestoAnual::MESES[$mes];
        DB::table('presupuesto_anual')->where('id_presupuesto', $presupuesto->id_presupuesto)->update([
            $columnaMes => DB::raw("{$columnaMes} + ".$monto),
            'total_ejecutado' => DB::raw('total_ejecutado + '.$monto),
        ]);
    }

    /** @return list<array{0: int, 1: int, 2: float}> [centro, cuenta, monto neto] agrupado por partida. */
    private static function montosPorPartida(OrdenCompra $oc): array
    {
        $oc->loadMissing('detalles.producto');
        $empresa = Empresa::find($oc->id_empresa);
        $tasaIva = $empresa ? (float) $empresa->tasa_iva_decimal : 0.12;
        $ivaIncluido = $empresa ? (bool) $empresa->iva_incluido_en_precio : false;

        $partidas = [];
        foreach ($oc->detalles as $linea) {
            [$idCentro, $idCuenta] = [$linea->centro_efectivo, $linea->cuenta_efectiva];
            if (! $idCentro || ! $idCuenta) {
                continue;
            }
            $clave = "{$idCentro}-{$idCuenta}";
            $partidas[$clave] ??= [(int) $idCentro, (int) $idCuenta, 0.0];
            $partidas[$clave][2] += self::montoNeto((float) $linea->subtotal, true, $tasaIva, $ivaIncluido);
        }

        return array_values($partidas);
    }

    private static function presupuesto(OrdenCompra $oc, int $idCentro, int $idCuenta): ?PresupuestoAnual
    {
        return PresupuestoAnual::query()
            ->where('id_empresa', $oc->id_empresa)
            ->where('id_centro', $idCentro)
            ->where('id_cuenta', $idCuenta)
            ->where('anio', $oc->fecha_emision->year)
            ->where('estado', PresupuestoAnual::ESTADO_APROBADO)
            ->first();
    }
}

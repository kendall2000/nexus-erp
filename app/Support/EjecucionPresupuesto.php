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
 *  - Va la base sin IVA (el IVA no es gasto) al mes de la fecha de emisión; en facturas,
 *    después de repartir el descuento global entre las líneas.
 *  - Se actualizan juntos el mes (eje_*) y total_ejecutado.
 *
 * Lo que se sumó queda en ejecucion_presupuesto (por documento, presupuesto y mes) y al
 * revertir se resta exactamente eso: no se vuelve a calcular, así que no se descuadra si
 * el presupuesto cambió de estado o el producto o servicio cambió de centro o de cuenta.
 */
class EjecucionPresupuesto
{
    public const ORDEN = 'ORDEN_COMPRA';

    public const FACTURA = 'FACTURA';

    /** Registra la orden en el presupuesto (al aprobarla). */
    public static function registrarOrden(OrdenCompra $oc): void
    {
        self::registrar(self::ORDEN, $oc->id_oc, $oc->id_empresa, $oc->fecha_emision, self::partidasOrden($oc), [PresupuestoAnual::ESTADO_APROBADO]);
    }

    /** Revierte lo que la orden sumó (al cancelarla). */
    public static function revertirOrden(OrdenCompra $oc): void
    {
        self::revertir(self::ORDEN, $oc->id_oc, fn () => self::registrarOrdenAntigua($oc));
    }

    /** Registra la factura en el presupuesto (al emitirla). */
    public static function registrarFactura(Factura $factura): void
    {
        self::registrar(self::FACTURA, $factura->id_factura, $factura->id_empresa, $factura->fecha_emision, self::partidasFactura($factura), [PresupuestoAnual::ESTADO_APROBADO]);
    }

    /** Revierte lo que la factura sumó (al anular una factura que ya se había emitido). */
    public static function revertirFactura(Factura $factura): void
    {
        self::revertir(self::FACTURA, $factura->id_factura, fn () => self::registrarFacturaAntigua($factura));
    }

    /**
     * Líneas que se pasarían del saldo disponible del presupuesto.
     *
     * @return list<array{presupuesto: PresupuestoAnual, requerido: float, disponible: float, sobregiro: float}>
     */
    public static function sobregiros(OrdenCompra $oc): array
    {
        $resultado = [];
        foreach (self::partidasOrden($oc) as [$idCentro, $idCuenta, $monto]) {
            $presupuesto = self::presupuesto($oc->id_empresa, $idCentro, $idCuenta, $oc->fecha_emision->year, [PresupuestoAnual::ESTADO_APROBADO]);
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

    /**
     * Base sin IVA de la orden por partida.
     *
     * @return list<array{0: int, 1: int, 2: float}> [centro, cuenta, monto neto]
     */
    public static function partidasOrden(OrdenCompra $oc): array
    {
        $oc->loadMissing('detalles.producto');
        [$tasaIva, $ivaIncluido] = self::fiscal($oc->id_empresa);

        $partidas = [];
        foreach ($oc->detalles as $linea) {
            self::acumular($partidas, $linea->centro_efectivo, $linea->cuenta_efectiva,
                self::montoNeto((float) $linea->subtotal, true, $tasaIva, $ivaIncluido));
        }

        return self::redondear($partidas);
    }

    /**
     * Base sin IVA de la factura por partida, con el descuento global repartido en proporción
     * al importe de cada línea. Las notas de crédito salen en negativo.
     *
     * @return list<array{0: int, 1: int, 2: float}> [centro, cuenta, monto neto]
     */
    public static function partidasFactura(Factura $factura): array
    {
        $factura->loadMissing('detalles.tipoServicio');
        [$tasaIva, $ivaIncluido] = self::fiscal($factura->id_empresa);
        $signo = $factura->tipo === 'NOTA_CREDITO' ? -1 : 1;
        $subtotal = (float) $factura->detalles->sum('subtotal');
        $factor = $subtotal > 0 ? 1 - min((float) $factura->descuento, $subtotal) / $subtotal : 1;

        $partidas = [];
        foreach ($factura->detalles as $linea) {
            self::acumular($partidas, $linea->centro_efectivo, $linea->cuenta_efectiva,
                $signo * self::montoNeto((float) $linea->subtotal * $factor, (bool) $linea->es_afecto_iva, $tasaIva, $ivaIncluido));
        }

        return self::redondear($partidas);
    }

    /**
     * Suma el documento a los presupuestos y deja constancia de cada monto. Si no cae en
     * ningún presupuesto, deja una marca vacía para saber que ya se registró con estas reglas.
     *
     * @param  list<array{0: int, 1: int, 2: float}>  $partidas
     * @param  list<string>  $estados  estados del presupuesto que cuentan (en vivo, solo APROBADO)
     */
    public static function registrar(string $documento, int $idDocumento, int $idEmpresa, \DateTimeInterface $fecha, array $partidas, array $estados): void
    {
        $mes = (int) $fecha->format('n');
        $filas = [];
        foreach ($partidas as [$idCentro, $idCuenta, $monto]) {
            $presupuesto = self::presupuesto($idEmpresa, $idCentro, $idCuenta, (int) $fecha->format('Y'), $estados);
            if (! $presupuesto || abs($monto) < 0.00005) {
                continue;
            }
            self::sumar($presupuesto->id_presupuesto, $mes, $monto);
            $filas[] = ['id_presupuesto' => $presupuesto->id_presupuesto, 'mes' => $mes, 'monto' => $monto];
        }
        $filas = $filas ?: [['id_presupuesto' => null, 'mes' => null, 'monto' => 0]];

        DB::table('ejecucion_presupuesto')->insert(array_map(fn ($f) => $f + [
            'id_empresa' => $idEmpresa, 'documento' => $documento, 'id_documento' => $idDocumento, 'created_at' => now(),
        ], $filas));
    }

    /**
     * Resta lo que el documento sumó y lo marca como revertido. Documentos aprobados o
     * emitidos antes de que existiera el registro: se calculan como antes ($antiguo).
     */
    private static function revertir(string $documento, int $idDocumento, \Closure $antiguo): void
    {
        $vigentes = fn () => DB::table('ejecucion_presupuesto')->where('documento', $documento)
            ->where('id_documento', $idDocumento)->whereNull('revertido_at');
        if (! DB::table('ejecucion_presupuesto')->where('documento', $documento)->where('id_documento', $idDocumento)->exists()) {
            $antiguo();
        }

        foreach ($vigentes()->lockForUpdate()->get() as $fila) {
            if ($fila->id_presupuesto) {
                self::sumar((int) $fila->id_presupuesto, (int) $fila->mes, -1 * (float) $fila->monto);
            }
        }
        $vigentes()->update(['revertido_at' => now()]);
    }

    /**
     * Para revertir un documento sin registro: lo que se le habría sumado con la regla de
     * entonces (presupuestos aprobados hoy), anotado sin volver a sumarlo.
     */
    private static function registrarOrdenAntigua(OrdenCompra $oc): void
    {
        self::anotarSinSumar(self::ORDEN, $oc->id_oc, $oc->id_empresa, $oc->fecha_emision, self::partidasOrden($oc));
    }

    private static function registrarFacturaAntigua(Factura $factura): void
    {
        self::anotarSinSumar(self::FACTURA, $factura->id_factura, $factura->id_empresa, $factura->fecha_emision, self::partidasFactura($factura));
    }

    /** @param  list<array{0: int, 1: int, 2: float}>  $partidas */
    private static function anotarSinSumar(string $documento, int $idDocumento, int $idEmpresa, \DateTimeInterface $fecha, array $partidas): void
    {
        $mes = (int) $fecha->format('n');
        foreach ($partidas as [$idCentro, $idCuenta, $monto]) {
            $presupuesto = self::presupuesto($idEmpresa, $idCentro, $idCuenta, (int) $fecha->format('Y'), [PresupuestoAnual::ESTADO_APROBADO]);
            if ($presupuesto) {
                DB::table('ejecucion_presupuesto')->insert([
                    'id_empresa' => $idEmpresa, 'documento' => $documento, 'id_documento' => $idDocumento,
                    'id_presupuesto' => $presupuesto->id_presupuesto, 'mes' => $mes, 'monto' => $monto, 'created_at' => now(),
                ]);
            }
        }
    }

    /** Suma (o resta) al mes y al total_ejecutado juntos. */
    private static function sumar(int $idPresupuesto, int $mes, float $monto): void
    {
        $columnaMes = 'eje_'.PresupuestoAnual::MESES[$mes];
        $monto = round($monto, 4);
        DB::table('presupuesto_anual')->where('id_presupuesto', $idPresupuesto)->update([
            $columnaMes => DB::raw("{$columnaMes} + ".$monto),
            'total_ejecutado' => DB::raw('total_ejecutado + '.$monto),
        ]);
    }

    /** @param  list<string>  $estados */
    private static function presupuesto(int $idEmpresa, int $idCentro, int $idCuenta, int $anio, array $estados): ?PresupuestoAnual
    {
        return PresupuestoAnual::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_centro', $idCentro)
            ->where('id_cuenta', $idCuenta)
            ->where('anio', $anio)
            ->whereIn('estado', $estados)
            ->first();
    }

    /** @return array{0: float, 1: bool} [tasa de IVA, ¿el precio incluye IVA?] */
    private static function fiscal(int $idEmpresa): array
    {
        $empresa = Empresa::find($idEmpresa);

        return [$empresa ? (float) $empresa->tasa_iva_decimal : 0.12, $empresa ? (bool) $empresa->iva_incluido_en_precio : false];
    }

    private static function acumular(array &$partidas, mixed $idCentro, mixed $idCuenta, float $monto): void
    {
        if (! $idCentro || ! $idCuenta) {
            return;
        }
        $partidas["{$idCentro}-{$idCuenta}"] ??= [(int) $idCentro, (int) $idCuenta, 0.0];
        $partidas["{$idCentro}-{$idCuenta}"][2] += $monto;
    }

    /** @return list<array{0: int, 1: int, 2: float}> */
    private static function redondear(array $partidas): array
    {
        return array_values(array_map(fn ($p) => [$p[0], $p[1], round($p[2], 4)], $partidas));
    }
}

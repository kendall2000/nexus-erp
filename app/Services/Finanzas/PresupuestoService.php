<?php

namespace App\Services\Finanzas;

use App\Models\Finanzas\Factura;
use App\Models\Inventario\OrdenCompra;
use App\Support\EjecucionPresupuesto;
use App\Support\PlanCuentas;

/**
 * Servicio de dominio Financiero y Presupuestario.
 *
 * Provee una interfaz inyectable para ejecución de presupuesto y plan de cuentas.
 */
class PresupuestoService
{
    public function registrarOrden(OrdenCompra $orden): void
    {
        EjecucionPresupuesto::registrarOrden($orden);
    }

    public function revertirOrden(OrdenCompra $orden): void
    {
        EjecucionPresupuesto::revertirOrden($orden);
    }

    public function registrarFactura(Factura $factura): void
    {
        EjecucionPresupuesto::registrarFactura($factura);
    }

    public function revertirFactura(Factura $factura): void
    {
        EjecucionPresupuesto::revertirFactura($factura);
    }

    public function validarSobregiros(OrdenCompra $orden): array
    {
        return EjecucionPresupuesto::sobregiros($orden);
    }

    public function montoNeto(float $subtotal, bool $afectoIva, float $tasaIva, bool $ivaIncluido): float
    {
        return EjecucionPresupuesto::montoNeto($subtotal, $afectoIva, $tasaIva, $ivaIncluido);
    }

    public function exportarPlantillaCuentas(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return PlanCuentas::plantilla();
    }
}

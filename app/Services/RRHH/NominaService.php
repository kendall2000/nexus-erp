<?php

namespace App\Services\RRHH;

use App\Models\RRHH\Empleado;
use App\Models\RRHH\PeriodoNomina;
use App\Support\Nomina;
use App\Support\Prestaciones;

/**
 * Servicio de dominio de Recursos Humanos, Nómina y Prestaciones Laborales.
 */
class NominaService
{
    public function asegurarConceptos(int $idEmpresa): array
    {
        return Nomina::conceptos($idEmpresa);
    }

    public function procesarPeriodo(PeriodoNomina $periodo): int
    {
        return Nomina::procesar($periodo);
    }

    public function diasTrabajados(PeriodoNomina $periodo, Empleado $empleado): float
    {
        return Nomina::diasTrabajados($periodo, $empleado);
    }

    public function calcularPrestacionAnual(int $idEmpresa, int $idUsuario, string $tipo, int $anio): int
    {
        return Prestaciones::calcularAnual($idEmpresa, $idUsuario, $tipo, $anio);
    }
}

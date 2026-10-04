<?php

namespace App\Support;

use App\Models\RRHH\Empleado;
use App\Models\RRHH\PrestacionLaboral;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Prestaciones laborales (Guatemala). Las reglas están aquí para revisarlas en un solo lugar;
 * conviene validarlas con el contador de la empresa.
 *
 *  - Aguinaldo (Decreto 76-78): un salario por año, del 1 de diciembre al 30 de noviembre.
 *  - Bono 14 (Decreto 42-92): un salario por año, del 1 de julio al 30 de junio.
 *    Ambos proporcionales a los días laborados en el periodo: salario × días / 365.
 *  - Vacaciones no gozadas: salario / 30 por cada día pendiente (solo se pagan en dinero al terminar la relación).
 *  - Indemnización (despido injustificado): un salario por año de servicio, proporcional;
 *    la base incluye la doceava parte del aguinaldo y del bono 14 (salario × 14 / 12).
 * El salario es el del contrato que regía al final del periodo (no se promedian los últimos seis meses).
 * Los cálculos nacen CALCULADO → se aprueban → se pagan; recalcular solo reemplaza los que siguen CALCULADO.
 */
class Prestaciones
{
    public const TIPOS = [
        'AGUINALDO' => 'Aguinaldo', 'BONO14' => 'Bono 14', 'INDEMNIZACION' => 'Indemnización',
        'VACACIONES_DINERO' => 'Vacaciones no gozadas', 'OTRO' => 'Otro',
    ];

    /** Las que se calculan cada año para toda la planilla. */
    public const ANUALES = ['AGUINALDO', 'BONO14'];

    /** Mes en que empieza el periodo anual de cada prestación. */
    private const MES_INICIO = ['AGUINALDO' => 12, 'BONO14' => 7];

    public const PREFIJO_LIQUIDACION = 'LIQ-';

    /** Periodo anual que se paga en $anio: [desde, hasta]. */
    public static function periodo(string $tipo, int $anio): array
    {
        $desde = Carbon::create($anio - 1, self::MES_INICIO[$tipo], 1)->startOfDay();

        return [$desde, $desde->copy()->addYear()->subDay()];
    }

    /** Inicio del periodo anual que está corriendo en esa fecha. */
    public static function inicioPeriodo(string $tipo, Carbon $fecha): Carbon
    {
        $inicio = Carbon::create($fecha->year, self::MES_INICIO[$tipo], 1)->startOfDay();

        return $inicio->gt($fecha) ? $inicio->subYear() : $inicio;
    }

    /** Días entre dos fechas, ambas incluidas. */
    public static function dias(Carbon $desde, Carbon $hasta): int
    {
        return $hasta->lt($desde) ? 0 : (int) round(abs($desde->copy()->startOfDay()->diffInDays($hasta->copy()->startOfDay()))) + 1;
    }

    /** Parte del salario que corresponde a los días laborados (el periodo completo es un salario). */
    public static function proporcional(float $salario, int $dias, int $diasPeriodo = 365): float
    {
        return $dias >= $diasPeriodo ? round($salario, 2) : round($salario * $dias / 365, 2);
    }

    /** Salario del contrato que regía en esa fecha (el más reciente que ya había empezado). */
    public static function salario(Empleado $e, Carbon $fecha): ?float
    {
        $contrato = $e->contratos->filter(fn ($c) => $c->fecha_inicio->lte($fecha))->sortByDesc('fecha_inicio')->first();

        return $contrato ? (float) $contrato->salario_base : null;
    }

    /**
     * Aguinaldo o bono 14 del año para todos los empleados activos con contrato.
     *
     * @return array{calculados: int, conservados: int, sinContrato: list<string>}
     */
    public static function calcularAnual(int $idEmpresa, string $tipo, int $anio): array
    {
        [$desde, $hasta] = self::periodo($tipo, $anio);
        $empleados = Empleado::query()->where('id_empresa', $idEmpresa)->where('estado', '!=', 'BAJA')->with('contratos')
            ->whereDate('fecha_ingreso', '<=', $hasta)->get();

        $resultado = ['calculados' => 0, 'conservados' => 0, 'sinContrato' => []];
        foreach ($empleados as $e) {
            $salario = self::salario($e, $hasta);
            if ($salario === null) {
                // Un rotativo sin contrato cobra por día: no genera prestaciones.
                if (! $e->es_rotativo) {
                    $resultado['sinContrato'][] = $e->nombre_completo;
                }

                continue;
            }
            $dias = self::dias($e->fecha_ingreso->max($desde), $hasta);
            $guardada = self::guardar($e, $tipo, (string) $anio, $salario, self::proporcional($salario, $dias, self::dias($desde, $hasta)), $dias);
            $resultado[$guardada ? 'calculados' : 'conservados']++;
        }

        return $resultado;
    }

    /**
     * Liquidación al terminar la relación laboral: aguinaldo y bono 14 proporcionales,
     * vacaciones pendientes y, si corresponde, indemnización.
     *
     * @return list<PrestacionLaboral>
     */
    public static function liquidar(Empleado $e, Carbon $salida, bool $indemnizacion, float $diasVacaciones): array
    {
        $e->loadMissing('contratos');
        $salario = self::salario($e, $salida);
        if ($salario === null) {
            throw ValidationException::withMessages(['id_empleado' => 'El empleado no tiene contrato a esa fecha: no hay salario para liquidar.']);
        }
        $anteriores = PrestacionLaboral::query()->where('id_empleado', $e->id_empleado)->where('periodo_calculo', 'like', self::PREFIJO_LIQUIDACION.'%')->get();
        if ($anteriores->where('estado', '!=', 'CALCULADO')->isNotEmpty()) {
            throw ValidationException::withMessages(['id_empleado' => 'El empleado ya tiene una liquidación aprobada o pagada.']);
        }
        $anteriores->each->delete(); // recalcular reemplaza la liquidación que aún no se aprobó

        $periodo = self::PREFIJO_LIQUIDACION.$salida->format('Ymd');
        $lineas = [];
        foreach (self::ANUALES as $tipo) {
            $dias = self::dias($e->fecha_ingreso->max(self::inicioPeriodo($tipo, $salida)), $salida);
            $lineas[] = self::guardar($e, $tipo, $periodo, $salario, self::proporcional($salario, $dias), $dias);
        }
        if ($diasVacaciones > 0) {
            $lineas[] = self::guardar($e, 'VACACIONES_DINERO', $periodo, $salario, round($salario / 30 * $diasVacaciones, 2), $diasVacaciones);
        }
        if ($indemnizacion) {
            $base = round($salario * 14 / 12, 4);
            $dias = self::dias($e->fecha_ingreso, $salida);
            $lineas[] = self::guardar($e, 'INDEMNIZACION', $periodo, $base, round($base * $dias / 365, 2), $dias);
        }

        return array_values(array_filter($lineas));
    }

    /** Crea o recalcula la prestación; si ya está aprobada o pagada la deja como está (devuelve null). */
    private static function guardar(Empleado $e, string $tipo, string $periodo, float $base, float $monto, float $dias): ?PrestacionLaboral
    {
        $p = PrestacionLaboral::query()->firstOrNew(['id_empleado' => $e->id_empleado, 'tipo' => $tipo, 'periodo_calculo' => $periodo]);
        if ($p->exists && $p->estado !== 'CALCULADO') {
            return null;
        }
        $p->fill(['id_empresa' => $e->id_empresa, 'monto_base' => $base, 'monto_calculado' => $monto, 'dias_calculados' => $dias, 'estado' => 'CALCULADO'])->save();

        return $p;
    }
}

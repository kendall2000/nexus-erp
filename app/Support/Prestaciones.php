<?php

namespace App\Support;

use App\Models\RRHH\Empleado;
use App\Models\RRHH\PrestacionLaboral;
use App\Models\RRHH\SolicitudAusencia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        // Se divide entre los días del periodo (366 si tiene un 29 de febrero).
        return $dias >= $diasPeriodo ? round($salario, 2) : round($salario * $dias / $diasPeriodo, 2);
    }

    /**
     * Salario que regía en esa fecha (contrato vigente y cambios con su fecha efectiva, ver
     * Salarios::vigente). Un contrato rescindido no rige después de su fin: un reactivado sin
     * contrato nuevo no tiene salario.
     */
    public static function salario(Empleado $e, Carbon $fecha): ?float
    {
        return Salarios::vigente($e, $fecha);
    }

    /**
     * Aguinaldo o bono 14 del año para todos los que trabajaron en el periodo, también los que ya
     * están de baja (el periodo cumplido se les debe, hasta su fecha de baja). A quien se liquidó
     * dentro del periodo no se le calcula: ese periodo lo paga su liquidación. Solo periodos ya
     * terminados: antes de que termine nadie lo ha devengado completo.
     *
     * @return array{calculados: int, conservados: int, liquidados: int, sinContrato: list<string>}
     */
    public static function calcularAnual(int $idEmpresa, string $tipo, int $anio): array
    {
        [$desde, $hasta] = self::periodo($tipo, $anio);
        if ($hasta->gte(today())) {
            throw ValidationException::withMessages(['anio' => self::TIPOS[$tipo]." {$anio} cubre del {$desde->format('d/m/Y')} al {$hasta->format('d/m/Y')}: se calcula cuando ese periodo haya terminado."]);
        }
        DB::table('empresa')->where('id_empresa', $idEmpresa)->lockForUpdate()->first(); // un cálculo a la vez por empresa
        $empleados = Empleado::query()->where('id_empresa', $idEmpresa)->with(['contratos', 'historialSalarial'])
            ->whereDate('fecha_ingreso', '<=', $hasta)
            ->where(fn ($q) => $q->whereNull('fecha_baja')->orWhereDate('fecha_baja', '>=', $desde))->get();

        $resultado = ['calculados' => 0, 'conservados' => 0, 'liquidados' => 0, 'sinContrato' => []];
        foreach ($empleados as $e) {
            $liquidacion = self::salidaLiquidada($e);
            if ($liquidacion && $liquidacion->between($desde, $hasta)) {
                // Lo paga la liquidación; un cálculo anual sin aprobar que hubiera quedado se quita.
                PrestacionLaboral::query()->where(['id_empleado' => $e->id_empleado, 'tipo' => $tipo, 'periodo_calculo' => (string) $anio, 'estado' => 'CALCULADO'])->delete();
                $resultado['liquidados']++;

                continue;
            }
            $fin = collect([$hasta, $e->fecha_baja, $liquidacion])->filter()->min();
            if ($fin->lt($desde)) {
                continue; // salió antes del periodo
            }
            $salario = self::salario($e, $fin);
            if ($salario === null) {
                // Un rotativo sin contrato cobra por día: no genera prestaciones.
                if (! $e->es_rotativo) {
                    $resultado['sinContrato'][] = $e->nombre_completo;
                }

                continue;
            }
            $dias = self::dias($e->fecha_ingreso->max($desde), $fin);
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
        Empleado::query()->whereKey($e->id_empleado)->lockForUpdate()->first(); // una liquidación a la vez por empleado
        $e->loadMissing('contratos');
        $salario = self::salario($e, $salida);
        if ($salario === null) {
            throw ValidationException::withMessages(['id_empleado' => 'El empleado no tiene contrato a esa fecha: no hay salario para liquidar.']);
        }
        // Vacaciones: no más días que los que tiene disponibles a la salida.
        $disponible = self::saldoVacaciones($e, $salida)['disponible'];
        if ($diasVacaciones > $disponible + 0.005) {
            throw ValidationException::withMessages(['dias_vacaciones' => 'A la salida tiene '.rtrim(rtrim(number_format($disponible, 2), '0'), '.').' días de vacaciones disponibles: no se pueden pagar más.']);
        }
        // Solo cuenta la relación laboral vigente: un reactivado (con nueva fecha de ingreso) se
        // puede volver a liquidar aunque tenga aprobada la de su relación anterior.
        $anteriores = PrestacionLaboral::query()->where('id_empleado', $e->id_empleado)->where('periodo_calculo', 'like', self::PREFIJO_LIQUIDACION.'%')->get()
            ->filter(fn ($p) => ($f = self::fechaLiquidacion($p->periodo_calculo)) && $f->gte($e->fecha_ingreso));
        if ($anteriores->where('estado', '!=', 'CALCULADO')->isNotEmpty()) {
            throw ValidationException::withMessages(['id_empleado' => 'El empleado ya tiene una liquidación aprobada o pagada.']);
        }
        $anteriores->each->delete(); // recalcular reemplaza la liquidación que aún no se aprobó

        $periodo = self::PREFIJO_LIQUIDACION.$salida->format('Ymd');
        $lineas = [];
        foreach (self::ANUALES as $tipo) {
            // El periodo que corre a la salida lo paga la liquidación, salvo que su cálculo anual
            // ya esté aprobado o pagado (no se paga dos veces); uno sin aprobar se reemplaza.
            $inicio = self::inicioPeriodo($tipo, $salida);
            $anual = PrestacionLaboral::query()->where(['id_empleado' => $e->id_empleado, 'tipo' => $tipo, 'periodo_calculo' => (string) ($inicio->year + 1)])->first();
            if ($anual && $anual->estado !== 'CALCULADO') {
                continue;
            }
            $anual?->delete();
            $dias = self::dias($e->fecha_ingreso->max($inicio), $salida);
            $lineas[] = self::guardar($e, $tipo, $periodo, $salario, self::proporcional($salario, $dias, self::dias($inicio, $inicio->copy()->addYear()->subDay())), $dias);
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

    /** Fecha de salida de una liquidación a partir de su periodo («LIQ-20261003»). */
    public static function fechaLiquidacion(string $periodo): ?Carbon
    {
        return preg_match('/^'.self::PREFIJO_LIQUIDACION.'(\d{8})$/', $periodo, $m) ? Carbon::createFromFormat('Ymd', $m[1])->startOfDay() : null;
    }

    /** Salida de la liquidación de su relación laboral vigente (la más reciente), si la tiene. */
    public static function salidaLiquidada(Empleado $e): ?Carbon
    {
        return PrestacionLaboral::query()->where('id_empleado', $e->id_empleado)->where('periodo_calculo', 'like', self::PREFIJO_LIQUIDACION.'%')
            ->pluck('periodo_calculo')->map(fn ($p) => self::fechaLiquidacion($p))
            ->filter(fn ($f) => $f && $f->gte($e->fecha_ingreso))->sortDesc()->first();
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

    /**
     * Saldo de vacaciones del empleado a una fecha de corte (Guatemala: 15 días hábiles por año laborado).
     *
     * @return array{dias_servicio: int, devengado: float, gozados: float, pendientes: float, disponible: float, salario_diario: float, valor_estimado: float}
     */
    public static function saldoVacaciones(Empleado $e, ?Carbon $fechaCorte = null): array
    {
        $corte = ($fechaCorte ?? today())->copy()->startOfDay();
        $hasta = $e->fecha_baja && $e->fecha_baja->lt($corte) ? $e->fecha_baja : $corte;
        $diasServicio = $e->fecha_ingreso ? self::dias($e->fecha_ingreso, $hasta) : 0;
        $devengado = round($diasServicio * 15 / 365, 2);

        $solicitudes = $e->relationLoaded('solicitudesAusencia')
            ? $e->solicitudesAusencia
            : SolicitudAusencia::query()->where('id_empleado', $e->id_empleado)->where('tipo', 'VACACIONES')->get();

        $gozados = (float) $solicitudes->where('tipo', 'VACACIONES')->where('estado', 'APROBADO')->sum('dias_habiles');
        $pendientes = (float) $solicitudes->where('tipo', 'VACACIONES')->where('estado', 'PENDIENTE')->sum('dias_habiles');
        $disponible = round(max(0.0, $devengado - $gozados), 2);

        $e->loadMissing('contratos');
        $salario = self::salario($e, $hasta) ?? 0.0;
        $salarioDiario = round($salario / 30, 2);

        return [
            'dias_servicio' => $diasServicio,
            'devengado' => $devengado,
            'gozados' => round($gozados, 2),
            'pendientes' => round($pendientes, 2),
            'disponible' => $disponible,
            'salario_diario' => $salarioDiario,
            'valor_estimado' => round($disponible * $salarioDiario, 2),
        ];
    }

    /**
     * Pasivo laboral acumulado al día de corte para todos los empleados activos con contrato.
     *
     * @return array{filas: list<array<string, mixed>>, totales: array<string, float>}
     */
    public static function pasivoAcumulado(int $idEmpresa, ?Carbon $fechaCorte = null): array
    {
        $corte = ($fechaCorte ?? today())->copy()->startOfDay();
        $empleados = Empleado::query()
            ->where('id_empresa', $idEmpresa)
            ->where('estado', '!=', 'BAJA')
            ->with(['cargo', 'departamento', 'contratos', 'solicitudesAusencia'])
            ->whereDate('fecha_ingreso', '<=', $corte)
            ->orderBy('primer_apellido')
            ->orderBy('primer_nombre')
            ->get();

        $filas = [];
        $totales = [
            'aguinaldo' => 0.0,
            'bono14' => 0.0,
            'vacaciones' => 0.0,
            'indemnizacion' => 0.0,
            'total' => 0.0,
        ];

        $inicioAguinaldo = self::inicioPeriodo('AGUINALDO', $corte);
        $inicioBono14 = self::inicioPeriodo('BONO14', $corte);

        foreach ($empleados as $e) {
            $salario = self::salario($e, $corte);
            if ($salario === null) {
                continue;
            }

            $diasAguinaldo = self::dias($e->fecha_ingreso->max($inicioAguinaldo), $corte);
            $aguinaldo = self::proporcional($salario, $diasAguinaldo);

            $diasBono14 = self::dias($e->fecha_ingreso->max($inicioBono14), $corte);
            $bono14 = self::proporcional($salario, $diasBono14);

            $vac = self::saldoVacaciones($e, $corte);
            $vacacionesMonto = $vac['valor_estimado'];

            $diasServicio = self::dias($e->fecha_ingreso, $corte);
            $baseIndemnizacion = round($salario * 14 / 12, 4);
            $indemnizacion = round($baseIndemnizacion * $diasServicio / 365, 2);

            $totalEmpleado = round($aguinaldo + $bono14 + $vacacionesMonto + $indemnizacion, 2);

            $filas[] = [
                'empleado' => $e,
                'salario' => $salario,
                'dias_servicio' => $diasServicio,
                'aguinaldo' => $aguinaldo,
                'dias_aguinaldo' => $diasAguinaldo,
                'bono14' => $bono14,
                'dias_bono14' => $diasBono14,
                'vacaciones_dias' => $vac['disponible'],
                'vacaciones_monto' => $vacacionesMonto,
                'indemnizacion' => $indemnizacion,
                'total' => $totalEmpleado,
            ];

            $totales['aguinaldo'] += $aguinaldo;
            $totales['bono14'] += $bono14;
            $totales['vacaciones'] += $vacacionesMonto;
            $totales['indemnizacion'] += $indemnizacion;
            $totales['total'] += $totalEmpleado;
        }

        foreach ($totales as $k => $v) {
            $totales[$k] = round($v, 2);
        }

        return [
            'filas' => $filas,
            'totales' => $totales,
        ];
    }
}

<?php

namespace App\Support;

use App\Http\Controllers\AsistenciaController;
use App\Models\Finanzas\PresupuestoAnual;
use App\Models\RRHH\Asistencia;
use App\Models\RRHH\CoberturaRotativo;
use App\Models\RRHH\ConceptoNomina;
use App\Models\RRHH\DetalleNomina;
use App\Models\RRHH\DetalleNominaConcepto;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\PeriodoNomina;
use App\Models\RRHH\PrestamoEmpleado;
use App\Models\RRHH\SolicitudAusencia;
use Illuminate\Support\Carbon;

/**
 * Cálculo de nómina (Guatemala). Las tasas y reglas están aquí como constantes para
 * revisarlas en un solo lugar; conviene validarlas con el contador de la empresa.
 *
 * Por empleado y periodo:
 *  - Salario base proporcional a los días trabajados dentro del periodo (ingreso o baja a medio periodo),
 *    menos las ausencias injustificadas y los permisos sin goce registrados en Asistencia.
 *  - Bonificación incentivo (Decreto 37-2001): Q250 mensuales, prorrateada; no afecta IGSS.
 *  - IGSS laboral 4.83 % y cuota patronal 12.67 % (IGSS 10.67 + INTECAP 1 + IRTRA 1) sobre ingresos afectos.
 *  - ISR de asalariados: se proyecta a un año (ingresos afectos del periodo × periodos del año),
 *    se restan la deducción única de Q48,000 y el IGSS laboral anual, 5 % hasta Q300,000 y
 *    Q15,000 + 7 % del excedente; se retiene la parte del periodo.
 *  - Horas extra: (salario / 30 / 8) × 1.5 por hora, afectas a IGSS e ISR.
 *  - Personal rotativo: días cubiertos dentro del periodo × la tarifa diaria de cada cobertura. Un rotativo sin
 *    contrato solo cobra eso (sin salario base ni bonificación); el concepto ROTATIVO nace sin afectar IGSS ni ISR.
 *  - Cuota de préstamos activos (la cuota registrada es quincenal y se convierte al periodo).
 * Los conceptos automáticos se recalculan; los manuales (bonos, descuentos) se conservan.
 */
class Nomina
{
    public const IGSS_LABORAL = 0.0483;

    public const IGSS_PATRONAL = 0.1267;

    public const BONIFICACION_INCENTIVO_MENSUAL = 250.0;

    public const ISR_DEDUCCION_UNICA = 48000.0;

    public const ISR_TRAMO = 300000.0;

    /** tipo de periodo => [días del periodo, periodos por año] */
    public const PERIODOS = ['MENSUAL' => [30, 12], 'QUINCENAL' => [15, 24], 'CATORCENAL' => [14, 26], 'SEMANAL' => [7, 52]];

    /** Conceptos automáticos (se recalculan). */
    public const AUTOMATICOS = ['SALBASE', 'BONINC', 'HEXTRA', 'ROTATIVO', 'IGSS', 'ISR', 'PRESTAMO'];

    /** Horas extra: hora ordinaria = salario mensual / 30 / 8 (jornada diurna), pagada al 150 %. */
    public const RECARGO_HORA_EXTRA = 1.5;

    /** Conceptos de cada empresa: código => [nombre, tipo, afecta IGSS, afecta ISR, es fijo]. */
    public const CONCEPTOS = [
        'SALBASE' => ['Salario base', 'INGRESO', true, true, true],
        'BONINC' => ['Bonificación incentivo (Decreto 37-2001)', 'INGRESO', false, false, true],
        'HEXTRA' => ['Horas extra', 'INGRESO', true, true, false],
        'ROTATIVO' => ['Días cubiertos como rotativo', 'INGRESO', false, false, false],
        'BONPROD' => ['Bono de productividad', 'INGRESO', false, true, false],
        'IGSS' => ['Cuota laboral IGSS (4.83 %)', 'DEDUCCION', false, false, true],
        'ISR' => ['Retención ISR', 'DEDUCCION', false, false, true],
        'PRESTAMO' => ['Descuento de préstamo', 'DEDUCCION', false, false, true],
        'AUSENCIA' => ['Descuento por ausencia', 'DEDUCCION', false, false, false],
        'UNIFORME' => ['Descuento de uniforme', 'DEDUCCION', false, false, false],
    ];

    /** Crea los conceptos que falten para la empresa y devuelve codigo => concepto. */
    public static function conceptos(int $idEmpresa): array
    {
        $existentes = ConceptoNomina::query()->where('id_empresa', $idEmpresa)->get()->keyBy('codigo');
        foreach (self::CONCEPTOS as $codigo => [$nombre, $tipo, $igss, $isr, $fijo]) {
            if (! $existentes->has($codigo)) {
                $existentes[$codigo] = ConceptoNomina::create([
                    'id_empresa' => $idEmpresa, 'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo,
                    'afecta_igss' => $igss, 'afecta_isr' => $isr, 'es_fijo' => $fijo, 'activo' => true,
                ]);
            }
        }

        return $existentes->all();
    }

    /** Días del periodo que el empleado estuvo contratado (máximo, los del periodo). */
    public static function diasTrabajados(PeriodoNomina $p, Empleado $e): float
    {
        [$diasPeriodo] = self::PERIODOS[$p->tipo];
        $desde = $e->fecha_ingreso && $e->fecha_ingreso->gt($p->fecha_inicio) ? $e->fecha_ingreso : $p->fecha_inicio;
        $hasta = $e->fecha_baja && $e->fecha_baja->lt($p->fecha_fin) ? $e->fecha_baja : $p->fecha_fin;
        if ($hasta->lt($desde)) {
            return 0;
        }
        $calendario = (int) round(abs($p->fecha_inicio->diffInDays($p->fecha_fin))) + 1;
        $contratado = (int) round(abs($desde->diffInDays($hasta))) + 1;

        // Mes o quincena completos cuentan como 30 o 15 días aunque el mes tenga 31 o 28.
        return $contratado >= $calendario ? (float) $diasPeriodo : round(min($diasPeriodo, $contratado), 2);
    }

    /** Ausencias injustificadas y días hábiles de permisos sin goce aprobados dentro del periodo. */
    public static function diasNoPagados(PeriodoNomina $p, Empleado $e): float
    {
        $ausencias = Asistencia::query()->where('id_empleado', $e->id_empleado)->where('estado', 'AUSENTE')
            ->whereBetween('fecha', [$p->fecha_inicio->toDateString(), $p->fecha_fin->toDateString().' 23:59:59'])->count();
        $sinGoce = SolicitudAusencia::query()->where('id_empleado', $e->id_empleado)->where('estado', 'APROBADO')->where('tipo', 'PERMISO_SIN_GOCE')
            ->whereDate('fecha_inicio', '<=', $p->fecha_fin)->whereDate('fecha_fin', '>=', $p->fecha_inicio)->get()
            ->sum(fn ($s) => AsistenciaController::diasHabiles($s->fecha_inicio->max($p->fecha_inicio), $s->fecha_fin->min($p->fecha_fin)));

        return (float) ($ausencias + $sinGoce);
    }

    /** Coberturas vigentes del rotativo que tocan el periodo. */
    public static function coberturas(PeriodoNomina $p, int $idEmpleado)
    {
        return CoberturaRotativo::query()->where('id_rotativo', $idEmpleado)->vigentes()
            ->entre($p->fecha_inicio->toDateString(), $p->fecha_fin->toDateString())->with('titular')->orderBy('fecha_inicio')->get();
    }

    /** Crea o actualiza el detalle del empleado y lo recalcula. Con salario 0 (rotativo sin contrato) solo cobra sus coberturas. */
    public static function procesarEmpleado(PeriodoNomina $p, Empleado $e, float $salarioMensual): DetalleNomina
    {
        $detalle = DetalleNomina::query()->firstOrNew(['id_periodo' => $p->id_periodo, 'id_empleado' => $e->id_empleado]);
        $detalle->fill([
            'id_empresa' => $p->id_empresa,
            'cargo_snapshot' => $e->cargo?->nombre,
            'salario_base' => $salarioMensual,
            // La primera vez se descuentan las ausencias y se traen las horas extra de Asistencia;
            // después se respetan los ajustes hechos a mano.
            'dias_trabajados' => $detalle->exists ? $detalle->dias_trabajados : ($salarioMensual > 0 ? max(0, self::diasTrabajados($p, $e) - self::diasNoPagados($p, $e)) : 0),
            'horas_extra' => $detalle->exists ? $detalle->horas_extra : (float) Asistencia::query()->where('id_empleado', $e->id_empleado)
                ->whereBetween('fecha', [$p->fecha_inicio->toDateString(), $p->fecha_fin->toDateString().' 23:59:59'])->sum('horas_extra'),
            'estado_pago' => 'CALCULADO',
        ])->save();
        self::recalcular($detalle, $p);

        return $detalle;
    }

    /** Rehace los conceptos automáticos sobre los manuales y actualiza los totales del detalle. */
    public static function recalcular(DetalleNomina $d, PeriodoNomina $p): void
    {
        $conceptos = self::conceptos($p->id_empresa);
        $porId = collect($conceptos)->keyBy('id_concepto');
        [$diasPeriodo, $periodosAnio] = self::PERIODOS[$p->tipo];
        $idsAutomaticos = collect(self::AUTOMATICOS)->map(fn ($c) => $conceptos[$c]->id_concepto)->all();
        DetalleNominaConcepto::query()->where('id_detalle', $d->id_detalle)->whereIn('id_concepto', $idsAutomaticos)->delete();

        $linea = fn (string $codigo, float $monto, ?string $descripcion = null) => $monto > 0
            ? DetalleNominaConcepto::create(['id_detalle' => $d->id_detalle, 'id_concepto' => $conceptos[$codigo]->id_concepto,
                'tipo' => $conceptos[$codigo]->tipo, 'monto' => round($monto, 2), 'descripcion' => $descripcion])
            : null;

        // Fracción del mes que cubre el periodo trabajado.
        $fraccion = ((float) $d->dias_trabajados / $diasPeriodo) * (12 / $periodosAnio);
        $linea('SALBASE', (float) $d->salario_base * $fraccion, rtrim(rtrim(number_format((float) $d->dias_trabajados, 2), '0'), '.').' días');
        $linea('BONINC', self::BONIFICACION_INCENTIVO_MENSUAL * $fraccion);
        $horas = (float) $d->horas_extra;
        $linea('HEXTRA', $horas * (float) $d->salario_base / 30 / 8 * self::RECARGO_HORA_EXTRA, rtrim(rtrim(number_format($horas, 2), '0'), '.').' h al 150 %');

        foreach (self::coberturas($p, $d->id_empleado) as $c) {
            $dias = $c->diasEn($p->fecha_inicio, $p->fecha_fin);
            $linea('ROTATIVO', $dias * (float) $c->tarifa_dia, $dias.' días × '.number_format((float) $c->tarifa_dia, 2)
                .' · '.($c->titular ? 'cubre a '.$c->titular->nombre_completo : 'puesto vacante'));
        }

        $lineas = DetalleNominaConcepto::query()->where('id_detalle', $d->id_detalle)->get();
        $ingresos = $lineas->where('tipo', 'INGRESO');
        $afectoIgss = (float) $ingresos->filter(fn ($l) => $porId[$l->id_concepto]?->afecta_igss)->sum('monto');
        $afectoIsr = (float) $ingresos->filter(fn ($l) => $porId[$l->id_concepto]?->afecta_isr)->sum('monto');

        $igss = round($afectoIgss * self::IGSS_LABORAL, 2);
        $linea('IGSS', $igss);
        $isr = self::isrDelPeriodo($afectoIsr, $igss, $periodosAnio);
        $linea('ISR', $isr);

        // Préstamos activos: la cuota registrada es quincenal.
        foreach (PrestamoEmpleado::query()->where('id_empleado', $d->id_empleado)->where('estado', 'ACTIVO')->where('monto_pendiente', '>', 0)->get() as $prestamo) {
            $cuota = min((float) $prestamo->monto_pendiente, (float) $prestamo->cuota_quincenal * 24 / $periodosAnio);
            $linea('PRESTAMO', $cuota, "Préstamo #{$prestamo->id_prestamo}");
        }

        $lineas = DetalleNominaConcepto::query()->where('id_detalle', $d->id_detalle)->get();
        $totalIngresos = round((float) $lineas->where('tipo', 'INGRESO')->sum('monto'), 2);
        $totalDeducciones = round((float) $lineas->where('tipo', 'DEDUCCION')->sum('monto'), 2);
        $d->update([
            'total_ingresos' => $totalIngresos,
            'total_deducciones' => $totalDeducciones,
            'liquido_pagar' => round($totalIngresos - $totalDeducciones, 2),
            'cuota_igss_emp' => $igss,
            'cuota_igss_pat' => round($afectoIgss * self::IGSS_PATRONAL, 2),
            'isr_retenido' => $isr,
        ]);
    }

    /** ISR del periodo con la renta proyectada a un año. */
    public static function isrDelPeriodo(float $afectoPeriodo, float $igssPeriodo, int $periodosAnio): float
    {
        $renta = $afectoPeriodo * $periodosAnio - self::ISR_DEDUCCION_UNICA - $igssPeriodo * $periodosAnio;
        if ($renta <= 0) {
            return 0.0;
        }
        $anual = $renta <= self::ISR_TRAMO ? $renta * 0.05 : self::ISR_TRAMO * 0.05 + ($renta - self::ISR_TRAMO) * 0.07;

        return round($anual / $periodosAnio, 2);
    }

    /** Totales del periodo desde sus detalles. */
    public static function totalesPeriodo(PeriodoNomina $p): void
    {
        $p->update([
            'total_bruto' => round((float) $p->detalles()->sum('total_ingresos'), 2),
            'total_deducciones' => round((float) $p->detalles()->sum('total_deducciones'), 2),
            'total_neto' => round((float) $p->detalles()->sum('liquido_pagar'), 2),
        ]);
    }

    /** Aplica (+1) o revierte (−1) los descuentos de préstamos del periodo sobre su saldo. */
    public static function aplicarPrestamos(PeriodoNomina $p, int $signo): void
    {
        $idPrestamo = ConceptoNomina::query()->where('id_empresa', $p->id_empresa)->where('codigo', 'PRESTAMO')->value('id_concepto');
        $lineas = DetalleNominaConcepto::query()->where('id_concepto', $idPrestamo)
            ->whereIn('id_detalle', $p->detalles()->pluck('id_detalle'))->get();
        foreach ($lineas as $l) {
            if (! preg_match('/#(\d+)/', (string) $l->descripcion, $m)) {
                continue;
            }
            $prestamo = PrestamoEmpleado::query()->lockForUpdate()->find((int) $m[1]);
            if (! $prestamo) {
                continue;
            }
            $pendiente = round((float) $prestamo->monto_pendiente - $signo * (float) $l->monto, 4);
            $prestamo->update(['monto_pendiente' => max(0, $pendiente), 'estado' => $pendiente <= 0.009 ? 'LIQUIDADO' : 'ACTIVO']);
        }
    }

    /** Nombre sugerido del periodo («Quincena 2 de octubre 2026»). */
    public static function nombreSugerido(string $tipo, Carbon $inicio): string
    {
        $mes = PresupuestoAnual::MESES[$inicio->month].' '.$inicio->year;

        return match ($tipo) {
            'MENSUAL' => 'Nómina de '.$mes,
            'QUINCENAL' => 'Quincena '.($inicio->day <= 15 ? 1 : 2).' de '.$mes,
            default => ucfirst(strtolower($tipo)).' del '.$inicio->format('d/m/Y'),
        };
    }
}

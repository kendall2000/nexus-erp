<?php

namespace App\Support;

use App\Models\RRHH\Empleado;
use Illuminate\Support\Carbon;

/**
 * Salario que regía en cada fecha según el contrato y los cambios de salario con su fecha
 * efectiva (historial_salarial). Un aumento registrado hoy con fecha efectiva futura no cambia
 * lo anterior a esa fecha, y uno con fecha pasada no cambia lo anterior a su fecha.
 *
 * El contrato guarda el último salario acordado (cambiarSalario lo sobrescribe); el original
 * del contrato es el «salario anterior» del primer cambio registrado sobre él.
 */
class Salarios
{
    /** Salario mensual vigente en esa fecha; null si ningún contrato regía ese día. */
    public static function vigente(Empleado $e, Carbon $fecha): ?float
    {
        $fecha = $fecha->copy()->startOfDay();
        $e->loadMissing(['contratos', 'historialSalarial']);
        $contrato = $e->contratos
            ->filter(fn ($c) => $c->fecha_inicio->lte($fecha) && ! ($c->estado === 'RESCINDIDO' && $c->fecha_fin && $c->fecha_fin->lt($fecha)))
            ->sortByDesc('fecha_inicio')->first();
        if (! $contrato) {
            return null;
        }

        // Cambios sobre ese contrato: dentro de su vigencia y sin contar el alta del contrato.
        $cambios = $e->historialSalarial->filter(fn ($h) => $h->tipo_cambio !== 'CONTRATACION'
            && $h->fecha_efectiva->gte($contrato->fecha_inicio)
            && (! $contrato->fecha_fin || $h->fecha_efectiva->lte($contrato->fecha_fin)));

        $aplicado = $cambios->filter(fn ($h) => $h->fecha_efectiva->lte($fecha))
            ->sortBy([['fecha_efectiva', 'desc'], ['id_historial', 'desc']])->first();
        if ($aplicado) {
            return (float) $aplicado->salario_nuevo;
        }
        $primero = $cambios->sortBy('id_historial')->first();

        return (float) ($primero?->salario_anterior ?? $contrato->salario_base);
    }

    /**
     * Salario mensual promedio de los días del rango en que regía un contrato (cada día pesa
     * igual): un cambio a mitad del periodo queda prorrateado. Null si ningún día tenía contrato.
     */
    public static function promedio(Empleado $e, Carbon $desde, Carbon $hasta): ?float
    {
        $suma = 0.0;
        $dias = 0;
        for ($d = $desde->copy()->startOfDay(); $d->lte($hasta); $d->addDay()) {
            if (($s = self::vigente($e, $d)) !== null) {
                $suma += $s;
                $dias++;
            }
        }

        return $dias ? round($suma / $dias, 4) : null;
    }
}

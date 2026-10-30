<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Vencimientos de SLA de tickets: horas corridas o solo de lunes a viernes. */
class Sla
{
    /** Suma $horas a $inicio; si no aplica en fines de semana, sábado y domingo no cuentan. */
    public static function vence(Carbon $inicio, int $horas, bool $finesDeSemana): Carbon
    {
        if ($finesDeSemana) {
            return $inicio->copy()->addHours($horas);
        }
        $momento = $inicio->copy();
        // Un ticket abierto en fin de semana empieza a contar el lunes a las 00:00.
        while ($momento->isWeekend()) {
            $momento = $momento->addDay()->startOfDay();
        }
        for ($pendientes = $horas; $pendientes > 0; $pendientes--) {
            $momento->addHour();
            while ($momento->isWeekend()) {
                $momento->addDay();
            }
        }

        return $momento;
    }

    /** Semáforo: [etiqueta, color] según cuánto falta para $limite. */
    public static function semaforo(?Carbon $limite, ?Carbon $cumplido = null): array
    {
        if (! $limite) {
            return ['Sin SLA', 'secondary'];
        }
        if ($cumplido) {
            return $cumplido->lte($limite) ? ['Cumplido', 'success'] : ['Cumplido tarde', 'danger'];
        }

        return match (true) {
            $limite->isPast() => ['Vencido', 'danger'],
            now()->diffInMinutes($limite) <= 60 => ['Vence pronto', 'warning'],
            default => ['En tiempo', 'success'],
        };
    }
}

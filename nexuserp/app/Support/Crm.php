<?php

namespace App\Support;

use App\Models\CRM\EtapaFunnel;
use Illuminate\Support\Collection;

/** Etapas del embudo de ventas: si la empresa no tiene, se crean las de uso común. */
class Crm
{
    /** [nombre, probabilidad, color, ganada, perdida] en orden. */
    private const ETAPAS = [
        ['Lead nuevo', 5, '#94A3B8', false, false],
        ['Contactado', 15, '#60A5FA', false, false],
        ['Calificado', 25, '#34D399', false, false],
        ['Propuesta enviada', 50, '#FBBF24', false, false],
        ['Negociación', 75, '#F97316', false, false],
        ['Ganada', 100, '#10B981', true, false],
        ['Perdida', 0, '#EF4444', false, true],
    ];

    /** @return Collection<int, EtapaFunnel> etapas activas de la empresa, en orden */
    public static function etapas(int $idEmpresa): Collection
    {
        $etapas = EtapaFunnel::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('orden')->get();
        if ($etapas->isNotEmpty() || EtapaFunnel::query()->where('id_empresa', $idEmpresa)->exists()) {
            return $etapas;
        }
        foreach (self::ETAPAS as $i => [$nombre, $probabilidad, $color, $ganada, $perdida]) {
            EtapaFunnel::create(['id_empresa' => $idEmpresa, 'nombre' => $nombre, 'orden' => $i + 1, 'color_hex' => $color,
                'probabilidad_cierre' => $probabilidad, 'es_ganada' => $ganada, 'es_perdida' => $perdida, 'activo' => true]);
        }

        return EtapaFunnel::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('orden')->get();
    }
}

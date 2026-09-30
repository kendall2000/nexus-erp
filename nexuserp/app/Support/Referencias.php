<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revisa si un registro está en uso antes de eliminarlo (el esquema no tiene
 * todas las llaves foráneas, así que se verifica aquí).
 */
class Referencias
{
    /**
     * @param  array<string, string>  $tablas  tabla => nombre para el mensaje («clientes»)
     * @return string|null «2 clientes y 1 sucursal» o null si nadie lo usa
     */
    public static function enUso(string $columna, int $id, array $tablas): ?string
    {
        $usos = [];
        foreach ($tablas as $tabla => $nombre) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                continue;
            }
            $consulta = DB::table($tabla)->where($columna, $id);
            if (Schema::hasColumn($tabla, 'deleted_at')) {
                $consulta->whereNull('deleted_at');
            }
            if ($n = $consulta->count()) {
                $usos[] = "{$n} {$nombre}";
            }
        }

        if (! $usos) {
            return null;
        }
        $ultimo = array_pop($usos);

        return $usos ? implode(', ', $usos).' y '.$ultimo : $ultimo;
    }
}

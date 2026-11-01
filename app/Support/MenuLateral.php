<?php

namespace App\Support;

use App\Models\Core\Modulo;
use App\Models\Core\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menú lateral dinámico y multinivel (Padre -> Hijo -> Nieto) basado en la tabla «modulo».
 */
class MenuLateral
{
    /** @return Collection<int, array{nombre: string, items: Collection}> */
    public static function para(?Usuario $usuario): Collection
    {
        if (! $usuario) {
            return collect();
        }

        $esAdmin = $usuario->esAdministrador();
        $modulos = Modulo::query()->where('activo', true)->orderBy('orden')->orderBy('id_modulo')->get();

        // Módulos que tienen permiso «ver»: los demás son solo del Administrador.
        $conVer = DB::table('permiso')->join('accion', 'accion.id_accion', '=', 'permiso.id_accion')
            ->where('accion.codigo', 'ver')->pluck('permiso.id_modulo')->map(fn ($id) => (int) $id)->all();

        $visible = fn (Modulo $m) => $esAdmin || (in_array($m->id_modulo, $conVer, true) && $usuario->puede($m->codigo.'.ver'));

        $construirArbol = function (Modulo $m) use (&$construirArbol, $modulos, $visible) {
            $hijosDirectos = $modulos->where('id_modulo_padre', $m->id_modulo);

            $hijosTransformados = $hijosDirectos
                ->map(fn (Modulo $h) => $construirArbol($h))
                ->filter(fn ($h) => $h['hijos']->isNotEmpty() || ($h['ruta'] && $visible($modulos->firstWhere('id_modulo', $h['id_modulo']))))
                ->values();

            return [
                'id_modulo' => $m->id_modulo,
                'nombre' => $m->nombre,
                'icono' => $m->icono ?: 'chevrons-right',
                'ruta' => $m->ruta,
                'hijos' => $hijosTransformados,
            ];
        };

        return $modulos->whereNull('id_modulo_padre')
            ->map($construirArbol)
            // Opción con ruta visible, o submenú con al menos un descendiente visible
            ->filter(fn ($o) => $o['hijos']->isNotEmpty() || ($o['ruta'] && $visible($modulos->firstWhere('id_modulo', $o['id_modulo']))))
            ->groupBy(fn ($o) => $modulos->firstWhere('id_modulo', $o['id_modulo'])->grupo ?: 'General')
            ->map(fn ($items, $grupo) => ['nombre' => $grupo, 'items' => $items->values()])
            ->values();
    }

    /** La opción apunta a la página actual (o a una subpágina suya). */
    public static function activa(?string $ruta): bool
    {
        $ruta = trim((string) $ruta, '/');

        return $ruta !== '' && request()->is($ruta, $ruta.'/*');
    }

    /** Verifica si algún ítem o descendiente está activo recursivamente */
    public static function tieneActivo(array|Collection $item): bool
    {
        $hijos = is_array($item) ? ($item['hijos'] ?? collect()) : ($item->hijos ?? collect());
        $ruta = is_array($item) ? ($item['ruta'] ?? null) : ($item->ruta ?? null);

        if (self::activa($ruta)) {
            return true;
        }

        foreach ($hijos as $hijo) {
            if (self::tieneActivo($hijo)) {
                return true;
            }
        }

        return false;
    }

    /** Limpia cache si existe */
    public static function olvidar(): void
    {
        // Si se implementa cache por usuario se borra aquí.
    }
}


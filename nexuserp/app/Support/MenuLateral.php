<?php

namespace App\Support;

use App\Models\Core\Menu;
use App\Models\Core\Usuario;
use Illuminate\Support\Collection;

/**
 * Menú lateral desde la tabla «menu» (se administra en Gestión de menú).
 * El Administrador ve todo; los demás, las opciones sin roles asignados o
 * asignadas a alguno de sus roles (menu_rol). Los grupos vacíos no se muestran.
 */
class MenuLateral
{
    /** @return Collection<int, array{id: int, nombre: string, items: Collection}> */
    public static function para(Usuario $usuario): Collection
    {
        $esAdmin = $usuario->esAdministrador();
        $idRoles = $usuario->roles()->pluck('rol.id_rol')->all();

        return Menu::query()
            ->with(['hijos' => function ($q) use ($esAdmin, $idRoles) {
                if (! $esAdmin) {
                    $q->where(fn ($s) => $s->whereDoesntHave('roles')
                        ->orWhereHas('roles', fn ($r) => $r->whereIn('menu_rol.id_rol', $idRoles)));
                }
            }])
            ->grupos($usuario->id_empresa)
            ->get()
            ->map(fn (Menu $grupo) => [
                'id' => $grupo->id_menu,
                'nombre' => $grupo->nombre,
                'items' => $grupo->hijos->map(fn (Menu $item) => [
                    'id' => $item->id_menu,
                    'nombre' => $item->nombre,
                    'icono' => $item->icono && $item->icono !== 'NULL' ? $item->icono : 'chevrons-right',
                    'ruta' => $item->ruta && $item->ruta !== 'NULL' ? $item->ruta : '#',
                ])->values(),
            ])
            ->filter(fn ($g) => $g['items']->isNotEmpty())
            ->values();
    }

    /** La opción apunta a la página actual (o a una subpágina suya). */
    public static function activa(string $ruta): bool
    {
        $ruta = trim($ruta, '/');

        return $ruta !== '' && $ruta !== '#' && request()->is($ruta, $ruta.'/*');
    }
}

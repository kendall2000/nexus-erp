<?php

namespace App\Support;

use App\Models\Core\Modulo;
use App\Models\Core\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menú lateral desde la tabla «modulo» (como en sistema-inventario): cada módulo
 * activo con ruta es una opción, agrupada por su «grupo». Se ve si el usuario
 * tiene «modulo.ver»; los módulos sin permisos son solo del Administrador.
 * Los módulos con hijos se muestran como submenú. Grupos vacíos no aparecen.
 */
class MenuLateral
{
    /** @return Collection<int, array{nombre: string, items: Collection}> */
    public static function para(Usuario $usuario): Collection
    {
        $esAdmin = $usuario->esAdministrador();
        $modulos = Modulo::query()->where('activo', true)->orderBy('orden')->orderBy('id_modulo')->get();
        // Módulos que tienen permiso «ver»: los demás son solo del Administrador.
        $conVer = DB::table('permiso')->join('accion', 'accion.id_accion', '=', 'permiso.id_accion')
            ->where('accion.codigo', 'ver')->pluck('permiso.id_modulo')->map(fn ($id) => (int) $id)->all();

        $visible = fn (Modulo $m) => $esAdmin || (in_array($m->id_modulo, $conVer, true) && $usuario->puede($m->codigo.'.ver'));
        $opcion = fn (Modulo $m) => [
            'nombre' => $m->nombre,
            'icono' => $m->icono ?: 'chevrons-right',
            'ruta' => $m->ruta,
            'hijos' => $modulos->where('id_modulo_padre', $m->id_modulo)->filter(fn ($h) => $h->ruta && $visible($h))
                ->map(fn ($h) => ['nombre' => $h->nombre, 'icono' => $h->icono ?: 'chevrons-right', 'ruta' => $h->ruta, 'hijos' => collect()])->values(),
        ];

        return $modulos->whereNull('id_modulo_padre')
            ->map($opcion)
            // Opción con ruta visible, o submenú con al menos un hijo visible.
            ->filter(fn ($o, $i) => $o['hijos']->isNotEmpty() || ($o['ruta'] && $visible($modulos->get($i))))
            ->groupBy(fn ($o, $i) => $modulos->get($i)->grupo ?: 'General')
            ->map(fn ($items, $grupo) => ['nombre' => $grupo, 'items' => $items->values()])
            ->values();
    }

    /** La opción apunta a la página actual (o a una subpágina suya). */
    public static function activa(?string $ruta): bool
    {
        $ruta = trim((string) $ruta, '/');

        return $ruta !== '' && request()->is($ruta, $ruta.'/*');
    }
}

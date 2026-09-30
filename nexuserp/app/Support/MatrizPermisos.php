<?php

namespace App\Support;

use App\Models\Core\Accion;
use App\Models\Core\Modulo;
use App\Models\Core\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de la matriz «módulos × acciones» (como en sistema-inventario) que usan
 * Roles y los permisos extra de Usuarios. Solo aparecen los módulos activos con
 * permisos; los que no tienen son pantallas solo del Administrador.
 */
class MatrizPermisos
{
    /**
     * @return array{acciones: Collection<int, Accion>, grupos: Collection<string, Collection<int, array{modulo: Modulo, permisos: array<int, int>}>>}
     *                  permisos: id_accion => id_permiso
     */
    public static function datos(): array
    {
        $modulos = Modulo::query()->where('activo', true)->with('permisos')->orderBy('orden')->orderBy('id_modulo')->get()
            ->filter(fn (Modulo $m) => $m->permisos->isNotEmpty());
        $idsAccion = $modulos->flatMap(fn ($m) => $m->permisos->pluck('id_accion'))->unique()->all();

        return [
            'acciones' => Accion::query()->whereIn('id_accion', $idsAccion)->orderBy('orden')->get(),
            'grupos' => $modulos->map(fn (Modulo $m) => [
                'modulo' => $m,
                'permisos' => $m->permisos->mapWithKeys(fn ($p) => [(int) $p->id_accion => (int) $p->id_permiso])->all(),
            ])->groupBy(fn ($fila) => $fila['modulo']->grupo ?: 'General'),
        ];
    }

    /**
     * Permisos (ids) que el usuario tiene por sus roles activos y sus extras;
     * null si es Administrador (tiene todos). Se usa para que nadie dé permisos
     * que él mismo no tiene.
     *
     * @return list<int>|null
     */
    public static function idsDe(Usuario $usuario): ?array
    {
        if ($usuario->esAdministrador()) {
            return null;
        }

        return self::idsPorRoles($usuario)->merge(self::idsExtras($usuario))->unique()->values()->all();
    }

    /** @return Collection<int, int> ids de permisos que dan los roles activos del usuario. */
    public static function idsPorRoles(Usuario $usuario): Collection
    {
        return DB::table('rol_permiso')
            ->join('usuario_rol', 'usuario_rol.id_rol', '=', 'rol_permiso.id_rol')
            ->join('rol', 'rol.id_rol', '=', 'rol_permiso.id_rol')
            ->where('usuario_rol.id_usuario', $usuario->id_usuario)->where('rol.activo', true)
            ->pluck('rol_permiso.id_permiso')->map(fn ($id) => (int) $id)->unique()->values();
    }

    /** @return Collection<int, int> ids de los permisos extra del usuario. */
    public static function idsExtras(Usuario $usuario): Collection
    {
        return DB::table('usuario_permiso')->where('id_usuario', $usuario->id_usuario)
            ->pluck('id_permiso')->map(fn ($id) => (int) $id)->values();
    }
}

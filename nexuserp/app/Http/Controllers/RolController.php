<?php

namespace App\Http\Controllers;

use App\Models\Core\Menu;
use App\Models\Core\ModuloSistema;
use App\Models\Core\Permiso;
use App\Models\Core\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Roles y permisos de la empresa. Permisos: CONFIG.ROLES.VER / .GESTIONAR.
 * El Administrador tiene todo y no se renombra, desactiva, limita ni elimina.
 * Para evitar que alguien se dé más acceso, quien no es Administrador no puede
 * editar sus propios roles ni dar permisos que él mismo no tiene.
 */
class RolController extends Controller
{
    public function index(Request $request): View
    {
        $roles = $this->deMiEmpresa($request)
            ->withCount(['usuarios', 'permisos'])
            ->orderBy('nombre')
            ->get();

        return view('roles.index', ['roles' => $roles, 'totalPermisos' => Permiso::count()]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new Rol(['activo' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $permisos = $this->permisosElegidos($request);
        $menu = $this->menuElegido($request);

        $rol = DB::transaction(function () use ($request, $datos, $permisos, $menu) {
            $rol = Rol::create($datos + ['id_empresa' => $request->user()->id_empresa, 'es_rol_sistema' => false]);
            $this->guardarPermisos($rol, $permisos);
            $this->guardarMenu($rol, $menu);

            return $rol;
        });

        return redirect()->route('roles.index')->with('status', "Rol {$rol->nombre} creado.");
    }

    public function edit(Request $request, int $rol): View
    {
        return $this->formulario($request, $this->deMiEmpresa($request)->findOrFail($rol));
    }

    public function update(Request $request, int $rol): RedirectResponse
    {
        $rol = $this->deMiEmpresa($request)->findOrFail($rol);
        if ($error = $this->noEditable($request, $rol)) {
            return back()->withErrors(['rol' => $error]);
        }

        $datos = $this->validar($request, $rol);
        $permisos = $this->permisosElegidos($request);
        $menu = $this->menuElegido($request);

        // Los permisos del rol que quien edita no tiene no aparecen como editables: se conservan.
        if (($propios = $this->permisosPropios($request)) !== null) {
            $actuales = $rol->permisos()->pluck('permiso.id_permiso')->map(fn ($id) => (int) $id)->all();
            $permisos = array_values(array_unique([...$permisos, ...array_diff($actuales, $propios)]));
        }

        DB::transaction(function () use ($rol, $datos, $permisos, $menu) {
            if ($rol->esAdministrador()) {
                // Solo se puede cambiar la descripción, los 2 pasos y el menú.
                $rol->update(['descripcion' => $datos['descripcion'], 'requiere_2fa' => $datos['requiere_2fa']]);
            } else {
                $rol->update($datos);
                $this->guardarPermisos($rol, $permisos);
            }
            $this->guardarMenu($rol, $menu);
        });

        return redirect()->route('roles.index')->with('status', "Rol {$rol->nombre} actualizado.");
    }

    public function destroy(Request $request, int $rol): RedirectResponse
    {
        $rol = $this->deMiEmpresa($request)->withCount('usuarios')->findOrFail($rol);
        if ($rol->esProtegido()) {
            return back()->withErrors(['rol' => "El rol {$rol->nombre} no se puede eliminar."]);
        }
        if ($rol->usuarios_count > 0) {
            return back()->withErrors(['rol' => 'No se puede eliminar un rol que tiene usuarios asignados.']);
        }
        if ($error = $this->noEditable($request, $rol)) {
            return back()->withErrors(['rol' => $error]);
        }

        DB::transaction(function () use ($rol) {
            $rol->permisos()->detach();
            DB::table('menu_rol')->where('id_rol', $rol->id_rol)->delete();
            $rol->delete();
        });

        return redirect()->route('roles.index')->with('status', "Rol {$rol->nombre} eliminado.");
    }

    private function formulario(Request $request, Rol $rol): View
    {
        return view('roles.form', [
            'rol' => $rol,
            'soloLectura' => $rol->exists ? $this->noEditable($request, $rol) : null,
            'modulos' => ModuloSistema::query()->where('activo', true)->with(['permisos' => fn ($q) => $q->orderBy('codigo')])
                ->orderBy('orden_menu')->get()->filter(fn ($m) => $m->permisos->isNotEmpty()),
            'grupos' => Menu::query()->grupos($request->user()->id_empresa)->with('hijos')->get()
                ->filter(fn ($g) => $g->hijos->isNotEmpty()),
            'permisosMarcados' => $rol->exists ? $rol->permisos()->pluck('permiso.id_permiso')->map(fn ($id) => (int) $id)->all() : [],
            'menuMarcado' => $rol->exists ? DB::table('menu_rol')->where('id_rol', $rol->id_rol)->pluck('id_menu')->map(fn ($id) => (int) $id)->all() : [],
            'permisosPropios' => $this->permisosPropios($request),
        ]);
    }

    /** Motivo por el que el usuario actual no puede modificar este rol (null si puede). */
    private function noEditable(Request $request, Rol $rol): ?string
    {
        $usuario = $request->user();
        if ($rol->es_rol_sistema) {
            return 'Los roles del sistema no se pueden modificar.';
        }
        if (! $usuario->esAdministrador() && $usuario->roles()->where('rol.id_rol', $rol->id_rol)->exists()) {
            return 'No puedes modificar un rol que tú mismo tienes. Pídeselo al Administrador.';
        }
        if (! $usuario->esAdministrador() && $rol->esAdministrador()) {
            return 'Solo el Administrador puede modificar el rol Administrador.';
        }

        return null;
    }

    private function deMiEmpresa(Request $request)
    {
        return Rol::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Rol $rol = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $request->merge(['nombre' => trim((string) $request->input('nombre'))]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100',
                Rule::unique('rol', 'nombre')->where('id_empresa', $idEmpresa)->ignore($rol?->id_rol, 'id_rol'),
                ...($rol?->esAdministrador() ? [] : [Rule::notIn([Rol::ADMINISTRADOR])])],
            'descripcion' => ['nullable', 'string', 'max:300'],
        ], [
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
            'nombre.not_in' => 'Ese nombre está reservado para el rol Administrador.',
        ]);

        return $datos + [
            'requiere_2fa' => $request->boolean('requiere_2fa'),
            // El Administrador siempre queda activo.
            'activo' => $rol?->esAdministrador() ? true : $request->boolean('activo'),
        ];
    }

    /** @return list<int> Permisos marcados; quien no es Administrador solo puede dar los que tiene. */
    private function permisosElegidos(Request $request): array
    {
        $request->validate([
            'permisos' => ['array'],
            'permisos.*' => ['integer', Rule::exists('permiso', 'id_permiso')],
        ]);
        $ids = array_values(array_unique(array_map('intval', $request->input('permisos', []))));

        $propios = $this->permisosPropios($request);
        if ($propios !== null && array_diff($ids, $propios)) {
            abort(403, 'No puedes dar permisos que tú no tienes.');
        }

        return $ids;
    }

    /** @return list<int> Opciones del menú marcadas (de la empresa). */
    private function menuElegido(Request $request): array
    {
        $request->validate([
            'menu' => ['array'],
            'menu.*' => ['integer', Rule::exists('menu', 'id_menu')->where('id_empresa', $request->user()->id_empresa)],
        ]);

        return array_values(array_unique(array_map('intval', $request->input('menu', []))));
    }

    /** @return list<int>|null Permisos del usuario actual; null = Administrador (todos). */
    private function permisosPropios(Request $request): ?array
    {
        $usuario = $request->user();
        if ($usuario->esAdministrador()) {
            return null;
        }

        return DB::table('rol_permiso')
            ->join('usuario_rol', 'usuario_rol.id_rol', '=', 'rol_permiso.id_rol')
            ->join('rol', 'rol.id_rol', '=', 'rol_permiso.id_rol')
            ->where('usuario_rol.id_usuario', $usuario->id_usuario)
            ->where('rol.activo', true)
            ->pluck('rol_permiso.id_permiso')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** Permiso asignado = permitido (el código ya indica la acción: VER, CREAR, EDITAR…). */
    private function guardarPermisos(Rol $rol, array $ids): void
    {
        $todas = ['puede_crear' => true, 'puede_leer' => true, 'puede_editar' => true, 'puede_eliminar' => true, 'puede_exportar' => true];
        $rol->permisos()->sync(array_fill_keys($ids, $todas));
    }

    private function guardarMenu(Rol $rol, array $ids): void
    {
        DB::table('menu_rol')->where('id_rol', $rol->id_rol)->delete();
        DB::table('menu_rol')->insert(array_map(fn ($id) => ['id_menu' => $id, 'id_rol' => $rol->id_rol], $ids));
    }
}

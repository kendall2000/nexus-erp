<?php

namespace App\Http\Controllers;

use App\Models\Core\Permiso;
use App\Models\Core\Rol;
use App\Support\Bitacora;
use App\Support\MatrizPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Roles y permisos (matriz módulos × acciones, como en sistema-inventario).
 * Permisos: roles.ver / .crear / .editar / .eliminar. El Administrador tiene todo
 * y no se renombra, desactiva, limita ni elimina. Para evitar que alguien se dé
 * más acceso, quien no es Administrador no puede editar sus propios roles ni dar
 * permisos que él mismo no tiene. Lo que ve cada rol en el menú sale del permiso «ver».
 */
class RolController extends Controller
{
    public function index(Request $request): View
    {
        $roles = $this->deMiEmpresa($request)->withCount(['usuarios', 'permisos'])->orderBy('nombre')->get();

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

        $rol = DB::transaction(function () use ($request, $datos, $permisos) {
            $rol = Rol::create($datos + ['id_empresa' => $request->user()->id_empresa, 'es_rol_sistema' => false]);
            $rol->permisos()->sync($permisos);
            Bitacora::registrarLista('rol_permiso', (string) $rol->id_rol, 'permisos', [], Bitacora::codigosPermiso($permisos), $rol->id_empresa);

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

        // Los permisos del rol que quien edita no tiene no aparecen como editables: se conservan.
        if (($propios = MatrizPermisos::idsDe($request->user())) !== null) {
            $actuales = $rol->permisos()->pluck('permiso.id_permiso')->map(fn ($id) => (int) $id)->all();
            $permisos = array_values(array_unique([...$permisos, ...array_diff($actuales, $propios)]));
        }

        DB::transaction(function () use ($rol, $datos, $permisos) {
            if ($rol->esAdministrador()) {
                // Tiene todo: solo cambian la descripción y los 2 pasos.
                $rol->update(['descripcion' => $datos['descripcion'], 'requiere_2fa' => $datos['requiere_2fa']]);
            } else {
                $rol->update($datos);
                $antes = Bitacora::codigosPermiso($rol->permisos()->pluck('permiso.id_permiso'));
                $rol->permisos()->sync($permisos);
                Bitacora::registrarLista('rol_permiso', (string) $rol->id_rol, 'permisos', $antes, Bitacora::codigosPermiso($permisos), $rol->id_empresa);
            }
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
            Bitacora::registrarLista('rol_permiso', (string) $rol->id_rol, 'permisos', Bitacora::codigosPermiso($rol->permisos()->pluck('permiso.id_permiso')), [], $rol->id_empresa);
            $rol->permisos()->detach();
            $rol->delete();
        });

        return redirect()->route('roles.index')->with('status', "Rol {$rol->nombre} eliminado.");
    }

    private function formulario(Request $request, Rol $rol): View
    {
        return view('roles.form', [
            'rol' => $rol,
            'soloLectura' => $rol->exists ? $this->noEditable($request, $rol) : null,
            'matriz' => MatrizPermisos::datos(),
            'marcados' => $rol->exists ? $rol->permisos()->pluck('permiso.id_permiso')->map(fn ($id) => (int) $id)->all() : [],
            'propios' => MatrizPermisos::idsDe($request->user()),
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
            return 'Solo un administrador puede modificar los roles con acceso total.';
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
                // Un nombre de acceso total (Administrador, Superadmin…) da todos los permisos:
                // solo quien ya tiene acceso total puede crearlo o ponérselo a otro rol.
                function (string $atributo, mixed $valor, \Closure $fallar) use ($request, $rol) {
                    if (! $rol?->esAdministrador() && Rol::esNombreDeAccesoTotal((string) $valor) && ! $request->user()->esAdministrador()) {
                        $fallar('Ese nombre está reservado para los roles con acceso total ('.implode(', ', Rol::ACCESO_TOTAL).').');
                    }
                }],
            'descripcion' => ['nullable', 'string', 'max:300'],
        ], [
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
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

        $propios = MatrizPermisos::idsDe($request->user());
        if ($propios !== null && array_diff($ids, $propios)) {
            abort(403, 'No puedes dar permisos que tú no tienes.');
        }

        return $ids;
    }
}

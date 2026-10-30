<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\Core\Rol;
use App\Models\Core\Sucursal;
use App\Models\Core\Usuario;
use App\Support\Archivos;
use App\Support\Bitacora;
use App\Support\Contrasenas;
use App\Support\MatrizPermisos;
use App\Support\Seguridad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Usuarios de la empresa. No hay registro público: las cuentas se crean aquí.
 * Permisos: CONFIG.USUARIOS.VER / .CREAR / .EDITAR (el Administrador pasa siempre).
 */
class UsuarioController extends Controller
{
    use PasswordValidationRules;

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));

        $usuarios = $this->deMiEmpresa($request)
            ->with(['roles', 'sucursal'])
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre_completo', 'like', "%{$buscar}%")
                ->orWhere('username', 'like', "%{$buscar}%")
                ->orWhere('email', 'like', "%{$buscar}%")))
            ->orderBy('nombre_completo')
            ->paginate(25)->withQueryString();

        return view('usuarios.index', ['usuarios' => $usuarios, 'buscar' => $buscar]);
    }

    public function create(Request $request): View
    {
        return view('usuarios.form', [
            'usuario' => new Usuario(['activo' => true]),
            'idRol' => null,
            'roles' => $this->roles($request),
            'sucursales' => $this->sucursales($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        $usuario = new Usuario([
            'id_empresa' => $request->user()->id_empresa,
            'id_sucursal' => $datos['id_sucursal'] ?? null,
            'nombre_completo' => $datos['nombre_completo'],
            'username' => $datos['username'],
            'email' => $datos['email'],
            'activo' => true,
            'intentos_fallidos' => 0,
        ]);
        // La contraseña la pone el administrador: por defecto el usuario debe cambiarla al entrar.
        Contrasenas::cambiar($usuario, $datos['password'], $request->boolean('debe_cambiar_password'));
        $this->asignarRol($request, $usuario, (int) $datos['id_rol']);
        if ($error = $this->guardarFoto($request, $usuario)) {
            return redirect()->route('usuarios.edit', $usuario->id_usuario)
                ->with('status', "Usuario {$usuario->username} creado.")->withErrors(['foto' => $error]);
        }

        return redirect()->route('usuarios.index')->with('status', "Usuario {$usuario->username} creado.");
    }

    public function edit(Request $request, int $usuario): View
    {
        $usuario = $this->gestionable($request, $usuario, ['roles']);

        return view('usuarios.form', [
            'usuario' => $usuario,
            'idRol' => $usuario->roles->first()?->id_rol,
            'roles' => $this->roles($request, $usuario->roles->first()?->id_rol),
            'sucursales' => $this->sucursales($request),
            // Permisos extra (además de los del rol), como en sistema-inventario.
            'matriz' => MatrizPermisos::datos(),
            'heredados' => MatrizPermisos::idsPorRoles($usuario)->all(),
            'extras' => MatrizPermisos::idsExtras($usuario)->all(),
            'propios' => MatrizPermisos::idsDe($request->user()),
        ]);
    }

    /**
     * Reemplaza los permisos extra del usuario. Quien no es Administrador solo puede
     * dar los que él tiene; los extras que no puede tocar se conservan.
     */
    public function permisos(Request $request, int $usuario): RedirectResponse
    {
        $usuario = $this->gestionable($request, $usuario);
        $request->validate([
            'permisos' => ['array'],
            'permisos.*' => ['integer', Rule::exists('permiso', 'id_permiso')],
        ]);
        $ids = array_values(array_unique(array_map('intval', $request->input('permisos', []))));
        // Los que ya da su rol no se guardan como extra.
        $ids = array_values(array_diff($ids, MatrizPermisos::idsPorRoles($usuario)->all()));

        $propios = MatrizPermisos::idsDe($request->user());
        if ($propios !== null) {
            if (array_diff($ids, $propios)) {
                abort(403, 'No puedes dar permisos que tú no tienes.');
            }
            $ids = array_values(array_unique([...$ids, ...array_diff(MatrizPermisos::idsExtras($usuario)->all(), $propios)]));
        }

        DB::transaction(function () use ($request, $usuario, $ids) {
            $antes = Bitacora::codigosPermiso(MatrizPermisos::idsExtras($usuario));
            DB::table('usuario_permiso')->where('id_usuario', $usuario->id_usuario)->whereNotIn('id_permiso', $ids)->delete();
            $existentes = MatrizPermisos::idsExtras($usuario)->all();
            DB::table('usuario_permiso')->insert(array_map(fn ($id) => [
                'id_usuario' => $usuario->id_usuario, 'id_permiso' => $id,
                'asignado_por' => $request->user()->id_usuario, 'asignado_at' => now(),
            ], array_values(array_diff($ids, $existentes))));
            Bitacora::registrarLista('usuario_permiso', (string) $usuario->id_usuario, 'permisos', $antes, Bitacora::codigosPermiso($ids), $usuario->id_empresa);
        });

        $n = count($ids);

        return redirect()->route('usuarios.edit', $usuario->id_usuario)
            ->with('status', "Permisos extra de {$usuario->username} guardados ({$n} ".($n === 1 ? 'permiso' : 'permisos').').');
    }

    public function update(Request $request, int $usuario): RedirectResponse
    {
        $usuario = $this->gestionable($request, $usuario, ['roles']);
        $datos = $this->validar($request, $usuario);
        $esYo = $usuario->is($request->user());

        if ($esYo && (int) $datos['id_rol'] !== (int) $usuario->roles->first()?->id_rol) {
            return back()->withErrors(['id_rol' => 'No puedes cambiar tu propio rol.'])->withInput();
        }

        $usuario->update([
            'id_sucursal' => $datos['id_sucursal'] ?? null,
            'nombre_completo' => $datos['nombre_completo'],
            'username' => $datos['username'],
            'email' => $datos['email'],
        ]);
        $this->asignarRol($request, $usuario, (int) $datos['id_rol']);
        $errorFoto = $this->guardarFoto($request, $usuario);

        if (! empty($datos['password'])) {
            $usuario->forceFill(['intentos_fallidos' => 0, 'bloqueado_hasta' => null]);
            Contrasenas::cambiar($usuario, $datos['password'], ! $esYo && $request->boolean('debe_cambiar_password'));
            if (! $esYo) {
                // Contraseña restablecida por otra persona: se cierran sus sesiones.
                Seguridad::cerrarSesiones($usuario->id_usuario);
                Seguridad::registrar('RESET_PASSWORD', $usuario->username, $usuario->id_usuario, 'Restablecida por '.$request->user()->username);
            }
        } elseif (! $esYo) {
            // Sin contraseña nueva también se puede pedir (o quitar) el cambio al entrar.
            $usuario->forceFill(['debe_cambiar_password' => $request->boolean('debe_cambiar_password')])->save();
        }

        if ($errorFoto) {
            return redirect()->route('usuarios.edit', $usuario->id_usuario)
                ->with('status', "Usuario {$usuario->username} actualizado.")->withErrors(['foto' => $errorFoto]);
        }

        return redirect()->route('usuarios.index')->with('status', "Usuario {$usuario->username} actualizado.");
    }

    /** Activar / desactivar. Al desactivar se cierran sus sesiones. */
    public function estado(Request $request, int $usuario): RedirectResponse
    {
        $usuario = $this->gestionable($request, $usuario);
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puedes desactivarte a ti mismo.']);
        }

        $usuario->update(['activo' => ! $usuario->activo]);
        if (! $usuario->activo) {
            Seguridad::cerrarSesiones($usuario->id_usuario);
        }

        return back()->with('status', $usuario->activo
            ? "Usuario {$usuario->username} activado."
            : "Usuario {$usuario->username} desactivado y sus sesiones cerradas.");
    }

    public function sesiones(Request $request, int $usuario): RedirectResponse
    {
        $usuario = $this->gestionable($request, $usuario);
        $n = Seguridad::cerrarSesiones($usuario->id_usuario);
        Seguridad::registrar('SESION_CERRADA', $usuario->username, $usuario->id_usuario, "{$n} sesiones · por ".$request->user()->username);

        return back()->with('status', "Se cerraron {$n} sesiones de {$usuario->username}.");
    }

    /** Eliminación lógica (queda en la base con deleted_at). */
    public function destroy(Request $request, int $usuario): RedirectResponse
    {
        $usuario = $this->gestionable($request, $usuario);
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puedes eliminar tu propia cuenta.']);
        }

        Seguridad::cerrarSesiones($usuario->id_usuario);
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('status', "Usuario {$usuario->username} eliminado.");
    }

    private function deMiEmpresa(Request $request)
    {
        return Usuario::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** Usuario de mi empresa que puedo gestionar: nadie toca a quien tiene más acceso que él. */
    private function gestionable(Request $request, int $id, array $con = []): Usuario
    {
        $usuario = $this->deMiEmpresa($request)->with($con)->findOrFail($id);
        abort_unless($request->user()->puedeGestionar($usuario), 403,
            'Este usuario tiene más acceso que tú: solo un administrador puede modificarlo.');

        return $usuario;
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Usuario $usuario = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        return $request->validate([
            'nombre_completo' => ['required', 'string', 'max:200'],
            'username' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('usuario', 'username')->ignore($usuario?->id_usuario, 'id_usuario')],
            'email' => ['required', 'email', 'max:150',
                Rule::unique('usuario', 'email')->ignore($usuario?->id_usuario, 'id_usuario')],
            'id_rol' => ['required', Rule::exists('rol', 'id_rol')->where('id_empresa', $idEmpresa)->where('activo', true),
                // Sin escalada: no se asigna un rol con acceso total ni con permisos que uno no tiene
                // (si el usuario ya lo tenía, se puede conservar).
                function (string $atributo, mixed $valor, \Closure $fallar) use ($request, $usuario) {
                    $rol = Rol::query()->find((int) $valor);
                    $yaLoTiene = $usuario && $usuario->roles->contains('id_rol', (int) $valor);
                    if ($rol && ! $yaLoTiene && ! $request->user()->puedeAsignarRol($rol)) {
                        $fallar('No puedes asignar un rol con más acceso que el tuyo.');
                    }
                }],
            'id_sucursal' => ['nullable', Rule::exists('sucursal', 'id_sucursal')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'password' => array_filter([$usuario ? 'nullable' : 'required', 'string', Password::default(), 'confirmed', $usuario ? $this->noRepetida($usuario) : null]),
            'debe_cambiar_password' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'username.regex' => 'El usuario solo puede tener letras minúsculas, números, punto, guion y guion bajo (sin espacios).',
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'email.unique' => 'Este correo ya está registrado.',
        ]);
    }

    /**
     * Foto de perfil en Contabo (carpeta usuarios/). Devuelve el error si la subida falla:
     * el usuario ya quedó guardado y se avisa sin perder lo demás.
     */
    private function guardarFoto(Request $request, Usuario $usuario): ?string
    {
        if ($request->boolean('quitar_foto') && $usuario->avatar_url) {
            Archivos::borrar($usuario->avatar_url);
            $usuario->update(['avatar_url' => null]);
        }
        if (! $request->hasFile('foto')) {
            return null;
        }
        try {
            $anterior = $usuario->avatar_url;
            $usuario->update(['avatar_url' => Archivos::subir($request->file('foto'), 'usuarios', 'foto')]);
            Archivos::borrar($anterior);

            return null;
        } catch (ValidationException $e) {
            return $e->validator->errors()->first('foto');
        }
    }

    /** Un rol por usuario (como en la pantalla anterior); si no cambió, se conserva su fecha de asignación. */
    private function asignarRol(Request $request, Usuario $usuario, int $idRol): void
    {
        $actuales = array_map('intval', $usuario->roles()->pluck('rol.id_rol')->all());
        if ($actuales === [$idRol]) {
            return;
        }
        $nombres = fn (array $ids) => Rol::query()->whereIn('id_rol', $ids)->pluck('nombre');
        Bitacora::registrarLista('usuario_rol', (string) $usuario->id_usuario, 'roles', $nombres($actuales), $nombres([$idRol]), $usuario->id_empresa);
        $usuario->roles()->sync([$idRol => [
            'fecha_asignacion' => now()->toDateString(),
            'asignado_por' => $request->user()->id_usuario,
        ]]);
    }

    /** Roles que puedo asignar (más el que ya tiene el usuario, para no perderlo al guardar). */
    private function roles(Request $request, ?int $actual = null)
    {
        $yo = $request->user();

        return Rol::query()->where('id_empresa', $yo->id_empresa)->where('activo', true)->orderBy('nombre')->get()
            ->filter(fn (Rol $rol) => $rol->id_rol === $actual || $yo->puedeAsignarRol($rol))->values();
    }

    private function sucursales(Request $request)
    {
        return Sucursal::query()->where('id_empresa', $request->user()->id_empresa)->where('activo', true)->orderBy('nombre')->get();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Core\Rol;
use App\Models\Core\Sucursal;
use App\Models\Core\Usuario;
use App\Support\Archivos;
use App\Support\Seguridad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $usuario = Usuario::create([
            'id_empresa' => $request->user()->id_empresa,
            'id_sucursal' => $datos['id_sucursal'] ?? null,
            'nombre_completo' => $datos['nombre_completo'],
            'username' => $datos['username'],
            'email' => $datos['email'],
            'password_hash' => Hash::make($datos['password']),
            'activo' => true,
            'intentos_fallidos' => 0,
        ]);
        $this->asignarRol($request, $usuario, (int) $datos['id_rol']);
        if ($error = $this->guardarFoto($request, $usuario)) {
            return redirect()->route('usuarios.edit', $usuario->id_usuario)
                ->with('status', "Usuario {$usuario->username} creado.")->withErrors(['foto' => $error]);
        }

        return redirect()->route('usuarios.index')->with('status', "Usuario {$usuario->username} creado.");
    }

    public function edit(Request $request, int $usuario): View
    {
        $usuario = $this->deMiEmpresa($request)->with('roles')->findOrFail($usuario);

        return view('usuarios.form', [
            'usuario' => $usuario,
            'idRol' => $usuario->roles->first()?->id_rol,
            'roles' => $this->roles($request),
            'sucursales' => $this->sucursales($request),
        ]);
    }

    public function update(Request $request, int $usuario): RedirectResponse
    {
        $usuario = $this->deMiEmpresa($request)->with('roles')->findOrFail($usuario);
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
            $usuario->forceFill([
                'password_hash' => Hash::make($datos['password']),
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ])->save();
            if (! $esYo) {
                // Contraseña restablecida por otra persona: se cierran sus sesiones.
                Seguridad::cerrarSesiones($usuario->id_usuario);
                $usuario->tokens()->delete();
                Seguridad::registrar('RESET_PASSWORD', $usuario->username, $usuario->id_usuario, 'Restablecida por '.$request->user()->username);
            }
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
        $usuario = $this->deMiEmpresa($request)->findOrFail($usuario);
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puedes desactivarte a ti mismo.']);
        }

        $usuario->update(['activo' => ! $usuario->activo]);
        if (! $usuario->activo) {
            Seguridad::cerrarSesiones($usuario->id_usuario);
            $usuario->tokens()->delete();
        }

        return back()->with('status', $usuario->activo
            ? "Usuario {$usuario->username} activado."
            : "Usuario {$usuario->username} desactivado y sus sesiones cerradas.");
    }

    public function sesiones(Request $request, int $usuario): RedirectResponse
    {
        $usuario = $this->deMiEmpresa($request)->findOrFail($usuario);
        $n = Seguridad::cerrarSesiones($usuario->id_usuario);
        Seguridad::registrar('SESION_CERRADA', $usuario->username, $usuario->id_usuario, "{$n} sesiones · por ".$request->user()->username);

        return back()->with('status', "Se cerraron {$n} sesiones de {$usuario->username}.");
    }

    /** Eliminación lógica (queda en la base con deleted_at). */
    public function destroy(Request $request, int $usuario): RedirectResponse
    {
        $usuario = $this->deMiEmpresa($request)->findOrFail($usuario);
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puedes eliminar tu propia cuenta.']);
        }

        Seguridad::cerrarSesiones($usuario->id_usuario);
        $usuario->tokens()->delete();
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('status', "Usuario {$usuario->username} eliminado.");
    }

    private function deMiEmpresa(Request $request)
    {
        return Usuario::query()->where('id_empresa', $request->user()->id_empresa);
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
            'id_rol' => ['required', Rule::exists('rol', 'id_rol')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'id_sucursal' => ['nullable', Rule::exists('sucursal', 'id_sucursal')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'password' => [$usuario ? 'nullable' : 'required', 'string', Password::default(), 'confirmed'],
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
        if (array_map('intval', $usuario->roles()->pluck('rol.id_rol')->all()) === [$idRol]) {
            return;
        }
        $usuario->roles()->sync([$idRol => [
            'fecha_asignacion' => now()->toDateString(),
            'asignado_por' => $request->user()->id_usuario,
        ]]);
    }

    private function roles(Request $request)
    {
        return Rol::query()->where('id_empresa', $request->user()->id_empresa)->where('activo', true)->orderBy('nombre')->get();
    }

    private function sucursales(Request $request)
    {
        return Sucursal::query()->where('id_empresa', $request->user()->id_empresa)->where('activo', true)->orderBy('nombre')->get();
    }
}

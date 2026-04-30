<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Usuario;
use App\Models\Core\Empresa;
use App\Models\Core\Sucursal;
use App\Models\Core\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/usuarios
    // ────────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $usuarios = Usuario::with(['empresa', 'sucursal', 'roles'])
            ->where('id_empresa', $idEmpresa)
            ->whereNull('deleted_at')
            ->orderBy('nombre_completo')
            ->get()
            ->map(fn($u) => [
                'id_usuario'      => $u->id_usuario,
                'nombre_completo' => $u->nombre_completo,
                'username'        => $u->username,
                'email'           => $u->email,
                'avatar_url'      => $u->avatar_url,
                'sucursal'        => $u->sucursal?->nombre ?? '—',
                'id_sucursal'     => $u->id_sucursal,
                'roles'           => $u->roles->pluck('nombre')->join(', ') ?: '—',
                'activo'          => $u->activo ? 'Activo' : 'Inactivo',
                'ultimo_login'    => $u->ultimo_login?->format('d/m/Y H:i') ?? 'Nunca',
                'created_at'      => $u->created_at?->format('d/m/Y'),
            ]);

        return response()->json(['success' => true, 'data' => $usuarios]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/v1/usuarios
    // ────────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $request->validate([
            'nombre_completo' => 'required|string|max:200',
            'username'        => 'required|string|max:60|unique:usuario,username',
            'email'           => 'required|email|max:150|unique:usuario,email',
            'password'        => 'required|string|min:8',
            'id_sucursal'     => 'nullable|exists:sucursal,id_sucursal',
            'id_rol'          => 'nullable|exists:rol,id_rol',
        ], [
            'username.unique'  => 'Este nombre de usuario ya está en uso.',
            'email.unique'     => 'Este correo ya está registrado.',
            'password.min'     => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $usuario = Usuario::create([
            'id_empresa'      => $idEmpresa,
            'id_sucursal'     => $request->id_sucursal,
            'nombre_completo' => $request->nombre_completo,
            'username'        => $request->username,
            'email'           => $request->email,
            'password_hash'   => Hash::make($request->password),
            'activo'          => true,
            'intentos_fallidos' => 0,
        ]);

        // Asignar rol si viene
        if ($request->id_rol) {
            $usuario->roles()->attach($request->id_rol, [
                'fecha_asignacion' => now()->toDateString(),
                'asignado_por'     => $request->user()->id_usuario,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'data'    => ['id_usuario' => $usuario->id_usuario],
        ], 201);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/usuarios/{id}
    // ────────────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::with(['sucursal', 'roles'])
            ->where('id_empresa', $request->user()->id_empresa)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id_usuario'      => $usuario->id_usuario,
                'nombre_completo' => $usuario->nombre_completo,
                'username'        => $usuario->username,
                'email'           => $usuario->email,
                'avatar_url'      => $usuario->avatar_url,
                'id_sucursal'     => $usuario->id_sucursal,
                'activo'          => $usuario->activo,
                'roles'           => $usuario->roles->pluck('id_rol'),
                'ultimo_login'    => $usuario->ultimo_login?->format('d/m/Y H:i'),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PUT /api/v1/usuarios/{id}
    // ────────────────────────────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $usuario = Usuario::where('id_empresa', $idEmpresa)->findOrFail($id);

        $request->validate([
            'nombre_completo' => 'required|string|max:200',
            'username'        => ['required','string','max:60', Rule::unique('usuario','username')->ignore($id, 'id_usuario')],
            'email'           => ['required','email','max:150', Rule::unique('usuario','email')->ignore($id, 'id_usuario')],
            'id_sucursal'     => 'nullable|exists:sucursal,id_sucursal',
            'id_rol'          => 'nullable|exists:rol,id_rol',
        ]);

        $usuario->update([
            'nombre_completo' => $request->nombre_completo,
            'username'        => $request->username,
            'email'           => $request->email,
            'id_sucursal'     => $request->id_sucursal,
            'avatar_url'      => $request->avatar_url,
        ]);

        // Actualizar rol
        if ($request->has('id_rol')) {
            $usuario->roles()->sync($request->id_rol ? [
                $request->id_rol => [
                    'fecha_asignacion' => now()->toDateString(),
                    'asignado_por'     => $request->user()->id_usuario,
                ]
            ] : []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PATCH /api/v1/usuarios/{id}/toggle
    // Activa o desactiva el usuario
    // ────────────────────────────────────────────────────────────────────────
    public function toggle(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::where('id_empresa', $request->user()->id_empresa)
                          ->findOrFail($id);

        // No puede desactivarse a sí mismo
        if ($usuario->id_usuario === $request->user()->id_usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propia cuenta.',
            ], 422);
        }

        $usuario->update(['activo' => !$usuario->activo]);

        return response()->json([
            'success' => true,
            'message' => $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PATCH /api/v1/usuarios/{id}/reset-password
    // ────────────────────────────────────────────────────────────────────────
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'password_nuevo' => 'required|string|min:8',
        ]);

        $usuario = Usuario::where('id_empresa', $request->user()->id_empresa)
                          ->findOrFail($id);

        $usuario->update([
            'password_hash'     => Hash::make($request->password_nuevo),
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
        ]);

        // Revocar todos los tokens activos del usuario
        $usuario->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida. Las sesiones activas fueron cerradas.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // DELETE /api/v1/usuarios/{id}
    // Soft delete
    // ────────────────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::where('id_empresa', $request->user()->id_empresa)
                          ->findOrFail($id);

        if ($usuario->id_usuario === $request->user()->id_usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propia cuenta.',
            ], 422);
        }

        $usuario->tokens()->delete();
        $usuario->delete(); // SoftDelete

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/usuarios/catalogos
    // Retorna sucursales y roles para los selects del formulario
    // ────────────────────────────────────────────────────────────────────────
    public function catalogos(Request $request): JsonResponse
    {
        $idEmpresa = $request->user()->id_empresa;

        $sucursales = Sucursal::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->get(['id_sucursal as id', 'nombre as name']);

        $roles = Rol::where('id_empresa', $idEmpresa)
            ->where('activo', true)
            ->get(['id_rol as id', 'nombre as name']);

        return response()->json([
            'success' => true,
            'data'    => [
                'sucursales' => $sucursales,
                'roles'      => $roles,
            ],
        ]);
    }
}
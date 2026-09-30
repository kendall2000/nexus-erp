<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Http\Resources\Core\UsuarioResource;
use App\Models\Core\AuditoriaAcceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * El inicio y cierre de sesión los maneja Fortify por sesión web (POST /login,
 * POST /logout). Aquí solo queda lo que usan las pantallas actuales.
 */
class AuthController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/auth/me
    // Retorna el usuario autenticado con sus permisos
    // ────────────────────────────────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user()->load(['empresa', 'sucursal', 'roles.permisos']);

        return response()->json([
            'success' => true,
            'data'    => new UsuarioResource($usuario),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/cambiar-password
    // ────────────────────────────────────────────────────────────────────────
    public function cambiarPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password_actual' => ['required', 'string'],
            'password_nuevo'  => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_nuevo.min'       => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password_nuevo.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $usuario = $request->user();

        // Verifica contraseña actual
        if (!Hash::check($request->password_actual, $usuario->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta.',
            ], 422);
        }

        // Actualiza contraseña
        $usuario->update([
            'password_hash' => Hash::make($request->password_nuevo),
        ]);

        // Revoca los tokens antiguos que aún no hayan expirado
        $usuario->tokens()->delete();

        AuditoriaAcceso::registrar(
            'CAMBIO_PASSWORD',
            $usuario->username,
            $request->ip(),
            $usuario->id_usuario,
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }
}

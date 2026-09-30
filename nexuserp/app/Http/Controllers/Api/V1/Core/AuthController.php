<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Http\Resources\Core\UsuarioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * El inicio y cierre de sesión los maneja Fortify por sesión web (POST /login,
 * POST /logout) y el cambio de contraseña, desde «Seguridad de mi cuenta».
 * Aquí solo queda lo que usan las pantallas actuales.
 */
class AuthController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/auth/me
    // Retorna el usuario autenticado con sus permisos
    // ────────────────────────────────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user()->load(['empresa', 'sucursal', 'roles']);

        return response()->json([
            'success' => true,
            'data'    => new UsuarioResource($usuario),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Core\UsuarioResource;
use App\Models\Core\AuditoriaAcceso;
use App\Models\Core\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/login
    // ────────────────────────────────────────────────────────────────────────
    public function login(LoginRequest $request): JsonResponse
    {
        $ip        = $request->ip();
        $userAgent = $request->userAgent();
        $loginInput= $request->login;

        // Busca por email o username
        $usuario = $request->esEmail()
            ? Usuario::where('email', $loginInput)->first()
            : Usuario::where('username', $loginInput)->first();

        // Usuario no existe
        if (!$usuario) {
            AuditoriaAcceso::registrar('LOGIN_FAIL', $loginInput, $ip, null, $userAgent);
            return $this->errorUnauthorized('Credenciales incorrectas.');
        }

        // Cuenta inactiva
        if (!$usuario->activo) {
            AuditoriaAcceso::registrar('LOGIN_FAIL', $loginInput, $ip, $usuario->id_usuario, $userAgent);
            return $this->errorUnauthorized('Tu cuenta está inactiva. Contacta al administrador.');
        }

        // Cuenta bloqueada
        if ($usuario->estaBloqueado()) {
            AuditoriaAcceso::registrar('LOGIN_FAIL', $loginInput, $ip, $usuario->id_usuario, $userAgent);
            return $this->errorUnauthorized(
                'Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intenta en 30 minutos.'
            );
        }

        // Contraseña incorrecta
        if (!Hash::check($request->password, $usuario->password_hash)) {
            $usuario->registrarLoginFallido();
            AuditoriaAcceso::registrar('LOGIN_FAIL', $loginInput, $ip, $usuario->id_usuario, $userAgent);

            $intentosRestantes = max(0, 5 - $usuario->fresh()->intentos_fallidos);
            return $this->errorUnauthorized(
                "Contraseña incorrecta. Intentos restantes: {$intentosRestantes}."
            );
        }

        // ── Login exitoso ────────────────────────────────────────────────────
        $usuario->registrarLogin();

        // Carga relaciones para el Resource
        $usuario->load(['empresa', 'sucursal', 'roles.permisos']);

        // Determina expiración del token
        $expiracion = $request->boolean('remember')
            ? now()->addDays(30)
            : now()->addHours(8);

        // Crea token Sanctum
        $token = $usuario->createToken(
            name      : "nexuserp_{$usuario->id_usuario}",
            expiresAt : $expiracion
        )->plainTextToken;

        AuditoriaAcceso::registrar('LOGIN_OK', $loginInput, $ip, $usuario->id_usuario, $userAgent);

        return response()->json([
            'success' => true,
            'message' => '¡Bienvenido, ' . $usuario->nombre_completo . '!',
            'data'    => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'expira_en'  => $expiracion->toDateTimeString(),
                'usuario'    => new UsuarioResource($usuario),
            ],
        ], 200);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/logout
    // ────────────────────────────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Revoca solo el token actual
        $request->user()->currentAccessToken()->delete();

        AuditoriaAcceso::registrar(
            'LOGOUT',
            $usuario->username,
            $request->ip(),
            $usuario->id_usuario,
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // POST /api/v1/auth/logout-all
    // Cierra TODAS las sesiones del usuario (todos los dispositivos)
    // ────────────────────────────────────────────────────────────────────────
    public function logoutAll(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Revoca TODOS los tokens
        $usuario->tokens()->delete();

        AuditoriaAcceso::registrar(
            'LOGOUT',
            $usuario->username,
            $request->ip(),
            $usuario->id_usuario,
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada en todos los dispositivos.',
        ]);
    }

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
    // POST /api/v1/auth/refresh
    // Invalida el token actual y genera uno nuevo
    // ────────────────────────────────────────────────────────────────────────
    public function refresh(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Elimina token actual
        $request->user()->currentAccessToken()->delete();

        // Genera token fresco por 8 horas
        $expiracion = now()->addHours(8);
        $nuevoToken = $usuario->createToken(
            name      : "nexuserp_{$usuario->id_usuario}",
            expiresAt : $expiracion
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token renovado correctamente.',
            'data'    => [
                'token'      => $nuevoToken,
                'token_type' => 'Bearer',
                'expira_en'  => $expiracion->toDateTimeString(),
            ],
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

        // Revoca todos los tokens — fuerza nuevo login en otros dispositivos
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
            'message' => 'Contraseña actualizada. Por seguridad, inicia sesión nuevamente.',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ────────────────────────────────────────────────────────────────────────
    private function errorUnauthorized(string $mensaje): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
        ], 401);
    }
}
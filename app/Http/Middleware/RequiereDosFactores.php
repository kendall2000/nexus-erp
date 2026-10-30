<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el rol del usuario exige verificación en dos pasos y todavía no la
 * activó, solo puede entrar a Seguridad de su cuenta (para activarla) y salir.
 */
class RequiereDosFactores
{
    /** Rutas permitidas mientras la activa. */
    private const PERMITIDAS = [
        'cuenta.seguridad', 'user-password.update', 'logout', 'password.confirm', 'password.confirm.store', 'password.confirmation',
        'two-factor.enable', 'two-factor.confirm', 'two-factor.disable', 'two-factor.qr-code', 'two-factor.secret-key',
        'two-factor.recovery-codes', 'two-factor.regenerate-recovery-codes', 'two-factor.login', 'two-factor.login.store',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->debeActivarDosPasos() || $request->routeIs(...self::PERMITIDAS)) {
            return $next($request);
        }
        $mensaje = 'Tu rol exige la verificación en dos pasos: actívala aquí para seguir usando el sistema.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensaje], 403);
        }

        return redirect()->route('cuenta.seguridad')->with('aviso', $mensaje);
    }
}

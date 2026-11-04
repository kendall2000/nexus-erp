<?php

namespace App\Http\Middleware;

use App\Support\Seguridad;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Límite de «¿Olvidaste tu contraseña?» (la ruta de Fortify no trae ninguno): por IP y por
 * correo, para que no sirva para mandar correos en masa ni para probar direcciones. El aviso
 * es el mismo exista o no el correo.
 */
class LimitarRecuperacion
{
    private const MINUTOS = 15;

    private const POR_IP = 10;

    private const POR_CORREO = 3;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->routeIs('password.email')) {
            return $next($request);
        }

        $llaves = [
            'recuperar|ip|'.$request->ip() => self::POR_IP,
            'recuperar|correo|'.Seguridad::normalizarLogin($request->input('email')) => self::POR_CORREO,
        ];
        foreach ($llaves as $llave => $maximo) {
            if (RateLimiter::tooManyAttempts($llave, $maximo)) {
                $minutos = max(1, (int) ceil(RateLimiter::availableIn($llave) / 60));

                return back()->withInput($request->only('email'))->withErrors([
                    'email' => "Demasiadas solicitudes. Espera {$minutos} ".($minutos === 1 ? 'minuto' : 'minutos').' e intenta de nuevo.',
                ]);
            }
        }
        foreach (array_keys($llaves) as $llave) {
            RateLimiter::hit($llave, self::MINUTOS * 60);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\Contrasenas;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si la contraseña venció (política de «Seguridad y accesos») o la puso el administrador,
 * solo deja entrar a Seguridad de su cuenta para cambiarla, y salir. Unos días antes de
 * que venza avisa una vez por sesión.
 */
class ContrasenaVencida
{
    /** Rutas permitidas mientras la cambia. */
    private const PERMITIDAS = ['cuenta.seguridad', 'user-password.update', 'logout', 'password.confirm', 'password.confirm.store', 'password.confirmation'];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        if (! $usuario || $request->routeIs(...self::PERMITIDAS)) {
            return $next($request);
        }

        if (Contrasenas::debeCambiar($usuario)) {
            $mensaje = $usuario->debe_cambiar_password
                ? 'Tu contraseña la asignó el administrador: cámbiala por una tuya para seguir usando el sistema.'
                : 'Tu contraseña venció: cámbiala para seguir usando el sistema.';

            return redirect()->route('cuenta.seguridad')->with('aviso', $mensaje);
        }

        $vence = Contrasenas::venceEl($usuario);
        if ($vence && $request->isMethod('GET') && ! $request->session()->has('aviso_vencimiento') && now()->diffInDays($vence) < Contrasenas::DIAS_AVISO) {
            $request->session()->put('aviso_vencimiento', true);
            $dias = (int) ceil(now()->diffInDays($vence));
            $request->session()->now('aviso', 'Tu contraseña vence '.($dias <= 1 ? 'mañana' : "en {$dias} días").' ('.$vence->format('d/m/Y').'). Cámbiala en Seguridad de mi cuenta.');
        }

        return $next($request);
    }
}

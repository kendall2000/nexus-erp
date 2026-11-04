<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica al usuario la empresa elegida en la barra superior (guardada en la sesión).
 * Si ya no tiene acceso a ella (le quitaron el rol o se desactivó), vuelve a la suya.
 */
class EmpresaActiva
{
    public const SESION = 'empresa_activa';

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        if (! $usuario || ! $request->hasSession()) {
            return $next($request);
        }
        // Se recalcula en cada petición (roles y empresas pudieron cambiar desde la anterior).
        $usuario->olvidarPermisos();
        $id = (int) $request->session()->get(self::SESION);
        $principal = $usuario->idEmpresaPrincipal();

        if ($id && $id !== $principal && $usuario->puedeUsarEmpresa($id)) {
            $usuario->usarEmpresa($id);
        } else {
            if ($id) {
                $request->session()->forget(self::SESION);
            }
            if (! $usuario->enEmpresaPrincipal()) {
                $usuario->usarEmpresa($principal);
            }
        }

        return $next($request);
    }
}

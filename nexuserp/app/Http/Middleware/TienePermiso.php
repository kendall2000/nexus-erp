<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso en rutas: ->middleware('permiso:productos.ver'). Con «|» basta cualquiera de los
 * permisos ('permiso:pagos.crear|facturas.cobrar'). El Administrador pasa siempre.
 */
class TienePermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $usuario = $request->user();
        $permitido = $usuario && collect(explode('|', $permiso))->contains(fn ($p) => $usuario->puede($p));
        abort_unless($permitido, 403, 'Tu rol no tiene permiso para esta pantalla.');

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso en rutas: ->middleware('permiso:INV.PRODUCTOS.VER'). El Administrador pasa siempre. */
class TienePermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        abort_unless($request->user()?->puede($permiso), 403, 'Tu rol no tiene permiso para esta pantalla.');

        return $next($request);
    }
}

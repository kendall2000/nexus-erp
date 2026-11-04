<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SoloAdministrador
{
    /** Uso: `admin` (administrador de la empresa activa) o `admin:general` (lo que comparten todas las empresas). */
    public function handle(Request $request, Closure $next, ?string $nivel = null): Response
    {
        if ($nivel === 'general') {
            abort_unless($request->user()?->esAdministradorGeneral(), 403, 'Solo el administrador general puede cambiar lo que comparten todas las empresas.');

            return $next($request);
        }
        abort_unless($request->user()?->esAdministrador(), 403, 'Solo el administrador puede entrar aquí.');

        return $next($request);
    }
}

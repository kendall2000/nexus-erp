<?php

namespace App\Http\Middleware;

use App\Support\PoliticaContenido;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Encabezados HTTP básicos contra clickjacking, sniffing de tipos y fuga de referencias, y la CSP. */
class EncabezadosSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($encabezado = PoliticaContenido::encabezado()) {
            $response->headers->set($encabezado, PoliticaContenido::politica());
            $response->headers->set('Reporting-Endpoints', PoliticaContenido::puntoDeReporte());
        }
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}

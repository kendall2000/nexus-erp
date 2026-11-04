<?php

namespace App\Support;

/**
 * Política de seguridad de contenido (CSP) del sistema, fase 1: la que el sistema ya cumple.
 *
 * Todavía permite 'unsafe-inline' (hay <script> y on…= en las vistas) y 'unsafe-eval' (Vue
 * compila las plantillas en el navegador). Aun así, si se colara un XSS, impide cargar scripts
 * de otros dominios, enviar datos a otros servidores (fetch, imágenes, formularios), cambiar
 * la <base>, usar <object>/<embed> y que otro sitio meta el sistema en un iframe.
 */
class PoliticaContenido
{
    public const RUTA_REPORTE = '/csp-reporte';

    /** reportar | aplicar | apagado */
    public static function modo(): string
    {
        $modo = strtolower((string) config('csp.modo', 'reportar'));

        return in_array($modo, ['reportar', 'aplicar', 'apagado'], true) ? $modo : 'reportar';
    }

    /** Nombre del encabezado según el modo; null si está apagado. */
    public static function encabezado(): ?string
    {
        return match (self::modo()) {
            'aplicar' => 'Content-Security-Policy',
            'reportar' => 'Content-Security-Policy-Report-Only',
            default => null,
        };
    }

    public static function politica(): string
    {
        $contabo = self::origenes([config('filesystems.disks.contabo.url'), config('filesystems.disks.contabo.endpoint')]);

        $directivas = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', 'https://unicons.iconscout.com'],
            'font-src' => ["'self'", 'data:', 'https://fonts.gstatic.com', 'https://unicons.iconscout.com'],
            'img-src' => ["'self'", 'data:', 'blob:', ...$contabo],
            'connect-src' => ["'self'"],
            'media-src' => ["'self'", ...$contabo],
            'frame-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
            'report-uri' => [self::RUTA_REPORTE],
            'report-to' => ['csp'],
        ];

        return collect($directivas)->map(fn ($v, $k) => $k.' '.implode(' ', array_unique($v)))->implode('; ');
    }

    /** Encabezado Reporting-Endpoints para «report-to csp» (navegadores nuevos). */
    public static function puntoDeReporte(): string
    {
        return 'csp="'.url(self::RUTA_REPORTE).'"';
    }

    /** @return list<string> esquema://host[:puerto] de cada URL válida */
    private static function origenes(array $urls): array
    {
        $origenes = [];
        foreach ($urls as $url) {
            $p = parse_url((string) $url);
            if (! empty($p['scheme']) && ! empty($p['host']) && in_array(strtolower($p['scheme']), ['http', 'https'], true)) {
                $origenes[] = strtolower($p['scheme']).'://'.strtolower($p['host']).(isset($p['port']) ? ':'.$p['port'] : '');
            }
        }

        return array_values(array_unique($origenes));
    }
}

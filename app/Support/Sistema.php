<?php

namespace App\Support;

use App\Models\Core\ConfiguracionSistema;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Identidad del sistema (nombre, logo, colores, fondo del login) desde
 * ConfiguracionSistema. Se consulta una vez por petición.
 */
class Sistema
{
    /**
     * Configuración «login» (la que edita la pantalla Configuración); vacía si no hay conexión.
     * Se guarda en la petición actual (no en una variable estática) para que cada petición
     * lea los valores vigentes.
     */
    public static function config(): ConfiguracionSistema
    {
        $atributos = request()->attributes;
        if (! $atributos->has('sistema.config')) {
            try {
                $config = ConfiguracionSistema::obtenerLogin();
            } catch (Throwable) {
                $config = null;
            }
            $atributos->set('sistema.config', $config ?? new ConfiguracionSistema);
        }

        return $atributos->get('sistema.config');
    }

    public static function nombre(): string
    {
        return self::config()->nombreSistema ?: 'Nexus ERP';
    }

    /**
     * Logo como data URI para los PDF. dompdf trabaja con recursos remotos apagados
     * (evita que un PDF haga peticiones a cualquier URL), así que la imagen se descarga
     * aquí, con límite de tiempo y tamaño, y se incrusta. Null si no hay logo o falla.
     */
    public static function logoIncrustado(?string $url): ?string
    {
        if (! $url || ! preg_match('#^https?://#i', $url)) {
            return null;
        }

        try {
            $respuesta = Http::timeout(5)->get($url);
        } catch (Throwable) {
            return null;
        }

        $tipo = strtolower(trim(explode(';', (string) $respuesta->header('Content-Type'))[0]));
        if (! $respuesta->successful() || ! in_array($tipo, ['image/png', 'image/jpeg', 'image/gif'], true) || strlen($respuesta->body()) > 2 * 1024 * 1024) {
            return null;
        }

        return 'data:'.$tipo.';base64,'.base64_encode($respuesta->body());
    }

    /** Color primario válido (#rrggbb) o el azul de Phoenix. */
    public static function colorPrimario(): string
    {
        $color = (string) self::config()->colorPrimario;

        return preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : '#3874ff';
    }

    /** «#6366f1» → «99, 102, 241» (para las variables --phoenix-*-rgb). */
    public static function rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $hex = strlen($hex) === 3 ? preg_replace('/(.)/', '$1$1', $hex) : $hex;

        return implode(', ', array_map('hexdec', str_split($hex, 2)));
    }

    /** Oscurece un color (para el «hover» de los botones); con factor negativo lo aclara hacia el blanco. */
    public static function oscurecer(string $hex, float $factor = 0.15): string
    {
        $canal = fn (int $c) => $factor >= 0 ? $c * (1 - $factor) : $c + (255 - $c) * -$factor;

        return '#'.implode('', array_map(fn ($c) => str_pad(dechex((int) min(255, max(0, round($canal((int) $c))))), 2, '0', STR_PAD_LEFT), explode(', ', self::rgb($hex))));
    }
}

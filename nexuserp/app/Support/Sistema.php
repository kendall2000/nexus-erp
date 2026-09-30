<?php

namespace App\Support;

use App\Models\Core\ConfiguracionSistema;
use Throwable;

/**
 * Identidad del sistema (nombre, logo, colores, fondo del login) desde
 * ConfiguracionSistema. Se consulta una vez por petición.
 */
class Sistema
{
    private static ?ConfiguracionSistema $config = null;

    private static bool $cargada = false;

    /** Configuración «login» (la que edita la pantalla Configuración); vacía si no hay conexión. */
    public static function config(): ConfiguracionSistema
    {
        if (! self::$cargada) {
            self::$cargada = true;
            try {
                self::$config = ConfiguracionSistema::obtenerLogin();
            } catch (Throwable) {
                self::$config = null;
            }
        }

        return self::$config ?? new ConfiguracionSistema;
    }

    public static function nombre(): string
    {
        return self::config()->nombreSistema ?: 'Nexus ERP';
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

<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Archivos en Contabo (config/filesystems.php):
 *  - Públicos (disco «contabo»: logos, fotos de perfil): en la BD se guarda la URL completa.
 *  - Privados (disco «contabo_privado»: expediente de empleados): en la BD se guarda solo la
 *    ruta dentro del disco y se abren con respuesta(), detrás del permiso de la pantalla.
 */
class Archivos
{
    public const DISCO = 'contabo';

    public const DISCO_PRIVADO = 'contabo_privado';

    /** Hay credenciales de Contabo en el .env para ese disco. */
    public static function disponible(string $disco = self::DISCO): bool
    {
        $c = config('filesystems.disks.'.$disco);

        return filled($c['key'] ?? null) && filled($c['secret'] ?? null) && filled($c['bucket'] ?? null);
    }

    /**
     * Sube el archivo a «{carpeta}/» con nombre aleatorio y devuelve su URL pública.
     * Si Contabo no está configurado o falla, lanza un error de validación en $campo.
     */
    public static function subir(UploadedFile $archivo, string $carpeta, string $campo): string
    {
        if (! self::disponible()) {
            throw ValidationException::withMessages([$campo => 'La subida de archivos no está configurada (faltan las credenciales de Contabo en el .env).']);
        }

        try {
            $nombre = Str::random(40).'.'.strtolower($archivo->extension() ?: $archivo->getClientOriginalExtension());
            $ruta = $archivo->storePubliclyAs(trim($carpeta, '/'), $nombre, self::DISCO);

            return Storage::disk(self::DISCO)->url($ruta);
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages([$campo => 'No se pudo subir el archivo a Contabo. Intenta de nuevo en un momento.']);
        }
    }

    /**
     * Sube el archivo al disco privado en «{carpeta}/» con nombre aleatorio y devuelve su ruta
     * (no una URL: el archivo no tiene enlace público). Mismos errores que subir().
     */
    public static function subirPrivado(UploadedFile $archivo, string $carpeta, string $campo): string
    {
        if (! self::disponible(self::DISCO_PRIVADO)) {
            throw ValidationException::withMessages([$campo => 'La subida de archivos no está configurada (faltan las credenciales de Contabo en el .env).']);
        }

        try {
            $nombre = Str::random(40).'.'.strtolower($archivo->extension() ?: $archivo->getClientOriginalExtension());

            return $archivo->storeAs(trim($carpeta, '/'), $nombre, ['disk' => self::DISCO_PRIVADO, 'visibility' => 'private']);
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages([$campo => 'No se pudo subir el archivo a Contabo. Intenta de nuevo en un momento.']);
        }
    }

    /** El valor guardado es una ruta del disco privado (no una URL pública de las de antes). */
    public static function esPrivado(?string $valor): bool
    {
        return filled($valor) && ! preg_match('#^https?://#i', (string) $valor);
    }

    /**
     * Muestra un archivo guardado: el privado se envía desde el sistema (quien llama ya validó el
     * permiso); una URL pública de antes redirige a ella. $nombre es el nombre para descargarlo.
     */
    public static function respuesta(string $valor, string $nombre): StreamedResponse|RedirectResponse
    {
        if (! self::esPrivado($valor)) {
            return redirect()->away($valor);
        }
        $disco = Storage::disk(self::DISCO_PRIVADO);
        abort_unless($disco->exists($valor), 404, 'El archivo ya no existe.');

        $extension = strtolower(pathinfo($valor, PATHINFO_EXTENSION));
        $enNavegador = in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true);
        $archivo = trim(preg_replace('/[^\pL\pN ._-]+/u', '', $nombre) ?? '') ?: 'documento';

        return $disco->response($valor, $archivo.'.'.$extension, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ], $enNavegador ? 'inline' : 'attachment');
    }

    /** Ruta dentro del disco público de una URL subida por el sistema; null si es externa. */
    public static function rutaPublica(?string $url): ?string
    {
        $base = rtrim((string) config('filesystems.disks.'.self::DISCO.'.url'), '/');
        if (! $url || $base === '' || ! str_starts_with($url, $base.'/')) {
            return null;
        }
        $ruta = substr($url, strlen($base) + 1);
        $raiz = trim((string) config('filesystems.disks.'.self::DISCO.'.root'), '/');
        if ($raiz !== '' && str_starts_with($ruta, $raiz.'/')) {
            $ruta = substr($ruta, strlen($raiz) + 1);
        }

        return $ruta;
    }

    /**
     * Borra un archivo subido por el sistema: una ruta del disco privado o una URL del público.
     * URLs externas o de otras carpetas se ignoran. Nunca falla.
     */
    public static function borrar(?string $valor): void
    {
        $privado = self::esPrivado($valor);
        $ruta = $privado ? $valor : self::rutaPublica($valor);
        if (! $ruta || ! self::disponible($privado ? self::DISCO_PRIVADO : self::DISCO)) {
            return;
        }

        try {
            Storage::disk($privado ? self::DISCO_PRIVADO : self::DISCO)->delete($ruta);
        } catch (Throwable $e) {
            report($e);
        }
    }
}

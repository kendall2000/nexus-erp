<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Archivos públicos en Contabo (disco «contabo», config/filesystems.php).
 * En la BD se guarda la URL completa, como ya estaban las imágenes de Nexus.
 */
class Archivos
{
    public const DISCO = 'contabo';

    /** Hay credenciales de Contabo en el .env. */
    public static function disponible(): bool
    {
        $c = config('filesystems.disks.'.self::DISCO);

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

    /** Borra un archivo subido por el sistema. URLs externas o de otras carpetas se ignoran. Nunca falla. */
    public static function borrar(?string $url): void
    {
        $base = rtrim((string) config('filesystems.disks.'.self::DISCO.'.url'), '/');
        if (! $url || $base === '' || ! self::disponible() || ! str_starts_with($url, $base.'/')) {
            return;
        }

        try {
            $ruta = substr($url, strlen($base) + 1);
            $raiz = trim((string) config('filesystems.disks.'.self::DISCO.'.root'), '/');
            if ($raiz !== '' && str_starts_with($ruta, $raiz.'/')) {
                $ruta = substr($ruta, strlen($raiz) + 1);
            }
            Storage::disk(self::DISCO)->delete($ruta);
        } catch (Throwable $e) {
            report($e);
        }
    }
}

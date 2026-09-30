<?php

namespace App\Support;

use App\Models\Core\AuditoriaAcceso;
use App\Models\Core\ConfiguracionSistema;
use App\Models\Core\Usuario;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Seguridad de acceso: parámetros del inicio de sesión y registro en el
 * historial de accesos (tabla auditoria_acceso).
 *
 *  - intentos: intentos fallidos permitidos antes del bloqueo (ConfiguracionSistema.maxIntentosSesion).
 *  - bloqueo: minutos que dura el bloqueo, por usuario/correo + IP.
 *  - expira: minutos sin actividad tras los que la sesión se cierra (ConfiguracionSistema.sesionExpiraMin).
 */
class Seguridad
{
    private const CACHE = 'config_seguridad';

    /** @return array{intentos: int, bloqueo: int, expira: int} */
    public static function config(): array
    {
        try {
            if (is_array($guardada = Cache::get(self::CACHE))) {
                return $guardada;
            }
            $c = ConfiguracionSistema::obtenerLogin();
        } catch (Throwable) {
            // Sin conexión o sin tabla: valores por defecto, sin guardarlos en caché.
            return ['intentos' => 5, 'bloqueo' => 15, 'expira' => 120];
        }
        $config = [
            'intentos' => max(1, (int) ($c?->maxIntentosSesion ?: 5)),
            'bloqueo' => 15,
            'expira' => max(5, (int) ($c?->sesionExpiraMin ?: 120)),
        ];
        Cache::put(self::CACHE, $config, 600);

        return $config;
    }

    /** Llamar al guardar la configuración para que los cambios apliquen de inmediato. */
    public static function olvidar(): void
    {
        Cache::forget(self::CACHE);
    }

    /**
     * Busca al usuario por correo o por nombre de usuario (el campo «login»
     * del formulario acepta cualquiera de los dos).
     */
    public static function buscarUsuario(?string $login): ?Usuario
    {
        $login = trim((string) $login);
        if ($login === '') {
            return null;
        }

        return Usuario::query()
            ->where(str_contains($login, '@') ? 'email' : 'username', mb_strtolower($login))
            ->first();
    }

    /**
     * Registra un evento de acceso. Nunca interrumpe el inicio de sesión.
     * La columna «accion» es un enum: LOGIN_OK, LOGIN_FAIL, LOGOUT,
     * CAMBIO_PASSWORD, RESET_PASSWORD (bloqueos y desactivados van como LOGIN_FAIL).
     */
    public static function registrar(string $accion, ?string $login, ?int $idUsuario = null): void
    {
        try {
            $request = request();
            $idUsuario ??= self::buscarUsuario($login)?->id_usuario;
            AuditoriaAcceso::registrar(
                $accion,
                mb_substr(mb_strtolower((string) $login), 0, 60),
                (string) $request?->ip(),
                $idUsuario,
                mb_substr((string) $request?->userAgent(), 0, 500) ?: null,
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}

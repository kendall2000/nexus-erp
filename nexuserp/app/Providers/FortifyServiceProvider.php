<?php

namespace App\Providers;

use App\Support\Seguridad;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configurarAutenticacion();
        $this->configurarVistas();
        $this->configurarLimites();
        $this->registrarAccesos();
    }

    /** Credenciales correctas + usuario activo. Un usuario desactivado no entra aunque sepa la contraseña. */
    private function configurarAutenticacion(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $usuario = Seguridad::buscarUsuario($request->input(Fortify::username()));
            if (! $usuario || ! Hash::check((string) $request->input('password'), (string) $usuario->password_hash)) {
                return null;
            }
            if (! $usuario->puedeEntrar()) {
                Seguridad::registrar('LOGIN_FAIL', $request->input(Fortify::username()), $usuario->id_usuario);
                throw ValidationException::withMessages([
                    Fortify::username() => 'Tu usuario está desactivado. Consulta con el administrador.',
                ]);
            }

            return $usuario;
        });
    }

    private function configurarVistas(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
    }

    private function configurarLimites(): void
    {
        // Intentos y minutos de bloqueo por usuario/correo + IP.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());
            $c = Seguridad::config();

            return Limit::perMinutes($c['bloqueo'], $c['intentos'])->by($throttleKey)
                ->response(function (Request $request, array $headers) {
                    // El límite de Fortify no dispara el evento Lockout: el bloqueo se registra aquí.
                    Seguridad::registrar('LOGIN_FAIL', $request->input(Fortify::username()));
                    $minutos = max(1, (int) ceil(((int) ($headers['Retry-After'] ?? 60)) / 60));

                    return back()->withInput($request->only(Fortify::username()))->withErrors([
                        Fortify::username() => "Demasiados intentos fallidos. Espera {$minutos} ".($minutos === 1 ? 'minuto' : 'minutos').' e intenta de nuevo.',
                    ]);
                });
        });
    }

    /** Historial de accesos (auditoria_acceso): ingresos, fallidos y salidas. */
    private function registrarAccesos(): void
    {
        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['ultimo_login' => now()])->saveQuietly();
            Seguridad::registrar('LOGIN_OK', $event->user->username, $event->user->id_usuario);
        });
        Event::listen(Failed::class, function (Failed $event) {
            Seguridad::registrar('LOGIN_FAIL', $event->credentials[Fortify::username()] ?? null);
        });
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                Seguridad::registrar('LOGOUT', $event->user->username, $event->user->id_usuario);
            }
        });
    }
}

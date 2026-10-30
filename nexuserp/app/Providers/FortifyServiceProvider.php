<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Responses\EnlaceRecuperacionFallido;
use App\Models\Core\Usuario;
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
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Correo inexistente en «¿Olvidaste tu contraseña?»: misma respuesta que si existiera.
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, EnlaceRecuperacionFallido::class);
    }

    public function boot(): void
    {
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

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
                Seguridad::registrar('DESACTIVADO', $request->input(Fortify::username()), $usuario->id_usuario, 'Intentó entrar estando desactivado o sin rol activo');
                throw ValidationException::withMessages([
                    Fortify::username() => 'Tu usuario está desactivado. Consulta con el administrador.',
                ]);
            }

            return $usuario;
        });

        // «Confirmar contraseña» (zonas protegidas como activar 2 pasos): la columna es password_hash.
        Fortify::confirmPasswordsUsing(
            fn (Usuario $usuario, ?string $password) => Hash::check((string) $password, (string) $usuario->password_hash)
        );
    }

    private function configurarVistas(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
    }

    private function configurarLimites(): void
    {
        // Intentos y minutos de bloqueo por usuario/correo + IP (Seguridad y accesos).
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Seguridad::llaveIntentos($request->input(Fortify::username()), $request->ip());
            $c = Seguridad::config();

            return Limit::perMinutes($c['bloqueo'], $c['intentos'])->by($throttleKey)
                ->response(function (Request $request, array $headers) {
                    // El límite de Fortify no dispara el evento Lockout: el bloqueo se registra aquí.
                    Seguridad::registrar('BLOQUEO', $request->input(Fortify::username()), null, 'Intento durante el bloqueo');
                    $minutos = max(1, (int) ceil(((int) ($headers['Retry-After'] ?? 60)) / 60));

                    return back()->withInput($request->only(Fortify::username()))->withErrors([
                        Fortify::username() => "Demasiados intentos fallidos. Espera {$minutos} ".($minutos === 1 ? 'minuto' : 'minutos').' e intenta de nuevo.',
                    ]);
                });
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }

    /** Historial de accesos (auditoria_acceso): ingresos, fallidos y salidas. */
    private function registrarAccesos(): void
    {
        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['ultimo_login' => now()])->saveQuietly();
            Seguridad::registrar('LOGIN_OK', $event->user->username, $event->user->id_usuario, $event->remember ? 'Con «recordar sesión»' : null);
        });
        Event::listen(Failed::class, function (Failed $event) {
            Seguridad::registrar('LOGIN_FAIL', $event->credentials[Fortify::username()] ?? null);
        });
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                Seguridad::registrar('LOGOUT', $event->user->username, $event->user->id_usuario);
            }
        });
        Event::listen(TwoFactorAuthenticationFailed::class, function (TwoFactorAuthenticationFailed $event) {
            Seguridad::registrar('LOGIN_FAIL_2FA', $event->user->username ?? null, $event->user->id_usuario ?? null);
        });
    }
}

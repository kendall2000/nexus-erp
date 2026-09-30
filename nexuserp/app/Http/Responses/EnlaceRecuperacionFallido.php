<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse as RespuestaFortify;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

/**
 * «¿Olvidaste tu contraseña?» con un correo que no existe responde igual que
 * si existiera: así no se puede averiguar qué correos tienen cuenta.
 * Otros fallos (p. ej. demasiadas solicitudes) sí se muestran.
 */
class EnlaceRecuperacionFallido implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(private string $status) {}

    public function toResponse($request)
    {
        if ($this->status === Password::INVALID_USER) {
            return (new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT))->toResponse($request);
        }

        return (new RespuestaFortify($this->status))->toResponse($request);
    }
}

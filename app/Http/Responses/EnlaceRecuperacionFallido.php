<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse as RespuestaFortify;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

/**
 * «¿Olvidaste tu contraseña?» con un correo que no existe responde igual que
 * si existiera: así no se puede averiguar qué correos tienen cuenta.
 * Tampoco se avisa que ya se pidió un enlace hace poco, porque eso solo pasa con correos que existen.
 */
class EnlaceRecuperacionFallido implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(private string $status) {}

    public function toResponse($request)
    {
        // RESET_THROTTLED solo ocurre si la cuenta existe (ya se le mandó un enlace hace poco):
        // mostrarlo delataría el correo. El límite propio por IP y correo está en LimitarRecuperacion.
        if (in_array($this->status, [Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return (new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT))->toResponse($request);
        }

        return (new RespuestaFortify($this->status))->toResponse($request);
    }
}

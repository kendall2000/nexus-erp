<?php

namespace App\Actions\Fortify;

use App\Models\Core\Usuario;
use App\Support\Seguridad;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(Usuario $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password_hash' => Hash::make($input['password']),
        ])->save();

        // La contraseña pudo estar comprometida: se cierran todas sus sesiones.
        Seguridad::cerrarSesiones($user->id_usuario);
        Seguridad::registrar('RESET_PASSWORD', $user->username, $user->id_usuario);
    }
}

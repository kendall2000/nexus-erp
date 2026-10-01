<?php

namespace App\Actions\Fortify;

use App\Models\Core\Usuario;
use App\Support\Contrasenas;
use App\Support\Seguridad;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(Usuario $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => [...$this->passwordRules(), $this->noRepetida($user)],
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
        ])->validateWithBag('updatePassword');

        Contrasenas::cambiar($user, $input['password']);

        // Con la contraseña nueva, las demás sesiones abiertas quedan cerradas.
        $cerradas = Seguridad::cerrarSesiones($user->id_usuario, request()->session()->getId());
        Seguridad::registrar('CAMBIO_PASSWORD', $user->username, $user->id_usuario, $cerradas ? "{$cerradas} sesiones cerradas" : null);
    }
}

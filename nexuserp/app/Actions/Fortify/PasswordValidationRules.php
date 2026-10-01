<?php

namespace App\Actions\Fortify;

use App\Models\Core\Usuario;
use App\Support\Contrasenas;
use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }

    /** No deja repetir las últimas contraseñas del usuario (política de «Seguridad y accesos»). */
    protected function noRepetida(Usuario $usuario): Closure
    {
        return function (string $atributo, mixed $valor, Closure $fallar) use ($usuario) {
            if (is_string($valor) && Contrasenas::repetida($usuario, $valor)) {
                $n = Contrasenas::politica()['historial'];
                $fallar($n === 1 ? 'La contraseña nueva debe ser distinta de la actual.' : "No puedes repetir ninguna de tus últimas {$n} contraseñas.");
            }
        };
    }
}

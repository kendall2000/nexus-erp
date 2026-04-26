<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Pública — no requiere auth previa
    }

    public function rules(): array
    {
        return [
            'login'    => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'min:6'],
            'remember' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required'    => 'El usuario o correo es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }

    // Detecta si el login es email o username
    public function esEmail(): bool
    {
        return filter_var($this->login, FILTER_VALIDATE_EMAIL) !== false;
    }
}
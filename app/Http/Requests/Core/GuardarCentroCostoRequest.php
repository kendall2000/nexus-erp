<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarCentroCostoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => strtoupper(trim((string) $this->input('codigo'))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idCentro = $this->route('centro');
        if (is_object($idCentro)) {
            $idCentro = $idCentro->id_centro;
        }

        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
                Rule::unique('centro_costo', 'codigo')
                    ->where('id_empresa', $idEmpresa)
                    ->ignore($idCentro, 'id_centro'),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe un centro de costo con ese código.',
            'codigo.regex' => 'El código solo puede tener letras, números, punto, guion y guion bajo.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(): array
    {
        return $this->validated() + [
            'descripcion' => null,
            'activo' => $this->boolean('activo'),
        ];
    }
}

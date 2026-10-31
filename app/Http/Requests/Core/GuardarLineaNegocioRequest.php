<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarLineaNegocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idLinea = $this->route('linea');
        if (is_object($idLinea)) {
            $idLinea = $idLinea->id_linea;
        }

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('linea_negocio', 'nombre')
                    ->where('id_empresa', $idEmpresa)
                    ->ignore($idLinea, 'id_linea'),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una línea con ese nombre.',
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

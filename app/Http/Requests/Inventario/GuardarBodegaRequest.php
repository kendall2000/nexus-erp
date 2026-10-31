<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarBodegaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'ubicacion' => ['nullable', 'string', 'max:300'],
            'id_sucursal' => ['nullable', 'integer', Rule::exists('sucursal', 'id_sucursal')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'responsable_id' => ['nullable', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)->where('estado', 'ACTIVO')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_sucursal.exists' => 'La sucursal elegida no es válida.',
            'responsable_id.exists' => 'El responsable elegido no es válido.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(): array
    {
        return $this->validated() + [
            'id_sucursal' => null,
            'responsable_id' => null,
            'activo' => $this->boolean('activo'),
        ];
    }
}

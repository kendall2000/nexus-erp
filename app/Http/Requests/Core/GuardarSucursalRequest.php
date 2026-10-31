<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:300'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'id_pais' => ['nullable', 'integer', 'required_with:id_division', Rule::exists('pais', 'id_pais')->where('activo', true)],
            'id_division' => [
                'nullable',
                'integer',
                'required_with:id_municipio',
                Rule::exists('division_geografica', 'id_division')->where('activo', true)->where('id_pais', (int) $this->input('id_pais')),
            ],
            'id_municipio' => [
                'nullable',
                'integer',
                Rule::exists('municipio', 'id_municipio')->where('activo', true)->where('id_division', (int) $this->input('id_division')),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_division.exists' => 'El departamento no pertenece al país elegido.',
            'id_municipio.exists' => 'El municipio no pertenece al departamento elegido.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(): array
    {
        return $this->validated() + [
            'id_pais' => null,
            'id_division' => null,
            'id_municipio' => null,
            'es_casa_matriz' => $this->boolean('es_casa_matriz'),
            'activo' => $this->boolean('activo'),
        ];
    }
}

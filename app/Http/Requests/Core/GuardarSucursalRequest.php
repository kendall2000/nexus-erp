<?php

namespace App\Http\Requests\Core;

use App\Models\Core\Sucursal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('tipo')) {
            $this->merge(['tipo' => 'SUCURSAL']);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = (int) $this->user()->id_empresa;

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', Rule::in(array_keys(Sucursal::TIPOS))],
            // La agencia depende de una sucursal activa de la misma empresa (no de otra agencia).
            'id_sucursal_padre' => ['nullable', 'integer', 'required_if:tipo,AGENCIA',
                Rule::exists('sucursal', 'id_sucursal')->where('id_empresa', $idEmpresa)->where('tipo', 'SUCURSAL')->where('activo', true)],
            'codigo_establecimiento' => ['nullable', 'integer', 'min:1', 'max:9999',
                Rule::unique('sucursal', 'codigo_establecimiento')->where('id_empresa', $idEmpresa)->ignore($this->route('sucursal'), 'id_sucursal')],
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
            'id_sucursal_padre.required_if' => 'Elige la sucursal de la que depende la agencia.',
            'id_sucursal_padre.exists' => 'La agencia debe depender de una sucursal activa (no de otra agencia).',
            'codigo_establecimiento.unique' => 'Otra sede de la empresa ya tiene ese código de establecimiento.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(): array
    {
        $datos = $this->validated() + [
            'id_pais' => null,
            'id_division' => null,
            'id_municipio' => null,
            'codigo_establecimiento' => null,
            'es_casa_matriz' => $this->boolean('es_casa_matriz'),
            'activo' => $this->boolean('activo'),
        ];
        if ($datos['tipo'] !== 'AGENCIA') {
            $datos['id_sucursal_padre'] = null;
        }

        return $datos;
    }
}

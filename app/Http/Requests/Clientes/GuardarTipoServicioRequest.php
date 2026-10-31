<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarTipoServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'unidad_medida' => strtoupper(trim((string) $this->input('unidad_medida'))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idServicio = $this->route('servicio');
        if (is_object($idServicio)) {
            $idServicio = $idServicio->id_tipo_servicio;
        }

        return [
            'id_linea' => ['required', 'integer', Rule::exists('linea_negocio', 'id_linea')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('tipo_servicio', 'nombre')
                    ->where('id_linea', $this->integer('id_linea'))
                    ->ignore($idServicio, 'id_tipo_servicio'),
            ],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'unidad_medida' => ['required', 'string', 'max:50'],
            'precio_base' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'id_cuenta_ingreso' => ['nullable', 'integer', Rule::exists('cuenta_contable', 'id_cuenta')->where('id_empresa', $idEmpresa)->where('permite_movimiento', true)->where('tipo', 'INGRESO')],
            'id_centro_default' => ['nullable', 'integer', Rule::exists('centro_costo', 'id_centro')->where('id_empresa', $idEmpresa)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_linea.exists' => 'La línea de negocio no es válida o está inactiva.',
            'nombre.unique' => 'Esa línea ya tiene un servicio con ese nombre.',
            'id_cuenta_ingreso.exists' => 'La cuenta debe ser de ingreso y de movimiento.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(): array
    {
        return $this->validated() + [
            'descripcion' => null,
            'precio_base' => null,
            'id_cuenta_ingreso' => null,
            'id_centro_default' => null,
            'activo' => $this->boolean('activo'),
        ];
    }
}

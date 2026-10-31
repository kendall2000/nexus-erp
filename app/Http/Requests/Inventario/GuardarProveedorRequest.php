<?php

namespace App\Http\Requests\Inventario;

use App\Http\Controllers\ProveedorController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'razon_social' => trim((string) $this->input('razon_social')),
            'nit' => ($n = strtoupper(preg_replace('/\s+/', '', (string) $this->input('nit')))) === '' ? null : $n,
            'email' => ($e = strtolower(trim((string) $this->input('email')))) === '' ? null : $e,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idProveedor = $this->route('proveedor');
        if (is_object($idProveedor)) {
            $idProveedor = $idProveedor->id_proveedor;
        }

        return [
            'razon_social' => ['required', 'string', 'max:250'],
            'nombre_comercial' => ['nullable', 'string', 'max:150'],
            'nit' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('proveedor', 'nit')
                    ->where('id_empresa', $idEmpresa)
                    ->whereNull('deleted_at')
                    ->ignore($idProveedor, 'id_proveedor'),
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:300'],
            'contacto' => ['nullable', 'string', 'max:200'],
            'id_pais' => ['required', 'integer', Rule::exists('pais', 'id_pais')],
            'tipo_proveedor' => ['required', Rule::in(array_keys(ProveedorController::TIPOS))],
            'dias_credito' => ['required', 'integer', 'min:0', 'max:255'],
            'moneda_pago' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nit.unique' => 'Ya existe un proveedor con ese NIT.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(): array
    {
        return $this->validated() + ['activo' => $this->boolean('activo')];
    }
}

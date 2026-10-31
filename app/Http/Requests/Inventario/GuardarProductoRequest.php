<?php

namespace App\Http\Requests\Inventario;

use App\Http\Controllers\ProductoController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => strtoupper(trim((string) $this->input('codigo'))),
            'nombre' => trim((string) $this->input('nombre')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idProducto = $this->route('producto');
        if (is_object($idProducto)) {
            $idProducto = $idProducto->id_producto;
        }

        return [
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('producto', 'codigo')
                    ->where('id_empresa', $idEmpresa)
                    ->ignore($idProducto, 'id_producto'),
            ],
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'id_categoria' => ['nullable', 'integer', Rule::exists('categoria_producto', 'id_categoria')->where('id_empresa', $idEmpresa)],
            'unidad_medida' => ['required', Rule::in(array_keys(ProductoController::UNIDADES))],
            'precio_compra' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'precio_venta' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'stock_maximo' => ['nullable', 'numeric', 'min:0', 'gte:stock_minimo'],
            'id_cuenta_gasto' => ['nullable', 'integer', Rule::exists('cuenta_contable', 'id_cuenta')->where('id_empresa', $idEmpresa)->where('permite_movimiento', true)],
            'id_centro_default' => ['nullable', 'integer', Rule::exists('centro_costo', 'id_centro')->where('id_empresa', $idEmpresa)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe un producto con ese código.',
            'stock_maximo.gte' => 'El stock máximo no puede ser menor que el mínimo.',
            'id_categoria.exists' => 'La categoría elegida no es válida.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(): array
    {
        return $this->validated() + [
            'id_categoria' => null,
            'id_cuenta_gasto' => null,
            'id_centro_default' => null,
            'requiere_lote' => $this->boolean('requiere_lote'),
            'es_perecedero' => $this->boolean('es_perecedero'),
            'activo' => $this->boolean('activo'),
        ];
    }
}

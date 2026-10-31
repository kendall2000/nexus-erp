<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuardarOrdenCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'numero_oc' => ($n = strtoupper(trim((string) $this->input('numero_oc')))) === '' ? null : $n,
            'lineas' => array_values(array_filter((array) $this->input('lineas', []), fn ($l) => ! empty($l['id_producto']))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idOc = $this->route('orden');
        if (is_object($idOc)) {
            $idOc = $idOc->id_oc;
        }

        return [
            'numero_oc' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('orden_compra', 'numero_oc')
                    ->where('id_empresa', $idEmpresa)
                    ->ignore($idOc, 'id_oc'),
            ],
            'id_proveedor' => [
                'required',
                'integer',
                Rule::exists('proveedor', 'id_proveedor')
                    ->where('id_empresa', $idEmpresa)
                    ->where('activo', true)
                    ->whereNull('deleted_at'),
            ],
            'id_bodega' => ['nullable', 'integer', Rule::exists('bodega', 'id_bodega')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'fecha_emision' => ['required', 'date'],
            'fecha_entrega_esperada' => ['nullable', 'date', 'after_or_equal:fecha_emision'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'notas' => ['nullable', 'string', 'max:2000'],
            'lineas' => ['required', 'array', 'min:1', 'max:200'],
            'lineas.*.id_producto' => ['required', 'integer', Rule::exists('producto', 'id_producto')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'lineas.*.descripcion' => ['nullable', 'string', 'max:300'],
            'lineas.*.cantidad_pedida' => ['required', 'numeric', 'min:0.0001', 'max:99999999'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'lineas.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'lineas.*.id_centro' => ['nullable', 'integer', Rule::exists('centro_costo', 'id_centro')->where('id_empresa', $idEmpresa)],
            'lineas.*.id_cuenta' => ['nullable', 'integer', Rule::exists('cuenta_contable', 'id_cuenta')->where('id_empresa', $idEmpresa)->where('permite_movimiento', true)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'numero_oc.unique' => 'Ya existe una orden con ese número.',
            'id_proveedor.exists' => 'El proveedor no es válido o está inactivo.',
            'lineas.required' => 'Agrega al menos un producto.',
            'lineas.*.id_producto.exists' => 'Uno de los productos no es válido o está inactivo.',
            'lineas.*.cantidad_pedida.min' => 'La cantidad debe ser mayor que cero.',
        ];
    }

    /** @return array{encabezado: array<string, mixed>, lineas: list<array<string, mixed>>} */
    public function datosLimpios(): array
    {
        $datos = $this->validated();
        $lineas = [];
        foreach ($datos['lineas'] as $i => $l) {
            $bruto = round((float) $l['cantidad_pedida'] * (float) $l['precio_unitario'], 4);
            $descuento = round((float) ($l['descuento'] ?? 0), 4);
            if ($descuento > $bruto) {
                throw ValidationException::withMessages(["lineas.{$i}.descuento" => 'El descuento de una línea no puede ser mayor que su importe.']);
            }
            $lineas[] = [
                'id_producto' => (int) $l['id_producto'],
                'descripcion' => $l['descripcion'] ?? null,
                'cantidad_pedida' => $l['cantidad_pedida'],
                'precio_unitario' => $l['precio_unitario'],
                'descuento' => $descuento,
                'subtotal' => round($bruto - $descuento, 4),
                'id_centro' => $l['id_centro'] ?? null,
                'id_cuenta' => $l['id_cuenta'] ?? null,
            ];
        }

        return [
            'encabezado' => collect($datos)->only(['numero_oc', 'id_proveedor', 'id_bodega', 'fecha_emision', 'fecha_entrega_esperada', 'moneda', 'notas'])
                ->all() + ['id_bodega' => null, 'fecha_entrega_esperada' => null, 'notas' => null],
            'lineas' => $lineas,
        ];
    }
}

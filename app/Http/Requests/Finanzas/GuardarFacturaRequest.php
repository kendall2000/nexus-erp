<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Controllers\FacturaController;
use App\Models\Clientes\TipoServicio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuardarFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lineas' => array_values(array_filter(
                (array) $this->input('lineas', []),
                fn ($l) => trim((string) ($l['descripcion'] ?? '')) !== '' || ! empty($l['id_tipo_servicio'])
            )),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $esEdicion = (bool) $this->route('factura');
        $serviciosValidos = TipoServicio::query()
            ->where('activo', true)
            ->whereHas('lineaNegocio', fn ($q) => $q->where('id_empresa', $idEmpresa))
            ->pluck('id_tipo_servicio')
            ->all();

        return [
            'id_serie' => [
                $esEdicion ? 'nullable' : 'required',
                'integer',
                Rule::exists('serie_facturacion', 'id_serie')
                    ->where('id_empresa', $idEmpresa)
                    ->where('activo', true)
                    ->whereIn('tipo', array_keys(FacturaController::TIPOS)),
            ],
            'id_cliente' => [
                'required',
                'integer',
                Rule::exists('cliente', 'id_cliente')
                    ->where('id_empresa', $idEmpresa)
                    ->where('activo', true)
                    ->whereNull('deleted_at'),
            ],
            'id_contrato' => [
                'nullable',
                'integer',
                Rule::exists('contrato_servicio', 'id_contrato')
                    ->where('id_empresa', $idEmpresa)
                    ->where('id_cliente', $this->integer('id_cliente')),
            ],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date', 'after_or_equal:fecha_emision'],
            'periodo_servicio_inicio' => ['nullable', 'date'],
            'periodo_servicio_fin' => ['nullable', 'date', 'after_or_equal:periodo_servicio_inicio'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'descuento' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'lineas' => ['required', 'array', 'min:1', 'max:200'],
            'lineas.*.id_tipo_servicio' => ['nullable', 'integer', Rule::in($serviciosValidos)],
            'lineas.*.descripcion' => ['nullable', 'string', 'max:300'],
            'lineas.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'lineas.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'lineas.*.id_centro' => ['nullable', 'integer', Rule::exists('centro_costo', 'id_centro')->where('id_empresa', $idEmpresa)],
            'lineas.*.id_cuenta' => ['nullable', 'integer', Rule::exists('cuenta_contable', 'id_cuenta')->where('id_empresa', $idEmpresa)->where('permite_movimiento', true)->where('tipo', 'INGRESO')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_serie.required' => 'Elige la serie (define el tipo de documento).',
            'id_cliente.exists' => 'El cliente no es válido o está inactivo.',
            'id_contrato.exists' => 'El contrato no es de este cliente.',
            'fecha_vencimiento.after_or_equal' => 'El vencimiento no puede ser antes de la emisión.',
            'lineas.required' => 'Agrega al menos una línea.',
            'lineas.*.id_tipo_servicio.in' => 'Uno de los servicios no es válido.',
            'lineas.*.id_cuenta.exists' => 'La cuenta de una línea debe ser de ingreso y de movimiento.',
            'lineas.*.cantidad.min' => 'La cantidad debe ser mayor que cero.',
        ];
    }

    /** @return array{encabezado: array<string, mixed>, lineas: list<array<string, mixed>>} */
    public function datosLimpios(): array
    {
        $datos = $this->validated();
        $servicios = TipoServicio::query()
            ->whereIn('id_tipo_servicio', array_filter(array_column($datos['lineas'], 'id_tipo_servicio')))
            ->pluck('nombre', 'id_tipo_servicio');

        $lineas = [];
        $subtotal = 0.0;
        foreach ($datos['lineas'] as $i => $l) {
            $bruto = round((float) $l['cantidad'] * (float) $l['precio_unitario'], 4);
            $descuento = round((float) ($l['descuento'] ?? 0), 4);
            if ($descuento > $bruto) {
                throw ValidationException::withMessages(["lineas.{$i}.descuento" => 'El descuento de una línea no puede ser mayor que su importe.']);
            }
            $descripcion = trim((string) ($l['descripcion'] ?? '')) ?: ($servicios[$l['id_tipo_servicio'] ?? 0] ?? '');
            $lineas[] = [
                'id_tipo_servicio' => $l['id_tipo_servicio'] ?? null,
                'descripcion' => $descripcion,
                'cantidad' => $l['cantidad'],
                'precio_unitario' => $l['precio_unitario'],
                'descuento' => $descuento,
                'subtotal' => round($bruto - $descuento, 4),
                'es_afecto_iva' => ! empty($this->input("lineas.{$i}.es_afecto_iva")),
                'id_centro' => $l['id_centro'] ?? null,
                'id_cuenta' => $l['id_cuenta'] ?? null,
            ];
            $subtotal += $bruto - $descuento;
        }

        if ((float) ($datos['descuento'] ?? 0) > $subtotal) {
            throw ValidationException::withMessages(['descuento' => 'El descuento global no puede ser mayor que el subtotal.']);
        }

        return [
            'encabezado' => collect($datos)->only(['id_serie', 'id_cliente', 'id_contrato', 'fecha_emision', 'fecha_vencimiento', 'periodo_servicio_inicio', 'periodo_servicio_fin', 'moneda', 'notas'])->all()
                + ['descuento' => round((float) ($datos['descuento'] ?? 0), 4), 'id_contrato' => null, 'periodo_servicio_inicio' => null, 'periodo_servicio_fin' => null, 'notas' => null],
            'lineas' => $lineas,
        ];
    }
}

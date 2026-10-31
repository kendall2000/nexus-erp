<?php

namespace App\Http\Requests\Clientes;

use App\Http\Controllers\ContratoController;
use App\Models\Clientes\ContratoServicio;
use App\Models\Clientes\TipoServicio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarContratoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'numero_contrato' => ($n = strtoupper(trim((string) $this->input('numero_contrato')))) === '' ? null : $n,
            'lineas' => array_values(array_filter((array) $this->input('lineas', []), fn ($l) => ! empty($l['id_tipo_servicio']))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idContrato = $this->route('contrato');
        $c = is_object($idContrato) ? $idContrato : ($idContrato ? ContratoServicio::query()->where('id_empresa', $idEmpresa)->find($idContrato) : null);

        $idCliente = $c && $c->estado !== 'BORRADOR' ? $c->id_cliente : $this->integer('id_cliente');
        $servicios = TipoServicio::query()->whereHas('lineaNegocio', fn ($q) => $q->where('id_empresa', $idEmpresa))->pluck('id_tipo_servicio')->all();

        return [
            'numero_contrato' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('contrato_servicio', 'numero_contrato')
                    ->where('id_empresa', $idEmpresa)
                    ->ignore($c?->id_contrato, 'id_contrato'),
            ],
            'id_cliente' => [
                $c && $c->estado !== 'BORRADOR' ? 'nullable' : 'required',
                'integer',
                Rule::exists('cliente', 'id_cliente')
                    ->where('id_empresa', $idEmpresa)
                    ->where('activo', true)
                    ->whereNull('deleted_at'),
            ],
            'nombre_proyecto' => ['nullable', 'string', 'max:200'],
            'id_vendedor' => ['nullable', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after:fecha_inicio'],
            'fecha_firma' => ['nullable', 'date'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'periodicidad_factura' => ['required', Rule::in(array_keys(ContratoController::PERIODICIDADES))],
            'dia_facturacion' => ['nullable', 'integer', 'between:1,28'],
            'notas' => ['nullable', 'string', 'max:5000'],
            'lineas' => ['required', 'array', 'min:1', 'max:200'],
            'lineas.*.id_tipo_servicio' => ['required', 'integer', Rule::in($servicios)],
            'lineas.*.id_sitio' => ['nullable', 'integer', Rule::exists('sitio_trabajo', 'id_sitio')->where('id_cliente', $idCliente)],
            'lineas.*.descripcion' => ['nullable', 'string', 'max:300'],
            'lineas.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'lineas.*.descuento_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'numero_contrato.unique' => 'Ya existe un contrato con ese número.',
            'id_cliente.exists' => 'El cliente no es válido o está inactivo.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior al inicio.',
            'dia_facturacion.between' => 'El día de facturación va del 1 al 28 (para que exista en todos los meses).',
            'lineas.required' => 'Agrega al menos un servicio.',
            'lineas.*.id_tipo_servicio.in' => 'Uno de los servicios no es válido.',
            'lineas.*.id_sitio.exists' => 'El sitio de una línea no es del cliente.',
        ];
    }

    /** @return array{encabezado: array<string, mixed>, lineas: list<array<string, mixed>>} */
    public function datosLimpios(): array
    {
        $datos = $this->validated();

        $lineas = array_map(fn ($l) => [
            'id_tipo_servicio' => (int) $l['id_tipo_servicio'],
            'id_sitio' => $l['id_sitio'] ?? null,
            'descripcion' => $l['descripcion'] ?? null,
            'cantidad' => $l['cantidad'],
            'precio_unitario' => $l['precio_unitario'],
            'descuento_pct' => round((float) ($l['descuento_pct'] ?? 0), 2),
            'subtotal' => round((float) $l['cantidad'] * (float) $l['precio_unitario'] * (1 - (float) ($l['descuento_pct'] ?? 0) / 100), 4),
        ], $datos['lineas']);

        return [
            'encabezado' => collect($datos)->only(['numero_contrato', 'id_cliente', 'nombre_proyecto', 'id_vendedor', 'fecha_inicio', 'fecha_fin', 'fecha_firma', 'moneda', 'periodicidad_factura', 'dia_facturacion', 'notas'])
                ->all() + array_fill_keys(['nombre_proyecto', 'id_vendedor', 'fecha_fin', 'fecha_firma', 'dia_facturacion', 'notas'], null),
            'lineas' => $lineas,
        ];
    }
}

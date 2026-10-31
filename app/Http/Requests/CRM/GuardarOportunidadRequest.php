<?php

namespace App\Http\Requests\CRM;

use App\Models\CRM\Oportunidad;
use App\Support\Crm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarOportunidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $esEdicion = (bool) $this->route('oportunidad');
        $etapasAbiertas = Crm::etapas($idEmpresa)->where('es_ganada', false)->where('es_perdida', false);

        return [
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'id_cliente' => ['nullable', 'required_without:id_prospecto', 'integer', Rule::exists('cliente', 'id_cliente')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'id_prospecto' => ['nullable', 'integer', Rule::exists('prospecto', 'id_prospecto')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'id_etapa' => [$esEdicion ? 'nullable' : 'required', 'integer', Rule::in($etapasAbiertas->pluck('id_etapa')->all())],
            'id_responsable' => ['required', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'id_linea' => ['nullable', 'integer', Rule::exists('linea_negocio', 'id_linea')->where('id_empresa', $idEmpresa)],
            'valor_estimado' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'probabilidad' => ['nullable', 'integer', 'between:0,100'],
            'fecha_cierre_estimada' => ['nullable', 'date'],
            'competidores' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_cliente.required_without' => 'Elige un cliente o un prospecto.',
            'id_etapa.in' => 'Elige una etapa abierta.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(?Oportunidad $o = null): array
    {
        $datos = $this->validated();
        $idEmpresa = $this->user()->id_empresa;
        $etapasAbiertas = Crm::etapas($idEmpresa)->where('es_ganada', false)->where('es_perdida', false);

        if ($o) {
            unset($datos['id_etapa']);
        }
        $etapa = $etapasAbiertas->firstWhere('id_etapa', (int) ($datos['id_etapa'] ?? $o?->id_etapa));
        $datos['probabilidad'] = (int) ($datos['probabilidad'] ?? $etapa?->probabilidad_cierre ?? $o?->probabilidad ?? 50);
        $datos['valor_ponderado'] = round((float) $datos['valor_estimado'] * $datos['probabilidad'] / 100, 4);

        return $datos;
    }
}

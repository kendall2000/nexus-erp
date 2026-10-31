<?php

namespace App\Http\Requests\CRM;

use App\Http\Controllers\ProspectoController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarProspectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $web = trim((string) $this->input('sitio_web'));
        $this->merge([
            'sitio_web' => $web !== '' && ! preg_match('#^https?://#i', $web) ? 'https://'.$web : ($web ?: null),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;

        return [
            'nombre_empresa' => ['required', 'string', 'max:250'],
            'nombre_contacto' => ['required', 'string', 'max:200'],
            'cargo_contacto' => ['nullable', 'string', 'max:150'],
            'email_contacto' => ['required', 'email', 'max:150'],
            'telefono_contacto' => ['nullable', 'string', 'max:20'],
            'whatsapp_contacto' => ['nullable', 'string', 'max:20'],
            'sitio_web' => ['nullable', 'url:http,https', 'max:200'],
            'empleados_estimados' => ['nullable', 'string', 'max:20'],
            'id_fuente' => ['nullable', 'integer', Rule::exists('fuente_lead', 'id_fuente')->where('activo', true)],
            'id_asignado_a' => ['nullable', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'id_pais' => ['nullable', 'integer', 'exists:pais,id_pais'],
            'id_industria' => ['nullable', 'integer', 'exists:industria,id_industria'],
            'temperatura' => ['required', Rule::in(array_keys(ProspectoController::TEMPERATURAS))],
            'puntuacion_lead' => ['nullable', 'integer', 'between:0,100'],
            'interes_servicio' => ['nullable', 'string', 'max:5000'],
            'presupuesto_estimado' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'notas' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'sitio_web.url' => 'El sitio web no es una dirección válida.',
            'id_asignado_a.exists' => 'El vendedor no es válido.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(): array
    {
        $datos = $this->validated();
        $datos['puntuacion_lead'] = (int) ($datos['puntuacion_lead'] ?? 0);

        return $datos;
    }
}

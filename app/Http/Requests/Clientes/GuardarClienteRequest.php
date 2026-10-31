<?php

namespace App\Http\Requests\Clientes;

use App\Http\Controllers\ClienteController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $web = trim((string) $this->input('sitio_web'));

        $this->merge([
            'nit' => ClienteController::normalizarNit($this->input('nit')),
            'sitio_web' => $web !== '' && ! preg_match('#^https?://#i', $web) ? 'https://'.$web : ($web ?: null),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idCliente = $this->route('cliente');
        if (is_object($idCliente)) {
            $idCliente = $idCliente->id_cliente;
        }

        return [
            'razon_social' => ['required', 'string', 'max:250'],
            'nombre_comercial' => ['nullable', 'string', 'max:150'],
            'nit' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9A-Z]+$/',
                Rule::unique('cliente', 'nit')
                    ->where('id_empresa', $idEmpresa)
                    ->whereNull('deleted_at')
                    ->where(fn ($q) => $q->where('nit', '!=', ClienteController::CONSUMIDOR_FINAL))
                    ->ignore($idCliente, 'id_cliente'),
            ],
            'tipo_persona' => ['required', Rule::in(['JURIDICA', 'NATURAL'])],
            'id_industria' => ['nullable', 'integer', 'exists:industria,id_industria'],
            'email_principal' => ['nullable', 'email', 'max:150'],
            'telefono_principal' => ['nullable', 'string', 'max:20'],
            'sitio_web' => ['nullable', 'url:http,https', 'max:200'],
            'id_pais' => ['required', 'integer', Rule::exists('pais', 'id_pais')->where('activo', true)],
            'id_municipio' => ['nullable', 'integer', 'exists:municipio,id_municipio'],
            'direccion_fiscal' => ['nullable', 'string', 'max:300'],
            'segmento' => ['nullable', Rule::in(array_keys(ClienteController::SEGMENTOS))],
            'categoria' => ['nullable', Rule::in(ClienteController::CATEGORIAS)],
            'moneda_facturacion' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'dias_credito' => ['required', 'integer', 'min:0', 'max:255'],
            'limite_credito' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nit.unique' => 'Ya hay un cliente con ese NIT.',
            'nit.regex' => 'El NIT solo puede tener números y letras (los guiones y espacios se quitan solos).',
            'sitio_web.url' => 'El sitio web no es una dirección válida.',
            'dias_credito.max' => 'Los días de crédito no pueden pasar de 255.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $idMunicipio = $this->input('id_municipio');
                $idPais = $this->input('id_pais');
                if (! empty($idMunicipio) && ! empty($idPais)) {
                    $paisDelMunicipio = DB::table('municipio')
                        ->join('division_geografica', 'division_geografica.id_division', '=', 'municipio.id_division')
                        ->where('municipio.id_municipio', $idMunicipio)
                        ->value('division_geografica.id_pais');
                    if ((int) $paisDelMunicipio !== (int) $idPais) {
                        $validator->errors()->add('id_municipio', 'El municipio no pertenece al país elegido.');
                    }
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(): array
    {
        return $this->validated()
            + array_fill_keys([
                'nombre_comercial',
                'id_industria',
                'email_principal',
                'telefono_principal',
                'id_municipio',
                'direccion_fiscal',
                'segmento',
                'categoria',
                'limite_credito',
            ], null)
            + ['activo' => $this->boolean('activo')];
    }
}

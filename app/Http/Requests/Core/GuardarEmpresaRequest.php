<?php

namespace App\Http\Requests\Core;

use App\Support\CatalogosEmpresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nit' => strtoupper(str_replace([' ', '-'], '', trim((string) $this->input('nit')))),
            'moneda_base' => strtoupper(trim((string) $this->input('moneda_base'))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('empresa');
        $nueva = $id === null;

        return [
            'nombre_comercial' => ['required', 'string', 'max:100'],
            'nombre_legal' => ['required', 'string', 'max:200'],
            'nit' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Z]+$/',
                Rule::unique('empresa', 'nit')->where('id_pais', (int) $this->input('id_pais'))->ignore($id, 'id_empresa')],
            'id_pais' => ['required', 'integer', Rule::exists('pais', 'id_pais')->where('activo', true)],
            'id_division' => ['nullable', 'integer', 'required_with:id_municipio',
                Rule::exists('division_geografica', 'id_division')->where('activo', true)->where('id_pais', (int) $this->input('id_pais'))],
            'id_municipio' => ['nullable', 'integer',
                Rule::exists('municipio', 'id_municipio')->where('activo', true)->where('id_division', (int) $this->input('id_division'))],
            'moneda_base' => ['required', 'string', 'size:3', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'tasa_iva' => ['required', 'numeric', 'min:0', 'max:50'],
            'email_corporativo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:300'],
            'fecha_fundacion' => ['nullable', 'date', 'before_or_equal:today'],
            // Datos para sus documentos (lo que falte sale de los del consorcio).
            'sitio_web' => ['nullable', 'url:https,http', 'max:150'],
            'regimen_fiscal' => ['nullable', 'string', 'max:150'],
            'leyenda_factura' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'], // sin SVG: puede llevar scripts
            'quitar_logo' => ['nullable', 'boolean'],
            // Solo al crear: de qué empresa se copian los catálogos y el nombre de la casa matriz.
            // Una empresa de la instalación o «plantilla» (la plantilla del sistema).
            'id_empresa_modelo' => [Rule::requiredIf($nueva && $this->filled('catalogos')), 'nullable',
                $this->input('id_empresa_modelo') === CatalogosEmpresa::PLANTILLA ? Rule::in([CatalogosEmpresa::hayPlantilla() ? CatalogosEmpresa::PLANTILLA : ''])
                    : Rule::exists('empresa', 'id_empresa')],
            'catalogos' => ['nullable', 'array'],
            'catalogos.*' => [Rule::in(array_keys(CatalogosEmpresa::GRUPOS))],
            'casa_matriz' => [$nueva ? 'required' : 'nullable', 'string', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nit.unique' => 'Ya hay una empresa con ese NIT en ese país.',
            'nit.regex' => 'El NIT solo lleva números y letras (sin guiones ni espacios).',
            'id_division.exists' => 'El departamento no pertenece al país elegido.',
            'id_municipio.exists' => 'El municipio no pertenece al departamento elegido.',
            'id_empresa_modelo.required' => 'Elige de qué empresa se copian los catálogos.',
            'logo.mimes' => 'El logo debe ser una imagen JPG, PNG o WebP.',
        ];
    }

    /** Datos de la tabla empresa. @return array<string, mixed> */
    public function datosEmpresa(): array
    {
        return array_merge($this->safe()->only([
            'nombre_comercial', 'nombre_legal', 'nit', 'id_pais', 'moneda_base', 'tasa_iva',
            'email_corporativo', 'telefono', 'direccion', 'fecha_fundacion', 'sitio_web', 'regimen_fiscal', 'leyenda_factura',
        ]), [
            'id_municipio' => $this->validated('id_municipio'),
            'iva_incluido_en_precio' => $this->boolean('iva_incluido_en_precio'),
        ]);
    }
}

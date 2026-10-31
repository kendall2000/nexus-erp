<?php

namespace App\Http\Requests\CRM;

use App\Http\Controllers\CampanaController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarCampanaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;

        return [
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'tipo' => ['required', Rule::in(array_keys(CampanaController::TIPOS))],
            'objetivo' => ['required', Rule::in(array_keys(CampanaController::OBJETIVOS))],
            'id_linea' => ['nullable', 'integer', Rule::exists('linea_negocio', 'id_linea')->where('id_empresa', $idEmpresa)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'presupuesto' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'meta_leads' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}

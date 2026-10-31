<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Controllers\SerieFacturacionController;
use App\Models\Finanzas\SerieFacturacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuardarSerieFacturacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo_serie' => strtoupper(trim((string) $this->input('codigo_serie'))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codigo_serie' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9][A-Z0-9-]*$/'],
            'tipo' => ['required', Rule::in(array_keys(SerieFacturacionController::TIPOS))],
            'descripcion' => ['nullable', 'string', 'max:100'],
            'ultimo_numero' => ['required', 'integer', 'min:0', 'max:99999999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'codigo_serie.regex' => 'El código solo puede tener letras, números y guion.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(?SerieFacturacion $serie = null): array
    {
        $datos = $this->validated();
        $idEmpresa = $this->user()->id_empresa;

        $duplicada = SerieFacturacion::query()
            ->where('id_empresa', $idEmpresa)
            ->where('codigo_serie', $datos['codigo_serie'])
            ->where('tipo', $datos['tipo'])
            ->when($serie, fn ($q) => $q->whereKeyNot($serie->id_serie))
            ->exists();

        if ($duplicada) {
            throw ValidationException::withMessages(['codigo_serie' => 'Ya hay una serie con ese código para ese tipo de documento.']);
        }

        return $datos + ['descripcion' => null, 'activo' => $this->boolean('activo')];
    }
}

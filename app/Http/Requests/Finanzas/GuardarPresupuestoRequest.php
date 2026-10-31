<?php

namespace App\Http\Requests\Finanzas;

use App\Http\Controllers\PresupuestoController;
use App\Models\Finanzas\PresupuestoAnual;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuardarPresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $reglasMeses = [];
        foreach (PresupuestoAnual::MESES as $m) {
            $reglasMeses["pre_{$m}"] = ['nullable', 'numeric', 'min:0', 'max:9999999999'];
        }

        return [
            'id_centro' => ['required', 'integer', Rule::exists('centro_costo', 'id_centro')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'id_cuenta' => [
                'required',
                'integer',
                Rule::exists('cuenta_contable', 'id_cuenta')
                    ->where('id_empresa', $idEmpresa)
                    ->where('activo', true)
                    ->where('permite_movimiento', true)
                    ->whereIn('tipo', PresupuestoController::TIPOS_CUENTA),
            ],
            'anio' => ['required', 'integer', 'between:2000,2100'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
        ] + $reglasMeses;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_centro.exists' => 'El centro de costo no es válido o está inactivo.',
            'id_cuenta.exists' => 'La cuenta debe ser de movimiento, activa y de ingreso, gasto o costo.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(?PresupuestoAnual $p = null): array
    {
        $datos = $this->validated();
        $idEmpresa = $this->user()->id_empresa;

        $duplicado = PresupuestoAnual::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_centro', $datos['id_centro'])
            ->where('id_cuenta', $datos['id_cuenta'])
            ->where('anio', $datos['anio'])
            ->when($p, fn ($q) => $q->whereKeyNot($p->id_presupuesto))
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages(['id_cuenta' => 'Ya hay un presupuesto de ese centro y cuenta para '.$datos['anio'].'.']);
        }

        foreach (PresupuestoAnual::MESES as $m) {
            $datos["pre_{$m}"] = round((float) ($datos["pre_{$m}"] ?? 0), 2);
        }

        return $datos;
    }
}

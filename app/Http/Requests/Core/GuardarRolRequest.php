<?php

namespace App\Http\Requests\Core;

use App\Models\Core\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idRol = $this->route('rol');
        $rol = is_object($idRol) ? $idRol : ($idRol ? Rol::query()->where('id_empresa', $idEmpresa)->find($idRol) : null);

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('rol', 'nombre')->where('id_empresa', $idEmpresa)->ignore($rol?->id_rol, 'id_rol'),
                function (string $atributo, mixed $valor, \Closure $fallar) use ($rol) {
                    if (! $rol?->esAdministrador() && Rol::esNombreDeAccesoTotal((string) $valor) && ! $this->user()->esAdministrador()) {
                        $fallar('Ese nombre está reservado para los roles con acceso total ('.implode(', ', Rol::ACCESO_TOTAL).').');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(?Rol $rol = null): array
    {
        return $this->validated() + [
            'requiere_2fa' => $this->boolean('requiere_2fa'),
            'activo' => $rol?->esAdministrador() ? true : $this->boolean('activo'),
        ];
    }
}

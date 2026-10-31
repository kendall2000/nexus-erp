<?php

namespace App\Http\Requests\RRHH;

use App\Http\Controllers\EmpleadoController;
use App\Models\RRHH\Empleado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuardarEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $documento = strtoupper(preg_replace('/[\s\-]+/', '', (string) $this->input('dpi_nit')));
        $this->merge([
            'dpi_nit' => $documento,
            'codigo_empleado' => ($c = strtoupper(trim((string) $this->input('codigo_empleado')))) === '' ? null : $c,
            'nit_personal' => ($n = strtoupper(preg_replace('/[\s\-]+/', '', (string) $this->input('nit_personal')))) === '' ? null : $n,
            'es_rotativo' => $this->boolean('es_rotativo'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $idEmpresa = $this->user()->id_empresa;
        $idEmpleado = $this->route('empleado');
        if (is_object($idEmpleado)) {
            $idEmpleado = $idEmpleado->id_empleado;
        }
        $mia = fn (string $tabla, string $llave) => Rule::exists($tabla, $llave)->where('id_empresa', $idEmpresa);

        return [
            'primer_nombre' => ['required', 'string', 'max:80'],
            'segundo_nombre' => ['nullable', 'string', 'max:80'],
            'primer_apellido' => ['required', 'string', 'max:80'],
            'segundo_apellido' => ['nullable', 'string', 'max:80'],
            'apellido_casada' => ['nullable', 'string', 'max:80'],
            'tipo_doc_id' => ['required', Rule::in(['DPI', 'DUI', 'DNI', 'PASAPORTE', 'OTRO'])],
            'dpi_nit' => [
                'required',
                'string',
                'max:20',
                Rule::when($this->input('tipo_doc_id') === 'DPI', ['digits:13']),
                Rule::unique('empleado', 'dpi_nit')->where('id_empresa', $idEmpresa)->ignore($idEmpleado, 'id_empleado'),
            ],
            'nit_personal' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+K?$/'],
            'igss_afiliacion' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:-14 years'],
            'genero' => ['nullable', Rule::in(['M', 'F', 'OTRO', 'NO_ESPECIFICA'])],
            'estado_civil' => ['nullable', Rule::in(['SOLTERO', 'CASADO', 'UNION_LIBRE', 'DIVORCIADO', 'VIUDO'])],
            'nacionalidad' => ['nullable', 'string', 'max:80'],
            'email_personal' => ['nullable', 'email', 'max:150'],
            'email_corporativo' => ['nullable', 'email', 'max:150'],
            'telefono_personal' => ['nullable', 'string', 'max:20'],
            'telefono_emergencia' => ['nullable', 'string', 'max:20'],
            'contacto_emergencia' => ['nullable', 'string', 'max:200'],
            'id_municipio' => ['nullable', 'integer', 'exists:municipio,id_municipio'],
            'direccion' => ['nullable', 'string', 'max:300'],
            'codigo_empleado' => ['nullable', 'string', 'max:30', Rule::unique('empleado', 'codigo_empleado')->where('id_empresa', $idEmpresa)->ignore($idEmpleado, 'id_empleado')],
            'id_sucursal' => ['nullable', 'integer', $mia('sucursal', 'id_sucursal')],
            'id_depto_org' => ['nullable', 'integer', $mia('departamento_org', 'id_depto_org')],
            'id_cargo' => ['nullable', 'integer', $mia('cargo', 'id_cargo')],
            'id_supervisor' => ['nullable', 'integer', $mia('empleado', 'id_empleado')->whereNull('deleted_at')],
            'fecha_ingreso' => ['required', 'date'],
            'tipo_contrato' => ['required', Rule::in(array_keys(EmpleadoController::TIPOS_CONTRATO))],
            'modalidad_trabajo' => ['required', Rule::in(['PRESENCIAL', 'REMOTO', 'HIBRIDO'])],
            'es_rotativo' => ['boolean'],
            'tarifa_dia' => ['nullable', 'numeric', 'min:0.01', 'max:999999', 'required_if:es_rotativo,true'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tarifa_dia.required_if' => 'Un rotativo necesita su tarifa diaria.',
            'dpi_nit.digits' => 'El DPI debe tener 13 dígitos.',
            'dpi_nit.unique' => 'Ya hay un empleado con ese documento.',
            'codigo_empleado.unique' => 'Ya hay un empleado con ese código.',
            'nit_personal.regex' => 'El NIT solo lleva números y, al final, una K.',
            'fecha_nacimiento.before' => 'El empleado debe tener al menos 14 años.',
        ];
    }

    /** @return array<string, mixed> */
    public function datosLimpios(?Empleado $e = null): array
    {
        $datos = $this->validated();

        if (! empty($datos['id_supervisor']) && $e) {
            for ($jefe = (int) $datos['id_supervisor'], $i = 0; $jefe && $i < 50; $i++) {
                if ($jefe === $e->id_empleado) {
                    throw ValidationException::withMessages(['id_supervisor' => 'El jefe inmediato no puede ser el mismo empleado ni alguien a su cargo.']);
                }
                $jefe = (int) Empleado::query()->whereKey($jefe)->value('id_supervisor');
            }
        }

        return $datos + array_fill_keys([
            'segundo_nombre', 'segundo_apellido', 'apellido_casada', 'igss_afiliacion', 'fecha_nacimiento', 'genero', 'estado_civil', 'nacionalidad',
            'email_personal', 'email_corporativo', 'telefono_personal', 'telefono_emergencia', 'contacto_emergencia', 'id_municipio', 'direccion', 'id_sucursal', 'id_depto_org',
            'id_cargo', 'id_supervisor', 'tarifa_dia',
        ], null);
    }
}

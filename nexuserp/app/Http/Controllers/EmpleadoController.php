<?php

namespace App\Http\Controllers;

use App\Models\Core\DivisionGeografica;
use App\Models\Core\Municipio;
use App\Models\Core\Sucursal;
use App\Models\RRHH\Cargo;
use App\Models\RRHH\ContratoLaboral;
use App\Models\RRHH\DepartamentoOrg;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\HistorialSalarial;
use App\Support\ExportarCsv;
use App\Support\Referencias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Empleados, su contrato laboral vigente y el historial salarial.
 * Permisos: empleados.ver / crear / editar / eliminar / exportar.
 * El salario vigente es el del contrato VIGENTE (lo usa Nómina); cada cambio queda en historial_salarial.
 */
class EmpleadoController extends Controller
{
    public const ESTADOS = [
        'ACTIVO' => ['Activo', 'success'], 'VACACIONES' => ['Vacaciones', 'info'], 'LICENCIA' => ['Licencia', 'info'],
        'SUSPENDIDO' => ['Suspendido', 'warning'], 'INACTIVO' => ['Inactivo', 'secondary'], 'BAJA' => ['De baja', 'danger'],
    ];

    /** Estados que se eligen al editar; BAJA solo con «Dar de baja». */
    private const ESTADOS_EDITABLES = ['ACTIVO', 'VACACIONES', 'LICENCIA', 'SUSPENDIDO', 'INACTIVO'];

    public const TIPOS_CONTRATO = ['INDEFINIDO' => 'Indefinido', 'TIEMPO_DEFINIDO' => 'Tiempo definido', 'PRUEBA' => 'Periodo de prueba', 'OBRA' => 'Por obra', 'OUTSOURCING' => 'Outsourcing'];

    public const JORNADAS = ['COMPLETA' => 'Diurna completa', 'PARCIAL' => 'Parcial', 'NOCTURNA' => 'Nocturna', 'MIXTA' => 'Mixta'];

    public const CAMBIOS_SALARIO = ['AUMENTO' => 'Aumento', 'PROMOCION' => 'Promoción', 'AJUSTE' => 'Ajuste', 'CORRECCION' => 'Corrección'];

    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('empleados.index', [
            'empleados' => $this->filtrados($request)->with(['cargo', 'departamento', 'contratoVigente'])->orderBy('primer_apellido')->orderBy('primer_nombre')->paginate(25)->withQueryString(),
            'departamentos' => DepartamentoOrg::query()->where('id_empresa', $idEmpresa)->orderBy('nombre')->get(),
            'estados' => self::ESTADOS,
            'filtros' => $request->only(['buscar', 'departamento', 'estado']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $empleados = $this->filtrados($request)->with(['cargo', 'departamento', 'sucursal', 'supervisor', 'contratoVigente'])->orderBy('primer_apellido')->cursor();

        return ExportarCsv::descargar('empleados', ['Código', 'Nombre', 'Documento', 'Cargo', 'Departamento', 'Sucursal', 'Jefe inmediato', 'Ingreso', 'Contrato', 'Salario', 'Moneda', 'Correo', 'Teléfono', 'Estado'],
            (function () use ($empleados) {
                foreach ($empleados as $e) {
                    yield [$e->codigo_empleado, $e->nombre_completo, $e->tipo_doc_id.' '.$e->dpi_nit, $e->cargo?->nombre, $e->departamento?->nombre, $e->sucursal?->nombre,
                        $e->supervisor?->nombre_completo, $e->fecha_ingreso?->format('d/m/Y'), self::TIPOS_CONTRATO[$e->tipo_contrato] ?? $e->tipo_contrato,
                        $e->contratoVigente?->salario_base, $e->contratoVigente?->moneda, $e->email_corporativo ?: $e->email_personal, $e->telefono_personal, self::ESTADOS[$e->estado][0] ?? $e->estado];
                }
            })());
    }

    public function show(Request $request, int $empleado): View
    {
        $e = $this->deMiEmpresa($request)->with(['cargo', 'departamento', 'sucursal', 'supervisor', 'municipio.division', 'contratoVigente', 'subordinados' => fn ($q) => $q->where('estado', '!=', 'BAJA')])
            ->findOrFail($empleado);

        return view('empleados.show', [
            'e' => $e,
            'contratos' => $e->contratos()->orderByDesc('fecha_inicio')->get(),
            'historial' => $e->historialSalarial()->with('cargo')->orderByDesc('id_historial')->get(),
            'estados' => self::ESTADOS,
            'tipos' => self::TIPOS_CONTRATO,
            'jornadas' => self::JORNADAS,
            'cambios' => self::CAMBIOS_SALARIO,
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'siguienteContrato' => $this->siguienteNumero($request, 'contrato'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new Empleado([
            'fecha_ingreso' => now(), 'tipo_doc_id' => 'DPI', 'tipo_contrato' => 'INDEFINIDO', 'modalidad_trabajo' => 'PRESENCIAL', 'estado' => 'ACTIVO', 'nacionalidad' => 'Guatemalteca',
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['codigo_empleado'] ??= $this->siguienteNumero($request, 'empleado');
        $empleado = Empleado::create($datos + [
            'id_empresa' => $request->user()->id_empresa,
            'estado' => 'ACTIVO',
            'created_by' => $request->user()->id_usuario,
            'updated_by' => $request->user()->id_usuario,
        ]);

        return redirect()->route('empleados.show', $empleado->id_empleado)->with('status', "Empleado {$empleado->nombre_completo} creado. Registra su contrato y salario.");
    }

    public function edit(Request $request, int $empleado): View
    {
        return $this->formulario($request, $this->deMiEmpresa($request)->with('municipio')->findOrFail($empleado));
    }

    public function update(Request $request, int $empleado): RedirectResponse
    {
        $e = $this->deMiEmpresa($request)->findOrFail($empleado);
        if ($e->estado === 'BAJA') {
            return redirect()->route('empleados.show', $e->id_empleado)->withErrors(['empleado' => 'El empleado está de baja: reactívalo para editarlo.']);
        }
        $datos = $this->validar($request, $e);
        $datos['codigo_empleado'] ??= $e->codigo_empleado;
        $request->validate(['estado' => ['required', Rule::in(self::ESTADOS_EDITABLES)]]);
        $e->update($datos + ['estado' => $request->input('estado'), 'updated_by' => $request->user()->id_usuario]);

        return redirect()->route('empleados.show', $e->id_empleado)->with('status', "Empleado {$e->nombre_completo} actualizado.");
    }

    /** Baja laboral: cierra el contrato vigente. El registro se conserva (historial, nóminas). */
    public function baja(Request $request, int $empleado): RedirectResponse
    {
        $e = $this->deMiEmpresa($request)->findOrFail($empleado);
        $datos = $request->validate([
            'fecha_baja' => ['required', 'date', 'after_or_equal:'.$e->fecha_ingreso->toDateString()],
            'motivo_baja' => ['required', 'string', 'min:5', 'max:300'],
        ], ['fecha_baja.after_or_equal' => 'La baja no puede ser antes del ingreso.']);
        if ($e->estado === 'BAJA') {
            return back()->withErrors(['empleado' => 'El empleado ya está de baja.']);
        }

        DB::transaction(function () use ($e, $datos, $request) {
            $e->update($datos + ['estado' => 'BAJA', 'updated_by' => $request->user()->id_usuario]);
            $e->contratos()->where('estado', 'VIGENTE')->update(['estado' => 'RESCINDIDO', 'fecha_fin' => $datos['fecha_baja']]);
            // Deja de ser jefe inmediato de su equipo.
            Empleado::query()->where('id_supervisor', $e->id_empleado)->update(['id_supervisor' => null]);
        });

        return back()->with('status', "{$e->nombre_completo} quedó de baja y su contrato se cerró.");
    }

    public function reactivar(Request $request, int $empleado): RedirectResponse
    {
        $e = $this->deMiEmpresa($request)->findOrFail($empleado);
        if ($e->estado !== 'BAJA') {
            return back()->withErrors(['empleado' => 'Solo se reactivan empleados de baja.']);
        }
        $e->update(['estado' => 'ACTIVO', 'fecha_baja' => null, 'motivo_baja' => null, 'updated_by' => $request->user()->id_usuario]);

        return back()->with('status', "{$e->nombre_completo} reactivado. Registra un contrato nuevo.");
    }

    /** Borrado lógico, solo si nunca participó en nómina, asistencia ni contratos de servicio. */
    public function destroy(Request $request, int $empleado): RedirectResponse
    {
        $e = $this->deMiEmpresa($request)->findOrFail($empleado);
        $uso = Referencias::enUso('id_empleado', $e->id_empleado, [
            'detalle_nomina' => 'nóminas', 'asistencia' => 'registros de asistencia', 'cobertura_rotativo.id_rotativo' => 'coberturas (como rotativo)',
            'cobertura_rotativo.id_titular' => 'coberturas (como titular)', 'asignacion_contrato' => 'asignaciones a contratos',
            'bodega.responsable_id' => 'bodegas (como responsable)', 'empleado.id_supervisor' => 'empleados (como jefe)',
        ]);
        if ($uso) {
            return back()->withErrors(['empleado' => "No se puede eliminar a {$e->nombre_completo}: aparece en {$uso}. Dalo de baja."]);
        }
        $e->delete();

        return redirect()->route('empleados.index')->with('status', "Empleado {$e->nombre_completo} eliminado.");
    }

    // ── Contrato laboral y salario ───────────────────────────────────────

    /** Nuevo contrato vigente: el anterior queda RENOVADO y se registra la contratación en el historial. */
    public function guardarContrato(Request $request, int $empleado): RedirectResponse
    {
        $e = $this->deMiEmpresa($request)->findOrFail($empleado);
        if ($e->estado === 'BAJA') {
            return back()->withErrors(['contrato' => 'El empleado está de baja: reactívalo antes.']);
        }
        $idEmpresa = $request->user()->id_empresa;
        $request->merge(['numero_contrato' => ($n = strtoupper(trim((string) $request->input('numero_contrato')))) === '' ? null : $n]);
        $datos = $request->validateWithBag('contrato', [
            'numero_contrato' => ['nullable', 'string', 'max:50', Rule::unique('contrato_laboral', 'numero_contrato')->where('id_empresa', $idEmpresa)],
            'tipo' => ['required', Rule::in(array_keys(self::TIPOS_CONTRATO))],
            'fecha_inicio' => ['required', 'date', 'after_or_equal:'.$e->fecha_ingreso->toDateString()],
            'fecha_fin' => [Rule::requiredIf(in_array($request->input('tipo'), ['TIEMPO_DEFINIDO', 'PRUEBA', 'OBRA'], true)), 'nullable', 'date', 'after:fecha_inicio'],
            'salario_base' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'jornada' => ['required', Rule::in(array_keys(self::JORNADAS))],
            'horas_semana' => ['required', 'integer', 'min:1', 'max:72'],
        ], [
            'fecha_inicio.after_or_equal' => 'El contrato no puede empezar antes del ingreso.',
            'fecha_fin.required' => 'Un contrato a plazo, de prueba o por obra necesita fecha de fin.',
        ]);

        DB::transaction(function () use ($e, $datos, $request, $idEmpresa) {
            $anterior = $e->contratos()->where('estado', 'VIGENTE')->lockForUpdate()->first();
            $anterior?->update(['estado' => 'RENOVADO', 'fecha_fin' => $anterior->fecha_fin ?? $datos['fecha_inicio']]);
            $datos['numero_contrato'] ??= $this->siguienteNumero($request, 'contrato');
            ContratoLaboral::create($datos + [
                'id_empleado' => $e->id_empleado, 'id_empresa' => $idEmpresa, 'estado' => 'VIGENTE', 'created_by' => $request->user()->id_usuario,
            ]);
            HistorialSalarial::create([
                'id_empleado' => $e->id_empleado, 'id_cargo' => $e->id_cargo, 'salario_anterior' => $anterior?->salario_base,
                'salario_nuevo' => $datos['salario_base'], 'moneda' => $datos['moneda'], 'tipo_cambio' => 'CONTRATACION',
                'fecha_efectiva' => $datos['fecha_inicio'], 'motivo' => $anterior ? 'Nuevo contrato' : 'Contrato inicial', 'created_by' => $request->user()->id_usuario,
            ]);
            $e->update(['tipo_contrato' => $datos['tipo']]);
        });

        return back()->with('status', 'Contrato registrado.');
    }

    /** Cambio de salario sobre el contrato vigente (queda en el historial). */
    public function cambiarSalario(Request $request, int $empleado): RedirectResponse
    {
        $e = $this->deMiEmpresa($request)->findOrFail($empleado);
        $datos = $request->validateWithBag('salario', [
            'tipo_cambio' => ['required', Rule::in(array_keys(self::CAMBIOS_SALARIO))],
            'salario_nuevo' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'fecha_efectiva' => ['required', 'date'],
            'motivo' => ['required', 'string', 'min:5', 'max:300'],
            'id_cargo' => ['nullable', 'integer', Rule::exists('cargo', 'id_cargo')->where('id_empresa', $request->user()->id_empresa)->where('activo', true)],
        ]);

        return DB::transaction(function () use ($e, $datos, $request) {
            $contrato = $e->contratos()->where('estado', 'VIGENTE')->lockForUpdate()->first();
            if (! $contrato) {
                throw ValidationException::withMessages(['salario_nuevo' => 'El empleado no tiene contrato vigente.'])->errorBag('salario');
            }
            if ($datos['fecha_efectiva'] < $contrato->fecha_inicio->toDateString()) {
                throw ValidationException::withMessages(['fecha_efectiva' => 'La fecha no puede ser anterior al inicio del contrato.'])->errorBag('salario');
            }
            HistorialSalarial::create([
                'id_empleado' => $e->id_empleado, 'id_cargo' => $datos['id_cargo'] ?? $e->id_cargo, 'salario_anterior' => $contrato->salario_base,
                'salario_nuevo' => $datos['salario_nuevo'], 'moneda' => $contrato->moneda, 'tipo_cambio' => $datos['tipo_cambio'],
                'fecha_efectiva' => $datos['fecha_efectiva'], 'motivo' => $datos['motivo'], 'aprobado_por' => $request->user()->id_usuario,
                'created_by' => $request->user()->id_usuario,
            ]);
            $contrato->update(['salario_base' => $datos['salario_nuevo']]);
            if (! empty($datos['id_cargo'])) {
                $e->update(['id_cargo' => $datos['id_cargo']]); // una promoción puede cambiar el cargo
            }

            return back()->with('status', 'Salario actualizado a '.$contrato->moneda.' '.number_format((float) $datos['salario_nuevo'], 2).'.');
        });
    }

    // ── Privados ─────────────────────────────────────────────────────────

    private function formulario(Request $request, Empleado $e): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('empleados.form', [
            'e' => $e,
            'sucursales' => Sucursal::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')->get(),
            'departamentos' => DepartamentoOrg::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')->get(),
            'cargos' => Cargo::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')->get(),
            'supervisores' => $this->deMiEmpresa($request)->where('estado', '!=', 'BAJA')->when($e->exists, fn ($q) => $q->whereKeyNot($e->id_empleado))
                ->orderBy('primer_nombre')->get(),
            'divisiones' => DivisionGeografica::query()->where('activo', true)->orderBy('nombre')->get(['id_division', 'nombre']),
            'municipios' => Municipio::query()->where('activo', true)->orderBy('nombre')->get(['id_municipio', 'id_division', 'nombre']),
            'estados' => collect(self::ESTADOS)->only(self::ESTADOS_EDITABLES)->all(),
            'tipos' => self::TIPOS_CONTRATO,
        ]);
    }

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        // Cada palabra debe aparecer en algún campo del nombre, el código o el documento («ana lópez»).
        $palabras = array_filter(preg_split('/\s+/', $buscar));

        return $this->deMiEmpresa($request)
            ->when($palabras, function ($q) use ($palabras) {
                foreach ($palabras as $p) {
                    $q->where(fn ($q) => $q->where('primer_nombre', 'like', "%{$p}%")->orWhere('segundo_nombre', 'like', "%{$p}%")
                        ->orWhere('primer_apellido', 'like', "%{$p}%")->orWhere('segundo_apellido', 'like', "%{$p}%")->orWhere('apellido_casada', 'like', "%{$p}%")
                        ->orWhere('codigo_empleado', 'like', "%{$p}%")->orWhere('dpi_nit', 'like', "%{$p}%"));
                }
            })
            ->when($request->filled('departamento'), fn ($q) => $q->where('id_depto_org', $request->integer('departamento')))
            ->when(array_key_exists((string) $request->query('estado'), self::ESTADOS), fn ($q) => $q->where('estado', $request->query('estado')),
                fn ($q) => $q->where('estado', '!=', 'BAJA')); // por defecto no se listan las bajas
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return Empleado::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** EMP-0001 (empleados) o CL-AAAA-0001 (contratos laborales), por empresa. */
    private function siguienteNumero(Request $request, string $que): string
    {
        [$tabla, $columna, $prefijo] = $que === 'empleado'
            ? ['empleado', 'codigo_empleado', 'EMP-']
            : ['contrato_laboral', 'numero_contrato', 'CL-'.now()->year.'-'];
        $ultimo = DB::table($tabla)->where('id_empresa', $request->user()->id_empresa)->where($columna, 'like', $prefijo.'%')->pluck($columna)
            ->map(fn ($n) => (int) substr($n, strlen($prefijo)))->max() ?? 0;

        return $prefijo.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Empleado $e = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $documento = strtoupper(preg_replace('/[\s\-]+/', '', (string) $request->input('dpi_nit')));
        $request->merge([
            'dpi_nit' => $documento,
            'codigo_empleado' => ($c = strtoupper(trim((string) $request->input('codigo_empleado')))) === '' ? null : $c,
            'nit_personal' => ($n = strtoupper(preg_replace('/[\s\-]+/', '', (string) $request->input('nit_personal')))) === '' ? null : $n,
            'es_rotativo' => $request->boolean('es_rotativo'),
        ]);
        $mia = fn (string $tabla, string $llave) => Rule::exists($tabla, $llave)->where('id_empresa', $idEmpresa);

        $datos = $request->validate([
            'primer_nombre' => ['required', 'string', 'max:80'],
            'segundo_nombre' => ['nullable', 'string', 'max:80'],
            'primer_apellido' => ['required', 'string', 'max:80'],
            'segundo_apellido' => ['nullable', 'string', 'max:80'],
            'apellido_casada' => ['nullable', 'string', 'max:80'],
            'tipo_doc_id' => ['required', Rule::in(['DPI', 'DUI', 'DNI', 'PASAPORTE', 'OTRO'])],
            // El DPI de Guatemala (CUI) tiene 13 dígitos.
            'dpi_nit' => ['required', 'string', 'max:20', Rule::when($request->input('tipo_doc_id') === 'DPI', ['digits:13']),
                Rule::unique('empleado', 'dpi_nit')->where('id_empresa', $idEmpresa)->ignore($e?->id_empleado, 'id_empleado')],
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
            'codigo_empleado' => ['nullable', 'string', 'max:30', Rule::unique('empleado', 'codigo_empleado')->where('id_empresa', $idEmpresa)->ignore($e?->id_empleado, 'id_empleado')],
            'id_sucursal' => ['nullable', 'integer', $mia('sucursal', 'id_sucursal')],
            'id_depto_org' => ['nullable', 'integer', $mia('departamento_org', 'id_depto_org')],
            'id_cargo' => ['nullable', 'integer', $mia('cargo', 'id_cargo')],
            'id_supervisor' => ['nullable', 'integer', $mia('empleado', 'id_empleado')->whereNull('deleted_at')],
            'fecha_ingreso' => ['required', 'date'],
            'tipo_contrato' => ['required', Rule::in(array_keys(self::TIPOS_CONTRATO))],
            'modalidad_trabajo' => ['required', Rule::in(['PRESENCIAL', 'REMOTO', 'HIBRIDO'])],
            // Personal rotativo: cubre a otros y cobra por día (Asistencia → Personal rotativo).
            'es_rotativo' => ['boolean'],
            'tarifa_dia' => ['nullable', 'numeric', 'min:0.01', 'max:999999', 'required_if:es_rotativo,true'],
        ], [
            'tarifa_dia.required_if' => 'Un rotativo necesita su tarifa diaria.',
            'dpi_nit.digits' => 'El DPI debe tener 13 dígitos.',
            'dpi_nit.unique' => 'Ya hay un empleado con ese documento.',
            'codigo_empleado.unique' => 'Ya hay un empleado con ese código.',
            'nit_personal.regex' => 'El NIT solo lleva números y, al final, una K.',
            'fecha_nacimiento.before' => 'El empleado debe tener al menos 14 años.',
        ]);

        // El jefe inmediato no puede ser él mismo ni alguien de su propio equipo (evita ciclos).
        if (! empty($datos['id_supervisor']) && $e) {
            for ($jefe = (int) $datos['id_supervisor'], $i = 0; $jefe && $i < 50; $i++) {
                if ($jefe === $e->id_empleado) {
                    throw ValidationException::withMessages(['id_supervisor' => 'El jefe inmediato no puede ser el mismo empleado ni alguien a su cargo.']);
                }
                $jefe = (int) Empleado::query()->whereKey($jefe)->value('id_supervisor');
            }
        }

        return $datos + array_fill_keys(['segundo_nombre', 'segundo_apellido', 'apellido_casada', 'igss_afiliacion', 'fecha_nacimiento', 'genero', 'estado_civil', 'nacionalidad',
            'email_personal', 'email_corporativo', 'telefono_personal', 'telefono_emergencia', 'contacto_emergencia', 'id_municipio', 'direccion', 'id_sucursal', 'id_depto_org',
            'id_cargo', 'id_supervisor', 'tarifa_dia'], null);
    }
}

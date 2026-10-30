<?php

namespace App\Http\Controllers;

use App\Models\Core\CentroCosto;
use App\Models\RRHH\Cargo;
use App\Models\RRHH\DepartamentoOrg;
use App\Support\Referencias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Departamentos y cargos de la empresa. Permiso: empleados.configurar. */
class OrganizacionController extends Controller
{
    public const NIVELES = [1 => 'Operativo', 2 => 'Supervisión', 3 => 'Gerencia', 4 => 'Dirección'];

    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('organizacion.index', [
            'departamentos' => $this->departamentos($request)->with('padre')->withCount(['empleados', 'cargos'])->orderBy('nombre')->get(),
            'cargos' => $this->cargos($request)->with('departamento')->withCount('empleados')->orderBy('nivel_jerarquico', 'desc')->orderBy('nombre')->get(),
            'centros' => CentroCosto::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('codigo')->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'niveles' => self::NIVELES,
        ]);
    }

    public function guardarDepartamento(Request $request, ?int $departamento = null): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $d = $departamento ? $this->departamentos($request)->findOrFail($departamento) : new DepartamentoOrg(['id_empresa' => $idEmpresa]);
        $request->merge(['codigo' => ($c = strtoupper(trim((string) $request->input('codigo')))) === '' ? null : $c]);
        $datos = $request->validateWithBag('departamento', [
            'nombre' => ['required', 'string', 'max:150', Rule::unique('departamento_org', 'nombre')->where('id_empresa', $idEmpresa)->ignore($d->id_depto_org, 'id_depto_org')],
            'codigo' => ['nullable', 'string', 'max:20'],
            'id_padre' => ['nullable', 'integer', Rule::exists('departamento_org', 'id_depto_org')->where('id_empresa', $idEmpresa)],
            'centro_costo' => ['nullable', Rule::exists('centro_costo', 'codigo')->where('id_empresa', $idEmpresa)],
        ], ['nombre.unique' => 'Ya hay un departamento con ese nombre.']);

        // Sin ciclos: el padre no puede ser el mismo departamento ni uno que dependa de él.
        for ($p = $datos['id_padre'] ?? null, $i = 0; $d->exists && $p && $i < 50; $i++) {
            if ((int) $p === $d->id_depto_org) {
                throw ValidationException::withMessages(['id_padre' => 'Un departamento no puede depender de sí mismo ni de uno de sus subdepartamentos.'])->errorBag('departamento');
            }
            $p = DepartamentoOrg::query()->whereKey($p)->value('id_padre');
        }
        $d->fill($datos + ['id_padre' => null, 'centro_costo' => null, 'activo' => $request->boolean('activo', true)])->save();

        return redirect()->route('organizacion.index')->with('status', "Departamento «{$d->nombre}» guardado.");
    }

    public function eliminarDepartamento(Request $request, int $departamento): RedirectResponse
    {
        $d = $this->departamentos($request)->findOrFail($departamento);
        $uso = Referencias::enUso('id_depto_org', $d->id_depto_org, ['empleado' => 'empleados', 'cargo' => 'cargos', 'departamento_org.id_padre' => 'subdepartamentos']);
        if ($uso) {
            return back()->withErrors(['departamento' => "No se puede eliminar «{$d->nombre}»: lo usan {$uso}. Puedes desactivarlo."]);
        }
        $d->delete();

        return back()->with('status', "Departamento «{$d->nombre}» eliminado.");
    }

    public function guardarCargo(Request $request, ?int $cargo = null): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $c = $cargo ? $this->cargos($request)->findOrFail($cargo) : new Cargo(['id_empresa' => $idEmpresa]);
        $datos = $request->validateWithBag('cargo', [
            'nombre' => ['required', 'string', 'max:150', Rule::unique('cargo', 'nombre')->where('id_empresa', $idEmpresa)->ignore($c->id_cargo, 'id_cargo')],
            'id_depto_org' => ['nullable', 'integer', Rule::exists('departamento_org', 'id_depto_org')->where('id_empresa', $idEmpresa)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'nivel_jerarquico' => ['required', 'integer', Rule::in(array_keys(self::NIVELES))],
            'salario_min' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'salario_max' => ['nullable', 'numeric', 'min:0', 'max:9999999999', 'gte:salario_min'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
        ], ['nombre.unique' => 'Ya hay un cargo con ese nombre.', 'salario_max.gte' => 'El salario máximo no puede ser menor que el mínimo.']);
        $c->fill($datos + ['id_depto_org' => null, 'descripcion' => null, 'salario_min' => null, 'salario_max' => null,
            'requiere_vehiculo' => $request->boolean('requiere_vehiculo'), 'activo' => $request->boolean('activo', true)])->save();

        return redirect()->route('organizacion.index')->with('status', "Cargo «{$c->nombre}» guardado.");
    }

    public function eliminarCargo(Request $request, int $cargo): RedirectResponse
    {
        $c = $this->cargos($request)->findOrFail($cargo);
        $uso = Referencias::enUso('id_cargo', $c->id_cargo, ['empleado' => 'empleados', 'historial_salarial' => 'registros del historial salarial']);
        if ($uso) {
            return back()->withErrors(['cargo' => "No se puede eliminar «{$c->nombre}»: lo usan {$uso}. Puedes desactivarlo."]);
        }
        $c->delete();

        return back()->with('status', "Cargo «{$c->nombre}» eliminado.");
    }

    private function departamentos(Request $request): Builder
    {
        return DepartamentoOrg::query()->where('id_empresa', $request->user()->id_empresa);
    }

    private function cargos(Request $request): Builder
    {
        return Cargo::query()->where('id_empresa', $request->user()->id_empresa);
    }
}

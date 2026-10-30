<?php

namespace App\Http\Controllers;

use App\Models\Core\DivisionGeografica;
use App\Models\Core\Municipio;
use App\Models\Core\Pais;
use App\Models\Core\Sucursal;
use App\Support\Referencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Sucursales de la empresa (solo Administrador). Siempre hay como máximo una casa matriz. */
class SucursalController extends Controller
{
    public function index(Request $request): View
    {
        return view('sucursales.index', [
            'sucursales' => $this->deMiEmpresa($request)->with(['pais', 'division', 'municipio'])
                ->withCount('usuarios')->orderByDesc('es_casa_matriz')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Sucursal(['activo' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $sucursal = DB::transaction(function () use ($request, $datos) {
            $this->quitarCasaMatrizSiCorresponde($request, $datos);

            return Sucursal::create($datos + ['id_empresa' => $request->user()->id_empresa]);
        });

        return redirect()->route('sucursales.index')->with('status', "Sucursal «{$sucursal->nombre}» creada.");
    }

    public function edit(Request $request, int $sucursal): View
    {
        return $this->formulario($this->deMiEmpresa($request)->findOrFail($sucursal));
    }

    public function update(Request $request, int $sucursal): RedirectResponse
    {
        $sucursal = $this->deMiEmpresa($request)->findOrFail($sucursal);
        $datos = $this->validar($request);

        if ($sucursal->es_casa_matriz && ! $datos['es_casa_matriz']) {
            return back()->withErrors(['es_casa_matriz' => 'Para quitar la casa matriz, marca otra sucursal como casa matriz.'])->withInput();
        }
        if ($datos['es_casa_matriz'] && ! $datos['activo']) {
            return back()->withErrors(['activo' => 'La casa matriz no se puede desactivar.'])->withInput();
        }

        DB::transaction(function () use ($request, $datos, $sucursal) {
            $this->quitarCasaMatrizSiCorresponde($request, $datos, $sucursal);
            $sucursal->update($datos);
        });

        return redirect()->route('sucursales.index')->with('status', "Sucursal «{$sucursal->nombre}» actualizada.");
    }

    public function estado(Request $request, int $sucursal): RedirectResponse
    {
        $sucursal = $this->deMiEmpresa($request)->findOrFail($sucursal);
        if ($sucursal->es_casa_matriz && $sucursal->activo) {
            return back()->withErrors(['sucursal' => 'La casa matriz no se puede desactivar.']);
        }
        $sucursal->update(['activo' => ! $sucursal->activo]);

        return back()->with('status', "Sucursal «{$sucursal->nombre}» ".($sucursal->activo ? 'activada.' : 'desactivada.'));
    }

    public function destroy(Request $request, int $sucursal): RedirectResponse
    {
        $sucursal = $this->deMiEmpresa($request)->findOrFail($sucursal);
        if ($sucursal->es_casa_matriz) {
            return back()->withErrors(['sucursal' => 'La casa matriz no se puede eliminar.']);
        }
        if ($uso = Referencias::enUso('id_sucursal', $sucursal->id_sucursal, ['bodega' => 'bodegas', 'empleado' => 'empleados', 'usuario' => 'usuarios'])) {
            return back()->withErrors(['sucursal' => "No se puede eliminar «{$sucursal->nombre}»: la usan {$uso}. Puedes desactivarla."]);
        }
        $sucursal->delete();

        return redirect()->route('sucursales.index')->with('status', "Sucursal «{$sucursal->nombre}» eliminada.");
    }

    private function formulario(Sucursal $sucursal): View
    {
        return view('sucursales.form', [
            'sucursal' => $sucursal,
            'paises' => Pais::query()->where('activo', true)->orderBy('nombre')->get(['id_pais', 'nombre']),
            // Catálogos pequeños: se filtran en el navegador (selects en cascada, sin API).
            'divisiones' => DivisionGeografica::query()->where('activo', true)->orderBy('nombre')->get(['id_division', 'id_pais', 'nombre']),
            'municipios' => Municipio::query()->where('activo', true)->orderBy('nombre')->get(['id_municipio', 'id_division', 'nombre']),
        ]);
    }

    private function deMiEmpresa(Request $request)
    {
        return Sucursal::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:300'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'id_pais' => ['nullable', 'integer', 'required_with:id_division', Rule::exists('pais', 'id_pais')->where('activo', true)],
            // El departamento debe ser del país y el municipio del departamento.
            'id_division' => ['nullable', 'integer', 'required_with:id_municipio',
                Rule::exists('division_geografica', 'id_division')->where('activo', true)->where('id_pais', (int) $request->input('id_pais'))],
            'id_municipio' => ['nullable', 'integer',
                Rule::exists('municipio', 'id_municipio')->where('activo', true)->where('id_division', (int) $request->input('id_division'))],
        ], [
            'id_division.exists' => 'El departamento no pertenece al país elegido.',
            'id_municipio.exists' => 'El municipio no pertenece al departamento elegido.',
        ]);

        return $datos + [
            'id_pais' => null, 'id_division' => null, 'id_municipio' => null,
            'es_casa_matriz' => $request->boolean('es_casa_matriz'),
            'activo' => $request->boolean('activo'),
        ];
    }

    private function quitarCasaMatrizSiCorresponde(Request $request, array $datos, ?Sucursal $excepto = null): void
    {
        if ($datos['es_casa_matriz']) {
            $this->deMiEmpresa($request)->when($excepto, fn ($q) => $q->whereKeyNot($excepto->id_sucursal))
                ->update(['es_casa_matriz' => false]);
        }
    }
}

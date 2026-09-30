<?php

namespace App\Http\Controllers;

use App\Models\Core\DivisionGeografica;
use App\Models\Core\Municipio;
use App\Models\Core\Pais;
use App\Support\Referencias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Geografía (solo Administrador): países, departamentos y municipios. Es un
 * catálogo compartido por todas las empresas. {tipo} = pais | division | municipio.
 */
class GeografiaController extends Controller
{
    private const TIPOS = [
        'pais' => ['modelo' => Pais::class, 'llave' => 'id_pais', 'nombre' => 'país'],
        'division' => ['modelo' => DivisionGeografica::class, 'llave' => 'id_division', 'nombre' => 'departamento'],
        'municipio' => ['modelo' => Municipio::class, 'llave' => 'id_municipio', 'nombre' => 'municipio'],
    ];

    /** Dónde se usa cada nivel: tabla => texto (para no borrar lo que está en uso). */
    private const USOS = [
        'pais' => ['division_geografica' => 'departamentos', 'cliente' => 'clientes', 'empresa' => 'empresas', 'prospecto' => 'prospectos', 'proveedor' => 'proveedores', 'sucursal' => 'sucursales'],
        'division' => ['municipio' => 'municipios', 'sucursal' => 'sucursales'],
        'municipio' => ['cliente' => 'clientes', 'empleado' => 'empleados', 'empresa' => 'empresas', 'sitio_trabajo' => 'sitios de trabajo', 'sucursal' => 'sucursales'],
    ];

    public function index(Request $request): View
    {
        $idPais = $request->integer('pais') ?: null;
        $idDivision = $request->integer('departamento') ?: null;

        return view('geografia.index', [
            'pestana' => in_array($request->query('pestana'), array_keys(self::TIPOS), true) ? $request->query('pestana') : 'pais',
            'paises' => Pais::query()->withCount('divisiones')->orderBy('nombre')->get(),
            'divisiones' => DivisionGeografica::query()->with('pais')->withCount('municipios')
                ->when($idPais, fn ($q) => $q->where('id_pais', $idPais))->orderBy('nombre')->get(),
            'municipios' => Municipio::query()->with('division.pais')
                ->when($idDivision, fn ($q) => $q->where('id_division', $idDivision))
                ->when($idPais && ! $idDivision, fn ($q) => $q->whereHas('division', fn ($d) => $d->where('id_pais', $idPais)))
                ->orderBy('nombre')->get(),
            'todasDivisiones' => DivisionGeografica::query()->with('pais')->orderBy('nombre')->get(),
            'idPais' => $idPais,
            'idDivision' => $idDivision,
        ]);
    }

    public function store(Request $request, string $tipo): RedirectResponse
    {
        $conf = self::TIPOS[$tipo];
        $registro = $conf['modelo']::create($this->validar($request, $tipo) + ['activo' => true]);

        return $this->volver($request, $tipo)->with('status', ucfirst($conf['nombre'])." «{$registro->nombre}» creado.");
    }

    public function edit(string $tipo, int $id): View
    {
        return view('geografia.form', [
            'tipo' => $tipo,
            'nombreTipo' => self::TIPOS[$tipo]['nombre'],
            'registro' => $this->buscar($tipo, $id),
            'paises' => Pais::query()->orderBy('nombre')->get(),
            'divisiones' => DivisionGeografica::query()->with('pais')->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, string $tipo, int $id): RedirectResponse
    {
        $registro = $this->buscar($tipo, $id);
        $registro->update($this->validar($request, $tipo, $registro) + ['activo' => $request->boolean('activo')]);

        return $this->volver($request, $tipo)->with('status', ucfirst(self::TIPOS[$tipo]['nombre'])." «{$registro->nombre}» actualizado.");
    }

    public function estado(Request $request, string $tipo, int $id): RedirectResponse
    {
        $registro = $this->buscar($tipo, $id);
        $registro->update(['activo' => ! $registro->activo]);

        return back()->with('status', ucfirst(self::TIPOS[$tipo]['nombre'])." «{$registro->nombre}» ".($registro->activo ? 'activado.' : 'desactivado.'));
    }

    public function destroy(Request $request, string $tipo, int $id): RedirectResponse
    {
        $conf = self::TIPOS[$tipo];
        $registro = $this->buscar($tipo, $id);
        if ($uso = Referencias::enUso($conf['llave'], $id, self::USOS[$tipo])) {
            return back()->withErrors(['geografia' => "No se puede eliminar «{$registro->nombre}»: lo usan {$uso}. Puedes desactivarlo."]);
        }
        $registro->delete();

        return back()->with('status', ucfirst($conf['nombre'])." «{$registro->nombre}» eliminado.");
    }

    private function buscar(string $tipo, int $id): Model
    {
        return self::TIPOS[$tipo]['modelo']::query()->findOrFail($id);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, string $tipo, ?Model $registro = null): array
    {
        $request->merge(['nombre' => trim((string) $request->input('nombre'))]);
        $ignorar = fn ($regla) => $registro ? $regla->ignore($registro->getKey(), self::TIPOS[$tipo]['llave']) : $regla;

        return match ($tipo) {
            'pais' => $this->mayusculas($request->validate([
                'nombre' => ['required', 'string', 'max:100', $ignorar(Rule::unique('pais', 'nombre'))],
                'codigo_iso2' => ['nullable', 'alpha', 'size:2', $ignorar(Rule::unique('pais', 'codigo_iso2'))],
                'codigo_iso3' => ['nullable', 'alpha', 'size:3', $ignorar(Rule::unique('pais', 'codigo_iso3'))],
                'prefijo_tel' => ['nullable', 'regex:/^\+[0-9]{1,4}$/'],
                'moneda_defecto' => ['nullable', 'alpha', 'size:3'],
            ], ['prefijo_tel.regex' => 'El prefijo telefónico es + y de 1 a 4 números (p. ej. +502).'])),
            // Nombre único dentro del país / del departamento.
            'division' => $request->validate([
                'id_pais' => ['required', 'integer', Rule::exists('pais', 'id_pais')],
                'nombre' => ['required', 'string', 'max:100', $ignorar(Rule::unique('division_geografica', 'nombre')->where('id_pais', (int) $request->input('id_pais')))],
            ], ['nombre.unique' => 'Ese departamento ya existe en el país.']) + ['tipo' => 'departamento'],
            'municipio' => $request->validate([
                'id_division' => ['required', 'integer', Rule::exists('division_geografica', 'id_division')],
                'nombre' => ['required', 'string', 'max:100', $ignorar(Rule::unique('municipio', 'nombre')->where('id_division', (int) $request->input('id_division')))],
            ], ['nombre.unique' => 'Ese municipio ya existe en el departamento.']),
        };
    }

    private function mayusculas(array $datos): array
    {
        foreach (['codigo_iso2', 'codigo_iso3', 'moneda_defecto'] as $campo) {
            if (! empty($datos[$campo])) {
                $datos[$campo] = strtoupper($datos[$campo]);
            }
        }

        return $datos;
    }

    private function volver(Request $request, string $tipo): RedirectResponse
    {
        return redirect()->route('geografia.index', array_filter([
            'pestana' => $tipo,
            'pais' => $request->integer('volver_pais') ?: null,
            'departamento' => $request->integer('volver_departamento') ?: null,
        ]));
    }
}

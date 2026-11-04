<?php

namespace App\Http\Controllers;

use App\Http\Requests\Core\GuardarSucursalRequest;
use App\Models\Core\DivisionGeografica;
use App\Models\Core\Municipio;
use App\Models\Core\Pais;
use App\Models\Core\Sucursal;
use App\Support\Referencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Sucursales y agencias de la empresa (solo Administrador). Una agencia depende de una sucursal
 * activa; la casa matriz es siempre una sucursal y hay como máximo una.
 */
class SucursalController extends Controller
{
    /** Lo que impide eliminar una sede (se puede desactivar). */
    private const USOS = [
        'bodega' => 'bodegas', 'empleado' => 'empleados', 'usuario' => 'usuarios', 'sucursal.id_sucursal_padre' => 'agencias',
        'factura' => 'facturas', 'orden_compra' => 'órdenes de compra', 'ticket' => 'tickets', 'serie_facturacion' => 'series de facturación',
    ];

    public function index(Request $request): View
    {
        return view('sucursales.index', [
            'sucursales' => Sucursal::enArbol($this->deMiEmpresa($request)->with(['pais', 'division', 'municipio', 'padre'])
                ->withCount('usuarios')->orderByDesc('es_casa_matriz')->orderBy('nombre')->get()),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Sucursal(['activo' => true]));
    }

    public function store(GuardarSucursalRequest $request): RedirectResponse
    {
        $datos = $request->datosLimpios();
        if ($error = $this->reglasDeTipo($datos)) {
            return back()->withErrors($error)->withInput();
        }
        $sucursal = DB::transaction(function () use ($request, $datos) {
            $this->quitarCasaMatrizSiCorresponde($request, $datos);

            return Sucursal::create($datos + ['id_empresa' => $this->idEmpresa($request)]);
        });

        return redirect()->route('sucursales.index')->with('status', Sucursal::TIPOS[$sucursal->tipo]." «{$sucursal->nombre}» creada.");
    }

    public function edit(Request $request, int $sucursal): View
    {
        return $this->formulario($this->deMiEmpresa($request)->findOrFail($sucursal));
    }

    public function update(GuardarSucursalRequest $request, int $sucursal): RedirectResponse
    {
        $sucursal = $this->deMiEmpresa($request)->findOrFail($sucursal);
        $datos = $request->datosLimpios();

        if ($error = $this->reglasDeTipo($datos, $sucursal)) {
            return back()->withErrors($error)->withInput();
        }
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

        return redirect()->route('sucursales.index')->with('status', Sucursal::TIPOS[$sucursal->tipo]." «{$sucursal->nombre}» actualizada.");
    }

    public function estado(Request $request, int $sucursal): RedirectResponse
    {
        $sucursal = $this->deMiEmpresa($request)->findOrFail($sucursal);
        if ($sucursal->es_casa_matriz && $sucursal->activo) {
            return back()->withErrors(['sucursal' => 'La casa matriz no se puede desactivar.']);
        }
        if ($sucursal->activo && ($agencias = $sucursal->agencias()->where('activo', true)->count())) {
            return back()->withErrors(['sucursal' => "«{$sucursal->nombre}» tiene {$agencias} ".($agencias === 1 ? 'agencia activa' : 'agencias activas').': desactívalas o pásalas a otra sucursal primero.']);
        }
        if (! $sucursal->activo && $sucursal->esAgencia() && ! $sucursal->padre?->activo) {
            return back()->withErrors(['sucursal' => "Activa primero la sucursal de la que depende «{$sucursal->nombre}», o pásala a otra."]);
        }
        $sucursal->update(['activo' => ! $sucursal->activo]);

        return back()->with('status', Sucursal::TIPOS[$sucursal->tipo]." «{$sucursal->nombre}» ".($sucursal->activo ? 'activada.' : 'desactivada.'));
    }

    public function destroy(Request $request, int $sucursal): RedirectResponse
    {
        $sucursal = $this->deMiEmpresa($request)->findOrFail($sucursal);
        if ($sucursal->es_casa_matriz) {
            return back()->withErrors(['sucursal' => 'La casa matriz no se puede eliminar.']);
        }
        if ($uso = Referencias::enUso('id_sucursal', $sucursal->id_sucursal, self::USOS)) {
            return back()->withErrors(['sucursal' => "No se puede eliminar «{$sucursal->nombre}»: la usan {$uso}. Puedes desactivarla."]);
        }
        $sucursal->delete();

        return redirect()->route('sucursales.index')->with('status', Sucursal::TIPOS[$sucursal->tipo]." «{$sucursal->nombre}» eliminada.");
    }

    private function formulario(Sucursal $sucursal): View
    {
        return view('sucursales.form', [
            'sucursal' => $sucursal,
            'tipos' => Sucursal::TIPOS,
            // Las agencias dependen de una sucursal activa (no de sí misma ni de otra agencia).
            'padres' => Sucursal::query()->where('id_empresa', $this->idEmpresa())->where('tipo', 'SUCURSAL')->where('activo', true)
                ->when($sucursal->exists, fn ($q) => $q->whereKeyNot($sucursal->id_sucursal))->orderByDesc('es_casa_matriz')->orderBy('nombre')->get(['id_sucursal', 'nombre']),
            'paises' => Pais::query()->where('activo', true)->orderBy('nombre')->get(['id_pais', 'nombre']),
            // Catálogos pequeños: se filtran en el navegador (selects en cascada, sin API).
            'divisiones' => DivisionGeografica::query()->where('activo', true)->orderBy('nombre')->get(['id_division', 'id_pais', 'nombre']),
            'municipios' => Municipio::query()->where('activo', true)->orderBy('nombre')->get(['id_municipio', 'id_division', 'nombre']),
        ]);
    }

    /** @return array<string, string>|null error de validación si el tipo no cuadra */
    private function reglasDeTipo(array $datos, ?Sucursal $sucursal = null): ?array
    {
        if ($datos['tipo'] === 'AGENCIA') {
            if ($datos['es_casa_matriz']) {
                return ['es_casa_matriz' => 'Una agencia no puede ser la casa matriz.'];
            }
            if ($sucursal && (int) $datos['id_sucursal_padre'] === $sucursal->id_sucursal) {
                return ['id_sucursal_padre' => 'Una agencia no puede depender de sí misma.'];
            }
            if ($sucursal && ($n = $sucursal->agencias()->count())) {
                return ['tipo' => "No se puede volver agencia: tiene {$n} ".($n === 1 ? 'agencia' : 'agencias').' que dependen de ella.'];
            }
        }

        return null;
    }

    private function deMiEmpresa(Request $request)
    {
        return $this->consultaEmpresa(Sucursal::class, $request);
    }

    private function quitarCasaMatrizSiCorresponde(Request $request, array $datos, ?Sucursal $excepto = null): void
    {
        if ($datos['es_casa_matriz']) {
            $this->deMiEmpresa($request)->when($excepto, fn ($q) => $q->whereKeyNot($excepto->id_sucursal))
                ->update(['es_casa_matriz' => false]);
        }
    }
}

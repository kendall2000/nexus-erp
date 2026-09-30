<?php

namespace App\Http\Controllers;

use App\Models\Core\Sucursal;
use App\Models\Inventario\Bodega;
use App\Models\RRHH\Empleado;
use App\Support\Referencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Bodegas de la empresa. Permisos: INV.BODEGAS.VER / .GESTIONAR. */
class BodegaController extends Controller
{
    public function index(Request $request): View
    {
        $bodegas = $this->deMiEmpresa($request)
            ->with(['sucursal', 'responsable'])
            ->withCount(['stocks as productos_con_stock' => fn ($q) => $q->where('cantidad_actual', '>', 0)])
            ->withSum(['stocks as valor_inventario' => fn ($q) => $q->where('cantidad_actual', '>', 0)], DB::raw('cantidad_actual * costo_promedio'))
            ->orderBy('nombre')->get();

        return view('bodegas.index', ['bodegas' => $bodegas]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new Bodega(['activo' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $bodega = Bodega::create($this->validar($request) + ['id_empresa' => $request->user()->id_empresa]);

        return redirect()->route('bodegas.index')->with('status', "Bodega «{$bodega->nombre}» creada.");
    }

    public function edit(Request $request, int $bodega): View
    {
        return $this->formulario($request, $this->deMiEmpresa($request)->findOrFail($bodega));
    }

    public function update(Request $request, int $bodega): RedirectResponse
    {
        $bodega = $this->deMiEmpresa($request)->findOrFail($bodega);
        $bodega->update($this->validar($request));

        return redirect()->route('bodegas.index')->with('status', "Bodega «{$bodega->nombre}» actualizada.");
    }

    public function estado(Request $request, int $bodega): RedirectResponse
    {
        $bodega = $this->deMiEmpresa($request)->findOrFail($bodega);
        $bodega->update(['activo' => ! $bodega->activo]);

        return back()->with('status', "Bodega «{$bodega->nombre}» ".($bodega->activo ? 'activada.' : 'desactivada.'));
    }

    public function destroy(Request $request, int $bodega): RedirectResponse
    {
        $bodega = $this->deMiEmpresa($request)->findOrFail($bodega);

        if ($bodega->stocks()->where('cantidad_actual', '>', 0)->exists()) {
            return back()->withErrors(['bodega' => "No se puede eliminar «{$bodega->nombre}»: tiene productos en existencia."]);
        }
        $uso = Referencias::enUso('id_bodega', $bodega->id_bodega, [
            'orden_compra' => 'órdenes de compra', 'recepcion_mercaderia' => 'recepciones', 'movimiento_inventario' => 'movimientos de inventario',
        ]);
        if ($uso) {
            return back()->withErrors(['bodega' => "No se puede eliminar «{$bodega->nombre}»: la usan {$uso}. Puedes desactivarla."]);
        }

        DB::transaction(function () use ($bodega) {
            $bodega->stocks()->delete(); // solo quedan filas en cero
            $bodega->delete();
        });

        return redirect()->route('bodegas.index')->with('status', "Bodega «{$bodega->nombre}» eliminada.");
    }

    private function formulario(Request $request, Bodega $bodega): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('bodegas.form', [
            'bodega' => $bodega,
            'sucursales' => Sucursal::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')->get(),
            'empleados' => Empleado::query()->where('id_empresa', $idEmpresa)->where('estado', 'ACTIVO')
                ->orderBy('primer_nombre')->orderBy('primer_apellido')->get(),
        ]);
    }

    private function deMiEmpresa(Request $request)
    {
        return Bodega::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        $idEmpresa = $request->user()->id_empresa;

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'ubicacion' => ['nullable', 'string', 'max:300'],
            'id_sucursal' => ['nullable', 'integer', Rule::exists('sucursal', 'id_sucursal')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'responsable_id' => ['nullable', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)->where('estado', 'ACTIVO')],
        ], [
            'id_sucursal.exists' => 'La sucursal elegida no es válida.',
            'responsable_id.exists' => 'El responsable elegido no es válido.',
        ]);

        return $datos + ['id_sucursal' => null, 'responsable_id' => null, 'activo' => $request->boolean('activo')];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finanzas\GuardarSerieFacturacionRequest;
use App\Models\Finanzas\SerieFacturacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Series de facturación: prefijo y correlativo de cada tipo de documento.
 * Permisos: series_facturacion.ver / crear / editar / eliminar.
 * El correlativo solo se ajusta mientras la serie no tenga documentos (p. ej. para continuar
 * la numeración de otro sistema); después lo maneja FacturaController con la serie bloqueada.
 */
class SerieFacturacionController extends Controller
{
    public const TIPOS = [
        'FACTURA' => 'Factura', 'CREDITO_FISCAL' => 'Crédito fiscal', 'NOTA_CREDITO' => 'Nota de crédito',
        'NOTA_DEBITO' => 'Nota de débito', 'RECIBO' => 'Recibo',
    ];

    public function index(Request $request): View
    {
        return view('series-facturacion.index', [
            'series' => $this->deMiEmpresa($request)->withCount('facturas')->orderBy('tipo')->orderBy('codigo_serie')->get(),
            'tipos' => self::TIPOS,
        ]);
    }

    public function create(): View
    {
        return view('series-facturacion.form', ['serie' => new SerieFacturacion(['tipo' => 'FACTURA', 'ultimo_numero' => 0, 'activo' => true]), 'usada' => false, 'tipos' => self::TIPOS]);
    }

    public function store(GuardarSerieFacturacionRequest $request): RedirectResponse
    {
        $serie = SerieFacturacion::create($request->datosLimpios() + ['id_empresa' => $this->idEmpresa($request)]);

        return redirect()->route('series-facturacion.index')->with('status', "Serie {$serie->codigo_serie} creada: el siguiente número será {$serie->formatearNumero($serie->ultimo_numero + 1)}.");
    }

    public function edit(Request $request, int $serie): View
    {
        $serie = $this->deMiEmpresa($request)->findOrFail($serie);

        return view('series-facturacion.form', ['serie' => $serie, 'usada' => $serie->facturas()->exists(), 'tipos' => self::TIPOS]);
    }

    public function update(GuardarSerieFacturacionRequest $request, int $serie): RedirectResponse
    {
        return DB::transaction(function () use ($request, $serie) {
            $serie = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($serie);
            $datos = $request->datosLimpios($serie);
            if ($serie->facturas()->exists()) {
                // Con documentos emitidos, el código, el tipo y el correlativo ya no cambian.
                $datos = collect($datos)->only(['descripcion', 'activo'])->all();
            }
            $serie->update($datos);

            return redirect()->route('series-facturacion.index')->with('status', "Serie {$serie->codigo_serie} actualizada.");
        });
    }

    public function estado(Request $request, int $serie): RedirectResponse
    {
        $serie = $this->deMiEmpresa($request)->findOrFail($serie);
        $serie->update(['activo' => ! $serie->activo]);

        return back()->with('status', "Serie {$serie->codigo_serie} ".($serie->activo ? 'activada.' : 'desactivada: ya no se usa para documentos nuevos.'));
    }

    public function destroy(Request $request, int $serie): RedirectResponse
    {
        $serie = $this->deMiEmpresa($request)->findOrFail($serie);
        if ($serie->facturas()->exists()) {
            return back()->withErrors(['serie' => "No se puede eliminar la serie {$serie->codigo_serie}: tiene documentos. Puedes desactivarla."]);
        }
        $serie->delete();

        return redirect()->route('series-facturacion.index')->with('status', "Serie {$serie->codigo_serie} eliminada.");
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return $this->consultaEmpresa(SerieFacturacion::class, $request);
    }
}

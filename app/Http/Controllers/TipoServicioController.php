<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\GuardarTipoServicioRequest;
use App\Models\Clientes\TipoServicio;
use App\Models\Core\CentroCosto;
use App\Models\Core\CuentaContable;
use App\Models\Core\LineaNegocio;
use App\Support\ExportarCsv;
use App\Support\Referencias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tipos de servicio (lo que se vende en facturas y contratos). Pertenecen a la empresa a través
 * de su línea de negocio. Permisos: tipos_servicio.ver / crear / editar / eliminar / exportar.
 */
class TipoServicioController extends Controller
{
    /** Unidades sugeridas (se acepta otra). */
    public const UNIDADES = ['MES', 'QUINCENA', 'SEMANA', 'DIA', 'HORA', 'EVENTO', 'SERVICIO', 'M2', 'UNIDAD'];

    public function index(Request $request): View
    {
        return view('tipos-servicio.index', [
            'servicios' => $this->filtrados($request)->with(['lineaNegocio', 'cuentaIngreso', 'centroDefault'])->orderBy('id_linea')->orderBy('nombre')->get(),
            'lineas' => $this->lineas($request)->orderBy('nombre')->get(),
            'filtros' => $request->only(['buscar', 'linea', 'estado']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $servicios = $this->filtrados($request)->with(['lineaNegocio', 'cuentaIngreso', 'centroDefault'])->orderBy('id_linea')->orderBy('nombre')->get();

        return ExportarCsv::descargar('tipos_servicio', ['Línea', 'Servicio', 'Unidad', 'Precio base', 'Moneda', 'Cuenta de ingreso', 'Centro de costo', 'Activo'],
            $servicios->map(fn ($s) => [$s->lineaNegocio?->nombre, $s->nombre, $s->unidad_medida, $s->precio_base, $s->moneda, $s->cuentaIngreso?->etiqueta, $s->centroDefault?->etiqueta, $s->activo]));
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new TipoServicio(['id_linea' => $request->integer('linea') ?: null, 'unidad_medida' => 'MES', 'moneda' => 'GTQ', 'activo' => true]));
    }

    public function store(GuardarTipoServicioRequest $request): RedirectResponse
    {
        $servicio = TipoServicio::create($request->datosLimpios());

        return redirect()->route('tipos-servicio.index')->with('status', "Servicio «{$servicio->nombre}» creado.");
    }

    public function edit(Request $request, int $servicio): View
    {
        return $this->formulario($request, $this->deMiEmpresa($request)->findOrFail($servicio));
    }

    public function update(GuardarTipoServicioRequest $request, int $servicio): RedirectResponse
    {
        $servicio = $this->deMiEmpresa($request)->findOrFail($servicio);
        $servicio->update($request->datosLimpios());

        return redirect()->route('tipos-servicio.index')->with('status', "Servicio «{$servicio->nombre}» actualizado.");
    }

    public function estado(Request $request, int $servicio): RedirectResponse
    {
        $servicio = $this->deMiEmpresa($request)->findOrFail($servicio);
        $servicio->update(['activo' => ! $servicio->activo]);

        return back()->with('status', "Servicio «{$servicio->nombre}» ".($servicio->activo ? 'activado.' : 'desactivado.'));
    }

    public function destroy(Request $request, int $servicio): RedirectResponse
    {
        $servicio = $this->deMiEmpresa($request)->findOrFail($servicio);
        $uso = Referencias::enUso('id_tipo_servicio', $servicio->id_tipo_servicio, ['detalle_factura' => 'líneas de facturas', 'contrato_servicio_detalle' => 'líneas de contratos']);
        if ($uso) {
            return back()->withErrors(['servicio' => "No se puede eliminar «{$servicio->nombre}»: lo usan {$uso}. Puedes desactivarlo."]);
        }
        $servicio->delete();

        return redirect()->route('tipos-servicio.index')->with('status', "Servicio «{$servicio->nombre}» eliminado.");
    }

    private function formulario(Request $request, TipoServicio $servicio): View
    {
        $idEmpresa = $this->idEmpresa($request);

        return view('tipos-servicio.form', [
            'servicio' => $servicio,
            'lineas' => $this->lineas($request)->where('activo', true)->orderBy('nombre')->get(),
            'cuentas' => CuentaContable::query()->where('id_empresa', $idEmpresa)->where('activo', true)->where('permite_movimiento', true)->where('tipo', 'INGRESO')->orderBy('codigo')->get(),
            'centros' => CentroCosto::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('codigo')->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'unidades' => self::UNIDADES,
        ]);
    }

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"))
            ->when($request->filled('linea'), fn ($q) => $q->where('id_linea', $request->integer('linea')))
            ->when(in_array($request->query('estado'), ['activos', 'inactivos'], true), fn ($q) => $q->where('activo', $request->query('estado') === 'activos'));
    }

    /** Los servicios no tienen id_empresa: son de la empresa por su línea de negocio. */
    private function deMiEmpresa(Request $request): Builder
    {
        return TipoServicio::query()->whereHas('lineaNegocio', fn ($q) => $q->where('id_empresa', $this->idEmpresa($request)));
    }

    private function lineas(Request $request): Builder
    {
        return $this->consultaEmpresa(LineaNegocio::class, $request);
    }
}

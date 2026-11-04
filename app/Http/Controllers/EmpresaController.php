<?php

namespace App\Http\Controllers;

use App\Http\Requests\Core\GuardarEmpresaRequest;
use App\Models\Core\DivisionGeografica;
use App\Models\Core\Empresa;
use App\Models\Core\Moneda;
use App\Models\Core\Municipio;
use App\Models\Core\Pais;
use App\Models\Core\Sucursal;
use App\Support\Archivos;
use App\Support\CatalogosEmpresa;
use App\Support\Referencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Empresas del grupo (solo el administrador general). Una empresa nueva nace con su casa matriz y
 * con los catálogos copiados de una empresa modelo. No se eliminan: se desactivan.
 */
class EmpresaController extends Controller
{
    /** Documentos que fijan la moneda base: con ellos ya no se puede cambiar. */
    private const FIJAN_MONEDA = [
        'factura' => 'facturas', 'orden_compra' => 'órdenes de compra', 'periodo_nomina' => 'periodos de nómina',
        'contrato_servicio' => 'contratos', 'presupuesto_anual' => 'presupuestos',
    ];

    public function index(Request $request): View
    {
        return view('empresas.index', [
            'empresas' => Empresa::query()->with('pais')->withCount(['sucursales', 'usuarios'])
                ->orderByDesc('activo')->orderBy('nombre_comercial')->get(),
            'principal' => $request->user()->idEmpresaPrincipal(),
            'activa' => (int) $request->user()->id_empresa,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new Empresa(['activo' => true, 'tasa_iva' => 12, 'iva_incluido_en_precio' => true, 'moneda_base' => 'GTQ']));
    }

    public function store(GuardarEmpresaRequest $request): RedirectResponse
    {
        $datos = $request->datosEmpresa();
        $grupos = $request->validated('catalogos') ?? [];
        if ($request->hasFile('logo')) {
            $datos['logo_url'] = Archivos::subir($request->file('logo'), 'empresas', 'logo');
        }

        [$empresa, $copiados] = DB::transaction(function () use ($request, $datos, $grupos) {
            $empresa = Empresa::create($datos + ['activo' => true]);
            Sucursal::create([
                'id_empresa' => $empresa->id_empresa, 'nombre' => $request->validated('casa_matriz'), 'es_casa_matriz' => true, 'activo' => true,
                'id_pais' => $datos['id_pais'], 'id_division' => $request->validated('id_division'), 'id_municipio' => $datos['id_municipio'],
                'direccion' => $datos['direccion'] ?? null, 'telefono' => $datos['telefono'] ?? null, 'email' => $datos['email_corporativo'] ?? null,
            ]);
            $modelo = $request->validated('id_empresa_modelo');
            $copiados = match (true) {
                ! $grupos => [],
                $modelo === CatalogosEmpresa::PLANTILLA => CatalogosEmpresa::desdePlantilla($empresa->id_empresa, $grupos),
                default => CatalogosEmpresa::copiar((int) $modelo, $empresa->id_empresa, $grupos),
            };

            return [$empresa, $copiados];
        });

        $detalle = collect($copiados)->map(fn ($n, $nombre) => mb_strtolower($nombre).": {$n}")->join(', ');

        return redirect()->route('empresas.index')->with('status', "Empresa «{$empresa->nombre_comercial}» creada con su casa matriz"
            .($detalle ? " y sus catálogos ({$detalle})" : ', sin catálogos copiados')
            .'. Elígela en la barra superior para configurarla.');
    }

    public function edit(Request $request, int $empresa): View
    {
        return $this->formulario($request, Empresa::query()->findOrFail($empresa));
    }

    public function update(GuardarEmpresaRequest $request, int $empresa): RedirectResponse
    {
        $empresa = Empresa::query()->findOrFail($empresa);
        $datos = $request->datosEmpresa();

        if ($datos['moneda_base'] !== $empresa->moneda_base && ($uso = $this->documentosConMoneda($empresa))) {
            return back()->withErrors(['moneda_base' => "La moneda base ya no se puede cambiar: la empresa tiene {$uso}."])->withInput();
        }
        $logoAnterior = $empresa->logo_url;
        if ($request->hasFile('logo')) {
            $datos['logo_url'] = Archivos::subir($request->file('logo'), 'empresas', 'logo');
        } elseif ($request->boolean('quitar_logo')) {
            $datos['logo_url'] = null;
        }
        $empresa->update($datos);
        if ($logoAnterior && array_key_exists('logo_url', $datos) && $datos['logo_url'] !== $logoAnterior) {
            Archivos::borrar($logoAnterior);
        }

        return redirect()->route('empresas.index')->with('status', "Empresa «{$empresa->nombre_comercial}» actualizada.");
    }

    public function estado(Request $request, int $empresa): RedirectResponse
    {
        $empresa = Empresa::query()->findOrFail($empresa);

        if ($empresa->activo) {
            if ($empresa->id_empresa === $request->user()->idEmpresaPrincipal()) {
                return back()->withErrors(['empresa' => 'No puedes desactivar tu propia empresa principal.']);
            }
            // Su empresa principal siempre la pueden usar: hay que moverlos o desactivarlos antes.
            $usuarios = DB::table('usuario')->where('id_empresa', $empresa->id_empresa)->where('activo', true)->whereNull('deleted_at')->count();
            if ($usuarios) {
                return back()->withErrors(['empresa' => "No se puede desactivar «{$empresa->nombre_comercial}»: es la empresa principal de {$usuarios} "
                    .($usuarios === 1 ? 'usuario activo' : 'usuarios activos').'. Cámbialos de empresa o desactívalos primero.']);
            }
        }
        $empresa->update(['activo' => ! $empresa->activo]);

        return back()->with('status', "Empresa «{$empresa->nombre_comercial}» ".($empresa->activo ? 'activada.' : 'desactivada: ya nadie puede trabajar en ella.'));
    }

    private function formulario(Request $request, Empresa $empresa): View
    {
        return view('empresas.form', [
            'empresa' => $empresa,
            'division' => $empresa->id_municipio ? Municipio::query()->whereKey($empresa->id_municipio)->value('id_division') : null,
            'monedaFija' => $empresa->exists ? $this->documentosConMoneda($empresa) : null,
            'paises' => Pais::query()->where('activo', true)->orderBy('nombre')->get(['id_pais', 'nombre', 'moneda_defecto']),
            'divisiones' => DivisionGeografica::query()->where('activo', true)->orderBy('nombre')->get(['id_division', 'id_pais', 'nombre']),
            'municipios' => Municipio::query()->where('activo', true)->orderBy('nombre')->get(['id_municipio', 'id_division', 'nombre']),
            'monedas' => Moneda::query()->where('activo', true)->orderBy('codigo')->get(['codigo', 'nombre']),
            'modelos' => Empresa::query()->where('activo', true)->orderBy('nombre_comercial')->get(['id_empresa', 'nombre_comercial']),
            'modelo' => (int) $request->user()->id_empresa,
            'grupos' => CatalogosEmpresa::GRUPOS,
            'hayPlantilla' => CatalogosEmpresa::hayPlantilla(),
            'contaboListo' => Archivos::disponible(),
        ]);
    }

    private function documentosConMoneda(Empresa $empresa): ?string
    {
        return Referencias::enUso('id_empresa', $empresa->id_empresa, self::FIJAN_MONEDA);
    }
}

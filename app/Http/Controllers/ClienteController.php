<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\GuardarClienteRequest;
use App\Models\Clientes\Cliente;
use App\Models\Clientes\ContactoCliente;
use App\Models\Clientes\Industria;
use App\Models\Core\DivisionGeografica;
use App\Models\Core\Empresa;
use App\Models\Core\Municipio;
use App\Models\Core\Pais;
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
 * Clientes y sus contactos. Permisos: clientes.ver / crear / editar / eliminar /
 * exportar / imprimir. Los contactos se gestionan desde la ficha con clientes.editar.
 */
class ClienteController extends Controller
{
    public const SEGMENTOS = ['GRANDE' => 'Grande', 'MEDIANA' => 'Mediana', 'PEQUENA' => 'Pequeña', 'GOBIERNO' => 'Gobierno', 'ONG' => 'ONG'];

    public const CATEGORIAS = ['A', 'B', 'C'];

    /** NIT genérico de consumidor final: puede repetirse. */
    public const CONSUMIDOR_FINAL = 'CF';

    public function index(Request $request): View
    {
        return view('clientes.index', [
            'clientes' => $this->filtrados($request)->with('pais')
                ->withSum(['facturas as saldo' => fn ($q) => $q->whereIn('estado', Cliente::ESTADOS_CON_SALDO)], 'saldo_pendiente')
                ->orderBy('razon_social')->paginate(25)->withQueryString(),
            'segmentos' => self::SEGMENTOS,
            'categorias' => self::CATEGORIAS,
            'filtros' => $request->only(['buscar', 'segmento', 'categoria', 'estado']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $clientes = $this->filtrados($request)->with(['pais', 'municipio', 'industria'])->orderBy('razon_social')->cursor();

        return ExportarCsv::descargar('clientes', ['Razón social', 'Nombre comercial', 'NIT', 'Tipo', 'Industria', 'Segmento', 'Categoría', 'Correo', 'Teléfono',
            'País', 'Municipio', 'Dirección fiscal', 'Moneda', 'Días de crédito', 'Límite de crédito', 'Activo'], (function () use ($clientes) {
                foreach ($clientes as $c) {
                    yield [$c->razon_social, $c->nombre_comercial, $c->nit, $c->tipo_persona === 'NATURAL' ? 'Persona natural' : 'Persona jurídica', $c->industria?->nombre,
                        self::SEGMENTOS[$c->segmento] ?? '', $c->categoria, $c->email_principal, $c->telefono_principal, $c->pais?->nombre, $c->municipio?->nombre,
                        $c->direccion_fiscal, $c->moneda_facturacion, $c->dias_credito, $c->limite_credito, $c->activo];
                }
            })());
    }

    public function show(Request $request, int $cliente): View
    {
        $c = $this->deMiEmpresa($request)->with(['pais', 'municipio.division', 'industria', 'contactos' => fn ($q) => $q->orderByDesc('es_contacto_principal')->orderBy('nombre')])
            ->findOrFail($cliente);

        return view('clientes.show', ['c' => $c] + $this->cuenta($c));
    }

    /** Estado de cuenta imprimible: datos del cliente y facturas con saldo. */
    public function imprimir(Request $request, int $cliente): View
    {
        $c = $this->deMiEmpresa($request)->with(['pais', 'municipio', 'contactos'])->findOrFail($cliente);

        return view('clientes.imprimir', ['c' => $c, 'empresa' => Empresa::find($this->idEmpresa($request))] + $this->cuenta($c));
    }

    public function create(Request $request): View
    {
        return $this->formulario(new Cliente([
            'tipo_persona' => 'JURIDICA', 'moneda_facturacion' => 'GTQ', 'dias_credito' => 30, 'activo' => true,
            'id_pais' => Pais::query()->where('codigo_iso2', 'GT')->value('id_pais'),
        ]));
    }

    public function store(GuardarClienteRequest $request): RedirectResponse
    {
        $cliente = Cliente::create($request->datosLimpios() + $this->selloCreacion($request));

        return redirect()->route('clientes.show', $cliente->id_cliente)->with('status', "Cliente «{$cliente->razon_social}» creado.");
    }

    public function edit(Request $request, int $cliente): View
    {
        return $this->formulario($this->deMiEmpresa($request)->with('municipio')->findOrFail($cliente));
    }

    public function update(GuardarClienteRequest $request, int $cliente): RedirectResponse
    {
        $cliente = $this->deMiEmpresa($request)->findOrFail($cliente);
        $cliente->update($request->datosLimpios() + $this->selloActualizacion($request));

        return redirect()->route('clientes.show', $cliente->id_cliente)->with('status', "Cliente «{$cliente->razon_social}» actualizado.");
    }

    public function estado(Request $request, int $cliente): RedirectResponse
    {
        $cliente = $this->deMiEmpresa($request)->findOrFail($cliente);
        $cliente->update(['activo' => ! $cliente->activo, 'updated_by' => $this->idUsuario($request)]);

        return back()->with('status', "Cliente «{$cliente->razon_social}» ".($cliente->activo ? 'activado.' : 'desactivado.'));
    }

    /** Borrado lógico (deleted_at): el historial de facturas y pagos sigue apuntando al cliente. */
    public function destroy(Request $request, int $cliente): RedirectResponse
    {
        $cliente = $this->deMiEmpresa($request)->findOrFail($cliente);

        if ($cliente->facturas()->where('estado', '!=', 'ANULADA')->exists()) {
            return back()->withErrors(['cliente' => "No se puede eliminar «{$cliente->razon_social}»: tiene facturas. Puedes desactivarlo."]);
        }
        $uso = Referencias::enUso('id_cliente', $cliente->id_cliente, [
            'pago' => 'pagos', 'contrato_servicio' => 'contratos', 'sitio_trabajo' => 'sitios de trabajo', 'ticket' => 'tickets', 'oportunidad' => 'oportunidades',
        ]);
        if ($uso) {
            return back()->withErrors(['cliente' => "No se puede eliminar «{$cliente->razon_social}»: tiene {$uso}. Puedes desactivarlo."]);
        }
        $cliente->delete();

        return redirect()->route('clientes.index')->with('status', "Cliente «{$cliente->razon_social}» eliminado.");
    }

    // ── Contactos ────────────────────────────────────────────────────────

    public function guardarContacto(Request $request, int $cliente, ?int $contacto = null): RedirectResponse
    {
        $cliente = $this->deMiEmpresa($request)->findOrFail($cliente);
        $registro = $contacto ? $cliente->contactos()->findOrFail($contacto) : new ContactoCliente(['id_cliente' => $cliente->id_cliente]);

        $datos = $request->validateWithBag('contacto', [
            'nombre' => ['required', 'string', 'max:200'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
        ]);
        $datos += ['es_contacto_principal' => $request->boolean('es_contacto_principal'), 'recibe_facturas' => $request->boolean('recibe_facturas'), 'activo' => true];

        DB::transaction(function () use ($cliente, $registro, $datos) {
            // Solo un contacto principal por cliente.
            if ($datos['es_contacto_principal']) {
                $cliente->contactos()->where('id_contacto', '!=', $registro->id_contacto ?? 0)->update(['es_contacto_principal' => false]);
            }
            $registro->fill($datos)->save();
        });

        return redirect()->route('clientes.show', $cliente->id_cliente)->with('status', "Contacto «{$registro->nombre}» ".($contacto ? 'actualizado.' : 'agregado.'));
    }

    public function eliminarContacto(Request $request, int $cliente, int $contacto): RedirectResponse
    {
        $cliente = $this->deMiEmpresa($request)->findOrFail($cliente);
        $registro = $cliente->contactos()->findOrFail($contacto);
        $registro->delete();

        return redirect()->route('clientes.show', $cliente->id_cliente)->with('status', "Contacto «{$registro->nombre}» eliminado.");
    }

    // ── Privados ─────────────────────────────────────────────────────────

    /** @return array{facturas: \Illuminate\Support\Collection, saldo: float, vencido: float} */
    private function cuenta(Cliente $c): array
    {
        $facturas = $c->facturas()->whereIn('estado', Cliente::ESTADOS_CON_SALDO)->where('saldo_pendiente', '>', 0)
            ->orderBy('fecha_vencimiento')->get();

        return [
            'facturas' => $facturas,
            'saldo' => (float) $facturas->sum('saldo_pendiente'),
            'vencido' => (float) $facturas->filter(fn ($f) => $f->fecha_vencimiento?->isPast())->sum('saldo_pendiente'),
        ];
    }

    private function formulario(Cliente $cliente): View
    {
        return view('clientes.form', [
            'cliente' => $cliente,
            'industrias' => Industria::query()->orderBy('nombre')->get(),
            'paises' => Pais::query()->where('activo', true)->orderBy('nombre')->get(['id_pais', 'nombre']),
            // Catálogos pequeños: se filtran en el navegador (selects en cascada, sin API).
            'divisiones' => DivisionGeografica::query()->where('activo', true)->orderBy('nombre')->get(['id_division', 'id_pais', 'nombre']),
            'municipios' => Municipio::query()->where('activo', true)->orderBy('nombre')->get(['id_municipio', 'id_division', 'nombre']),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'segmentos' => self::SEGMENTOS,
            'categorias' => self::CATEGORIAS,
        ]);
    }

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->buscar($buscar))
            ->when(array_key_exists((string) $request->query('segmento'), self::SEGMENTOS), fn ($q) => $q->where('segmento', $request->query('segmento')))
            ->when(in_array($request->query('categoria'), self::CATEGORIAS, true), fn ($q) => $q->where('categoria', $request->query('categoria')))
            ->when(in_array($request->query('estado'), ['activos', 'inactivos'], true), fn ($q) => $q->where('activo', $request->query('estado') === 'activos'));
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return $this->consultaEmpresa(Cliente::class, $request);
    }

    /** NIT sin espacios ni guiones y en mayúsculas («1234567-8» → «12345678»); vacío → null. */
    public static function normalizarNit(?string $nit): ?string
    {
        $nit = strtoupper(preg_replace('/[\s\-]+/', '', (string) $nit));

        return $nit === '' ? null : $nit;
    }
}

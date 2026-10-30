<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Core\LineaNegocio;
use App\Models\CRM\Oportunidad;
use App\Models\CRM\Propuesta;
use App\Models\CRM\Prospecto;
use App\Models\RRHH\Empleado;
use App\Support\Crm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Oportunidades de venta en un embudo por etapas y sus propuestas. Permisos: oportunidades.ver / crear / editar.
 * Mover de etapa ajusta la probabilidad; una etapa «ganada» o «perdida» cierra la oportunidad.
 */
class OportunidadController extends Controller
{
    public const ESTADOS_PROPUESTA = [
        'BORRADOR' => ['Borrador', 'secondary'], 'ENVIADA' => ['Enviada', 'info'], 'EN_REVISION' => ['En revisión', 'warning'],
        'ACEPTADA' => ['Aceptada', 'success'], 'RECHAZADA' => ['Rechazada', 'danger'], 'VENCIDA' => ['Vencida', 'dark'],
    ];

    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $etapas = Crm::etapas($idEmpresa);
        $oportunidades = $this->deMiEmpresa($request)->with(['cliente', 'prospecto', 'responsable'])
            ->when($request->filled('responsable'), fn ($q) => $q->where('id_responsable', $request->integer('responsable')))
            ->orderBy('fecha_cierre_estimada')->get();
        $abiertas = $oportunidades->whereIn('id_etapa', $etapas->where('es_ganada', false)->where('es_perdida', false)->pluck('id_etapa'));

        return view('oportunidades.index', [
            'etapas' => $etapas,
            'porEtapa' => $oportunidades->groupBy('id_etapa'),
            'resumen' => [
                'abiertas' => $abiertas->count(),
                'valor' => (float) $abiertas->sum('valor_estimado'),
                'ponderado' => (float) $abiertas->sum(fn ($o) => (float) $o->valor_estimado * (int) $o->probabilidad / 100),
                'ganadasMes' => (float) $oportunidades->whereIn('id_etapa', $etapas->where('es_ganada', true)->pluck('id_etapa'))
                    ->filter(fn ($o) => $o->fecha_cierre_real && $o->fecha_cierre_real->isSameMonth(now()))->sum('valor_estimado'),
            ],
            'vendedores' => $this->vendedores($request)->get(),
            'filtros' => $request->only(['responsable']),
        ]);
    }

    public function show(Request $request, int $oportunidad): View
    {
        $o = $this->deMiEmpresa($request)->with(['cliente', 'prospecto', 'responsable', 'etapa', 'lineaNegocio'])->findOrFail($oportunidad);

        return view('oportunidades.show', [
            'o' => $o,
            'etapas' => Crm::etapas($o->id_empresa),
            'propuestas' => $o->propuestas()->with('elaboradoPor')->orderByDesc('version')->get(),
            'vendedores' => $this->vendedores($request)->get(),
            'estadosPropuesta' => self::ESTADOS_PROPUESTA,
            'siguientePropuesta' => $this->siguientePropuesta($request),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new Oportunidad([
            'id_cliente' => $request->integer('cliente') ?: null, 'id_prospecto' => $request->integer('prospecto') ?: null,
            'moneda' => 'GTQ', 'fecha_cierre_estimada' => now()->addMonth(),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $o = Oportunidad::create($datos + ['id_empresa' => $request->user()->id_empresa, 'created_by' => $request->user()->id_usuario]);

        return redirect()->route('oportunidades.show', $o->id_oportunidad)->with('status', "Oportunidad «{$o->nombre}» creada.");
    }

    public function edit(Request $request, int $oportunidad): View
    {
        return $this->formulario($request, $this->deMiEmpresa($request)->findOrFail($oportunidad));
    }

    public function update(Request $request, int $oportunidad): RedirectResponse
    {
        $o = $this->deMiEmpresa($request)->findOrFail($oportunidad);
        $o->update($this->validar($request, $o));

        return redirect()->route('oportunidades.show', $o->id_oportunidad)->with('status', 'Oportunidad actualizada.');
    }

    /** Cambia de etapa. Ganada o perdida la cierra (perdida pide la razón); volver a una abierta la reabre. */
    public function mover(Request $request, int $oportunidad): RedirectResponse
    {
        $o = $this->deMiEmpresa($request)->findOrFail($oportunidad);
        $etapas = Crm::etapas($o->id_empresa);
        $datos = $request->validate([
            'id_etapa' => ['required', 'integer', Rule::in($etapas->pluck('id_etapa')->all())],
            'razon_cierre' => ['nullable', 'string', 'max:300'],
        ]);
        $etapa = $etapas->firstWhere('id_etapa', (int) $datos['id_etapa']);
        if ($etapa->es_perdida && blank($datos['razon_cierre'] ?? null)) {
            throw ValidationException::withMessages(['razon_cierre' => 'Indica por qué se perdió la oportunidad.']);
        }
        $cerrada = $etapa->es_ganada || $etapa->es_perdida;
        $probabilidad = (int) ($etapa->probabilidad_cierre ?? $o->probabilidad);
        $o->update([
            'id_etapa' => $etapa->id_etapa,
            'probabilidad' => $probabilidad,
            'valor_ponderado' => round((float) $o->valor_estimado * $probabilidad / 100, 4),
            'fecha_cierre_real' => $cerrada ? today() : null,
            'razon_cierre' => $cerrada ? ($datos['razon_cierre'] ?? null) : null,
        ]);

        return back()->with('status', "Oportunidad en «{$etapa->nombre}».".($etapa->es_ganada && $o->id_cliente && $request->user()->puede('contratos.crear') ? ' Ya puedes registrar el contrato del cliente.' : ''));
    }

    // ── Propuestas ───────────────────────────────────────────────────────

    public function guardarPropuesta(Request $request, int $oportunidad): RedirectResponse
    {
        $o = $this->deMiEmpresa($request)->findOrFail($oportunidad);
        $datos = $request->validateWithBag('propuesta', [
            'titulo' => ['required', 'string', 'max:200'],
            'id_elaborado_por' => ['required', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $o->id_empresa)->whereNull('deleted_at')],
            'valor_propuesto' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date', 'after_or_equal:fecha_emision'],
            'notas_internas' => ['nullable', 'string', 'max:5000'],
        ]);
        DB::transaction(function () use ($o, $datos, $request) {
            Propuesta::create($datos + [
                'id_oportunidad' => $o->id_oportunidad, 'numero_propuesta' => $this->siguientePropuesta($request),
                'version' => (int) $o->propuestas()->max('version') + 1, 'moneda' => $o->moneda, 'estado' => 'BORRADOR',
            ]);
        });

        return back()->with('status', 'Propuesta registrada en borrador.');
    }

    public function estadoPropuesta(Request $request, int $oportunidad, int $propuesta): RedirectResponse
    {
        $o = $this->deMiEmpresa($request)->findOrFail($oportunidad);
        $p = $o->propuestas()->findOrFail($propuesta);
        $transiciones = ['BORRADOR' => ['ENVIADA'], 'ENVIADA' => ['EN_REVISION', 'ACEPTADA', 'RECHAZADA', 'VENCIDA'], 'EN_REVISION' => ['ACEPTADA', 'RECHAZADA', 'VENCIDA']];
        $datos = $request->validate([
            'estado' => ['required', Rule::in($transiciones[$p->estado] ?? [])],
            'motivo_rechazo' => ['nullable', 'required_if:estado,RECHAZADA', 'string', 'max:300'],
        ], ['estado.in' => 'Ese cambio de estado no está permitido.', 'motivo_rechazo.required_if' => 'Indica por qué la rechazaron.']);
        $p->update($datos);

        return back()->with('status', "Propuesta {$p->numero_propuesta}: ".strtolower(self::ESTADOS_PROPUESTA[$datos['estado']][0]).'.');
    }

    // ── Privados ─────────────────────────────────────────────────────────

    private function formulario(Request $request, Oportunidad $o): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('oportunidades.form', [
            'o' => $o,
            'etapas' => Crm::etapas($idEmpresa)->where('es_ganada', false)->where('es_perdida', false),
            'clientes' => Cliente::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('razon_social')->get(['id_cliente', 'razon_social']),
            'prospectos' => Prospecto::query()->where('id_empresa', $idEmpresa)->whereNotIn('estado', ['DESCARTADO', 'CONVERTIDO'])->orderBy('nombre_empresa')->get(['id_prospecto', 'nombre_empresa']),
            'lineas' => LineaNegocio::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')->get(),
            'vendedores' => $this->vendedores($request)->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
        ]);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return Oportunidad::query()->where('id_empresa', $request->user()->id_empresa);
    }

    private function vendedores(Request $request): Builder
    {
        return Empleado::query()->where('id_empresa', $request->user()->id_empresa)->whereNotIn('estado', ['BAJA', 'INACTIVO'])->orderBy('primer_nombre');
    }

    /** PR-AAAA-0001 por empresa y año. */
    private function siguientePropuesta(Request $request): string
    {
        $prefijo = 'PR-'.now()->year.'-';
        $ultimo = Propuesta::query()->whereHas('oportunidad', fn ($q) => $q->where('id_empresa', $request->user()->id_empresa))
            ->where('numero_propuesta', 'like', $prefijo.'%')->pluck('numero_propuesta')->map(fn ($n) => (int) substr($n, strlen($prefijo)))->max() ?? 0;

        return $prefijo.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Oportunidad $o = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $etapasAbiertas = Crm::etapas($idEmpresa)->where('es_ganada', false)->where('es_perdida', false);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'id_cliente' => ['nullable', 'required_without:id_prospecto', 'integer', Rule::exists('cliente', 'id_cliente')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'id_prospecto' => ['nullable', 'integer', Rule::exists('prospecto', 'id_prospecto')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'id_etapa' => [$o ? 'nullable' : 'required', 'integer', Rule::in($etapasAbiertas->pluck('id_etapa')->all())],
            'id_responsable' => ['required', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'id_linea' => ['nullable', 'integer', Rule::exists('linea_negocio', 'id_linea')->where('id_empresa', $idEmpresa)],
            'valor_estimado' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'probabilidad' => ['nullable', 'integer', 'between:0,100'],
            'fecha_cierre_estimada' => ['nullable', 'date'],
            'competidores' => ['nullable', 'string', 'max:300'],
        ], ['id_cliente.required_without' => 'Elige un cliente o un prospecto.', 'id_etapa.in' => 'Elige una etapa abierta.']);

        // La etapa solo se elige al crear (después se mueve en el embudo).
        if ($o) {
            unset($datos['id_etapa']);
        }
        $etapa = $etapasAbiertas->firstWhere('id_etapa', (int) ($datos['id_etapa'] ?? $o?->id_etapa));
        $datos['probabilidad'] = (int) ($datos['probabilidad'] ?? $etapa?->probabilidad_cierre ?? $o?->probabilidad ?? 50);
        $datos['valor_ponderado'] = round((float) $datos['valor_estimado'] * $datos['probabilidad'] / 100, 4);

        return $datos;
    }
}

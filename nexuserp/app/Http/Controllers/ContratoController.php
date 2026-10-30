<?php

namespace App\Http\Controllers;

use App\Models\Clientes\AsignacionContrato;
use App\Models\Clientes\Cliente;
use App\Models\Clientes\ContratoServicio;
use App\Models\Clientes\ContratoServicioDetalle;
use App\Models\Clientes\SitioTrabajo;
use App\Models\Clientes\TipoServicio;
use App\Models\Core\Empresa;
use App\Models\Core\Municipio;
use App\Models\RRHH\Empleado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Contratos de servicio con clientes: líneas por tipo de servicio y sitio, sitios de trabajo del
 * cliente y personal asignado. Permisos: contratos.ver / crear / editar / imprimir / cerrar / reabrir.
 *
 * Flujo: BORRADOR → (activar) VIGENTE ⇄ (suspender / reanudar) SUSPENDIDO → (cerrar) CANCELADO o
 * VENCIDO; «reabrir» devuelve un contrato cerrado a VIGENTE. Se editan mientras no estén cerrados.
 */
class ContratoController extends Controller
{
    public const ESTADOS = [
        'BORRADOR' => ['Borrador', 'secondary'], 'VIGENTE' => ['Vigente', 'success'], 'SUSPENDIDO' => ['Suspendido', 'warning'],
        'VENCIDO' => ['Vencido', 'dark'], 'CANCELADO' => ['Cancelado', 'danger'], 'RENOVADO' => ['Renovado', 'info'],
    ];

    public const PERIODICIDADES = ['MENSUAL' => 'Mensual', 'QUINCENAL' => 'Quincenal', 'SEMANAL' => 'Semanal', 'UNICA' => 'Pago único', 'ANUAL' => 'Anual'];

    public const TURNOS = ['MANANA' => 'Mañana', 'TARDE' => 'Tarde', 'NOCHE' => 'Noche', 'MIXTO' => 'Mixto'];

    /** Estados cerrados: no se editan; se reabren con permiso. */
    private const CERRADOS = ['VENCIDO', 'CANCELADO', 'RENOVADO'];

    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $vigentes = $this->deMiEmpresa($request)->where('estado', 'VIGENTE');

        return view('contratos.index', [
            'contratos' => $this->filtrados($request)->with(['cliente', 'vendedor'])->withCount('asignacionesActivas')
                ->orderByDesc('fecha_inicio')->orderByDesc('id_contrato')->paginate(25)->withQueryString(),
            'resumen' => [
                'vigentes' => (clone $vigentes)->count(),
                'mensual' => (clone $vigentes)->groupBy('moneda')->selectRaw('moneda, SUM(valor_mensual) as total')->pluck('total', 'moneda'),
                'porVencer' => (clone $vigentes)->whereNotNull('fecha_fin')->whereBetween('fecha_fin', [today(), today()->addDays(30)])->count(),
            ],
            'clientes' => Cliente::query()->where('id_empresa', $idEmpresa)->orderBy('razon_social')->get(['id_cliente', 'razon_social']),
            'estados' => self::ESTADOS,
            'filtros' => $request->only(['buscar', 'cliente', 'estado', 'por_vencer']),
        ]);
    }

    public function show(Request $request, int $contrato): View
    {
        $c = $this->cargar($request, $contrato);
        $idEmpresa = $request->user()->id_empresa;

        return view('contratos.show', [
            'c' => $c,
            'sitios' => SitioTrabajo::query()->where('id_cliente', $c->id_cliente)->with('municipio')->orderBy('nombre')->get(),
            'asignaciones' => $c->asignaciones()->with(['empleado', 'sitio'])->orderByDesc('activo')->orderBy('fecha_inicio')->get(),
            'empleados' => Empleado::query()->where('id_empresa', $idEmpresa)->whereNotIn('estado', ['BAJA', 'INACTIVO'])->orderBy('primer_nombre')->get(),
            'facturas' => $c->facturas()->orderByDesc('fecha_emision')->limit(24)->get(),
            'municipios' => Municipio::query()->where('activo', true)->orderBy('nombre')->get(['id_municipio', 'nombre']),
            'estados' => self::ESTADOS,
            'periodicidades' => self::PERIODICIDADES,
            'turnos' => self::TURNOS,
        ]);
    }

    public function imprimir(Request $request, int $contrato): View
    {
        return view('contratos.imprimir', [
            'c' => $this->cargar($request, $contrato),
            'empresa' => Empresa::find($request->user()->id_empresa),
            'periodicidades' => self::PERIODICIDADES,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new ContratoServicio([
            'id_cliente' => $request->integer('cliente') ?: null, 'fecha_inicio' => now()->startOfMonth()->addMonth(),
            'moneda' => 'GTQ', 'periodicidad_factura' => 'MENSUAL', 'dia_facturacion' => 1, 'estado' => 'BORRADOR',
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $c = DB::transaction(function () use ($request, $datos) {
            $encabezado = $datos['encabezado'];
            $encabezado['numero_contrato'] ??= $this->siguienteNumero($request);
            $c = ContratoServicio::create($encabezado + [
                'id_empresa' => $request->user()->id_empresa, 'estado' => 'BORRADOR', 'valor_mensual' => 0,
                'created_by' => $request->user()->id_usuario, 'updated_by' => $request->user()->id_usuario,
            ]);
            $this->guardarLineas($c, $datos['lineas']);

            return $c;
        });

        return redirect()->route('contratos.show', $c->id_contrato)->with('status', "Contrato {$c->numero_contrato} creado en borrador.");
    }

    public function edit(Request $request, int $contrato): View|RedirectResponse
    {
        $c = $this->cargar($request, $contrato);
        if (in_array($c->estado, self::CERRADOS, true)) {
            return redirect()->route('contratos.show', $c->id_contrato)->withErrors(['contrato' => 'El contrato está cerrado: reábrelo para editarlo.']);
        }

        return $this->formulario($request, $c);
    }

    public function update(Request $request, int $contrato): RedirectResponse
    {
        $c = $this->deMiEmpresa($request)->findOrFail($contrato);
        if (in_array($c->estado, self::CERRADOS, true)) {
            return redirect()->route('contratos.show', $c->id_contrato)->withErrors(['contrato' => 'El contrato está cerrado: reábrelo para editarlo.']);
        }
        $datos = $this->validar($request, $c);
        DB::transaction(function () use ($c, $datos, $request) {
            $encabezado = $datos['encabezado'];
            $encabezado['numero_contrato'] ??= $c->numero_contrato;
            // El cliente no cambia una vez vigente (tiene sitios, personal y facturas).
            if ($c->estado !== 'BORRADOR') {
                unset($encabezado['id_cliente']);
            }
            $c->update($encabezado + ['updated_by' => $request->user()->id_usuario]);
            $c->detalles()->delete();
            $this->guardarLineas($c, $datos['lineas']);
        });

        return redirect()->route('contratos.show', $c->id_contrato)->with('status', "Contrato {$c->numero_contrato} actualizado.");
    }

    /** Cambios de estado: activar, suspender, reanudar, cerrar y reabrir. */
    public function estado(Request $request, int $contrato, string $accion): RedirectResponse
    {
        $reglas = [
            'activar' => [['BORRADOR'], 'VIGENTE', 'contratos.editar'],
            'suspender' => [['VIGENTE'], 'SUSPENDIDO', 'contratos.editar'],
            'reanudar' => [['SUSPENDIDO'], 'VIGENTE', 'contratos.editar'],
            'cerrar' => [['VIGENTE', 'SUSPENDIDO'], null, 'contratos.cerrar'],
            'reabrir' => [['VENCIDO', 'CANCELADO'], 'VIGENTE', 'contratos.reabrir'],
        ];
        abort_unless(isset($reglas[$accion]), 404);
        [$desde, $hacia, $permiso] = $reglas[$accion];
        abort_unless($request->user()->puede($permiso), 403);
        $motivo = $accion === 'cerrar' ? $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:300']], ['motivo.required' => 'Indica el motivo del cierre.'])['motivo'] : null;

        return DB::transaction(function () use ($request, $contrato, $accion, $desde, $hacia, $motivo) {
            $c = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($contrato);
            if (! in_array($c->estado, $desde, true)) {
                return back()->withErrors(['contrato' => 'No se puede '.$accion.' un contrato '.strtolower(self::ESTADOS[$c->estado][0] ?? $c->estado).'.']);
            }
            if ($accion === 'activar' && ! $c->detalles()->exists()) {
                return back()->withErrors(['contrato' => 'Agrega al menos un servicio antes de activar el contrato.']);
            }
            if ($accion === 'reabrir' && $c->fecha_fin && $c->fecha_fin->lt(today())) {
                return back()->withErrors(['contrato' => 'La fecha de fin ya pasó: amplíala antes de reabrir.']);
            }
            // Al cerrar: VENCIDO si llegó a su fecha de fin; si no, CANCELADO.
            $nuevo = $hacia ?? ($c->fecha_fin && $c->fecha_fin->lte(today()) ? 'VENCIDO' : 'CANCELADO');
            $cambios = ['estado' => $nuevo, 'updated_by' => $request->user()->id_usuario];
            if ($motivo) {
                $cambios['notas'] = trim(($c->notas ? $c->notas."\n" : '').'[Cerrado el '.now()->format('d/m/Y').'] '.$motivo);
                // El personal deja de estar asignado.
                $c->asignaciones()->where('activo', true)->update(['activo' => false, 'fecha_fin' => today()]);
            }
            $c->update($cambios);

            return back()->with('status', "Contrato {$c->numero_contrato}: ".strtolower(self::ESTADOS[$nuevo][0]).'.');
        });
    }

    // ── Sitios de trabajo (del cliente) ──────────────────────────────────

    public function guardarSitio(Request $request, int $contrato, ?int $sitio = null): RedirectResponse
    {
        $c = $this->deMiEmpresa($request)->findOrFail($contrato);
        $s = $sitio ? SitioTrabajo::query()->where('id_cliente', $c->id_cliente)->findOrFail($sitio) : new SitioTrabajo(['id_cliente' => $c->id_cliente]);
        $datos = $request->validateWithBag('sitio', [
            'nombre' => ['required', 'string', 'max:200'],
            'direccion' => ['required', 'string', 'max:300'],
            'id_municipio' => ['nullable', 'integer', 'exists:municipio,id_municipio'],
            'responsable_cliente' => ['nullable', 'string', 'max:200'],
            'tel_responsable' => ['nullable', 'string', 'max:20'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
        $s->fill($datos + ['activo' => true])->save();

        return redirect()->route('contratos.show', $c->id_contrato)->with('status', "Sitio «{$s->nombre}» guardado.");
    }

    // ── Personal asignado ────────────────────────────────────────────────

    public function asignar(Request $request, int $contrato): RedirectResponse
    {
        $c = $this->deMiEmpresa($request)->findOrFail($contrato);
        if (! in_array($c->estado, ['BORRADOR', 'VIGENTE', 'SUSPENDIDO'], true)) {
            return back()->withErrors(['asignacion' => 'No se asigna personal a un contrato cerrado.']);
        }
        $datos = $request->validateWithBag('asignacion', [
            'id_empleado' => ['required', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $c->id_empresa)->whereNull('deleted_at')->whereNotIn('estado', ['BAJA', 'INACTIVO'])],
            'id_sitio' => ['nullable', 'integer', Rule::exists('sitio_trabajo', 'id_sitio')->where('id_cliente', $c->id_cliente)],
            'fecha_inicio' => ['required', 'date', 'after_or_equal:'.$c->fecha_inicio->toDateString()],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'rol_en_sitio' => ['nullable', 'string', 'max:150'],
            'turno' => ['nullable', Rule::in(array_keys(self::TURNOS))],
        ], [
            'id_empleado.exists' => 'El empleado no es válido o no está activo.',
            'fecha_inicio.after_or_equal' => 'La asignación no puede empezar antes que el contrato.',
        ]);
        $repetida = $c->asignaciones()->where('activo', true)->where('id_empleado', $datos['id_empleado'])
            ->where('id_sitio', $datos['id_sitio'] ?? null)->exists();
        if ($repetida) {
            throw ValidationException::withMessages(['id_empleado' => 'Ese empleado ya está asignado a este contrato en ese sitio.'])->errorBag('asignacion');
        }
        AsignacionContrato::create($datos + ['id_contrato' => $c->id_contrato, 'activo' => true]);

        return redirect()->route('contratos.show', $c->id_contrato)->with('status', 'Personal asignado.');
    }

    public function finalizarAsignacion(Request $request, int $contrato, int $asignacion): RedirectResponse
    {
        $c = $this->deMiEmpresa($request)->findOrFail($contrato);
        $a = $c->asignaciones()->where('activo', true)->findOrFail($asignacion);
        $a->update(['activo' => false, 'fecha_fin' => $a->fecha_inicio->gt(today()) ? $a->fecha_inicio : today()]);

        return back()->with('status', 'Asignación finalizada.');
    }

    // ── Privados ─────────────────────────────────────────────────────────

    private function formulario(Request $request, ContratoServicio $c): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $clientes = Cliente::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('razon_social')->get(['id_cliente', 'razon_social', 'moneda_facturacion']);

        return view('contratos.form', [
            'c' => $c,
            'clientes' => $clientes,
            'sitios' => SitioTrabajo::query()->whereIn('id_cliente', $clientes->pluck('id_cliente'))->where('activo', true)->orderBy('nombre')->get(['id_sitio', 'id_cliente', 'nombre']),
            'servicios' => TipoServicio::query()->where('activo', true)->whereHas('lineaNegocio', fn ($q) => $q->where('id_empresa', $idEmpresa))->with('lineaNegocio')->orderBy('nombre')->get(),
            'vendedores' => Empleado::query()->where('id_empresa', $idEmpresa)->where('estado', '!=', 'BAJA')->orderBy('primer_nombre')->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'periodicidades' => self::PERIODICIDADES,
            'siguienteNumero' => $c->exists ? null : $this->siguienteNumero($request),
        ]);
    }

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q->where('numero_contrato', 'like', "%{$buscar}%")->orWhere('nombre_proyecto', 'like', "%{$buscar}%")
                ->orWhereHas('cliente', fn ($q) => $q->where('razon_social', 'like', "%{$buscar}%"))))
            ->when($request->filled('cliente'), fn ($q) => $q->where('id_cliente', $request->integer('cliente')))
            ->when(array_key_exists((string) $request->query('estado'), self::ESTADOS), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->boolean('por_vencer'), fn ($q) => $q->where('estado', 'VIGENTE')->whereNotNull('fecha_fin')->whereBetween('fecha_fin', [today(), today()->addDays(30)]));
    }

    private function cargar(Request $request, int $contrato): ContratoServicio
    {
        return $this->deMiEmpresa($request)->with(['cliente', 'vendedor', 'detalles.tipoServicio', 'detalles.sitio'])->findOrFail($contrato);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return ContratoServicio::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** CS-AAAA-0001, consecutivo por empresa y año. */
    private function siguienteNumero(Request $request): string
    {
        $prefijo = 'CS-'.now()->year.'-';
        $ultimo = $this->deMiEmpresa($request)->where('numero_contrato', 'like', $prefijo.'%')->pluck('numero_contrato')
            ->map(fn ($n) => (int) substr($n, strlen($prefijo)))->max() ?? 0;

        return $prefijo.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array{encabezado: array<string, mixed>, lineas: list<array<string, mixed>>} */
    private function validar(Request $request, ?ContratoServicio $c = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $request->merge([
            'numero_contrato' => ($n = strtoupper(trim((string) $request->input('numero_contrato')))) === '' ? null : $n,
            'lineas' => array_values(array_filter((array) $request->input('lineas', []), fn ($l) => ! empty($l['id_tipo_servicio']))),
        ]);
        $idCliente = $c && $c->estado !== 'BORRADOR' ? $c->id_cliente : $request->integer('id_cliente');
        $servicios = TipoServicio::query()->whereHas('lineaNegocio', fn ($q) => $q->where('id_empresa', $idEmpresa))->pluck('id_tipo_servicio')->all();

        $datos = $request->validate([
            'numero_contrato' => ['nullable', 'string', 'max:60', Rule::unique('contrato_servicio', 'numero_contrato')->where('id_empresa', $idEmpresa)->ignore($c?->id_contrato, 'id_contrato')],
            'id_cliente' => [$c && $c->estado !== 'BORRADOR' ? 'nullable' : 'required', 'integer', Rule::exists('cliente', 'id_cliente')->where('id_empresa', $idEmpresa)->where('activo', true)->whereNull('deleted_at')],
            'nombre_proyecto' => ['nullable', 'string', 'max:200'],
            'id_vendedor' => ['nullable', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after:fecha_inicio'],
            'fecha_firma' => ['nullable', 'date'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'periodicidad_factura' => ['required', Rule::in(array_keys(self::PERIODICIDADES))],
            'dia_facturacion' => ['nullable', 'integer', 'between:1,28'],
            'notas' => ['nullable', 'string', 'max:5000'],
            'lineas' => ['required', 'array', 'min:1', 'max:200'],
            'lineas.*.id_tipo_servicio' => ['required', 'integer', Rule::in($servicios)],
            'lineas.*.id_sitio' => ['nullable', 'integer', Rule::exists('sitio_trabajo', 'id_sitio')->where('id_cliente', $idCliente)],
            'lineas.*.descripcion' => ['nullable', 'string', 'max:300'],
            'lineas.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'lineas.*.descuento_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'numero_contrato.unique' => 'Ya existe un contrato con ese número.',
            'id_cliente.exists' => 'El cliente no es válido o está inactivo.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior al inicio.',
            'dia_facturacion.between' => 'El día de facturación va del 1 al 28 (para que exista en todos los meses).',
            'lineas.required' => 'Agrega al menos un servicio.',
            'lineas.*.id_tipo_servicio.in' => 'Uno de los servicios no es válido.',
            'lineas.*.id_sitio.exists' => 'El sitio de una línea no es del cliente.',
        ]);

        $lineas = array_map(fn ($l) => [
            'id_tipo_servicio' => (int) $l['id_tipo_servicio'],
            'id_sitio' => $l['id_sitio'] ?? null,
            'descripcion' => $l['descripcion'] ?? null,
            'cantidad' => $l['cantidad'],
            'precio_unitario' => $l['precio_unitario'],
            'descuento_pct' => round((float) ($l['descuento_pct'] ?? 0), 2),
            'subtotal' => round((float) $l['cantidad'] * (float) $l['precio_unitario'] * (1 - (float) ($l['descuento_pct'] ?? 0) / 100), 4),
        ], $datos['lineas']);

        return [
            'encabezado' => collect($datos)->only(['numero_contrato', 'id_cliente', 'nombre_proyecto', 'id_vendedor', 'fecha_inicio', 'fecha_fin', 'fecha_firma', 'moneda', 'periodicidad_factura', 'dia_facturacion', 'notas'])
                ->all() + array_fill_keys(['nombre_proyecto', 'id_vendedor', 'fecha_fin', 'fecha_firma', 'dia_facturacion', 'notas'], null),
            'lineas' => $lineas,
        ];
    }

    /** Guarda las líneas y recalcula el valor mensual y el total estimado (si hay fecha de fin). */
    private function guardarLineas(ContratoServicio $c, array $lineas): void
    {
        foreach ($lineas as $l) {
            ContratoServicioDetalle::create($l + ['id_contrato' => $c->id_contrato]);
        }
        $mensual = round((float) collect($lineas)->sum('subtotal'), 4);
        $meses = $c->fecha_fin ? max(1, (int) ceil(Carbon::parse($c->fecha_inicio)->floatDiffInMonths(Carbon::parse($c->fecha_fin)))) : null;
        $c->update(['valor_mensual' => $mensual, 'valor_total_estimado' => $meses ? round($mensual * $meses, 4) : null]);
    }
}

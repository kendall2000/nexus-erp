<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Clientes\ContratoServicio;
use App\Models\Clientes\TipoServicio;
use App\Models\Core\CentroCosto;
use App\Models\Core\CuentaContable;
use App\Models\Core\Empresa;
use App\Models\Finanzas\DetalleFactura;
use App\Models\Finanzas\Factura;
use App\Models\Finanzas\SerieFacturacion;
use App\Support\EjecucionPresupuesto;
use App\Support\ExportarCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Facturas de venta. Permisos: facturas.ver / crear / editar / anular / imprimir / exportar /
 * condonar (cobrar = registrar un pago, en PagoController).
 *
 * Flujo: BORRADOR → (emitir) EMITIDA → (marcar enviada) ENVIADA → PARCIAL / PAGADA con los
 * pagos. Se anula mientras no tenga pagos. Emitir ejecuta el presupuesto de ingresos y anular
 * lo revierte (App\Support\EjecucionPresupuesto). El tipo sale de la serie elegida y el número
 * se toma de la serie al crear el borrador.
 */
class FacturaController extends Controller
{
    public const ESTADOS = [
        'BORRADOR' => ['Borrador', 'secondary'],
        'EMITIDA' => ['Emitida', 'info'],
        'ENVIADA' => ['Enviada', 'primary'],
        'PARCIAL' => ['Pagada en parte', 'warning'],
        'PAGADA' => ['Pagada', 'success'],
        'VENCIDA' => ['Vencida', 'danger'],
        'ANULADA' => ['Anulada', 'dark'],
    ];

    public const TIPOS = [
        'FACTURA' => 'Factura',
        'CREDITO_FISCAL' => 'Crédito fiscal',
        'NOTA_CREDITO' => 'Nota de crédito',
        'NOTA_DEBITO' => 'Nota de débito',
    ];

    /** Estados que se pueden anular (si no hay pagos). */
    private const ANULABLES = ['BORRADOR', 'EMITIDA', 'ENVIADA', 'VENCIDA'];

    /** Tramos de antigüedad de la cartera: [nombre, desde días vencida, hasta]. */
    public const TRAMOS = [['Al día', null, 0], ['1–30 días', 1, 30], ['31–60 días', 31, 60], ['61–90 días', 61, 90], ['Más de 90', 91, null]];

    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('facturas.index', [
            'facturas' => $this->filtradas($request)->with('cliente')->orderByDesc('fecha_emision')->orderByDesc('id_factura')->paginate(25)->withQueryString(),
            'cartera' => $this->cartera($idEmpresa),
            'clientes' => Cliente::query()->where('id_empresa', $idEmpresa)->orderBy('razon_social')->get(['id_cliente', 'razon_social']),
            'estados' => self::ESTADOS,
            'tipos' => self::TIPOS,
            'filtros' => $request->only(['buscar', 'estado', 'cliente', 'desde', 'hasta']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $facturas = $this->filtradas($request)->with('cliente')->orderByDesc('fecha_emision')->cursor();

        return ExportarCsv::descargar('facturas', ['Número', 'Tipo', 'Cliente', 'NIT', 'Emisión', 'Vencimiento', 'Moneda', 'Subtotal', 'Descuento', 'Base imponible', 'IVA', 'Total', 'Pagado', 'Saldo', 'Estado'],
            (function () use ($facturas) {
                foreach ($facturas as $f) {
                    yield [$f->numero_completo, self::TIPOS[$f->tipo] ?? $f->tipo, $f->cliente?->razon_social, $f->cliente?->nit, $f->fecha_emision?->format('d/m/Y'),
                        $f->fecha_vencimiento?->format('d/m/Y'), $f->moneda, $f->subtotal, $f->descuento, $f->base_imponible, $f->iva, $f->total, $f->total_pagado,
                        $f->saldo_pendiente, self::ESTADOS[$f->estado][0] ?? $f->estado];
                }
            })());
    }

    public function show(Request $request, int $factura): View
    {
        return view('facturas.show', [
            'f' => $this->cargar($request, $factura)->load(['pagos' => fn ($q) => $q->orderBy('fecha_pago'), 'anuladaPor', 'condonadoPor']),
            'estados' => self::ESTADOS,
            'tipos' => self::TIPOS,
            'eliminable' => $this->esUltimoDeSuSerie($this->deMiEmpresa($request)->findOrFail($factura)),
        ]);
    }

    public function imprimir(Request $request, int $factura): View
    {
        $f = $this->cargar($request, $factura);
        abort_if($f->estado === 'BORRADOR', 404);

        return view('facturas.imprimir', ['f' => $f, 'empresa' => Empresa::find($request->user()->id_empresa), 'tipos' => self::TIPOS, 'estados' => self::ESTADOS]);
    }

    /** Con ?contrato= se prellena con el cliente, la moneda, los servicios y el periodo del mes. */
    public function create(Request $request): View
    {
        $contrato = $request->filled('contrato')
            ? ContratoServicio::query()->where('id_empresa', $request->user()->id_empresa)->where('estado', 'VIGENTE')->with('detalles.tipoServicio', 'detalles.sitio')->find($request->integer('contrato'))
            : null;
        $idCliente = $contrato?->id_cliente ?? ($request->filled('cliente') ? $request->integer('cliente') : null);
        $cliente = $idCliente ? $this->clientes($request)->find($idCliente) : null;

        $f = new Factura([
            'id_cliente' => $cliente?->id_cliente,
            'id_contrato' => $contrato?->id_contrato,
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays($cliente->dias_credito ?? 30),
            'moneda' => $contrato->moneda ?? $cliente->moneda_facturacion ?? 'GTQ',
            'periodo_servicio_inicio' => $contrato ? now()->startOfMonth() : null,
            'periodo_servicio_fin' => $contrato ? now()->endOfMonth() : null,
            'descuento' => 0,
            'estado' => 'BORRADOR',
        ]);
        if ($contrato) {
            $periodo = ucfirst(\App\Models\Finanzas\PresupuestoAnual::MESES[now()->month]).' '.now()->year;
            $f->setRelation('detalles', $contrato->detalles->map(fn ($d) => new DetalleFactura([
                'id_tipo_servicio' => $d->id_tipo_servicio,
                'descripcion' => mb_substr(trim(($d->tipoServicio?->nombre ?? 'Servicio').($d->sitio ? ' — '.$d->sitio->nombre : '').' · '.$periodo), 0, 300),
                'cantidad' => $d->cantidad,
                'precio_unitario' => $d->precio_unitario,
                'descuento' => round((float) $d->cantidad * (float) $d->precio_unitario * (float) $d->descuento_pct / 100, 2),
                'es_afecto_iva' => true,
            ])));
        }

        return $this->formulario($request, $f);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $idEmpresa = $request->user()->id_empresa;

        $factura = DB::transaction(function () use ($request, $datos, $idEmpresa) {
            // Se bloquea la serie: dos usuarios no pueden recibir el mismo número.
            $serie = SerieFacturacion::query()->where('id_empresa', $idEmpresa)->where('activo', true)
                ->whereIn('tipo', array_keys(self::TIPOS))->lockForUpdate()->findOrFail($datos['encabezado']['id_serie']);
            $numero = (int) $serie->ultimo_numero + 1;
            $serie->update(['ultimo_numero' => $numero]);

            $factura = Factura::create($datos['encabezado'] + [
                'id_empresa' => $idEmpresa,
                'tipo' => $serie->tipo,
                'numero_factura' => $numero,
                'numero_completo' => $serie->formatearNumero($numero),
                'estado' => 'BORRADOR',
                'subtotal' => 0, 'base_imponible' => 0, 'iva' => 0, 'total' => 0, 'total_pagado' => 0, 'saldo_pendiente' => 0,
                'created_by' => $request->user()->id_usuario,
            ]);
            $this->guardarLineas($factura, $datos['lineas']);

            return $factura;
        });

        return redirect()->route('facturas.show', $factura->id_factura)->with('status', "{$this->nombre($factura)} creada en borrador.");
    }

    public function edit(Request $request, int $factura): View|RedirectResponse
    {
        $f = $this->cargar($request, $factura);
        if ($f->estado !== 'BORRADOR') {
            return redirect()->route('facturas.show', $f->id_factura)->withErrors(['factura' => 'Solo se editan facturas en borrador.']);
        }

        return $this->formulario($request, $f);
    }

    public function update(Request $request, int $factura): RedirectResponse
    {
        $f = $this->deMiEmpresa($request)->findOrFail($factura);
        if ($f->estado !== 'BORRADOR') {
            return redirect()->route('facturas.show', $f->id_factura)->withErrors(['factura' => 'Solo se editan facturas en borrador.']);
        }
        $datos = $this->validar($request, $f);

        DB::transaction(function () use ($f, $datos) {
            $f->update(collect($datos['encabezado'])->except('id_serie')->all()); // la serie y el número no cambian
            $f->detalles()->delete();
            $this->guardarLineas($f, $datos['lineas']);
        });

        return redirect()->route('facturas.show', $f->id_factura)->with('status', "{$this->nombre($f)} actualizada.");
    }

    /** Solo el último borrador de su serie se elimina (y devuelve el número); los demás se anulan para no dejar huecos. */
    public function destroy(Request $request, int $factura): RedirectResponse
    {
        return DB::transaction(function () use ($request, $factura) {
            $f = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($factura);
            if ($f->estado !== 'BORRADOR' || ! $this->esUltimoDeSuSerie($f, true)) {
                return back()->withErrors(['factura' => 'Solo se puede eliminar el último borrador de su serie; los demás se anulan para no dejar huecos en la numeración.']);
            }
            $f->detalles()->delete();
            $f->delete();
            SerieFacturacion::query()->whereKey($f->id_serie)->update(['ultimo_numero' => $f->numero_factura - 1]);

            return redirect()->route('facturas.index')->with('status', "{$this->nombre($f)} eliminada; su número queda libre.");
        });
    }

    public function emitir(Request $request, int $factura): RedirectResponse
    {
        return DB::transaction(function () use ($request, $factura) {
            $f = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($factura);
            if ($f->estado !== 'BORRADOR') {
                return back()->withErrors(['factura' => 'Solo se emiten facturas en borrador.']);
            }
            if ((float) $f->total <= 0) {
                return back()->withErrors(['factura' => 'No se puede emitir un documento en cero.']);
            }
            $f->update(['estado' => 'EMITIDA']);
            EjecucionPresupuesto::registrarFactura($f);

            return back()->with('status', "{$this->nombre($f)} emitida.");
        });
    }

    /** Marca que el documento ya se entregó al cliente (EMITIDA → ENVIADA). */
    public function enviar(Request $request, int $factura): RedirectResponse
    {
        $f = $this->deMiEmpresa($request)->findOrFail($factura);
        if ($f->estado !== 'EMITIDA') {
            return back()->withErrors(['factura' => 'Solo se marcan como enviadas las facturas emitidas.']);
        }
        $f->update(['estado' => 'ENVIADA']);

        return back()->with('status', "{$this->nombre($f)} marcada como enviada.");
    }

    public function anular(Request $request, int $factura): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:300']], ['motivo.required' => 'Indica el motivo de la anulación.']);

        return DB::transaction(function () use ($request, $factura, $datos) {
            $f = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($factura);
            if (! in_array($f->estado, self::ANULABLES, true)) {
                return back()->withErrors(['factura' => 'No se puede anular un documento '.strtolower(self::ESTADOS[$f->estado][0] ?? $f->estado).'.']);
            }
            if ((float) $f->total_pagado > 0 || $f->pagos()->where('estado', 'APLICADO')->exists()) {
                return back()->withErrors(['factura' => 'Tiene cobros aplicados: revierte o devuelve primero esos cobros para poder anularla.']);
            }
            if ((float) $f->monto_condonado > 0) {
                return back()->withErrors(['factura' => 'Tiene saldo condonado: ya no se puede anular.']);
            }
            $emitida = $f->estado !== 'BORRADOR';
            $f->update([
                'estado' => 'ANULADA',
                'saldo_pendiente' => 0,
                'anulada_por' => $request->user()->id_usuario,
                'fecha_anulacion' => now(),
                'notas' => trim(($f->notas ? $f->notas."\n" : '').'[Anulada el '.now()->format('d/m/Y').'] '.$datos['motivo']),
            ]);
            if ($emitida) {
                EjecucionPresupuesto::revertirFactura($f);
            }

            return back()->with('status', "{$this->nombre($f)} anulada".($emitida ? ' y revertida del presupuesto.' : '.'));
        });
    }

    /** Perdona todo o parte del saldo pendiente (queda registrado en la factura y en sus notas). */
    public function condonar(Request $request, int $factura): RedirectResponse
    {
        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['required', 'string', 'min:5', 'max:300'],
        ], ['motivo.required' => 'Indica el motivo de la condonación.']);

        return DB::transaction(function () use ($request, $factura, $datos) {
            $f = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($factura);
            if (! in_array($f->estado, Cliente::ESTADOS_CON_SALDO, true) || (float) $f->saldo_pendiente <= 0) {
                return back()->withErrors(['factura' => 'La factura no tiene saldo por condonar.']);
            }
            if (round((float) $datos['monto'], 2) > round((float) $f->saldo_pendiente, 2)) {
                return back()->withErrors(['monto' => 'No se puede condonar más que el saldo ('.$f->moneda.' '.number_format((float) $f->saldo_pendiente, 2).').']);
            }
            $monto = round((float) $datos['monto'], 4);
            $f->update([
                'monto_condonado' => round((float) $f->monto_condonado + $monto, 4),
                'condonado_por' => $request->user()->id_usuario,
                'fecha_condonacion' => now(),
                'notas' => trim(($f->notas ? $f->notas."\n" : '').'[Condonado '.$f->moneda.' '.number_format($monto, 2).' el '.now()->format('d/m/Y').'] '.$datos['motivo']),
            ]);
            $f->recalcularCobro();

            return back()->with('status', 'Se condonaron '.$f->moneda.' '.number_format($monto, 2).'.'.($f->estado === 'PAGADA' ? ' La factura queda saldada.' : ''));
        });
    }

    // ── Privados ─────────────────────────────────────────────────────────

    private function nombre(Factura $f): string
    {
        return (self::TIPOS[$f->tipo] ?? 'Documento').' '.$f->numero_completo;
    }

    private function esUltimoDeSuSerie(Factura $f, bool $bloquear = false): bool
    {
        $serie = SerieFacturacion::query()->whereKey($f->id_serie)->when($bloquear, fn ($q) => $q->lockForUpdate())->first();

        return $f->estado === 'BORRADOR' && $serie && (int) $serie->ultimo_numero === (int) $f->numero_factura;
    }

    /**
     * Cartera por cobrar de la empresa, por moneda y tramo de antigüedad.
     *
     * @return array<string, array{total: float, vencido: float, tramos: list<float>}>
     */
    private function cartera(int $idEmpresa): array
    {
        $cartera = [];
        $pendientes = Factura::query()->where('id_empresa', $idEmpresa)->whereIn('estado', Cliente::ESTADOS_CON_SALDO)->where('saldo_pendiente', '>', 0)
            ->get(['moneda', 'fecha_vencimiento', 'saldo_pendiente']);
        foreach ($pendientes as $f) {
            $c = &$cartera[$f->moneda];
            $c ??= ['total' => 0.0, 'vencido' => 0.0, 'tramos' => array_fill(0, count(self::TRAMOS), 0.0)];
            $dias = $f->fecha_vencimiento ? (int) $f->fecha_vencimiento->startOfDay()->diffInDays(today(), false) : 0;
            foreach (self::TRAMOS as $i => [, $desde, $hasta]) {
                if (($desde === null || $dias >= $desde) && ($hasta === null || $dias <= $hasta)) {
                    $c['tramos'][$i] += (float) $f->saldo_pendiente;
                    break;
                }
            }
            $c['total'] += (float) $f->saldo_pendiente;
            $c['vencido'] += $dias > 0 ? (float) $f->saldo_pendiente : 0;
            unset($c);
        }
        ksort($cartera);

        return $cartera;
    }

    private function filtradas(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = (string) $request->query('estado');

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q->where('numero_completo', 'like', "%{$buscar}%")
                ->orWhereHas('cliente', fn ($q) => $q->where('razon_social', 'like', "%{$buscar}%")->orWhere('nit', 'like', "%{$buscar}%"))))
            ->when($estado === 'vencidas', fn ($q) => $q->whereIn('estado', Cliente::ESTADOS_CON_SALDO)->where('saldo_pendiente', '>', 0)->whereDate('fecha_vencimiento', '<', today()))
            ->when(array_key_exists($estado, self::ESTADOS), fn ($q) => $q->where('estado', $estado))
            ->when($request->filled('cliente'), fn ($q) => $q->where('id_cliente', $request->integer('cliente')))
            ->when($this->fecha($request->query('desde')), fn ($q, $d) => $q->whereDate('fecha_emision', '>=', $d))
            ->when($this->fecha($request->query('hasta')), fn ($q, $d) => $q->whereDate('fecha_emision', '<=', $d));
    }

    private function fecha(mixed $valor): ?string
    {
        try {
            return is_string($valor) && $valor !== '' ? Carbon::parse($valor)->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function cargar(Request $request, int $factura): Factura
    {
        return $this->deMiEmpresa($request)
            ->with(['cliente', 'serie', 'detalles.tipoServicio', 'detalles.centroCosto', 'detalles.cuentaContable'])
            ->findOrFail($factura);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return Factura::query()->where('id_empresa', $request->user()->id_empresa);
    }

    private function clientes(Request $request): Builder
    {
        return Cliente::query()->where('id_empresa', $request->user()->id_empresa)->where('activo', true);
    }

    private function tiposServicio(Request $request): Builder
    {
        return TipoServicio::query()->where('activo', true)
            ->whereHas('lineaNegocio', fn ($q) => $q->where('id_empresa', $request->user()->id_empresa));
    }

    private function formulario(Request $request, Factura $f): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $empresa = Empresa::find($idEmpresa);

        return view('facturas.form', [
            'f' => $f,
            'clientes' => $this->clientes($request)->orderBy('razon_social')->get(['id_cliente', 'razon_social', 'nit', 'moneda_facturacion', 'dias_credito']),
            'series' => SerieFacturacion::query()->where('id_empresa', $idEmpresa)->where('activo', true)->whereIn('tipo', array_keys(self::TIPOS))->orderBy('tipo')->get(),
            'servicios' => $this->tiposServicio($request)->with('lineaNegocio')->orderBy('nombre')->get(),
            'centros' => CentroCosto::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('codigo')->get(),
            'cuentas' => CuentaContable::query()->where('id_empresa', $idEmpresa)->where('activo', true)->where('permite_movimiento', true)->where('tipo', 'INGRESO')->orderBy('codigo')->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'fiscal' => ['tasa' => $empresa ? (float) $empresa->tasa_iva_decimal : 0.12, 'incluido' => $empresa ? (bool) $empresa->iva_incluido_en_precio : true],
            'tipos' => self::TIPOS,
        ]);
    }

    /** @return array{encabezado: array<string, mixed>, lineas: list<array<string, mixed>>} */
    private function validar(Request $request, ?Factura $f = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        // Se descartan las filas vacías del formulario.
        $request->merge(['lineas' => array_values(array_filter((array) $request->input('lineas', []),
            fn ($l) => trim((string) ($l['descripcion'] ?? '')) !== '' || ! empty($l['id_tipo_servicio'])))]);

        $datos = $request->validate([
            'id_serie' => [$f ? 'nullable' : 'required', 'integer', Rule::exists('serie_facturacion', 'id_serie')->where('id_empresa', $idEmpresa)->where('activo', true)
                ->whereIn('tipo', array_keys(self::TIPOS))],
            'id_cliente' => ['required', 'integer', Rule::exists('cliente', 'id_cliente')->where('id_empresa', $idEmpresa)->where('activo', true)->whereNull('deleted_at')],
            'id_contrato' => ['nullable', 'integer', Rule::exists('contrato_servicio', 'id_contrato')->where('id_empresa', $idEmpresa)->where('id_cliente', $request->integer('id_cliente'))],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date', 'after_or_equal:fecha_emision'],
            'periodo_servicio_inicio' => ['nullable', 'date'],
            'periodo_servicio_fin' => ['nullable', 'date', 'after_or_equal:periodo_servicio_inicio'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'descuento' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'lineas' => ['required', 'array', 'min:1', 'max:200'],
            'lineas.*.id_tipo_servicio' => ['nullable', 'integer', Rule::in($this->tiposServicio($request)->pluck('id_tipo_servicio')->all())],
            'lineas.*.descripcion' => ['nullable', 'string', 'max:300'],
            'lineas.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'lineas.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'lineas.*.id_centro' => ['nullable', 'integer', Rule::exists('centro_costo', 'id_centro')->where('id_empresa', $idEmpresa)],
            'lineas.*.id_cuenta' => ['nullable', 'integer', Rule::exists('cuenta_contable', 'id_cuenta')->where('id_empresa', $idEmpresa)->where('permite_movimiento', true)->where('tipo', 'INGRESO')],
        ], [
            'id_serie.required' => 'Elige la serie (define el tipo de documento).',
            'id_cliente.exists' => 'El cliente no es válido o está inactivo.',
            'id_contrato.exists' => 'El contrato no es de este cliente.',
            'fecha_vencimiento.after_or_equal' => 'El vencimiento no puede ser antes de la emisión.',
            'lineas.required' => 'Agrega al menos una línea.',
            'lineas.*.id_tipo_servicio.in' => 'Uno de los servicios no es válido.',
            'lineas.*.id_cuenta.exists' => 'La cuenta de una línea debe ser de ingreso y de movimiento.',
            'lineas.*.cantidad.min' => 'La cantidad debe ser mayor que cero.',
        ]);

        $servicios = TipoServicio::query()->whereIn('id_tipo_servicio', array_filter(array_column($datos['lineas'], 'id_tipo_servicio')))->pluck('nombre', 'id_tipo_servicio');
        $lineas = [];
        $subtotal = 0.0;
        foreach ($datos['lineas'] as $i => $l) {
            $bruto = round((float) $l['cantidad'] * (float) $l['precio_unitario'], 4);
            $descuento = round((float) ($l['descuento'] ?? 0), 4);
            if ($descuento > $bruto) {
                throw ValidationException::withMessages(["lineas.{$i}.descuento" => 'El descuento de una línea no puede ser mayor que su importe.']);
            }
            $descripcion = trim((string) ($l['descripcion'] ?? '')) ?: ($servicios[$l['id_tipo_servicio'] ?? 0] ?? '');
            $lineas[] = [
                'id_tipo_servicio' => $l['id_tipo_servicio'] ?? null,
                'descripcion' => $descripcion,
                'cantidad' => $l['cantidad'],
                'precio_unitario' => $l['precio_unitario'],
                'descuento' => $descuento,
                'subtotal' => round($bruto - $descuento, 4),
                'es_afecto_iva' => ! empty($request->input("lineas.{$i}.es_afecto_iva")),
                'id_centro' => $l['id_centro'] ?? null,
                'id_cuenta' => $l['id_cuenta'] ?? null,
            ];
            $subtotal += $bruto - $descuento;
        }
        if ((float) ($datos['descuento'] ?? 0) > $subtotal) {
            throw ValidationException::withMessages(['descuento' => 'El descuento global no puede ser mayor que el subtotal.']);
        }

        return [
            'encabezado' => collect($datos)->only(['id_serie', 'id_cliente', 'id_contrato', 'fecha_emision', 'fecha_vencimiento', 'periodo_servicio_inicio', 'periodo_servicio_fin', 'moneda', 'notas'])->all()
                + ['descuento' => round((float) ($datos['descuento'] ?? 0), 4), 'id_contrato' => null, 'periodo_servicio_inicio' => null, 'periodo_servicio_fin' => null, 'notas' => null],
            'lineas' => $lineas,
        ];
    }

    private function guardarLineas(Factura $f, array $lineas): void
    {
        foreach ($lineas as $l) {
            DetalleFactura::create($l + ['id_factura' => $f->id_factura]);
        }
        $f->recalcularTotales();
    }
}

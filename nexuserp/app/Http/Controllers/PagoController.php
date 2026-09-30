<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Core\Empresa;
use App\Models\Finanzas\Factura;
use App\Models\Finanzas\Pago;
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
 * Cobros de facturas. Permisos: pagos.ver / exportar / imprimir; registrar un cobro con
 * pagos.crear o facturas.cobrar; revertir un cobro mal capturado con pagos.eliminar y
 * devolver el dinero al cliente con pagos.devolver. Un pago nunca se borra: cambia de estado.
 * El saldo y el estado de la factura los recalcula Factura::recalcularCobro.
 */
class PagoController extends Controller
{
    public function index(Request $request): View
    {
        $pagos = $this->filtrados($request)->with(['factura', 'cliente'])->orderByDesc('fecha_pago')->orderByDesc('id_pago');

        return view('pagos.index', [
            'pagos' => $pagos->paginate(25)->withQueryString(),
            // Total cobrado (solo aplicados) de lo filtrado, por moneda.
            'cobrado' => $this->filtrados($request)->where('estado', 'APLICADO')->groupBy('moneda')->selectRaw('moneda, SUM(monto) as total, COUNT(*) as cantidad')->get(),
            'porAcreditar' => $this->deMiEmpresa($request)->where('estado', 'APLICADO')->whereNull('fecha_acreditado')->whereNot('forma_pago', 'EFECTIVO')->count(),
            'clientes' => Cliente::query()->where('id_empresa', $request->user()->id_empresa)->orderBy('razon_social')->get(['id_cliente', 'razon_social']),
            'formas' => Pago::FORMAS,
            'estados' => Pago::ESTADOS,
            'filtros' => $request->only(['buscar', 'cliente', 'forma', 'estado', 'desde', 'hasta', 'sin_acreditar']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $pagos = $this->filtrados($request)->with(['factura', 'cliente'])->orderByDesc('fecha_pago')->cursor();

        return ExportarCsv::descargar('pagos', ['Recibo', 'Fecha', 'Cliente', 'Factura', 'Forma de pago', 'Referencia', 'Banco', 'Moneda', 'Monto', 'Acreditado', 'Estado'],
            (function () use ($pagos) {
                foreach ($pagos as $p) {
                    yield [$p->id_pago, $p->fecha_pago?->format('d/m/Y'), $p->cliente?->razon_social, $p->factura?->numero_completo, Pago::FORMAS[$p->forma_pago] ?? $p->forma_pago,
                        $p->referencia, $p->banco_origen, $p->moneda, $p->monto, $p->fecha_acreditado?->format('d/m/Y'), Pago::ESTADOS[$p->estado][0] ?? $p->estado];
                }
            })());
    }

    public function show(Request $request, int $pago): View
    {
        return view('pagos.show', ['p' => $this->cargar($request, $pago), 'formas' => Pago::FORMAS, 'estados' => Pago::ESTADOS]);
    }

    public function imprimir(Request $request, int $pago): View
    {
        return view('pagos.imprimir', ['p' => $this->cargar($request, $pago), 'empresa' => Empresa::find($request->user()->id_empresa), 'formas' => Pago::FORMAS]);
    }

    /** Formulario de cobro; con ?factura= la deja elegida. */
    public function create(Request $request): View
    {
        $pendientes = $this->cobrables($request)->with('cliente')->orderBy('fecha_vencimiento')->get();

        return view('pagos.form', [
            'facturas' => $pendientes,
            'factura' => $request->filled('factura') ? $pendientes->firstWhere('id_factura', $request->integer('factura')) : null,
            'formas' => Pago::FORMAS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'id_factura' => ['required', 'integer'],
            'forma_pago' => ['required', Rule::in(array_keys(Pago::FORMAS))],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
            'fecha_acreditado' => ['nullable', 'date', 'after_or_equal:fecha_pago'],
            'referencia' => ['nullable', 'string', 'max:100', Rule::requiredIf(! in_array($request->input('forma_pago'), ['EFECTIVO', 'OTRO'], true))],
            'banco_origen' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string', 'max:300'],
        ], [
            'fecha_pago.before_or_equal' => 'La fecha del pago no puede ser futura.',
            'referencia.required' => 'Indica la referencia (número de boleta, transferencia, cheque o autorización).',
        ]);

        $pago = DB::transaction(function () use ($request, $datos) {
            // Se bloquea la factura: dos cobros simultáneos no pueden pagar dos veces el mismo saldo.
            $factura = $this->cobrables($request)->lockForUpdate()->find($datos['id_factura']);
            if (! $factura) {
                throw ValidationException::withMessages(['id_factura' => 'La factura no existe o ya no tiene saldo por cobrar.']);
            }
            if (round((float) $datos['monto'], 2) > round((float) $factura->saldo_pendiente, 2)) {
                throw ValidationException::withMessages(['monto' => 'El monto supera el saldo pendiente ('.$factura->moneda.' '.number_format((float) $factura->saldo_pendiente, 2).').']);
            }
            if (Carbon::parse($datos['fecha_pago'])->lt($factura->fecha_emision)) {
                throw ValidationException::withMessages(['fecha_pago' => 'El pago no puede ser anterior a la emisión de la factura.']);
            }

            $pago = Pago::create(array_merge($datos, [
                'id_empresa' => $factura->id_empresa,
                'id_cliente' => $factura->id_cliente,
                'moneda' => $factura->moneda, // se cobra en la moneda de la factura
                'referencia' => $datos['referencia'] ?? '',
                'estado' => 'APLICADO',
                'created_by' => $request->user()->id_usuario,
            ]));
            $factura->recalcularCobro();

            return $pago;
        });

        return redirect()->route('pagos.show', $pago->id_pago)->with('status', 'Cobro registrado. '.$this->resumenFactura($pago->factura()->first()));
    }

    /** Anota la fecha en que el banco acreditó el depósito, transferencia o cheque. */
    public function acreditar(Request $request, int $pago): RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($pago);
        $datos = $request->validate(['fecha_acreditado' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$p->fecha_pago->toDateString()]], [
            'fecha_acreditado.after_or_equal' => 'La acreditación no puede ser anterior al pago.',
        ]);
        if ($p->estado !== 'APLICADO') {
            return back()->withErrors(['pago' => 'Solo se acreditan pagos aplicados.']);
        }
        $p->update($datos);

        return back()->with('status', 'Acreditación registrada.');
    }

    /** Cobro mal capturado: deja de contar y la factura recupera el saldo. */
    public function revertir(Request $request, int $pago): RedirectResponse
    {
        return $this->deshacer($request, $pago, 'REVERTIDO', 'Cobro revertido.');
    }

    /** Se devolvió el dinero al cliente: deja de contar y la factura recupera el saldo. */
    public function devolver(Request $request, int $pago): RedirectResponse
    {
        return $this->deshacer($request, $pago, 'DEVUELTO', 'Devolución registrada.');
    }

    // ── Privados ─────────────────────────────────────────────────────────

    private function deshacer(Request $request, int $pago, string $estado, string $mensaje): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:300']], ['motivo.required' => 'Indica el motivo.']);

        return DB::transaction(function () use ($request, $pago, $estado, $mensaje, $datos) {
            $p = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($pago);
            if ($p->estado !== 'APLICADO') {
                return back()->withErrors(['pago' => 'Este cobro ya fue '.strtolower(Pago::ESTADOS[$p->estado][0]).'.']);
            }
            $factura = $p->id_factura ? Factura::query()->lockForUpdate()->find($p->id_factura) : null;
            $p->update(['estado' => $estado, 'revertido_por' => $request->user()->id_usuario, 'fecha_reversion' => now(), 'motivo_reversion' => $datos['motivo']]);
            $factura?->recalcularCobro();

            return back()->with('status', $mensaje.' '.($factura ? $this->resumenFactura($factura) : ''));
        });
    }

    private function resumenFactura(?Factura $f): string
    {
        return $f ? "La factura {$f->numero_completo} queda ".($f->estado === 'PAGADA' ? 'pagada.' : "con saldo {$f->moneda} ".number_format((float) $f->saldo_pendiente, 2).'.') : '';
    }

    private function cobrables(Request $request): Builder
    {
        return Factura::query()->where('id_empresa', $request->user()->id_empresa)
            ->whereIn('estado', Cliente::ESTADOS_CON_SALDO)->where('saldo_pendiente', '>', 0);
    }

    private function cargar(Request $request, int $pago): Pago
    {
        return $this->deMiEmpresa($request)->with(['factura', 'cliente', 'creadoPor', 'revertidoPor'])->findOrFail($pago);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return Pago::query()->where('id_empresa', $request->user()->id_empresa);
    }

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));
        $fecha = function (mixed $v): ?string {
            try {
                return is_string($v) && $v !== '' ? Carbon::parse($v)->toDateString() : null;
            } catch (\Throwable) {
                return null;
            }
        };

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q->where('referencia', 'like', "%{$buscar}%")
                ->orWhereHas('factura', fn ($q) => $q->where('numero_completo', 'like', "%{$buscar}%"))
                ->orWhereHas('cliente', fn ($q) => $q->where('razon_social', 'like', "%{$buscar}%"))))
            ->when($request->filled('cliente'), fn ($q) => $q->where('id_cliente', $request->integer('cliente')))
            ->when(array_key_exists((string) $request->query('forma'), Pago::FORMAS), fn ($q) => $q->where('forma_pago', $request->query('forma')))
            ->when(array_key_exists((string) $request->query('estado'), Pago::ESTADOS), fn ($q) => $q->where('estado', $request->query('estado')))
            ->when($request->boolean('sin_acreditar'), fn ($q) => $q->where('estado', 'APLICADO')->whereNull('fecha_acreditado')->whereNot('forma_pago', 'EFECTIVO'))
            ->when($fecha($request->query('desde')), fn ($q, $d) => $q->whereDate('fecha_pago', '>=', $d))
            ->when($fecha($request->query('hasta')), fn ($q, $d) => $q->whereDate('fecha_pago', '<=', $d));
    }
}

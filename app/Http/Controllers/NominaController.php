<?php

namespace App\Http\Controllers;

use App\Models\Core\Empresa;
use App\Models\RRHH\ConceptoNomina;
use App\Models\RRHH\DetalleNomina;
use App\Models\RRHH\DetalleNominaConcepto;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\PeriodoNomina;
use App\Models\RRHH\PrestamoEmpleado;
use App\Services\RRHH\NominaService;
use App\Support\ExportarCsv;
use App\Support\Nomina;
use App\Support\Salarios;
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
 * Nómina por periodos. Permisos: nomina.ver / procesar (crear periodos, calcular, ajustar, pagar,
 * préstamos) / cerrar / reabrir / imprimir. Cálculo en App\Support\Nomina.
 *
 * Flujo: ABIERTO → (procesar) EN_PROCESO → (cerrar: aplica los préstamos) CERRADO → (pagar) PAGADO;
 * un periodo CERRADO se reabre (revierte los préstamos). Uno PAGADO ya no cambia.
 */
class NominaController extends Controller
{
    public const ESTADOS = ['ABIERTO' => ['Abierto', 'secondary'], 'EN_PROCESO' => ['En proceso', 'info'], 'CERRADO' => ['Cerrado', 'warning'], 'PAGADO' => ['Pagado', 'success']];

    public const TIPOS = ['MENSUAL' => 'Mensual', 'QUINCENAL' => 'Quincenal', 'CATORCENAL' => 'Catorcenal', 'SEMANAL' => 'Semanal'];

    private const EDITABLES = ['ABIERTO', 'EN_PROCESO'];

    /** Días que puede abarcar cada tipo de periodo (mínimo, máximo). */
    private const DURACIONES = ['MENSUAL' => [28, 31], 'QUINCENAL' => [13, 16], 'CATORCENAL' => [14, 14], 'SEMANAL' => [7, 7]];

    public function index(Request $request): View
    {
        // Resumen del último periodo calculado: líquido, cuota patronal y costo total con provisiones.
        $ultimo = $this->periodos($request)->whereHas('detalles')->orderByDesc('fecha_inicio')->first();
        $resumen = null;
        if ($ultimo) {
            $detalles = $ultimo->detalles()->get(['id_detalle', 'total_ingresos', 'cuota_igss_pat']);
            $patronal = round((float) $detalles->sum('cuota_igss_pat'), 2);
            $provisiones = Nomina::provisionesSobreBase($this->baseProvisiones($ultimo));
            $resumen = [
                'periodo' => $ultimo,
                'empleados' => $detalles->count(),
                'patronal' => $patronal,
                'provisiones' => $provisiones['total'],
                'costo_total' => round((float) $ultimo->total_bruto + $patronal + $provisiones['total'], 2),
            ];
        }

        return view('nomina.index', [
            'resumen' => $resumen,
            'provisiones' => Nomina::PROVISIONES,
            'periodos' => $this->periodos($request)->withCount('detalles')->orderByDesc('fecha_inicio')->paginate(24),
            'prestamos' => PrestamoEmpleado::query()->where('id_empresa', $this->idEmpresa($request))->where('estado', 'ACTIVO')->with('empleado')->orderByDesc('fecha_otorgamiento')->get(),
            'empleados' => $this->empleados($request)->get(),
            'estados' => self::ESTADOS,
            'tipos' => self::TIPOS,
            'sugerido' => $this->periodoSugerido($request),
        ]);
    }

    public function show(Request $request, int $periodo, NominaService $nominaService): View
    {
        $p = $this->periodos($request)->findOrFail($periodo);
        $conceptos = $nominaService->asegurarConceptos($p->id_empresa);

        return view('nomina.show', [
            'p' => $p,
            'provisiones' => Nomina::provisionesSobreBase($this->baseProvisiones($p)),
            'detalles' => $p->detalles()->with(['empleado', 'conceptos.concepto'])->get()->sortBy(fn ($d) => $d->empleado?->nombre_completo)->values(),
            'manuales' => collect($conceptos)->except(Nomina::AUTOMATICOS)->where('activo', true)->values(),
            'estados' => self::ESTADOS,
            'tipos' => self::TIPOS,
            'editable' => in_array($p->estado, self::EDITABLES, true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEmpresa = $this->idEmpresa($request);
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(self::TIPOS))],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'fecha_pago' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'nombre' => ['nullable', 'string', 'max:100'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
        ]);
        // La duración debe corresponder al tipo: un «mensual» de 15 días pagaría un mes completo.
        $largo = (int) Carbon::parse($datos['fecha_inicio'])->diffInDays(Carbon::parse($datos['fecha_fin'])) + 1;
        [$minimo, $maximo] = self::DURACIONES[$datos['tipo']];
        if ($largo < $minimo || $largo > $maximo) {
            throw ValidationException::withMessages(['fecha_fin' => 'Un periodo '.strtolower(self::TIPOS[$datos['tipo']])." dura de {$minimo} a {$maximo} días; estas fechas abarcan {$largo}."]);
        }
        $datos['nombre'] ??= Nomina::nombreSugerido($datos['tipo'], Carbon::parse($datos['fecha_inicio']));

        // Cada periodo procesa a toda la planilla: dos periodos que se cruzan (de cualquier tipo)
        // le pagarían dos veces los mismos días. Bloquea la empresa para que un doble clic no cree dos.
        $p = DB::transaction(function () use ($request, $idEmpresa, $datos) {
            DB::table('empresa')->where('id_empresa', $idEmpresa)->lockForUpdate()->first();
            $cruce = $this->periodos($request)
                ->where('fecha_inicio', '<=', $datos['fecha_fin'])->where('fecha_fin', '>=', $datos['fecha_inicio'])->first();
            if ($cruce) {
                throw ValidationException::withMessages(['fecha_inicio' => "Esas fechas se cruzan con el periodo «{$cruce->nombre}»: se pagarían dos veces los mismos días."]);
            }

            return PeriodoNomina::create($datos + ['id_empresa' => $idEmpresa, 'estado' => 'ABIERTO', 'created_by' => $this->idUsuario($request)]);
        });

        return redirect()->route('nomina.show', $p->id_periodo)->with('status', "Periodo «{$p->nombre}» creado. Procésalo para calcular la nómina.");
    }

    /** Calcula (o recalcula) a todos los empleados con contrato vigente en el periodo; conserva los ajustes manuales. */
    public function procesar(Request $request, int $periodo): RedirectResponse
    {
        return DB::transaction(function () use ($request, $periodo) {
            $p = $this->periodos($request)->lockForUpdate()->findOrFail($periodo);
            if (! in_array($p->estado, self::EDITABLES, true)) {
                return back()->withErrors(['periodo' => 'El periodo ya está cerrado.']);
            }
            [$procesados, $sinContrato, $obsoletos] = $this->calcularPeriodo($p);
            $p->update(['estado' => 'EN_PROCESO']);

            return back()->with('status', "Nómina calculada para {$procesados} empleados."
                .($obsoletos ? " Se quitaron {$obsoletos} que ya no corresponden a este periodo." : '')
                .($sinContrato ? ' Sin contrato vigente (no incluidos): '.implode(', ', $sinContrato).'.' : ''));
        });
    }

    /**
     * Calcula (o recalcula) a todos los empleados con contrato vigente en el periodo, quita a
     * quien ya no corresponde y actualiza los totales. Respeta los ajustes manuales.
     *
     * @return array{0: int, 1: list<string>, 2: int} procesados, sin contrato, quitados
     */
    private function calcularPeriodo(PeriodoNomina $p): array
    {
        $empleados = Empleado::query()->where('id_empresa', $p->id_empresa)->with(['cargo', 'contratos', 'historialSalarial'])
            ->where('fecha_ingreso', '<=', $p->fecha_fin)
            ->where(fn ($q) => $q->whereNull('fecha_baja')->orWhere('fecha_baja', '>=', $p->fecha_inicio))->get();

        $procesados = 0;
        $sinContrato = [];
        $incluidos = [];
        foreach ($empleados as $e) {
            // Contrato que cubre el periodo (el vigente, o el rescindido si la baja cae dentro).
            $contrato = $e->contratos->filter(fn ($c) => $c->fecha_inicio->lte($p->fecha_fin) && (! $c->fecha_fin || $c->fecha_fin->gte($p->fecha_inicio)))
                ->sortByDesc('fecha_inicio')->first();
            // Un rotativo sin contrato entra solo si cubrió a alguien en el periodo: cobra por día.
            if (! $contrato && $e->es_rotativo) {
                if (Nomina::coberturas($p, $e->id_empleado)->isEmpty()) {
                    continue;
                }
            } elseif (! $contrato) {
                $sinContrato[] = $e->nombre_completo;

                continue;
            }
            // Salario según la fecha efectiva de cada cambio: un aumento a mitad del periodo
            // se prorratea por días y uno con fecha futura todavía no cuenta.
            $salario = 0.0;
            if ($contrato) {
                $desde = $e->fecha_ingreso && $e->fecha_ingreso->gt($p->fecha_inicio) ? $e->fecha_ingreso : $p->fecha_inicio;
                $hasta = $e->fecha_baja && $e->fecha_baja->lt($p->fecha_fin) ? $e->fecha_baja : $p->fecha_fin;
                $salario = Salarios::promedio($e, $desde, $hasta) ?? (float) $contrato->salario_base;
            }
            Nomina::procesarEmpleado($p, $e, $salario);
            $incluidos[] = $e->id_empleado;
            $procesados++;
        }
        // Quien ya no califica (cobertura anulada, contrato que ya no cubre el periodo, baja
        // anterior) sale de la nómina: si no, se le pagaría lo calculado antes.
        $obsoletos = $p->detalles()->whereNotIn('id_empleado', $incluidos)->pluck('id_detalle');
        if ($obsoletos->isNotEmpty()) {
            DetalleNominaConcepto::query()->whereIn('id_detalle', $obsoletos)->delete();
            DetalleNomina::query()->whereIn('id_detalle', $obsoletos)->delete();
        }
        Nomina::totalesPeriodo($p);

        return [$procesados, $sinContrato, $obsoletos->count()];
    }
    /** Ajustes por empleado: días trabajados y conceptos manuales (horas extra, bonos, descuentos). */
    public function ajustar(Request $request, int $periodo, int $detalle): RedirectResponse
    {
        $p = $this->periodos($request)->findOrFail($periodo);
        $d = $p->detalles()->findOrFail($detalle);
        if (! in_array($p->estado, self::EDITABLES, true)) {
            return back()->withErrors(['periodo' => 'El periodo ya está cerrado.']);
        }
        [$diasPeriodo] = Nomina::PERIODOS[$p->tipo];
        $manuales = collect(Nomina::conceptos($p->id_empresa))->except(Nomina::AUTOMATICOS)->pluck('id_concepto')->all();
        $datos = $request->validateWithBag('ajuste', [
            'dias_trabajados' => ['nullable', 'numeric', 'min:0', 'max:'.$diasPeriodo],
            'horas_extra' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'id_concepto' => ['nullable', 'integer', Rule::in($manuales), 'required_with:monto'],
            'monto' => ['nullable', 'numeric', 'min:0.01', 'max:9999999', 'required_with:id_concepto'],
            'descripcion' => ['nullable', 'string', 'max:200'],
        ], ['dias_trabajados.max' => "Un periodo {$p->tipo} tiene como máximo {$diasPeriodo} días."]);

        $error = DB::transaction(function () use ($p, $d, $datos) {
            if (! in_array(PeriodoNomina::query()->lockForUpdate()->whereKey($p->id_periodo)->value('estado'), self::EDITABLES, true)) {
                return 'El periodo ya está cerrado.';
            }
            // Lo que se ajusta a mano queda marcado: al reprocesar se respeta en vez de recalcularse.
            $cambios = [];
            if (isset($datos['dias_trabajados'])) {
                $cambios += ['dias_trabajados' => $datos['dias_trabajados'], 'dias_manual' => true];
            }
            if (isset($datos['horas_extra'])) {
                $cambios += ['horas_extra' => $datos['horas_extra'], 'horas_manual' => true];
            }
            $d->update($cambios);
            if (! empty($datos['id_concepto'])) {
                $concepto = ConceptoNomina::query()->findOrFail($datos['id_concepto']);
                DetalleNominaConcepto::create(['id_detalle' => $d->id_detalle, 'id_concepto' => $concepto->id_concepto, 'tipo' => $concepto->tipo,
                    'monto' => round((float) $datos['monto'], 2), 'descripcion' => $datos['descripcion'] ?? null]);
            }
            Nomina::recalcular($d->fresh(), $p);
            Nomina::totalesPeriodo($p);

            return null;
        });
        if ($error) {
            return back()->withErrors(['periodo' => $error]);
        }

        return back()->with('status', 'Ajuste aplicado a '.$d->empleado?->nombre_completo.'.');
    }

    public function quitarConcepto(Request $request, int $periodo, int $linea): RedirectResponse
    {
        $p = $this->periodos($request)->findOrFail($periodo);
        if (! in_array($p->estado, self::EDITABLES, true)) {
            return back()->withErrors(['periodo' => 'El periodo ya está cerrado.']);
        }
        $l = DetalleNominaConcepto::query()->whereIn('id_detalle', $p->detalles()->pluck('id_detalle'))->findOrFail($linea);
        $automatico = ConceptoNomina::query()->whereKey($l->id_concepto)->whereIn('codigo', Nomina::AUTOMATICOS)->exists();
        abort_if($automatico, 403, 'Los conceptos automáticos se recalculan solos.');

        $error = DB::transaction(function () use ($p, $l) {
            if (! in_array(PeriodoNomina::query()->lockForUpdate()->whereKey($p->id_periodo)->value('estado'), self::EDITABLES, true)) {
                return 'El periodo ya está cerrado.';
            }
            $d = DetalleNomina::query()->findOrFail($l->id_detalle);
            $l->delete();
            Nomina::recalcular($d, $p);
            Nomina::totalesPeriodo($p);

            return null;
        });
        if ($error) {
            return back()->withErrors(['periodo' => $error]);
        }

        return back()->with('status', 'Concepto quitado.');
    }

    public function cerrar(Request $request, int $periodo): RedirectResponse
    {
        return $this->transicion($request, $periodo, 'EN_PROCESO', 'CERRADO', function (PeriodoNomina $p) {
            if (! $p->detalles()->exists()) {
                return 'El periodo no tiene empleados calculados.';
            }
            // Se cierra lo mismo que se revisó: si algo cambió desde el último cálculo (coberturas,
            // préstamos, ausencias, bajas…) se avisa y no se cierra. La excepción deshace el recálculo.
            $antes = $p->detalles()->pluck('liquido_pagar', 'id_empleado')->map(fn ($v) => round((float) $v, 2))->all();
            $this->calcularPeriodo($p);
            $despues = $p->detalles()->pluck('liquido_pagar', 'id_empleado')->map(fn ($v) => round((float) $v, 2))->all();
            ksort($antes);
            ksort($despues);
            if ($antes !== $despues) {
                $nombres = Empleado::query()->whereIn('id_empleado', array_keys(array_diff_assoc($despues, $antes) + array_diff_assoc($antes, $despues)))
                    ->get()->map(fn ($e) => $e->nombre_completo)->take(5)->implode(', ');
                throw ValidationException::withMessages(['periodo' => "Hubo cambios desde el último cálculo ({$nombres}). Vuelve a procesar el periodo, revísalo y ciérralo."]);
            }
            $negativos = $p->detalles()->where('liquido_pagar', '<', 0)->with('empleado')->get();
            if ($negativos->isNotEmpty()) {
                throw ValidationException::withMessages(['periodo' => 'El líquido queda negativo para '.$negativos->map(fn ($d) => $d->empleado?->nombre_completo)->implode(', ').': ajusta sus descuentos antes de cerrar.']);
            }
            $corregidos = Nomina::aplicarPrestamos($p, 1);
            $p->detalles()->update(['estado_pago' => 'APROBADO']);
            if ($corregidos) {
                session()->flash('aviso', "A {$corregidos} empleado(s) se les bajó el descuento del préstamo: otro periodo ya había descontado parte del saldo.");
            }

            return null;
        }, 'cerrado: se descontaron las cuotas de los préstamos.');
    }

    public function reabrir(Request $request, int $periodo): RedirectResponse
    {
        return $this->transicion($request, $periodo, 'CERRADO', 'EN_PROCESO', function (PeriodoNomina $p) {
            Nomina::aplicarPrestamos($p, -1);
            $p->detalles()->update(['estado_pago' => 'CALCULADO']);

            return null;
        }, 'reabierto: las cuotas de los préstamos se devolvieron al saldo.');
    }

    public function pagar(Request $request, int $periodo): RedirectResponse
    {
        $fecha = $request->validate(['fecha_pago' => ['required', 'date', 'before_or_equal:today']], ['fecha_pago.before_or_equal' => 'La fecha de pago no puede ser futura.'])['fecha_pago'];

        return $this->transicion($request, $periodo, 'CERRADO', 'PAGADO', function (PeriodoNomina $p) use ($fecha) {
            $p->update(['fecha_pago' => $fecha]);
            $p->detalles()->update(['estado_pago' => 'PAGADO', 'fecha_pago' => $fecha]);

            return null;
        }, 'marcado como pagado.');
    }

    /** Boletas de pago imprimibles (todas, o la de un empleado con ?detalle=). */
    public function imprimir(Request $request, int $periodo): View
    {
        $p = $this->periodos($request)->findOrFail($periodo);

        return view('nomina.boletas', [
            'p' => $p,
            'detalles' => $p->detalles()->with(['empleado', 'conceptos.concepto'])
                ->when($request->filled('detalle'), fn ($q) => $q->whereKey($request->integer('detalle')))->get()
                ->sortBy(fn ($d) => $d->empleado?->nombre_completo),
            'empresa' => Empresa::find($p->id_empresa),
            'tipos' => self::TIPOS,
        ]);
    }

    /** Planilla completa del periodo en CSV (una fila por empleado). */
    public function exportar(Request $request, int $periodo): StreamedResponse
    {
        $p = $this->periodos($request)->findOrFail($periodo);
        $detalles = $p->detalles()->with(['empleado', 'conceptos.concepto'])->get()->sortBy(fn ($d) => $d->empleado?->nombre_completo);
        $monto = fn ($d, string $codigo) => round((float) $d->conceptos->filter(fn ($l) => $l->concepto?->codigo === $codigo)->sum('monto'), 2);

        return ExportarCsv::descargar('planilla_'.$p->id_periodo, [
            'Código', 'Empleado', 'Cargo', 'Días', 'Horas extra', 'Salario base', 'Bonificación incentivo', 'Horas extra (monto)',
            'Total ingresos', 'IGSS laboral', 'ISR', 'Préstamos', 'Total deducciones', 'Líquido', 'Cuota patronal',
        ], $detalles->map(fn ($d) => [
            $d->empleado?->codigo_empleado, $d->empleado?->nombre_completo, $d->cargo_snapshot, (float) $d->dias_trabajados, (float) $d->horas_extra,
            $monto($d, 'SALBASE'), $monto($d, 'BONINC'), $monto($d, 'HEXTRA'), (float) $d->total_ingresos,
            (float) $d->cuota_igss_emp, (float) $d->isr_retenido, $monto($d, 'PRESTAMO'), (float) $d->total_deducciones,
            (float) $d->liquido_pagar, (float) $d->cuota_igss_pat,
        ]));
    }

    /** Archivo para acreditar en el banco: un renglón por empleado con líquido positivo. */
    public function banco(Request $request, int $periodo): StreamedResponse
    {
        $p = $this->periodos($request)->findOrFail($periodo);
        $detalles = $p->detalles()->with('empleado')->where('liquido_pagar', '>', 0)->get()->sortBy(fn ($d) => $d->empleado?->nombre_completo);

        return ExportarCsv::descargar('banco_'.$p->id_periodo, ['Código', 'Documento', 'Nombre', 'Monto', 'Moneda', 'Referencia'],
            $detalles->map(fn ($d) => [
                $d->empleado?->codigo_empleado, $d->empleado?->dpi_nit, $d->empleado?->nombre_completo,
                number_format((float) $d->liquido_pagar, 2, '.', ''), $p->moneda, 'NOMINA-'.$p->id_periodo,
            ]));
    }

    public function guardarPrestamo(Request $request): RedirectResponse
    {
        $idEmpresa = $this->idEmpresa($request);
        $datos = $request->validateWithBag('prestamo', [
            'id_empleado' => ['required', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')->whereNotIn('estado', ['BAJA'])],
            'monto_total' => ['required', 'numeric', 'min:1', 'max:9999999'],
            'cuota_quincenal' => ['required', 'numeric', 'min:1', 'lte:monto_total'],
            'fecha_otorgamiento' => ['required', 'date', 'before_or_equal:today'],
            'motivo' => ['nullable', 'string', 'max:300'],
        ], ['cuota_quincenal.lte' => 'La cuota no puede ser mayor que el préstamo.']);
        PrestamoEmpleado::create($datos + ['id_empresa' => $idEmpresa, 'monto_pendiente' => $datos['monto_total'], 'moneda' => 'GTQ', 'estado' => 'ACTIVO', 'aprobado_por' => $this->idUsuario($request)]);

        return back()->with('status', 'Préstamo registrado: se descontará en las próximas nóminas.');
    }

    // ── Privados ─────────────────────────────────────────────────────────

    /** @param  callable(PeriodoNomina): ?string  $cambio */
    private function transicion(Request $request, int $periodo, string $desde, string $hacia, callable $cambio, string $mensaje): RedirectResponse
    {
        return DB::transaction(function () use ($request, $periodo, $desde, $hacia, $cambio, $mensaje) {
            $p = $this->periodos($request)->lockForUpdate()->findOrFail($periodo);
            if ($p->estado !== $desde) {
                return back()->withErrors(['periodo' => 'El periodo debe estar '.strtolower(self::ESTADOS[$desde][0]).'.']);
            }
            if ($error = $cambio($p)) {
                return back()->withErrors(['periodo' => $error]);
            }
            $p->update(['estado' => $hacia]);

            return back()->with('status', "Periodo «{$p->nombre}» {$mensaje}");
        });
    }

    /** Base de las provisiones: salario ordinario y horas extra devengados en el periodo. */
    private function baseProvisiones(PeriodoNomina $p): float
    {
        $ids = ConceptoNomina::query()->where('id_empresa', $p->id_empresa)->whereIn('codigo', ['SALBASE', 'HEXTRA'])->pluck('id_concepto');

        return (float) DetalleNominaConcepto::query()->whereIn('id_detalle', $p->detalles()->pluck('id_detalle'))
            ->whereIn('id_concepto', $ids)->sum('monto');
    }

    private function periodos(Request $request): Builder
    {
        return $this->consultaEmpresa(PeriodoNomina::class, $request);
    }

    private function empleados(Request $request): Builder
    {
        return Empleado::query()->where('id_empresa', $this->idEmpresa($request))->where('estado', '!=', 'BAJA')->orderBy('primer_nombre');
    }

    /** Siguiente quincena después del último periodo quincenal (o la actual). */
    private function periodoSugerido(Request $request): array
    {
        $ultimo = $this->periodos($request)->where('tipo', 'QUINCENAL')->orderByDesc('fecha_fin')->first();
        $inicio = $ultimo ? $ultimo->fecha_fin->copy()->addDay() : (now()->day <= 15 ? now()->startOfMonth() : now()->startOfMonth()->addDays(15));
        $fin = $inicio->day === 1 ? $inicio->copy()->addDays(14) : $inicio->copy()->endOfMonth();

        return ['tipo' => 'QUINCENAL', 'fecha_inicio' => $inicio->format('Y-m-d'), 'fecha_fin' => $fin->format('Y-m-d'), 'fecha_pago' => $fin->format('Y-m-d')];
    }
}

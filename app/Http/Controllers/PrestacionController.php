<?php

namespace App\Http\Controllers;

use App\Models\RRHH\Empleado;
use App\Models\RRHH\PrestacionLaboral;
use App\Support\ExportarCsv;
use App\Support\Prestaciones;
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
 * Prestaciones laborales: aguinaldo y bono 14 de toda la planilla, y liquidaciones por empleado.
 * Permisos: prestaciones.ver / procesar (calcular, pagar, eliminar cálculos) / aprobar / exportar.
 * Flujo: CALCULADO → (aprobar) APROBADO → (pagar) PAGADO. Cálculo en App\Support\Prestaciones.
 */
class PrestacionController extends Controller
{
    public const ESTADOS = ['CALCULADO' => ['Calculado', 'secondary'], 'APROBADO' => ['Aprobado', 'warning'], 'PAGADO' => ['Pagado', 'success']];

    public function index(Request $request): View
    {
        $prestaciones = $this->filtradas($request)->with('empleado')->get()->sortBy(fn ($p) => $p->empleado?->nombre_completo)->values();

        return view('prestaciones.index', [
            'anio' => $this->anio($request),
            // Un grupo por prestación anual (tipo + año) y uno por liquidación (empleado + fecha de salida).
            'grupos' => $prestaciones->groupBy(fn ($p) => $this->esLiquidacion($p) ? $p->periodo_calculo.'|'.$p->id_empleado : $p->tipo.'|'.$p->periodo_calculo)
                ->sortKeys(),
            'totales' => $prestaciones->groupBy('estado')->map(fn ($g) => round((float) $g->sum('monto_calculado'), 2)),
            'empleados' => Empleado::query()->where('id_empresa', $request->user()->id_empresa)->orderBy('primer_nombre')->orderBy('primer_apellido')->get(),
            'tipos' => Prestaciones::TIPOS,
            'anuales' => Prestaciones::ANUALES,
            'estados' => self::ESTADOS,
            'filtros' => $request->only(['tipo', 'estado']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $prestaciones = $this->filtradas($request)->with('empleado')->get()->sortBy(fn ($p) => $p->empleado?->nombre_completo);

        return ExportarCsv::descargar('prestaciones', ['Empleado', 'Código', 'Prestación', 'Periodo', 'Días', 'Base', 'Monto', 'Estado', 'Fecha de pago'],
            $prestaciones->map(fn ($p) => [$p->empleado?->nombre_completo, $p->empleado?->codigo_empleado, Prestaciones::TIPOS[$p->tipo] ?? $p->tipo, $p->periodo_calculo,
                (float) $p->dias_calculados, (float) $p->monto_base, (float) $p->monto_calculado, self::ESTADOS[$p->estado][0] ?? $p->estado, $p->fecha_pago?->format('d/m/Y')]));
    }

    /** Aguinaldo o bono 14 del año para toda la planilla. Recalcular conserva lo aprobado o pagado. */
    public function calcular(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(Prestaciones::ANUALES)],
            'anio' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
        ]);
        $r = DB::transaction(fn () => Prestaciones::calcularAnual($request->user()->id_empresa, $datos['tipo'], (int) $datos['anio']));

        return redirect()->route('prestaciones.index', ['anio' => $datos['anio'], 'tipo' => $datos['tipo']])
            ->with('status', Prestaciones::TIPOS[$datos['tipo']]." {$datos['anio']}: {$r['calculados']} empleados calculados."
                .($r['conservados'] ? " {$r['conservados']} ya aprobados o pagados se conservaron." : '')
                .($r['sinContrato'] ? ' Sin contrato (no incluidos): '.implode(', ', $r['sinContrato']).'.' : ''));
    }

    /** Liquidación de un empleado a su fecha de salida. */
    public function liquidar(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('liquidacion', [
            'id_empleado' => ['required', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $request->user()->id_empresa)->whereNull('deleted_at')],
            'fecha_salida' => ['required', 'date'],
            'dias_vacaciones' => ['nullable', 'numeric', 'min:0', 'max:90'],
            'indemnizacion' => ['nullable', 'boolean'],
        ]);
        $e = Empleado::query()->findOrFail($datos['id_empleado']);
        $salida = Carbon::parse($datos['fecha_salida'])->startOfDay();
        if ($salida->lt($e->fecha_ingreso)) {
            return back()->withErrors(['fecha_salida' => 'La salida no puede ser antes del ingreso.'], 'liquidacion')->withInput();
        }
        try {
            $lineas = DB::transaction(fn () => Prestaciones::liquidar($e, $salida, $request->boolean('indemnizacion'), (float) ($datos['dias_vacaciones'] ?? 0)));
        } catch (ValidationException $ex) {
            return back()->withErrors($ex->errors(), 'liquidacion')->withInput();
        }

        return redirect()->route('prestaciones.index', ['anio' => $salida->year])
            ->with('status', "Liquidación de {$e->nombre_completo} calculada: ".number_format((float) collect($lineas)->sum('monto_calculado'), 2).'.');
    }

    public function aprobar(Request $request, int $prestacion): RedirectResponse
    {
        return $this->transicion($this->deMiEmpresa($request)->whereKey($prestacion), 'CALCULADO', ['estado' => 'APROBADO'], 'aprobada', true);
    }

    public function pagar(Request $request, int $prestacion): RedirectResponse
    {
        $fecha = $request->validate(['fecha_pago' => ['required', 'date']])['fecha_pago'];

        return $this->transicion($this->deMiEmpresa($request)->whereKey($prestacion), 'APROBADO', ['estado' => 'PAGADO', 'fecha_pago' => $fecha], 'pagada', true);
    }

    /** Aprueba todas las calculadas de un grupo (prestación anual o liquidación). */
    public function aprobarLote(Request $request): RedirectResponse
    {
        return $this->transicion($this->lote($request), 'CALCULADO', ['estado' => 'APROBADO'], 'aprobadas');
    }

    /** Paga todas las aprobadas de un grupo. */
    public function pagarLote(Request $request): RedirectResponse
    {
        $fecha = $request->validate(['fecha_pago' => ['required', 'date']])['fecha_pago'];

        return $this->transicion($this->lote($request), 'APROBADO', ['estado' => 'PAGADO', 'fecha_pago' => $fecha], 'pagadas');
    }

    /** Solo se elimina un cálculo que nadie ha aprobado. */
    public function destroy(Request $request, int $prestacion): RedirectResponse
    {
        return DB::transaction(function () use ($request, $prestacion) {
            $p = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($prestacion);
            if ($p->estado !== 'CALCULADO') {
                return back()->withErrors(['prestacion' => 'Solo se eliminan prestaciones en estado calculado.']);
            }
            $p->delete();

            return back()->with('status', 'Cálculo eliminado.');
        });
    }

    // ── Privados ─────────────────────────────────────────────────────────

    /** Pasa de $desde al estado nuevo las prestaciones de la consulta, una por una para que queden en la bitácora. */
    private function transicion(Builder $consulta, string $desde, array $cambio, string $hecho, bool $una = false): RedirectResponse
    {
        return DB::transaction(function () use ($consulta, $desde, $cambio, $hecho, $una) {
            $prestaciones = $consulta->lockForUpdate()->get();
            abort_if($una && $prestaciones->isEmpty(), 404);
            $cambiar = $prestaciones->where('estado', $desde);
            if ($cambiar->isEmpty()) {
                return back()->withErrors(['prestacion' => 'No hay prestaciones en estado '.strtolower(self::ESTADOS[$desde][0]).' para ese cambio.']);
            }
            $cambiar->each->update($cambio);

            return back()->with('status', $una ? "Prestación {$hecho}." : "{$cambiar->count()} prestaciones {$hecho} (".number_format((float) $cambiar->sum('monto_calculado'), 2).').');
        });
    }

    /** Prestaciones de un grupo de la pantalla: periodo, y tipo (anuales) o empleado (liquidación). */
    private function lote(Request $request): Builder
    {
        $datos = $request->validate([
            'periodo' => ['required', 'string', 'max:20'],
            'tipo' => ['nullable', Rule::in(array_keys(Prestaciones::TIPOS))],
            'id_empleado' => ['nullable', 'integer'],
        ]);

        return $this->deMiEmpresa($request)->where('periodo_calculo', $datos['periodo'])
            ->when($datos['tipo'] ?? null, fn ($q, $tipo) => $q->where('tipo', $tipo))
            ->when($datos['id_empleado'] ?? null, fn ($q, $id) => $q->where('id_empleado', $id));
    }

    private function filtradas(Request $request): Builder
    {
        $anio = $this->anio($request);

        return $this->deMiEmpresa($request)
            ->where(fn ($q) => $q->where('periodo_calculo', (string) $anio)->orWhere('periodo_calculo', 'like', Prestaciones::PREFIJO_LIQUIDACION.$anio.'%'))
            ->when(isset(Prestaciones::TIPOS[(string) $request->query('tipo')]), fn ($q) => $q->where('tipo', $request->query('tipo')))
            ->when(isset(self::ESTADOS[(string) $request->query('estado')]), fn ($q) => $q->where('estado', $request->query('estado')));
    }

    private function anio(Request $request): int
    {
        $anio = (int) $request->query('anio');

        return $anio >= 2000 && $anio <= 2100 ? $anio : (int) now()->year;
    }

    private function esLiquidacion(PrestacionLaboral $p): bool
    {
        return str_starts_with($p->periodo_calculo, Prestaciones::PREFIJO_LIQUIDACION);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return PrestacionLaboral::query()->where('id_empresa', $request->user()->id_empresa);
    }
}

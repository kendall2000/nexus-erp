<?php

namespace App\Http\Controllers;

use App\Models\RRHH\CoberturaRotativo;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\PeriodoNomina;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Personal rotativo: coberturas de empleados ausentes (vacaciones, suspensiones, incapacidades…)
 * pagadas por día. Permisos: asistencia.ver / editar (registrar y anular coberturas).
 * El rotativo se marca en la ficha del empleado con su tarifa diaria habitual; cada cobertura
 * puede llevar otra tarifa. Nómina paga los días cubiertos del periodo (App\Support\Nomina).
 */
class RotativoController extends Controller
{
    public const ESTADOS = ['VIGENTE' => ['Vigente', 'success'], 'ANULADA' => ['Anulada', 'secondary']];

    public const MOTIVOS = [
        'VACACIONES' => 'Vacaciones', 'SUSPENSION' => 'Suspensión', 'INCAPACIDAD' => 'Incapacidad (IGSS)', 'PERMISO' => 'Permiso',
        'LICENCIA' => 'Licencia', 'AUSENCIA' => 'Ausencia', 'VACANTE' => 'Puesto vacante', 'OTRO' => 'Otro',
    ];

    public function index(Request $request): View
    {
        $mes = $this->mes($request->query('mes'));
        $coberturas = $this->deMiEmpresa($request)->entre($mes, $mes->copy()->endOfMonth())->with(['rotativo', 'titular.cargo'])
            ->orderByDesc('fecha_inicio')->orderByDesc('id_cobertura')->get();

        return view('rotativos.index', [
            'mes' => $mes,
            'coberturas' => $coberturas,
            // Días y monto del mes por rotativo (solo las coberturas vigentes, solo los días que caen en el mes).
            'resumen' => $coberturas->where('estado', 'VIGENTE')->groupBy('id_rotativo')->map(fn ($grupo) => [
                'rotativo' => $grupo->first()->rotativo,
                'dias' => $grupo->sum(fn ($c) => $c->diasEn($mes, $mes->copy()->endOfMonth())),
                'monto' => round($grupo->sum(fn ($c) => $c->diasEn($mes, $mes->copy()->endOfMonth()) * (float) $c->tarifa_dia), 2),
            ])->sortByDesc('monto')->values(),
            'rotativos' => $this->empleados($request)->where('es_rotativo', true)->get(),
            'titulares' => $this->empleados($request)->with('cargo')->get(),
            'motivos' => self::MOTIVOS,
            'estados' => self::ESTADOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $empleado = fn () => Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')->whereNot('estado', 'BAJA');
        $datos = $request->validate([
            'id_rotativo' => ['required', 'integer', $empleado()->where('es_rotativo', true)],
            'motivo' => ['required', Rule::in(array_keys(self::MOTIVOS))],
            'id_titular' => [Rule::requiredIf($request->input('motivo') !== 'VACANTE'), 'nullable', 'integer', 'different:id_rotativo', $empleado()],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'paga_fines_semana' => ['nullable', 'boolean'],
            'tarifa_dia' => ['nullable', 'numeric', 'min:0.01', 'max:999999'],
            'observaciones' => ['nullable', 'string', 'max:300'],
        ], [
            'id_rotativo.exists' => 'El empleado elegido no está marcado como rotativo.',
            'id_titular.required' => 'Indica a quién cubre (o elige «Puesto vacante»).',
            'id_titular.different' => 'El rotativo no puede cubrirse a sí mismo.',
        ]);
        $datos['paga_fines_semana'] = $request->boolean('paga_fines_semana');
        $datos['tarifa_dia'] ??= Empleado::query()->whereKey($datos['id_rotativo'])->value('tarifa_dia');
        if (! $datos['tarifa_dia']) {
            throw ValidationException::withMessages(['tarifa_dia' => 'El rotativo no tiene tarifa diaria: escríbela aquí o en su ficha.']);
        }
        $dias = CoberturaRotativo::diasEntre(Carbon::parse($datos['fecha_inicio']), Carbon::parse($datos['fecha_fin']), $datos['paga_fines_semana']);
        if ($dias === 0) {
            throw ValidationException::withMessages(['fecha_inicio' => 'El rango solo tiene fin de semana: marca «Paga fines de semana» o cambia las fechas.']);
        }
        if ($dias > 999) {
            throw ValidationException::withMessages(['fecha_fin' => 'La cobertura es demasiado larga: regístrala por tramos.']);
        }

        $cobertura = DB::transaction(function () use ($request, $datos, $dias) {
            // Bloquea al rotativo para que dos registros simultáneos no lo dejen en dos puestos a la vez.
            Empleado::query()->whereKey($datos['id_rotativo'])->lockForUpdate()->first();
            $this->sinNominaCerrada($request, $datos['fecha_inicio'], $datos['fecha_fin']);
            $cruces = fn () => $this->deMiEmpresa($request)->vigentes()->entre($datos['fecha_inicio'], $datos['fecha_fin']);
            if ($cruces()->where('id_rotativo', $datos['id_rotativo'])->exists()) {
                throw ValidationException::withMessages(['id_rotativo' => 'El rotativo ya tiene otra cobertura en esas fechas.']);
            }
            if (! empty($datos['id_titular']) && $cruces()->where('id_titular', $datos['id_titular'])->exists()) {
                throw ValidationException::withMessages(['id_titular' => 'Ese empleado ya tiene quien lo cubra en esas fechas.']);
            }

            return CoberturaRotativo::create(array_merge($datos, [
                'id_empresa' => $request->user()->id_empresa, 'dias' => $dias, 'total' => round($dias * (float) $datos['tarifa_dia'], 4),
                'estado' => 'VIGENTE', 'created_by' => $request->user()->id_usuario,
            ]));
        });

        return redirect()->route('rotativos.index', ['mes' => $cobertura->fecha_inicio->format('Y-m')])
            ->with('status', "Cobertura registrada: {$dias} días × ".number_format((float) $cobertura->tarifa_dia, 2).' = '.number_format((float) $cobertura->total, 2).'. Se pagará en la nómina.');
    }

    /** Las coberturas no se borran: se anulan (y dejan de pagarse) mientras la nómina siga abierta. */
    public function anular(Request $request, int $cobertura): RedirectResponse
    {
        return DB::transaction(function () use ($request, $cobertura) {
            $c = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($cobertura);
            if ($c->estado !== 'VIGENTE') {
                return back()->withErrors(['cobertura' => 'La cobertura ya estaba anulada.']);
            }
            try {
                $this->sinNominaCerrada($request, $c->fecha_inicio->toDateString(), $c->fecha_fin->toDateString());
            } catch (ValidationException $e) {
                return back()->withErrors(['cobertura' => $e->validator->errors()->first()]);
            }
            $c->update(['estado' => 'ANULADA', 'anulada_por' => $request->user()->id_usuario]);

            return back()->with('status', 'Cobertura anulada: recalcula la nómina del periodo si ya estaba procesada.');
        });
    }

    // ── Privados ─────────────────────────────────────────────────────────

    /** Una nómina cerrada o pagada ya no cambia: no se registran ni anulan coberturas en sus fechas. */
    private function sinNominaCerrada(Request $request, string $desde, string $hasta): void
    {
        $periodo = PeriodoNomina::query()->where('id_empresa', $request->user()->id_empresa)->whereIn('estado', ['CERRADO', 'PAGADO'])
            ->whereDate('fecha_inicio', '<=', $hasta)->whereDate('fecha_fin', '>=', $desde)->first();
        if ($periodo) {
            throw ValidationException::withMessages(['fecha_inicio' => "Esas fechas tocan una nómina ya cerrada («{$periodo->nombre}»): reábrela o usa otras fechas."]);
        }
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return CoberturaRotativo::query()->where('id_empresa', $request->user()->id_empresa);
    }

    private function empleados(Request $request): Builder
    {
        return Empleado::query()->where('id_empresa', $request->user()->id_empresa)->where('estado', '!=', 'BAJA')
            ->orderBy('primer_nombre')->orderBy('primer_apellido');
    }

    /** Primer día del mes pedido («2026-10»), o del mes actual. */
    private function mes(mixed $valor): Carbon
    {
        try {
            return is_string($valor) && preg_match('/^\d{4}-\d{2}$/', $valor) ? Carbon::createFromFormat('!Y-m', $valor) : today()->startOfMonth();
        } catch (\Throwable) {
            return today()->startOfMonth();
        }
    }
}

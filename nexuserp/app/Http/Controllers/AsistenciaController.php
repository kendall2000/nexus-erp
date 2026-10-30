<?php

namespace App\Http\Controllers;

use App\Models\RRHH\Asistencia;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\SolicitudAusencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Asistencia diaria y solicitudes de ausencia. Permisos: asistencia.ver / editar (registrar asistencia y
 * solicitudes) / aprobar (aprobar o rechazar solicitudes). Nómina descuenta las ausencias y trae las horas extra.
 */
class AsistenciaController extends Controller
{
    /** Hora de entrada de la jornada: después de esta, «tarde». */
    public const HORA_ENTRADA = '08:00';

    public const ESTADOS = [
        'PRESENTE' => ['Presente', 'success'], 'TARDE' => ['Tarde', 'warning'], 'AUSENTE' => ['Ausente', 'danger'],
        'PERMISO' => ['Permiso', 'info'], 'VACACIONES' => ['Vacaciones', 'primary'], 'LICENCIA' => ['Licencia', 'secondary'],
    ];

    public const TIPOS_AUSENCIA = [
        'VACACIONES' => 'Vacaciones', 'PERMISO_CON_GOCE' => 'Permiso con goce', 'PERMISO_SIN_GOCE' => 'Permiso sin goce',
        'LICENCIA' => 'Licencia', 'INCAPACIDAD' => 'Incapacidad (IGSS)', 'DUELO' => 'Duelo',
    ];

    /** Cómo queda marcada la asistencia de los días de una ausencia aprobada. */
    private const ESTADO_POR_AUSENCIA = [
        'VACACIONES' => 'VACACIONES', 'PERMISO_CON_GOCE' => 'PERMISO', 'PERMISO_SIN_GOCE' => 'PERMISO',
        'LICENCIA' => 'LICENCIA', 'INCAPACIDAD' => 'LICENCIA', 'DUELO' => 'PERMISO',
    ];

    public function index(Request $request): View
    {
        $fecha = $this->fecha($request->query('fecha')) ?? today();
        $empleados = $this->empleados($request, $fecha)->get();
        $registros = Asistencia::query()->where('id_empresa', $request->user()->id_empresa)->whereDate('fecha', $fecha)->get()->keyBy('id_empleado');
        $mes = $fecha->copy()->startOfMonth();

        return view('asistencia.index', [
            'fecha' => $fecha,
            'empleados' => $empleados,
            'registros' => $registros,
            'resumen' => Asistencia::query()->where('id_empresa', $request->user()->id_empresa)->whereBetween('fecha', [$mes, $mes->copy()->endOfMonth()])
                ->selectRaw("id_empleado, SUM(CASE WHEN estado IN ('PRESENTE','TARDE') THEN 1 ELSE 0 END) as presentes, SUM(CASE WHEN estado = 'TARDE' THEN 1 ELSE 0 END) as tardes,
                    SUM(CASE WHEN estado = 'AUSENTE' THEN 1 ELSE 0 END) as ausencias, SUM(minutos_tarde) as minutos, SUM(horas_extra) as extras")
                ->groupBy('id_empleado')->get()->keyBy('id_empleado'),
            'solicitudes' => SolicitudAusencia::query()->whereIn('id_empleado', $this->todosLosEmpleados($request))->with(['empleado', 'aprobadoPor'])
                ->where(fn ($q) => $q->where('estado', 'PENDIENTE')->orWhere('fecha_fin', '>=', today()->subDays(30)))
                ->orderByRaw("CASE estado WHEN 'PENDIENTE' THEN 0 ELSE 1 END")->orderByDesc('fecha_inicio')->get(),
            'estados' => self::ESTADOS,
            'tipos' => self::TIPOS_AUSENCIA,
            'horaEntrada' => self::HORA_ENTRADA,
        ]);
    }

    /** Guarda la asistencia del día de todos los empleados del formulario. */
    public function guardar(Request $request): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $datos = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'asistencia' => ['required', 'array'],
            'asistencia.*.estado' => ['nullable', Rule::in(array_keys(self::ESTADOS))],
            'asistencia.*.entrada' => ['nullable', 'date_format:H:i'],
            'asistencia.*.salida' => ['nullable', 'date_format:H:i'],
            'asistencia.*.horas_extra' => ['nullable', 'numeric', 'min:0', 'max:12'],
            'asistencia.*.observaciones' => ['nullable', 'string', 'max:300'],
        ], ['fecha.before_or_equal' => 'No se registra asistencia de días futuros.']);
        $fecha = Carbon::parse($datos['fecha'])->startOfDay();
        $validos = $this->empleados($request, $fecha)->pluck('id_empleado')->all();

        $guardados = 0;
        DB::transaction(function () use ($datos, $fecha, $validos, $idEmpresa, &$guardados) {
            foreach ($datos['asistencia'] as $idEmpleado => $a) {
                if (empty($a['estado']) || ! in_array((int) $idEmpleado, $validos, true)) {
                    continue;
                }
                $entrada = ! empty($a['entrada']) ? $fecha->copy()->setTimeFromTimeString($a['entrada']) : null;
                $salida = ! empty($a['salida']) ? $fecha->copy()->setTimeFromTimeString($a['salida']) : null;
                $estado = $a['estado'];
                $minutos = 0;
                if (in_array($estado, ['PRESENTE', 'TARDE'], true) && $entrada) {
                    $minutos = max(0, (int) $fecha->copy()->setTimeFromTimeString(self::HORA_ENTRADA)->diffInMinutes($entrada, false));
                    $estado = $minutos > 0 ? 'TARDE' : 'PRESENTE';
                }
                self::guardarDia((int) $idEmpleado, $fecha, [
                    'id_empresa' => $idEmpresa, 'hora_entrada' => $entrada, 'hora_salida' => $salida && $entrada && $salida->lt($entrada) ? null : $salida,
                    'estado' => $estado, 'minutos_tarde' => $minutos, 'horas_extra' => in_array($estado, ['PRESENTE', 'TARDE'], true) ? (float) ($a['horas_extra'] ?? 0) : 0,
                    'observaciones' => $a['observaciones'] ?? null, 'tipo' => 'NORMAL', 'registrado_por' => 'MANUAL',
                ]);
                $guardados++;
            }
        });

        return redirect()->route('asistencia.index', ['fecha' => $fecha->toDateString()])->with('status', "Asistencia del {$fecha->format('d/m/Y')} guardada ({$guardados} empleados).");
    }

    public function solicitar(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('solicitud', [
            'id_empleado' => ['required', 'integer', Rule::in($this->todosLosEmpleados($request))],
            'tipo' => ['required', Rule::in(array_keys(self::TIPOS_AUSENCIA))],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ], ['id_empleado.in' => 'El empleado no es válido.']);
        $dias = self::diasHabiles(Carbon::parse($datos['fecha_inicio']), Carbon::parse($datos['fecha_fin']));
        if ($dias === 0) {
            return back()->withErrors(['fecha_inicio' => 'El rango no incluye días hábiles.'], 'solicitud')->withInput();
        }
        $cruce = SolicitudAusencia::query()->where('id_empleado', $datos['id_empleado'])->whereIn('estado', ['PENDIENTE', 'APROBADO'])
            ->where('fecha_inicio', '<=', $datos['fecha_fin'])->where('fecha_fin', '>=', $datos['fecha_inicio'])->exists();
        if ($cruce) {
            return back()->withErrors(['fecha_inicio' => 'Ya hay una solicitud pendiente o aprobada en esas fechas.'], 'solicitud')->withInput();
        }
        SolicitudAusencia::create($datos + ['dias_habiles' => min(255, $dias), 'estado' => 'PENDIENTE']);

        return back()->with('status', "Solicitud registrada ({$dias} días hábiles). Queda pendiente de aprobación.");
    }

    /** Aprobar marca los días hábiles en la asistencia; rechazar solo cambia el estado. */
    public function resolver(Request $request, int $solicitud, string $decision): RedirectResponse
    {
        abort_unless(in_array($decision, ['aprobar', 'rechazar'], true), 404);
        $datos = $request->validate(['observaciones' => [$decision === 'rechazar' ? 'required' : 'nullable', 'string', 'max:500']],
            ['observaciones.required' => 'Indica por qué se rechaza.']);

        return DB::transaction(function () use ($request, $solicitud, $decision, $datos) {
            $s = SolicitudAusencia::query()->whereIn('id_empleado', $this->todosLosEmpleados($request))->lockForUpdate()->findOrFail($solicitud);
            if ($s->estado !== 'PENDIENTE') {
                return back()->withErrors(['solicitud' => 'La solicitud ya fue resuelta.']);
            }
            $s->update(['estado' => $decision === 'aprobar' ? 'APROBADO' : 'RECHAZADO', 'aprobado_por' => $request->user()->id_usuario,
                'fecha_aprobacion' => now(), 'observaciones' => $datos['observaciones'] ?? null]);
            if ($decision === 'aprobar') {
                foreach (CarbonPeriod::create($s->fecha_inicio, $s->fecha_fin) as $dia) {
                    if (! $dia->isWeekend()) {
                        self::guardarDia($s->id_empleado, $dia, [
                            'id_empresa' => $request->user()->id_empresa, 'estado' => self::ESTADO_POR_AUSENCIA[$s->tipo], 'hora_entrada' => null, 'hora_salida' => null,
                            'minutos_tarde' => 0, 'horas_extra' => 0, 'tipo' => 'NORMAL', 'registrado_por' => 'SISTEMA',
                            'observaciones' => self::TIPOS_AUSENCIA[$s->tipo].' (solicitud #'.$s->id_solicitud.')',
                        ]);
                    }
                }
            }

            return back()->with('status', 'Solicitud '.($decision === 'aprobar' ? 'aprobada: los días quedaron marcados en la asistencia.' : 'rechazada.'));
        });
    }

    /** Crea o actualiza el registro del empleado en ese día (uno por día). */
    private static function guardarDia(int $idEmpleado, Carbon $dia, array $datos): void
    {
        $registro = Asistencia::query()->where('id_empleado', $idEmpleado)->whereDate('fecha', $dia)->first()
            ?? new Asistencia(['id_empleado' => $idEmpleado, 'fecha' => $dia->toDateString()]);
        $registro->fill($datos)->save();
    }

    /** Días de lunes a viernes entre dos fechas (inclusive). */
    public static function diasHabiles(Carbon $desde, Carbon $hasta): int
    {
        return collect(CarbonPeriod::create($desde, $hasta))->reject(fn ($d) => $d->isWeekend())->count();
    }

    // ── Privados ─────────────────────────────────────────────────────────

    /** Empleados que trabajaban en esa fecha (ingresados y sin baja anterior). */
    private function empleados(Request $request, Carbon $fecha): Builder
    {
        return Empleado::query()->where('id_empresa', $request->user()->id_empresa)->whereDate('fecha_ingreso', '<=', $fecha)
            ->where(fn ($q) => $q->where('estado', '!=', 'BAJA')->orWhereDate('fecha_baja', '>=', $fecha))
            ->orderBy('primer_nombre')->orderBy('primer_apellido');
    }

    /** @return list<int> */
    private function todosLosEmpleados(Request $request): array
    {
        return Empleado::query()->where('id_empresa', $request->user()->id_empresa)->pluck('id_empleado')->map(fn ($id) => (int) $id)->all();
    }

    private function fecha(mixed $valor): ?Carbon
    {
        try {
            return is_string($valor) && $valor !== '' ? Carbon::parse($valor)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

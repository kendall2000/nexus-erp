<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Clientes\ContratoServicio;
use App\Models\CRM\CategoriaTicket;
use App\Models\CRM\EscalacionTicket;
use App\Models\CRM\EvaluacionSatisfaccion;
use App\Models\CRM\SlaConfig;
use App\Models\CRM\Ticket;
use App\Models\CRM\TicketComentario;
use App\Models\RRHH\Empleado;
use App\Support\Sla;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Tickets de servicio al cliente con SLA. Permisos: tickets.ver / crear / editar (responder y cambiar
 * estado) / asignar / cerrar / reabrir. El agente asignado es un empleado; quien escribe queda con su usuario.
 *
 * Flujo: ABIERTO → EN_PROGRESO (primera respuesta pública) ⇄ PENDIENTE_CLIENTE → RESUELTO → CERRADO;
 * un ticket resuelto o cerrado se reabre (REABIERTO).
 */
class TicketController extends Controller
{
    public const ESTADOS = [
        'ABIERTO' => ['Abierto', 'primary'], 'EN_PROGRESO' => ['En progreso', 'info'], 'PENDIENTE_CLIENTE' => ['Esperando al cliente', 'warning'],
        'RESUELTO' => ['Resuelto', 'success'], 'CERRADO' => ['Cerrado', 'dark'], 'REABIERTO' => ['Reabierto', 'danger'],
    ];

    public const PRIORIDADES = ['BAJA' => ['Baja', 'secondary'], 'MEDIA' => ['Media', 'info'], 'ALTA' => ['Alta', 'warning'], 'CRITICA' => ['Crítica', 'danger']];

    public const TIPOS = ['INCIDENTE' => 'Incidente', 'SOLICITUD' => 'Solicitud', 'QUEJA' => 'Queja', 'CONSULTA' => 'Consulta', 'MEJORA' => 'Mejora'];

    public const CANALES = ['EMAIL' => 'Correo', 'TELEFONO' => 'Teléfono', 'WHATSAPP' => 'WhatsApp', 'WEB' => 'Web', 'PRESENCIAL' => 'Presencial', 'APP' => 'App'];

    /** Estados en los que el ticket sigue abierto (cuenta el SLA). */
    public const ACTIVOS = ['ABIERTO', 'EN_PROGRESO', 'PENDIENTE_CLIENTE', 'REABIERTO'];

    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;
        $activos = $this->deMiEmpresa($request)->whereIn('estado', self::ACTIVOS);

        return view('tickets.index', [
            'tickets' => $this->filtrados($request)->with(['cliente', 'categoria', 'asignadoA'])
                ->orderByRaw("CASE prioridad WHEN 'CRITICA' THEN 1 WHEN 'ALTA' THEN 2 WHEN 'MEDIA' THEN 3 ELSE 4 END")
                ->orderBy('fecha_limite_resolucion')->orderByDesc('id_ticket')->paginate(25)->withQueryString(),
            'resumen' => [
                'abiertos' => (clone $activos)->count(),
                'sinAsignar' => (clone $activos)->whereNull('id_asignado_a')->count(),
                'vencidos' => (clone $activos)->whereNotNull('fecha_limite_resolucion')->where('fecha_limite_resolucion', '<', now())->count(),
            ],
            'clientes' => Cliente::query()->where('id_empresa', $idEmpresa)->orderBy('razon_social')->get(['id_cliente', 'razon_social']),
            'agentes' => $this->agentes($request)->get(),
            'estados' => self::ESTADOS,
            'prioridades' => self::PRIORIDADES,
            'filtros' => $request->only(['buscar', 'estado', 'prioridad', 'cliente', 'asignado', 'vencidos']),
        ]);
    }

    public function show(Request $request, int $ticket): View
    {
        $t = $this->deMiEmpresa($request)->with(['cliente', 'contrato', 'categoria', 'asignadoA', 'sla'])->findOrFail($ticket);

        return view('tickets.show', [
            't' => $t,
            'comentarios' => $t->comentarios()->with(['usuario', 'autor'])->orderBy('created_at')->orderBy('id_comentario')->get(),
            'escalaciones' => $t->escalaciones()->with(['escaladoA', 'usuario'])->orderBy('created_at')->get(),
            'agentes' => $this->agentes($request)->get(),
            'estados' => self::ESTADOS,
            'prioridades' => self::PRIORIDADES,
            'tipos' => self::TIPOS,
            'canales' => self::CANALES,
        ]);
    }

    public function create(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('tickets.form', [
            'clientes' => Cliente::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('razon_social')->get(['id_cliente', 'razon_social']),
            'contratos' => ContratoServicio::query()->where('id_empresa', $idEmpresa)->whereIn('estado', ['VIGENTE', 'SUSPENDIDO'])->orderBy('numero_contrato')->get(['id_contrato', 'id_cliente', 'numero_contrato', 'nombre_proyecto']),
            'categorias' => CategoriaTicket::query()->where('id_empresa', $idEmpresa)->where('activo', true)->orderBy('nombre')->get(),
            'planes' => $this->planesSla($request),
            'agentes' => $this->agentes($request)->get(),
            'prioridades' => self::PRIORIDADES,
            'tipos' => self::TIPOS,
            'canales' => self::CANALES,
            'elegido' => $request->only(['cliente', 'contrato']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $datos = $request->validate([
            'id_cliente' => ['required', 'integer', Rule::exists('cliente', 'id_cliente')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'id_contrato' => ['nullable', 'integer', Rule::exists('contrato_servicio', 'id_contrato')->where('id_empresa', $idEmpresa)->where('id_cliente', $request->integer('id_cliente'))],
            'id_categoria' => ['nullable', 'integer', Rule::exists('categoria_ticket', 'id_categoria')->where('id_empresa', $idEmpresa)->where('activo', true)],
            'id_asignado_a' => ['nullable', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')->whereNotIn('estado', ['BAJA', 'INACTIVO'])],
            'asunto' => ['required', 'string', 'max:300'],
            'descripcion' => ['required', 'string', 'max:10000'],
            'canal_origen' => ['required', Rule::in(array_keys(self::CANALES))],
            'prioridad' => ['required', Rule::in(array_keys(self::PRIORIDADES))],
            'tipo' => ['required', Rule::in(array_keys(self::TIPOS))],
            'plan_sla' => ['nullable', 'string', 'max:100'],
        ], [
            'id_contrato.exists' => 'El contrato no es de este cliente.',
            'id_asignado_a.exists' => 'El agente no es un empleado activo.',
        ]);
        if (! empty($datos['id_asignado_a']) && ! $request->user()->puede('tickets.asignar')) {
            throw ValidationException::withMessages(['id_asignado_a' => 'No tienes permiso para asignar tickets.']);
        }

        $t = DB::transaction(function () use ($request, $datos, $idEmpresa) {
            $apertura = now();
            $sla = ! empty($datos['plan_sla'])
                ? SlaConfig::query()->where('id_empresa', $idEmpresa)->where('activo', true)->where('nombre', $datos['plan_sla'])->where('prioridad', $datos['prioridad'])->first()
                : null;

            return Ticket::create(collect($datos)->except('plan_sla')->all() + [
                'id_empresa' => $idEmpresa,
                'numero_ticket' => $this->siguienteNumero($request),
                'estado' => 'ABIERTO',
                'fecha_apertura' => $apertura,
                'id_sla' => $sla?->id_sla,
                'sla_primera_respuesta_hrs' => $sla?->tiempo_primera_respuesta_hrs,
                'sla_resolucion_hrs' => $sla?->tiempo_resolucion_hrs,
                'fecha_limite_respuesta' => $sla ? Sla::vence($apertura, (int) $sla->tiempo_primera_respuesta_hrs, (bool) $sla->aplica_fines_semana) : null,
                'fecha_limite_resolucion' => $sla ? Sla::vence($apertura, (int) $sla->tiempo_resolucion_hrs, (bool) $sla->aplica_fines_semana) : null,
                'created_by' => $request->user()->id_usuario,
                'updated_by' => $request->user()->id_usuario,
            ]);
        });

        return redirect()->route('tickets.show', $t->id_ticket)->with('status', "Ticket {$t->numero_ticket} abierto.");
    }

    /** Comentario (público o nota interna). La primera respuesta pública marca el SLA de respuesta. */
    public function responder(Request $request, int $ticket): RedirectResponse
    {
        $datos = $request->validate([
            'contenido' => ['required', 'string', 'max:10000'],
            'es_nota_interna' => ['nullable', 'boolean'],
            'estado' => ['nullable', Rule::in(['EN_PROGRESO', 'PENDIENTE_CLIENTE', 'RESUELTO'])],
        ]);

        return DB::transaction(function () use ($request, $ticket, $datos) {
            $t = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($ticket);
            if (! in_array($t->estado, self::ACTIVOS, true)) {
                return back()->withErrors(['ticket' => 'El ticket no está abierto: reábrelo para responder.']);
            }
            $interna = (bool) ($datos['es_nota_interna'] ?? false);
            TicketComentario::create(['id_ticket' => $t->id_ticket, 'id_usuario' => $request->user()->id_usuario, 'es_nota_interna' => $interna, 'contenido' => $datos['contenido']]);

            $cambios = ['updated_by' => $request->user()->id_usuario];
            if (! $interna && ! $t->fecha_primera_respuesta) {
                $cambios['fecha_primera_respuesta'] = now();
            }
            $nuevo = $datos['estado'] ?? (! $interna && in_array($t->estado, ['ABIERTO', 'REABIERTO'], true) ? 'EN_PROGRESO' : null);
            if ($nuevo) {
                $cambios['estado'] = $nuevo;
                if ($nuevo === 'RESUELTO') {
                    $cambios['fecha_resolucion'] = now();
                }
            }
            $t->update($cambios);

            return back()->with('status', $interna ? 'Nota interna agregada.' : 'Respuesta registrada'.($nuevo ? ': '.strtolower(self::ESTADOS[$nuevo][0]).'.' : '.'));
        });
    }

    /** Asignar o reasignar; si ya tenía agente, queda registrada la escalación con su motivo. */
    public function asignar(Request $request, int $ticket): RedirectResponse
    {
        $t = $this->deMiEmpresa($request)->findOrFail($ticket);
        $datos = $request->validate([
            'id_asignado_a' => ['required', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $t->id_empresa)->whereNull('deleted_at')->whereNotIn('estado', ['BAJA', 'INACTIVO'])],
            'motivo' => [Rule::requiredIf((bool) $t->id_asignado_a), 'nullable', 'string', 'min:5', 'max:500'],
        ], ['motivo.required' => 'Indica por qué se reasigna.', 'id_asignado_a.exists' => 'El agente no es un empleado activo.']);
        if ((int) $datos['id_asignado_a'] === (int) $t->id_asignado_a) {
            return back()->withErrors(['id_asignado_a' => 'Ya está asignado a ese agente.']);
        }
        if (! in_array($t->estado, self::ACTIVOS, true)) {
            return back()->withErrors(['ticket' => 'El ticket no está abierto.']);
        }

        DB::transaction(function () use ($request, $t, $datos) {
            if ($t->id_asignado_a) {
                EscalacionTicket::create([
                    'id_ticket' => $t->id_ticket, 'escalado_por' => $t->id_asignado_a, 'escalado_a' => $datos['id_asignado_a'],
                    'id_usuario' => $request->user()->id_usuario, 'motivo' => $datos['motivo'], 'nivel' => $t->escalaciones()->count() + 1,
                ]);
            }
            $t->update(['id_asignado_a' => $datos['id_asignado_a'], 'updated_by' => $request->user()->id_usuario]);
        });

        return back()->with('status', 'Ticket asignado a '.Empleado::find($datos['id_asignado_a'])?->nombre_completo.'.');
    }

    /** Cierre con calificación opcional del cliente (queda también como evaluación CSAT). */
    public function cerrar(Request $request, int $ticket): RedirectResponse
    {
        $datos = $request->validate([
            'calificacion_cliente' => ['nullable', 'integer', 'between:1,5'],
            'comentario_calificacion' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($request, $ticket, $datos) {
            $t = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($ticket);
            if ($t->estado === 'CERRADO') {
                return back()->withErrors(['ticket' => 'El ticket ya está cerrado.']);
            }
            $t->update($datos + ['estado' => 'CERRADO', 'fecha_cierre' => now(), 'fecha_resolucion' => $t->fecha_resolucion ?? now(), 'updated_by' => $request->user()->id_usuario]);
            if (! empty($datos['calificacion_cliente'])) {
                EvaluacionSatisfaccion::create([
                    'id_empresa' => $t->id_empresa, 'id_cliente' => $t->id_cliente, 'id_contrato' => $t->id_contrato, 'id_ticket' => $t->id_ticket,
                    'tipo' => 'CSAT', 'puntuacion' => $datos['calificacion_cliente'], 'comentarios' => $datos['comentario_calificacion'] ?? null,
                    'canal' => in_array($t->canal_origen, ['EMAIL', 'WHATSAPP', 'TELEFONO', 'APP', 'WEB'], true) ? $t->canal_origen : 'TELEFONO', 'fecha_respuesta' => now(),
                ]);
            }

            return back()->with('status', "Ticket {$t->numero_ticket} cerrado.");
        });
    }

    public function reabrir(Request $request, int $ticket): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:2000']], ['motivo.required' => 'Indica por qué se reabre.']);

        return DB::transaction(function () use ($request, $ticket, $datos) {
            $t = $this->deMiEmpresa($request)->lockForUpdate()->findOrFail($ticket);
            if (! in_array($t->estado, ['RESUELTO', 'CERRADO'], true)) {
                return back()->withErrors(['ticket' => 'Solo se reabren tickets resueltos o cerrados.']);
            }
            TicketComentario::create(['id_ticket' => $t->id_ticket, 'id_usuario' => $request->user()->id_usuario, 'es_nota_interna' => true, 'contenido' => 'Reabierto: '.$datos['motivo']]);
            $t->update(['estado' => 'REABIERTO', 'fecha_resolucion' => null, 'fecha_cierre' => null, 'updated_by' => $request->user()->id_usuario]);

            return back()->with('status', "Ticket {$t->numero_ticket} reabierto.");
        });
    }

    // ── Privados ─────────────────────────────────────────────────────────

    private function filtrados(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = (string) $request->query('estado');

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q->where('numero_ticket', 'like', "%{$buscar}%")->orWhere('asunto', 'like', "%{$buscar}%")
                ->orWhereHas('cliente', fn ($q) => $q->where('razon_social', 'like', "%{$buscar}%"))))
            // Por defecto solo los abiertos; «todos» muestra también los cerrados.
            ->when(array_key_exists($estado, self::ESTADOS), fn ($q) => $q->where('estado', $estado), fn ($q) => $estado === 'todos' ? $q : $q->whereIn('estado', self::ACTIVOS))
            ->when(array_key_exists((string) $request->query('prioridad'), self::PRIORIDADES), fn ($q) => $q->where('prioridad', $request->query('prioridad')))
            ->when($request->filled('cliente'), fn ($q) => $q->where('id_cliente', $request->integer('cliente')))
            ->when($request->query('asignado') === 'ninguno', fn ($q) => $q->whereNull('id_asignado_a'))
            ->when(ctype_digit((string) $request->query('asignado')), fn ($q) => $q->where('id_asignado_a', $request->integer('asignado')))
            ->when($request->boolean('vencidos'), fn ($q) => $q->whereIn('estado', self::ACTIVOS)->whereNotNull('fecha_limite_resolucion')->where('fecha_limite_resolucion', '<', now()));
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return Ticket::query()->where('id_empresa', $request->user()->id_empresa);
    }

    private function agentes(Request $request): Builder
    {
        return Empleado::query()->where('id_empresa', $request->user()->id_empresa)->whereNotIn('estado', ['BAJA', 'INACTIVO'])->orderBy('primer_nombre');
    }

    /** Nombres de los planes de SLA activos (cada plan tiene una fila por prioridad). */
    private function planesSla(Request $request)
    {
        return SlaConfig::query()->where('id_empresa', $request->user()->id_empresa)->where('activo', true)->orderBy('nombre')->distinct()->pluck('nombre');
    }

    /** TK-AAAA-00001, consecutivo por empresa y año. */
    private function siguienteNumero(Request $request): string
    {
        $prefijo = 'TK-'.now()->year.'-';
        $ultimo = $this->deMiEmpresa($request)->where('numero_ticket', 'like', $prefijo.'%')->pluck('numero_ticket')
            ->map(fn ($n) => (int) substr($n, strlen($prefijo)))->max() ?? 0;

        return $prefijo.str_pad((string) ($ultimo + 1), 5, '0', STR_PAD_LEFT);
    }
}

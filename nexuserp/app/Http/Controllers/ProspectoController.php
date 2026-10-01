<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Clientes\ContactoCliente;
use App\Models\Clientes\Industria;
use App\Models\Core\Pais;
use App\Models\CRM\FuenteLead;
use App\Models\CRM\Oportunidad;
use App\Models\CRM\Prospecto;
use App\Models\CRM\SeguimientoProspecto;
use App\Models\RRHH\Empleado;
use App\Support\Crm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Prospectos (leads) y su seguimiento comercial. Permisos: prospectos.ver / crear / editar / eliminar.
 * Un prospecto calificado se convierte en cliente (y opcionalmente en una oportunidad).
 */
class ProspectoController extends Controller
{
    public const ESTADOS = [
        'NUEVO' => ['Nuevo', 'secondary'], 'EN_CONTACTO' => ['En contacto', 'info'], 'CALIFICADO' => ['Calificado', 'primary'],
        'NO_CALIFICADO' => ['No calificado', 'warning'], 'CONVERTIDO' => ['Convertido en cliente', 'success'], 'DESCARTADO' => ['Descartado', 'dark'],
    ];

    public const TEMPERATURAS = ['FRIO' => ['Frío', 'info'], 'TIBIO' => ['Tibio', 'warning'], 'CALIENTE' => ['Caliente', 'danger']];

    public const TIPOS_SEGUIMIENTO = ['LLAMADA' => 'Llamada', 'EMAIL' => 'Correo', 'WHATSAPP' => 'WhatsApp', 'REUNION' => 'Reunión', 'VISITA' => 'Visita', 'DEMO' => 'Demostración', 'OTRO' => 'Otro'];

    public const RESULTADOS = [
        'SIN_CONTACTO' => 'Sin contacto', 'CONTACTADO' => 'Contactado', 'INTERESADO' => 'Interesado',
        'SOLICITO_PROPUESTA' => 'Solicitó propuesta', 'NO_INTERESADO' => 'No interesado', 'REAGENDAR' => 'Reagendar',
    ];

    /** Estados abiertos (se trabajan). */
    private const ABIERTOS = ['NUEVO', 'EN_CONTACTO', 'CALIFICADO', 'NO_CALIFICADO'];

    public function index(Request $request): View
    {
        $estado = (string) $request->query('estado');
        $buscar = trim((string) $request->query('buscar'));

        return view('prospectos.index', [
            'prospectos' => $this->deMiEmpresa($request)->with(['fuente', 'asignadoA', 'ultimoSeguimiento'])
                ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q->where('nombre_empresa', 'like', "%{$buscar}%")->orWhere('nombre_contacto', 'like', "%{$buscar}%")->orWhere('email_contacto', 'like', "%{$buscar}%")))
                ->when(array_key_exists($estado, self::ESTADOS), fn ($q) => $q->where('estado', $estado), fn ($q) => $estado === 'todos' ? $q : $q->whereIn('estado', self::ABIERTOS))
                ->when(array_key_exists((string) $request->query('temperatura'), self::TEMPERATURAS), fn ($q) => $q->where('temperatura', $request->query('temperatura')))
                ->when($request->filled('fuente'), fn ($q) => $q->where('id_fuente', $request->integer('fuente')))
                ->when($request->filled('asignado'), fn ($q) => $q->where('id_asignado_a', $request->integer('asignado')))
                ->orderByRaw("CASE temperatura WHEN 'CALIENTE' THEN 1 WHEN 'TIBIO' THEN 2 ELSE 3 END")->orderByDesc('updated_at')->paginate(25)->withQueryString(),
            'fuentes' => FuenteLead::query()->where('activo', true)->orderBy('nombre')->get(),
            'vendedores' => $this->vendedores($request)->get(),
            'estados' => self::ESTADOS,
            'temperaturas' => self::TEMPERATURAS,
            'filtros' => $request->only(['buscar', 'estado', 'temperatura', 'fuente', 'asignado']),
        ]);
    }

    public function show(Request $request, int $prospecto): View
    {
        $p = $this->deMiEmpresa($request)->with(['fuente', 'asignadoA', 'pais', 'industria', 'clienteGenerado', 'oportunidades.etapa'])->findOrFail($prospecto);

        return view('prospectos.show', [
            'p' => $p,
            'seguimientos' => $p->seguimientos()->with('realizadoPor')->orderByDesc('fecha_hora')->get(),
            'vendedores' => $this->vendedores($request)->get(),
            'etapas' => Crm::etapas($p->id_empresa)->where('es_ganada', false)->where('es_perdida', false),
            'estados' => self::ESTADOS,
            'temperaturas' => self::TEMPERATURAS,
            'tipos' => self::TIPOS_SEGUIMIENTO,
            'resultados' => self::RESULTADOS,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new Prospecto(['temperatura' => 'FRIO', 'moneda' => 'GTQ', 'estado' => 'NUEVO']));
    }

    public function store(Request $request): RedirectResponse
    {
        $p = Prospecto::create($this->validar($request) + [
            'id_empresa' => $request->user()->id_empresa, 'estado' => 'NUEVO',
            'created_by' => $request->user()->id_usuario, 'updated_by' => $request->user()->id_usuario,
        ]);

        return redirect()->route('prospectos.show', $p->id_prospecto)->with('status', "Prospecto «{$p->nombre_empresa}» registrado.");
    }

    public function edit(Request $request, int $prospecto): View|RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($prospecto);
        if (! in_array($p->estado, self::ABIERTOS, true)) {
            return redirect()->route('prospectos.show', $p->id_prospecto)->withErrors(['prospecto' => 'Un prospecto convertido o descartado ya no se edita.']);
        }

        return $this->formulario($request, $p);
    }

    public function update(Request $request, int $prospecto): RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($prospecto);
        if (! in_array($p->estado, self::ABIERTOS, true)) {
            return redirect()->route('prospectos.show', $p->id_prospecto)->withErrors(['prospecto' => 'Un prospecto convertido o descartado ya no se edita.']);
        }
        $datos = $this->validar($request);
        $request->validate(['estado' => ['required', Rule::in(self::ABIERTOS)]]);
        $p->update($datos + ['estado' => $request->input('estado'), 'updated_by' => $request->user()->id_usuario]);

        return redirect()->route('prospectos.show', $p->id_prospecto)->with('status', 'Prospecto actualizado.');
    }

    /** Registra una llamada, reunión, etc. El primer contacto pasa el prospecto a «en contacto». */
    public function seguimiento(Request $request, int $prospecto): RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($prospecto);
        $datos = $request->validateWithBag('seguimiento', [
            'id_realizado_por' => ['required', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $p->id_empresa)->whereNull('deleted_at')],
            'tipo' => ['required', Rule::in(array_keys(self::TIPOS_SEGUIMIENTO))],
            'fecha_hora' => ['required', 'date', 'before_or_equal:now'],
            'duracion_min' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'resultado' => ['required', Rule::in(array_keys(self::RESULTADOS))],
            'resumen' => ['required', 'string', 'max:5000'],
            'proxima_accion' => ['nullable', 'string', 'max:300'],
            'fecha_proxima_accion' => ['nullable', 'date', 'after_or_equal:today'],
        ], ['fecha_hora.before_or_equal' => 'El seguimiento ya debe haber ocurrido.', 'id_realizado_por.required' => 'Indica qué vendedor lo realizó.']);
        if (! in_array($p->estado, self::ABIERTOS, true)) {
            return back()->withErrors(['prospecto' => 'El prospecto ya está cerrado.']);
        }

        DB::transaction(function () use ($p, $datos, $request) {
            SeguimientoProspecto::create($datos + ['id_prospecto' => $p->id_prospecto]);
            $cambios = ['updated_by' => $request->user()->id_usuario];
            if ($p->estado === 'NUEVO' && $datos['resultado'] !== 'SIN_CONTACTO') {
                $cambios['estado'] = 'EN_CONTACTO';
            }
            if (in_array($datos['resultado'], ['INTERESADO', 'SOLICITO_PROPUESTA'], true) && $p->temperatura === 'FRIO') {
                $cambios['temperatura'] = 'TIBIO';
            }
            $p->update($cambios);
        });

        return back()->with('status', 'Seguimiento registrado.');
    }

    public function descartar(Request $request, int $prospecto): RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($prospecto);
        $datos = $request->validate(['motivo_descarte' => ['required', 'string', 'min:5', 'max:300']], ['motivo_descarte.required' => 'Indica por qué se descarta.']);
        if (! in_array($p->estado, self::ABIERTOS, true)) {
            return back()->withErrors(['prospecto' => 'El prospecto ya está cerrado.']);
        }
        $p->update($datos + ['estado' => 'DESCARTADO', 'updated_by' => $request->user()->id_usuario]);

        return back()->with('status', 'Prospecto descartado.');
    }

    /** Crea el cliente con su contacto principal; con «etapa», también una oportunidad. */
    public function convertir(Request $request, int $prospecto): RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($prospecto);
        $etapas = Crm::etapas($p->id_empresa)->where('es_ganada', false)->where('es_perdida', false);
        $datos = $request->validate([
            'crear_oportunidad' => ['nullable', 'boolean'],
            'id_etapa' => ['nullable', 'required_if:crear_oportunidad,1', 'integer', Rule::in($etapas->pluck('id_etapa')->all())],
            'id_responsable' => ['nullable', 'required_if:crear_oportunidad,1', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $p->id_empresa)->whereNull('deleted_at')],
        ], ['id_responsable.required_if' => 'La oportunidad necesita un vendedor responsable.']);
        if (! in_array($p->estado, self::ABIERTOS, true)) {
            return back()->withErrors(['prospecto' => 'El prospecto ya está cerrado.']);
        }
        $existente = Cliente::query()->where('id_empresa', $p->id_empresa)->where('razon_social', $p->nombre_empresa)->first();

        $cliente = DB::transaction(function () use ($p, $datos, $request, $existente, $etapas) {
            $cliente = $existente ?? Cliente::create([
                'id_empresa' => $p->id_empresa, 'id_pais' => $p->id_pais ?? Pais::query()->where('codigo_iso2', 'GT')->value('id_pais'), 'id_industria' => $p->id_industria,
                'razon_social' => $p->nombre_empresa, 'tipo_persona' => 'JURIDICA', 'email_principal' => $p->email_contacto, 'telefono_principal' => $p->telefono_contacto,
                'sitio_web' => $p->sitio_web, 'moneda_facturacion' => $p->moneda ?: 'GTQ', 'dias_credito' => 30, 'activo' => true,
                'created_by' => $request->user()->id_usuario, 'updated_by' => $request->user()->id_usuario,
            ]);
            ContactoCliente::create(['id_cliente' => $cliente->id_cliente, 'nombre' => $p->nombre_contacto, 'cargo' => $p->cargo_contacto, 'email' => $p->email_contacto,
                'telefono' => $p->telefono_contacto, 'whatsapp' => $p->whatsapp_contacto, 'es_contacto_principal' => ! $cliente->contactos()->where('es_contacto_principal', true)->exists(),
                'recibe_facturas' => false, 'activo' => true]);
            $p->update(['estado' => 'CONVERTIDO', 'fecha_conversion' => now(), 'id_cliente_generado' => $cliente->id_cliente, 'updated_by' => $request->user()->id_usuario]);

            if (! empty($datos['crear_oportunidad'])) {
                $etapa = $etapas->firstWhere('id_etapa', (int) $datos['id_etapa']);
                $valor = (float) ($p->presupuesto_estimado ?? 0);
                Oportunidad::create([
                    'id_empresa' => $p->id_empresa, 'id_cliente' => $cliente->id_cliente, 'id_prospecto' => $p->id_prospecto, 'id_etapa' => $etapa->id_etapa,
                    'id_responsable' => $datos['id_responsable'], 'nombre' => 'Servicios para '.$p->nombre_empresa, 'descripcion' => $p->interes_servicio,
                    'valor_estimado' => $valor, 'moneda' => $p->moneda ?: 'GTQ', 'probabilidad' => (int) $etapa->probabilidad_cierre,
                    'valor_ponderado' => round($valor * (int) $etapa->probabilidad_cierre / 100, 4), 'created_by' => $request->user()->id_usuario,
                ]);
            }

            return $cliente;
        });

        return redirect()->route('clientes.show', $cliente->id_cliente)
            ->with('status', ($existente ? "El prospecto se unió al cliente existente «{$cliente->razon_social}»" : "Cliente «{$cliente->razon_social}» creado desde el prospecto")
                .(! empty($datos['crear_oportunidad']) ? ' con su oportunidad.' : '.'));
    }

    public function destroy(Request $request, int $prospecto): RedirectResponse
    {
        $p = $this->deMiEmpresa($request)->findOrFail($prospecto);
        if ($p->estado === 'CONVERTIDO' || $p->oportunidades()->exists()) {
            return back()->withErrors(['prospecto' => 'No se elimina un prospecto convertido o con oportunidades.']);
        }
        $p->delete(); // borrado lógico

        return redirect()->route('prospectos.index')->with('status', "Prospecto «{$p->nombre_empresa}» eliminado.");
    }

    // ── Privados ─────────────────────────────────────────────────────────

    private function formulario(Request $request, Prospecto $p): View
    {
        return view('prospectos.form', [
            'p' => $p,
            'fuentes' => FuenteLead::query()->where('activo', true)->orderBy('nombre')->get(),
            'vendedores' => $this->vendedores($request)->get(),
            'paises' => Pais::query()->where('activo', true)->orderBy('nombre')->get(['id_pais', 'nombre']),
            'industrias' => Industria::query()->orderBy('nombre')->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'temperaturas' => self::TEMPERATURAS,
            'estados' => collect(self::ESTADOS)->only(self::ABIERTOS)->all(),
        ]);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return Prospecto::query()->where('id_empresa', $request->user()->id_empresa);
    }

    private function vendedores(Request $request): Builder
    {
        return Empleado::query()->where('id_empresa', $request->user()->id_empresa)->whereNotIn('estado', ['BAJA', 'INACTIVO'])->orderBy('primer_nombre');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $web = trim((string) $request->input('sitio_web'));
        $request->merge(['sitio_web' => $web !== '' && ! preg_match('#^https?://#i', $web) ? 'https://'.$web : ($web ?: null)]);

        $datos = $request->validate([
            'nombre_empresa' => ['required', 'string', 'max:250'],
            'nombre_contacto' => ['required', 'string', 'max:200'],
            'cargo_contacto' => ['nullable', 'string', 'max:150'],
            'email_contacto' => ['required', 'email', 'max:150'],
            'telefono_contacto' => ['nullable', 'string', 'max:20'],
            'whatsapp_contacto' => ['nullable', 'string', 'max:20'],
            'sitio_web' => ['nullable', 'url:http,https', 'max:200'],
            'empleados_estimados' => ['nullable', 'string', 'max:20'],
            'id_fuente' => ['nullable', 'integer', Rule::exists('fuente_lead', 'id_fuente')->where('activo', true)],
            'id_asignado_a' => ['nullable', 'integer', Rule::exists('empleado', 'id_empleado')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'id_pais' => ['nullable', 'integer', 'exists:pais,id_pais'],
            'id_industria' => ['nullable', 'integer', 'exists:industria,id_industria'],
            'temperatura' => ['required', Rule::in(array_keys(self::TEMPERATURAS))],
            'puntuacion_lead' => ['nullable', 'integer', 'between:0,100'],
            'interes_servicio' => ['nullable', 'string', 'max:5000'],
            'presupuesto_estimado' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'notas' => ['nullable', 'string', 'max:5000'],
        ], ['sitio_web.url' => 'El sitio web no es una dirección válida.', 'id_asignado_a.exists' => 'El vendedor no es válido.']);
        $datos['puntuacion_lead'] = (int) ($datos['puntuacion_lead'] ?? 0);

        return $datos;
    }
}

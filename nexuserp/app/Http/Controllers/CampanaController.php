<?php

namespace App\Http\Controllers;

use App\Models\Clientes\Cliente;
use App\Models\Core\LineaNegocio;
use App\Models\CRM\Campana;
use App\Models\CRM\CampanaContacto;
use App\Models\CRM\Prospecto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Campañas de marketing y sus contactos. Permisos: campanas.ver / crear / editar. */
class CampanaController extends Controller
{
    public const ESTADOS = ['PLANIFICADA' => ['Planificada', 'secondary'], 'ACTIVA' => ['Activa', 'success'], 'PAUSADA' => ['Pausada', 'warning'], 'FINALIZADA' => ['Finalizada', 'dark'], 'CANCELADA' => ['Cancelada', 'danger']];

    public const TIPOS = ['EMAIL' => 'Correo', 'WHATSAPP' => 'WhatsApp', 'LLAMADAS' => 'Llamadas', 'REDES_SOCIALES' => 'Redes sociales', 'EVENTO' => 'Evento', 'REFERIDOS' => 'Referidos', 'OTRO' => 'Otro'];

    public const OBJETIVOS = ['LEADS' => 'Generar prospectos', 'AWARENESS' => 'Dar a conocer la marca', 'REACTIVACION' => 'Reactivar clientes', 'FIDELIZACION' => 'Fidelizar clientes', 'UPSELL' => 'Vender más a clientes'];

    public const ESTADOS_ENVIO = ['PENDIENTE' => 'Pendiente', 'ENVIADO' => 'Enviado', 'ENTREGADO' => 'Entregado', 'ABIERTO' => 'Abierto', 'CLICK' => 'Hizo clic', 'REBOTADO' => 'Rebotado', 'BAJA' => 'Se dio de baja'];

    public function index(Request $request): View
    {
        return view('campanas.index', [
            'campanas' => $this->deMiEmpresa($request)->with('lineaNegocio')->withCount('contactos')->orderByDesc('fecha_inicio')->paginate(25),
            'estados' => self::ESTADOS,
            'tipos' => self::TIPOS,
            'objetivos' => self::OBJETIVOS,
        ]);
    }

    public function show(Request $request, int $campana): View
    {
        $c = $this->deMiEmpresa($request)->with('lineaNegocio')->findOrFail($campana);
        $contactos = $c->contactos()->with(['prospecto', 'cliente'])->get();
        $idEmpresa = $request->user()->id_empresa;

        return view('campanas.show', [
            'c' => $c,
            'contactos' => $contactos,
            'embudo' => $contactos->countBy('estado_envio'),
            'prospectos' => Prospecto::query()->where('id_empresa', $idEmpresa)->whereNotIn('estado', ['DESCARTADO'])->whereNotIn('id_prospecto', $contactos->pluck('id_prospecto')->filter())->orderBy('nombre_empresa')->get(['id_prospecto', 'nombre_empresa']),
            'clientes' => Cliente::query()->where('id_empresa', $idEmpresa)->where('activo', true)->whereNotIn('id_cliente', $contactos->pluck('id_cliente')->filter())->orderBy('razon_social')->get(['id_cliente', 'razon_social']),
            'estados' => self::ESTADOS,
            'tipos' => self::TIPOS,
            'objetivos' => self::OBJETIVOS,
            'estadosEnvio' => self::ESTADOS_ENVIO,
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new Campana(['tipo' => 'EMAIL', 'objetivo' => 'LEADS', 'moneda' => 'GTQ', 'fecha_inicio' => now(), 'estado' => 'PLANIFICADA']));
    }

    public function store(Request $request): RedirectResponse
    {
        $c = Campana::create($this->validar($request) + ['id_empresa' => $request->user()->id_empresa, 'estado' => 'PLANIFICADA', 'created_by' => $request->user()->id_usuario]);

        return redirect()->route('campanas.show', $c->id_campana)->with('status', "Campaña «{$c->nombre}» creada.");
    }

    public function edit(Request $request, int $campana): View
    {
        return $this->formulario($request, $this->deMiEmpresa($request)->findOrFail($campana));
    }

    public function update(Request $request, int $campana): RedirectResponse
    {
        $c = $this->deMiEmpresa($request)->findOrFail($campana);
        $datos = $this->validar($request);
        $extra = $request->validate([
            'estado' => ['required', Rule::in(array_keys(self::ESTADOS))],
            'gasto_real' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'leads_generados' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
        $c->update($datos + ['estado' => $extra['estado'], 'gasto_real' => (float) ($extra['gasto_real'] ?? 0), 'leads_generados' => (int) ($extra['leads_generados'] ?? 0)]);

        return redirect()->route('campanas.show', $c->id_campana)->with('status', 'Campaña actualizada.');
    }

    /** Agrega prospectos y/o clientes a la campaña (sin repetir). */
    public function agregarContactos(Request $request, int $campana): RedirectResponse
    {
        $c = $this->deMiEmpresa($request)->findOrFail($campana);
        $idEmpresa = $request->user()->id_empresa;
        $datos = $request->validate([
            'prospectos' => ['nullable', 'array'],
            'prospectos.*' => ['integer', Rule::exists('prospecto', 'id_prospecto')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
            'clientes' => ['nullable', 'array'],
            'clientes.*' => ['integer', Rule::exists('cliente', 'id_cliente')->where('id_empresa', $idEmpresa)->whereNull('deleted_at')],
        ]);
        $agregados = 0;
        DB::transaction(function () use ($c, $datos, &$agregados) {
            foreach (['prospectos' => ['PROSPECTO', 'id_prospecto'], 'clientes' => ['CLIENTE', 'id_cliente']] as $campo => [$tipo, $columna]) {
                foreach (array_unique($datos[$campo] ?? []) as $id) {
                    if (! $c->contactos()->where($columna, $id)->exists()) {
                        CampanaContacto::create(['id_campana' => $c->id_campana, 'tipo_contacto' => $tipo, $columna => $id, 'estado_envio' => 'PENDIENTE']);
                        $agregados++;
                    }
                }
            }
        });

        return back()->with('status', "{$agregados} contactos agregados a la campaña.");
    }

    public function estadoContacto(Request $request, int $campana, int $contacto): RedirectResponse
    {
        $c = $this->deMiEmpresa($request)->findOrFail($campana);
        $k = $c->contactos()->findOrFail($contacto);
        $datos = $request->validate(['estado_envio' => ['required', Rule::in(array_keys(self::ESTADOS_ENVIO))], 'resultado' => ['nullable', 'string', 'max:200']]);
        $k->update($datos + [
            'fecha_envio' => $k->fecha_envio ?? ($datos['estado_envio'] !== 'PENDIENTE' ? now() : null),
            'fecha_apertura' => $k->fecha_apertura ?? (in_array($datos['estado_envio'], ['ABIERTO', 'CLICK'], true) ? now() : null),
        ]);

        return back()->with('status', 'Contacto actualizado.');
    }

    private function formulario(Request $request, Campana $c): View
    {
        return view('campanas.form', [
            'c' => $c,
            'lineas' => LineaNegocio::query()->where('id_empresa', $request->user()->id_empresa)->where('activo', true)->orderBy('nombre')->get(),
            'monedas' => DB::table('moneda')->where('activo', true)->orderBy('codigo')->get(),
            'estados' => self::ESTADOS,
            'tipos' => self::TIPOS,
            'objetivos' => self::OBJETIVOS,
        ]);
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return Campana::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'tipo' => ['required', Rule::in(array_keys(self::TIPOS))],
            'objetivo' => ['required', Rule::in(array_keys(self::OBJETIVOS))],
            'id_linea' => ['nullable', 'integer', Rule::exists('linea_negocio', 'id_linea')->where('id_empresa', $request->user()->id_empresa)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'presupuesto' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'moneda' => ['required', Rule::exists('moneda', 'codigo')->where('activo', true)],
            'meta_leads' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
    }
}

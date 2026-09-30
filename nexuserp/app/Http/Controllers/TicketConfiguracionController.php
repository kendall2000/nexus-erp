<?php

namespace App\Http\Controllers;

use App\Models\CRM\CategoriaTicket;
use App\Models\CRM\SlaConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Categorías y planes de SLA de tickets. Permiso: tickets.configurar.
 * Un plan (p. ej. «SLA Premium») tiene una fila por prioridad con sus horas de respuesta y resolución.
 */
class TicketConfiguracionController extends Controller
{
    public function index(Request $request): View
    {
        $idEmpresa = $request->user()->id_empresa;

        return view('tickets.configuracion', [
            'categorias' => CategoriaTicket::query()->where('id_empresa', $idEmpresa)->orderBy('nombre')->get(),
            'slas' => SlaConfig::query()->where('id_empresa', $idEmpresa)->orderBy('nombre')
                ->orderByRaw("CASE prioridad WHEN 'CRITICA' THEN 1 WHEN 'ALTA' THEN 2 WHEN 'MEDIA' THEN 3 ELSE 4 END")->get()->groupBy('nombre'),
            'prioridades' => TicketController::PRIORIDADES,
        ]);
    }

    public function guardarCategoria(Request $request, ?int $categoria = null): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $c = $categoria ? CategoriaTicket::query()->where('id_empresa', $idEmpresa)->findOrFail($categoria) : new CategoriaTicket(['id_empresa' => $idEmpresa]);
        $datos = $request->validateWithBag('categoria', [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('categoria_ticket', 'nombre')->where('id_empresa', $idEmpresa)->ignore($c->id_categoria, 'id_categoria')],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'prioridad_default' => ['required', Rule::in(array_keys(TicketController::PRIORIDADES))],
        ], ['nombre.unique' => 'Ya hay una categoría con ese nombre.']);
        $c->fill($datos + ['descripcion' => null, 'activo' => $request->boolean('activo', true)])->save();

        return redirect()->route('tickets.configuracion')->with('status', "Categoría «{$c->nombre}» guardada.");
    }

    public function guardarSla(Request $request, ?int $sla = null): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $s = $sla ? SlaConfig::query()->where('id_empresa', $idEmpresa)->findOrFail($sla) : new SlaConfig(['id_empresa' => $idEmpresa]);
        $datos = $request->validateWithBag('sla', [
            'nombre' => ['required', 'string', 'max:100'],
            'prioridad' => ['required', Rule::in(array_keys(TicketController::PRIORIDADES))],
            'tiempo_primera_respuesta_hrs' => ['required', 'integer', 'min:1', 'max:255'],
            'tiempo_resolucion_hrs' => ['required', 'integer', 'min:1', 'max:65535', 'gte:tiempo_primera_respuesta_hrs'],
        ], ['tiempo_resolucion_hrs.gte' => 'La resolución no puede ser antes que la primera respuesta.']);
        $repetido = SlaConfig::query()->where('id_empresa', $idEmpresa)->where('nombre', $datos['nombre'])->where('prioridad', $datos['prioridad'])
            ->when($s->exists, fn ($q) => $q->whereKeyNot($s->id_sla))->exists();
        if ($repetido) {
            throw ValidationException::withMessages(['prioridad' => 'Ese plan ya tiene tiempos para esa prioridad.'])->errorBag('sla');
        }
        $s->fill($datos + ['aplica_fines_semana' => $request->boolean('aplica_fines_semana'), 'activo' => $request->boolean('activo', true)])->save();

        return redirect()->route('tickets.configuracion')->with('status', "SLA «{$s->nombre}» ({$s->prioridad}) guardado. Aplica a los tickets nuevos.");
    }
}

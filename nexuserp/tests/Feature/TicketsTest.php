<?php

namespace Tests\Feature;

use App\Support\Sla;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 7e: Tickets con SLA, respuestas, asignación con escalación, cierre y reapertura. */
class TicketsTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        foreach (['BAJA' => [8, 72, false], 'MEDIA' => [4, 48, false], 'ALTA' => [2, 24, true], 'CRITICA' => [1, 4, true]] as $prioridad => [$r, $s, $fds]) {
            DB::table('sla_config')->insert(['id_empresa' => 1, 'nombre' => 'SLA Estándar', 'prioridad' => $prioridad, 'tiempo_primera_respuesta_hrs' => $r, 'tiempo_resolucion_hrs' => $s, 'aplica_fines_semana' => $fds]);
        }
        $this->ids = [
            'cliente' => DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Condominio Las Flores', 'activo' => true]),
            'categoria' => DB::table('categoria_ticket')->insertGetId(['id_empresa' => 1, 'nombre' => 'Emergencia en sitio', 'prioridad_default' => 'CRITICA']),
            'ana' => DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Ana', 'primer_apellido' => 'Soporte', 'estado' => 'ACTIVO']),
            'luis' => DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Luis', 'primer_apellido' => 'Supervisor', 'estado' => 'ACTIVO']),
        ];
    }

    private function abrir(array $cambios = [])
    {
        return $this->post(route('tickets.store'), array_merge([
            'id_cliente' => $this->ids['cliente'], 'id_categoria' => $this->ids['categoria'], 'asunto' => 'Fuga de agua en Torre A',
            'descripcion' => 'El cliente reporta una fuga en el sótano.', 'canal_origen' => 'TELEFONO', 'prioridad' => 'ALTA', 'tipo' => 'INCIDENTE', 'plan_sla' => 'SLA Estándar',
        ], $cambios));
    }

    private function ticket(): object
    {
        return DB::table('ticket')->orderByDesc('id_ticket')->first();
    }

    public function test_sla_sin_fines_de_semana(): void
    {
        $viernes = Carbon::parse('2026-10-02 16:00'); // viernes
        $this->assertSame('2026-10-05 04:00', Sla::vence($viernes, 12, false)->format('Y-m-d H:i')); // salta sábado y domingo
        $this->assertSame('2026-10-03 04:00', Sla::vence($viernes, 12, true)->format('Y-m-d H:i'));
        $this->assertSame('2026-10-05 04:00', Sla::vence(Carbon::parse('2026-10-03 10:00'), 4, false)->format('Y-m-d H:i')); // abierto en sábado
    }

    public function test_abre_con_numero_y_limites_de_sla(): void
    {
        Carbon::setTestNow('2026-09-30 10:00');
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->abrir()->assertSessionHasNoErrors();
        $t = $this->ticket();
        $this->assertSame(['TK-2026-00001', 'ABIERTO', 2, 24], [$t->numero_ticket, $t->estado, (int) $t->sla_primera_respuesta_hrs, (int) $t->sla_resolucion_hrs]);
        $this->assertSame(['2026-09-30 12:00', '2026-10-01 10:00'], [Carbon::parse($t->fecha_limite_respuesta)->format('Y-m-d H:i'), Carbon::parse($t->fecha_limite_resolucion)->format('Y-m-d H:i')]);

        $this->actingAs($admin)->abrir(['plan_sla' => ''])->assertSessionHasNoErrors();
        $this->assertNull($this->ticket()->fecha_limite_resolucion);
        Carbon::setTestNow();
    }

    public function test_primera_respuesta_publica_y_notas_internas(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->abrir();
        $id = $this->ticket()->id_ticket;

        $this->actingAs($admin)->post(route('tickets.responder', $id), ['contenido' => 'Revisar con el supervisor', 'es_nota_interna' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(['ABIERTO', null], [$this->ticket()->estado, $this->ticket()->fecha_primera_respuesta]); // la nota interna no cuenta

        $this->actingAs($admin)->post(route('tickets.responder', $id), ['contenido' => 'Enviamos un técnico en 30 minutos.', 'es_nota_interna' => '0'])->assertSessionHasNoErrors();
        $t = $this->ticket();
        $this->assertSame('EN_PROGRESO', $t->estado);
        $this->assertNotNull($t->fecha_primera_respuesta);
        $this->assertSame((int) $admin->id_usuario, (int) DB::table('ticket_comentario')->orderByDesc('id_comentario')->value('id_usuario'));

        $this->actingAs($admin)->post(route('tickets.responder', $id), ['contenido' => 'Fuga reparada.', 'estado' => 'RESUELTO']);
        $this->assertSame('RESUELTO', $this->ticket()->estado);
        $this->assertNotNull($this->ticket()->fecha_resolucion);
        // Resuelto: ya no se responde sin reabrir.
        $this->actingAs($admin)->post(route('tickets.responder', $id), ['contenido' => 'Algo más'])->assertSessionHasErrors('ticket');
    }

    public function test_reasignar_registra_la_escalacion(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->abrir(['id_asignado_a' => $this->ids['ana']]);
        $id = $this->ticket()->id_ticket;

        $this->actingAs($admin)->patch(route('tickets.asignar', $id), ['id_asignado_a' => $this->ids['luis']])->assertSessionHasErrors('motivo');
        $this->actingAs($admin)->patch(route('tickets.asignar', $id), ['id_asignado_a' => $this->ids['luis'], 'motivo' => 'Requiere supervisor'])->assertSessionHasNoErrors();
        $e = DB::table('escalacion_ticket')->sole();
        $this->assertSame([$this->ids['ana'], $this->ids['luis'], 1, (int) $admin->id_usuario], [(int) $e->escalado_por, (int) $e->escalado_a, (int) $e->nivel, (int) $e->id_usuario]);
        $this->assertSame($this->ids['luis'], (int) $this->ticket()->id_asignado_a);

        $baja = DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Ex', 'estado' => 'BAJA']);
        $this->actingAs($admin)->patch(route('tickets.asignar', $id), ['id_asignado_a' => $baja, 'motivo' => 'Prueba de baja'])->assertSessionHasErrors('id_asignado_a');
    }

    public function test_cerrar_con_calificacion_y_reabrir(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->abrir();
        $id = $this->ticket()->id_ticket;

        $this->actingAs($admin)->patch(route('tickets.cerrar', $id), ['calificacion_cliente' => 5, 'comentario_calificacion' => 'Muy rápido'])->assertSessionHasNoErrors();
        $this->assertSame(['CERRADO', 5], [$this->ticket()->estado, (int) $this->ticket()->calificacion_cliente]);
        $this->assertSame([5, 'CSAT'], [(int) DB::table('evaluacion_satisfaccion')->value('puntuacion'), DB::table('evaluacion_satisfaccion')->value('tipo')]);

        $this->actingAs($admin)->patch(route('tickets.reabrir', $id), ['motivo' => ''])->assertSessionHasErrors('motivo');
        $this->actingAs($admin)->patch(route('tickets.reabrir', $id), ['motivo' => 'Volvió a gotear'])->assertSessionHasNoErrors();
        $this->assertSame(['REABIERTO', null], [$this->ticket()->estado, $this->ticket()->fecha_cierre]);
    }

    public function test_configuracion_de_categorias_y_sla(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('tickets.categorias.store'), ['nombre' => 'Facturación', 'prioridad_default' => 'BAJA', 'activo' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('tickets.slas.store'), ['nombre' => 'SLA Estándar', 'prioridad' => 'ALTA', 'tiempo_primera_respuesta_hrs' => 1, 'tiempo_resolucion_hrs' => 8])
            ->assertSessionHasErrorsIn('sla', 'prioridad'); // ya existe esa prioridad en el plan
        $this->actingAs($admin)->post(route('tickets.slas.store'), ['nombre' => 'SLA Premium', 'prioridad' => 'ALTA', 'tiempo_primera_respuesta_hrs' => 4, 'tiempo_resolucion_hrs' => 2])
            ->assertSessionHasErrorsIn('sla', 'tiempo_resolucion_hrs');
        $this->actingAs($admin)->post(route('tickets.slas.store'), ['nombre' => 'SLA Premium', 'prioridad' => 'ALTA', 'tiempo_primera_respuesta_hrs' => 1, 'tiempo_resolucion_hrs' => 8, 'activo' => '1'])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->get(route('tickets.configuracion'))->assertOk()->assertSee('SLA Premium')->assertSee('Facturación');
        $this->actingAs($admin)->get(route('tickets.create'))->assertOk()->assertSee('SLA Premium');
    }

    public function test_pantallas_y_permisos(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->abrir();
        $id = $this->ticket()->id_ticket;
        $this->actingAs($admin)->get(route('tickets.index'))->assertOk()->assertSee('TK-')->assertSee('Fuga de agua');
        $this->actingAs($admin)->get(route('tickets.show', $id))->assertOk()->assertSee('El cliente reporta una fuga');

        $agente = $this->crearUsuario(['username' => 'a', 'email' => 'a@nexus.test'], 'Soporte');
        $this->darPermisos($agente, ['tickets.ver', 'tickets.crear']);
        $this->actingAs($agente)->abrir(['id_asignado_a' => $this->ids['ana']])->assertSessionHasErrors('id_asignado_a'); // sin permiso de asignar
        $this->actingAs($agente)->post(route('tickets.responder', $id), ['contenido' => 'Hola'])->assertForbidden();
        $this->actingAs($agente)->get(route('tickets.configuracion'))->assertForbidden();
        $ajeno = DB::table('ticket')->insertGetId(['id_empresa' => 2, 'id_cliente' => 1, 'numero_ticket' => 'X', 'asunto' => 'Ajeno']);
        $this->actingAs($agente)->get(route('tickets.show', $ajeno))->assertNotFound();
    }
}

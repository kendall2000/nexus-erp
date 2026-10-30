<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 7h: Prospectos, oportunidades (embudo y propuestas) y campañas. */
class CrmTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $this->ids = [
            'pais' => DB::table('pais')->insertGetId(['codigo_iso2' => 'GT', 'nombre' => 'Guatemala']),
            'fuente' => DB::table('fuente_lead')->insertGetId(['nombre' => 'Referido']),
            'vendedora' => DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Sofía', 'primer_apellido' => 'Ventas', 'estado' => 'ACTIVO']),
        ];
    }

    private function prospecto(array $cambios = [])
    {
        return $this->post(route('prospectos.store'), array_merge([
            'nombre_empresa' => 'Hotel Las Palmas', 'nombre_contacto' => 'Mario Ruiz', 'email_contacto' => 'mario@palmas.test', 'telefono_contacto' => '5555-1234',
            'id_fuente' => $this->ids['fuente'], 'id_asignado_a' => $this->ids['vendedora'], 'temperatura' => 'FRIO', 'moneda' => 'GTQ', 'presupuesto_estimado' => 20000,
            'sitio_web' => 'palmas.test',
        ], $cambios));
    }

    private function ultimo(string $tabla, string $llave): object
    {
        return DB::table($tabla)->orderByDesc($llave)->first();
    }

    public function test_prospecto_seguimiento_y_descarte(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->prospecto()->assertSessionHasNoErrors();
        $p = $this->ultimo('prospecto', 'id_prospecto');
        $this->assertSame(['NUEVO', 'https://palmas.test'], [$p->estado, $p->sitio_web]);
        $this->prospecto(['email_contacto' => 'no-es-correo'])->assertSessionHasErrors('email_contacto');

        $this->post(route('prospectos.seguimiento', $p->id_prospecto), ['id_realizado_por' => $this->ids['vendedora'], 'tipo' => 'LLAMADA',
            'fecha_hora' => now()->subHour()->format('Y-m-d H:i'), 'resultado' => 'INTERESADO', 'resumen' => 'Quiere cotización de limpieza'])->assertSessionHasNoErrors();
        $p = $this->ultimo('prospecto', 'id_prospecto');
        $this->assertSame(['EN_CONTACTO', 'TIBIO'], [$p->estado, $p->temperatura]); // primer contacto e interés
        $this->post(route('prospectos.seguimiento', $p->id_prospecto), ['id_realizado_por' => $this->ids['vendedora'], 'tipo' => 'LLAMADA',
            'fecha_hora' => now()->addDay()->format('Y-m-d H:i'), 'resultado' => 'CONTACTADO', 'resumen' => 'Futuro'])->assertSessionHasErrorsIn('seguimiento', 'fecha_hora');

        $this->patch(route('prospectos.descartar', $p->id_prospecto), ['motivo_descarte' => ''])->assertSessionHasErrors('motivo_descarte');
        $this->patch(route('prospectos.descartar', $p->id_prospecto), ['motivo_descarte' => 'Sin presupuesto este año'])->assertSessionHasNoErrors();
        $this->assertSame('DESCARTADO', $this->ultimo('prospecto', 'id_prospecto')->estado);
        $this->get(route('prospectos.edit', $p->id_prospecto))->assertRedirect(); // cerrado: no se edita
    }

    public function test_convertir_en_cliente_con_oportunidad(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->prospecto();
        $p = $this->ultimo('prospecto', 'id_prospecto');
        $this->get(route('prospectos.show', $p->id_prospecto))->assertOk(); // crea las etapas por defecto
        $etapa = DB::table('etapa_funnel')->where('nombre', 'Calificado')->value('id_etapa');

        $this->post(route('prospectos.convertir', $p->id_prospecto), ['crear_oportunidad' => '1', 'id_etapa' => $etapa])->assertSessionHasErrors('id_responsable');
        $this->post(route('prospectos.convertir', $p->id_prospecto), ['crear_oportunidad' => '1', 'id_etapa' => $etapa, 'id_responsable' => $this->ids['vendedora']])->assertRedirect();

        $cliente = $this->ultimo('cliente', 'id_cliente');
        $this->assertSame(['Hotel Las Palmas', 'mario@palmas.test'], [$cliente->razon_social, $cliente->email_principal]);
        $this->assertSame(['Mario Ruiz', 1], [DB::table('contacto_cliente')->value('nombre'), (int) DB::table('contacto_cliente')->value('es_contacto_principal')]);
        $p = $this->ultimo('prospecto', 'id_prospecto');
        $this->assertSame(['CONVERTIDO', (int) $cliente->id_cliente], [$p->estado, (int) $p->id_cliente_generado]);
        $o = $this->ultimo('oportunidad', 'id_oportunidad');
        $this->assertSame([(int) $cliente->id_cliente, 25, 20000.0, 5000.0], [(int) $o->id_cliente, (int) $o->probabilidad, (float) $o->valor_estimado, (float) $o->valor_ponderado]);
        $this->post(route('prospectos.convertir', $p->id_prospecto), [])->assertSessionHasErrors('prospecto'); // no se convierte dos veces
    }

    public function test_embudo_mover_ganar_y_perder(): void
    {
        $this->actingAs($this->crearUsuario());
        $cliente = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Banco Central', 'activo' => true]);
        $this->get(route('oportunidades.index'))->assertOk()->assertSee('Lead nuevo'); // etapas por defecto
        $etapas = DB::table('etapa_funnel')->pluck('id_etapa', 'nombre');

        $this->post(route('oportunidades.store'), ['nombre' => 'Seguridad sede', 'id_etapa' => $etapas['Ganada'], 'id_responsable' => $this->ids['vendedora'], 'valor_estimado' => 1000, 'moneda' => 'GTQ', 'id_cliente' => $cliente])
            ->assertSessionHasErrors('id_etapa'); // no se crea ya ganada
        $this->post(route('oportunidades.store'), ['nombre' => 'Seguridad sede', 'id_etapa' => $etapas['Negociación'], 'id_responsable' => $this->ids['vendedora'], 'valor_estimado' => 1000, 'moneda' => 'GTQ'])
            ->assertSessionHasErrors('id_cliente'); // sin cliente ni prospecto
        $this->post(route('oportunidades.store'), ['nombre' => 'Seguridad sede', 'id_etapa' => $etapas['Negociación'], 'id_responsable' => $this->ids['vendedora'], 'valor_estimado' => 10000, 'moneda' => 'GTQ', 'id_cliente' => $cliente])
            ->assertSessionHasNoErrors();
        $o = $this->ultimo('oportunidad', 'id_oportunidad');
        $this->assertSame([75, 7500.0], [(int) $o->probabilidad, (float) $o->valor_ponderado]);

        $this->patch(route('oportunidades.mover', $o->id_oportunidad), ['id_etapa' => $etapas['Perdida']])->assertSessionHasErrors('razon_cierre');
        $this->patch(route('oportunidades.mover', $o->id_oportunidad), ['id_etapa' => $etapas['Ganada']])->assertSessionHasNoErrors();
        $o = $this->ultimo('oportunidad', 'id_oportunidad');
        $this->assertSame([100, today()->toDateString()], [(int) $o->probabilidad, substr((string) $o->fecha_cierre_real, 0, 10)]);
        $this->get(route('oportunidades.index'))->assertOk()->assertSee('10,000.00');
        $this->get(route('oportunidades.show', $o->id_oportunidad))->assertOk()->assertSee('Registrar contrato');
    }

    public function test_propuestas_con_version_y_estados(): void
    {
        $this->actingAs($this->crearUsuario());
        $cliente = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Banco Central', 'activo' => true]);
        $this->get(route('oportunidades.index'));
        $etapa = DB::table('etapa_funnel')->where('nombre', 'Calificado')->value('id_etapa');
        $this->post(route('oportunidades.store'), ['nombre' => 'Limpieza', 'id_etapa' => $etapa, 'id_responsable' => $this->ids['vendedora'], 'valor_estimado' => 5000, 'moneda' => 'GTQ', 'id_cliente' => $cliente]);
        $o = $this->ultimo('oportunidad', 'id_oportunidad')->id_oportunidad;
        $datos = ['titulo' => 'Propuesta de limpieza', 'id_elaborado_por' => $this->ids['vendedora'], 'valor_propuesto' => 5000, 'fecha_emision' => now()->toDateString(), 'fecha_vencimiento' => now()->addDays(30)->toDateString()];

        $this->post(route('oportunidades.propuestas.store', $o), $datos)->assertSessionHasNoErrors();
        $this->post(route('oportunidades.propuestas.store', $o), ['valor_propuesto' => 4500] + $datos);
        $this->assertSame([['PR-'.now()->year.'-0001', 1], ['PR-'.now()->year.'-0002', 2]],
            DB::table('propuesta')->orderBy('id_propuesta')->get()->map(fn ($p) => [$p->numero_propuesta, (int) $p->version])->all());

        $id = DB::table('propuesta')->orderBy('id_propuesta')->value('id_propuesta');
        $this->patch(route('oportunidades.propuestas.estado', [$o, $id]), ['estado' => 'ACEPTADA'])->assertSessionHasErrors('estado'); // primero se envía
        $this->patch(route('oportunidades.propuestas.estado', [$o, $id]), ['estado' => 'ENVIADA'])->assertSessionHasNoErrors();
        $this->patch(route('oportunidades.propuestas.estado', [$o, $id]), ['estado' => 'RECHAZADA'])->assertSessionHasErrors('motivo_rechazo');
        $this->patch(route('oportunidades.propuestas.estado', [$o, $id]), ['estado' => 'RECHAZADA', 'motivo_rechazo' => 'Precio alto'])->assertSessionHasNoErrors();
        $this->get(route('oportunidades.show', $o))->assertOk()->assertSee('Precio alto');
    }

    public function test_campanas_y_contactos(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->prospecto();
        $prospecto = $this->ultimo('prospecto', 'id_prospecto')->id_prospecto;
        $cliente = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Banco Central', 'activo' => true]);

        $this->post(route('campanas.store'), ['nombre' => 'Expo 2026', 'tipo' => 'EVENTO', 'objetivo' => 'LEADS', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-09-01', 'moneda' => 'GTQ'])
            ->assertSessionHasErrors('fecha_fin');
        $this->post(route('campanas.store'), ['nombre' => 'Expo 2026', 'tipo' => 'EVENTO', 'objetivo' => 'LEADS', 'fecha_inicio' => '2026-10-01', 'moneda' => 'GTQ', 'presupuesto' => 3000, 'meta_leads' => 20])
            ->assertSessionHasNoErrors();
        $c = $this->ultimo('campana', 'id_campana')->id_campana;

        $this->post(route('campanas.contactos.store', $c), ['prospectos' => [$prospecto], 'clientes' => [$cliente]])->assertSessionHasNoErrors();
        $this->post(route('campanas.contactos.store', $c), ['prospectos' => [$prospecto]]); // no se repite
        $this->assertSame(2, DB::table('campana_contacto')->count());
        $k = DB::table('campana_contacto')->value('id_contacto_campana');
        $this->patch(route('campanas.contactos.update', [$c, $k]), ['estado_envio' => 'ABIERTO', 'resultado' => 'Pidió llamada'])->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('campana_contacto')->where('id_contacto_campana', $k)->value('fecha_apertura'));

        $this->put(route('campanas.update', $c), ['nombre' => 'Expo 2026', 'tipo' => 'EVENTO', 'objetivo' => 'LEADS', 'fecha_inicio' => '2026-10-01', 'moneda' => 'GTQ',
            'estado' => 'ACTIVA', 'gasto_real' => 1500, 'leads_generados' => 10])->assertSessionHasNoErrors();
        $this->get(route('campanas.show', $c))->assertOk()->assertSee('150.00')->assertSee('Hotel Las Palmas'); // costo por prospecto 1,500 / 10
    }

    public function test_permisos_y_otra_empresa(): void
    {
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');
        $this->darPermisos($vendedor, ['prospectos.ver', 'oportunidades.ver', 'campanas.ver']);
        $this->actingAs($vendedor)->post(route('prospectos.store'), [])->assertForbidden();
        $this->actingAs($vendedor)->get(route('campanas.index'))->assertOk()->assertDontSee('Nueva campaña');
        $ajeno = DB::table('prospecto')->insertGetId(['id_empresa' => 2, 'nombre_empresa' => 'Ajeno', 'nombre_contacto' => 'X', 'email_contacto' => 'x@x.test']);
        $this->actingAs($vendedor)->get(route('prospectos.show', $ajeno))->assertNotFound();
    }
}

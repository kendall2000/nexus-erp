<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 7d: Contratos de servicio (estados, sitios, personal y facturar desde el contrato). */
class ContratosTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $cliente = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Condominio Las Flores', 'dias_credito' => 15, 'activo' => true]);
        $linea = DB::table('linea_negocio')->insertGetId(['id_empresa' => 1, 'nombre' => 'Limpieza']);
        $this->ids = [
            'cliente' => $cliente,
            'servicio' => DB::table('tipo_servicio')->insertGetId(['id_linea' => $linea, 'nombre' => 'Conserjería', 'precio_base' => 3500]),
            'sitio' => DB::table('sitio_trabajo')->insertGetId(['id_cliente' => $cliente, 'nombre' => 'Torre A', 'direccion' => 'Zona 10']),
            'empleado' => DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Luis', 'primer_apellido' => 'Pérez', 'estado' => 'ACTIVO', 'fecha_ingreso' => '2026-01-01']),
        ];
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'id_cliente' => $this->ids['cliente'], 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31', 'moneda' => 'GTQ',
            'periodicidad_factura' => 'MENSUAL', 'dia_facturacion' => 5,
            'lineas' => [
                ['id_tipo_servicio' => $this->ids['servicio'], 'id_sitio' => $this->ids['sitio'], 'cantidad' => 2, 'precio_unitario' => 3500, 'descuento_pct' => 10],
                ['id_tipo_servicio' => ''], // vacía: se descarta
            ],
        ], $cambios);
    }

    private function contrato(): object
    {
        return DB::table('contrato_servicio')->orderByDesc('id_contrato')->first();
    }

    public function test_crea_en_borrador_y_calcula_valores(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('contratos.store'), $this->datos(['estado' => 'VIGENTE']))->assertSessionHasNoErrors();

        $c = $this->contrato();
        $this->assertSame(['CS-'.now()->year.'-0001', 'BORRADOR', 6300.0, 75600.0], [$c->numero_contrato, $c->estado, (float) $c->valor_mensual, (float) $c->valor_total_estimado]);
        $this->assertSame(1, DB::table('contrato_servicio_detalle')->count());

        // Sitio de otro cliente y servicio de otra empresa: rechazados.
        $otroCliente = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Otro', 'activo' => true]);
        $sitioAjeno = DB::table('sitio_trabajo')->insertGetId(['id_cliente' => $otroCliente, 'nombre' => 'X', 'direccion' => 'Y']);
        $this->actingAs($admin)->post(route('contratos.store'), $this->datos(['lineas' => [['id_tipo_servicio' => $this->ids['servicio'], 'id_sitio' => $sitioAjeno, 'cantidad' => 1, 'precio_unitario' => 1]]]))
            ->assertSessionHasErrors('lineas.0.id_sitio');
        $lineaAjena = DB::table('linea_negocio')->insertGetId(['id_empresa' => 2, 'nombre' => 'Ajena']);
        $servicioAjeno = DB::table('tipo_servicio')->insertGetId(['id_linea' => $lineaAjena, 'nombre' => 'Ajeno']);
        $this->actingAs($admin)->post(route('contratos.store'), $this->datos(['lineas' => [['id_tipo_servicio' => $servicioAjeno, 'cantidad' => 1, 'precio_unitario' => 1]]]))
            ->assertSessionHasErrors('lineas.0.id_tipo_servicio');
        $this->actingAs($admin)->post(route('contratos.store'), $this->datos(['dia_facturacion' => 31]))->assertSessionHasErrors('dia_facturacion');
    }

    public function test_flujo_de_estados_y_cierre_libera_al_personal(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('contratos.store'), $this->datos(['fecha_fin' => now()->addYear()->format('Y-m-d')]));
        $id = $this->contrato()->id_contrato;

        $this->actingAs($admin)->patch(route('contratos.estado', [$id, 'suspender']))->assertSessionHasErrors('contrato');
        $this->actingAs($admin)->patch(route('contratos.estado', [$id, 'activar']))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('contratos.asignaciones.store', $id), ['id_empleado' => $this->ids['empleado'], 'id_sitio' => $this->ids['sitio'], 'fecha_inicio' => '2026-02-01', 'turno' => 'MANANA'])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('contratos.asignaciones.store', $id), ['id_empleado' => $this->ids['empleado'], 'id_sitio' => $this->ids['sitio'], 'fecha_inicio' => '2026-02-01'])
            ->assertSessionHasErrorsIn('asignacion', 'id_empleado'); // ya asignado ahí

        $this->actingAs($admin)->patch(route('contratos.estado', [$id, 'cerrar']), ['motivo' => ''])->assertSessionHasErrors('motivo');
        $this->actingAs($admin)->patch(route('contratos.estado', [$id, 'cerrar']), ['motivo' => 'El cliente rescindió'])->assertSessionHasNoErrors();
        $c = $this->contrato();
        $this->assertSame('CANCELADO', $c->estado); // la fecha de fin no había llegado
        $this->assertStringContainsString('El cliente rescindió', $c->notas);
        $this->assertSame(0, (int) DB::table('asignacion_contrato')->value('activo'));

        $this->actingAs($admin)->get(route('contratos.edit', $id))->assertRedirect(); // cerrado: no se edita
        $this->actingAs($admin)->patch(route('contratos.estado', [$id, 'reabrir']))->assertSessionHasNoErrors();
        $this->assertSame('VIGENTE', $this->contrato()->estado);
    }

    public function test_activar_exige_servicios_y_el_cliente_queda_fijo(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('contratos.store'), $this->datos());
        $id = $this->contrato()->id_contrato;
        DB::table('contrato_servicio_detalle')->delete();
        $this->actingAs($admin)->patch(route('contratos.estado', [$id, 'activar']))->assertSessionHasErrors('contrato');

        $this->actingAs($admin)->put(route('contratos.update', $id), $this->datos())->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('contratos.estado', [$id, 'activar']));
        $otro = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Otro', 'activo' => true]);
        $this->actingAs($admin)->put(route('contratos.update', $id), $this->datos(['id_cliente' => $otro, 'nombre_proyecto' => 'Ampliación']))->assertSessionHasNoErrors();
        $this->assertSame([$this->ids['cliente'], 'Ampliación'], [(int) $this->contrato()->id_cliente, $this->contrato()->nombre_proyecto]);
    }

    public function test_facturar_desde_el_contrato(): void
    {
        $admin = $this->crearUsuario();
        DB::table('empresa')->update(['tasa_iva' => 12, 'iva_incluido_en_precio' => true]);
        DB::table('serie_facturacion')->insert(['id_empresa' => 1, 'codigo_serie' => 'A', 'tipo' => 'FACTURA']);
        $this->actingAs($admin)->post(route('contratos.store'), $this->datos());
        $id = $this->contrato()->id_contrato;

        $this->actingAs($admin)->get(route('facturas.create', ['contrato' => $id]))->assertOk()->assertDontSee('Conserjería — Torre A'); // borrador: no se factura
        $this->actingAs($admin)->patch(route('contratos.estado', [$id, 'activar']));
        $this->actingAs($admin)->get(route('facturas.create', ['contrato' => $id]))->assertOk()->assertSee('Conserjería — Torre A')->assertSee('Factura del contrato');

        $this->actingAs($admin)->post(route('facturas.store'), [
            'id_serie' => DB::table('serie_facturacion')->value('id_serie'), 'id_cliente' => $this->ids['cliente'], 'id_contrato' => $id, 'moneda' => 'GTQ',
            'fecha_emision' => now()->format('Y-m-d'), 'fecha_vencimiento' => now()->addDays(15)->format('Y-m-d'),
            'lineas' => [['id_tipo_servicio' => $this->ids['servicio'], 'descripcion' => 'Conserjería', 'cantidad' => 2, 'precio_unitario' => 3500, 'descuento' => 700, 'es_afecto_iva' => '1']],
        ])->assertSessionHasNoErrors();
        $this->assertSame([$id, 6300.0], [(int) DB::table('factura')->value('id_contrato'), (float) DB::table('factura')->value('total')]);
        $this->actingAs($admin)->get(route('contratos.show', $id))->assertOk()->assertSee('A-00000001');

        // El contrato tiene que ser del cliente de la factura.
        $otro = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Otro', 'activo' => true]);
        $this->actingAs($admin)->post(route('facturas.store'), ['id_serie' => DB::table('serie_facturacion')->value('id_serie'), 'id_cliente' => $otro, 'id_contrato' => $id, 'moneda' => 'GTQ',
            'fecha_emision' => now()->format('Y-m-d'), 'fecha_vencimiento' => now()->format('Y-m-d'), 'lineas' => [['descripcion' => 'X', 'cantidad' => 1, 'precio_unitario' => 1]]])
            ->assertSessionHasErrors('id_contrato');
    }

    public function test_pantallas_y_permisos(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('contratos.store'), $this->datos());
        $id = $this->contrato()->id_contrato;
        $this->actingAs($admin)->post(route('contratos.sitios.store', $id), ['nombre' => 'Torre B', 'direccion' => 'Zona 10'])->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('contratos.index'))->assertOk()->assertSee('Condominio Las Flores');
        $this->actingAs($admin)->get(route('contratos.show', $id))->assertOk()->assertSee('Torre B')->assertSee('GTQ 6,300.00');
        $this->actingAs($admin)->get(route('contratos.imprimir', $id))->assertOk()->assertSee('CONTRATO DE SERVICIOS');

        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');
        $this->darPermisos($vendedor, ['contratos.ver', 'contratos.editar']);
        $this->actingAs($vendedor)->patch(route('contratos.estado', [$id, 'activar']))->assertSessionHasNoErrors();
        $this->actingAs($vendedor)->patch(route('contratos.estado', [$id, 'cerrar']), ['motivo' => 'Sin permiso de cierre'])->assertForbidden();
        $ajeno = DB::table('contrato_servicio')->insertGetId(['id_empresa' => 2, 'numero_contrato' => 'X', 'fecha_inicio' => '2026-01-01']);
        $this->actingAs($vendedor)->get(route('contratos.show', $ajeno))->assertNotFound();
    }
}

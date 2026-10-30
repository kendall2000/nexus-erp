<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 5b: Clientes en Blade (ficha, contactos, estado de cuenta). */
class ClientesTest extends TestCase
{
    use EsquemaNexus;

    private array $geo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $gt = DB::table('pais')->insertGetId(['codigo_iso2' => 'GT', 'nombre' => 'Guatemala']);
        $sv = DB::table('pais')->insertGetId(['codigo_iso2' => 'SV', 'nombre' => 'El Salvador']);
        $guate = DB::table('division_geografica')->insertGetId(['id_pais' => $gt, 'nombre' => 'Guatemala']);
        $sanSalvador = DB::table('division_geografica')->insertGetId(['id_pais' => $sv, 'nombre' => 'San Salvador']);
        $this->geo = [
            'gt' => $gt, 'sv' => $sv,
            'mixco' => DB::table('municipio')->insertGetId(['id_division' => $guate, 'nombre' => 'Mixco']),
            'soyapango' => DB::table('municipio')->insertGetId(['id_division' => $sanSalvador, 'nombre' => 'Soyapango']),
        ];
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'razon_social' => 'Comercial Uno, S.A.', 'nit' => '1234567-8', 'tipo_persona' => 'JURIDICA',
            'id_pais' => $this->geo['gt'], 'id_municipio' => $this->geo['mixco'], 'moneda_facturacion' => 'GTQ',
            'dias_credito' => 30, 'limite_credito' => 1000, 'sitio_web' => 'comercialuno.com', 'activo' => '1',
        ], $cambios);
    }

    private function cliente(): object
    {
        return DB::table('cliente')->orderByDesc('id_cliente')->first();
    }

    public function test_crea_cliente_normalizando_nit_y_sitio_web(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->post(route('clientes.store'), $this->datos())->assertSessionHasNoErrors();
        $c = $this->cliente();
        $this->assertSame(['12345678', 'https://comercialuno.com', 1, (int) $admin->id_usuario], [$c->nit, $c->sitio_web, (int) $c->activo, (int) $c->created_by]);

        // Mismo NIT escrito distinto: duplicado.
        $this->actingAs($admin)->post(route('clientes.store'), $this->datos(['nit' => '12345 67-8']))->assertSessionHasErrors('nit');
        // «CF» (consumidor final) sí se repite.
        $this->actingAs($admin)->post(route('clientes.store'), $this->datos(['nit' => 'cf']))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('clientes.store'), $this->datos(['nit' => 'CF']))->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('cliente')->where('nit', 'CF')->count());
    }

    public function test_el_nit_de_un_cliente_eliminado_se_puede_reusar(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('clientes.store'), $this->datos());
        $this->actingAs($admin)->delete(route('clientes.destroy', $this->cliente()->id_cliente))->assertSessionHasNoErrors();

        $this->assertNotNull(DB::table('cliente')->value('deleted_at')); // borrado lógico
        $this->actingAs($admin)->post(route('clientes.store'), $this->datos())->assertSessionHasNoErrors();
    }

    public function test_el_municipio_debe_ser_del_pais(): void
    {
        $this->actingAs($this->crearUsuario())->post(route('clientes.store'), $this->datos(['id_municipio' => $this->geo['soyapango']]))
            ->assertSessionHasErrors('id_municipio');
        $this->assertSame(0, DB::table('cliente')->count());
    }

    public function test_ficha_con_saldo_y_estado_de_cuenta(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('clientes.store'), $this->datos());
        $id = $this->cliente()->id_cliente;
        DB::table('factura')->insert([
            ['id_empresa' => 1, 'id_cliente' => $id, 'numero_completo' => 'A-1', 'fecha_emision' => now()->subDays(40), 'fecha_vencimiento' => now()->subDays(10), 'total' => 800, 'saldo_pendiente' => 800, 'estado' => 'EMITIDA'],
            ['id_empresa' => 1, 'id_cliente' => $id, 'numero_completo' => 'A-2', 'fecha_emision' => now(), 'fecha_vencimiento' => now()->addDays(30), 'total' => 500, 'saldo_pendiente' => 300, 'estado' => 'PARCIAL'],
            ['id_empresa' => 1, 'id_cliente' => $id, 'numero_completo' => 'A-3', 'fecha_emision' => now(), 'fecha_vencimiento' => now(), 'total' => 999, 'saldo_pendiente' => 999, 'estado' => 'ANULADA'],
        ]);

        $this->actingAs($admin)->get(route('clientes.index'))->assertOk()->assertSee('GTQ 1,100.00')->assertSee('Supera el límite de crédito', false);
        $this->actingAs($admin)->get(route('clientes.show', $id))->assertOk()
            ->assertSee('A-1')->assertSee('A-2')->assertDontSee('A-3')->assertSee('GTQ 1,100.00')->assertSee('GTQ 800.00');
        $this->actingAs($admin)->get(route('clientes.imprimir', $id))->assertOk()->assertSee('ESTADO DE CUENTA')->assertSee('1,100.00');
        $this->assertStringContainsString('Comercial Uno', $this->actingAs($admin)->get(route('clientes.exportar'))->streamedContent());
    }

    public function test_no_elimina_cliente_con_facturas_o_pagos(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('clientes.store'), $this->datos());
        $id = $this->cliente()->id_cliente;

        DB::table('factura')->insert(['id_empresa' => 1, 'id_cliente' => $id, 'estado' => 'BORRADOR']);
        $this->actingAs($admin)->delete(route('clientes.destroy', $id))->assertSessionHasErrors('cliente');

        DB::table('factura')->update(['estado' => 'ANULADA']);
        DB::table('pago')->insert(['id_empresa' => 1, 'id_cliente' => $id]);
        $this->actingAs($admin)->delete(route('clientes.destroy', $id))->assertSessionHasErrors('cliente');
        $this->assertNull($this->cliente()->deleted_at);
    }

    public function test_contactos_con_un_solo_principal(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('clientes.store'), $this->datos());
        $id = $this->cliente()->id_cliente;

        $this->actingAs($admin)->post(route('clientes.contactos.store', $id), ['nombre' => 'Ana', 'es_contacto_principal' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('clientes.contactos.store', $id), ['nombre' => 'Luis', 'email' => 'luis@x.com', 'es_contacto_principal' => '1', 'recibe_facturas' => '1']);
        $this->assertSame(['Luis'], DB::table('contacto_cliente')->where('es_contacto_principal', true)->pluck('nombre')->all());

        $ana = DB::table('contacto_cliente')->where('nombre', 'Ana')->value('id_contacto');
        $this->actingAs($admin)->put(route('clientes.contactos.update', [$id, $ana]), ['nombre' => 'Ana María', 'email' => 'no-es-correo'])->assertSessionHasErrorsIn('contacto', 'email');
        $this->actingAs($admin)->put(route('clientes.contactos.update', [$id, $ana]), ['nombre' => 'Ana María'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->get(route('clientes.show', $id))->assertSee('Ana María')->assertSee('Recibe facturas');

        $this->actingAs($admin)->delete(route('clientes.contactos.destroy', [$id, $ana]));
        $this->assertSame(1, DB::table('contacto_cliente')->count());
    }

    public function test_permisos_y_otra_empresa(): void
    {
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');
        $this->darPermisos($vendedor, ['clientes.ver']);
        $ajeno = DB::table('cliente')->insertGetId(['id_empresa' => 2, 'razon_social' => 'Ajeno', 'id_pais' => $this->geo['gt']]);
        $propio = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Propio', 'id_pais' => $this->geo['gt']]);

        $this->actingAs($vendedor)->get(route('clientes.index'))->assertOk()->assertSee('Propio')->assertDontSee('Ajeno')->assertDontSee('Nuevo cliente');
        $this->actingAs($vendedor)->get(route('clientes.show', $propio))->assertOk()->assertDontSee('Estado de cuenta')->assertDontSee('Agregar contacto');
        $this->actingAs($vendedor)->get(route('clientes.show', $ajeno))->assertNotFound();
        $this->actingAs($vendedor)->post(route('clientes.contactos.store', $propio), ['nombre' => 'X'])->assertForbidden();
        $this->actingAs($vendedor)->get(route('clientes.exportar'))->assertForbidden();

        // Un contacto de otro cliente no se puede editar desde este.
        $admin = $this->crearUsuario();
        $contactoAjeno = DB::table('contacto_cliente')->insertGetId(['id_cliente' => $ajeno, 'nombre' => 'Ajeno']);
        $this->actingAs($admin)->put(route('clientes.contactos.update', [$propio, $contactoAjeno]), ['nombre' => 'Hack'])->assertNotFound();
    }

    public function test_las_apis_de_clientes_y_geografia_ya_no_existen(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->getJson('/api/v1/clientes/clientes')->assertNotFound();
        $this->actingAs($admin)->getJson('/api/v1/geografia/divisiones/1')->assertNotFound();
    }
}

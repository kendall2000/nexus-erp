<?php

namespace Tests\Feature;

use App\Support\MenuLateral;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Sucursal o agencia en facturas (y el establecimiento de la serie), órdenes de compra y tickets,
 * sus filtros y el resumen por sucursal.
 */
class SucursalEnDocumentosTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        DB::table('empresa')->update(['tasa_iva' => 12, 'iva_incluido_en_precio' => true]);
        $central = DB::table('sucursal')->insertGetId(['id_empresa' => 1, 'nombre' => 'Central', 'es_casa_matriz' => true, 'codigo_establecimiento' => 1]);
        $norte = DB::table('sucursal')->insertGetId(['id_empresa' => 1, 'nombre' => 'Norte', 'codigo_establecimiento' => 2]);
        $this->ids = [
            'central' => $central,
            'norte' => $norte,
            'agencia' => DB::table('sucursal')->insertGetId(['id_empresa' => 1, 'nombre' => 'Agencia Mixco', 'tipo' => 'AGENCIA', 'id_sucursal_padre' => $norte, 'codigo_establecimiento' => 3]),
            'ajena' => DB::table('sucursal')->insertGetId(['id_empresa' => 2, 'nombre' => 'De otra empresa']),
            'cliente' => DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Cliente Uno', 'nit' => '123', 'dias_credito' => 30, 'activo' => true]),
            'serie' => DB::table('serie_facturacion')->insertGetId(['id_empresa' => 1, 'codigo_serie' => 'A', 'tipo' => 'FACTURA']),
            'serieNorte' => DB::table('serie_facturacion')->insertGetId(['id_empresa' => 1, 'codigo_serie' => 'N', 'tipo' => 'FACTURA', 'id_sucursal' => $norte]),
            'proveedor' => DB::table('proveedor')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Proveedor Uno', 'moneda_pago' => 'GTQ']),
            'bodegaNorte' => DB::table('bodega')->insertGetId(['id_empresa' => 1, 'nombre' => 'Bodega Norte', 'id_sucursal' => $norte]),
            'producto' => DB::table('producto')->insertGetId(['id_empresa' => 1, 'codigo' => 'P1', 'nombre' => 'Cemento', 'precio_compra' => 50]),
        ];
    }

    private function factura(array $cambios = []): object
    {
        $this->post(route('facturas.store'), array_merge([
            'id_serie' => $this->ids['serie'], 'id_cliente' => $this->ids['cliente'], 'moneda' => 'GTQ',
            'fecha_emision' => now()->format('Y-m-d'), 'fecha_vencimiento' => now()->addDays(30)->format('Y-m-d'),
            'lineas' => [['descripcion' => 'Servicio', 'cantidad' => 1, 'precio_unitario' => 1120, 'descuento' => 0, 'es_afecto_iva' => '1']],
        ], $cambios))->assertSessionHasNoErrors();

        return DB::table('factura')->orderByDesc('id_factura')->first();
    }

    public function test_factura_con_sucursal_y_serie_de_un_establecimiento(): void
    {
        $this->actingAs($this->crearUsuario(['id_sucursal' => $this->ids['norte']]));

        // Se propone la sucursal del usuario.
        $this->get(route('facturas.create'))->assertOk()->assertSee('Sucursal o agencia')
            ->assertSee('<option value="'.$this->ids['norte'].'" selected', false)->assertSee('Agencia Mixco (agencia de Norte)');

        $this->assertSame($this->ids['agencia'], (int) $this->factura(['id_sucursal' => $this->ids['agencia']])->id_sucursal);
        $this->post(route('facturas.store'), ['id_sucursal' => $this->ids['ajena']] + $this->datosMinimos())->assertSessionHasErrors('id_sucursal');

        // La serie N es del establecimiento Norte: aunque se elija otra sede, la factura queda en Norte.
        $f = $this->factura(['id_serie' => $this->ids['serieNorte'], 'id_sucursal' => $this->ids['central']]);
        $this->assertSame($this->ids['norte'], (int) $f->id_sucursal);
        $this->put(route('facturas.update', $f->id_factura), $this->datosMinimos(['id_sucursal' => $this->ids['central']]))->assertSessionHasNoErrors();
        $this->assertSame($this->ids['norte'], (int) DB::table('factura')->where('id_factura', $f->id_factura)->value('id_sucursal'));

        // El impreso muestra el establecimiento.
        $this->patch(route('facturas.emitir', $f->id_factura))->assertSessionHasNoErrors();
        $this->get(route('facturas.imprimir', $f->id_factura))->assertOk()->assertSee('Establecimiento 2 · Norte');
    }

    private function datosMinimos(array $cambios = []): array
    {
        return array_merge(['id_cliente' => $this->ids['cliente'], 'moneda' => 'GTQ', 'id_serie' => $this->ids['serie'],
            'fecha_emision' => now()->format('Y-m-d'), 'fecha_vencimiento' => now()->addDays(30)->format('Y-m-d'),
            'lineas' => [['descripcion' => 'Servicio', 'cantidad' => 1, 'precio_unitario' => 100, 'es_afecto_iva' => '1']]], $cambios);
    }

    public function test_filtros_y_exportacion_por_sucursal(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->factura(['id_sucursal' => $this->ids['central'], 'lineas' => [['descripcion' => 'De central', 'cantidad' => 1, 'precio_unitario' => 10, 'es_afecto_iva' => '1']]]);
        $deNorte = $this->factura(['id_sucursal' => $this->ids['norte']]);
        DB::table('factura')->insert(['id_empresa' => 1, 'id_cliente' => $this->ids['cliente'], 'numero_completo' => 'VIEJA-1', 'estado' => 'BORRADOR', 'tipo' => 'FACTURA', 'moneda' => 'GTQ']);

        $this->get(route('facturas.index', ['sucursal' => $this->ids['norte']]))->assertOk()->assertSee($deNorte->numero_completo)->assertDontSee('A-00000001')->assertDontSee('VIEJA-1');
        $this->get(route('facturas.index', ['sucursal' => 'sin']))->assertOk()->assertSee('VIEJA-1')->assertDontSee($deNorte->numero_completo);
        $csv = $this->get(route('facturas.exportar', ['sucursal' => $this->ids['norte']]))->streamedContent();
        $this->assertStringContainsString('Sucursal', $csv);
        $this->assertStringContainsString('Norte', $csv);
        $this->assertStringNotContainsString('Central', $csv);
    }

    public function test_orden_de_compra_toma_la_sucursal_de_su_bodega_y_el_ticket_la_suya(): void
    {
        $this->actingAs($this->crearUsuario());
        $orden = ['id_proveedor' => $this->ids['proveedor'], 'id_bodega' => $this->ids['bodegaNorte'], 'fecha_emision' => now()->format('Y-m-d'), 'moneda' => 'GTQ',
            'lineas' => [['id_producto' => $this->ids['producto'], 'cantidad_pedida' => 2, 'precio_unitario' => 50, 'descuento' => 0]]];

        $this->post(route('ordenes-compra.store'), $orden + ['id_sucursal' => ''])->assertSessionHasNoErrors();
        $this->assertSame($this->ids['norte'], (int) DB::table('orden_compra')->value('id_sucursal'));
        $this->post(route('ordenes-compra.store'), $orden + ['id_sucursal' => $this->ids['agencia']])->assertSessionHasNoErrors();
        $this->assertSame($this->ids['agencia'], (int) DB::table('orden_compra')->max('id_sucursal'));
        $this->post(route('ordenes-compra.store'), $orden + ['id_sucursal' => $this->ids['ajena']])->assertSessionHasErrors('id_sucursal');
        $this->get(route('ordenes-compra.index', ['sucursal' => $this->ids['agencia']]))->assertOk()->assertSee('Agencia Mixco');

        $cliente = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Condominio', 'activo' => true]);
        $ticket = ['id_cliente' => $cliente, 'asunto' => 'Fuga', 'descripcion' => 'Fuga en el sótano.', 'canal_origen' => 'TELEFONO', 'prioridad' => 'ALTA', 'tipo' => 'INCIDENTE'];
        $this->get(route('tickets.create'))->assertOk()->assertSee('<option value="'.$this->ids['central'].'" selected', false); // la casa matriz
        $this->post(route('tickets.store'), $ticket + ['id_sucursal' => $this->ids['norte']])->assertSessionHasNoErrors();
        $this->assertSame($this->ids['norte'], (int) DB::table('ticket')->value('id_sucursal'));
        $this->get(route('tickets.index', ['sucursal' => $this->ids['central']]))->assertOk()->assertDontSee('Fuga');
        $this->get(route('tickets.index', ['sucursal' => $this->ids['norte']]))->assertOk()->assertSee('Fuga');
    }

    public function test_serie_atada_a_un_establecimiento_y_sede_en_uso(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->post(route('series-facturacion.store'), ['codigo_serie' => 'M', 'tipo' => 'FACTURA', 'ultimo_numero' => 0, 'id_sucursal' => $this->ids['agencia'], 'activo' => '1'])->assertSessionHasNoErrors();
        $m = DB::table('serie_facturacion')->where('codigo_serie', 'M')->first();
        $this->assertSame($this->ids['agencia'], (int) $m->id_sucursal);
        $this->get(route('series-facturacion.index'))->assertOk()->assertSee('Agencia Mixco');

        // Con documentos, el establecimiento ya no cambia; y la sede ya no se puede eliminar.
        $this->factura(['id_serie' => $m->id_serie]);
        $this->put(route('series-facturacion.update', $m->id_serie), ['codigo_serie' => 'M', 'tipo' => 'FACTURA', 'ultimo_numero' => 1, 'id_sucursal' => $this->ids['central'], 'activo' => '1'])->assertSessionHasNoErrors();
        $this->assertSame($this->ids['agencia'], (int) DB::table('serie_facturacion')->where('id_serie', $m->id_serie)->value('id_sucursal'));
        $this->delete(route('sucursales.destroy', $this->ids['agencia']))->assertSessionHasErrors('sucursal');
    }

    public function test_resumen_por_sucursal(): void
    {
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');
        $this->darPermisos($vendedor, ['facturas.ver', 'facturas.crear', 'facturas.editar']);
        $this->actingAs($this->crearUsuario());
        foreach ([[$this->ids['norte'], 1120], [$this->ids['agencia'], 560], [$this->ids['agencia'], 560]] as [$sede, $monto]) {
            $f = $this->factura(['id_sucursal' => $sede, 'lineas' => [['descripcion' => 'S', 'cantidad' => 1, 'precio_unitario' => $monto, 'es_afecto_iva' => '1']]]);
            $this->patch(route('facturas.emitir', $f->id_factura))->assertSessionHasNoErrors();
        }
        DB::table('empleado')->insert([['id_empresa' => 1, 'primer_nombre' => 'Ana', 'estado' => 'ACTIVO', 'id_sucursal' => $this->ids['agencia']],
            ['id_empresa' => 1, 'primer_nombre' => 'Luis', 'estado' => 'BAJA', 'id_sucursal' => $this->ids['agencia']]]);

        // El administrador ve todas las secciones: cada sede con sus montos, la agencia en su propia fila.
        $this->get(route('por-sucursal.index'))->assertOk()
            ->assertSeeInOrder(['Central', 'Sin movimientos', 'Norte', '1,120.00', 'Agencia Mixco', '1,120.00', 'Total de la empresa', '2,240.00'])
            ->assertSee('Tickets y personal');
        $csv = $this->get(route('por-sucursal.exportar'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Agencia Mixco', $csv);
        $this->assertStringContainsString('Personal;"Agencia Mixco";;1', str_replace(['"Personal"', '"1"'], ['Personal', '1'], $csv));

        // Con solo permiso de facturas: ventas sí, tickets y personal no; y lo encuentra en el menú.
        $this->actingAs($vendedor)->get(route('por-sucursal.index'))->assertOk()->assertSee('Ventas y compras')->assertDontSee('Tickets y personal');
        $this->get(route('por-sucursal.exportar'))->assertForbidden();
        DB::table('modulo')->insert(['codigo' => 'por_sucursal', 'nombre' => 'Resumen por sucursal', 'ruta' => '/sistema/por-sucursal', 'activo' => true]);
        $this->assertTrue(MenuLateral::para($vendedor->fresh())->flatMap(fn ($g) => $g['items'])->contains(fn ($o) => $o['ruta'] === '/sistema/por-sucursal'));
        $sinPermiso = $this->crearUsuario(['username' => 'x', 'email' => 'x@nexus.test'], 'Bodega');
        $this->actingAs($sinPermiso)->get(route('por-sucursal.index'))->assertForbidden();
    }
}

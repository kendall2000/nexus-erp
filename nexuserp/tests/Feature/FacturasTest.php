<?php

namespace Tests\Feature;

use App\Models\Finanzas\PresupuestoAnual;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 5d: Facturas en Blade (numeración por serie, totales, emitir/anular y presupuesto de ingresos). */
class FacturasTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        DB::table('empresa')->update(['tasa_iva' => 12, 'iva_incluido_en_precio' => true]);
        $centro = DB::table('centro_costo')->insertGetId(['id_empresa' => 1, 'codigo' => 'VEN', 'nombre' => 'Ventas']);
        $cuenta = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'codigo' => '4.01', 'nombre' => 'Servicios', 'tipo' => 'INGRESO']);
        $linea = DB::table('linea_negocio')->insertGetId(['id_empresa' => 1, 'nombre' => 'Seguridad']);
        $this->ids = [
            'centro' => $centro,
            'cuenta' => $cuenta,
            'cliente' => DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Cliente Uno', 'nit' => '123', 'dias_credito' => 30, 'activo' => true]),
            'serie' => DB::table('serie_facturacion')->insertGetId(['id_empresa' => 1, 'codigo_serie' => 'A', 'tipo' => 'FACTURA', 'ultimo_numero' => 6]),
            'nc' => DB::table('serie_facturacion')->insertGetId(['id_empresa' => 1, 'codigo_serie' => 'NC', 'tipo' => 'NOTA_CREDITO']),
            // El servicio trae centro y cuenta: las líneas los heredan.
            'servicio' => DB::table('tipo_servicio')->insertGetId(['id_linea' => $linea, 'nombre' => 'Vigilancia', 'precio_base' => 500, 'id_centro_default' => $centro, 'id_cuenta_ingreso' => $cuenta]),
        ];
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'id_serie' => $this->ids['serie'], 'id_cliente' => $this->ids['cliente'], 'moneda' => 'GTQ',
            'fecha_emision' => now()->format('Y-m-d'), 'fecha_vencimiento' => now()->addDays(30)->format('Y-m-d'),
            'lineas' => [
                ['id_tipo_servicio' => $this->ids['servicio'], 'descripcion' => '', 'cantidad' => 2, 'precio_unitario' => 560, 'descuento' => 0, 'es_afecto_iva' => '1'],
                ['descripcion' => '', 'cantidad' => 1, 'precio_unitario' => 0], // vacía: se descarta
            ],
        ], $cambios);
    }

    private function factura(): object
    {
        return DB::table('factura')->orderByDesc('id_factura')->first();
    }

    private function presupuesto(float $monto = 10000): int
    {
        return DB::table('presupuesto_anual')->insertGetId(['id_empresa' => 1, 'id_centro' => $this->ids['centro'], 'id_cuenta' => $this->ids['cuenta'],
            'anio' => now()->year, 'total_presupuestado' => $monto, 'estado' => 'APROBADO']);
    }

    private function ejecutado(int $id): float
    {
        return round((float) DB::table('presupuesto_anual')->where('id_presupuesto', $id)->value('total_ejecutado'), 2);
    }

    public function test_crea_borrador_con_numero_de_la_serie_tipo_y_totales(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['tipo' => 'NOTA_CREDITO', 'estado' => 'PAGADA']))->assertSessionHasNoErrors();
        $f = $this->factura();
        // El tipo sale de la serie (no del formulario) y siempre nace en borrador.
        $this->assertSame(['FACTURA', 'BORRADOR', 7, 'A-00000007'], [$f->tipo, $f->estado, (int) $f->numero_factura, $f->numero_completo]);
        $this->assertSame(7, (int) DB::table('serie_facturacion')->where('id_serie', $this->ids['serie'])->value('ultimo_numero'));
        // IVA incluido: 1120 = base 1000 + IVA 120.
        $this->assertSame([1120.0, 1000.0, 120.0, 1120.0], [(float) $f->subtotal, (float) $f->base_imponible, (float) $f->iva, (float) $f->total]);
        $linea = DB::table('detalle_factura')->sole();
        $this->assertSame('Vigilancia', $linea->descripcion); // descripción tomada del servicio
    }

    public function test_el_descuento_global_se_aplica_antes_del_iva(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['descuento' => 112, 'lineas' => [
            ['descripcion' => 'Servicio', 'cantidad' => 1, 'precio_unitario' => 1120, 'es_afecto_iva' => '1'],
            ['descripcion' => 'Exento', 'cantidad' => 1, 'precio_unitario' => 280, 'es_afecto_iva' => '0'],
        ]]))->assertSessionHasNoErrors();

        $f = $this->factura();
        // Subtotal 1400; descuento 112 repartido 89.60 afecto / 22.40 exento; afecto neto 1030.40 → base 920, IVA 110.40.
        $this->assertSame([1400.0, 112.0, 1177.6, 110.4, 1288.0], [(float) $f->subtotal, (float) $f->descuento, round((float) $f->base_imponible, 2), round((float) $f->iva, 2), (float) $f->total]);
        $this->assertSame(round((float) $f->base_imponible + (float) $f->iva, 2), (float) $f->total); // cuadra

        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['descuento' => 5000]))->assertSessionHasErrors('descuento');
    }

    public function test_valida_cliente_serie_y_cuentas_de_mi_empresa(): void
    {
        $admin = $this->crearUsuario();
        $ajeno = DB::table('cliente')->insertGetId(['id_empresa' => 2, 'razon_social' => 'Ajeno']);
        $serieAjena = DB::table('serie_facturacion')->insertGetId(['id_empresa' => 2, 'codigo_serie' => 'Z', 'tipo' => 'FACTURA']);
        $recibo = DB::table('serie_facturacion')->insertGetId(['id_empresa' => 1, 'codigo_serie' => 'R', 'tipo' => 'RECIBO']);
        $gasto = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'codigo' => '5.01', 'nombre' => 'Gasto', 'tipo' => 'GASTO']);

        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['id_cliente' => $ajeno]))->assertSessionHasErrors('id_cliente');
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['id_serie' => $serieAjena]))->assertSessionHasErrors('id_serie');
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['id_serie' => $recibo]))->assertSessionHasErrors('id_serie');
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['lineas' => [['descripcion' => 'X', 'cantidad' => 1, 'precio_unitario' => 10, 'id_cuenta' => $gasto]]]))->assertSessionHasErrors('lineas.0.id_cuenta');
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['fecha_vencimiento' => now()->subDay()->format('Y-m-d')]))->assertSessionHasErrors('fecha_vencimiento');
        $this->assertSame(0, DB::table('factura')->count());
        $this->assertSame(6, (int) DB::table('serie_facturacion')->where('id_serie', $this->ids['serie'])->value('ultimo_numero'));
    }

    public function test_emitir_ejecuta_presupuesto_y_anular_lo_revierte(): void
    {
        $admin = $this->crearUsuario();
        $p = $this->presupuesto();
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos());
        $id = $this->factura()->id_factura;

        $this->actingAs($admin)->patch(route('facturas.emitir', $id))->assertSessionHasNoErrors();
        $this->assertSame(['EMITIDA', 1120.0], [$this->factura()->estado, (float) $this->factura()->saldo_pendiente]);
        $mes = 'eje_'.PresupuestoAnual::MESES[now()->month];
        $this->assertSame([1000.0, 1000.0], [round((float) DB::table('presupuesto_anual')->value($mes), 2), $this->ejecutado($p)]); // base sin IVA

        $this->actingAs($admin)->put(route('facturas.update', $id), $this->datos())->assertSessionHasErrors('factura');
        $this->actingAs($admin)->patch(route('facturas.enviar', $id))->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('facturas.anular', $id), ['motivo' => ''])->assertSessionHasErrors('motivo');
        $this->actingAs($admin)->patch(route('facturas.anular', $id), ['motivo' => 'Error en el cliente'])->assertSessionHasNoErrors();

        $f = $this->factura();
        $this->assertSame(['ANULADA', 0.0, (int) $admin->id_usuario], [$f->estado, (float) $f->saldo_pendiente, (int) $f->anulada_por]);
        $this->assertStringContainsString('Error en el cliente', $f->notas);
        $this->assertSame(0.0, $this->ejecutado($p));
    }

    public function test_anular_un_borrador_no_toca_el_presupuesto(): void
    {
        $admin = $this->crearUsuario();
        $p = $this->presupuesto();
        DB::table('presupuesto_anual')->update(['total_ejecutado' => 300]);
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos());

        // Antes el hook restaba al anular aunque la factura nunca se hubiera emitido.
        $this->actingAs($admin)->patch(route('facturas.anular', $this->factura()->id_factura), ['motivo' => 'Duplicada'])->assertSessionHasNoErrors();
        $this->assertSame(300.0, $this->ejecutado($p));
    }

    public function test_no_anula_con_pagos_ni_emite_en_cero(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['lineas' => [['descripcion' => 'Cortesía', 'cantidad' => 1, 'precio_unitario' => 0]]]));
        $this->actingAs($admin)->patch(route('facturas.emitir', $this->factura()->id_factura))->assertSessionHasErrors('factura');

        $this->actingAs($admin)->post(route('facturas.store'), $this->datos());
        $id = $this->factura()->id_factura;
        $this->actingAs($admin)->patch(route('facturas.emitir', $id));
        DB::table('pago')->insert(['id_empresa' => 1, 'id_cliente' => $this->ids['cliente'], 'id_factura' => $id, 'monto' => 100, 'fecha_pago' => now()]);
        $this->actingAs($admin)->patch(route('facturas.anular', $id), ['motivo' => 'Intento de anular'])->assertSessionHasErrors('factura');
        $this->assertSame('EMITIDA', $this->factura()->estado);
    }

    public function test_solo_se_elimina_el_ultimo_borrador_de_la_serie(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos());
        $primero = $this->factura()->id_factura;
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos());
        $segundo = $this->factura()->id_factura;

        $this->actingAs($admin)->delete(route('facturas.destroy', $primero))->assertSessionHasErrors('factura');
        $this->actingAs($admin)->delete(route('facturas.destroy', $segundo))->assertSessionHasNoErrors();
        $this->assertSame(7, (int) DB::table('serie_facturacion')->where('id_serie', $this->ids['serie'])->value('ultimo_numero'));

        // El número liberado se vuelve a usar.
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos());
        $this->assertSame('A-00000008', $this->factura()->numero_completo);
    }

    public function test_nota_de_credito_resta_del_presupuesto_y_no_deja_saldo(): void
    {
        $admin = $this->crearUsuario();
        $p = $this->presupuesto();
        DB::table('presupuesto_anual')->update(['total_ejecutado' => 1500]);
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['id_serie' => $this->ids['nc']]));
        $nc = $this->factura();
        $this->assertSame(['NOTA_CREDITO', 'NC-00000001', 0.0], [$nc->tipo, $nc->numero_completo, (float) $nc->saldo_pendiente]);

        $this->actingAs($admin)->patch(route('facturas.emitir', $nc->id_factura))->assertSessionHasNoErrors();
        $this->assertSame(500.0, $this->ejecutado($p));
    }

    public function test_pantallas_cartera_y_permisos(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('facturas.store'), $this->datos(['fecha_emision' => now()->subDays(50)->format('Y-m-d'), 'fecha_vencimiento' => now()->subDays(40)->format('Y-m-d')]));
        $id = $this->factura()->id_factura;
        $this->actingAs($admin)->patch(route('facturas.emitir', $id));

        $this->actingAs($admin)->get(route('facturas.index'))->assertOk()->assertSee('Cartera por cobrar')->assertSee('1,120.00')->assertSee('Vencida');
        $this->actingAs($admin)->get(route('facturas.index', ['estado' => 'vencidas']))->assertOk()->assertSee('A-00000007');
        $this->actingAs($admin)->get(route('facturas.show', $id))->assertOk()->assertSee('Vencida hace 40 días')->assertSee('Vigilancia');
        $this->actingAs($admin)->get(route('facturas.imprimir', $id))->assertOk()->assertSee('FACTURA')->assertSee('1,120.00');
        $this->actingAs($admin)->get(route('facturas.create'))->assertOk()->assertSee('sigue A-00000008');
        $this->assertStringContainsString('A-00000007', $this->actingAs($admin)->get(route('facturas.exportar'))->streamedContent());

        $cajero = $this->crearUsuario(['username' => 'c', 'email' => 'c@nexus.test'], 'Caja');
        $this->darPermisos($cajero, ['facturas.ver']);
        $this->actingAs($cajero)->get(route('facturas.show', $id))->assertOk()->assertDontSee('Anular')->assertDontSee('Imprimir');
        $this->actingAs($cajero)->patch(route('facturas.anular', $id), ['motivo' => 'Sin permiso'])->assertForbidden();
        $this->actingAs($cajero)->post(route('facturas.store'), $this->datos())->assertForbidden();

        $ajena = DB::table('factura')->insertGetId(['id_empresa' => 2, 'id_cliente' => 1, 'numero_completo' => 'Z-1']);
        $this->actingAs($admin)->get(route('facturas.show', $ajena))->assertNotFound();
    }

    public function test_la_api_de_facturas_ya_no_existe(): void
    {
        $this->actingAs($this->crearUsuario())->getJson('/api/v1/finanzas/facturas')->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 5e: Pagos en Blade (cobrar, revertir, devolver, acreditar) y condonar en facturas. */
class PagosTest extends TestCase
{
    use EsquemaNexus;

    private int $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $this->cliente = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Cliente Uno', 'nit' => '123', 'activo' => true]);
    }

    private function factura(array $datos = []): int
    {
        return DB::table('factura')->insertGetId($datos + [
            'id_empresa' => 1, 'id_cliente' => $this->cliente, 'numero_completo' => 'A-00000001', 'tipo' => 'FACTURA', 'moneda' => 'GTQ',
            'fecha_emision' => now()->subDays(5)->format('Y-m-d'), 'fecha_vencimiento' => now()->addDays(25)->format('Y-m-d'),
            'total' => 1000, 'saldo_pendiente' => 1000, 'estado' => 'ENVIADA',
        ]);
    }

    private function cobro(int $factura, array $cambios = []): array
    {
        return array_merge(['id_factura' => $factura, 'forma_pago' => 'TRANSFERENCIA', 'monto' => 400, 'fecha_pago' => now()->format('Y-m-d'), 'referencia' => 'TRF-1'], $cambios);
    }

    private function estadoFactura(int $id): array
    {
        $f = DB::table('factura')->where('id_factura', $id)->first();

        return [$f->estado, (float) $f->total_pagado, (float) $f->saldo_pendiente];
    }

    public function test_cobro_parcial_y_total_mueven_saldo_y_estado(): void
    {
        $admin = $this->crearUsuario();
        $f = $this->factura();

        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f, ['moneda' => 'USD']))->assertSessionHasNoErrors();
        $this->assertSame(['PARCIAL', 400.0, 600.0], $this->estadoFactura($f));
        $pago = DB::table('pago')->first();
        $this->assertSame(['GTQ', 'APLICADO', $this->cliente], [$pago->moneda, $pago->estado, (int) $pago->id_cliente]); // moneda de la factura

        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f, ['monto' => 600.01]))->assertSessionHasErrors('monto');
        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f, ['monto' => 600, 'forma_pago' => 'EFECTIVO', 'referencia' => '']))->assertSessionHasNoErrors();
        $this->assertSame(['PAGADA', 1000.0, 0.0], $this->estadoFactura($f));

        // Ya pagada: no admite más cobros.
        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f, ['monto' => 1]))->assertSessionHasErrors('id_factura');
    }

    public function test_valida_referencia_fechas_y_facturas_cobrables(): void
    {
        $admin = $this->crearUsuario();
        $f = $this->factura();

        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f, ['referencia' => '']))->assertSessionHasErrors('referencia');
        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f, ['fecha_pago' => now()->addDay()->format('Y-m-d')]))->assertSessionHasErrors('fecha_pago');
        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f, ['fecha_pago' => now()->subDays(30)->format('Y-m-d')]))->assertSessionHasErrors('fecha_pago');

        $borrador = $this->factura(['estado' => 'BORRADOR', 'numero_completo' => 'A-2']);
        $ajena = $this->factura(['id_empresa' => 2, 'numero_completo' => 'Z-1']);
        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($borrador))->assertSessionHasErrors('id_factura');
        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($ajena))->assertSessionHasErrors('id_factura');
        $this->assertSame(0, DB::table('pago')->count());
    }

    public function test_revertir_y_devolver_no_borran_y_devuelven_el_saldo(): void
    {
        $admin = $this->crearUsuario();
        $f = $this->factura();
        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f, ['monto' => 1000]));
        $pago = DB::table('pago')->value('id_pago');
        $this->assertSame('PAGADA', $this->estadoFactura($f)[0]);

        $this->actingAs($admin)->patch(route('pagos.devolver', $pago), ['motivo' => ''])->assertSessionHasErrors('motivo');
        $this->actingAs($admin)->patch(route('pagos.devolver', $pago), ['motivo' => 'Cliente pagó dos veces'])->assertSessionHasNoErrors();

        $p = DB::table('pago')->sole(); // sigue existiendo
        $this->assertSame(['DEVUELTO', (int) $admin->id_usuario, 'Cliente pagó dos veces'], [$p->estado, (int) $p->revertido_por, $p->motivo_reversion]);
        $this->assertSame(['EMITIDA', 0.0, 1000.0], $this->estadoFactura($f));

        // No se deshace dos veces.
        $this->actingAs($admin)->patch(route('pagos.revertir', $pago), ['motivo' => 'Otra vez'])->assertSessionHasErrors('pago');

        // Con el cobro devuelto la factura ya se puede anular.
        $this->actingAs($admin)->patch(route('facturas.anular', $f), ['motivo' => 'Se facturó por error'])->assertSessionHasNoErrors();
        $this->assertSame('ANULADA', $this->estadoFactura($f)[0]);
    }

    public function test_condonar_saldo(): void
    {
        $admin = $this->crearUsuario();
        $f = $this->factura();
        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f, ['monto' => 900]));

        $this->actingAs($admin)->patch(route('facturas.condonar', $f), ['monto' => 150, 'motivo' => 'Acuerdo comercial'])->assertSessionHasErrors('monto');
        $this->actingAs($admin)->patch(route('facturas.condonar', $f), ['monto' => 100, 'motivo' => 'Acuerdo comercial'])->assertSessionHasNoErrors();
        $fila = DB::table('factura')->where('id_factura', $f)->first();
        $this->assertSame(['PAGADA', 0.0, 100.0], [$fila->estado, (float) $fila->saldo_pendiente, (float) $fila->monto_condonado]);
        $this->assertStringContainsString('Acuerdo comercial', $fila->notas);

        // Si luego se revierte el cobro, lo condonado se mantiene aparte.
        $this->actingAs($admin)->patch(route('pagos.revertir', DB::table('pago')->value('id_pago')), ['motivo' => 'Cheque rechazado']);
        $this->assertSame(['PARCIAL', 0.0, 900.0], $this->estadoFactura($f));
        // Y con saldo condonado ya no se anula.
        $this->actingAs($admin)->patch(route('facturas.anular', $f), ['motivo' => 'Intento'])->assertSessionHasErrors('factura');
    }

    public function test_acreditar_pantallas_y_recibo(): void
    {
        $admin = $this->crearUsuario();
        $f = $this->factura();
        $this->actingAs($admin)->post(route('pagos.store'), $this->cobro($f));
        $pago = DB::table('pago')->value('id_pago');

        $this->actingAs($admin)->get(route('pagos.index'))->assertOk()->assertSee('GTQ 400.00')->assertSee('Por acreditar en banco');
        $this->actingAs($admin)->patch(route('pagos.acreditar', $pago), ['fecha_acreditado' => now()->subDays(1)->format('Y-m-d')])->assertSessionHasErrors('fecha_acreditado');
        $this->actingAs($admin)->patch(route('pagos.acreditar', $pago), ['fecha_acreditado' => now()->format('Y-m-d')])->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('pago')->value('fecha_acreditado'));

        $this->actingAs($admin)->get(route('pagos.show', $pago))->assertOk()->assertSee('TRF-1')->assertSee('A-00000001');
        $this->actingAs($admin)->get(route('pagos.imprimir', $pago))->assertOk()->assertSee('RECIBO DE CAJA')->assertSee('400.00');
        $this->actingAs($admin)->get(route('pagos.create', ['factura' => $f]))->assertOk()->assertSee('saldo GTQ 600.00');
        $this->actingAs($admin)->get(route('facturas.show', $f))->assertOk()->assertSee('Registrar cobro')->assertSee('Condonar');
        $this->assertStringContainsString('TRF-1', $this->actingAs($admin)->get(route('pagos.exportar'))->streamedContent());
    }

    public function test_permisos_cobrar_desde_facturas_y_otra_empresa(): void
    {
        $f = $this->factura();
        // Con facturas.cobrar basta para registrar el cobro, aunque no tenga pagos.crear.
        $cajero = $this->crearUsuario(['username' => 'c', 'email' => 'c@nexus.test'], 'Caja');
        $this->darPermisos($cajero, ['facturas.ver', 'facturas.cobrar', 'pagos.ver']);
        $this->actingAs($cajero)->post(route('pagos.store'), $this->cobro($f))->assertSessionHasNoErrors();
        $pago = DB::table('pago')->value('id_pago');

        $this->actingAs($cajero)->get(route('pagos.show', $pago))->assertOk()->assertDontSee('Revertir')->assertDontSee('Devolver dinero');
        $this->actingAs($cajero)->patch(route('pagos.revertir', $pago), ['motivo' => 'Sin permiso'])->assertForbidden();
        $this->actingAs($cajero)->patch(route('facturas.condonar', $f), ['monto' => 1, 'motivo' => 'Sin permiso'])->assertForbidden();

        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Lector');
        $this->darPermisos($lector, ['pagos.ver']);
        $this->actingAs($lector)->post(route('pagos.store'), $this->cobro($f))->assertForbidden();

        $ajeno = DB::table('pago')->insertGetId(['id_empresa' => 2, 'monto' => 5, 'fecha_pago' => now()]);
        $this->actingAs($cajero)->get(route('pagos.show', $ajeno))->assertNotFound();
    }

    public function test_la_api_de_pagos_ya_no_existe(): void
    {
        $this->actingAs($this->crearUsuario())->getJson('/api/v1/finanzas/pagos')->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Finanzas\PresupuestoAnual;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 4c: Órdenes de compra en Blade y ejecución del presupuesto (aprobar / cancelar). */
class OrdenesCompraTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $centro = DB::table('centro_costo')->insertGetId(['id_empresa' => 1, 'codigo' => 'CC1', 'nombre' => 'Operaciones']);
        $cuenta = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'codigo' => '6101', 'nombre' => 'Materiales', 'tipo' => 'GASTO']);
        $this->ids = [
            'centro' => $centro,
            'cuenta' => $cuenta,
            'proveedor' => DB::table('proveedor')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Proveedor Uno', 'moneda_pago' => 'GTQ']),
            'bodega' => DB::table('bodega')->insertGetId(['id_empresa' => 1, 'nombre' => 'Central']),
            // El producto trae su centro y cuenta: las líneas los heredan.
            'producto' => DB::table('producto')->insertGetId(['id_empresa' => 1, 'codigo' => 'P1', 'nombre' => 'Cemento', 'precio_compra' => 50,
                'id_centro_default' => $centro, 'id_cuenta_gasto' => $cuenta]),
        ];
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'id_proveedor' => $this->ids['proveedor'], 'id_bodega' => $this->ids['bodega'],
            'fecha_emision' => now()->format('Y-m-d'), 'moneda' => 'GTQ',
            'lineas' => [['id_producto' => $this->ids['producto'], 'cantidad_pedida' => 2, 'precio_unitario' => 50, 'descuento' => 0], ['id_producto' => '']],
        ], $cambios);
    }

    private function presupuesto(float $monto, string $estado = 'APROBADO'): int
    {
        return DB::table('presupuesto_anual')->insertGetId([
            'id_empresa' => 1, 'id_centro' => $this->ids['centro'], 'id_cuenta' => $this->ids['cuenta'], 'anio' => now()->year,
            'total_presupuestado' => $monto, 'estado' => $estado,
        ]);
    }

    private function ejecutado(int $idPresupuesto): array
    {
        $p = DB::table('presupuesto_anual')->where('id_presupuesto', $idPresupuesto)->first();
        $mes = 'eje_'.PresupuestoAnual::MESES[now()->month];

        return [round((float) $p->{$mes}, 2), round((float) $p->total_ejecutado, 2)];
    }

    private function orden(): object
    {
        return DB::table('orden_compra')->orderByDesc('id_oc')->first();
    }

    public function test_siempre_nace_en_borrador_con_numero_y_totales(): void
    {
        $admin = $this->crearUsuario();

        // Aunque se envíe «RECIBIDA», la orden se crea en borrador (antes se podía saltar la aprobación).
        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos(['estado' => 'RECIBIDA']))->assertSessionHasNoErrors();

        $oc = $this->orden();
        $this->assertSame('BORRADOR', $oc->estado);
        $this->assertSame('OC-'.now()->year.'-0001', $oc->numero_oc);
        $this->assertSame([100.0, 12.0, 112.0], [(float) $oc->subtotal, (float) $oc->iva, (float) $oc->total]);
        $this->assertSame(1, DB::table('detalle_orden_compra')->count()); // la fila vacía se descarta

        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos());
        $this->assertSame('OC-'.now()->year.'-0002', $this->orden()->numero_oc);
    }

    public function test_iva_incluido_en_el_precio(): void
    {
        DB::table('empresa')->update(['iva_incluido_en_precio' => true]);

        $this->actingAs($this->crearUsuario())->post(route('ordenes-compra.store'), $this->datos(['lineas' => [['id_producto' => $this->ids['producto'], 'cantidad_pedida' => 1, 'precio_unitario' => 112]]]));

        $oc = $this->orden();
        $this->assertSame([112.0, 12.0, 112.0], [(float) $oc->subtotal, round((float) $oc->iva, 2), (float) $oc->total]);
    }

    public function test_valida_productos_de_mi_empresa_y_descuentos(): void
    {
        $admin = $this->crearUsuario();
        $ajeno = DB::table('producto')->insertGetId(['id_empresa' => 2, 'codigo' => 'X', 'nombre' => 'Ajeno']);

        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos(['lineas' => [['id_producto' => $ajeno, 'cantidad_pedida' => 1, 'precio_unitario' => 1]]]))
            ->assertSessionHasErrors('lineas.0.id_producto');
        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos(['lineas' => [['id_producto' => $this->ids['producto'], 'cantidad_pedida' => 1, 'precio_unitario' => 10, 'descuento' => 20]]]))
            ->assertSessionHasErrors('lineas.0.descuento');
        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos(['lineas' => [['id_producto' => '']]]))->assertSessionHasErrors('lineas');
        $this->assertSame(0, DB::table('orden_compra')->count());
    }

    public function test_aprobar_ejecuta_el_presupuesto_y_cancelar_lo_revierte(): void
    {
        $admin = $this->crearUsuario();
        $idPresupuesto = $this->presupuesto(1000);
        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos());
        $oc = $this->orden();

        $this->actingAs($admin)->patch(route('ordenes-compra.aprobar', $oc->id_oc))->assertSessionHasNoErrors();
        $this->assertSame('ENVIADA', $this->orden()->estado);
        // Base sin IVA (100) en el mes y en el total a la vez (antes el total no se movía).
        $this->assertSame([100.0, 100.0], $this->ejecutado($idPresupuesto));

        // Ya aprobada no se edita.
        $this->actingAs($admin)->put(route('ordenes-compra.update', $oc->id_oc), $this->datos())->assertSessionHasErrors('orden');

        $this->actingAs($admin)->patch(route('ordenes-compra.cancelar', $oc->id_oc))->assertSessionHasNoErrors();
        $this->assertSame('CANCELADA', $this->orden()->estado);
        $this->assertSame([0.0, 0.0], $this->ejecutado($idPresupuesto));
    }

    public function test_sobregiro_pide_confirmacion_y_forzar_aprueba(): void
    {
        $admin = $this->crearUsuario();
        $idPresupuesto = $this->presupuesto(50);
        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos());
        $oc = $this->orden();

        $this->actingAs($admin)->patch(route('ordenes-compra.aprobar', $oc->id_oc))->assertSessionHas('sobregiros');
        $this->assertSame('BORRADOR', $this->orden()->estado);
        $this->assertSame([0.0, 0.0], $this->ejecutado($idPresupuesto));

        $this->actingAs($admin)->patch(route('ordenes-compra.aprobar', $oc->id_oc), ['forzar' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('ENVIADA', $this->orden()->estado);
        $this->assertSame([100.0, 100.0], $this->ejecutado($idPresupuesto));
    }

    public function test_presupuesto_no_aprobado_no_se_toca_al_aprobar_ni_al_cancelar(): void
    {
        $admin = $this->crearUsuario();
        $borrador = $this->presupuesto(1000, 'BORRADOR');
        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos());
        $oc = $this->orden();

        $this->actingAs($admin)->patch(route('ordenes-compra.aprobar', $oc->id_oc));
        $this->actingAs($admin)->patch(route('ordenes-compra.cancelar', $oc->id_oc));

        // Antes la cancelación restaba aunque nunca se hubiera sumado (quedaba en negativo).
        $this->assertSame([0.0, 0.0], $this->ejecutado($borrador));
    }

    public function test_no_cancela_si_ya_se_recibio_mercaderia(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos());
        $oc = $this->orden();
        $this->actingAs($admin)->patch(route('ordenes-compra.aprobar', $oc->id_oc));
        DB::table('detalle_orden_compra')->update(['cantidad_recibida' => 1]);

        $this->actingAs($admin)->patch(route('ordenes-compra.cancelar', $oc->id_oc))->assertSessionHasErrors('orden');
        $this->assertSame('ENVIADA', $this->orden()->estado);
    }

    public function test_cada_accion_exige_su_permiso(): void
    {
        $comprador = $this->crearUsuario(['username' => 'c', 'email' => 'c@nexus.test'], 'Compras');
        $this->darPermisos($comprador, ['ordenes_compra.ver', 'ordenes_compra.crear', 'ordenes_compra.editar']);

        $this->actingAs($comprador)->post(route('ordenes-compra.store'), $this->datos())->assertSessionHasNoErrors();
        $oc = $this->orden();

        $this->actingAs($comprador)->get(route('ordenes-compra.show', $oc->id_oc))->assertOk()->assertDontSee('Aprobar')->assertDontSee('Imprimir');
        $this->actingAs($comprador)->patch(route('ordenes-compra.aprobar', $oc->id_oc))->assertForbidden();
        $this->actingAs($comprador)->patch(route('ordenes-compra.cancelar', $oc->id_oc))->assertForbidden();
        $this->actingAs($comprador)->get(route('ordenes-compra.imprimir', $oc->id_oc))->assertForbidden();
    }

    public function test_imprimir_y_exportar(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('ordenes-compra.store'), $this->datos());
        $oc = $this->orden();

        $this->actingAs($admin)->get(route('ordenes-compra.imprimir', $oc->id_oc))->assertOk()->assertSee('ORDEN DE COMPRA')->assertSee('Cemento');
        $this->assertStringContainsString($oc->numero_oc, $this->actingAs($admin)->get(route('ordenes-compra.exportar'))->streamedContent());
    }

    public function test_no_toca_ordenes_de_otra_empresa(): void
    {
        $ajena = DB::table('orden_compra')->insertGetId(['id_empresa' => 2, 'estado' => 'BORRADOR', 'fecha_emision' => now()]);

        $this->actingAs($this->crearUsuario())->get(route('ordenes-compra.show', $ajena))->assertNotFound();
    }

    public function test_registrar_ejecucion_actualiza_mes_y_total(): void
    {
        $id = $this->presupuesto(1000);
        $p = PresupuestoAnual::find($id);

        $p->registrarEjecucion(now()->month, 40);

        $this->assertSame([40.0, 40.0], $this->ejecutado($id));
        $this->assertSame(0, DB::table('presupuesto_anual')->where('id_presupuesto', '!=', $id)->count());
    }

    public function test_la_api_de_ordenes_ya_no_existe(): void
    {
        $this->actingAs($this->crearUsuario())->getJson('/api/v1/inventario/ordenes-compra')->assertNotFound();
    }
}

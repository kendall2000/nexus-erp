<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 5c: Presupuesto en Blade (borrador → aprobado → cerrado / reabrir, copiar de otro año). */
class PresupuestoTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $this->ids = [
            'centro' => DB::table('centro_costo')->insertGetId(['id_empresa' => 1, 'codigo' => 'ADM', 'nombre' => 'Administración']),
            'cuenta' => DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'codigo' => '5.01', 'nombre' => 'Sueldos', 'tipo' => 'GASTO']),
        ];
    }

    private function datos(array $cambios = []): array
    {
        $meses = [];
        foreach (\App\Models\Finanzas\PresupuestoAnual::MESES as $m) {
            $meses["pre_{$m}"] = 100;
        }

        return array_merge(['id_centro' => $this->ids['centro'], 'id_cuenta' => $this->ids['cuenta'], 'anio' => 2026, 'moneda' => 'GTQ'], $meses, $cambios);
    }

    private function presupuesto(): object
    {
        return DB::table('presupuesto_anual')->orderByDesc('id_presupuesto')->first();
    }

    public function test_crea_en_borrador_con_total_y_sin_duplicados(): void
    {
        $admin = $this->crearUsuario();

        // Aunque se envíen estado y ejecución, nace en borrador y sin ejecutar.
        $this->actingAs($admin)->post(route('presupuesto.store'), $this->datos(['estado' => 'APROBADO', 'eje_enero' => 999]))->assertSessionHasNoErrors();
        $p = $this->presupuesto();
        $this->assertSame(['BORRADOR', 1200.0, 0.0], [$p->estado, (float) $p->total_presupuestado, (float) $p->total_ejecutado]);

        $this->actingAs($admin)->post(route('presupuesto.store'), $this->datos())->assertSessionHasErrors('id_cuenta');
        $this->actingAs($admin)->post(route('presupuesto.store'), $this->datos(['anio' => 2027]))->assertSessionHasNoErrors();

        // Editar para chocar con otra partida existente también es duplicado.
        $this->actingAs($admin)->put(route('presupuesto.update', $this->presupuesto()->id_presupuesto), $this->datos(['anio' => 2026]))->assertSessionHasErrors('id_cuenta');
    }

    public function test_solo_centros_y_cuentas_validos_de_mi_empresa(): void
    {
        $admin = $this->crearUsuario();
        $centroAjeno = DB::table('centro_costo')->insertGetId(['id_empresa' => 2, 'codigo' => 'X', 'nombre' => 'Ajeno']);
        $agrupacion = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'codigo' => '5', 'nombre' => 'Gastos', 'tipo' => 'GASTO', 'permite_movimiento' => false]);
        $activo = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'codigo' => '1.01', 'nombre' => 'Caja', 'tipo' => 'ACTIVO']);

        $this->actingAs($admin)->post(route('presupuesto.store'), $this->datos(['id_centro' => $centroAjeno]))->assertSessionHasErrors('id_centro');
        $this->actingAs($admin)->post(route('presupuesto.store'), $this->datos(['id_cuenta' => $agrupacion]))->assertSessionHasErrors('id_cuenta');
        $this->actingAs($admin)->post(route('presupuesto.store'), $this->datos(['id_cuenta' => $activo]))->assertSessionHasErrors('id_cuenta');
        $this->assertSame(0, DB::table('presupuesto_anual')->count());
    }

    public function test_flujo_aprobar_cerrar_reabrir(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('presupuesto.store'), $this->datos(array_fill_keys(array_map(fn ($m) => "pre_{$m}", \App\Models\Finanzas\PresupuestoAnual::MESES), 0)));
        $id = $this->presupuesto()->id_presupuesto;

        $this->actingAs($admin)->patch(route('presupuesto.aprobar', $id))->assertSessionHasErrors('presupuesto'); // en cero
        $this->actingAs($admin)->put(route('presupuesto.update', $id), $this->datos())->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('presupuesto.cerrar', $id))->assertSessionHasErrors('presupuesto'); // aún borrador

        $this->actingAs($admin)->patch(route('presupuesto.aprobar', $id))->assertSessionHasNoErrors();
        $this->assertSame(['APROBADO', (int) $admin->id_usuario], [$this->presupuesto()->estado, (int) $this->presupuesto()->aprobado_por]);
        $this->actingAs($admin)->put(route('presupuesto.update', $id), $this->datos(['pre_enero' => 5]))->assertSessionHasErrors('presupuesto');
        $this->actingAs($admin)->delete(route('presupuesto.destroy', $id))->assertSessionHasErrors('presupuesto');

        $this->actingAs($admin)->patch(route('presupuesto.cerrar', $id))->assertSessionHasNoErrors();
        $this->assertSame('CERRADO', $this->presupuesto()->estado);
        $this->actingAs($admin)->patch(route('presupuesto.reabrir', $id))->assertSessionHasNoErrors();
        $this->assertSame(['APROBADO', null], [$this->presupuesto()->estado, $this->presupuesto()->fecha_cierre]);
    }

    public function test_copiar_de_otro_anio_con_ajuste(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('presupuesto.store'), $this->datos(['anio' => 2025]));
        DB::table('presupuesto_anual')->update(['estado' => 'APROBADO', 'eje_enero' => 50, 'total_ejecutado' => 50]);

        $this->actingAs($admin)->post(route('presupuesto.clonar'), ['anio_origen' => 2025, 'anio_destino' => 2026, 'incremento' => 10])->assertSessionHasNoErrors();
        $nuevo = $this->presupuesto();
        $this->assertSame([2026, 'BORRADOR', 1320.0, 0.0], [(int) $nuevo->anio, $nuevo->estado, (float) $nuevo->total_presupuestado, (float) $nuevo->total_ejecutado]);

        // Repetir no duplica; un año vacío avisa.
        $this->actingAs($admin)->post(route('presupuesto.clonar'), ['anio_origen' => 2025, 'anio_destino' => 2026]);
        $this->assertSame(2, DB::table('presupuesto_anual')->count());
        $this->actingAs($admin)->post(route('presupuesto.clonar'), ['anio_origen' => 2010, 'anio_destino' => 2026])->assertSessionHasErrors('anio_origen');
    }

    public function test_pantallas_resumen_y_ordenes_que_ejecutan(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->post(route('presupuesto.store'), $this->datos());
        $id = $this->presupuesto()->id_presupuesto;
        DB::table('presupuesto_anual')->update(['estado' => 'APROBADO', 'eje_marzo' => 40, 'total_ejecutado' => 40]);
        // Orden aprobada cuya línea hereda centro y cuenta del producto.
        $producto = DB::table('producto')->insertGetId(['id_empresa' => 1, 'nombre' => 'Papel', 'id_centro_default' => $this->ids['centro'], 'id_cuenta_gasto' => $this->ids['cuenta']]);
        $oc = DB::table('orden_compra')->insertGetId(['id_empresa' => 1, 'numero_oc' => 'OC-2026-0007', 'fecha_emision' => '2026-03-10', 'estado' => 'ENVIADA']);
        DB::table('detalle_orden_compra')->insert(['id_oc' => $oc, 'id_producto' => $producto, 'cantidad_pedida' => 1, 'precio_unitario' => 40, 'subtotal' => 40]);
        $borrador = DB::table('orden_compra')->insertGetId(['id_empresa' => 1, 'numero_oc' => 'OC-2026-0008', 'fecha_emision' => '2026-03-10', 'estado' => 'BORRADOR']);
        DB::table('detalle_orden_compra')->insert(['id_oc' => $borrador, 'id_producto' => $producto, 'cantidad_pedida' => 1, 'precio_unitario' => 40, 'subtotal' => 40]);

        $this->actingAs($admin)->get(route('presupuesto.index', ['anio' => 2026]))->assertOk()
            ->assertSee('GTQ 1,200.00')->assertSee('3.3 %')->assertSee('grafico-presupuesto');
        $this->actingAs($admin)->get(route('presupuesto.show', $id))->assertOk()->assertSee('OC-2026-0007')->assertDontSee('OC-2026-0008');
        $csv = $this->actingAs($admin)->get(route('presupuesto.exportar', ['anio' => 2026]))->streamedContent();
        $this->assertStringContainsString('ADM — Administración', $csv);
    }

    public function test_permisos_y_otra_empresa(): void
    {
        $analista = $this->crearUsuario(['username' => 'a', 'email' => 'a@nexus.test'], 'Finanzas');
        $this->darPermisos($analista, ['presupuesto.ver', 'presupuesto.crear', 'presupuesto.editar']);
        $this->actingAs($analista)->post(route('presupuesto.store'), $this->datos())->assertSessionHasNoErrors();
        $id = $this->presupuesto()->id_presupuesto;

        $this->actingAs($analista)->get(route('presupuesto.show', $id))->assertOk()->assertDontSee('Aprobar');
        $this->actingAs($analista)->patch(route('presupuesto.aprobar', $id))->assertForbidden();
        $this->actingAs($analista)->get(route('presupuesto.exportar'))->assertForbidden();

        $ajeno = DB::table('presupuesto_anual')->insertGetId(['id_empresa' => 2, 'id_centro' => 1, 'id_cuenta' => 1, 'anio' => 2026]);
        $this->actingAs($analista)->get(route('presupuesto.show', $ajeno))->assertNotFound();
    }

    public function test_la_api_de_presupuesto_ya_no_existe(): void
    {
        $this->actingAs($this->crearUsuario())->getJson('/api/v1/finanzas/presupuestos')->assertNotFound();
    }
}

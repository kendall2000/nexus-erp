<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 7a: Kardex (movimientos manuales, traslados y saldo acumulado). */
class KardexTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $this->ids = [
            'central' => DB::table('bodega')->insertGetId(['id_empresa' => 1, 'nombre' => 'Central']),
            'sucursal' => DB::table('bodega')->insertGetId(['id_empresa' => 1, 'nombre' => 'Sucursal']),
            'cemento' => DB::table('producto')->insertGetId(['id_empresa' => 1, 'codigo' => 'P1', 'nombre' => 'Cemento']),
            'leche' => DB::table('producto')->insertGetId(['id_empresa' => 1, 'codigo' => 'P2', 'nombre' => 'Leche', 'requiere_lote' => true, 'es_perecedero' => true]),
        ];
    }

    private function mover(array $datos)
    {
        return $this->post(route('movimientos.store'), $datos + ['id_producto' => $this->ids['cemento'], 'id_bodega' => $this->ids['central'], 'motivo' => 'Conteo físico']);
    }

    private function stock(int $bodega, ?int $producto = null): array
    {
        $s = DB::table('stock_bodega')->where(['id_producto' => $producto ?? $this->ids['cemento'], 'id_bodega' => $bodega])->first();

        return [(float) $s?->cantidad_actual, (float) $s?->costo_promedio];
    }

    public function test_entrada_salida_y_baja_mueven_la_existencia(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->mover(['tipo' => 'AJUSTE_ENTRADA', 'cantidad' => 10, 'costo_unitario' => 50])->assertSessionHasNoErrors();
        $this->actingAs($admin)->mover(['tipo' => 'AJUSTE_ENTRADA', 'cantidad' => 10, 'costo_unitario' => 70]);
        $this->assertSame([20.0, 60.0], $this->stock($this->ids['central'])); // costo promedio

        $this->actingAs($admin)->mover(['tipo' => 'SALIDA', 'cantidad' => 5])->assertSessionHasNoErrors();
        $this->actingAs($admin)->mover(['tipo' => 'BAJA', 'cantidad' => 3, 'motivo' => 'Sacos rotos'])->assertSessionHasNoErrors();
        $this->assertSame([12.0, 60.0], $this->stock($this->ids['central']));

        $baja = DB::table('movimiento_inventario')->where('tipo_movimiento', 'BAJA')->first();
        $this->assertSame(['AJUSTE', 'Sacos rotos', 60.0, (int) $admin->id_usuario], [$baja->referencia_tipo, $baja->observaciones, (float) $baja->costo_unitario, (int) $baja->created_by]);
    }

    public function test_no_deja_la_existencia_en_negativo(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->mover(['tipo' => 'AJUSTE_ENTRADA', 'cantidad' => 4, 'costo_unitario' => 10]);

        $this->actingAs($admin)->mover(['tipo' => 'SALIDA', 'cantidad' => 4.5])->assertSessionHasErrors('cantidad');
        $this->actingAs($admin)->mover(['tipo' => 'TRASLADO', 'cantidad' => 5, 'id_bodega_destino' => $this->ids['sucursal']])->assertSessionHasErrors('cantidad');
        $this->assertSame([4.0, 10.0], $this->stock($this->ids['central']));
        $this->assertSame(1, DB::table('movimiento_inventario')->count());
    }

    public function test_traslado_saca_del_origen_y_entra_al_destino_al_costo_promedio(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->mover(['tipo' => 'AJUSTE_ENTRADA', 'cantidad' => 10, 'costo_unitario' => 25]);

        $this->actingAs($admin)->mover(['tipo' => 'TRASLADO', 'cantidad' => 4])->assertSessionHasErrors('id_bodega_destino');
        $this->actingAs($admin)->mover(['tipo' => 'TRASLADO', 'cantidad' => 4, 'id_bodega_destino' => $this->ids['central']])->assertSessionHasErrors('id_bodega_destino');
        $this->actingAs($admin)->mover(['tipo' => 'TRASLADO', 'cantidad' => 4, 'id_bodega_destino' => $this->ids['sucursal']])->assertSessionHasNoErrors();

        $this->assertSame([6.0, 25.0], $this->stock($this->ids['central']));
        $this->assertSame([4.0, 25.0], $this->stock($this->ids['sucursal']));
        [$salida, $entrada] = DB::table('movimiento_inventario')->where('referencia_tipo', 'TRASLADO')->orderBy('id_movimiento')->get()->all();
        $this->assertSame(['SALIDA', 'ENTRADA'], [$salida->tipo_movimiento, $entrada->tipo_movimiento]);
        $this->assertSame((int) $salida->id_movimiento, (int) $entrada->referencia_id);
    }

    public function test_lote_y_vencimiento_obligatorios_en_entradas_de_perecederos(): void
    {
        $admin = $this->crearUsuario();
        $base = ['tipo' => 'AJUSTE_ENTRADA', 'id_producto' => $this->ids['leche'], 'cantidad' => 2, 'costo_unitario' => 9];

        $this->actingAs($admin)->mover($base)->assertSessionHasErrors(['numero_lote', 'fecha_vencimiento']);
        $this->actingAs($admin)->mover($base + ['numero_lote' => 'L-01', 'fecha_vencimiento' => now()->subDay()->format('Y-m-d')])->assertSessionHasErrors('fecha_vencimiento');
        $this->actingAs($admin)->mover($base + ['numero_lote' => 'L-01', 'fecha_vencimiento' => now()->addMonth()->format('Y-m-d')])->assertSessionHasNoErrors();
        $this->assertSame('L-01', DB::table('movimiento_inventario')->value('numero_lote'));
    }

    public function test_kardex_con_saldo_acumulado(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->mover(['tipo' => 'AJUSTE_ENTRADA', 'cantidad' => 10, 'costo_unitario' => 5]);
        $this->actingAs($admin)->mover(['tipo' => 'SALIDA', 'cantidad' => 3]);
        $this->actingAs($admin)->mover(['tipo' => 'AJUSTE_ENTRADA', 'cantidad' => 1.5, 'costo_unitario' => 5]);

        $this->actingAs($admin)->get(route('movimientos.index', ['producto' => $this->ids['cemento'], 'bodega' => $this->ids['central']]))
            ->assertOk()->assertSee('Saldo anterior')->assertSeeInOrder(['10', '7', '8.5'])->assertSee('Existencia en Central');
        $this->actingAs($admin)->get(route('movimientos.index'))->assertOk()->assertSee('Cemento')->assertSee('Elige un producto y una bodega');
        $this->actingAs($admin)->get(route('movimientos.index', ['tipo' => 'SALIDA']))->assertOk()->assertSee('Salida')->assertDontSee('Entrada por ajuste');
        $this->actingAs($admin)->get(route('movimientos.create'))->assertOk()->assertSee('Traslado entre bodegas');
    }

    public function test_permisos_y_otra_empresa(): void
    {
        $bodeguero = $this->crearUsuario(['username' => 'b', 'email' => 'b@nexus.test'], 'Bodega');
        $this->darPermisos($bodeguero, ['movimientos.ver']);
        $this->actingAs($bodeguero)->get(route('movimientos.index'))->assertOk()->assertDontSee('Registrar movimiento');
        $this->actingAs($bodeguero)->mover(['tipo' => 'AJUSTE_ENTRADA', 'cantidad' => 1])->assertForbidden();

        $admin = $this->crearUsuario();
        $ajena = DB::table('bodega')->insertGetId(['id_empresa' => 2, 'nombre' => 'Ajena']);
        $this->actingAs($admin)->mover(['tipo' => 'AJUSTE_ENTRADA', 'cantidad' => 1, 'id_bodega' => $ajena])->assertSessionHasErrors('id_bodega');
        DB::table('movimiento_inventario')->insert(['id_empresa' => 2, 'id_producto' => $this->ids['cemento'], 'id_bodega' => $ajena, 'cantidad' => 1, 'observaciones' => 'Movimiento ajeno']);
        $this->actingAs($admin)->get(route('movimientos.index'))->assertDontSee('Movimiento ajeno');
    }
}

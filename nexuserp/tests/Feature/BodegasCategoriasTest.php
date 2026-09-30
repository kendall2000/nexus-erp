<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 4a: Bodegas (bodegas.*) y Categorías (categorias.*) en Blade. */
class BodegasCategoriasTest extends TestCase
{
    use EsquemaNexus;

    private function bodega(array $datos = []): int
    {
        return DB::table('bodega')->insertGetId($datos + ['id_empresa' => 1, 'nombre' => 'Central', 'activo' => true]);
    }

    private function categoria(string $nombre, ?int $padre = null, array $datos = []): int
    {
        return DB::table('categoria_producto')->insertGetId($datos + ['id_empresa' => 1, 'nombre' => $nombre, 'id_padre' => $padre, 'activo' => true]);
    }

    private function fila(string $tabla, string $llave, int $id): ?object
    {
        return DB::table($tabla)->where($llave, $id)->first();
    }

    // ── Bodegas ─────────────────────────────────────────────────────────────

    public function test_permisos_de_bodegas(): void
    {
        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Bodega');
        $this->darPermisos($lector, ['bodegas.ver']);
        $nadie = $this->crearUsuario(['username' => 'n', 'email' => 'n@nexus.test'], 'Ventas');

        $this->actingAs($nadie)->get(route('bodegas.index'))->assertForbidden();
        $this->actingAs($lector)->get(route('bodegas.index'))->assertOk()->assertDontSee('Nueva bodega');
        $this->actingAs($lector)->post(route('bodegas.store'), ['nombre' => 'X'])->assertForbidden();
    }

    public function test_lista_con_valor_del_inventario_solo_de_mi_empresa(): void
    {
        $central = $this->bodega(['nombre' => 'Central']);
        $this->bodega(['id_empresa' => 2, 'nombre' => 'Ajena']);
        DB::table('stock_bodega')->insert([
            ['id_producto' => 1, 'id_bodega' => $central, 'cantidad_actual' => 10, 'costo_promedio' => 2.5],
            ['id_producto' => 2, 'id_bodega' => $central, 'cantidad_actual' => 4, 'costo_promedio' => 10],
            ['id_producto' => 3, 'id_bodega' => $central, 'cantidad_actual' => 0, 'costo_promedio' => 99],
        ]);

        $respuesta = $this->actingAs($this->crearUsuario())->get(route('bodegas.index'));

        $respuesta->assertOk()->assertSee('Central')->assertDontSee('Ajena')->assertSee('65.00');
        $this->assertSame(2, (int) $respuesta->viewData('bodegas')->first()->productos_con_stock);
    }

    public function test_crea_y_edita_validando_sucursal_de_mi_empresa(): void
    {
        $admin = $this->crearUsuario();
        $mia = DB::table('sucursal')->insertGetId(['id_empresa' => 1, 'nombre' => 'Zona 10']);
        $ajena = DB::table('sucursal')->insertGetId(['id_empresa' => 2, 'nombre' => 'Ajena']);

        $this->actingAs($admin)->post(route('bodegas.store'), ['nombre' => 'Norte', 'id_sucursal' => $ajena])->assertSessionHasErrors('id_sucursal');
        $this->actingAs($admin)->post(route('bodegas.store'), ['nombre' => 'Norte', 'id_sucursal' => $mia, 'activo' => '1'])
            ->assertRedirect(route('bodegas.index'))->assertSessionHasNoErrors();

        $bodega = DB::table('bodega')->where('nombre', 'Norte')->first();
        $this->assertSame([1, $mia, 1], [(int) $bodega->id_empresa, (int) $bodega->id_sucursal, (int) $bodega->activo]);
    }

    public function test_no_elimina_bodegas_con_existencia_o_usadas_y_si_las_vacias(): void
    {
        $admin = $this->crearUsuario();
        $conStock = $this->bodega(['nombre' => 'Con stock']);
        $usada = $this->bodega(['nombre' => 'Usada']);
        $vacia = $this->bodega(['nombre' => 'Vacía']);
        DB::table('stock_bodega')->insert([
            ['id_producto' => 1, 'id_bodega' => $conStock, 'cantidad_actual' => 1, 'costo_promedio' => 1],
            ['id_producto' => 1, 'id_bodega' => $vacia, 'cantidad_actual' => 0, 'costo_promedio' => 1],
        ]);
        DB::table('orden_compra')->insert(['id_empresa' => 1, 'id_bodega' => $usada]);

        $this->actingAs($admin)->delete(route('bodegas.destroy', $conStock))->assertSessionHasErrors('bodega');
        $this->actingAs($admin)->delete(route('bodegas.destroy', $usada))->assertSessionHasErrors('bodega');
        $this->actingAs($admin)->delete(route('bodegas.destroy', $vacia))->assertRedirect(route('bodegas.index'));

        $this->assertNull($this->fila('bodega', 'id_bodega', $vacia));
        $this->assertSame(0, DB::table('stock_bodega')->where('id_bodega', $vacia)->count());
        $this->assertNotNull($this->fila('bodega', 'id_bodega', $conStock));
    }

    public function test_no_toca_bodegas_de_otra_empresa(): void
    {
        $ajena = $this->bodega(['id_empresa' => 2]);

        $this->actingAs($this->crearUsuario())->patch(route('bodegas.estado', $ajena))->assertNotFound();
    }

    // ── Categorías ──────────────────────────────────────────────────────────

    public function test_muestra_el_arbol_ordenado(): void
    {
        $bebidas = $this->categoria('Bebidas');
        $this->categoria('Gaseosas', $bebidas);
        $this->categoria('Abarrotes');

        $respuesta = $this->actingAs($this->crearUsuario())->get(route('categorias.index'));

        $respuesta->assertOk()->assertSeeInOrder(['Abarrotes', 'Bebidas', 'Gaseosas']);
        $this->assertSame([0, 0, 1], $respuesta->viewData('arbol')->pluck('nivel')->all());
    }

    public function test_crea_subcategorias_solo_con_padre_de_mi_empresa(): void
    {
        $admin = $this->crearUsuario();
        $bebidas = $this->categoria('Bebidas');
        $ajena = $this->categoria('Ajena', null, ['id_empresa' => 2]);

        $this->actingAs($admin)->post(route('categorias.store'), ['nombre' => 'Jugos', 'id_padre' => $ajena])->assertSessionHasErrors('id_padre');
        $this->actingAs($admin)->post(route('categorias.store'), ['nombre' => 'Bebidas'])->assertSessionHasErrors('nombre');
        $this->actingAs($admin)->post(route('categorias.store'), ['nombre' => ' Jugos ', 'id_padre' => $bebidas, 'activo' => '1'])
            ->assertRedirect(route('categorias.index'))->assertSessionHasNoErrors();

        $this->assertSame($bebidas, (int) DB::table('categoria_producto')->where('nombre', 'Jugos')->value('id_padre'));
    }

    public function test_no_permite_ciclos(): void
    {
        $admin = $this->crearUsuario();
        $a = $this->categoria('A');
        $b = $this->categoria('B', $a);
        $c = $this->categoria('C', $b);

        // A no puede quedar dentro de C (su nieta) ni de sí misma.
        $this->actingAs($admin)->put(route('categorias.update', $a), ['nombre' => 'A', 'id_padre' => $c])->assertSessionHasErrors('id_padre');
        $this->actingAs($admin)->put(route('categorias.update', $a), ['nombre' => 'A', 'id_padre' => $a])->assertSessionHasErrors('id_padre');
        $this->assertNull($this->fila('categoria_producto', 'id_categoria', $a)->id_padre);

        // El formulario tampoco las ofrece.
        $this->actingAs($admin)->get(route('categorias.edit', $a))->assertOk()
            ->assertDontSee('value="'.$b.'"', false)->assertDontSee('value="'.$c.'"', false);
    }

    public function test_no_elimina_categorias_con_subcategorias_o_productos(): void
    {
        $admin = $this->crearUsuario();
        $padre = $this->categoria('Padre');
        $this->categoria('Hija', $padre);
        $conProductos = $this->categoria('Con productos');
        DB::table('producto')->insert(['id_empresa' => 1, 'id_categoria' => $conProductos, 'nombre' => 'Agua']);
        $vacia = $this->categoria('Vacía');

        $this->actingAs($admin)->delete(route('categorias.destroy', $padre))->assertSessionHasErrors('categoria');
        $this->actingAs($admin)->delete(route('categorias.destroy', $conProductos))->assertSessionHasErrors('categoria');
        $this->actingAs($admin)->delete(route('categorias.destroy', $vacia))->assertRedirect(route('categorias.index'));
        $this->assertNull($this->fila('categoria_producto', 'id_categoria', $vacia));
    }

    public function test_las_apis_de_bodegas_y_categorias_ya_no_existen(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->getJson('/api/v1/inventario/bodegas')->assertNotFound();
        $this->actingAs($admin)->getJson('/api/v1/inventario/categorias')->assertNotFound();
    }
}

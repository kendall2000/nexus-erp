<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 4b: Productos (productos.*) y Proveedores (proveedores.*) en Blade. */
class ProductosProveedoresTest extends TestCase
{
    use EsquemaNexus;

    private function producto(array $datos = []): int
    {
        return DB::table('producto')->insertGetId($datos + ['id_empresa' => 1, 'codigo' => 'P-1', 'nombre' => 'Agua pura', 'unidad_medida' => 'UND', 'moneda' => 'GTQ', 'activo' => true]);
    }

    private function proveedor(array $datos = []): int
    {
        return DB::table('proveedor')->insertGetId($datos + ['id_empresa' => 1, 'razon_social' => 'Distribuidora S.A.', 'tipo_proveedor' => 'BIENES', 'moneda_pago' => 'GTQ', 'activo' => true]);
    }

    private function pais(): int
    {
        return DB::table('pais')->insertGetId(['nombre' => 'Guatemala', 'codigo_iso2' => 'GT']);
    }

    private function datosProducto(array $cambios = []): array
    {
        return array_merge(['codigo' => ' p-100 ', 'nombre' => 'Café molido', 'unidad_medida' => 'KG', 'moneda' => 'GTQ', 'precio_compra' => '25.5', 'stock_minimo' => '5', 'activo' => '1'], $cambios);
    }

    // ── Productos ───────────────────────────────────────────────────────────

    public function test_permisos_de_productos(): void
    {
        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Consulta');
        $this->darPermisos($lector, ['productos.ver']);

        $this->actingAs($lector)->get(route('productos.index'))->assertOk()->assertDontSee('Nuevo producto')->assertDontSee('Exportar');
        $this->actingAs($lector)->post(route('productos.store'), $this->datosProducto())->assertForbidden();
        $this->actingAs($lector)->get(route('productos.exportar'))->assertForbidden();
    }

    public function test_crea_producto_sin_poder_cambiar_la_empresa(): void
    {
        $admin = $this->crearUsuario();
        $categoriaAjena = DB::table('categoria_producto')->insertGetId(['id_empresa' => 2, 'nombre' => 'Ajena']);

        $this->actingAs($admin)->post(route('productos.store'), $this->datosProducto(['id_categoria' => $categoriaAjena]))->assertSessionHasErrors('id_categoria');
        $this->actingAs($admin)->post(route('productos.store'), $this->datosProducto(['id_empresa' => 2, 'stock_maximo' => '2']))->assertSessionHasErrors('stock_maximo');
        $this->actingAs($admin)->post(route('productos.store'), $this->datosProducto(['id_empresa' => 2]))
            ->assertRedirect(route('productos.index'))->assertSessionHasNoErrors();

        $p = DB::table('producto')->where('codigo', 'P-100')->first();
        $this->assertSame(1, (int) $p->id_empresa); // id_empresa del formulario se ignora
        $this->actingAs($admin)->post(route('productos.store'), $this->datosProducto())->assertSessionHasErrors('codigo');

        // Tampoco al editar (antes se guardaba todo lo que llegaba).
        $this->actingAs($admin)->put(route('productos.update', $p->id_producto), $this->datosProducto(['id_empresa' => 2, 'nombre' => 'Café tostado']))->assertSessionHasNoErrors();
        $p = DB::table('producto')->where('id_producto', $p->id_producto)->first();
        $this->assertSame([1, 'Café tostado'], [(int) $p->id_empresa, $p->nombre]);
    }

    public function test_lista_existencia_y_marca_reponer(): void
    {
        $id = $this->producto(['stock_minimo' => 10]);
        DB::table('stock_bodega')->insert([['id_producto' => $id, 'id_bodega' => 1, 'cantidad_actual' => 3], ['id_producto' => $id, 'id_bodega' => 2, 'cantidad_actual' => 4]]);
        $this->producto(['id_empresa' => 2, 'codigo' => 'X', 'nombre' => 'De otra empresa']);

        $this->actingAs($this->crearUsuario())->get(route('productos.index'))
            ->assertOk()->assertSee('Agua pura')->assertSee('Reponer')->assertDontSee('De otra empresa');
    }

    public function test_no_elimina_productos_con_existencia_o_usados(): void
    {
        $admin = $this->crearUsuario();
        $conStock = $this->producto(['codigo' => 'A']);
        $usado = $this->producto(['codigo' => 'B']);
        $libre = $this->producto(['codigo' => 'C']);
        DB::table('stock_bodega')->insert(['id_producto' => $conStock, 'id_bodega' => 1, 'cantidad_actual' => 1]);
        DB::table('detalle_orden_compra')->insert(['id_oc' => 1, 'id_producto' => $usado, 'cantidad_pedida' => 1, 'precio_unitario' => 1]);

        $this->actingAs($admin)->delete(route('productos.destroy', $conStock))->assertSessionHasErrors('producto');
        $this->actingAs($admin)->delete(route('productos.destroy', $usado))->assertSessionHasErrors('producto');
        $this->actingAs($admin)->delete(route('productos.destroy', $libre))->assertRedirect(route('productos.index'));
        $this->assertSame(2, DB::table('producto')->count());
    }

    public function test_exporta_productos_a_csv(): void
    {
        $this->producto(['codigo' => '=HACK', 'nombre' => 'Fórmula']);

        $respuesta = $this->actingAs($this->crearUsuario())->get(route('productos.exportar'));

        $respuesta->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $respuesta->streamedContent();
        $this->assertStringContainsString('Código;Nombre', $csv);
        $this->assertStringContainsString("'=HACK", $csv); // no se ejecuta como fórmula en Excel
    }

    // ── Proveedores ─────────────────────────────────────────────────────────

    public function test_crea_proveedor_normalizando_nit_y_sin_cambiar_la_empresa(): void
    {
        $admin = $this->crearUsuario();
        $pais = $this->pais();
        $datos = ['razon_social' => 'Café del Valle S.A.', 'nit' => ' 1234-5 k ', 'email' => 'VENTAS@Valle.com', 'id_pais' => $pais,
            'tipo_proveedor' => 'BIENES', 'dias_credito' => 30, 'moneda_pago' => 'GTQ', 'activo' => '1', 'id_empresa' => 2];

        $this->actingAs($admin)->post(route('proveedores.store'), $datos)->assertRedirect(route('proveedores.index'))->assertSessionHasNoErrors();

        $p = DB::table('proveedor')->first();
        $this->assertSame(['1234-5K', 'ventas@valle.com', 1], [$p->nit, $p->email, (int) $p->id_empresa]);
        $this->actingAs($admin)->post(route('proveedores.store'), $datos)->assertSessionHasErrors('nit');
    }

    public function test_no_elimina_proveedores_con_ordenes_abiertas(): void
    {
        $admin = $this->crearUsuario();
        $conOrden = $this->proveedor(['razon_social' => 'Con orden']);
        $libre = $this->proveedor(['razon_social' => 'Libre']);
        DB::table('orden_compra')->insert(['id_empresa' => 1, 'id_proveedor' => $conOrden, 'estado' => 'ENVIADA']);

        $this->actingAs($admin)->delete(route('proveedores.destroy', $conOrden))->assertSessionHasErrors('proveedor');
        $this->actingAs($admin)->delete(route('proveedores.destroy', $libre))->assertRedirect(route('proveedores.index'));

        $this->assertNotNull(DB::table('proveedor')->where('id_proveedor', $libre)->value('deleted_at')); // eliminación lógica
    }

    public function test_no_toca_proveedores_ni_productos_de_otra_empresa(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->patch(route('proveedores.estado', $this->proveedor(['id_empresa' => 2])))->assertNotFound();
        $this->actingAs($admin)->patch(route('productos.estado', $this->producto(['id_empresa' => 2])))->assertNotFound();
    }

    public function test_las_apis_ya_no_existen(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->getJson('/api/v1/inventario/productos')->assertNotFound();
        $this->actingAs($admin)->getJson('/api/v1/inventario/proveedores')->assertNotFound();
    }
}

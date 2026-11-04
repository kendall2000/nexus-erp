<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Auditoría de inventario, tanda E: costo sin IVA, lote y vencimiento en la recepción, fecha del
 * kardex, servicios, y bodegas o productos con existencias que no se desactivan ni cambian de unidad.
 */
class InventarioAuditoriaTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Carbon::setTestNow('2026-11-04 10:00');
        $this->actingAs($this->crearUsuario());
        $this->ids = [
            'proveedor' => DB::table('proveedor')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Proveedor Uno']),
            'bodega' => DB::table('bodega')->insertGetId(['id_empresa' => 1, 'nombre' => 'Central']),
            'otra' => DB::table('bodega')->insertGetId(['id_empresa' => 1, 'nombre' => 'Sur']),
        ];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function producto(array $datos = []): int
    {
        return DB::table('producto')->insertGetId($datos + ['id_empresa' => 1, 'codigo' => 'P-'.uniqid(), 'nombre' => 'Cemento', 'unidad_medida' => 'UND', 'moneda' => 'GTQ', 'activo' => true]);
    }

    /** Orden aprobada con una línea; devuelve [oc, línea]. */
    private function orden(int $producto, float $cantidad, float $subtotal): array
    {
        $oc = DB::table('orden_compra')->insertGetId(['id_empresa' => 1, 'id_proveedor' => $this->ids['proveedor'], 'id_bodega' => $this->ids['bodega'],
            'numero_oc' => 'OC-'.uniqid(), 'fecha_emision' => '2026-09-01', 'moneda' => 'GTQ', 'estado' => 'ENVIADA']);
        $linea = DB::table('detalle_orden_compra')->insertGetId(['id_oc' => $oc, 'id_producto' => $producto, 'cantidad_pedida' => $cantidad, 'precio_unitario' => $subtotal / $cantidad, 'subtotal' => $subtotal]);

        return [$oc, $linea];
    }

    private function recibir(int $oc, int $linea, array $datosLinea, string $fecha = '2026-11-04')
    {
        return $this->post(route('recepciones.store'), ['id_oc' => $oc, 'id_bodega' => $this->ids['bodega'], 'fecha_recepcion' => $fecha, 'lineas' => [$linea => $datosLinea]]);
    }

    public function test_con_iva_incluido_el_costo_es_sin_iva(): void
    {
        DB::table('empresa')->where('id_empresa', 1)->update(['iva_incluido_en_precio' => true, 'tasa_iva' => 12]);
        $p = $this->producto();
        [$oc, $linea] = $this->orden($p, 10, 112); // Q112 con IVA incluido = Q100 de base

        $this->get(route('recepciones.create', ['oc' => $oc]))->assertOk()->assertSee('value="10"', false);
        $this->recibir($oc, $linea, ['cantidad' => 10])->assertSessionHasNoErrors();
        $this->assertSame(10.0, (float) DB::table('stock_bodega')->where('id_producto', $p)->value('costo_promedio'));
    }

    public function test_lote_vencimiento_y_fecha_del_kardex(): void
    {
        $p = $this->producto(['requiere_lote' => true, 'es_perecedero' => true]);
        [$oc, $linea] = $this->orden($p, 5, 50);

        $this->get(route('recepciones.create', ['oc' => $oc]))->assertOk()->assertSee("name=\"lineas[{$linea}][lote]\"", false)->assertSee("name=\"lineas[{$linea}][vencimiento]\"", false);
        $this->recibir($oc, $linea, ['cantidad' => 5])->assertSessionHasErrors(["lineas.{$linea}.lote"]);
        $this->recibir($oc, $linea, ['cantidad' => 5, 'lote' => 'L-77'])->assertSessionHasErrors(["lineas.{$linea}.vencimiento"]);
        $this->recibir($oc, $linea, ['cantidad' => 5, 'lote' => 'L-77', 'vencimiento' => '2027-03-01'], '2026-10-01')->assertSessionHasNoErrors();

        $m = DB::table('movimiento_inventario')->where('id_producto', $p)->first();
        $this->assertSame(['L-77', '2027-03-01', '2026-10-01'], [$m->numero_lote, substr((string) $m->fecha_vencimiento, 0, 10), substr((string) $m->created_at, 0, 10)]);
    }

    public function test_un_servicio_se_recibe_sin_existencias_ni_movimientos(): void
    {
        $servicio = $this->producto(['nombre' => 'Instalación', 'unidad_medida' => 'SERV']);
        [$oc, $linea] = $this->orden($servicio, 1, 500);
        $this->recibir($oc, $linea, ['cantidad' => 1])->assertSessionHasNoErrors();

        $this->assertSame('RECIBIDA', DB::table('orden_compra')->where('id_oc', $oc)->value('estado'));
        $this->assertSame(0, DB::table('movimiento_inventario')->count());
        $this->assertSame(0, DB::table('stock_bodega')->where('id_producto', $servicio)->where('cantidad_actual', '>', 0)->count());
        $this->post(route('movimientos.store'), ['tipo' => 'AJUSTE_ENTRADA', 'id_producto' => $servicio, 'id_bodega' => $this->ids['bodega'], 'cantidad' => 3, 'costo_unitario' => 10, 'motivo' => 'Prueba de ajuste'])
            ->assertSessionHasErrors('id_producto');
    }

    public function test_con_existencias_no_se_desactiva_ni_cambia_la_unidad(): void
    {
        $p = $this->producto(['unidad_medida' => 'KG']);
        [$oc, $linea] = $this->orden($p, 100, 7500);
        $this->recibir($oc, $linea, ['cantidad' => 100])->assertSessionHasNoErrors();

        $this->patch(route('bodegas.estado', $this->ids['bodega']))->assertSessionHasErrors('bodega');
        $this->patch(route('productos.estado', $p))->assertSessionHasErrors('producto');
        $this->assertSame([1, 1], [(int) DB::table('bodega')->where('id_bodega', $this->ids['bodega'])->value('activo'), (int) DB::table('producto')->where('id_producto', $p)->value('activo')]);
        $this->patch(route('bodegas.estado', $this->ids['otra']))->assertSessionHasNoErrors(); // sin existencias sí

        $datos = ['codigo' => 'P-KG', 'nombre' => 'Cemento', 'unidad_medida' => 'LB', 'moneda' => 'GTQ', 'stock_minimo' => '0', 'activo' => '1'];
        $this->put(route('productos.update', $p), $datos)->assertSessionHasErrors('unidad_medida');
        $this->assertSame('KG', DB::table('producto')->where('id_producto', $p)->value('unidad_medida'));
        $this->put(route('productos.update', $p), ['unidad_medida' => 'KG'] + $datos)->assertSessionHasNoErrors();
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 4d: Recepciones en Blade; cada entrada queda en el kardex y actualiza el stock. */
class RecepcionesTest extends TestCase
{
    use EsquemaNexus;

    private array $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $this->ids = [
            'proveedor' => DB::table('proveedor')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Proveedor Uno']),
            'bodega' => DB::table('bodega')->insertGetId(['id_empresa' => 1, 'nombre' => 'Central']),
            'cemento' => DB::table('producto')->insertGetId(['id_empresa' => 1, 'codigo' => 'P1', 'nombre' => 'Cemento']),
            'arena' => DB::table('producto')->insertGetId(['id_empresa' => 1, 'codigo' => 'P2', 'nombre' => 'Arena']),
        ];
    }

    /** Orden con dos líneas: 10 de cemento a 50 (descuento 20) y 4 de arena a 25. */
    private function orden(string $estado = 'ENVIADA', int $empresa = 1): array
    {
        $oc = DB::table('orden_compra')->insertGetId([
            'id_empresa' => $empresa, 'id_proveedor' => $this->ids['proveedor'], 'id_bodega' => $this->ids['bodega'],
            'numero_oc' => 'OC-'.now()->year.'-0001', 'fecha_emision' => now()->subDays(3)->format('Y-m-d'), 'moneda' => 'GTQ', 'estado' => $estado,
        ]);
        $cemento = DB::table('detalle_orden_compra')->insertGetId(['id_oc' => $oc, 'id_producto' => $this->ids['cemento'], 'cantidad_pedida' => 10, 'precio_unitario' => 50, 'descuento' => 20, 'subtotal' => 480]);
        $arena = DB::table('detalle_orden_compra')->insertGetId(['id_oc' => $oc, 'id_producto' => $this->ids['arena'], 'cantidad_pedida' => 4, 'precio_unitario' => 25, 'subtotal' => 100]);

        return [$oc, $cemento, $arena];
    }

    private function recibir(int $oc, array $lineas, array $cambios = [])
    {
        return $this->post(route('recepciones.store'), array_merge([
            'id_oc' => $oc, 'id_bodega' => $this->ids['bodega'], 'fecha_recepcion' => now()->format('Y-m-d'), 'lineas' => $lineas,
        ], $cambios));
    }

    private function stock(int $producto): array
    {
        $s = DB::table('stock_bodega')->where(['id_producto' => $producto, 'id_bodega' => $this->ids['bodega']])->first();

        return [(float) $s?->cantidad_actual, (float) $s?->costo_promedio];
    }

    public function test_recepcion_parcial_deja_kardex_stock_y_orden_parcial(): void
    {
        [$oc, $cemento, $arena] = $this->orden();

        $this->actingAs($this->crearUsuario())
            ->recibir($oc, [$cemento => ['cantidad' => 6, 'costo' => 48], $arena => ['cantidad' => 0, 'costo' => 25]])
            ->assertSessionHasNoErrors()->assertRedirect();

        $rec = DB::table('recepcion_mercaderia')->first();
        $this->assertSame('REC-'.now()->year.'-0001', $rec->numero_recepcion);
        $this->assertSame(1, DB::table('detalle_recepcion')->count()); // la línea en 0 no se registra

        // Antes el kardex quedaba vacío: ahora hay una ENTRADA por línea recibida, ligada a la recepción.
        $mov = DB::table('movimiento_inventario')->sole();
        $this->assertSame(['ENTRADA', 'COMPRA', $rec->id_recepcion, $this->ids['cemento']], [$mov->tipo_movimiento, $mov->referencia_tipo, (int) $mov->referencia_id, (int) $mov->id_producto]);
        $this->assertSame([6.0, 288.0], [(float) $mov->cantidad, (float) $mov->costo_total]);

        // El stock se suma una sola vez (antes el controlador y el kardex podían duplicarlo).
        $this->assertSame([6.0, 48.0], $this->stock($this->ids['cemento']));
        $this->assertSame('PARCIAL', DB::table('orden_compra')->value('estado'));
        $this->assertSame(6.0, (float) DB::table('detalle_orden_compra')->where('id_linea', $cemento)->value('cantidad_recibida'));
    }

    public function test_completar_la_orden_y_costo_promedio(): void
    {
        [$oc, $cemento, $arena] = $this->orden();
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->recibir($oc, [$cemento => ['cantidad' => 6, 'costo' => 40]]);
        $this->actingAs($admin)->recibir($oc, [$cemento => ['cantidad' => 4, 'costo' => 50], $arena => ['cantidad' => 4, 'costo' => 25]])->assertSessionHasNoErrors();

        $orden = DB::table('orden_compra')->first();
        $this->assertSame('RECIBIDA', $orden->estado);
        $this->assertNotNull($orden->fecha_entrega_real);
        $this->assertSame([10.0, 44.0], $this->stock($this->ids['cemento'])); // (6×40 + 4×50) / 10
        $this->assertSame(3, DB::table('movimiento_inventario')->count());
        $this->assertSame('REC-'.now()->year.'-0002', DB::table('recepcion_mercaderia')->orderByDesc('id_recepcion')->value('numero_recepcion'));

        // Ya recibida: no admite más recepciones.
        $this->actingAs($admin)->recibir($oc, [$cemento => ['cantidad' => 1]])->assertSessionHasErrors('id_oc');
    }

    public function test_no_recibe_mas_de_lo_pendiente_ni_nada(): void
    {
        [$oc, $cemento] = $this->orden();
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->recibir($oc, [$cemento => ['cantidad' => 11]])->assertSessionHasErrors("lineas.{$cemento}.cantidad");
        $this->actingAs($admin)->recibir($oc, [$cemento => ['cantidad' => 0]])->assertSessionHasErrors('lineas');

        $this->assertSame(0, DB::table('recepcion_mercaderia')->count());
        $this->assertSame(0, DB::table('movimiento_inventario')->count());
        $this->assertSame(0, DB::table('stock_bodega')->count());
    }

    public function test_solo_ordenes_aprobadas_de_mi_empresa_y_lineas_de_la_orden(): void
    {
        $admin = $this->crearUsuario();
        [$borrador, $lineaBorrador] = $this->orden('BORRADOR');
        $this->actingAs($admin)->recibir($borrador, [$lineaBorrador => ['cantidad' => 1]])->assertSessionHasErrors('id_oc');

        [$ajena, $lineaAjena] = $this->orden('ENVIADA', 2);
        $this->actingAs($admin)->recibir($ajena, [$lineaAjena => ['cantidad' => 1]])->assertSessionHasErrors('id_oc');

        // Una línea de otra orden no se puede colar en esta.
        [$oc] = $this->orden();
        $this->actingAs($admin)->recibir($oc, [$lineaAjena => ['cantidad' => 1]])->assertSessionHasErrors('lineas');

        $this->assertSame(0, DB::table('recepcion_mercaderia')->count());
    }

    public function test_valida_bodega_fecha_y_numero(): void
    {
        [$oc, $cemento] = $this->orden();
        $admin = $this->crearUsuario();
        $inactiva = DB::table('bodega')->insertGetId(['id_empresa' => 1, 'nombre' => 'Vieja', 'activo' => false]);
        $ajena = DB::table('bodega')->insertGetId(['id_empresa' => 2, 'nombre' => 'Ajena']);
        $linea = [$cemento => ['cantidad' => 1]];

        $this->actingAs($admin)->recibir($oc, $linea, ['id_bodega' => $inactiva])->assertSessionHasErrors('id_bodega');
        $this->actingAs($admin)->recibir($oc, $linea, ['id_bodega' => $ajena])->assertSessionHasErrors('id_bodega');
        $this->actingAs($admin)->recibir($oc, $linea, ['fecha_recepcion' => now()->addDay()->format('Y-m-d')])->assertSessionHasErrors('fecha_recepcion');
        $this->actingAs($admin)->recibir($oc, $linea, ['fecha_recepcion' => now()->subDays(10)->format('Y-m-d')])->assertSessionHasErrors('fecha_recepcion');

        $this->actingAs($admin)->recibir($oc, $linea, ['numero_recepcion' => 'guia-77'])->assertSessionHasNoErrors();
        $this->assertSame('GUIA-77', DB::table('recepcion_mercaderia')->value('numero_recepcion'));
        $this->actingAs($admin)->recibir($oc, $linea, ['numero_recepcion' => 'GUIA-77'])->assertSessionHasErrors('numero_recepcion');
    }

    public function test_pantallas(): void
    {
        [$oc, $cemento] = $this->orden();
        $admin = $this->crearUsuario();
        $this->actingAs($admin)->recibir($oc, [$cemento => ['cantidad' => 2]]);
        $rec = DB::table('recepcion_mercaderia')->first();

        $this->actingAs($admin)->get(route('recepciones.index'))->assertOk()->assertSee($rec->numero_recepcion)->assertSee('Proveedor Uno');
        $this->actingAs($admin)->get(route('recepciones.create'))->assertOk()->assertSee('OC-'.now()->year.'-0001');
        $this->actingAs($admin)->get(route('recepciones.create', ['oc' => $oc]))->assertOk()->assertSee('Cemento')->assertSee('Registrar recepción');
        $this->actingAs($admin)->get(route('recepciones.show', $rec->id_recepcion))->assertOk()->assertSee('ENTRADA')->assertSee('Recibir lo pendiente');
        $this->actingAs($admin)->get(route('recepciones.imprimir', $rec->id_recepcion))->assertOk()->assertSee('RECEPCIÓN DE MERCADERÍA');
        $this->actingAs($admin)->get(route('ordenes-compra.show', $oc))->assertOk()->assertSee($rec->numero_recepcion)->assertSee('Registrar recepción');
    }

    public function test_cada_accion_exige_su_permiso_y_no_ve_otra_empresa(): void
    {
        [$oc, $cemento] = $this->orden();
        $bodeguero = $this->crearUsuario(['username' => 'b', 'email' => 'b@nexus.test'], 'Bodega');
        $this->darPermisos($bodeguero, ['recepciones.ver']);

        $this->actingAs($bodeguero)->get(route('recepciones.index'))->assertOk()->assertDontSee('Nueva recepción');
        $this->actingAs($bodeguero)->get(route('recepciones.create'))->assertForbidden();
        $this->actingAs($bodeguero)->recibir($oc, [$cemento => ['cantidad' => 1]])->assertForbidden();

        $ajena = DB::table('recepcion_mercaderia')->insertGetId(['id_empresa' => 2, 'numero_recepcion' => 'X-1', 'fecha_recepcion' => now()]);
        $this->actingAs($bodeguero)->get(route('recepciones.show', $ajena))->assertNotFound();

        $propia = DB::table('recepcion_mercaderia')->insertGetId(['id_empresa' => 1, 'id_oc' => $oc, 'numero_recepcion' => 'R-1', 'fecha_recepcion' => now()]);
        $this->actingAs($bodeguero)->get(route('recepciones.show', $propia))->assertOk()->assertDontSee('Imprimir');
        $this->actingAs($bodeguero)->get(route('recepciones.imprimir', $propia))->assertForbidden();
    }

    public function test_la_api_de_recepciones_ya_no_existe(): void
    {
        $this->actingAs($this->crearUsuario())->getJson('/api/v1/inventario/recepciones')->assertNotFound();
    }
}

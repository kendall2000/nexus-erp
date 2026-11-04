<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 3: reporte consolidado de las empresas a las que tiene acceso el usuario. */
class ConsolidadoTest extends TestCase
{
    use EsquemaNexus;

    private const MODULOS = ['facturas', 'ordenes_compra', 'pagos', 'presupuesto'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        DB::table('empresa')->insert([
            ['id_empresa' => 2, 'nombre_comercial' => 'Adinser', 'activo' => true],
            ['id_empresa' => 3, 'nombre_comercial' => 'ADESA', 'activo' => true],
        ]);
        $anio = now()->year;

        // Facturas: la empresa 1 en GTQ y USD; la 2 en GTQ; la 3 en GTQ (sin acceso para Ana).
        DB::table('factura')->insert([
            ['id_empresa' => 1, 'id_cliente' => 1, 'tipo' => 'FACTURA', 'moneda' => 'GTQ', 'total' => 1000, 'saldo_pendiente' => 400, 'estado' => 'PARCIAL', 'fecha_emision' => "{$anio}-01-15", 'fecha_vencimiento' => now()->subDays(45)->toDateString()],
            ['id_empresa' => 1, 'id_cliente' => 1, 'tipo' => 'NOTA_CREDITO', 'moneda' => 'GTQ', 'total' => 100, 'saldo_pendiente' => 0, 'estado' => 'PAGADA', 'fecha_emision' => "{$anio}-02-10", 'fecha_vencimiento' => null],
            ['id_empresa' => 1, 'id_cliente' => 1, 'tipo' => 'FACTURA', 'moneda' => 'GTQ', 'total' => 999, 'saldo_pendiente' => 999, 'estado' => 'BORRADOR', 'fecha_emision' => "{$anio}-02-10", 'fecha_vencimiento' => null],
            ['id_empresa' => 1, 'id_cliente' => 1, 'tipo' => 'FACTURA', 'moneda' => 'GTQ', 'total' => 777, 'saldo_pendiente' => 0, 'estado' => 'ANULADA', 'fecha_emision' => "{$anio}-02-10", 'fecha_vencimiento' => null],
            ['id_empresa' => 1, 'id_cliente' => 1, 'tipo' => 'FACTURA', 'moneda' => 'USD', 'total' => 50, 'saldo_pendiente' => 50, 'estado' => 'EMITIDA', 'fecha_emision' => "{$anio}-03-01", 'fecha_vencimiento' => now()->addDays(10)->toDateString()],
            ['id_empresa' => 2, 'id_cliente' => 1, 'tipo' => 'FACTURA', 'moneda' => 'GTQ', 'total' => 2000, 'saldo_pendiente' => 2000, 'estado' => 'VENCIDA', 'fecha_emision' => "{$anio}-03-05", 'fecha_vencimiento' => now()->subDays(120)->toDateString()],
            ['id_empresa' => 3, 'id_cliente' => 1, 'tipo' => 'FACTURA', 'moneda' => 'GTQ', 'total' => 5555, 'saldo_pendiente' => 0, 'estado' => 'PAGADA', 'fecha_emision' => "{$anio}-03-05", 'fecha_vencimiento' => null],
        ]);
        DB::table('pago')->insert([
            ['id_empresa' => 1, 'monto' => 600, 'moneda' => 'GTQ', 'fecha_pago' => "{$anio}-01-20", 'estado' => 'APLICADO'],
            ['id_empresa' => 1, 'monto' => 300, 'moneda' => 'GTQ', 'fecha_pago' => "{$anio}-01-21", 'estado' => 'REVERTIDO'],
        ]);
        DB::table('orden_compra')->insert([
            ['id_empresa' => 2, 'moneda' => 'GTQ', 'total' => 800, 'estado' => 'RECIBIDA', 'fecha_emision' => "{$anio}-04-01"],
            ['id_empresa' => 2, 'moneda' => 'GTQ', 'total' => 200, 'estado' => 'ENVIADA', 'fecha_emision' => "{$anio}-04-02"],
            ['id_empresa' => 2, 'moneda' => 'GTQ', 'total' => 999, 'estado' => 'BORRADOR', 'fecha_emision' => "{$anio}-04-03"],
        ]);
        $centro = DB::table('centro_costo')->insertGetId(['id_empresa' => 1, 'codigo' => 'ADM', 'nombre' => 'Administración']);
        DB::table('presupuesto_anual')->insert([
            ['id_empresa' => 1, 'id_centro' => $centro, 'id_cuenta' => 1, 'anio' => $anio, 'moneda' => 'GTQ', 'total_presupuestado' => 5000, 'total_ejecutado' => 1250, 'estado' => 'APROBADO'],
            ['id_empresa' => 1, 'id_centro' => $centro, 'id_cuenta' => 2, 'anio' => $anio, 'moneda' => 'GTQ', 'total_presupuestado' => 9999, 'total_ejecutado' => 0, 'estado' => 'BORRADOR'],
        ]);
    }

    private function rol(int $idEmpresa, array $permisos): int
    {
        $id = DB::table('rol')->insertGetId(['id_empresa' => $idEmpresa, 'nombre' => "Rol {$idEmpresa}", 'activo' => true]);
        foreach ($permisos as $codigo) {
            DB::table('rol_permiso')->insert(['id_rol' => $id, 'id_permiso' => $this->permiso($codigo)]);
        }

        return $id;
    }

    public function test_suma_por_empresa_y_moneda_con_las_reglas_del_sistema(): void
    {
        $this->actingAs($this->crearUsuario())->get(route('consolidado.index'))->assertOk()
            ->assertViewHas('datos', function (array $d) {
                $ventas = collect($d['ventas']['filas'])->keyBy(fn ($f) => "{$f['id_empresa']}|{$f['moneda']}");
                $this->assertEquals([1000.0, 100.0, 900.0, 400.0], [$ventas['1|GTQ']['facturado'], $ventas['1|GTQ']['notas_credito'], $ventas['1|GTQ']['neto'], $ventas['1|GTQ']['saldo']]);
                $this->assertEquals(50.0, $ventas['1|USD']['neto']);
                $this->assertEquals(1000.0, $ventas['1|GTQ']['meses'][0]);
                $this->assertEquals(-100.0, $ventas['1|GTQ']['meses'][1]);
                // Las monedas no se mezclan: GTQ = 900 + 2000 + 5555.
                $this->assertEquals(8455.0, $d['ventas']['totales']['GTQ']['neto']);
                $this->assertEquals(50.0, $d['ventas']['totales']['USD']['neto']);

                $compras = $d['compras']['totales']['GTQ'];
                $this->assertEquals([2, 1000.0, 800.0], [$compras['ordenes'], $compras['total'], $compras['recibido']]);

                $this->assertEquals(600.0, $d['cobros']['cobros']['totales']['GTQ']['cobrado']);
                $cartera = collect($d['cobros']['cartera']['filas'])->keyBy(fn ($f) => "{$f['id_empresa']}|{$f['moneda']}");
                $this->assertEquals(400.0, $cartera['1|GTQ']['d60']);
                $this->assertEquals(50.0, $cartera['1|USD']['al_dia']);
                $this->assertEquals(2000.0, $cartera['2|GTQ']['mas90']);

                $presupuesto = $d['presupuesto']['empresas']['totales']['GTQ'];
                $this->assertEquals([5000.0, 1250.0, 3750.0], [$presupuesto['presupuestado'], $presupuesto['ejecutado'], $presupuesto['disponible']]);
                $this->assertSame('Administración', $d['presupuesto']['centros'][0]['nombre']);

                return true;
            })
            ->assertSee('Reporte consolidado')->assertSee('Adinser')->assertSee('Administración')->assertSee('Venta neta por mes');
    }

    public function test_cada_seccion_incluye_solo_las_empresas_con_permiso_del_modulo(): void
    {
        $ana = $this->crearUsuario(['username' => 'ana', 'email' => 'ana@nexus.test'], 'Ventas');
        $this->darPermisos($ana, ['facturas.ver', 'pagos.ver']);
        DB::table('usuario_rol')->insert(['id_usuario' => $ana->id_usuario, 'id_rol' => $this->rol(2, ['ordenes_compra.ver'])]);

        $this->actingAs($ana)->get(route('consolidado.index'))->assertOk()
            ->assertViewHas('datos', function (array $d) {
                $this->assertSame([1], collect($d['ventas']['filas'])->pluck('id_empresa')->unique()->values()->all());
                $this->assertSame([2], collect($d['compras']['filas'])->pluck('id_empresa')->unique()->values()->all());
                $this->assertSame([1], collect($d['cobros']['cartera']['filas'])->pluck('id_empresa')->unique()->values()->all());
                $this->assertNull($d['presupuesto']);

                return true;
            })
            ->assertDontSee('ADESA')->assertDontSee('5,555.00');
    }

    public function test_sin_permisos_de_los_modulos_no_entra_ni_exporta(): void
    {
        $luis = $this->crearUsuario(['username' => 'luis', 'email' => 'luis@nexus.test'], 'Bodega');
        $this->darPermisos($luis, ['usuarios.ver']);

        $this->actingAs($luis)->get(route('consolidado.index'))->assertForbidden();
        $this->get(route('consolidado.exportar'))->assertForbidden();
    }

    public function test_exporta_solo_lo_que_puede_exportar(): void
    {
        $ana = $this->crearUsuario(['username' => 'ana', 'email' => 'ana@nexus.test'], 'Ventas');
        $this->darPermisos($ana, ['facturas.ver', 'facturas.exportar', 'presupuesto.ver']);

        $csv = $this->actingAs($ana)->get(route('consolidado.exportar'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Venta neta', $csv);
        $this->assertStringContainsString('900.00', $csv);
        $this->assertStringNotContainsString('Presupuestado', $csv);
        $this->assertStringNotContainsString('Adinser', $csv);
    }

    public function test_el_anio_fuera_de_rango_usa_el_actual(): void
    {
        $this->actingAs($this->crearUsuario())->get(route('consolidado.index', ['anio' => 1900]))
            ->assertOk()->assertViewHas('anio', (int) now()->year);
        $this->get(route('consolidado.index', ['anio' => now()->year - 1]))
            ->assertOk()->assertViewHas('datos', fn ($d) => $d['ventas']['filas'][0]['facturado'] == 0 && $d['ventas']['filas'][0]['saldo'] > 0);
    }
}

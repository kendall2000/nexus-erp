<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 7b: Líneas de negocio, tipos de servicio y series de facturación. */
class CatalogosComercialesTest extends TestCase
{
    use EsquemaNexus;

    public function test_lineas_de_negocio(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->post(route('lineas-negocio.store'), ['nombre' => 'Limpieza', 'activo' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('lineas-negocio.store'), ['nombre' => 'Limpieza'])->assertSessionHasErrors('nombre');
        $linea = DB::table('linea_negocio')->value('id_linea');
        DB::table('linea_negocio')->insert(['id_empresa' => 2, 'nombre' => 'Limpieza']); // otra empresa puede repetir el nombre

        DB::table('tipo_servicio')->insert(['id_linea' => $linea, 'nombre' => 'Alfombras']);
        $this->actingAs($admin)->delete(route('lineas-negocio.destroy', $linea))->assertSessionHasErrors('linea');
        $this->actingAs($admin)->get(route('lineas-negocio.index'))->assertOk()->assertSee('Limpieza');
    }

    public function test_tipos_de_servicio_solo_con_lineas_y_cuentas_de_mi_empresa(): void
    {
        $admin = $this->crearUsuario();
        $linea = DB::table('linea_negocio')->insertGetId(['id_empresa' => 1, 'nombre' => 'Seguridad']);
        $ajena = DB::table('linea_negocio')->insertGetId(['id_empresa' => 2, 'nombre' => 'Ajena']);
        $ingreso = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'codigo' => '4.01', 'nombre' => 'Servicios', 'tipo' => 'INGRESO']);
        $gasto = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'codigo' => '5.01', 'nombre' => 'Gasto', 'tipo' => 'GASTO']);
        $datos = ['id_linea' => $linea, 'nombre' => 'Vigilancia', 'unidad_medida' => 'mes', 'precio_base' => 500, 'moneda' => 'GTQ', 'id_cuenta_ingreso' => $ingreso, 'activo' => '1'];

        $this->actingAs($admin)->post(route('tipos-servicio.store'), ['id_linea' => $ajena] + $datos)->assertSessionHasErrors('id_linea');
        $this->actingAs($admin)->post(route('tipos-servicio.store'), ['id_cuenta_ingreso' => $gasto] + $datos)->assertSessionHasErrors('id_cuenta_ingreso');
        $this->actingAs($admin)->post(route('tipos-servicio.store'), $datos)->assertSessionHasNoErrors();
        $this->assertSame('MES', DB::table('tipo_servicio')->value('unidad_medida'));
        $this->actingAs($admin)->post(route('tipos-servicio.store'), $datos)->assertSessionHasErrors('nombre');

        // Los servicios de otra empresa no se ven ni se editan.
        $servicioAjeno = DB::table('tipo_servicio')->insertGetId(['id_linea' => $ajena, 'nombre' => 'Servicio ajeno']);
        $this->actingAs($admin)->get(route('tipos-servicio.index'))->assertOk()->assertSee('Vigilancia')->assertDontSee('Servicio ajeno');
        $this->actingAs($admin)->get(route('tipos-servicio.edit', $servicioAjeno))->assertNotFound();

        // En uso por una factura: no se elimina.
        $servicio = DB::table('tipo_servicio')->where('nombre', 'Vigilancia')->value('id_tipo_servicio');
        DB::table('detalle_factura')->insert(['id_factura' => 1, 'id_tipo_servicio' => $servicio, 'descripcion' => 'x', 'precio_unitario' => 1, 'subtotal' => 1]);
        $this->actingAs($admin)->delete(route('tipos-servicio.destroy', $servicio))->assertSessionHasErrors('servicio');
        $this->assertStringContainsString('Vigilancia', $this->actingAs($admin)->get(route('tipos-servicio.exportar'))->streamedContent());
    }

    public function test_series_de_facturacion(): void
    {
        $admin = $this->crearUsuario();
        $datos = ['codigo_serie' => 'b', 'tipo' => 'FACTURA', 'ultimo_numero' => 150, 'activo' => '1'];

        $this->actingAs($admin)->post(route('series-facturacion.store'), $datos)->assertSessionHasNoErrors();
        $serie = DB::table('serie_facturacion')->first();
        $this->assertSame(['B', 150], [$serie->codigo_serie, (int) $serie->ultimo_numero]);
        $this->actingAs($admin)->post(route('series-facturacion.store'), $datos)->assertSessionHasErrors('codigo_serie');
        $this->actingAs($admin)->post(route('series-facturacion.store'), ['tipo' => 'NOTA_CREDITO'] + $datos)->assertSessionHasNoErrors(); // mismo código, otro tipo
        $this->actingAs($admin)->get(route('series-facturacion.index'))->assertOk()->assertSee('B-00000151');

        // Con documentos emitidos, el correlativo y el código ya no cambian.
        DB::table('factura')->insert(['id_empresa' => 1, 'id_cliente' => 1, 'id_serie' => $serie->id_serie, 'numero_factura' => 151]);
        $this->actingAs($admin)->put(route('series-facturacion.update', $serie->id_serie), ['codigo_serie' => 'Z', 'tipo' => 'FACTURA', 'ultimo_numero' => 0, 'descripcion' => 'Ventas'])->assertSessionHasNoErrors();
        $serie = DB::table('serie_facturacion')->where('id_serie', $serie->id_serie)->first();
        $this->assertSame(['B', 150, 'Ventas', 0], [$serie->codigo_serie, (int) $serie->ultimo_numero, $serie->descripcion, (int) $serie->activo]);
        $this->actingAs($admin)->delete(route('series-facturacion.destroy', $serie->id_serie))->assertSessionHasErrors('serie');
    }

    public function test_permisos(): void
    {
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');
        $this->darPermisos($vendedor, ['tipos_servicio.ver']);

        $this->actingAs($vendedor)->get(route('tipos-servicio.index'))->assertOk()->assertDontSee('Nuevo servicio');
        $this->actingAs($vendedor)->get(route('lineas-negocio.index'))->assertForbidden();
        $this->actingAs($vendedor)->post(route('series-facturacion.store'), ['codigo_serie' => 'X', 'tipo' => 'FACTURA', 'ultimo_numero' => 0])->assertForbidden();
    }
}

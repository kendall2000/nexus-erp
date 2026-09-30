<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 3f: Sucursales y Geografía en Blade (solo Administrador). */
class SucursalesGeografiaTest extends TestCase
{
    use EsquemaNexus;

    private array $geo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema(); // este setUp reemplaza al del trait
        $gt = DB::table('pais')->insertGetId(['nombre' => 'Guatemala', 'codigo_iso2' => 'GT']);
        $sv = DB::table('pais')->insertGetId(['nombre' => 'El Salvador', 'codigo_iso2' => 'SV']);
        $guate = DB::table('division_geografica')->insertGetId(['id_pais' => $gt, 'nombre' => 'Guatemala', 'tipo' => 'departamento']);
        $sanSalvador = DB::table('division_geografica')->insertGetId(['id_pais' => $sv, 'nombre' => 'San Salvador', 'tipo' => 'departamento']);
        $mixco = DB::table('municipio')->insertGetId(['id_division' => $guate, 'nombre' => 'Mixco']);
        $this->geo = compact('gt', 'sv', 'guate', 'sanSalvador', 'mixco');
    }

    private function sucursal(array $datos = []): int
    {
        return DB::table('sucursal')->insertGetId($datos + ['id_empresa' => 1, 'nombre' => 'Central', 'activo' => true, 'es_casa_matriz' => false]);
    }

    private function fila(string $tabla, string $llave, int $id): ?object
    {
        return DB::table($tabla)->where($llave, $id)->first();
    }

    // ── Sucursales ──────────────────────────────────────────────────────────

    public function test_solo_el_administrador_administra_sucursales_y_geografia(): void
    {
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');

        $this->actingAs($vendedor)->get(route('sucursales.index'))->assertForbidden();
        $this->actingAs($vendedor)->get(route('geografia.index'))->assertForbidden();
        $this->actingAs($this->crearUsuario())->get(route('sucursales.index'))->assertOk();
    }

    public function test_crea_sucursal_con_ubicacion_coherente_y_una_sola_casa_matriz(): void
    {
        $admin = $this->crearUsuario();
        $anterior = $this->sucursal(['nombre' => 'Vieja', 'es_casa_matriz' => true]);

        $this->actingAs($admin)->get(route('sucursales.create'))->assertOk()->assertSee('Mixco');

        $this->actingAs($admin)->post(route('sucursales.store'), [
            'nombre' => 'Zona 10', 'id_pais' => $this->geo['gt'], 'id_division' => $this->geo['guate'], 'id_municipio' => $this->geo['mixco'],
            'es_casa_matriz' => '1', 'activo' => '1',
        ])->assertRedirect(route('sucursales.index'))->assertSessionHasNoErrors();

        $nueva = DB::table('sucursal')->where('nombre', 'Zona 10')->first();
        $this->assertTrue((bool) $nueva->es_casa_matriz);
        $this->assertSame(1, (int) $nueva->id_empresa);
        $this->assertFalse((bool) $this->fila('sucursal', 'id_sucursal', $anterior)->es_casa_matriz);
    }

    public function test_rechaza_departamento_de_otro_pais_y_municipio_de_otro_departamento(): void
    {
        $this->actingAs($this->crearUsuario())->post(route('sucursales.store'), [
            'nombre' => 'Mal', 'id_pais' => $this->geo['gt'], 'id_division' => $this->geo['sanSalvador'], 'id_municipio' => $this->geo['mixco'],
        ])->assertSessionHasErrors(['id_division', 'id_municipio']);
    }

    public function test_la_casa_matriz_no_se_desactiva_quita_ni_elimina(): void
    {
        $admin = $this->crearUsuario();
        $matriz = $this->sucursal(['es_casa_matriz' => true]);

        $this->actingAs($admin)->patch(route('sucursales.estado', $matriz))->assertSessionHasErrors('sucursal');
        $this->actingAs($admin)->delete(route('sucursales.destroy', $matriz))->assertSessionHasErrors('sucursal');
        $this->actingAs($admin)->put(route('sucursales.update', $matriz), ['nombre' => 'Central', 'activo' => '1'])->assertSessionHasErrors('es_casa_matriz');
        $this->assertTrue((bool) $this->fila('sucursal', 'id_sucursal', $matriz)->activo);
    }

    public function test_no_elimina_sucursales_en_uso(): void
    {
        $admin = $this->crearUsuario();
        $enUso = $this->sucursal(['nombre' => 'Con gente']);
        $vacia = $this->sucursal(['nombre' => 'Vacía']);
        $this->crearUsuario(['username' => 'u', 'email' => 'u@nexus.test', 'id_sucursal' => $enUso], 'Ventas');

        $this->actingAs($admin)->delete(route('sucursales.destroy', $enUso))->assertSessionHasErrors('sucursal');
        $this->actingAs($admin)->delete(route('sucursales.destroy', $vacia))->assertRedirect(route('sucursales.index'));

        $this->assertNotNull($this->fila('sucursal', 'id_sucursal', $enUso));
        $this->assertNull($this->fila('sucursal', 'id_sucursal', $vacia));
    }

    public function test_no_toca_sucursales_de_otra_empresa(): void
    {
        $ajena = $this->sucursal(['id_empresa' => 2]);

        $this->actingAs($this->crearUsuario())->patch(route('sucursales.estado', $ajena))->assertNotFound();
    }

    // ── Geografía ───────────────────────────────────────────────────────────

    public function test_crea_pais_con_codigos_iso_en_mayusculas(): void
    {
        $this->actingAs($this->crearUsuario())->post(route('geografia.store', 'pais'), [
            'nombre' => ' Honduras ', 'codigo_iso2' => 'hn', 'codigo_iso3' => 'hnd', 'prefijo_tel' => '+504',
        ])->assertRedirect(route('geografia.index', ['pestana' => 'pais']))->assertSessionHasNoErrors();

        $pais = DB::table('pais')->where('nombre', 'Honduras')->first();
        $this->assertSame(['HN', 'HND', '+504'], [$pais->codigo_iso2, $pais->codigo_iso3, $pais->prefijo_tel]);
    }

    public function test_valida_unicidad_por_nivel(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->post(route('geografia.store', 'pais'), ['nombre' => 'Guatemala'])->assertSessionHasErrors('nombre');
        $this->actingAs($admin)->post(route('geografia.store', 'pais'), ['nombre' => 'Otro', 'codigo_iso2' => 'GT'])->assertSessionHasErrors('codigo_iso2');
        // Mismo nombre en otro país sí se permite; en el mismo, no.
        $this->actingAs($admin)->post(route('geografia.store', 'division'), ['id_pais' => $this->geo['gt'], 'nombre' => 'Guatemala'])->assertSessionHasErrors('nombre');
        $this->actingAs($admin)->post(route('geografia.store', 'division'), ['id_pais' => $this->geo['sv'], 'nombre' => 'Guatemala'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('geografia.store', 'municipio'), ['id_division' => $this->geo['guate'], 'nombre' => 'Mixco'])->assertSessionHasErrors('nombre');
    }

    public function test_no_elimina_lo_que_esta_en_uso(): void
    {
        $admin = $this->crearUsuario();
        $this->sucursal(['id_pais' => $this->geo['gt'], 'id_division' => $this->geo['guate'], 'id_municipio' => $this->geo['mixco']]);

        $this->actingAs($admin)->delete(route('geografia.destroy', ['pais', $this->geo['gt']]))->assertSessionHasErrors('geografia');
        $this->actingAs($admin)->delete(route('geografia.destroy', ['division', $this->geo['guate']]))->assertSessionHasErrors('geografia');
        $this->actingAs($admin)->delete(route('geografia.destroy', ['municipio', $this->geo['mixco']]))->assertSessionHasErrors('geografia');

        // Un departamento sin municipios ni sucursales sí se elimina.
        $this->actingAs($admin)->delete(route('geografia.destroy', ['division', $this->geo['sanSalvador']]))->assertSessionHasNoErrors();
        $this->assertNull($this->fila('division_geografica', 'id_division', $this->geo['sanSalvador']));
    }

    public function test_editar_y_desactivar(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->get(route('geografia.edit', ['municipio', $this->geo['mixco']]))->assertOk()->assertSee('Mixco');
        $this->actingAs($admin)->put(route('geografia.update', ['municipio', $this->geo['mixco']]), [
            'id_division' => $this->geo['guate'], 'nombre' => 'Mixco Centro', 'activo' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Mixco Centro', $this->fila('municipio', 'id_municipio', $this->geo['mixco'])->nombre);

        $this->actingAs($admin)->patch(route('geografia.estado', ['pais', $this->geo['sv']]));
        $this->assertFalse((bool) $this->fila('pais', 'id_pais', $this->geo['sv'])->activo);
    }

    public function test_un_tipo_desconocido_no_existe(): void
    {
        // No llega al controlador: cae en la ruta comodín /sistema/{any}, que solo acepta GET.
        $this->actingAs($this->crearUsuario())->post('/sistema/geografia/usuario', ['nombre' => 'x'])->assertMethodNotAllowed();
    }

    public function test_la_api_de_geografia_ya_no_existe(): void
    {
        $admin = $this->crearUsuario();

        // Clientes (5b) ya filtra la cascada en el navegador: la API de lectura también se quitó.
        $this->actingAs($admin)->getJson('/api/v1/geografia/municipios/'.$this->geo['guate'])->assertNotFound();
        $this->actingAs($admin)->postJson('/api/v1/geografia/paises', ['nombre' => 'X'])->assertNotFound();
        $this->actingAs($admin)->getJson('/api/v1/sucursales')->assertNotFound();
    }
}

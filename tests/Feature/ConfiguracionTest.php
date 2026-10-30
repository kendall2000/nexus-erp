<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Paso 3e: Configuración del sistema en Blade (solo Administrador), imágenes en Contabo. */
class ConfiguracionTest extends TestCase
{
    use EsquemaNexus;

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'nombreSistema' => 'Nexus Pruebas', 'nombreEmpresa' => 'Empresa Demo', 'slogan' => 'Gestión sin fronteras',
            'moneda' => 'Q', 'monedaCodigo' => 'gtq', 'zonaHoraria' => 'America/Guatemala', 'formatoFecha' => 'd/m/Y',
            'colorPrimario' => '#6366F1', 'colorSecundario' => '#8b5cf6', 'colorAccent' => '#06b6d4',
            'loginTitulo' => 'Bienvenido', 'loginTextBoton' => 'Entrar',
        ], $cambios);
    }

    private function contaboFalso(): void
    {
        config(['filesystems.disks.contabo' => array_merge(config('filesystems.disks.contabo'), [
            'key' => 'k', 'secret' => 's', 'bucket' => 'b', 'url' => 'https://cdn.test/nexus',
        ])]);
        Storage::fake('contabo', ['url' => 'https://cdn.test/nexus']);
    }

    public function test_solo_el_administrador_entra(): void
    {
        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');

        $this->actingAs($vendedor)->get(route('configuracion.edit'))->assertForbidden();
        $this->actingAs($vendedor)->put(route('configuracion.update'), $this->datos())->assertForbidden();
        $this->actingAs($this->crearUsuario())->get(route('configuracion.edit'))->assertOk()->assertSee('Configuración del sistema');
    }

    public function test_guarda_los_campos_permitidos_en_todas_las_filas(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->put(route('configuracion.update'), $this->datos(['pestana' => 'apariencia']))
            ->assertRedirect(route('configuracion.edit', ['pestana' => 'apariencia']))->assertSessionHasNoErrors();

        foreach (DB::table('ConfiguracionSistema')->get() as $fila) {
            $this->assertSame('Nexus Pruebas', $fila->nombreSistema);
            $this->assertSame('GTQ', $fila->monedaCodigo);
            $this->assertSame('#6366f1', $fila->colorPrimario);
            $this->assertSame((int) date('Y'), (int) $fila->footerAnio);
            $this->assertSame($admin->id_usuario, (int) $fila->actualizadoPor);
        }
        // El login usa los textos nuevos.
        auth()->logout();
        $this->get('/login')->assertSee('Bienvenido')->assertSee('Entrar')->assertSee('Gestión sin fronteras');
    }

    public function test_ignora_campos_que_no_son_de_la_pantalla(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->put(route('configuracion.update'), $this->datos(['maxIntentosSesion' => 999, 'tipo' => 'hackeado', 'estado' => 0]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['login', 'general'], DB::table('ConfiguracionSistema')->pluck('tipo')->all());
        $this->assertSame(5, (int) DB::table('ConfiguracionSistema')->value('maxIntentosSesion'));
        $this->assertSame(1, (int) DB::table('ConfiguracionSistema')->value('estado'));
    }

    public function test_valida_colores_zona_horaria_y_formato(): void
    {
        $this->actingAs($this->crearUsuario())->put(route('configuracion.update'), $this->datos([
            'colorPrimario' => 'red', 'zonaHoraria' => 'Marte/Base', 'formatoFecha' => 'Y', 'monedaCodigo' => 'QUETZAL', 'sitioWeb' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors(['colorPrimario', 'zonaHoraria', 'formatoFecha', 'monedaCodigo', 'sitioWeb']);
    }

    public function test_sube_y_quita_imagenes_en_contabo(): void
    {
        $this->contaboFalso();
        $admin = $this->crearUsuario();
        DB::table('ConfiguracionSistema')->update(['imgFondoLogin' => 'https://cdn.test/nexus/sistema/viejo.png']);
        Storage::disk('contabo')->put('sistema/viejo.png', 'x');

        $this->actingAs($admin)->put(route('configuracion.update'), $this->datos([
            'imagen' => ['imgLogo' => UploadedFile::fake()->image('logo.png')],
            'quitar' => ['imgFondoLogin'],
        ]))->assertSessionHasNoErrors();

        $fila = DB::table('ConfiguracionSistema')->where('tipo', 'login')->first();
        $this->assertStringStartsWith('https://cdn.test/nexus/sistema/', $fila->imgLogo);
        Storage::disk('contabo')->assertExists('sistema/'.basename($fila->imgLogo));
        $this->assertNull($fila->imgFondoLogin);
        Storage::disk('contabo')->assertMissing('sistema/viejo.png');
    }

    public function test_no_acepta_svg(): void
    {
        $this->contaboFalso();

        $this->actingAs($this->crearUsuario())->put(route('configuracion.update'), $this->datos([
            'imagen' => ['imgLogo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')],
        ]))->assertSessionHasErrors('imagen.imgLogo');
    }

    public function test_sin_contabo_guarda_lo_demas_y_avisa_de_la_imagen(): void
    {
        config(['filesystems.disks.contabo.key' => null]);

        $this->actingAs($this->crearUsuario())->put(route('configuracion.update'), $this->datos([
            'nombreSistema' => 'Guardado Igual',
            'imagen' => ['imgLogo' => UploadedFile::fake()->image('logo.png')],
        ]))->assertSessionHasErrors('imagen.imgLogo');

        $this->assertSame('Guardado Igual', DB::table('ConfiguracionSistema')->value('nombreSistema'));
    }

    public function test_las_apis_de_configuracion_ya_no_existen(): void
    {
        $this->getJson('/api/v1/configuracion/login')->assertNotFound();
        $this->actingAs($this->crearUsuario())->getJson('/api/v1/core/configuracion')->assertNotFound();
    }
}

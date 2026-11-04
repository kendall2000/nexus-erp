<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Los documentos del expediente de empleados no tienen enlace público: se abren desde el sistema, con permiso. */
class DocumentosPrivadosTest extends TestCase
{
    use EsquemaNexus;

    private int $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        foreach (['contabo', 'contabo_privado'] as $disco) {
            config(["filesystems.disks.{$disco}" => array_merge(config("filesystems.disks.{$disco}"), ['key' => 'k', 'secret' => 's', 'bucket' => 'b', 'url' => 'https://cdn.test/nexus'])]);
        }
        Storage::fake('contabo', ['url' => 'https://cdn.test/nexus']);
        Storage::fake('contabo_privado');
        $this->ana = DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Ana', 'primer_apellido' => 'López', 'estado' => 'ACTIVO', 'fecha_ingreso' => '2025-01-01']);
    }

    private function documento(int $empleado, string $valor, string $nombre = 'DPI de Ana'): int
    {
        return DB::table('empleado_documento')->insertGetId(['id_empleado' => $empleado, 'tipo_documento' => 'DPI', 'nombre' => $nombre, 'url_archivo' => $valor]);
    }

    public function test_se_abre_desde_el_sistema_solo_con_permiso_y_de_su_empresa(): void
    {
        Storage::disk('contabo_privado')->put("expedientes/{$this->ana}/abc.pdf", '%PDF-contenido');
        $id = $this->documento($this->ana, "expedientes/{$this->ana}/abc.pdf");

        $lector = $this->crearUsuario(['username' => 'rrhh', 'email' => 'rrhh@nexus.test'], 'RRHH');
        $this->darPermisos($lector, ['empleados.ver']);
        $respuesta = $this->actingAs($lector)->get(route('empleados.documentos.show', [$this->ana, $id]))->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('%PDF-contenido', $respuesta->streamedContent());
        $this->assertStringStartsWith('inline; filename=', $respuesta->headers->get('Content-Disposition'));
        $this->assertStringContainsString('DPI de Ana.pdf', $respuesta->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));

        // La ficha enlaza a la ruta del sistema, no al bucket.
        $this->get(route('empleados.show', $this->ana))->assertOk()
            ->assertSee(route('empleados.documentos.show', [$this->ana, $id]), false)->assertDontSee('expedientes/');

        // Con otro empleado en la URL, o un empleado de otra empresa: no existe.
        $otro = DB::table('empleado')->insertGetId(['id_empresa' => 1, 'primer_nombre' => 'Luis', 'primer_apellido' => 'Pérez', 'estado' => 'ACTIVO', 'fecha_ingreso' => '2025-01-01']);
        $this->get(route('empleados.documentos.show', [$otro, $id]))->assertNotFound();
        $ajeno = DB::table('empleado')->insertGetId(['id_empresa' => 2, 'primer_nombre' => 'Eva', 'primer_apellido' => 'Ruiz', 'estado' => 'ACTIVO', 'fecha_ingreso' => '2025-01-01']);
        Storage::disk('contabo_privado')->put("expedientes/{$ajeno}/x.pdf", 'ajeno');
        $this->get(route('empleados.documentos.show', [$ajeno, $this->documento($ajeno, "expedientes/{$ajeno}/x.pdf")]))->assertNotFound();

        // Sin permiso de ver empleados: prohibido.
        $otroUsuario = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        $this->darPermisos($otroUsuario, ['clientes.ver']);
        $this->actingAs($otroUsuario)->get(route('empleados.documentos.show', [$this->ana, $id]))->assertForbidden();
    }

    public function test_word_se_descarga_y_los_documentos_viejos_redirigen_a_su_url(): void
    {
        Storage::disk('contabo_privado')->put("expedientes/{$this->ana}/c.docx", 'word');
        $word = $this->documento($this->ana, "expedientes/{$this->ana}/c.docx", 'Contrato');
        $viejo = $this->documento($this->ana, 'https://cdn.test/nexus/empleados/1/viejo.pdf');
        $faltante = $this->documento($this->ana, "expedientes/{$this->ana}/no-existe.pdf");

        $this->actingAs($this->crearUsuario());
        $this->assertStringStartsWith('attachment;', $this->get(route('empleados.documentos.show', [$this->ana, $word]))->headers->get('Content-Disposition'));
        $this->get(route('empleados.documentos.show', [$this->ana, $viejo]))->assertRedirect('https://cdn.test/nexus/empleados/1/viejo.pdf');
        $this->get(route('empleados.documentos.show', [$this->ana, $faltante]))->assertNotFound();
    }

    public function test_el_comando_mueve_los_documentos_viejos_al_disco_privado(): void
    {
        Storage::disk('contabo')->put('empleados/1/viejo.pdf', 'viejo');
        $viejo = $this->documento($this->ana, 'https://cdn.test/nexus/empleados/1/viejo.pdf');
        $externo = $this->documento($this->ana, 'https://otro-sitio.test/archivo.pdf');
        $sinArchivo = $this->documento($this->ana, 'https://cdn.test/nexus/empleados/1/borrado.pdf');
        Storage::disk('contabo_privado')->put("expedientes/{$this->ana}/nuevo.pdf", 'nuevo');
        $nuevo = $this->documento($this->ana, "expedientes/{$this->ana}/nuevo.pdf");

        // Simulación: no cambia nada.
        $this->artisan('empleados:documentos-privados', ['--dry-run' => true])->assertSuccessful();
        Storage::disk('contabo')->assertExists('empleados/1/viejo.pdf');
        $this->assertSame('https://cdn.test/nexus/empleados/1/viejo.pdf', DB::table('empleado_documento')->where('id_doc', $viejo)->value('url_archivo'));

        $this->artisan('empleados:documentos-privados')->assertSuccessful()->expectsOutputToContain('Movidos: 1 · omitidos: 2');
        $ruta = "expedientes/{$this->ana}/viejo.pdf";
        $this->assertSame($ruta, DB::table('empleado_documento')->where('id_doc', $viejo)->value('url_archivo'));
        $this->assertSame('viejo', Storage::disk('contabo_privado')->get($ruta));
        Storage::disk('contabo')->assertMissing('empleados/1/viejo.pdf');
        $this->assertSame('https://otro-sitio.test/archivo.pdf', DB::table('empleado_documento')->where('id_doc', $externo)->value('url_archivo'));
        $this->assertStringStartsWith('https://', DB::table('empleado_documento')->where('id_doc', $sinArchivo)->value('url_archivo'));
        $this->assertSame("expedientes/{$this->ana}/nuevo.pdf", DB::table('empleado_documento')->where('id_doc', $nuevo)->value('url_archivo'));
    }
}

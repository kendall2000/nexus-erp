<?php

namespace Tests\Feature;

use App\Models\Core\Usuario;
use App\Support\Archivos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Instalación de un cliente nuevo: plantilla de catálogos (nexus:exportar-plantilla), instalador
 * (nexus:instalar) y carpeta propia de archivos en Contabo obligatoria en producción.
 */
class InstaladorTest extends TestCase
{
    use EsquemaNexus;

    private string $plantilla;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        $this->plantilla = sys_get_temp_dir().'/nexus-plantilla-'.uniqid().'.json';
        config(['app.plantilla_empresa' => $this->plantilla]);
        DB::table('pais')->insert(['nombre' => 'Guatemala', 'codigo_iso2' => 'GT', 'moneda_defecto' => 'GTQ']);
        $this->permiso('clientes.ver'); // catálogos del sistema (db:seed)
    }

    protected function tearDown(): void
    {
        File::delete($this->plantilla);
        parent::tearDown();
    }

    /** Catálogos de la empresa modelo (la 1) y su exportación como plantilla. */
    private function exportarPlantilla(): void
    {
        DB::table('concepto_nomina')->insert([
            ['id_empresa' => 1, 'codigo' => 'SALBASE', 'nombre' => 'Salario base', 'tipo' => 'INGRESO', 'afecta_igss' => 1, 'afecta_isr' => 1],
            ['id_empresa' => 1, 'codigo' => 'IGSS', 'nombre' => 'IGSS laboral', 'tipo' => 'DEDUCCION', 'afecta_igss' => 0, 'afecta_isr' => 0],
        ]);
        $vendedor = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Vendedor']);
        DB::table('rol_permiso')->insert(['id_rol' => $vendedor, 'id_permiso' => $this->permiso('clientes.ver')]);
        $padre = DB::table('departamento_org')->insertGetId(['id_empresa' => 1, 'nombre' => 'Operaciones']);
        $hijo = DB::table('departamento_org')->insertGetId(['id_empresa' => 1, 'nombre' => 'Seguridad', 'id_padre' => $padre]);
        DB::table('cargo')->insert(['id_empresa' => 1, 'nombre' => 'Agente', 'id_depto_org' => $hijo]);
        DB::table('etapa_funnel')->insert(['id_empresa' => 1, 'nombre' => 'Calificado', 'orden' => 1]);

        $this->artisan('nexus:exportar-plantilla', ['--empresa' => 1])->assertSuccessful();
    }

    /** Deja la BD como la de un cliente nuevo: con catálogos del sistema y sin empresas. */
    private function vaciarEmpresas(): void
    {
        foreach (['rol_permiso', 'usuario_rol', 'rol', 'concepto_nomina', 'cargo', 'departamento_org', 'etapa_funnel', 'sucursal', 'usuario', 'empresa'] as $tabla) {
            DB::table($tabla)->delete();
        }
    }

    private function instalar(array $opciones = [])
    {
        return $this->artisan('nexus:instalar', $opciones + [
            '--empresa' => 'Seguridad Acme', '--nit' => '1234567-8', '--correo' => 'admin@acme.test', '--consorcio' => 'Grupo Acme',
            '--password' => 'Clave-Segura-2026', '--no-interaction' => true,
        ]);
    }

    public function test_la_plantilla_lleva_catalogos_y_permisos_por_codigo_sin_datos_de_la_empresa(): void
    {
        $this->exportarPlantilla();
        $p = json_decode(File::get($this->plantilla), true);

        $this->assertSame(['IGSS', 'SALBASE'], collect($p['concepto_nomina'])->pluck('codigo')->sort()->values()->all());
        $this->assertSame(['clientes.ver'], array_values($p['permisos_rol'])[0]);
        $this->assertArrayNotHasKey('id_empresa', $p['concepto_nomina'][0]);
        $this->assertSame('Empresa Demo', $p['_origen']['empresa']);
        $this->assertArrayNotHasKey('cliente', $p); // nunca datos de operación
    }

    public function test_instala_la_primera_empresa_con_su_administrador(): void
    {
        $this->exportarPlantilla();
        $this->vaciarEmpresas();

        $this->instalar()->expectsOutputToContain('Nexus instalado para «Seguridad Acme» (Guatemala, GTQ)')->assertSuccessful();

        $empresa = DB::table('empresa')->sole();
        $this->assertSame(['Seguridad Acme', '12345678', 'GTQ'], [$empresa->nombre_comercial, $empresa->nit, $empresa->moneda_base]);
        $this->assertSame(1, DB::table('sucursal')->where(['id_empresa' => $empresa->id_empresa, 'es_casa_matriz' => true])->count());
        $this->assertSame(2, DB::table('concepto_nomina')->where('id_empresa', $empresa->id_empresa)->count());
        $seguridad = DB::table('departamento_org')->where('nombre', 'Seguridad')->first();
        $this->assertSame((int) DB::table('departamento_org')->where('nombre', 'Operaciones')->value('id_depto_org'), (int) $seguridad->id_padre);
        $this->assertSame((int) $seguridad->id_depto_org, (int) DB::table('cargo')->value('id_depto_org'));
        $vendedor = DB::table('rol')->where(['id_empresa' => $empresa->id_empresa, 'nombre' => 'Vendedor'])->value('id_rol');
        $this->assertSame(1, DB::table('rol_permiso')->where('id_rol', $vendedor)->count());

        // Administrador general de la instalación, con la contraseña dada y obligado a cambiarla.
        $admin = Usuario::query()->where('username', 'admin')->sole();
        $this->assertTrue(Hash::check('Clave-Segura-2026', $admin->password_hash));
        $this->assertTrue((bool) $admin->debe_cambiar_password);
        $this->assertTrue($admin->esAdministradorGeneral());
        $this->assertSame(['Administrador'], DB::table('usuario_rol')->join('rol', 'rol.id_rol', '=', 'usuario_rol.id_rol')->where('id_usuario', $admin->id_usuario)->pluck('nombre')->all());
        // Ve todas las empresas de la instalación, también las que se creen después.
        DB::table('empresa')->insert(['nombre_comercial' => 'Segunda', 'activo' => true]);
        $this->assertSame(2, Usuario::query()->find($admin->id_usuario)->empresasDisponibles()->count());
        $this->assertSame('Grupo Acme', DB::table('ConfiguracionSistema')->value('nombreEmpresa'));

        // Ya instalado, no vuelve a correr.
        $this->instalar()->expectsOutputToContain('ya tiene 2 empresas')->assertFailed();
    }

    public function test_genera_la_contrasena_y_revisa_los_requisitos(): void
    {
        $this->vaciarEmpresas();
        // Sin plantilla, pide generarla (o --sin-catalogos).
        $this->instalar()->expectsOutputToContain('nexus:exportar-plantilla')->assertFailed();

        $this->instalar(['--sin-catalogos' => true, '--password' => null, '--nit' => 'NIT-INVÁLIDO!'])->expectsOutputToContain('El NIT solo lleva números y letras.')->assertFailed();
        $this->instalar(['--sin-catalogos' => true, '--password' => null, '--pais' => 'XX'])->expectsOutputToContain('El país XX no existe')->assertFailed();
        $this->instalar(['--sin-catalogos' => true, '--password' => null])->expectsOutputToContain('Contraseña inicial:')->assertSuccessful();
        // Sin plantilla de roles, crea el Administrador.
        $this->assertSame(['Administrador'], DB::table('rol')->pluck('nombre')->all());
        $this->assertTrue(Usuario::query()->sole()->esAdministradorGeneral());

        // Sin catálogos del sistema, pide db:seed.
        $this->vaciarEmpresas();
        DB::table('modulo')->delete();
        $this->instalar(['--sin-catalogos' => true])->expectsOutputToContain('db:seed')->assertFailed();
    }

    public function test_en_produccion_cada_instalacion_usa_su_carpeta_de_contabo(): void
    {
        config(['filesystems.disks.contabo' => array_merge(config('filesystems.disks.contabo'), ['key' => 'k', 'secret' => 's', 'bucket' => 'b', 'url' => 'https://cdn.test/nexus', 'root' => ''])]);
        Storage::fake('contabo', ['url' => 'https://cdn.test/nexus']);
        $this->assertNull(Archivos::problema()); // en desarrollo no se exige

        $this->app['env'] = 'production';
        $this->assertStringContainsString('falta CONTABO_CARPETA', Archivos::problema());
        $this->vaciarEmpresas();
        $this->instalar(['--sin-catalogos' => true, '--password' => null])->expectsOutputToContain('falta CONTABO_CARPETA')->assertFailed();

        // Cualquier subida (logos, fotos, expedientes) se bloquea con ese mensaje.
        try {
            Archivos::subir(UploadedFile::fake()->image('logo.png'), 'empresas', 'logo');
            $this->fail('Debió bloquear la subida sin carpeta.');
        } catch (ValidationException $e) {
            $this->assertSame(['logo' => ['La subida de archivos está bloqueada: falta CONTABO_CARPETA en el .env (la carpeta propia de esta instalación en Contabo).']], $e->errors());
        }

        // Con su carpeta, o la instalación original marcada con CONTABO_USAR_RAIZ, sí.
        config(['filesystems.disks.contabo.root' => 'clientes/acme']);
        $this->assertNull(Archivos::problema());
        config(['filesystems.disks.contabo.root' => '', 'filesystems.contabo_usar_raiz' => true]);
        $this->assertNull(Archivos::problema());
    }

    public function test_la_pantalla_de_empresas_ofrece_la_plantilla_del_sistema(): void
    {
        $this->exportarPlantilla();
        $this->actingAs($this->crearUsuario());
        $this->get(route('empresas.create'))->assertOk()->assertSee('Plantilla del sistema');

        $this->post(route('empresas.store'), [
            'nombre_comercial' => 'Adinser', 'nombre_legal' => 'Adinser, S.A.', 'nit' => '555', 'id_pais' => DB::table('pais')->value('id_pais'),
            'moneda_base' => 'GTQ', 'tasa_iva' => 12, 'casa_matriz' => 'Central', 'id_empresa_modelo' => 'plantilla', 'catalogos' => ['nomina', 'crm'],
        ])->assertSessionHasNoErrors();
        $nueva = (int) DB::table('empresa')->max('id_empresa');
        $this->assertSame(2, DB::table('concepto_nomina')->where('id_empresa', $nueva)->count());
        $this->assertSame(1, DB::table('etapa_funnel')->where('id_empresa', $nueva)->count());

        File::delete($this->plantilla); // sin plantilla, «plantilla» ya no es válida
        $this->post(route('empresas.store'), ['nombre_comercial' => 'Otra', 'nombre_legal' => 'Otra', 'nit' => '556', 'id_pais' => DB::table('pais')->value('id_pais'),
            'moneda_base' => 'GTQ', 'tasa_iva' => 12, 'casa_matriz' => 'Central', 'id_empresa_modelo' => 'plantilla', 'catalogos' => ['nomina']])
            ->assertSessionHasErrors('id_empresa_modelo');
    }
}

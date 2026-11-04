<?php

namespace Tests\Feature;

use App\Models\Core\Empresa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Empresas del grupo: solo el administrador general las crea (con casa matriz y catálogos copiados), edita y desactiva. */
class EmpresasTest extends TestCase
{
    use EsquemaNexus;

    private int $guatemala;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        $this->guatemala = DB::table('pais')->insertGetId(['nombre' => 'Guatemala', 'moneda_defecto' => 'GTQ']);
        $depto = DB::table('division_geografica')->insertGetId(['id_pais' => $this->guatemala, 'nombre' => 'Guatemala']);
        DB::table('municipio')->insert(['id_division' => $depto, 'nombre' => 'Mixco']);
        DB::table('empresa')->where('id_empresa', 1)->update(['nombre_legal' => 'Empresa Demo, S.A.', 'nit' => '1234567', 'id_pais' => $this->guatemala]);
    }

    private function datos(array $extra = []): array
    {
        return $extra + [
            'nombre_comercial' => 'Adinser', 'nombre_legal' => 'Administración de Servicios, S.A.', 'nit' => '987654-3',
            'id_pais' => $this->guatemala, 'moneda_base' => 'GTQ', 'tasa_iva' => 12, 'iva_incluido_en_precio' => 1,
            'direccion' => 'Zona 10', 'casa_matriz' => 'Oficinas centrales',
        ];
    }

    /** Catálogos de la empresa 1 (la modelo). */
    private function catalogosModelo(): void
    {
        DB::table('concepto_nomina')->insert([
            ['id_empresa' => 1, 'codigo' => 'SALBASE', 'nombre' => 'Salario base', 'tipo' => 'INGRESO', 'afecta_igss' => 1, 'afecta_isr' => 1],
            ['id_empresa' => 1, 'codigo' => 'IGSS', 'nombre' => 'IGSS laboral', 'tipo' => 'DEDUCCION', 'afecta_igss' => 0, 'afecta_isr' => 0],
        ]);
        $rol = DB::table('rol')->insertGetId(['id_empresa' => 1, 'nombre' => 'Vendedor']);
        DB::table('rol_permiso')->insert(['id_rol' => $rol, 'id_permiso' => $this->permiso('clientes.ver')]);
        $padre = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'codigo' => '4', 'nombre' => 'Ingresos', 'tipo' => 'INGRESO', 'naturaleza' => 'ACREEDORA', 'permite_movimiento' => 0]);
        $hija = DB::table('cuenta_contable')->insertGetId(['id_empresa' => 1, 'id_padre' => $padre, 'codigo' => '4.1', 'nombre' => 'Servicios', 'tipo' => 'INGRESO', 'naturaleza' => 'ACREEDORA', 'nivel' => 2]);
        $linea = DB::table('linea_negocio')->insertGetId(['id_empresa' => 1, 'nombre' => 'Limpieza']);
        DB::table('tipo_servicio')->insert(['id_linea' => $linea, 'nombre' => 'Limpieza mensual', 'id_cuenta_ingreso' => $hija]);
        DB::table('etapa_funnel')->insert(['id_empresa' => 1, 'nombre' => 'Calificado', 'orden' => 1]);
        DB::table('cliente')->insert(['id_empresa' => 1, 'razon_social' => 'Cliente que no se copia']);
    }

    public function test_crear_empresa_con_casa_matriz_y_catalogos_copiados(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->catalogosModelo();

        $this->get(route('empresas.create'))->assertOk()->assertSee('Copiar catálogos de')->assertSee('Conceptos de nómina');
        $this->post(route('empresas.store'), $this->datos(['id_empresa_modelo' => 1, 'catalogos' => ['nomina', 'roles', 'contabilidad', 'comercial', 'crm']]))
            ->assertSessionHasNoErrors()->assertRedirect(route('empresas.index'))
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'conceptos de nómina: 2'));

        $nueva = DB::table('empresa')->where('nombre_comercial', 'Adinser')->first();
        $this->assertSame(['9876543', 'GTQ', 1], [$nueva->nit, $nueva->moneda_base, (int) $nueva->activo]);

        $casa = DB::table('sucursal')->where('id_empresa', $nueva->id_empresa)->sole();
        $this->assertSame(['Oficinas centrales', 1, 'Zona 10'], [$casa->nombre, (int) $casa->es_casa_matriz, $casa->direccion]);

        $this->assertSame(['IGSS', 'SALBASE'], DB::table('concepto_nomina')->where('id_empresa', $nueva->id_empresa)->orderBy('codigo')->pluck('codigo')->all());
        $this->assertSame(1, DB::table('etapa_funnel')->where('id_empresa', $nueva->id_empresa)->count());
        $this->assertSame(0, DB::table('cliente')->where('id_empresa', $nueva->id_empresa)->count());

        // El rol nuevo lleva sus permisos, sin usuarios.
        $rol = DB::table('rol')->where(['id_empresa' => $nueva->id_empresa, 'nombre' => 'Vendedor'])->value('id_rol');
        $this->assertSame([$this->permiso('clientes.ver')], DB::table('rol_permiso')->where('id_rol', $rol)->pluck('id_permiso')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(0, DB::table('usuario_rol')->where('id_rol', $rol)->count());

        // El árbol de cuentas y el tipo de servicio apuntan a las copias, no a la empresa modelo.
        $padre = DB::table('cuenta_contable')->where(['id_empresa' => $nueva->id_empresa, 'codigo' => '4'])->value('id_cuenta');
        $hija = DB::table('cuenta_contable')->where(['id_empresa' => $nueva->id_empresa, 'codigo' => '4.1'])->first();
        $this->assertSame((int) $padre, (int) $hija->id_padre);
        $linea = DB::table('linea_negocio')->where('id_empresa', $nueva->id_empresa)->value('id_linea');
        $servicio = DB::table('tipo_servicio')->where('id_linea', $linea)->sole();
        $this->assertSame((int) $hija->id_cuenta, (int) $servicio->id_cuenta_ingreso);
        $this->assertSame(2, DB::table('tipo_servicio')->count());

        // El administrador general ya puede trabajar en ella.
        $this->post(route('empresa-activa.cambiar'), ['id_empresa' => $nueva->id_empresa])->assertSessionHasNoErrors();
    }

    public function test_sin_catalogos_y_validaciones(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->catalogosModelo();

        // NIT repetido en el mismo país (aunque se escriba con guion).
        $this->post(route('empresas.store'), $this->datos(['nit' => '123456-7']))->assertSessionHasErrors('nit');
        $this->post(route('empresas.store'), $this->datos(['casa_matriz' => '']))->assertSessionHasErrors('casa_matriz');
        $this->assertSame(1, DB::table('empresa')->count());

        $this->post(route('empresas.store'), $this->datos())->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'sin catálogos copiados'));
        $nueva = (int) DB::table('empresa')->max('id_empresa');
        $this->assertSame(0, DB::table('concepto_nomina')->where('id_empresa', $nueva)->count());
        $this->assertSame(1, DB::table('sucursal')->where('id_empresa', $nueva)->count());
    }

    public function test_editar_y_la_moneda_se_fija_con_documentos(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->post(route('empresas.store'), $this->datos())->assertSessionHasNoErrors();
        $id = (int) DB::table('empresa')->max('id_empresa');

        $this->get(route('empresas.edit', $id))->assertOk()->assertDontSee('Copiar catálogos de');
        $this->put(route('empresas.update', $id), $this->datos(['nombre_comercial' => 'Adinser GT', 'moneda_base' => 'USD', 'tasa_iva' => 15]))->assertSessionHasNoErrors();
        $this->assertSame(['Adinser GT', 'USD', 15.0], [DB::table('empresa')->where('id_empresa', $id)->value('nombre_comercial'),
            DB::table('empresa')->where('id_empresa', $id)->value('moneda_base'), (float) DB::table('empresa')->where('id_empresa', $id)->value('tasa_iva')]);

        // Con facturas, la moneda base ya no cambia (el resto sí).
        DB::table('factura')->insert(['id_empresa' => $id, 'id_cliente' => DB::table('cliente')->insertGetId(['id_empresa' => $id])]);
        $this->get(route('empresas.edit', $id))->assertOk()->assertSee('No se puede cambiar');
        $this->put(route('empresas.update', $id), $this->datos(['moneda_base' => 'GTQ']))->assertSessionHasErrors('moneda_base');
        $this->assertSame('USD', DB::table('empresa')->where('id_empresa', $id)->value('moneda_base'));
        $this->assertSame(1, DB::table('auditoria_cambio')->where(['tabla_afectada' => 'empresa', 'accion' => 'UPDATE'])->count());
    }

    public function test_desactivar(): void
    {
        $admin = $this->crearUsuario();
        $this->actingAs($admin);
        $this->post(route('empresas.store'), $this->datos())->assertSessionHasNoErrors();
        $id = (int) DB::table('empresa')->max('id_empresa');

        // Su propia empresa principal, nunca.
        $this->patch(route('empresas.estado', 1))->assertSessionHasErrors('empresa');

        // Con usuarios activos cuya principal es ella, tampoco (siempre podrían entrar).
        $otro = DB::table('usuario')->insertGetId(['id_empresa' => $id, 'username' => 'luis', 'password_hash' => 'x', 'activo' => true]);
        $this->patch(route('empresas.estado', $id))->assertSessionHasErrors(['empresa' => 'No se puede desactivar «Adinser»: es la empresa principal de 1 usuario activo. Cámbialos de empresa o desactívalos primero.']);

        DB::table('usuario')->where('id_usuario', $otro)->update(['activo' => false]);
        $this->patch(route('empresas.estado', $id))->assertSessionHasNoErrors();
        $this->assertSame(0, (int) DB::table('empresa')->where('id_empresa', $id)->value('activo'));
        $this->post(route('empresa-activa.cambiar'), ['id_empresa' => $id]);
        $this->assertNotEquals($id, session('empresa_activa'));

        $this->patch(route('empresas.estado', $id))->assertSessionHasNoErrors();
        $this->assertSame(1, (int) DB::table('empresa')->where('id_empresa', $id)->value('activo'));
    }

    public function test_solo_el_administrador_general(): void
    {
        // Rol normal en su empresa principal y Administrador en la 2, trabajando en la 2: administra esa empresa,
        // pero no crea, edita ni desactiva empresas.
        DB::table('empresa')->insert(['id_empresa' => 2, 'nombre_comercial' => 'Otra']);
        $marta = $this->crearUsuario(['username' => 'marta', 'email' => 'marta@nexus.test'], 'Ventas');
        DB::table('usuario_rol')->insert(['id_usuario' => $marta->id_usuario, 'id_rol' => DB::table('rol')->insertGetId(['id_empresa' => 2, 'nombre' => 'Administrador'])]);
        $this->actingAs($marta)->post(route('empresa-activa.cambiar'), ['id_empresa' => 2]);
        $this->get(route('sucursales.index'))->assertOk();
        $this->get(route('empresas.index'))->assertForbidden();
        $this->post(route('empresas.store'), $this->datos())->assertForbidden();
        $this->patch(route('empresas.estado', 1))->assertForbidden();

        // Sin acceso total, tampoco.
        $this->actingAs($this->crearUsuario(['username' => 'ventas', 'email' => 'v@nexus.test'], 'Ventas'));
        $this->get(route('empresas.index'))->assertForbidden();

        $this->actingAs($this->crearUsuario(['username' => 'jefe', 'email' => 'j@nexus.test']));
        $this->get(route('empresas.index'))->assertOk()->assertSee('Empresa Demo')->assertSee('Tu empresa principal')->assertSee('Otra');
    }

    public function test_logo_y_datos_propios_en_sus_documentos(): void
    {
        config(['filesystems.disks.contabo' => array_merge(config('filesystems.disks.contabo'), ['key' => 'k', 'secret' => 's', 'bucket' => 'b', 'url' => 'https://cdn.test/nexus'])]);
        Storage::fake('contabo', ['url' => 'https://cdn.test/nexus']);
        DB::table('ConfiguracionSistema')->update(['imgLogo' => 'https://cdn.test/nexus/sistema/consorcio.png', 'direccion' => 'Dirección del consorcio']);
        $this->actingAs($this->crearUsuario());

        // Sin datos propios, sus documentos llevan el logo y la dirección del consorcio.
        $html = (string) $this->blade('<x-print-layout titulo="Prueba" :empresa="$e">cuerpo</x-print-layout>', ['e' => Empresa::find(1)]);
        $this->assertStringContainsString('sistema/consorcio.png', $html);
        $this->assertStringContainsString('Dirección del consorcio', $html);

        // Con logo, régimen, sitio web y dirección propios, salen los suyos.
        $this->put(route('empresas.update', 1), $this->datos([
            'nombre_comercial' => 'Empresa Demo', 'nombre_legal' => 'Empresa Demo, S.A.', 'nit' => '1234567', 'direccion' => 'Zona 4, Mixco',
            'regimen_fiscal' => 'Sujeto a pagos trimestrales ISR', 'sitio_web' => 'https://demo.test', 'leyenda_factura' => 'Gracias por su compra.',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 100),
        ]))->assertSessionHasNoErrors();
        $logo = DB::table('empresa')->where('id_empresa', 1)->value('logo_url');
        $this->assertStringStartsWith('https://cdn.test/nexus/empresas/', $logo);
        Storage::disk('contabo')->assertExists(substr($logo, strlen('https://cdn.test/nexus/')));

        $html = (string) $this->blade('<x-print-layout titulo="Prueba" :empresa="$e">cuerpo</x-print-layout>', ['e' => Empresa::find(1)]);
        foreach ([$logo, 'Sujeto a pagos trimestrales ISR', 'Zona 4, Mixco', 'https://demo.test'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
        $this->assertStringNotContainsString('consorcio.png', $html);

        // Un SVG no se acepta (puede llevar scripts); quitar el logo vuelve al del consorcio y borra el archivo.
        $this->put(route('empresas.update', 1), $this->datos(['nit' => '1234567', 'logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml')]))->assertSessionHasErrors('logo');
        $this->put(route('empresas.update', 1), $this->datos(['nit' => '1234567', 'quitar_logo' => 1]))->assertSessionHasNoErrors();
        $this->assertNull(DB::table('empresa')->where('id_empresa', 1)->value('logo_url'));
        Storage::disk('contabo')->assertMissing(substr($logo, strlen('https://cdn.test/nexus/')));
        $this->assertSame('https://cdn.test/nexus/sistema/consorcio.png', Empresa::find(1)->logoDocumentos());
    }
}

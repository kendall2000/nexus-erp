<?php

namespace Tests\Feature;

use App\Models\Core\AuditoriaCambio;
use App\Models\Core\Usuario;
use App\Support\Bitacora;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Bitácora de cambios (auditoria_cambio): registro automático y pantalla bitacora.*. */
class BitacoraTest extends TestCase
{
    use EsquemaNexus;

    /** @return list<object> */
    private function cambios(string $tabla): array
    {
        return DB::table('auditoria_cambio')->where('tabla_afectada', $tabla)->orderBy('id_cambio')->get()
            ->map(function ($c) {
                $c->antes = json_decode((string) $c->datos_anteriores, true);
                $c->despues = json_decode((string) $c->datos_nuevos, true);

                return $c;
            })->all();
    }

    public function test_permisos_de_la_bitacora(): void
    {
        $lector = $this->crearUsuario(['username' => 'l', 'email' => 'l@nexus.test'], 'Auditor');
        $this->darPermisos($lector, ['bitacora.ver']);
        $nadie = $this->crearUsuario(['username' => 'n', 'email' => 'n@nexus.test'], 'Ventas');

        $this->actingAs($nadie)->get(route('bitacora.index'))->assertForbidden();
        $this->actingAs($lector)->get(route('bitacora.index'))->assertOk()->assertSee('Bitácora de cambios')->assertDontSee('Exportar');
        $this->actingAs($lector)->get(route('bitacora.exportar'))->assertForbidden();
    }

    public function test_registra_alta_cambio_y_baja_con_usuario_e_ip(): void
    {
        $admin = $this->crearUsuario();
        DB::table('auditoria_cambio')->delete(); // el alta del usuario de prueba no interesa aquí

        $this->actingAs($admin)->post(route('bodegas.store'), ['nombre' => 'Norte', 'activo' => '1'])->assertSessionHasNoErrors();
        $id = (int) DB::table('bodega')->where('nombre', 'Norte')->value('id_bodega');
        $this->actingAs($admin)->put(route('bodegas.update', $id), ['nombre' => 'Norte 2', 'activo' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->delete(route('bodegas.destroy', $id))->assertSessionHasNoErrors();

        [$alta, $cambio, $baja] = $this->cambios('bodega');
        $this->assertSame(['INSERT', 'UPDATE', 'DELETE'], [$alta->accion, $cambio->accion, $baja->accion]);
        $this->assertSame([(string) $id, $admin->id_usuario, 1, '127.0.0.1'], [$alta->id_registro, (int) $alta->id_usuario, (int) $alta->id_empresa, $alta->ip_address]);
        $this->assertSame('Norte', $alta->despues['nombre']);
        $this->assertNull($alta->antes);
        // Solo los campos que cambiaron.
        $this->assertSame(['nombre' => 'Norte'], $cambio->antes);
        $this->assertSame(['nombre' => 'Norte 2'], $cambio->despues);
        $this->assertSame('Norte 2', $baja->antes['nombre']);
        $this->assertNull($baja->despues);
    }

    public function test_no_guarda_contrasenas_ni_cambios_de_marcas_de_tiempo(): void
    {
        $usuario = $this->crearUsuario();
        $alta = $this->cambios('usuario')[0];
        $this->assertSame(Bitacora::OCULTO, $alta->despues['password_hash']);

        $usuario->update(['ultimo_login' => now(), 'intentos_fallidos' => 2]);
        $this->assertCount(1, $this->cambios('usuario'));

        $usuario->update(['password_hash' => Hash::make('Otra-Clave-2026!'), 'nombre_completo' => 'Nuevo nombre']);
        $cambio = $this->cambios('usuario')[1];
        $this->assertSame(['password_hash' => Bitacora::OCULTO, 'nombre_completo' => 'Nuevo nombre'], $cambio->despues);
        $this->assertStringNotContainsString('$2y$', (string) $cambio->datos_nuevos.$cambio->datos_anteriores);
    }

    public function test_registra_los_permisos_de_roles_y_usuarios_por_codigo(): void
    {
        $admin = $this->crearUsuario();
        $ver = $this->permiso('bodegas.ver', ['nombre' => 'Bodegas', 'grupo' => 'Inventario']);
        $crear = $this->permiso('bodegas.crear');

        $this->actingAs($admin)->post(route('roles.store'), ['nombre' => 'Bodeguero', 'activo' => '1', 'permisos' => [$ver, $crear]])->assertSessionHasNoErrors();
        $idRol = (int) DB::table('rol')->where('nombre', 'Bodeguero')->value('id_rol');
        $this->actingAs($admin)->put(route('roles.update', $idRol), ['nombre' => 'Bodeguero', 'activo' => '1', 'permisos' => [$ver]])->assertSessionHasNoErrors();

        [$alta, $cambio] = $this->cambios('rol_permiso');
        $this->assertSame([(string) $idRol, ['bodegas.crear', 'bodegas.ver']], [$alta->id_registro, $alta->despues['permisos']]);
        $this->assertSame(['permisos' => ['bodegas.crear']], $cambio->antes); // quitados
        $this->assertSame(['permisos' => []], $cambio->despues);             // agregados

        $vendedor = $this->crearUsuario(['username' => 'v', 'email' => 'v@nexus.test'], 'Ventas');
        $this->actingAs($admin)->put(route('usuarios.permisos', $vendedor->id_usuario), ['permisos' => [$crear]])->assertSessionHasNoErrors();
        $extra = $this->cambios('usuario_permiso')[0];
        $this->assertSame([(string) $vendedor->id_usuario, ['bodegas.crear']], [$extra->id_registro, $extra->despues['permisos']]);
    }

    public function test_registra_las_reglas_de_seguridad_de_configuracion(): void
    {
        $admin = $this->crearUsuario();

        $this->actingAs($admin)->put(route('seguridad.guardar'), ['max_intentos' => 3, 'bloqueo_minutos' => 45, 'sesion_expira_min' => 120])
            ->assertSessionHasNoErrors();

        $cambio = $this->cambios('ConfiguracionSistema')[0];
        // sesionExpiraMin no cambió (ya era 120): no aparece.
        $this->assertSame(['maxIntentosSesion' => '5', 'bloqueoMinutos' => '15'], $cambio->antes);
        $this->assertSame(['maxIntentosSesion' => '3', 'bloqueoMinutos' => '45'], $cambio->despues);
    }

    public function test_la_pantalla_muestra_solo_mi_empresa_filtra_y_abre_el_detalle(): void
    {
        $admin = $this->crearUsuario();
        Bitacora::registrar('factura', '7', 'UPDATE', ['estado' => 'BORRADOR'], ['estado' => 'EMITIDA'], 1);
        $mio = (int) DB::table('auditoria_cambio')->max('id_cambio');
        $ajeno = DB::table('auditoria_cambio')->insertGetId(['id_empresa' => 2, 'tabla_afectada' => 'factura', 'id_registro' => '99', 'accion' => 'DELETE', 'datos_anteriores' => '{"nombre":"Ajena"}']);

        $this->actingAs($admin)->get(route('bitacora.index', ['tabla' => 'factura']))->assertOk()
            ->assertSee('Facturas')->assertSee('estado: BORRADOR → EMITIDA')->assertDontSee('#99');
        $this->actingAs($admin)->get(route('bitacora.index', ['accion' => 'INSERT', 'tabla' => 'factura']))->assertOk()->assertDontSee('#7');
        $this->actingAs($admin)->get(route('bitacora.show', $mio))->assertOk()->assertSee('BORRADOR')->assertSee('EMITIDA');
        $this->actingAs($admin)->get(route('bitacora.show', $ajeno))->assertNotFound();
    }

    public function test_exporta_una_fila_por_campo(): void
    {
        $admin = $this->crearUsuario();
        Bitacora::registrar('factura', '7', 'UPDATE', ['estado' => 'BORRADOR', 'total' => '10'], ['estado' => 'EMITIDA', 'total' => '12'], 1);

        $csv = $this->actingAs($admin)->get(route('bitacora.exportar', ['tabla' => 'factura']))->assertOk()->streamedContent();

        $this->assertStringContainsString('Facturas;7;estado;BORRADOR;EMITIDA', $csv);
        $this->assertStringContainsString('Facturas;7;total;10;12', $csv);
    }

    public function test_la_bitacora_es_inmutable(): void
    {
        $this->crearUsuario();
        $registro = AuditoriaCambio::query()->firstOrFail();

        $this->assertFalse($registro->update(['tabla_afectada' => 'otra']));
        $this->assertFalse($registro->delete());
        $this->assertSame('usuario', AuditoriaCambio::query()->firstOrFail()->tabla_afectada);
        $this->assertFalse(app('router')->has('bitacora.destroy') || app('router')->has('bitacora.update'));
        $this->assertInstanceOf(Usuario::class, $registro->usuario()->getRelated());
    }
}

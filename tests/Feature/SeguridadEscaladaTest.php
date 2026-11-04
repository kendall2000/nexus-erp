<?php

namespace Tests\Feature;

use App\Models\Core\Rol;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/**
 * Reproduce los ataques de escalada encontrados en la auditoría de seguridad
 * (noviembre 2026). Cada prueba falla si el hueco vuelve a abrirse.
 */
class SeguridadEscaladaTest extends TestCase
{
    use EsquemaNexus;

    private function jefe(array $permisos)
    {
        $jefe = $this->crearUsuario(['username' => 'jefe', 'email' => 'jefe@nexus.test'], 'Jefe TI');
        $this->darPermisos($jefe, $permisos);

        return $jefe;
    }

    /** Ataque: crear «Súperadmin» (pasaba como rol normal) y entrar con acceso total. */
    public function test_los_nombres_reservados_no_se_burlan_con_acentos_ni_caracteres_invisibles(): void
    {
        $jefe = $this->jefe(['roles.ver', 'roles.crear']);

        foreach (['Súperadmin', 'Admínistrador', "Super\u{200B}Administrador", 'SuperAdministrador', '  SUPERADMIN  '] as $nombre) {
            $this->actingAs($jefe)->post(route('roles.store'), ['nombre' => $nombre])->assertSessionHasErrors('nombre');
        }
        $this->assertSame(0, DB::table('rol')->whereNotIn('nombre', ['Jefe TI'])->count());

        // Validar y dar acceso usan la misma comparación: si un rol así ya existe, es acceso total en ambos lados.
        $colado = $this->crearUsuario(['username' => 'colado', 'email' => 'colado@nexus.test'], 'Súperadmin');
        $this->assertTrue(Rol::esNombreDeAccesoTotal('Súperadmin'));
        $this->assertTrue($colado->esAdministrador());
        $this->assertFalse(Rol::esNombreDeAccesoTotal('Administradora'));
        $this->assertFalse($this->crearUsuario(['username' => 'ana', 'email' => 'ana@nexus.test'], 'Administradora')->esAdministrador());
    }

    /** Ataque: desactivar el rol de un superior para verlo «sin permisos». */
    public function test_no_desactiva_ni_quita_dos_pasos_a_un_rol_con_permisos_que_no_tiene(): void
    {
        $jefe = $this->jefe(['roles.ver', 'roles.editar', 'usuarios.ver', 'usuarios.editar']);
        $gerente = $this->crearUsuario(['username' => 'gerente', 'email' => 'gerente@nexus.test'], 'Gerente');
        $this->darPermisos($gerente, ['usuarios.ver', 'facturas.anular']);
        $idGerente = DB::table('rol')->where('nombre', 'Gerente')->value('id_rol');
        DB::table('rol')->where('id_rol', $idGerente)->update(['requiere_2fa' => true]);

        $this->actingAs($jefe)->put(route('roles.update', $idGerente), ['nombre' => 'Gerente', 'activo' => '0', 'requiere_2fa' => '1'])
            ->assertSessionHasErrors('rol');
        $this->actingAs($jefe)->put(route('roles.update', $idGerente), ['nombre' => 'Gerente', 'activo' => '1'])
            ->assertSessionHasErrors('rol');
        $this->assertSame(1, (int) DB::table('rol')->where('id_rol', $idGerente)->value('activo'));
        $this->assertSame(1, (int) DB::table('rol')->where('id_rol', $idGerente)->value('requiere_2fa'));

        // Lo demás del rol sí lo puede editar, como antes.
        $this->actingAs($jefe)->put(route('roles.update', $idGerente), ['nombre' => 'Gerente', 'descripcion' => 'Gerencia', 'activo' => '1', 'requiere_2fa' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Gerencia', DB::table('rol')->where('id_rol', $idGerente)->value('descripcion'));
    }

    /** Ataque: con el rol del superior desactivado, cambiarle la contraseña y entrar como él. */
    public function test_un_rol_desactivado_no_vuelve_gestionable_a_su_usuario(): void
    {
        $jefe = $this->jefe(['usuarios.ver', 'usuarios.editar']);
        $gerente = $this->crearUsuario(['username' => 'gerente', 'email' => 'gerente@nexus.test'], 'Gerente');
        $this->darPermisos($gerente, ['usuarios.ver', 'facturas.anular']);
        DB::table('rol')->where('nombre', 'Gerente')->update(['activo' => false]);

        $this->assertFalse($jefe->puedeGestionar($gerente));
        $this->actingAs($jefe)->put(route('usuarios.update', $gerente->id_usuario), [
            'nombre_completo' => 'Gerente', 'username' => 'gerente', 'email' => 'gerente@nexus.test',
            'id_rol' => DB::table('rol')->where('nombre', 'Gerente')->value('id_rol'),
            'password' => 'Robada-Clave-2027!', 'password_confirmation' => 'Robada-Clave-2027!',
        ])->assertForbidden();
        $this->assertTrue(Hash::check('Clave-Segura-2026', $gerente->fresh()->password_hash));

        // Un administrador con el rol desactivado tampoco se vuelve gestionable.
        $admin = $this->crearUsuario(['username' => 'admin2', 'email' => 'admin2@nexus.test'], 'Administrador', rolActivo: false);
        $this->assertFalse($jefe->puedeGestionar($admin));

        // Con un subordinado real sigue funcionando.
        $vendedor = $this->crearUsuario(['username' => 'ventas', 'email' => 'ventas@nexus.test'], 'Ventas');
        $this->darPermisos($vendedor, ['usuarios.ver']);
        $this->assertTrue($jefe->puedeGestionar($vendedor));
    }

    /** Ataque: leer salarios y datos personales por la bitácora rápida sin permiso. */
    public function test_la_bitacora_rapida_exige_permiso_y_el_boton_se_oculta(): void
    {
        $sinPermiso = $this->jefe(['clientes.ver']);
        $url = route('bitacora.rapido', ['tabla' => 'historial_salarial', 'registro' => 1]);
        $this->actingAs($sinPermiso)->getJson($url)->assertForbidden();

        $idCliente = DB::table('cliente')->insertGetId(['id_empresa' => 1, 'razon_social' => 'Cliente Uno']);
        $this->actingAs($sinPermiso)->get(route('clientes.show', $idCliente))->assertOk()->assertDontSee('data-auditoria-tabla', false);

        $auditor = $this->crearUsuario(['username' => 'auditor', 'email' => 'auditor@nexus.test'], 'Auditor');
        $this->darPermisos($auditor, ['bitacora.ver', 'clientes.ver']);
        $this->actingAs($auditor)->getJson($url)->assertOk();
        $this->actingAs($auditor)->get(route('clientes.show', $idCliente))->assertOk()->assertSee('data-auditoria-tabla', false);
    }
}

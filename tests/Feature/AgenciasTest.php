<?php

namespace Tests\Feature;

use App\Models\Core\Sucursal;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Agencias como tipo de sucursal (dependen de una sucursal activa) y código de establecimiento SAT único por empresa. */
class AgenciasTest extends TestCase
{
    use EsquemaNexus;

    private int $central;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        $this->actingAs($this->crearUsuario());
        $this->central = $this->sede(['nombre' => 'Central', 'es_casa_matriz' => true, 'codigo_establecimiento' => 1]);
    }

    private function sede(array $datos): int
    {
        return DB::table('sucursal')->insertGetId($datos + ['id_empresa' => 1, 'tipo' => 'SUCURSAL', 'activo' => true, 'es_casa_matriz' => false]);
    }

    private function agencia(array $datos = []): array
    {
        return $datos + ['tipo' => 'AGENCIA', 'nombre' => 'Agencia Zona 1', 'id_sucursal_padre' => $this->central, 'codigo_establecimiento' => 2, 'activo' => '1'];
    }

    public function test_crea_agencia_bajo_una_sucursal_con_su_codigo_sat(): void
    {
        $this->get(route('sucursales.create'))->assertOk()->assertSee('Código de establecimiento SAT')->assertSee('Depende de la sucursal');
        $this->post(route('sucursales.store'), $this->agencia())->assertSessionHasNoErrors()->assertSessionHas('status', 'Agencia «Agencia Zona 1» creada.');

        $a = DB::table('sucursal')->where('nombre', 'Agencia Zona 1')->first();
        $this->assertSame(['AGENCIA', $this->central, 2], [$a->tipo, (int) $a->id_sucursal_padre, (int) $a->codigo_establecimiento]);

        // El listado la muestra bajo su sucursal, y los selects de otras pantallas la nombran con su sucursal.
        $this->get(route('sucursales.index'))->assertOk()->assertSeeInOrder(['Central', 'Agencia Zona 1', 'Agencia de Central']);
        $this->assertSame(['Central', 'Agencia Zona 1 (agencia de Central)'], Sucursal::paraSelect(1)->map->etiqueta()->all());

        // Sin tipo (formularios anteriores), es una sucursal y no guarda padre.
        $this->post(route('sucursales.store'), ['nombre' => 'Norte', 'id_sucursal_padre' => $this->central, 'activo' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(['SUCURSAL', null], [DB::table('sucursal')->where('nombre', 'Norte')->value('tipo'), DB::table('sucursal')->where('nombre', 'Norte')->value('id_sucursal_padre')]);
    }

    public function test_reglas_de_la_agencia_y_del_codigo(): void
    {
        $otra = DB::table('empresa')->insertGetId(['nombre_comercial' => 'Otra']);
        $ajena = $this->sede(['id_empresa' => $otra, 'nombre' => 'Ajena', 'codigo_establecimiento' => 5]);
        $inactiva = $this->sede(['nombre' => 'Cerrada', 'activo' => false]);
        $agencia = $this->sede(['nombre' => 'Agencia Sur', 'tipo' => 'AGENCIA', 'id_sucursal_padre' => $this->central]);

        $this->post(route('sucursales.store'), $this->agencia(['id_sucursal_padre' => '']))->assertSessionHasErrors('id_sucursal_padre');
        foreach ([$ajena, $inactiva, $agencia] as $padre) { // de otra empresa, inactiva u otra agencia
            $this->post(route('sucursales.store'), $this->agencia(['id_sucursal_padre' => $padre]))->assertSessionHasErrors('id_sucursal_padre');
        }
        $this->post(route('sucursales.store'), $this->agencia(['es_casa_matriz' => '1']))->assertSessionHasErrors('es_casa_matriz');
        $this->post(route('sucursales.store'), $this->agencia(['codigo_establecimiento' => 1]))->assertSessionHasErrors('codigo_establecimiento');
        $this->post(route('sucursales.store'), $this->agencia(['codigo_establecimiento' => 5]))->assertSessionHasNoErrors(); // el 5 es de otra empresa

        // Una sucursal con agencias no se vuelve agencia ni se desactiva; la casa matriz no puede ser agencia.
        $norte = $this->sede(['nombre' => 'Norte']);
        $this->sede(['nombre' => 'Agencia Norte', 'tipo' => 'AGENCIA', 'id_sucursal_padre' => $norte]);
        $this->put(route('sucursales.update', $norte), ['tipo' => 'AGENCIA', 'nombre' => 'Norte', 'id_sucursal_padre' => $this->central, 'activo' => '1'])->assertSessionHasErrors('tipo');
        $this->patch(route('sucursales.estado', $norte))->assertSessionHasErrors(['sucursal' => '«Norte» tiene 1 agencia activa: desactívalas o pásalas a otra sucursal primero.']);
        $this->put(route('sucursales.update', $this->central), ['tipo' => 'AGENCIA', 'nombre' => 'Central', 'id_sucursal_padre' => $norte, 'es_casa_matriz' => '1', 'activo' => '1'])
            ->assertSessionHasErrors('es_casa_matriz');

        // Ni se elimina mientras tenga agencias.
        $this->delete(route('sucursales.destroy', $norte))->assertSessionHasErrors(['sucursal' => 'No se puede eliminar «Norte»: la usan 1 agencias. Puedes desactivarla.']);
    }

    public function test_agencia_de_una_sucursal_inactiva_no_se_reactiva(): void
    {
        $norte = $this->sede(['nombre' => 'Norte']);
        $agencia = $this->sede(['nombre' => 'Agencia Norte', 'tipo' => 'AGENCIA', 'id_sucursal_padre' => $norte]);
        $this->patch(route('sucursales.estado', $agencia))->assertSessionHasNoErrors();
        $this->patch(route('sucursales.estado', $norte))->assertSessionHasNoErrors(); // ya sin agencias activas

        $this->patch(route('sucursales.estado', $agencia))->assertSessionHasErrors('sucursal');
        $this->assertSame(0, (int) DB::table('sucursal')->where('id_sucursal', $agencia)->value('activo'));

        // Pasada a otra sucursal activa, ya se puede activar.
        $this->put(route('sucursales.update', $agencia), $this->agencia(['nombre' => 'Agencia Norte', 'codigo_establecimiento' => '', 'activo' => '0']))->assertSessionHasNoErrors();
        $this->patch(route('sucursales.estado', $agencia))->assertSessionHasNoErrors();
        $this->assertSame([1, $this->central], [(int) DB::table('sucursal')->where('id_sucursal', $agencia)->value('activo'), (int) DB::table('sucursal')->where('id_sucursal', $agencia)->value('id_sucursal_padre')]);
    }
}

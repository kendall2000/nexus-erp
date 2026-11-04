<?php

namespace Tests\Feature;

use App\Support\Sistema;
use Tests\Concerns\EsquemaNexus;
use Tests\TestCase;

/** Los JS y CSS propios llevan «?v=» para que el navegador baje la versión nueva al desplegar. */
class RecursosVersionadosTest extends TestCase
{
    use EsquemaNexus;

    public function test_los_recursos_propios_llevan_la_version_del_archivo(): void
    {
        $version = filemtime(public_path('assets/js/app-vue.js'));
        $this->assertSame(asset('assets/js/app-vue.js').'?v='.$version, Sistema::recurso('assets/js/app-vue.js'));
        $this->assertSame(asset('assets/js/no-existe.js'), Sistema::recurso('assets/js/no-existe.js'));

        $this->actingAs($this->crearUsuario())->get(route('dashboard'))->assertOk()
            ->assertSee('assets/js/app-vue.js?v='.$version, false)
            ->assertSee('assets/css/theme.min.css?v=', false)
            ->assertSee('assets/js/config.js?v=', false);
    }
}

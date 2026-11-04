<?php

namespace App\Console\Commands;

use App\Models\Core\Empresa;
use App\Models\Core\Rol;
use App\Models\Core\Sucursal;
use App\Models\Core\Usuario;
use App\Support\Archivos;
use App\Support\CatalogosEmpresa;
use App\Support\Contrasenas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Deja lista la instalación de un cliente nuevo (su propia BD), después de
 * `php artisan migrate --force && php artisan db:seed --force`:
 *  - la primera empresa con su casa matriz y los catálogos de la plantilla del sistema;
 *  - su usuario administrador (administrador general de la instalación), que debe cambiar
 *    la contraseña al entrar;
 *  - el nombre del consorcio en la configuración del sistema.
 * Solo corre en una BD sin empresas. Las demás empresas se crean en Sistema → Empresas.
 */
class InstalarNexus extends Command
{
    protected $signature = 'nexus:instalar
        {--empresa= : Nombre comercial de la primera empresa}
        {--razon-social= : Razón social (por defecto, el nombre comercial)}
        {--nit= : NIT de la empresa}
        {--pais=GT : País (código ISO de 2 letras)}
        {--iva=12 : Tasa de IVA en %}
        {--consorcio= : Nombre del grupo o consorcio en el sistema (por defecto, el de la empresa)}
        {--usuario=admin : Usuario del administrador}
        {--correo= : Correo del administrador}
        {--nombre=Administrador : Nombre completo del administrador}
        {--password= : Contraseña inicial (si no se da, se genera una)}
        {--sin-catalogos : No cargar la plantilla de catálogos}';

    protected $description = 'Instala Nexus en una BD nueva: primera empresa, catálogos y usuario administrador.';

    public function handle(): int
    {
        if ($error = $this->revisarPrerrequisitos()) {
            $this->error($error);

            return self::FAILURE;
        }

        $datos = [
            'empresa' => $this->opcion('empresa', 'Nombre comercial de la primera empresa'),
            'nit' => strtoupper(str_replace([' ', '-'], '', (string) $this->opcion('nit', 'NIT de la empresa'))),
            'correo' => $this->opcion('correo', 'Correo del administrador'),
            'pais' => strtoupper((string) $this->option('pais')),
            'iva' => $this->option('iva'),
            'usuario' => $this->option('usuario'),
            'nombre' => $this->option('nombre'),
            'password' => $this->option('password'),
        ];
        $datos['razon_social'] = $this->option('razon-social') ?: $datos['empresa'];
        $datos['consorcio'] = $this->option('consorcio') ?: $datos['empresa'];

        $validador = Validator::make($datos, [
            'empresa' => ['required', 'string', 'max:100'],
            'razon_social' => ['required', 'string', 'max:200'],
            'consorcio' => ['required', 'string', 'max:100'],
            'nit' => ['required', 'max:20', 'regex:/^[0-9A-Z]+$/'],
            'pais' => ['required', 'size:2'],
            'iva' => ['required', 'numeric', 'min:0', 'max:50'],
            'usuario' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/'],
            'correo' => ['required', 'email', 'max:150'],
            'nombre' => ['required', 'string', 'max:150'],
            'password' => ['nullable', 'string', Contrasenas::regla()],
        ], ['nit.regex' => 'El NIT solo lleva números y letras.', 'usuario.regex' => 'El usuario solo lleva letras, números, punto, guion y guion bajo.']);
        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $mensaje) {
                $this->error($mensaje);
            }

            return self::FAILURE;
        }

        $pais = DB::table('pais')->where('codigo_iso2', $datos['pais'])->where('activo', true)->first(['id_pais', 'nombre', 'moneda_defecto']);
        if (! $pais || ! DB::table('moneda')->where('codigo', $pais->moneda_defecto)->exists()) {
            $this->error("El país {$datos['pais']} no existe en el catálogo o su moneda no está registrada.");

            return self::FAILURE;
        }

        $generada = $datos['password'] === null;
        $password = $datos['password'] ?? $this->contrasenaSegura();

        [$empresa, $catalogos, $usuario] = DB::transaction(function () use ($datos, $pais, $password) {
            $empresa = Empresa::create([
                'nombre_comercial' => $datos['empresa'], 'nombre_legal' => $datos['razon_social'], 'nit' => $datos['nit'],
                'id_pais' => $pais->id_pais, 'moneda_base' => $pais->moneda_defecto, 'tasa_iva' => $datos['iva'],
                'iva_incluido_en_precio' => true, 'email_corporativo' => $datos['correo'], 'activo' => true,
            ]);
            $casa = Sucursal::create(['id_empresa' => $empresa->id_empresa, 'id_pais' => $pais->id_pais, 'nombre' => 'Casa matriz',
                'tipo' => 'SUCURSAL', 'es_casa_matriz' => true, 'activo' => true]);
            $catalogos = $this->option('sin-catalogos') ? [] : CatalogosEmpresa::desdePlantilla($empresa->id_empresa, array_keys(CatalogosEmpresa::GRUPOS));

            // Siempre el rol «Administrador» (el de la plantilla o uno nuevo): con acceso total en su empresa
            // principal, el usuario es el administrador del sistema y ve y administra todas las empresas.
            $rol = Rol::query()->where('id_empresa', $empresa->id_empresa)->get()
                ->first(fn ($r) => Rol::normalizarNombre((string) $r->nombre) === Rol::normalizarNombre(Rol::ADMINISTRADOR))
                ?? Rol::query()->create(['id_empresa' => $empresa->id_empresa, 'nombre' => Rol::ADMINISTRADOR,
                    'descripcion' => 'Administrador del sistema: acceso total a todas las empresas', 'es_rol_sistema' => true, 'activo' => true]);
            $rol->update(['activo' => true, 'es_rol_sistema' => true]);

            $usuario = new Usuario(['id_empresa' => $empresa->id_empresa, 'id_sucursal' => $casa->id_sucursal, 'username' => $datos['usuario'],
                'email' => $datos['correo'], 'nombre_completo' => $datos['nombre'], 'activo' => true, 'intentos_fallidos' => 0]);
            Contrasenas::cambiar($usuario, $password, true); // la cambia al entrar por primera vez
            DB::table('usuario_rol')->insert(['id_usuario' => $usuario->id_usuario, 'id_rol' => $rol->id_rol, 'fecha_asignacion' => today()->toDateString()]);

            DB::table('ConfiguracionSistema')->update(['nombreEmpresa' => $datos['consorcio']]);

            if (! $usuario->esAdministradorGeneral()) { // salvaguarda: nunca dejar una instalación sin administrador del sistema
                throw new RuntimeException('El usuario creado no quedó como administrador del sistema; no se instaló nada.');
            }

            return [$empresa, $catalogos, $usuario];
        });

        $this->info("Nexus instalado para «{$empresa->nombre_comercial}» ({$pais->nombre}, {$empresa->moneda_base}).");
        $this->table(['Catálogo', 'Filas'], collect($catalogos)->map(fn ($n, $nombre) => [$nombre, $n])->values()->all() ?: [['(sin catálogos)', 0]]);
        $this->line("Administrador del sistema (ve y administra todas las empresas): <comment>{$usuario->username}</comment> · {$usuario->email}");
        if ($generada) {
            $this->line("Contraseña inicial: <comment>{$password}</comment>  (se muestra solo esta vez; deberá cambiarla al entrar)");
        }
        if ($problema = Archivos::problema()) {
            $this->warn($problema);
        }

        return self::SUCCESS;
    }

    private function revisarPrerrequisitos(): ?string
    {
        if (! Schema::hasTable('empresa') || ! Schema::hasTable('modulo')) {
            return 'La base de datos está vacía: corre primero «php artisan migrate --force».';
        }
        if (! DB::table('modulo')->exists() || ! DB::table('pais')->exists()) {
            return 'Faltan los catálogos del sistema: corre primero «php artisan db:seed --force».';
        }
        if ($n = DB::table('empresa')->count()) {
            return "Esta instalación ya tiene {$n} ".($n === 1 ? 'empresa' : 'empresas').': las demás se crean en Sistema → Empresas.';
        }
        if (! $this->option('sin-catalogos') && ! CatalogosEmpresa::hayPlantilla()) {
            return 'Falta la plantilla de catálogos: genérala con «php artisan nexus:exportar-plantilla» en la instalación modelo, o usa --sin-catalogos.';
        }
        // Con Contabo configurado, cada instalación debe tener su propia carpeta (los archivos de los clientes no se mezclan).
        $problema = Archivos::problema();
        if ($problema !== null && filled(config('filesystems.disks.'.Archivos::DISCO.'.key'))) {
            return $problema;
        }

        return null;
    }

    private function opcion(string $nombre, string $pregunta): ?string
    {
        $valor = $this->option($nombre);
        if (($valor === null || $valor === '') && $this->input->isInteractive()) {
            $valor = $this->ask($pregunta);
        }

        return $valor === null ? null : trim((string) $valor);
    }

    /** 16 caracteres con mayúsculas, minúsculas, números y símbolos (cumple la política por defecto). */
    private function contrasenaSegura(): string
    {
        $grupos = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '!@#$%&*?-_'];
        $clave = array_map(fn ($g) => $g[random_int(0, strlen($g) - 1)], $grupos);
        $todos = implode('', $grupos);
        while (count($clave) < 16) {
            $clave[] = $todos[random_int(0, strlen($todos) - 1)];
        }
        shuffle($clave);

        return implode('', $clave);
    }
}

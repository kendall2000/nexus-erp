<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Catálogos de arranque de una empresa nueva, para que nómina, CRM, tickets y los demás módulos
 * funcionen desde el primer día. Salen de otra empresa de la instalación (copiar()) o de la
 * plantilla del sistema (desdePlantilla(): database/seeders/datos/plantilla-empresa.json, que
 * genera `php artisan nexus:exportar-plantilla` y usa el instalador de un cliente nuevo).
 * Solo definiciones (conceptos, etapas, roles…), nunca datos de operación (clientes, empleados,
 * facturas, series).
 */
class CatalogosEmpresa
{
    /**
     * Grupo => [nombre, ayuda, tablas]. Cada tabla: [nombre, llave primaria, columna padre (misma tabla) o null].
     * El orden importa: las tablas que otras referencian van primero.
     */
    public const GRUPOS = [
        'nomina' => ['Conceptos de nómina', 'IGSS, ISR, bonificación incentivo, préstamos y demás conceptos.', [['concepto_nomina', 'id_concepto', null]]],
        'roles' => ['Roles y sus permisos', 'Solo los roles: los usuarios se asignan después.', [['rol', 'id_rol', null]]],
        'organizacion' => ['Departamentos y cargos', 'Organigrama de RR. HH.', [['departamento_org', 'id_depto_org', 'id_padre'], ['cargo', 'id_cargo', null]]],
        'contabilidad' => ['Cuentas contables y centros de costo', 'Catálogo de cuentas y centros de costo.', [['cuenta_contable', 'id_cuenta', 'id_padre'], ['centro_costo', 'id_centro', null]]],
        'comercial' => ['Líneas de negocio y tipos de servicio', 'Lo que la empresa vende.', [['linea_negocio', 'id_linea', null], ['tipo_servicio', 'id_tipo_servicio', null]]],
        'crm' => ['Etapas del embudo', 'Etapas de las oportunidades de venta.', [['etapa_funnel', 'id_etapa', null]]],
        'tickets' => ['SLA y categorías de tickets', 'Tiempos de respuesta y categorías de soporte.', [['sla_config', 'id_sla', null], ['categoria_ticket', 'id_categoria', null]]],
        'inventario' => ['Categorías de productos', 'Árbol de categorías de inventario.', [['categoria_producto', 'id_categoria', 'id_padre']]],
        'plantillas' => ['Plantillas de comunicación', 'Correos y mensajes de campañas.', [['plantilla_comunicacion', 'id_plantilla', null]]],
    ];

    /** Valor de «copiar de» que significa la plantilla del sistema en vez de otra empresa. */
    public const PLANTILLA = 'plantilla';

    /** Llaves foráneas hacia otras tablas copiadas: tabla => [columna => tabla referenciada]. */
    private const REFERENCIAS = [
        'cargo' => ['id_depto_org' => 'departamento_org'],
        'tipo_servicio' => ['id_linea' => 'linea_negocio', 'id_cuenta_ingreso' => 'cuenta_contable', 'id_centro_default' => 'centro_costo'],
    ];

    /** Columnas que no se copian: la empresa la pone el destino y las fechas, la base de datos. */
    private const SIN_COPIAR = ['id_empresa', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Copia los grupos elegidos de la empresa $origen a $destino (dentro de una transacción).
     *
     * @param  list<string>  $grupos
     * @return array<string, int> nombre del grupo => filas copiadas (solo los que copiaron algo)
     */
    public static function copiar(int $origen, int $destino, array $grupos): array
    {
        return self::cargar(self::leer($origen), $destino, $grupos);
    }

    /** Igual que copiar(), pero desde la plantilla del sistema. */
    public static function desdePlantilla(int $destino, array $grupos): array
    {
        return self::cargar(self::plantilla(), $destino, $grupos);
    }

    /** Las pruebas la cambian con config(['app.plantilla_empresa' => …]) para no tocar la del repositorio. */
    public static function rutaPlantilla(): string
    {
        return (string) config('app.plantilla_empresa', database_path('seeders/datos/plantilla-empresa.json'));
    }

    public static function hayPlantilla(): bool
    {
        return File::exists(self::rutaPlantilla());
    }

    /**
     * Catálogos de una empresa como datos: tabla => filas (con su llave original, para enlazar
     * padres y referencias al cargarlos) y «permisos_rol» => [id_rol => códigos «modulo.accion»].
     * Los permisos van por código: así sirven en otra instalación aunque cambien los ids.
     *
     * @return array<string, array<int|string, mixed>>
     */
    public static function leer(int $origen): array
    {
        $datos = [];
        foreach (self::GRUPOS as [, , $tablas]) {
            foreach ($tablas as [$tabla, $llave]) {
                $consulta = $tabla === 'tipo_servicio'
                    // tipo_servicio no tiene id_empresa: es de la empresa por su línea de negocio.
                    ? DB::table($tabla)->whereIn('id_linea', DB::table('linea_negocio')->where('id_empresa', $origen)->select('id_linea'))
                    : DB::table($tabla)->where('id_empresa', $origen);
                $datos[$tabla] = $consulta->orderBy($llave)->get()
                    ->map(fn ($fila) => array_diff_key((array) $fila, array_flip(self::SIN_COPIAR)))->all();
            }
        }
        $datos['permisos_rol'] = DB::table('rol_permiso as rp')
            ->join('rol', 'rol.id_rol', '=', 'rp.id_rol')->join('permiso as p', 'p.id_permiso', '=', 'rp.id_permiso')
            ->join('modulo as m', 'm.id_modulo', '=', 'p.id_modulo')->join('accion as a', 'a.id_accion', '=', 'p.id_accion')
            ->where('rol.id_empresa', $origen)->orderBy('rp.id_rol')->orderBy('m.codigo')->orderBy('a.codigo')
            ->get(['rp.id_rol', 'm.codigo as modulo', 'a.codigo as accion'])
            ->groupBy('id_rol')->map(fn ($p) => $p->map(fn ($x) => "{$x->modulo}.{$x->accion}")->values()->all())->all();

        return $datos;
    }

    /** @return array<string, array<int|string, mixed>> */
    public static function plantilla(): array
    {
        if (! self::hayPlantilla()) {
            throw new RuntimeException('Falta la plantilla de catálogos (database/seeders/datos/plantilla-empresa.json): genérala con «php artisan nexus:exportar-plantilla».');
        }

        return json_decode(File::get(self::rutaPlantilla()), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, array<int|string, mixed>>  $datos  lo que devuelve leer() o plantilla()
     * @param  list<string>  $grupos
     * @return array<string, int>
     */
    private static function cargar(array $datos, int $destino, array $grupos): array
    {
        $mapas = []; // tabla => [id viejo => id nuevo]
        $resumen = [];

        foreach (self::GRUPOS as $clave => [$nombre, , $tablas]) {
            if (! in_array($clave, $grupos, true)) {
                continue;
            }
            $total = 0;
            foreach ($tablas as [$tabla, $llave, $padre]) {
                $filas = $datos[$tabla] ?? [];
                if ($tabla === 'tipo_servicio') { // solo los de las líneas que se copiaron
                    $filas = array_filter($filas, fn ($f) => isset($mapas['linea_negocio'][(int) $f['id_linea']]));
                }
                $mapas[$tabla] = self::insertar($tabla, $llave, $padre, $filas, $destino, $mapas);
                $total += count($mapas[$tabla]);
            }
            if ($clave === 'roles') {
                self::darPermisos($mapas['rol'], $datos['permisos_rol'] ?? []);
            }
            if ($total) {
                $resumen[$nombre] = $total;
            }
        }

        return $resumen;
    }

    /**
     * @param  iterable<array<string, mixed>>  $filas
     * @return array<int, int> id viejo => id nuevo
     */
    private static function insertar(string $tabla, string $llave, ?string $padre, iterable $filas, int $destino, array $mapas): array
    {
        $mapa = [];
        $padres = [];
        foreach ($filas as $fila) {
            $datos = array_diff_key($fila, array_flip([$llave, ...self::SIN_COPIAR]));
            if ($tabla !== 'tipo_servicio') {
                $datos['id_empresa'] = $destino;
            }
            if ($padre) {
                $padres[(int) $fila[$llave]] = $fila[$padre] ?? null;
                $datos[$padre] = null; // se enlaza al final, cuando ya existen todos
            }
            foreach (self::REFERENCIAS[$tabla] ?? [] as $columna => $referida) {
                if (($datos[$columna] ?? null) !== null) {
                    // Si esa tabla no se copió, la referencia queda vacía (la línea siempre está).
                    $datos[$columna] = $mapas[$referida][(int) $datos[$columna]] ?? null;
                }
            }
            $mapa[(int) $fila[$llave]] = (int) DB::table($tabla)->insertGetId($datos, $llave);
        }

        foreach ($padres as $viejo => $suPadre) {
            if ($suPadre !== null && isset($mapa[(int) $suPadre])) {
                DB::table($tabla)->where($llave, $mapa[$viejo])->update([$padre => $mapa[(int) $suPadre]]);
            }
        }

        return $mapa;
    }

    /**
     * @param  array<int, int>  $roles  id viejo => id nuevo
     * @param  array<int|string, list<string>>  $permisos  id viejo => códigos «modulo.accion»
     */
    private static function darPermisos(array $roles, array $permisos): void
    {
        $ids = DB::table('permiso as p')->join('modulo as m', 'm.id_modulo', '=', 'p.id_modulo')->join('accion as a', 'a.id_accion', '=', 'p.id_accion')
            ->get(['p.id_permiso', 'm.codigo as modulo', 'a.codigo as accion'])
            ->mapWithKeys(fn ($p) => ["{$p->modulo}.{$p->accion}" => (int) $p->id_permiso]);

        foreach ($roles as $viejo => $nuevo) {
            foreach ($permisos[$viejo] ?? [] as $codigo) {
                if (isset($ids[$codigo])) { // un permiso que no existe en esta instalación se omite
                    DB::table('rol_permiso')->insert(['id_rol' => $nuevo, 'id_permiso' => $ids[$codigo], 'asignado_at' => now()]);
                }
            }
        }
    }
}

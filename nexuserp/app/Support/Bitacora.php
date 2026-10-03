<?php

namespace App\Support;

use App\Models\Core\AuditoriaAcceso;
use App\Models\Core\AuditoriaCambio;
use App\Models\Inventario\StockBodega;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bitácora de cambios (auditoria_cambio): quién creó, cambió o borró qué, cuándo y desde qué IP.
 *
 *  - Los modelos Eloquent se registran solos (escuchar()): INSERT con los datos nuevos, UPDATE solo
 *    con los campos que cambiaron (antes y después) y DELETE con los datos que tenía.
 *  - Lo que no pasa por eventos de modelo (permisos de roles y usuarios, ConfiguracionSistema)
 *    se registra a mano con registrar().
 *  - Las contraseñas, tokens y secretos nunca se guardan: queda «(oculto)» para saber que cambiaron.
 *  - La bitácora es inmutable: no hay pantalla ni ruta para editarla o borrarla.
 */
class Bitacora
{
    public const ACCIONES = [
        'INSERT' => ['Creó', 'success'],
        'UPDATE' => ['Modificó', 'info'],
        'DELETE' => ['Eliminó', 'danger'],
    ];

    public const OCULTO = '(oculto)';

    /** Modelos que no se registran: la propia bitácora, accesos y saldos derivados del kardex. */
    private const EXCLUIDOS = [AuditoriaCambio::class, AuditoriaAcceso::class, StockBodega::class];

    /** Columnas que no cuentan como cambio (marcas de tiempo y contadores del inicio de sesión). */
    private const IGNORADAS = ['created_at', 'updated_at', 'fechaActualizacion', 'ultimo_login', 'intentos_fallidos', 'remember_token'];

    /** Columnas cuyo valor nunca se guarda, además de las $hidden de cada modelo. */
    private const SENSIBLES = ['password', 'password_hash', 'token_reset', 'two_factor_secret', 'two_factor_recovery_codes'];

    /** Nombre legible de las tablas más comunes (las demás se muestran con su nombre). */
    public const TABLAS = [
        'usuario' => 'Usuarios', 'rol' => 'Roles', 'rol_permiso' => 'Permisos de rol', 'usuario_permiso' => 'Permisos extra de usuario',
        'modulo' => 'Módulos', 'ConfiguracionSistema' => 'Configuración del sistema', 'sucursal' => 'Sucursales',
        'division_geografica' => 'Divisiones geográficas', 'municipio' => 'Municipios', 'pais' => 'Países',
        'cliente' => 'Clientes', 'contacto_cliente' => 'Contactos de cliente', 'contrato_servicio' => 'Contratos',
        'prospecto' => 'Prospectos', 'oportunidad' => 'Oportunidades', 'propuesta' => 'Propuestas', 'campana' => 'Campañas',
        'ticket' => 'Tickets', 'ticket_comentario' => 'Comentarios de ticket', 'linea_negocio' => 'Líneas de negocio', 'tipo_servicio' => 'Tipos de servicio',
        'producto' => 'Productos', 'categoria_producto' => 'Categorías', 'bodega' => 'Bodegas', 'movimiento_inventario' => 'Kardex',
        'proveedor' => 'Proveedores', 'orden_compra' => 'Órdenes de compra', 'detalle_orden_compra' => 'Líneas de orden de compra',
        'recepcion_mercaderia' => 'Recepciones', 'factura' => 'Facturas', 'detalle_factura' => 'Líneas de factura', 'pago' => 'Pagos',
        'presupuesto_anual' => 'Presupuesto', 'centro_costo' => 'Centros de costo', 'cuenta_contable' => 'Cuentas contables',
        'serie_facturacion' => 'Series de facturación', 'empleado' => 'Empleados', 'contrato_laboral' => 'Contratos laborales',
        'historial_salarial' => 'Historial salarial', 'periodo_nomina' => 'Nómina', 'prestamo_empleado' => 'Préstamos',
        'asistencia' => 'Asistencia', 'solicitud_ausencia' => 'Ausencias', 'cobertura_rotativo' => 'Coberturas de rotativos', 'departamento_org' => 'Departamentos', 'cargo' => 'Cargos',
    ];

    /** Registra los eventos created / updated / deleted de todos los modelos (AppServiceProvider). */
    public static function escuchar(): void
    {
        Event::listen('eloquent.created: *', fn (string $evento, array $datos) => self::deModelo($datos[0], 'INSERT'));
        Event::listen('eloquent.updated: *', fn (string $evento, array $datos) => self::deModelo($datos[0], 'UPDATE'));
        Event::listen('eloquent.deleted: *', fn (string $evento, array $datos) => self::deModelo($datos[0], 'DELETE'));
    }

    public static function deModelo(Model $modelo, string $accion): void
    {
        if (in_array($modelo::class, self::EXCLUIDOS, true)) {
            return;
        }
        $ocultas = array_merge(self::SENSIBLES, $modelo->getHidden());

        [$antes, $despues] = match ($accion) {
            'INSERT' => [null, self::limpiar($modelo->getAttributes(), $ocultas)],
            'DELETE' => [self::limpiar($modelo->getRawOriginal(), $ocultas), null],
            default => self::diferencia($modelo->getRawOriginal(), $modelo->getChanges(), $ocultas),
        };
        if ($accion === 'UPDATE' && ! $despues) {
            return; // solo cambiaron marcas de tiempo o contadores de sesión
        }

        self::registrar($modelo->getTable(), (string) $modelo->getKey(), $accion, $antes, $despues, $modelo->getAttribute('id_empresa'));
    }

    /**
     * Registro manual. Nunca interrumpe la operación: si falla, queda en el log de Laravel.
     *
     * @param  array<string, mixed>|null  $antes
     * @param  array<string, mixed>|null  $despues
     */
    public static function registrar(string $tabla, string $idRegistro, string $accion, ?array $antes, ?array $despues, mixed $idEmpresa = null): void
    {
        try {
            $usuario = Auth::user();
            DB::table('auditoria_cambio')->insert([
                'id_usuario' => $usuario?->getAuthIdentifier(),
                'id_empresa' => $usuario->id_empresa ?? (is_numeric($idEmpresa) ? (int) $idEmpresa : null),
                'tabla_afectada' => mb_substr($tabla, 0, 100),
                'id_registro' => mb_substr($idRegistro, 0, 36),
                'accion' => $accion,
                'datos_anteriores' => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                'datos_nuevos' => $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                'ip_address' => app()->bound('request') ? request()->ip() : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('No se pudo registrar en la bitácora de cambios', ['tabla' => $tabla, 'id' => $idRegistro, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Registra el cambio de una lista (p. ej. los permisos de un rol) si cambió.
     *
     * @param  iterable<mixed>  $antes
     * @param  iterable<mixed>  $despues
     */
    public static function registrarLista(string $tabla, string $idRegistro, string $campo, iterable $antes, iterable $despues, mixed $idEmpresa = null): void
    {
        $antes = collect($antes)->map(fn ($v) => (string) $v)->sort()->values()->all();
        $despues = collect($despues)->map(fn ($v) => (string) $v)->sort()->values()->all();
        if ($antes === $despues) {
            return;
        }
        $quitados = array_values(array_diff($antes, $despues));
        $agregados = array_values(array_diff($despues, $antes));
        self::registrar($tabla, $idRegistro, 'UPDATE', [$campo => $quitados], [$campo => $agregados], $idEmpresa);
    }

    /**
     * Registra un cambio de ConfiguracionSistema. Se llama antes del update (que se aplica
     * igual a todas sus filas), con los valores que se van a guardar.
     *
     * @param  array<string, mixed>  $cambios
     */
    public static function configuracion(array $cambios): void
    {
        $actual = (array) (DB::table('ConfiguracionSistema')->orderBy('idConfig')->first() ?? []);
        [$antes, $despues] = self::diferencia($actual, $cambios);
        if ($despues) {
            self::registrar('ConfiguracionSistema', (string) ($actual['idConfig'] ?? 0), 'UPDATE', $antes, $despues);
        }
    }

    /**
     * Códigos «modulo.accion» de los permisos indicados, para que la bitácora se lea sin ids.
     *
     * @param  iterable<int|string>  $ids
     * @return list<string>
     */
    public static function codigosPermiso(iterable $ids): array
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->values()->all();

        return $ids ? DB::table('permiso')->join('modulo', 'modulo.id_modulo', '=', 'permiso.id_modulo')
            ->join('accion', 'accion.id_accion', '=', 'permiso.id_accion')->whereIn('permiso.id_permiso', $ids)
            ->get(['modulo.codigo AS modulo', 'accion.codigo AS accion'])->map(fn ($p) => "{$p->modulo}.{$p->accion}")->all() : [];
    }

    /**
     * Antes y después de los campos que cambiaron, sin los ignorados y con los sensibles ocultos.
     *
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $cambios
     * @param  list<string>  $ocultas
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    public static function diferencia(array $original, array $cambios, array $ocultas = []): array
    {
        $antes = $despues = [];
        foreach ($cambios as $campo => $valor) {
            if (in_array($campo, self::IGNORADAS, true) || self::normalizar($original[$campo] ?? null) === self::normalizar($valor)) {
                continue;
            }
            $sensible = in_array($campo, $ocultas, true);
            $antes[$campo] = $sensible ? self::OCULTO : self::normalizar($original[$campo] ?? null);
            $despues[$campo] = $sensible ? self::OCULTO : self::normalizar($valor);
        }

        return [$antes ?: null, $despues ?: null];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  list<string>  $ocultas
     * @return array<string, mixed>
     */
    private static function limpiar(array $datos, array $ocultas): array
    {
        $limpio = [];
        foreach ($datos as $campo => $valor) {
            if (in_array($campo, self::IGNORADAS, true)) {
                continue;
            }
            $limpio[$campo] = in_array($campo, $ocultas, true) ? ($valor === null ? null : self::OCULTO) : self::normalizar($valor);
        }

        return $limpio;
    }

    /** Valores escalares comparables («1» y 1, fechas como texto). */
    private static function normalizar(mixed $valor): mixed
    {
        return match (true) {
            $valor instanceof \DateTimeInterface => $valor->format('Y-m-d H:i:s'),
            $valor instanceof \BackedEnum => $valor->value,
            is_bool($valor) => $valor ? '1' : '0',
            is_int($valor), is_float($valor) => is_float($valor) && floor($valor) == $valor ? (string) (int) $valor : (string) $valor,
            is_array($valor), is_object($valor) => json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
            is_string($valor) && is_numeric($valor) && str_contains($valor, '.') => rtrim(rtrim($valor, '0'), '.'),
            default => $valor,
        };
    }

    /** Campos que identifican un registro, en orden de preferencia, para el resumen de altas y bajas. */
    private const DESCRIPTORES = ['nombre', 'nombre_completo', 'razon_social', 'username', 'numero_factura', 'numero_oc', 'codigo', 'titulo', 'asunto', 'descripcion'];

    /**
     * Filas campo / antes / después de un registro de la bitácora.
     *
     * @return list<array{campo: string, antes: mixed, despues: mixed}>
     */
    public static function campos(AuditoriaCambio $cambio): array
    {
        $antes = $cambio->datos_anteriores ?? [];
        $despues = $cambio->datos_nuevos ?? [];
        $filas = [];
        foreach (array_unique([...array_keys($antes), ...array_keys($despues)]) as $campo) {
            $filas[] = ['campo' => (string) $campo, 'antes' => $antes[$campo] ?? null, 'despues' => $despues[$campo] ?? null];
        }

        return $filas;
    }

    /** Resumen de una línea: los campos que cambiaron o el nombre del registro creado o eliminado. */
    public static function resumen(AuditoriaCambio $cambio, int $maximo = 3): string
    {
        $texto = fn (mixed $v) => $v === null || $v === '' ? '∅' : (is_array($v) ? implode(', ', $v) : mb_strimwidth((string) $v, 0, 40, '…'));
        $datos = $cambio->datos_nuevos ?? $cambio->datos_anteriores ?? [];

        if ($cambio->accion !== 'UPDATE') {
            foreach (self::DESCRIPTORES as $campo) {
                if (! empty($datos[$campo]) && is_scalar($datos[$campo])) {
                    return $texto($datos[$campo]);
                }
            }

            return '';
        }

        $antes = $cambio->datos_anteriores ?? [];
        $partes = [];
        foreach (array_slice($datos, 0, $maximo, true) as $campo => $nuevo) {
            // En las listas (permisos, roles) «antes» son los quitados y «después» los agregados.
            $partes[] = is_array($nuevo) || is_array($antes[$campo] ?? null)
                ? $campo.': '.trim((($antes[$campo] ?? []) ? '−'.$texto($antes[$campo]) : '').' '.($nuevo ? '+'.$texto($nuevo) : ''))
                : $campo.': '.$texto($antes[$campo] ?? null).' → '.$texto($nuevo);
        }
        $resto = count($datos) - $maximo;

        return implode(' · ', $partes).($resto > 0 ? " (+{$resto} más)" : '');
    }

    /** Etiqueta legible de una tabla. */
    public static function tabla(string $tabla): string
    {
        return self::TABLAS[$tabla] ?? ucfirst(str_replace('_', ' ', $tabla));
    }
}

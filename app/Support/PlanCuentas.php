<?php

namespace App\Support;

use App\Models\Core\CuentaContable;
use Illuminate\Support\Facades\DB;

/**
 * Reglas del plan de cuentas, compartidas por el formulario y el importador.
 * Se valida el plan completo tal como quedaría (no fila por fila):
 *  - la cuenta padre existe en la empresa y no hay ciclos;
 *  - la padre es del mismo tipo y es de agrupación (no permite movimientos);
 *  - el nivel es la profundidad en el árbol (1 = raíz).
 */
class PlanCuentas
{
    public const TIPOS = ['ACTIVO', 'PASIVO', 'PATRIMONIO', 'INGRESO', 'GASTO', 'COSTO'];

    public const NATURALEZAS = ['DEUDORA', 'ACREEDORA'];

    /** Naturaleza habitual de cada tipo (se sugiere en el formulario y se usa si el archivo no la trae). */
    public const NATURALEZA_DE = [
        'ACTIVO' => 'DEUDORA', 'GASTO' => 'DEUDORA', 'COSTO' => 'DEUDORA',
        'PASIVO' => 'ACREEDORA', 'PATRIMONIO' => 'ACREEDORA', 'INGRESO' => 'ACREEDORA',
    ];

    /**
     * Plan actual de la empresa: codigo => [padre (código o null), tipo, permite_movimiento, nombre].
     *
     * @return array<string, array{padre: ?string, tipo: string, permite_movimiento: bool, nombre: string}>
     */
    public static function actual(int $idEmpresa): array
    {
        $cuentas = CuentaContable::query()->where('id_empresa', $idEmpresa)->get(['id_cuenta', 'id_padre', 'codigo', 'nombre', 'tipo', 'permite_movimiento']);
        $codigoDe = $cuentas->pluck('codigo', 'id_cuenta');
        $plan = [];
        foreach ($cuentas as $c) {
            $plan[$c->codigo] = ['padre' => $c->id_padre ? ($codigoDe[$c->id_padre] ?? null) : null, 'tipo' => $c->tipo,
                'permite_movimiento' => (bool) $c->permite_movimiento, 'nombre' => $c->nombre];
        }

        return $plan;
    }

    /**
     * Errores del plan, por código de cuenta. Solo revisa las cuentas en $revisar (todas si es null).
     *
     * @return array<string, list<string>>
     */
    public static function errores(array $plan, ?array $revisar = null): array
    {
        $errores = [];
        foreach ($revisar ?? array_keys($plan) as $codigo) {
            $cuenta = $plan[$codigo];
            $padre = $cuenta['padre'];
            if ($padre === null) {
                continue;
            }
            if (! isset($plan[$padre])) {
                $errores[$codigo][] = "La cuenta padre «{$padre}» no existe.";

                continue;
            }
            // Ciclo: subiendo por los padres se vuelve a la misma cuenta.
            $paso = $padre;
            for ($i = 0; $paso !== null && $i <= count($plan); $i++) {
                if ($paso === $codigo) {
                    $errores[$codigo][] = 'Una cuenta no puede quedar debajo de sí misma ni de una de sus subcuentas.';

                    continue 2;
                }
                $paso = $plan[$paso]['padre'] ?? null;
            }
            if ($plan[$padre]['tipo'] !== $cuenta['tipo']) {
                $errores[$codigo][] = "La cuenta padre «{$padre}» es de tipo {$plan[$padre]['tipo']}; la subcuenta debe ser del mismo tipo.";
            }
            if ($plan[$padre]['permite_movimiento']) {
                $errores[$codigo][] = "La cuenta padre «{$padre}» permite movimientos; para tener subcuentas debe ser de agrupación.";
            }
        }

        return $errores;
    }

    /** @return array<string, int> codigo => nivel */
    public static function niveles(array $plan): array
    {
        $niveles = [];
        $nivel = function (string $codigo) use (&$nivel, &$niveles, $plan): int {
            return $niveles[$codigo] ??= ($p = $plan[$codigo]['padre'] ?? null) !== null && isset($plan[$p]) ? $nivel($p) + 1 : 1;
        };
        foreach (array_keys($plan) as $codigo) {
            $nivel($codigo);
        }

        return $niveles;
    }

    /** Recalcula el nivel de todas las cuentas de la empresa (tras mover una rama o importar). */
    public static function recalcularNiveles(int $idEmpresa): void
    {
        $niveles = self::niveles(self::actual($idEmpresa));
        foreach (DB::table('cuenta_contable')->where('id_empresa', $idEmpresa)->get(['id_cuenta', 'codigo', 'nivel']) as $c) {
            if ((int) $c->nivel !== $niveles[$c->codigo]) {
                DB::table('cuenta_contable')->where('id_cuenta', $c->id_cuenta)->update(['nivel' => $niveles[$c->codigo]]);
            }
        }
    }
}

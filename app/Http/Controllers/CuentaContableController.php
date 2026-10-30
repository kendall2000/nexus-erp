<?php

namespace App\Http\Controllers;

use App\Models\Core\CuentaContable;
use App\Support\ExportarCsv;
use App\Support\PlanCuentas;
use App\Support\Referencias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plan de cuentas. Permisos: cuentas_contables.ver / crear / editar / eliminar / exportar;
 * importar exige crear y editar (el archivo puede traer cuentas nuevas y existentes).
 * Las reglas del árbol están en App\Support\PlanCuentas.
 */
class CuentaContableController extends Controller
{
    /** Dónde se usa una cuenta (no se puede eliminar si aparece aquí). */
    private const USOS = [
        'presupuesto_anual' => 'presupuestos',
        'detalle_orden_compra' => 'líneas de órdenes de compra',
        'detalle_factura' => 'líneas de facturas',
        'producto.id_cuenta_gasto' => 'productos',
        'tipo_servicio.id_cuenta_ingreso' => 'tipos de servicio',
    ];

    private const ENCABEZADOS = ['Código', 'Nombre', 'Tipo', 'Naturaleza', 'Código padre', 'Permite movimiento'];

    private const MAX_FILAS = 2000;

    public function index(Request $request): View
    {
        $filtrando = (bool) array_filter($request->only(['buscar', 'tipo', 'estado']));
        $cuentas = $this->filtradas($request)->withCount('hijas')->orderBy('codigo')->get();

        return view('cuentas-contables.index', [
            // Sin filtros se muestra el árbol (padres antes que hijas); con filtros, la lista de coincidencias.
            'cuentas' => $filtrando ? $cuentas : $this->ordenArbol($cuentas),
            'filtrando' => $filtrando,
            'tipos' => PlanCuentas::TIPOS,
            'filtros' => $request->only(['buscar', 'tipo', 'estado']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $cuentas = $this->filtradas($request)->with('padre:id_cuenta,codigo')->orderBy('codigo')->get();

        return ExportarCsv::descargar('cuentas_contables', [...self::ENCABEZADOS, 'Nivel', 'Activa'],
            $cuentas->map(fn ($c) => [$c->codigo, $c->nombre, $c->tipo, $c->naturaleza, $c->padre?->codigo, $c->permite_movimiento, $c->nivel, $c->activo]));
    }

    public function create(Request $request): View
    {
        $padre = $request->filled('padre') ? $this->deMiEmpresa($request)->find($request->integer('padre')) : null;

        return $this->formulario($request, new CuentaContable([
            'id_padre' => $padre?->id_cuenta,
            'tipo' => $padre?->tipo ?? 'GASTO',
            'naturaleza' => $padre?->naturaleza ?? 'DEUDORA',
            'permite_movimiento' => true,
            'activo' => true,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $idEmpresa = $request->user()->id_empresa;
        $cuenta = DB::transaction(function () use ($request, $idEmpresa) {
            $cuenta = CuentaContable::create($this->validar($request) + ['id_empresa' => $idEmpresa, 'nivel' => 1]);
            PlanCuentas::recalcularNiveles($idEmpresa);

            return $cuenta;
        });

        return redirect()->route('cuentas-contables.index')->with('status', "Cuenta «{$cuenta->etiqueta}» creada.");
    }

    public function edit(Request $request, int $cuenta): View
    {
        return $this->formulario($request, $this->deMiEmpresa($request)->findOrFail($cuenta));
    }

    public function update(Request $request, int $cuenta): RedirectResponse
    {
        $cuenta = $this->deMiEmpresa($request)->findOrFail($cuenta);
        DB::transaction(function () use ($request, $cuenta) {
            $cuenta->update($this->validar($request, $cuenta));
            PlanCuentas::recalcularNiveles($cuenta->id_empresa);
        });

        return redirect()->route('cuentas-contables.index')->with('status', "Cuenta «{$cuenta->etiqueta}» actualizada.");
    }

    public function estado(Request $request, int $cuenta): RedirectResponse
    {
        $cuenta = $this->deMiEmpresa($request)->findOrFail($cuenta);
        $cuenta->update(['activo' => ! $cuenta->activo]);

        return back()->with('status', "Cuenta «{$cuenta->etiqueta}» ".($cuenta->activo ? 'activada.' : 'desactivada.'));
    }

    public function destroy(Request $request, int $cuenta): RedirectResponse
    {
        $cuenta = $this->deMiEmpresa($request)->findOrFail($cuenta);
        if ($cuenta->hijas()->exists()) {
            return back()->withErrors(['cuenta' => "No se puede eliminar «{$cuenta->etiqueta}»: tiene subcuentas."]);
        }
        if ($uso = Referencias::enUso('id_cuenta', $cuenta->id_cuenta, self::USOS)) {
            return back()->withErrors(['cuenta' => "No se puede eliminar «{$cuenta->etiqueta}»: la usan {$uso}. Puedes desactivarla."]);
        }
        $cuenta->delete();

        return redirect()->route('cuentas-contables.index')->with('status', "Cuenta «{$cuenta->etiqueta}» eliminada.");
    }

    // ── Importar desde Excel / CSV ───────────────────────────────────────

    public function plantilla(): StreamedResponse
    {
        return ExportarCsv::descargar('plantilla_cuentas_contables', self::ENCABEZADOS, [
            ['5', 'GASTOS', 'GASTO', 'DEUDORA', '', 'No'],
            ['5.01', 'Gastos de personal', 'GASTO', 'DEUDORA', '5', 'No'],
            ['5.01.001', 'Sueldos y salarios', 'GASTO', 'DEUDORA', '5.01', 'Sí'],
        ]);
    }

    public function importarForm(): View
    {
        return view('cuentas-contables.importar', ['analisis' => session('importacion_cuentas')]);
    }

    /** Lee el archivo y muestra la vista previa; no guarda nada hasta confirmar. */
    public function importarPrevia(Request $request): RedirectResponse
    {
        $request->validate(['archivo' => ['required', 'file', 'max:2048', 'mimes:xlsx,csv,txt']], [
            'archivo.mimes' => 'El archivo debe ser Excel (.xlsx) o CSV.',
            'archivo.max' => 'El archivo no puede pasar de 2 MB.',
        ]);

        $filas = $this->leerArchivo($request->file('archivo'));
        $analisis = $this->analizar($filas, $request->user()->id_empresa);
        session(['importacion_cuentas' => $analisis]);

        return redirect()->route('cuentas-contables.importar');
    }

    public function importarConfirmar(Request $request): RedirectResponse
    {
        $previa = session('importacion_cuentas');
        if (! $previa) {
            return redirect()->route('cuentas-contables.importar')->withErrors(['archivo' => 'Vuelve a subir el archivo.']);
        }
        $idEmpresa = $request->user()->id_empresa;

        // Se vuelve a analizar contra el plan actual por si cambió desde la vista previa.
        $analisis = $this->analizar($previa['filas'], $idEmpresa);
        if ($analisis['errores']) {
            session(['importacion_cuentas' => $analisis]);

            return redirect()->route('cuentas-contables.importar')->withErrors(['archivo' => 'El archivo tiene errores; corrígelos antes de importar.']);
        }

        DB::transaction(function () use ($analisis, $idEmpresa) {
            $existentes = CuentaContable::query()->where('id_empresa', $idEmpresa)->get()->keyBy('codigo');
            // 1) crear / actualizar datos; 2) enlazar padres (ya existen todas); 3) niveles.
            foreach ($analisis['filas'] as $f) {
                $datos = ['nombre' => $f['nombre'], 'tipo' => $f['tipo'], 'naturaleza' => $f['naturaleza'], 'permite_movimiento' => $f['permite_movimiento']];
                if ($c = $existentes->get($f['codigo'])) {
                    $c->update($datos);
                } else {
                    $existentes[$f['codigo']] = CuentaContable::create($datos + ['id_empresa' => $idEmpresa, 'codigo' => $f['codigo'], 'nivel' => 1, 'activo' => true]);
                }
            }
            foreach ($analisis['filas'] as $f) {
                $existentes[$f['codigo']]->update(['id_padre' => $f['padre'] !== null ? $existentes[$f['padre']]->id_cuenta : null]);
            }
            PlanCuentas::recalcularNiveles($idEmpresa);
        });
        session()->forget('importacion_cuentas');

        return redirect()->route('cuentas-contables.index')
            ->with('status', "Importación completada: {$analisis['nuevas']} cuentas nuevas y {$analisis['actualizar']} actualizadas.");
    }

    public function importarCancelar(): RedirectResponse
    {
        session()->forget('importacion_cuentas');

        return redirect()->route('cuentas-contables.importar');
    }

    // ── Privados ─────────────────────────────────────────────────────────

    private function formulario(Request $request, CuentaContable $cuenta): View
    {
        $plan = PlanCuentas::actual($request->user()->id_empresa);
        // Una cuenta no puede colgar de sí misma ni de sus subcuentas.
        $excluir = [];
        if ($cuenta->exists) {
            foreach ($plan as $codigo => $c) {
                for ($p = $codigo; $p !== null; $p = $plan[$p]['padre'] ?? null) {
                    if ($p === $cuenta->codigo) {
                        $excluir[] = $codigo;
                        break;
                    }
                }
            }
        }

        return view('cuentas-contables.form', [
            'cuenta' => $cuenta,
            'padres' => $this->deMiEmpresa($request)->where('permite_movimiento', false)->whereNotIn('codigo', $excluir)->orderBy('codigo')->get(),
            'tieneHijas' => $cuenta->exists && $cuenta->hijas()->exists(),
            'tipos' => PlanCuentas::TIPOS,
            'naturalezas' => PlanCuentas::NATURALEZAS,
            'naturalezaDe' => PlanCuentas::NATURALEZA_DE,
        ]);
    }

    private function filtradas(Request $request): Builder
    {
        $buscar = trim((string) $request->query('buscar'));

        return $this->deMiEmpresa($request)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q->where('codigo', 'like', "{$buscar}%")->orWhere('nombre', 'like', "%{$buscar}%")))
            ->when(in_array($request->query('tipo'), PlanCuentas::TIPOS, true), fn ($q) => $q->where('tipo', $request->query('tipo')))
            ->when(in_array($request->query('estado'), ['activas', 'inactivas'], true), fn ($q) => $q->where('activo', $request->query('estado') === 'activas'));
    }

    private function deMiEmpresa(Request $request): Builder
    {
        return CuentaContable::query()->where('id_empresa', $request->user()->id_empresa);
    }

    /** Recorrido del árbol en profundidad: cada padre seguido de sus subcuentas. */
    private function ordenArbol($cuentas)
    {
        $hijas = $cuentas->groupBy(fn ($c) => $c->id_padre && $cuentas->contains('id_cuenta', $c->id_padre) ? $c->id_padre : 0);
        $orden = collect();
        $agregar = function ($idPadre) use (&$agregar, $hijas, $orden) {
            foreach ($hijas->get($idPadre, []) as $c) {
                $orden->push($c);
                $agregar($c->id_cuenta);
            }
        };
        $agregar(0);

        return $orden;
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?CuentaContable $cuenta = null): array
    {
        $idEmpresa = $request->user()->id_empresa;
        $request->merge(['codigo' => trim((string) $request->input('codigo'))]);

        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z][0-9A-Za-z.\-]*$/',
                Rule::unique('cuenta_contable', 'codigo')->where('id_empresa', $idEmpresa)->ignore($cuenta?->id_cuenta, 'id_cuenta')],
            'nombre' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(PlanCuentas::TIPOS)],
            'naturaleza' => ['required', Rule::in(PlanCuentas::NATURALEZAS)],
            'id_padre' => ['nullable', 'integer', Rule::exists('cuenta_contable', 'id_cuenta')->where('id_empresa', $idEmpresa)],
        ], [
            'codigo.unique' => 'Ya existe una cuenta con ese código.',
            'codigo.regex' => 'El código solo puede tener letras, números, punto y guion.',
            'id_padre.exists' => 'La cuenta padre no es válida.',
        ]);
        $datos['id_padre'] ??= null;
        $datos['permite_movimiento'] = $request->boolean('permite_movimiento');
        $datos['activo'] = $request->boolean('activo');

        // Plan tal como quedaría con este cambio.
        $plan = PlanCuentas::actual($idEmpresa);
        $codigoAnterior = $cuenta?->codigo;
        if ($codigoAnterior !== null && $codigoAnterior !== $datos['codigo']) {
            unset($plan[$codigoAnterior]);
            foreach ($plan as &$c) {
                $c['padre'] = $c['padre'] === $codigoAnterior ? $datos['codigo'] : $c['padre'];
            }
            unset($c);
        }
        $padre = $datos['id_padre'] ? CuentaContable::find($datos['id_padre'])->codigo : null;
        $plan[$datos['codigo']] = ['padre' => $padre, 'tipo' => $datos['tipo'], 'permite_movimiento' => $datos['permite_movimiento'], 'nombre' => $datos['nombre']];

        $hijas = array_keys(array_filter($plan, fn ($c) => $c['padre'] === $datos['codigo']));
        if ($hijas && $datos['permite_movimiento']) {
            throw ValidationException::withMessages(['permite_movimiento' => 'Una cuenta con subcuentas es de agrupación: no puede permitir movimientos.']);
        }
        if ($hijas && collect($hijas)->contains(fn ($h) => $plan[$h]['tipo'] !== $datos['tipo'])) {
            throw ValidationException::withMessages(['tipo' => 'Sus subcuentas son de otro tipo; el tipo de una cuenta y sus subcuentas debe coincidir.']);
        }
        if ($errores = PlanCuentas::errores($plan, [$datos['codigo']])) {
            throw ValidationException::withMessages(['id_padre' => $errores[$datos['codigo']]]);
        }

        return $datos;
    }

    /** @return list<array<int, mixed>> filas crudas, la primera es el encabezado */
    private function leerArchivo($archivo): array
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        if ($extension === 'xlsx') {
            // FastExcel elige el lector por la extensión: se copia con su nombre real.
            $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.Str::random(20).'.xlsx';
            copy($archivo->getRealPath(), $ruta);
            try {
                return (new FastExcel)->withoutHeaders()->import($ruta)->map(fn ($f) => array_values((array) $f))->all();
            } catch (\Throwable) {
                throw ValidationException::withMessages(['archivo' => 'No se pudo leer el archivo de Excel.']);
            } finally {
                @unlink($ruta);
            }
        }

        $contenido = (string) file_get_contents($archivo->getRealPath());
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }
        $primera = strtok($contenido, "\n");
        $separador = substr_count($primera, ';') >= substr_count($primera, ',') ? ';' : ',';
        $filas = [];
        foreach (preg_split('/\r\n|\n|\r/', $contenido) as $linea) {
            if (trim($linea) !== '') {
                $filas[] = str_getcsv($linea, $separador, '"', '');
            }
        }

        return $filas;
    }

    /**
     * Valida las filas contra el plan actual. Devuelve filas normalizadas, errores por línea y conteos.
     *
     * @param  list<array<int, mixed>>|list<array<string, mixed>>  $filas  crudas (con encabezado) o ya normalizadas
     */
    private function analizar(array $filas, int $idEmpresa): array
    {
        $normalizadas = isset($filas[0]['codigo']) ? $filas : $this->normalizar($filas);
        $errores = [];
        $plan = PlanCuentas::actual($idEmpresa);
        $existentes = array_keys($plan);
        $vistos = [];

        foreach ($normalizadas as $f) {
            $e = [];
            if ($f['codigo'] === '' || ! preg_match('/^[0-9A-Za-z][0-9A-Za-z.\-]*$/', $f['codigo']) || mb_strlen($f['codigo']) > 20) {
                $e[] = 'Código vacío o no válido (letras, números, punto y guion; máximo 20).';
            }
            if (isset($vistos[$f['codigo']])) {
                $e[] = "Código repetido (también en la línea {$vistos[$f['codigo']]}).";
            }
            if ($f['nombre'] === '' || mb_strlen($f['nombre']) > 200) {
                $e[] = 'Nombre vacío o de más de 200 caracteres.';
            }
            if (! in_array($f['tipo'], PlanCuentas::TIPOS, true)) {
                $e[] = 'Tipo no válido (ACTIVO, PASIVO, PATRIMONIO, INGRESO, GASTO o COSTO).';
            }
            if (! in_array($f['naturaleza'], PlanCuentas::NATURALEZAS, true)) {
                $e[] = 'Naturaleza no válida (DEUDORA o ACREEDORA).';
            }
            $vistos[$f['codigo']] ??= $f['linea'];
            if ($e) {
                $errores[$f['linea']] = ['codigo' => $f['codigo'], 'errores' => $e];
            } else {
                $plan[$f['codigo']] = ['padre' => $f['padre'], 'tipo' => $f['tipo'], 'permite_movimiento' => $f['permite_movimiento'], 'nombre' => $f['nombre']];
            }
        }

        // Si «permite movimiento» venía vacío: sí, salvo que la cuenta quede con subcuentas.
        $conHijas = array_flip(array_filter(array_column($plan, 'padre')));
        foreach ($normalizadas as &$f) {
            if ($f['permite_movimiento'] === null) {
                $f['permite_movimiento'] = ! isset($conHijas[$f['codigo']]);
                if (isset($plan[$f['codigo']])) {
                    $plan[$f['codigo']]['permite_movimiento'] = $f['permite_movimiento'];
                }
            }
        }
        unset($f);

        $lineaDe = $vistos; // código => primera línea en que aparece
        $revisar = array_values(array_filter(array_keys($plan), fn ($c) => isset($lineaDe[$c]) && ! isset($errores[$lineaDe[$c]])));
        foreach (PlanCuentas::errores($plan, $revisar) as $codigo => $e) {
            $errores[$lineaDe[$codigo]] = ['codigo' => $codigo, 'errores' => $e];
        }
        // Cuentas del sistema que el archivo dejaría con una padre de movimiento o de otro tipo.
        $afectadas = array_values(array_filter(array_keys($plan), fn ($c) => ! isset($lineaDe[$c]) && isset($lineaDe[$plan[$c]['padre'] ?? ''])));
        foreach (PlanCuentas::errores($plan, $afectadas) as $codigo => $e) {
            $linea = $lineaDe[$plan[$codigo]['padre']];
            $errores[$linea] ??= ['codigo' => $plan[$codigo]['padre'], 'errores' => []];
            $errores[$linea]['errores'][] = "Su subcuenta existente «{$codigo}»: ".implode(' ', $e);
        }
        ksort($errores);

        return [
            'filas' => $normalizadas,
            'errores' => $errores,
            'total' => count($normalizadas),
            'nuevas' => count(array_filter($normalizadas, fn ($f) => ! in_array($f['codigo'], $existentes, true))),
            'actualizar' => count(array_filter($normalizadas, fn ($f) => in_array($f['codigo'], $existentes, true))),
        ];
    }

    /** Convierte las filas crudas (encabezado + datos) en filas con nombre de campo. */
    private function normalizar(array $crudas): array
    {
        $clave = fn ($t) => Str::of((string) $t)->ascii()->lower()->replaceMatches('/[^a-z]+/', '_')->trim('_')->toString();
        $columnas = array_map($clave, (array) array_shift($crudas));
        $indice = fn (array $nombres) => collect($nombres)->map(fn ($n) => array_search($n, $columnas, true))->first(fn ($i) => $i !== false);
        $pos = [
            'codigo' => $indice(['codigo']),
            'nombre' => $indice(['nombre']),
            'tipo' => $indice(['tipo']),
            'naturaleza' => $indice(['naturaleza']),
            'padre' => $indice(['codigo_padre', 'padre']),
            'mov' => $indice(['permite_movimiento', 'movimiento']),
        ];
        if ($pos['codigo'] === null || $pos['nombre'] === null || $pos['tipo'] === null) {
            throw ValidationException::withMessages(['archivo' => 'El archivo debe tener al menos las columnas Código, Nombre y Tipo (usa la plantilla).']);
        }
        if (count($crudas) > self::MAX_FILAS) {
            throw ValidationException::withMessages(['archivo' => 'El archivo tiene más de '.self::MAX_FILAS.' filas.']);
        }

        $filas = [];
        foreach ($crudas as $i => $f) {
            $valor = fn (?int $p) => $p === null ? '' : trim((string) ($f[$p] ?? ''));
            if (implode('', array_map('trim', array_map('strval', $f))) === '') {
                continue; // fila vacía
            }
            $tipo = strtoupper($valor($pos['tipo']));
            $naturaleza = strtoupper($valor($pos['naturaleza']));
            $mov = Str::of($valor($pos['mov']))->ascii()->lower()->toString();
            $filas[] = [
                'linea' => $i + 2,
                'codigo' => $valor($pos['codigo']),
                'nombre' => $valor($pos['nombre']),
                'tipo' => $tipo,
                'naturaleza' => $naturaleza !== '' ? $naturaleza : (PlanCuentas::NATURALEZA_DE[$tipo] ?? ''),
                'padre' => $valor($pos['padre']) !== '' ? $valor($pos['padre']) : null,
                'permite_movimiento' => match (true) {
                    in_array($mov, ['si', 's', '1', 'x', 'true', 'verdadero'], true) => true,
                    in_array($mov, ['no', 'n', '0', 'false', 'falso'], true) => false,
                    default => null,
                },
            ];
        }
        if (! $filas) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no tiene cuentas.']);
        }

        return $filas;
    }
}

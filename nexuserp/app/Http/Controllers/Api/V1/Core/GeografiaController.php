<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Pais;
use App\Models\Core\DivisionGeografica;
use App\Models\Core\Municipio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GeografiaController extends Controller
{
    // ════════════════════════════════════════════════════════════
    // PAÍSES
    // ════════════════════════════════════════════════════════════
    public function paises(): JsonResponse
    {
        $paises = Pais::orderBy('nombre')->get()->map(fn($p) => [
            'id_pais'    => $p->id_pais,
            'codigo_iso' => $p->codigo_iso ?? '—',
            'nombre'     => $p->nombre,
            'activo'     => $p->activo ? 'Activo' : 'Inactivo',
        ]);

        return response()->json(['success' => true, 'data' => $paises]);
    }

    // Para selects (solo activos)
    public function paisesActivos(): JsonResponse
    {
        $paises = Pais::where('activo', true)
            ->orderBy('nombre')
            ->get(['id_pais as id', 'nombre as name']);

        return response()->json(['success' => true, 'data' => $paises]);
    }

    public function storePais(Request $request): JsonResponse
    {
        $request->validate([
            'codigo_iso' => ['nullable', 'string', 'size:2', Rule::unique('pais', 'codigo_iso')],
            'nombre'     => ['required', 'string', 'max:100', Rule::unique('pais', 'nombre')],
            'activo'     => 'boolean',
        ]);

        $pais = Pais::create([
            'codigo_iso' => strtoupper($request->codigo_iso),
            'nombre'     => $request->nombre,
            'activo'     => $request->activo ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'País creado correctamente.',
            'data'    => ['id_pais' => $pais->id_pais],
        ], 201);
    }

    public function updatePais(Request $request, int $id): JsonResponse
    {
        $pais = Pais::findOrFail($id);

        $request->validate([
            'codigo_iso' => ['nullable', 'string', 'size:2', Rule::unique('pais', 'codigo_iso')->ignore($id, 'id_pais')],
            'nombre'     => ['required', 'string', 'max:100', Rule::unique('pais', 'nombre')->ignore($id, 'id_pais')],
            'activo'     => 'boolean',
        ]);

        $pais->update([
            'codigo_iso' => $request->codigo_iso ? strtoupper($request->codigo_iso) : null,
            'nombre'     => $request->nombre,
            'activo'     => $request->activo ?? $pais->activo,
        ]);

        return response()->json(['success' => true, 'message' => 'País actualizado correctamente.']);
    }

    public function togglePais(int $id): JsonResponse
    {
        $pais = Pais::findOrFail($id);
        $pais->update(['activo' => !$pais->activo]);

        return response()->json([
            'success' => true,
            'message' => $pais->activo ? 'País activado.' : 'País desactivado.',
        ]);
    }

    public function destroyPais(int $id): JsonResponse
    {
        $pais = Pais::findOrFail($id);

        if ($pais->divisiones()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un país con departamentos asociados.',
            ], 422);
        }

        $pais->delete();
        return response()->json(['success' => true, 'message' => 'País eliminado.']);
    }

    // ════════════════════════════════════════════════════════════
    // DIVISIONES (Departamentos)
    // ════════════════════════════════════════════════════════════
    public function divisiones(): JsonResponse
    {
        $divisiones = DivisionGeografica::with('pais')
            ->orderBy('nombre')
            ->get()
            ->map(fn($d) => [
                'id_division' => $d->id_division,
                'id_pais'     => $d->id_pais,
                'pais'        => $d->pais?->nombre ?? '—',
                'nombre'      => $d->nombre,
                'tipo'        => $d->tipo ?? '—',
                'activo'      => $d->activo ? 'Activo' : 'Inactivo',
            ]);

        return response()->json(['success' => true, 'data' => $divisiones]);
    }

    // Para selects en cascada
    public function divisionesPorPais(int $idPais): JsonResponse
    {
        $divisiones = DivisionGeografica::where('id_pais', $idPais)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_division as id', 'nombre as name']);

        return response()->json(['success' => true, 'data' => $divisiones]);
    }

    public function storeDivision(Request $request): JsonResponse
    {
        $request->validate([
            'id_pais' => 'required|exists:pais,id_pais',
            'nombre'  => 'required|string|max:100',
            'tipo'    => 'nullable|string|max:50',
            'activo'  => 'boolean',
        ]);

        $division = DivisionGeografica::create([
            'id_pais' => $request->id_pais,
            'nombre'  => $request->nombre,
            'tipo'    => $request->tipo,
            'activo'  => $request->activo ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Departamento creado correctamente.',
            'data'    => ['id_division' => $division->id_division],
        ], 201);
    }

    public function updateDivision(Request $request, int $id): JsonResponse
    {
        $division = DivisionGeografica::findOrFail($id);

        $request->validate([
            'id_pais' => 'required|exists:pais,id_pais',
            'nombre'  => 'required|string|max:100',
            'tipo'    => 'nullable|string|max:50',
            'activo'  => 'boolean',
        ]);

        $division->update([
            'id_pais' => $request->id_pais,
            'nombre'  => $request->nombre,
            'tipo'    => $request->tipo,
            'activo'  => $request->activo ?? $division->activo,
        ]);

        return response()->json(['success' => true, 'message' => 'Departamento actualizado.']);
    }

    public function toggleDivision(int $id): JsonResponse
    {
        $division = DivisionGeografica::findOrFail($id);
        $division->update(['activo' => !$division->activo]);

        return response()->json([
            'success' => true,
            'message' => $division->activo ? 'Departamento activado.' : 'Departamento desactivado.',
        ]);
    }

    public function destroyDivision(int $id): JsonResponse
    {
        $division = DivisionGeografica::findOrFail($id);

        if ($division->municipios()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un departamento con municipios asociados.',
            ], 422);
        }

        $division->delete();
        return response()->json(['success' => true, 'message' => 'Departamento eliminado.']);
    }

    // ════════════════════════════════════════════════════════════
    // MUNICIPIOS
    // ════════════════════════════════════════════════════════════
    public function municipios(): JsonResponse
    {
        $municipios = Municipio::with(['division.pais'])
            ->orderBy('nombre')
            ->get()
            ->map(fn($m) => [
                'id_municipio' => $m->id_municipio,
                'id_division'  => $m->id_division,
                'division'     => $m->division?->nombre ?? '—',
                'pais'         => $m->division?->pais?->nombre ?? '—',
                'id_pais'      => $m->division?->id_pais,
                'nombre'       => $m->nombre,
                'activo'       => $m->activo ? 'Activo' : 'Inactivo',
            ]);

        return response()->json(['success' => true, 'data' => $municipios]);
    }

    // Para selects en cascada
    public function municipiosPorDivision(int $idDivision): JsonResponse
    {
        $municipios = Municipio::where('id_division', $idDivision)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_municipio as id', 'nombre as name']);

        return response()->json(['success' => true, 'data' => $municipios]);
    }

    public function storeMunicipio(Request $request): JsonResponse
    {
        $request->validate([
            'id_division' => 'required|exists:division_geografica,id_division',
            'nombre'      => 'required|string|max:100',
            'activo'      => 'boolean',
        ]);

        $municipio = Municipio::create([
            'id_division' => $request->id_division,
            'nombre'      => $request->nombre,
            'activo'      => $request->activo ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Municipio creado correctamente.',
            'data'    => ['id_municipio' => $municipio->id_municipio],
        ], 201);
    }

    public function updateMunicipio(Request $request, int $id): JsonResponse
    {
        $municipio = Municipio::findOrFail($id);

        $request->validate([
            'id_division' => 'required|exists:division_geografica,id_division',
            'nombre'      => 'required|string|max:100',
            'activo'      => 'boolean',
        ]);

        $municipio->update([
            'id_division' => $request->id_division,
            'nombre'      => $request->nombre,
            'activo'      => $request->activo ?? $municipio->activo,
        ]);

        return response()->json(['success' => true, 'message' => 'Municipio actualizado.']);
    }

    public function toggleMunicipio(int $id): JsonResponse
    {
        $municipio = Municipio::findOrFail($id);
        $municipio->update(['activo' => !$municipio->activo]);

        return response()->json([
            'success' => true,
            'message' => $municipio->activo ? 'Municipio activado.' : 'Municipio desactivado.',
        ]);
    }

    public function destroyMunicipio(int $id): JsonResponse
    {
        $municipio = Municipio::findOrFail($id);
        $municipio->delete();

        return response()->json(['success' => true, 'message' => 'Municipio eliminado.']);
    }
}
<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\DivisionGeografica;
use App\Models\Core\Municipio;
use Illuminate\Http\JsonResponse;

/**
 * Catálogos de geografía de solo lectura para los selects en cascada de las
 * pantallas que aún son Vue (Clientes). La administración está en
 * App\Http\Controllers\GeografiaController (/sistema/geografia).
 */
class GeografiaController extends Controller
{
    public function divisionesPorPais(int $idPais): JsonResponse
    {
        $divisiones = DivisionGeografica::where('id_pais', $idPais)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_division as id', 'nombre as name']);

        return response()->json(['success' => true, 'data' => $divisiones]);
    }

    public function municipiosPorDivision(int $idDivision): JsonResponse
    {
        $municipios = Municipio::where('id_division', $idDivision)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_municipio as id', 'nombre as name']);

        return response()->json(['success' => true, 'data' => $municipios]);
    }

    public function datosParaCascada(int $idMunicipio): JsonResponse
    {
        $municipio = Municipio::with('division')->findOrFail($idMunicipio);

        return response()->json([
            'success' => true,
            'data'    => [
                'id_municipio' => $municipio->id_municipio,
                'id_division'  => $municipio->id_division,
                'id_pais'      => $municipio->division?->id_pais,
            ],
        ]);
    }
}

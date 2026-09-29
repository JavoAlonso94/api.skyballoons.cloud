<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VueloController extends Controller
{
    /**
     * Catálogo de vuelos activos para socios comerciales.
     * GET /api/vuelos
     */
    public function index(Request $request)
    {
        try {
            $vuelos = DB::table('vuelos_globo_para_socios_comerciales as v')
                ->join('empresas as e', 'v.empresa_id', '=', 'e.id')
                ->select([
                    'v.id',
                    'v.categoria_vuelo_id',
                    'v.empresa_id',
                    'e.nombre as empresa',
                    'v.nombre',
                    'v.descripcion',
                    'v.duracion_minutos',
                    'v.capacidad_maxima',
                    'v.precio_base',
                    'v.estado',
                ])
                ->where('v.estado', 1)
                ->orderBy('v.nombre')
                ->get();

            return response()->json([
                'status' => 'success',
                'total'  => $vuelos->count(),
                'data'   => $vuelos,
            ], 200);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Ocurrió un error al obtener el catálogo de vuelos');
        }
    }
}


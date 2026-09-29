<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VueloController extends Controller
{
    /**
     * Catálogo de vuelos activos para socios comerciales con precios personalizados por socio.
     * GET /api/vuelos
     */
    public function index(Request $request)
    {
        try {
            // Obtenemos de forma segura el ID del socio autenticado mediante el token
            $socioId = $this->socioAutenticadoId($request);

            // Consulta utilizando JOINs seguros y respetando el estándar del ERP (Evita Schema Leak)
            $vuelos = DB::table('vuelos_globo_para_socios_comerciales as v')
                ->join('empresas as e', 'v.empresa_id', '=', 'e.id')
                ->leftJoin('precios_socios_comerciales_comisiones_vuelos as psc', function($join) use ($socioId) {
                    $join->on('psc.vuelo_id', '=', 'v.id')
                         ->where('psc.socio_id', '=', $socioId);
                })
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
                    // Si el socio tiene precio personalizado, lo toma; si no, usa el precio base del vuelo
                    DB::raw('COALESCE(psc.precio_adulto, v.precio_base) as precio'),
                    'psc.precio_adulto',
                    'psc.precio_menor',
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
            return response()->json([
                'status'  => 'error',
                'message' => 'Ocurrió un error al obtener el catálogo de vuelos',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}

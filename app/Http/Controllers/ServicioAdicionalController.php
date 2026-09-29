<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicioAdicionalController extends Controller
{
    /**
     * Servicios adicionales generales activos.
     * GET /api/servicios-adicionales
     */
    public function index(Request $request)
    {
        try {
            $servicios = DB::table('adicionales_para_socios_comerciales')
                ->select([
                    'id',
                    'nombre',
                    'descripcion',
                    'imagen',
                    'precio',
                    'estado',
                ])
                ->where('estado', 'activo')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $servicios,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los servicios adicionales',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Servicios adicionales específicos para un vuelo de socio (según la consulta de Javier Alonso).
     * GET /api/vuelos/{vueloId}/adicionales
     */
    public function getByVuelo($vueloId)
    {
        try {
            $adicionalesVuelo = DB::table('vuelo_adicionales_para_socios_comerciales as va')
                ->join('vuelos_globo_para_socios_comerciales as v', 'va.vuelo_id', '=', 'v.id')
                ->join('adicionales_para_socios_comerciales as a', 'va.adicional_id', '=', 'a.id')
                ->select([
                    'v.id as vuelo_id',
                    'v.nombre as vuelo_nombre',
                    'a.id as adicional_id',
                    'a.nombre as adicional_nombre',
                    'a.descripcion',
                    'a.imagen',
                    'va.precio as precio_en_vuelo',
                    'va.cantidad',
                    'va.total',
                ])
                ->where('va.vuelo_id', $vueloId)
                ->where('va.estado', 'activo')
                ->where('a.estado', 'activo')
                ->orderBy('va.orden', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $adicionalesVuelo,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los adicionales del vuelo',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}


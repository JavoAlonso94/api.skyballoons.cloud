<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaVueloController extends Controller
{
    /**
     * Lista las categorías oficiales de vuelos activos para los socios.
     * GET /api/categorias-vuelos
     */
    public function index(Request $request)
    {
        try {
            // Consulta directa a la tabla oficial del ERP usando JOIN seguro con empresas
            $categorias = DB::table('categoria_vuelos_globo_socios_comerciales as c')
                ->join('empresas as e', 'c.empresa_id', '=', 'e.id')
                ->select([
                    'c.id',
                    'c.empresa_id',
                    'e.nombre as empresa',
                    'c.nombre',
                    'c.descripcion',
                    'c.estado',
                ])
                ->where('c.estado', 1) // O 'activo' dependiendo si tu columna es booleana/entera o string
                ->orderBy('c.nombre')
                ->get();

            return response()->json([
                'status' => 'success',
                'total'  => $categorias->count(),
                'data'   => $categorias,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ocurrió un error al obtener las categorías de vuelos',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Vuelo;
use Illuminate\Http\Request;

class VueloController extends Controller
{
    /**
     * Muestra el catálogo de vuelos disponibles para socios comerciales.
     */
    public function index(Request $request)
    {
        try {
            // Consultamos solo los vuelos activos
            $vuelos = Vuelo::where('estado', 1)->get();

            return response()->json([
                'status' => 'success',
                'total' => $vuelos->count(),
                'data' => $vuelos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al obtener el catálogo de vuelos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

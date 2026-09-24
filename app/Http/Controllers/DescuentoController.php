<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use Illuminate\Http\Request;

class DescuentoController extends Controller
{
    /**
     * Muestra las promociones y descuentos activos para el modal.
     */
    public function index(Request $request)
    {
        try {
            $hoy = now()->toDateString();

            // Consultamos los descuentos que estén activos y cuya vigencia cubra la fecha actual
            $descuentos = Descuento::where('estado', 1)
                ->where('vigencia_inicio', '<=', $hoy)
                ->where('vigencia_fin', '>=', $hoy)
                ->get();

            return response()->json([
                'status' => 'success',
                'total' => $descuentos->count(),
                'data' => $descuentos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al obtener las promociones',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

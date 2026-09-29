<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use Illuminate\Http\Request;

class DescuentoController extends Controller
{
    /**
     * Promociones y descuentos vigentes.
     * GET /api/promociones
     */
    public function index(Request $request)
    {
        try {
            $hoy = date('Y-m-d');

            // LEFT JOIN: un descuento puede no tener empresa asignada (aplica global)
            $descuentos = DB::table('descuentos as d')
                ->leftJoin('empresas as e', 'd.empresa_id', '=', 'e.id')
                ->select([
                    'd.id',
                    'd.empresa_id',
                    'e.nombre as empresa',
                    'd.nombre',
                    'd.tipo',
                    'd.valor',
                    'd.aplica_a',
                    'd.vigencia_inicio',
                    'd.vigencia_fin',
                ])
                ->where('d.estado', 1)
                ->where('d.vigencia_inicio', '<=', $hoy)
                ->where('d.vigencia_fin', '>=', $hoy)
                ->orderBy('d.vigencia_fin')
                ->get();

            return response()->json([
                'status' => 'success',
                'total'  => $descuentos->count(),
                'data'   => $descuentos,
            ], 200);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Ocurrió un error al obtener las promociones');
        }
    }
}

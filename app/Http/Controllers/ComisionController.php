<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComisionController extends Controller
{
    /**
     * Lista las comisiones del socio autenticado con su inner join correspondiente.
     * GET /api/comisiones
     */
    public function index(Request $request)
    {
        try {
            $socioId = $this->socioAutenticadoId($request);

            $query = DB::table('socios_comerciales_comisiones as c')
                ->join('socios_comerciales as s', 'c.socio_comercial_id', '=', 's.id')
                ->select([
                    'c.id',
                    'c.pedido_venta_id',
                    'c.socio_comercial_id',
                    's.nombre as socio_comercial_nombre',
                    'c.configuracion_id',
                    'c.tipo',
                    'c.valor_configurado',
                    'c.base_calculo',
                    'c.monto_comision',
                    'c.estado',
                    'c.fecha_generacion',
                    'c.fecha_pago',
                    'c.usuario_pago',
                    'c.observaciones',
                    'c.created_at',
                    'c.updated_at'
                ])
                ->where('c.socio_comercial_id', $socioId);

            // Filtro opcional por estado (ej: pendiente, pagado, etc.)
            if ($request->filled('estado')) {
                $query->where('c.estado', $request->input('estado'));
            }

            $total = (clone $query)->count();
            $page = max(1, (int) $request->input('page', 1));
            $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

            $comisiones = $query
                ->orderBy('c.created_at', 'desc')
                ->forPage($page, $perPage)
                ->get();

            return response()->json([
                'status' => 'success',
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'data' => $comisiones,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al obtener las comisiones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

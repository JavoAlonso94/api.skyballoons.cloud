<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComisionController extends Controller
{
    public function index(Request $request)
    {
        // 1. Obtener/procesar las fechas de filtro usando Carbon (Predeterminado: mes actual)
        $fechaInicio = $request->input('fecha_inicio') 
            ? Carbon::parse($request->input('fecha_inicio'))->startOfDay() 
            : Carbon::now()->startOfMonth();

        $fechaFin = $request->input('fecha_fin') 
            ? Carbon::parse($request->input('fecha_fin'))->endOfDay() 
            : Carbon::now()->endOfMonth();

        $socioComercialId = $request->input('socio_comercial_id');

        // 2. Parámetros base para las consultas
        $params = [
            'fecha_inicio' => $fechaInicio->toDateTimeString(),
            'fecha_fin'    => $fechaFin->toDateTimeString(),
        ];

        // 3. Construir cláusula condicional si se filtra por un socio específico
        $filtroSocio = '';
        if ($socioComercialId) {
            $filtroSocio = ' AND c.socio_comercial_id = :socio_comercial_id ';
            $params['socio_comercial_id'] = $socioComercialId;
        }

        // 4. Consulta del listado de pedidos/comisiones
        $pedidosQuery = "
            SELECT 
                c.id AS comision_id,
                c.monto_comision,
                c.tipo AS tipo_comision,
                c.valor_configurado,
                c.base_calculo,
                c.estado AS estado_comision,
                c.fecha_generacion,
                p.id AS pedido_id,
                p.numero_pedido,
                p.total AS total_pedido,
                p.fecha_pedido,
                s.id AS socio_id,
                s.nombre AS socio_nombre
            FROM socios_comerciales_comisiones c
            INNER JOIN pedidos_venta p ON c.pedido_venta_id = p.id
            INNER JOIN socios_comerciales s ON c.socio_comercial_id = s.id
            WHERE c.fecha_generacion BETWEEN :fecha_inicio AND :fecha_fin
            {$filtroSocio}
            ORDER BY c.fecha_generacion DESC
        ";

        $pedidos = DB::select($pedidosQuery, $params);

        // 5. Consulta para obtener la sumatoria total de las comisiones en el rango
        $sumatoriaQuery = "
            SELECT 
                COALESCE(SUM(c.monto_comision), 0) AS total_comisiones,
                COUNT(c.id) AS total_registros
            FROM socios_comerciales_comisiones c
            WHERE c.fecha_generacion BETWEEN :fecha_inicio AND :fecha_fin
            {$filtroSocio}
        ";

        $sumatoria = DB::select($sumatoriaQuery, $params);

        // 6. Respuesta JSON estructurada
        return response()->json([
            'status' => 'success',
            'filtros' => [
                'fecha_inicio' => $fechaInicio->toDateTimeString(),
                'fecha_fin' => $fechaFin->toDateTimeString(),
                'socio_comercial_id' => $socioComercialId ?? null,
            ],
            'resumen' => [
                'sumatoria_comisiones' => (float) $sumatoria[0]->total_comisiones,
                'total_pedidos' => (int) $sumatoria[0]->total_registros,
            ],
            'data' => $pedidos
        ], 200);
    }
}
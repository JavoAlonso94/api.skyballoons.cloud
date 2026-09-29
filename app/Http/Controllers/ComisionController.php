<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComisionController extends Controller
{
    /**
     * Todas las comisiones del socio autenticado.
     * GET /api/comisiones
     */
    public function index(Request $request)
    {
        try {
            $socioId  = $this->socioAutenticadoId($request);
            $query    = $this->consulta($request, $socioId);

            $comisiones = $query->orderBy('com.fecha_generacion', 'desc')->get();

            return response()->json([
                'status'            => 'success',
                'filtros'           => $this->filtros($request, $socioId),
                'resumen'           => [
                    'sumatoria_comisiones' => (float) $comisiones->sum('monto_comision'),
                    'total_registros'      => $comisiones->count(),
                ],
                'data'              => $comisiones->map(fn ($row) => $this->transformar($row))->values(),
                'socio_autenticado' => [
                    'id'    => $socioId,
                    'email' => $request->input('socio_autenticado_email'),
                ],
            ], 200);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Ocurrió un error al obtener las comisiones');
        }
    }

    /**
     * Comisiones paginadas.
     * GET /api/comisiones/paginado?page=1&per_page=20
     *
     * (La ruta existía pero el método no, y devolvía error 500.)
     */
    public function indexPaginado(Request $request)
    {
        try {
            $socioId = $this->socioAutenticadoId($request);
            $page    = max(1, (int) $request->input('page', 1));
            $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

            $query = $this->consulta($request, $socioId);

            $totalRegistros = (clone $query)->count();
            $sumatoria      = (clone $query)->sum('com.monto_comision');

            $comisiones = $query
                ->orderBy('com.fecha_generacion', 'desc')
                ->forPage($page, $perPage)
                ->get();

            return response()->json([
                'status'     => 'success',
                'filtros'    => $this->filtros($request, $socioId),
                'resumen'    => [
                    'sumatoria_comisiones' => (float) $sumatoria,
                    'total_registros'      => $totalRegistros,
                ],
                'paginacion' => [
                    'page'      => $page,
                    'per_page'  => $perPage,
                    'last_page' => (int) ceil($totalRegistros / $perPage),
                ],
                'data'       => $comisiones->map(fn ($row) => $this->transformar($row))->values(),
            ], 200);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Ocurrió un error al obtener las comisiones');
        }
    }

    /**
     * Query base con JOIN / LEFT JOIN. El socio SIEMPRE es el del token.
     */
    private function consulta(Request $request, int $socioId)
    {
        $query = DB::table('socios_comerciales_comisiones as com')
            ->join('socios_comerciales as s', 'com.socio_comercial_id', '=', 's.id')
            ->leftJoin('socios_comerciales_configuracion_comisiones as conf', 'com.configuracion_id', '=', 'conf.id')
            ->leftJoin('users as u', 'com.usuario_pago', '=', 'u.id')
            ->select([
                'com.id',
                'com.socio_comercial_id',
                's.nombre as socio_nombre',
                'com.pedido_venta_id as pedido_codigo',
                'com.tipo',
                'com.valor_configurado',
                'com.base_calculo',
                'com.monto_comision',
                'com.estado',
                'com.fecha_generacion',
                'com.fecha_pago',
                'u.name as usuario_pago_nombre',
                'com.observaciones',
                'com.configuracion_id',
                DB::raw("IFNULL(conf.tipo, '-') as config_tipo"),
                DB::raw('IFNULL(conf.valor, 0) as config_valor'),
            ])
            ->where('com.socio_comercial_id', $socioId);

        if ($request->filled('estado')) {
            $query->where('com.estado', $request->input('estado'));
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('com.fecha_generacion', '>=', $request->input('fecha_inicio'));
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('com.fecha_generacion', '<=', $request->input('fecha_fin'));
        }
        if ($request->filled('pedido_venta_id')) {
            $query->where('com.pedido_venta_id', $request->input('pedido_venta_id'));
        }

        return $query;
    }

    private function filtros(Request $request, int $socioId): array
    {
        return [
            'socio_id'        => $socioId,
            'estado'          => $request->input('estado'),
            'fecha_inicio'    => $request->input('fecha_inicio'),
            'fecha_fin'       => $request->input('fecha_fin'),
            'pedido_venta_id' => $request->input('pedido_venta_id'),
        ];
    }

    private function transformar($row): array
    {
        return [
            'id'                 => $row->id,
            'socio_comercial_id' => $row->socio_comercial_id,
            'socio_nombre'       => $row->socio_nombre,
            'pedido_codigo'      => $row->pedido_codigo,
            'tipo'               => $row->tipo,
            'valor_configurado'  => $row->tipo === 'porcentaje'
                ? $row->valor_configurado . '%'
                : number_format($row->valor_configurado, 2),
            'base_calculo'       => number_format($row->base_calculo, 2),
            'monto_comision'     => number_format($row->monto_comision, 2),
            'estado'             => $row->estado,
            'fecha_generacion'   => Carbon::parse($row->fecha_generacion)->format('d/m/Y H:i'),
            'fecha_pago'         => $row->fecha_pago
                ? Carbon::parse($row->fecha_pago)->format('d/m/Y H:i')
                : '-',
            'usuario_pago_nombre' => $row->usuario_pago_nombre ?? '-',
            'observaciones'      => $row->observaciones ?? '',
            'configuracion'      => $row->configuracion_id
                ? $row->config_tipo . ' (' . $row->config_valor . ')'
                : 'Manual / Vuelo',
        ];
    }
}

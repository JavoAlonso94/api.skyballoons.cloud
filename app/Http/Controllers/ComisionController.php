<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComisionController extends Controller
{
    public function index(Request $request)
    {
        // Verificar si es un socio autenticado
        $socioAutenticadoId = $request->socio_autenticado_id ?? null;
        
        // Si hay socio autenticado, forzar el filtro
        if ($socioAutenticadoId) {
            $request->merge(['socio_id' => $socioAutenticadoId]);
        }
        
        // Si no hay socio autenticado, permitir filtro manual (para admin)
        $socioId = $request->input('socio_id');

        // Construir la consulta base con los joins
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
                DB::raw('IFNULL(conf.tipo, "-") as config_tipo'),
                DB::raw('IFNULL(conf.valor, 0) as config_valor')
            ]);

        // Aplicar filtro de socio (obligatorio si es socio autenticado)
        if ($socioId) {
            $query->where('com.socio_comercial_id', $socioId);
        } elseif ($socioAutenticadoId) {
            $query->where('com.socio_comercial_id', $socioAutenticadoId);
        }

        // Aplicar filtros adicionales
        if ($request->filled('estado')) {
            $query->where('com.estado', $request->estado);
        }
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('com.fecha_generacion', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('com.fecha_generacion', '<=', $request->fecha_fin);
        }
        
        if ($request->filled('pedido_venta_id')) {
            $query->where('com.pedido_venta_id', $request->pedido_venta_id);
        }

        // Obtener resultados
        $comisiones = $query->orderBy('com.fecha_generacion', 'desc')->get();

        // Transformar los datos
        $data = $comisiones->map(function ($row) {
            return [
                'id' => $row->id,
                'socio_comercial_id' => $row->socio_comercial_id,
                'socio_nombre' => $row->socio_nombre,
                'pedido_codigo' => $row->pedido_codigo,
                'tipo' => $row->tipo,
                'valor_configurado' => $row->tipo === 'porcentaje' 
                    ? $row->valor_configurado . '%' 
                    : number_format($row->valor_configurado, 2),
                'base_calculo' => number_format($row->base_calculo, 2),
                'monto_comision' => number_format($row->monto_comision, 2),
                'estado' => $row->estado,
                'fecha_generacion' => Carbon::parse($row->fecha_generacion)->format('d/m/Y H:i'),
                'fecha_pago' => $row->fecha_pago 
                    ? Carbon::parse($row->fecha_pago)->format('d/m/Y H:i') 
                    : '-',
                'usuario_pago_nombre' => $row->usuario_pago_nombre ?? '-',
                'observaciones' => $row->observaciones ?? '',
                'configuracion' => $row->configuracion_id 
                    ? $row->config_tipo . ' (' . $row->config_valor . ')' 
                    : 'Manual / Vuelo',
                'estado_badge' => $this->getEstadoBadge($row->estado),
                'acciones' => $this->getAcciones($row),
            ];
        });

        // Calcular totales
        $totalComisiones = $comisiones->sum('monto_comision');
        $totalRegistros = $comisiones->count();

        // Respuesta JSON
        $response = [
            'status' => 'success',
            'filtros' => [
                'socio_id' => $socioId,
                'estado' => $request->estado ?? null,
                'fecha_inicio' => $request->fecha_inicio ?? null,
                'fecha_fin' => $request->fecha_fin ?? null,
                'pedido_venta_id' => $request->pedido_venta_id ?? null,
            ],
            'resumen' => [
                'sumatoria_comisiones' => (float) $totalComisiones,
                'total_registros' => $totalRegistros,
            ],
            'data' => $data
        ];

        // Si es socio autenticado, agregar información del socio
        if ($socioAutenticadoId) {
            $response['socio_autenticado'] = [
                'id' => $socioAutenticadoId,
                'email' => $request->socio_autenticado_email ?? null,
            ];
        }

        return response()->json($response, 200);
    }

    private function getEstadoBadge($estado)
    {
        $badge = 'bg-secondary';
        
        if ($estado === 'pendiente') {
            $badge = 'bg-warning text-dark';
        } elseif ($estado === 'pagada') {
            $badge = 'bg-success';
        } elseif ($estado === 'cancelada') {
            $badge = 'bg-danger';
        }
        
        return '<span class="badge ' . $badge . '">' . ucfirst($estado) . '</span>';
    }

    private function getAcciones($row)
    {
        $btn = '';
        
        if ($row->estado === 'pendiente') {
            $btn .= '<button class="btn btn-sm btn-success btn-pagar-comision" data-id="' . $row->id . '" title="Pagar comisión"><i class="fas fa-check-circle"></i></button> ';
            $btn .= '<button class="btn btn-sm btn-danger btn-cancelar-comision" data-id="' . $row->id . '" title="Cancelar comisión"><i class="fas fa-times-circle"></i></button> ';
        }
        
        $btn .= '<button class="btn btn-sm btn-info btn-editar-comision" data-id="' . $row->id . '" title="Editar observación"><i class="fas fa-edit"></i></button>';
        
        return $btn;
    }
}
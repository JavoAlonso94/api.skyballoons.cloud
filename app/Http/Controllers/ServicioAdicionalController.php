<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicioAdicionalController extends Controller
{
    /**
     * Servicios adicionales visibles para socios/cotizaciones.
     * GET /api/servicios-adicionales
     */
    public function index(Request $request)
    {
        try {
            $servicios = DB::table('servicios_adicionales as sa')
                ->select([
                    'sa.id_servicio_adicional',
                    'sa.descripcion',
                    'sa.observaciones',
                    'sa.precio',
                ])
                ->where('sa.id_estatus', 1)
                ->where('sa.visibilidad_cotizaciones', 1)
                ->orderBy('sa.descripcion')
                ->get();

            $formateados = $servicios->map(function ($servicio) {
                return [
                    'id'          => $servicio->id_servicio_adicional,
                    'nombre'      => $servicio->descripcion,
                    'descripcion' => $servicio->observaciones,
                    'imagen'      => null,
                    'precio'      => $servicio->precio,
                    'estado'      => 'Activo',
                ];
            });

            return response()->json($formateados, 200);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Error al obtener los servicios adicionales');
        }
    }
}

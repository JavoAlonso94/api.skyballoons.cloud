<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicioAdicionalController extends Controller
{
    public function index()
{
        // Filtramos por estatus activo y visibilidad para cotizaciones/socios
        $servicios = DB::table('servicios_adicionales')
            ->where('id_estatus', 1)
            ->where('visibilidad_cotizaciones', 1) // Este campo limita a los servicios del portal
            ->get();

        $formateados = $servicios->map(function ($servicio) {
            return [
                'id'          => $servicio->id_servicio_adicional,
                'nombre'      => $servicio->descripcion,
                'descripcion' => $servicio->observaciones,
                'imagen'      => null,
                'precio'      => $servicio->precio,
                'estado'      => 'Activo'
            ];
        });

        return response()->json($formateados, 200);
    }
}

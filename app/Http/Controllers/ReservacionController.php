<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservacionController extends Controller
{
    /**
     * Lista las reservaciones DEL SOCIO AUTENTICADO.
     * GET /api/reservaciones
     */
    public function index(Request $request)
    {
        try {
            $socioId = $this->socioAutenticadoId($request);

            // Consulta utilizando JOINs seguros tal como lo requiere el estándar del ERP
            $query = DB::table('reservaciones_socios_comerciales as r')
                ->join('socios_comerciales as s', 'r.socio_id', '=', 's.id')
                ->join('vuelos_globo_para_socios_comerciales as v', 'r.vuelo_id', '=', 'v.id')
                ->join('empresas as e', 'r.empresa_id', '=', 'e.id')
                ->select([
                    'r.id',
                    'r.socio_id',
                    's.nombre as socio',
                    'v.id as vuelo_id',
                    'v.nombre as vuelo',
                    'e.id as empresa_id',
                    'e.nombre as empresa',
                    'r.fecha_reserva',
                    'r.fecha_vuelo',
                    'r.hora_salida',
                    'r.numero_personas',
                    'r.json_tarifas',
                    'r.created_at',
                    // Cálculo seguro de pagos asociados desde la tabla relacional
                    DB::raw("COALESCE((SELECT SUM(p.monto)
                                       FROM pagos_socio_comercial_reservacion p
                                       WHERE p.reservacion_id = r.id
                                         AND p.estado = 'pagado'), 0) as total_pagado"),
                    DB::raw("COALESCE((SELECT COUNT(*)
                                       FROM pagos_socio_comercial_reservacion p2
                                       WHERE p2.reservacion_id = r.id), 0) as cantidad_pagos"),
                ])
                ->where('r.socio_id', $socioId);

            // Filtros opcionales por fecha de vuelo
            if ($request->filled('fecha_inicio')) {
                $query->whereDate('r.fecha_vuelo', '>=', $request->input('fecha_inicio'));
            }
            if ($request->filled('fecha_fin')) {
                $query->whereDate('r.fecha_vuelo', '<=', $request->input('fecha_fin'));
            }

            $total   = (clone $query)->count();
            $page    = max(1, (int) $request->input('page', 1));
            $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

            $reservaciones = $query
                ->orderBy('r.created_at', 'desc')
                ->forPage($page, $perPage)
                ->get();

            // Decodificamos el JSON de tarifas de manera limpia para la app
            $reservaciones->transform(function ($item) {
                if (isset($item->json_tarifas) && is_string($item->json_tarifas)) {
                    $item->json_tarifas = json_decode($item->json_tarifas);
                }
                return $item;
            });

            return response()->json([
                'status'   => 'success',
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'data'     => $reservaciones,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ocurrió un error al obtener las reservaciones',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crea una reservación desde la App y la guarda en la misma tabla del ERP.
     * POST /api/reservaciones
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'vuelo_id'        => 'required|integer',
                'fecha_vuelo'     => 'required|date|after_or_equal:today',
                'hora_salida'     => 'required|string|max:8',
                'numero_personas' => 'required|integer|min:1',
                'json_tarifas'    => 'nullable',
            ]);

            // El socio se obtiene de forma segura mediante el token autenticado
            $socioId = $this->socioAutenticadoId($request);

            // Validamos que el vuelo exista en el ERP y obtenemos su empresa asociada
            $vuelo = DB::table('vuelos_globo_para_socios_comerciales as v')
                ->join('empresas as e', 'v.empresa_id', '=', 'e.id')
                ->where('v.id', $request->input('vuelo_id'))
                ->select(['v.id', 'v.empresa_id', 'v.nombre', 'e.nombre as empresa'])
                ->first();

            if (!$vuelo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vuelo seleccionado no existe o no está disponible',
                ], 404);
            }

            $jsonTarifasInput = $request->input('json_tarifas');
            $jsonTarifasEncoded = is_array($jsonTarifasInput) ? json_encode($jsonTarifasInput) : $jsonTarifasInput;

            // Inserción directa en la tabla compartida con el ERP
            $reservacionId = DB::table('reservaciones_socios_comerciales')->insertGetId([
                'socio_id'        => $socioId,
                'vuelo_id'        => $vuelo->id,
                'empresa_id'      => $vuelo->empresa_id,
                'fecha_reserva'   => date('Y-m-d H:i:s'),
                'fecha_vuelo'     => $request->input('fecha_vuelo'),
                'hora_salida'     => $request->input('hora_salida'),
                'numero_personas' => $request->input('numero_personas'),
                'json_tarifas'    => $jsonTarifasEncoded,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            // Recuperamos el registro recién creado usando el estándar de JOINs del ERP
            $detalle = DB::table('reservaciones_socios_comerciales as r')
                ->join('socios_comerciales as s', 'r.socio_id', '=', 's.id')
                ->join('vuelos_globo_para_socios_comerciales as v', 'r.vuelo_id', '=', 'v.id')
                ->join('empresas as e', 'r.empresa_id', '=', 'e.id')
                ->where('r.id', $reservacionId)
                ->select([
                    'r.id',
                    'r.socio_id',
                    's.nombre as socio',
                    'v.id as vuelo_id',
                    'v.nombre as vuelo',
                    'e.id as empresa_id',
                    'e.nombre as empresa',
                    'r.fecha_reserva',
                    'r.fecha_vuelo',
                    'r.hora_salida',
                    'r.numero_personas',
                    'r.json_tarifas',
                    'r.created_at',
                ])
                ->first();

            if ($detalle && isset($detalle->json_tarifas) && is_string($detalle->json_tarifas)) {
                $detalle->json_tarifas = json_decode($detalle->json_tarifas);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Reservación creada exitosamente',
                'data'    => $detalle,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error de validación en los datos enviados',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ocurrió un error al procesar la reservación',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}

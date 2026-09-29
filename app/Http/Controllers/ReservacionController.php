<?php

namespace App\Http\Controllers;

use App\Models\Reservacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservacionController extends Controller
{
    /**
     * Lista las reservaciones DEL SOCIO AUTENTICADO.
     * GET /api/reservaciones?estado=&fecha_inicio=&fecha_fin=&page=&per_page=
     */
    public function index(Request $request)
    {
        try {
            $socioId = $this->socioAutenticadoId($request);

            $query = DB::table('reservaciones_socios_comerciales as r')
                ->join('socios_comerciales as s', 'r.socio_id', '=', 's.id')
                ->join('vuelos_globo_para_socios_comerciales as v', 'r.vuelo_id', '=', 'v.id')
                ->join('empresas as e', 'r.empresa_id', '=', 'e.id')
                ->select([
                    'r.id',
                    's.nombre as socio',
                    'v.nombre as vuelo',
                    'e.nombre as empresa',
                    'r.fecha_reserva',
                    'r.fecha_vuelo',
                    'r.hora_salida',
                    'r.numero_personas',
                    'r.subtotal',
                    'r.impuesto',
                    'r.descuento',
                    'r.total',
                    'r.moneda',
                    'r.estado',
                    'r.created_at',
                    DB::raw("COALESCE((SELECT SUM(p.monto)
                                       FROM pagos_socio_comercial_reservacion p
                                       WHERE p.reservacion_id = r.id
                                         AND p.estado = 'pagado'), 0) as total_pagado"),
                    DB::raw("COALESCE((SELECT COUNT(*)
                                       FROM pagos_socio_comercial_reservacion p2
                                       WHERE p2.reservacion_id = r.id), 0) as cantidad_pagos"),
                ])
                // Filtro obligatorio: solo las reservaciones del dueño del token
                ->where('r.socio_id', $socioId);

            if ($request->filled('estado')) {
                $query->where('r.estado', $request->input('estado'));
            }
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

            return response()->json([
                'status' => 'success',
                'total'  => $total,
                'page'   => $page,
                'per_page' => $perPage,
                'data'   => $reservaciones,
            ], 200);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Ocurrió un error al obtener las reservaciones');
        }
    }

    /**
     * Crea una reservación para el socio autenticado.
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
                'json_tarifas'    => 'nullable|array',
                'subtotal'        => 'required|numeric|min:0',
                'impuesto'        => 'nullable|numeric|min:0',
                'descuento'       => 'nullable|numeric|min:0',
                'total'           => 'required|numeric|min:0',
                'moneda'          => 'nullable|string|size:3',
                'observaciones'   => 'nullable|string|max:1000',
            ]);

            // El socio SIEMPRE sale del token. Antes se aceptaba 'socio_id' del body,
            // lo que permitía crear reservaciones a nombre de otro socio.
            $socioId = $this->socioAutenticadoId($request);

            // El vuelo debe existir y estar activo; de ahí sale la empresa (no del cliente).
            $vuelo = DB::table('vuelos_globo_para_socios_comerciales as v')
                ->join('empresas as e', 'v.empresa_id', '=', 'e.id')
                ->where('v.id', $request->input('vuelo_id'))
                ->where('v.estado', 1)
                ->select(['v.id', 'v.empresa_id', 'v.nombre', 'e.nombre as empresa'])
                ->first();

            if (!$vuelo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vuelo seleccionado no existe o no está disponible',
                ], 404);
            }

            $reservacion = Reservacion::create([
                'socio_id'        => $socioId,
                'vuelo_id'        => $vuelo->id,
                'empresa_id'      => $vuelo->empresa_id,
                'fecha_reserva'   => date('Y-m-d H:i:s'),
                'fecha_vuelo'     => $request->input('fecha_vuelo'),
                'hora_salida'     => $request->input('hora_salida'),
                'numero_personas' => $request->input('numero_personas'),
                'json_tarifas'    => $request->input('json_tarifas'),
                'subtotal'        => $request->input('subtotal'),
                'impuesto'        => $request->input('impuesto', 0),
                'descuento'       => $request->input('descuento', 0),
                'total'           => $request->input('total'),
                'moneda'          => strtoupper($request->input('moneda', 'MXN')),
                'estado'          => 'pendiente',
                'observaciones'   => $request->input('observaciones'),
            ]);

            // Devolvemos el registro ya con sus relaciones (JOIN), no el modelo crudo
            $detalle = DB::table('reservaciones_socios_comerciales as r')
                ->join('socios_comerciales as s', 'r.socio_id', '=', 's.id')
                ->join('vuelos_globo_para_socios_comerciales as v', 'r.vuelo_id', '=', 'v.id')
                ->join('empresas as e', 'r.empresa_id', '=', 'e.id')
                ->where('r.id', $reservacion->id)
                ->select([
                    'r.id',
                    's.nombre as socio',
                    'v.nombre as vuelo',
                    'e.nombre as empresa',
                    'r.fecha_vuelo',
                    'r.hora_salida',
                    'r.numero_personas',
                    'r.subtotal',
                    'r.impuesto',
                    'r.descuento',
                    'r.total',
                    'r.moneda',
                    'r.estado',
                    'r.created_at',
                ])
                ->first();

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
            return $this->errorInterno($e, 'Ocurrió un error al procesar la reservación');
        }
    }
}

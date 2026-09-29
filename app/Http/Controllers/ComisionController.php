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
                    DB::raw("COALESCE((SELECT SUM(p.monto)
                                       FROM pagos_socio_comercial_reservacion p
                                       WHERE p.reservacion_id = r.id
                                         AND p.estado = 'pagado'), 0) as total_pagado"),
                    DB::raw("COALESCE((SELECT COUNT(*)
                                       FROM pagos_socio_comercial_reservacion p2
                                       WHERE p2.reservacion_id = r.id), 0) as cantidad_pagos"),
                ])
                ->where('r.socio_id', $socioId);

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
     * Crea una reservación y calcula automáticamente la comisión del socio.
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

            $socioId = $this->socioAutenticadoId($request);

            // 1. Validar que el vuelo exista y obtener su empresa y precio base
            $vuelo = DB::table('vuelos_globo_para_socios_comerciales as v')
                ->join('empresas as e', 'v.empresa_id', '=', 'e.id')
                ->where('v.id', $request->input('vuelo_id'))
                ->select(['v.id', 'v.empresa_id', 'v.nombre', 'v.precio_base', 'e.nombre as empresa'])
                ->first();

            if (!$vuelo) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El vuelo seleccionado no existe o no está disponible',
                ], 404);
            }

            $jsonTarifasInput = $request->input('json_tarifas');
            $jsonTarifasEncoded = is_array($jsonTarifasInput) ? json_encode($jsonTarifasInput) : $jsonTarifasInput;

            // 2. Insertar la reservación en la tabla compartida con el ERP
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

            // 3. CALCULO DE COMISIÓN AUTOMÁTICA
            // A. Buscar configuración de comisión del socio
            $configComision = DB::table('socios_comerciales_configuracion_comisiones')
                ->where('socio_comercial_id', $socioId)
                ->where('estado', 1)
                ->first();

            $tipoComision  = $configComision->tipo ?? 'porcentaje'; // 'porcentaje' o 'monto_fijo'
            $valorComision = $configComision->valor ?? 10.00;        // Valor configurado (ej: 10)
            $configId      = $configComision->id ?? null;

            // B. Verificar si el socio tiene un precio personalizado para este vuelo
            $precioPersonalizado = DB::table('precios_socios_comerciales_comisiones_vuelos')
                ->where('socio_id', $socioId)
                ->where('vuelo_id', $vuelo->id)
                ->first();

            $numeroPersonas = (int) $request->input('numero_personas', 1);

            if ($precioPersonalizado) {
                // Si tiene precio personalizado por adulto, lo usamos como base
                $baseCalculo = $precioPersonalizado->precio_adulto * $numeroPersonas;
            } else {
                // Si no, usamos el precio base del vuelo multiplicado por las personas
                $baseCalculo = $vuelo->precio_base * $numeroPersonas;
            }

            // C. Calcular el monto final de la comisión
            $montoComision = ($tipoComision === 'porcentaje')
                ? ($baseCalculo * ($valorComision / 100))
                : ($valorComision * $numeroPersonas);

            // D. Insertar el registro de la comisión para que lo lea el ComisionController
            DB::table('socios_comerciales_comisiones')->insert([
                'socio_comercial_id'  => $socioId,
                'configuracion_id'    => $configId,
                'pedido_venta_id'     => 'RES-' . $reservacionId,
                'tipo'                => $tipoComision,
                'valor_configurado'   => $valorComision,
                'base_calculo'        => $baseCalculo,
                'monto_comision'      => $montoComision,
                'estado'              => 'pendiente',
                'fecha_generacion'    => date('Y-m-d H:i:s'),
                'observaciones'       => 'Comisión generada automáticamente por reserva #' . $reservacionId,
                'created_at'          => date('Y-m-d H:i:s'),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);

            // 4. Recuperar el detalle con JOIN para responder a Lovable
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
                'message' => 'Reservación creada y comisión calculada exitosamente',
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

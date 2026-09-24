<?php

namespace App\Http\Controllers;

use App\Models\Reservacion;
use Illuminate\Http\Request;

class ReservacionController extends Controller
{
    /**
     * Almacena una nueva reservación realizada por un socio comercial.
     */
    public function store(Request $request)
    {
        try {
            // Validamos los campos obligatorios para la reserva
            $this->validate($request, [
                'vuelo_id' => 'required|integer',
                'empresa_id' => 'required|integer',
                'fecha_vuelo' => 'required|date',
                'hora_salida' => 'required',
                'numero_personas' => 'required|integer|min:1',
                'subtotal' => 'required|numeric',
                'total' => 'required|numeric',
            ]);

            // Obtenemos el ID del socio autenticado mediante el token Bearer
            // (Ajusta según cómo guardes o extraigas el socio en tu middleware de autenticación)
            $socioId = $request->auth ? $request->auth->id : $request->input('socio_id');

            // Creamos la reservación
            $reservacion = Reservacion::create([
                'socio_id' => $socioId,
                'vuelo_id' => $request->input('vuelo_id'),
                'empresa_id' => $request->input('empresa_id'),
                'fecha_reserva' => now(),
                'fecha_vuelo' => $request->input('fecha_vuelo'),
                'hora_salida' => $request->input('hora_salida'),
                'numero_personas' => $request->input('numero_personas'),
                'json_tarifas' => $request->input('json_tarifas'),
                'subtotal' => $request->input('subtotal'),
                'impuesto' => $request->input('impuesto', 0),
                'descuento' => $request->input('descuento', 0),
                'total' => $request->input('total'),
                'moneda' => $request->input('moneda', 'MXN'),
                'estado' => 'pendiente', // Estado inicial por defecto
                'observaciones' => $request->input('observaciones'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservación creada exitosamente',
                'data' => $reservacion
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación en los datos enviados',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al procesar la reservación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

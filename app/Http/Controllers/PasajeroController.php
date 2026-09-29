<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Importación obligatoria de la clase DB

class PasajeroController extends Controller
{
    /**
     * Obtiene la lista de pasajeros, con opción de filtrar por reservacion_id
     */
    public function index(Request $request)
    {
        try {
            $query = DB::table('reservacion_pasajeros')
                ->whereNull('deleted_at'); // Ignoramos registros eliminados (Soft Delete)

            // Si envían un id de reservación como parámetro (?reservacion_id=X)
            if ($request->has('reservacion_id')) {
                $query->where('reservacion_id', $request->input('reservacion_id'));
            }

            $pasajeros = $query->get();

            // Mapeo estructurado para el frontend
            $formateados = $pasajeros->map(function ($pasajero) {
                return [
                    'id'                           => $pasajero->id,
                    'reservacion_id'               => $pasajero->reservacion_id,
                    'nombres'                      => $pasajero->nombres,
                    'apellido_paterno'             => $pasajero->apellido_paterno,
                    'apellido_materno'             => $pasajero->apellido_materno,
                    'nombre_completo'              => trim("{$pasajero->nombres} {$pasajero->apellido_paterno} {$pasajero->apellido_materno}"),
                    'fecha_nacimiento'             => $pasajero->fecha_nacimiento,
                    'peso_aproximado'              => $pasajero->peso_aproximado,
                    'idioma_id'                    => $pasajero->idioma_id,
                    'nombre_preferido_certificado' => $pasajero->nombre_preferido_certificado,
                    'firma'                        => $pasajero->firma,
                    'created_at'                   => $pasajero->created_at,
                    'updated_at'                   => $pasajero->updated_at
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $formateados
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pasajeros',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guarda un nuevo pasajero asociado a una reservación
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'reservacion_id'   => 'required|integer',
            'nombres'          => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'peso_aproximado'  => 'required|numeric'
        ]);

        try {
            $id = DB::table('reservacion_pasajeros')->insertGetId([
                'reservacion_id'               => $request->input('reservacion_id'),
                'nombres'                      => $request->input('nombres'),
                'apellido_paterno'             => $request->input('apellido_paterno'),
                'apellido_materno'             => $request->input('apellido_materno'),
                'fecha_nacimiento'             => $request->input('fecha_nacimiento'),
                'peso_aproximado'              => $request->input('peso_aproximado'),
                'idioma_id'                    => $request->input('idioma_id', 1),
                'nombre_preferido_certificado' => $request->input('nombre_preferido_certificado'),
                'firma'                        => $request->input('firma'),
                'created_at'                   => date('Y-m-d H:i:s'),
                'updated_at'                   => date('Y-m-d H:i:s')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pasajero registrado correctamente',
                'id'      => $id
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pasajero',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint para obtener el esquema exacto de la tabla reservacion_pasajeros
     */
    public function esquema()
    {
        try {
            $esquema = DB::select('DESCRIBE reservacion_pasajeros');

            return response()->json([
                'success' => true,
                'esquema' => $esquema
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el esquema de la tabla',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}

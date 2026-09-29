<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PasajeroController extends Controller
{
    /**
     * Pasajeros de las reservaciones DEL SOCIO AUTENTICADO.
     * GET /api/pasajeros?reservacion_id=X
     */
    public function index(Request $request)
    {
        try {
            $socioId = $this->socioAutenticadoId($request);

            // INNER JOIN con reservaciones: solo se ven pasajeros de reservaciones del socio
            $query = DB::table('reservacion_pasajeros as p')
                ->join('reservaciones_socios_comerciales as r', 'p.reservacion_id', '=', 'r.id')
                ->select([
                    'p.id',
                    'p.reservacion_id',
                    'p.nombres',
                    'p.apellido_paterno',
                    'p.apellido_materno',
                    'p.fecha_nacimiento',
                    'p.peso_aproximado',
                    'p.idioma_id',
                    'p.nombre_preferido_certificado',
                    'p.firma',
                    'p.created_at',
                    'p.updated_at',
                ])
                ->where('r.socio_id', $socioId)
                ->whereNull('p.deleted_at');

            if ($request->filled('reservacion_id')) {
                $query->where('p.reservacion_id', $request->input('reservacion_id'));
            }

            $formateados = $query->orderBy('p.id')->get()->map(function ($p) {
                return [
                    'id'                           => $p->id,
                    'reservacion_id'               => $p->reservacion_id,
                    'nombres'                      => $p->nombres,
                    'apellido_paterno'             => $p->apellido_paterno,
                    'apellido_materno'             => $p->apellido_materno,
                    'nombre_completo'              => trim("{$p->nombres} {$p->apellido_paterno} {$p->apellido_materno}"),
                    'fecha_nacimiento'             => $p->fecha_nacimiento,
                    'peso_aproximado'              => $p->peso_aproximado,
                    'idioma_id'                    => $p->idioma_id,
                    'nombre_preferido_certificado' => $p->nombre_preferido_certificado,
                    'firma'                        => $p->firma,
                    'created_at'                   => $p->created_at,
                    'updated_at'                   => $p->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $formateados,
            ], 200);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Error al obtener los pasajeros');
        }
    }

    /**
     * Registra un pasajero en una reservación DEL SOCIO AUTENTICADO.
     * POST /api/pasajeros
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'reservacion_id'   => 'required|integer',
            'nombres'          => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'peso_aproximado'  => 'required|numeric|min:1',
            'idioma_id'        => 'nullable|integer',
            'nombre_preferido_certificado' => 'nullable|string|max:255',
            'firma'            => 'nullable|string',
        ]);

        try {
            $socioId = $this->socioAutenticadoId($request);

            // La reservación debe pertenecer al socio del token
            $reservacion = DB::table('reservaciones_socios_comerciales as r')
                ->where('r.id', $request->input('reservacion_id'))
                ->where('r.socio_id', $socioId)
                ->select(['r.id'])
                ->first();

            if (!$reservacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservación no encontrada',
                ], 404);
            }

            $ahora = date('Y-m-d H:i:s');

            $id = DB::table('reservacion_pasajeros')->insertGetId([
                'reservacion_id'               => $reservacion->id,
                'nombres'                      => $request->input('nombres'),
                'apellido_paterno'             => $request->input('apellido_paterno'),
                'apellido_materno'             => $request->input('apellido_materno'),
                'fecha_nacimiento'             => $request->input('fecha_nacimiento'),
                'peso_aproximado'              => $request->input('peso_aproximado'),
                'idioma_id'                    => $request->input('idioma_id', 1),
                'nombre_preferido_certificado' => $request->input('nombre_preferido_certificado'),
                'firma'                        => $request->input('firma'),
                'created_at'                   => $ahora,
                'updated_at'                   => $ahora,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pasajero registrado correctamente',
                'id'      => $id,
            ], 201);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Error al registrar el pasajero');
        }
    }

    // El método esquema() (DESCRIBE reservacion_pasajeros) se ELIMINÓ:
    // exponer la estructura de la BD por HTTP es una mala práctica de seguridad.
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AltaSocioComercialController extends Controller
{
    /**
     * Crear socio comercial y sus credenciales de acceso
     */
    public function crearCuenta(Request $request)
    {
        // 1. Validaciones
        $this->validate($request, [
            // Tabla socios_comerciales
            'categoria_id'       => 'required|integer|exists:categorias_socios_comerciales,id',
            'nombre'             => 'required|string|max:200',
            'razon_social'       => 'nullable|string|max:200',
            'ruc'                => 'nullable|string|max:20|unique:socios_comerciales,ruc',
            'contacto_principal' => 'nullable|string|max:150',
            'telefono'           => 'nullable|string|max:50',
            'direccion'          => 'nullable|string',
            'sitio_web'          => 'nullable|url|max:200',
            'logo'               => 'nullable|string|max:255',
            'codigo_barras'      => 'nullable|string|max:100|unique:socios_comerciales,codigo_barras',
            'notas'              => 'nullable|string',
            
            // Tabla socio_accesos
            'email'              => 'required|email|max:100|unique:socio_accesos,email',
            'password'           => 'required|string|min:6'
        ]);

        $now = date('Y-m-d H:i:s');

        try {
            // 2. Transacción de Base de Datos
            $resultado = DB::transaction(function () use ($request, $now) {
                
                // Insertar en socios_comerciales
                $socioId = DB::table('socios_comerciales')->insertGetId([
                    'categoria_id'       => $request->input('categoria_id'),
                    'nombre'             => $request->input('nombre'),
                    'razon_social'       => $request->input('razon_social'),
                    'ruc'                => $request->input('ruc'),
                    'contacto_principal' => $request->input('contacto_principal'),
                    'email'              => $request->input('email'), // Mismo correo comercial
                    'telefono'           => $request->input('telefono'),
                    'direccion'          => $request->input('direccion'),
                    'sitio_web'          => $request->input('sitio_web'),
                    'logo'               => $request->input('logo'),
                    'codigo_barras'      => $request->input('codigo_barras'),
                    'notas'              => $request->input('notas'),
                    'estado'             => 'activo',
                    'created_at'         => $now,
                    'updated_at'         => $now
                ]);

                // Insertar en socio_accesos
                DB::table('socio_accesos')->insert([
                    'socio_id'   => $socioId,
                    'email'      => $request->input('email'),
                    'password'   => Hash::make($request->input('password')),
                    'estado'     => 'activo',
                    'created_at' => $now,
                    'updated_at' => $now
                ]);

                // Retornar socio creado
                return DB::table('socios_comerciales')->where('id', $socioId)->first();
            });

            return response()->json([
                'message' => 'Cuenta de socio comercial creada con éxito',
                'data'    => $resultado
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Error al registrar la cuenta',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
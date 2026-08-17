<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class SocioController extends Controller
{
    /**
     * Actualizar todos los datos del socio comercial y su cuenta.
     * PUT /api/socios/{id}
     */
    public function update(Request $request, $id)
    {
        // 1. Verificar existencia en la tabla correcta: socios_comerciales
        $socio = DB::table('socios_comerciales')
            ->where('id', $id)
            ->first();

        if (!$socio) {
            return response()->json([
                'success' => false,
                'message' => 'Socio comercial no encontrado.',
            ], 404);
        }

        // 2. Validar tipos de datos acorde al esquema SQL
        $validator = Validator::make($request->all(), [
            'categoria_id'       => 'required|integer',
            'nombre'             => 'required|string|max:200',
            'razon_social'       => 'nullable|string|max:200',
            'ruc'                => 'nullable|string|max:20',
            'contacto_principal' => 'nullable|string|max:150',
            'email'              => 'nullable|email|max:100',
            'telefono'           => 'nullable|string|max:50',
            'direccion'          => 'nullable|string',
            'sitio_web'          => 'nullable|string|max:200',
            'logo'               => 'nullable|string|max:255',
            'codigo_barras'      => 'nullable|string|max:100',
            'notas'              => 'nullable|string',
            'estado'             => 'nullable|in:activo,inactivo',

            'password'           => 'nullable|string|min:8|max:100',
            'cuenta_estado'      => 'nullable|in:activo,inactivo,bloqueado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 3. Verificaciones de Unicidad
        if ($request->filled('ruc')) {
            $rucExiste = DB::table('socios_comerciales')
                ->where('ruc', $request->ruc)
                ->where('id', '!=', $id)
                ->exists();

            if ($rucExiste) {
                return response()->json([
                    'success' => false,
                    'message' => 'El RUC ya pertenece a otro socio comercial.',
                ], 409);
            }
        }

        if ($request->filled('codigo_barras')) {
            $codigoExiste = DB::table('socios_comerciales')
                ->where('codigo_barras', $request->codigo_barras)
                ->where('id', '!=', $id)
                ->exists();

            if ($codigoExiste) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de barras ya pertenece a otro socio comercial.',
                ], 409);
            }
        }

        if ($request->filled('email')) {
            $emailExiste = DB::table('socio_accesos')
                ->where('email', strtolower(trim($request->email)))
                ->where('socio_id', '!=', $id)
                ->exists();

            if ($emailExiste) {
                return response()->json([
                    'success' => false,
                    'message' => 'El email ya está utilizado por otra cuenta de acceso.',
                ], 409);
            }
        }

        try {
            DB::beginTransaction();

            // 4. Actualizar tabla socios_comerciales
            $data = [
                'categoria_id'       => $request->categoria_id,
                'nombre'             => $request->nombre,
                'razon_social'       => $request->razon_social,
                'ruc'                => $request->ruc,
                'contacto_principal' => $request->contacto_principal,
                'email'              => $request->email,
                'telefono'           => $request->telefono,
                'direccion'          => $request->direccion,
                'sitio_web'          => $request->sitio_web,
                'logo'               => $request->logo,
                'codigo_barras'      => $request->codigo_barras,
                'notas'              => $request->notas,
                'estado'             => $request->estado ?? 'activo',
                'updated_at'         => date('Y-m-d H:i:s'),
            ];

            DB::table('socios_comerciales')
                ->where('id', $id)
                ->update($data);

            // 5. Gestionar cuenta de acceso
            $cuenta = DB::table('socio_accesos')
                ->where('socio_id', $id)
                ->first();

            if ($cuenta) {
                $cuentaData = [
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ($request->filled('email')) {
                    $cuentaData['email'] = strtolower(trim($request->email));
                }

                if ($request->filled('cuenta_estado')) {
                    $cuentaData['estado'] = $request->cuenta_estado;
                }

                if ($request->filled('password')) {
                    $cuentaData['password']         = Hash::make($request->password);
                    $cuentaData['api_token']        = null;
                    $cuentaData['token_expires_at'] = null;
                }

                DB::table('socio_accesos')
                    ->where('socio_id', $id)
                    ->update($cuentaData);
            } else if ($request->filled('password')) {
                if (!$request->filled('email')) {
                    throw new \Exception('Se requiere email para crear la cuenta de acceso.');
                }

                DB::table('socio_accesos')->insert([
                    'socio_id'         => $id,
                    'email'            => strtolower(trim($request->email)),
                    'password'         => Hash::make($request->password),
                    'api_token'        => null,
                    'token_expires_at' => null,
                    'ultimo_acceso'    => null,
                    'estado'           => $request->cuenta_estado ?? 'activo',
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
            }

            DB::commit();

            // 6. Obtener datos limpios
            $socioActualizado = DB::table('socios_comerciales')
                ->where('id', $id)
                ->first();

            $cuentaActualizada = DB::table('socio_accesos')
                ->where('socio_id', $id)
                ->select([
                    'id',
                    'socio_id',
                    'email',
                    'estado',
                    'token_expires_at',
                    'ultimo_acceso',
                    'created_at',
                    'updated_at',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Socio comercial y cuenta actualizados correctamente.',
                'data'    => [
                    'socio'  => $socioActualizado,
                    'cuenta' => $cuentaActualizada,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el socio comercial.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear socio comercial + cuenta de acceso.
     * POST /api/socios-comerciales-crea-cuenta
     */
    public function crearCuenta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Datos del socio
            'categoria_id'       => 'required|integer',
            'nombre'             => 'required|string|max:200',
            'razon_social'       => 'nullable|string|max:200',
            'ruc'                => 'nullable|string|max:20',
            'contacto_principal' => 'nullable|string|max:150',
            'email'              => 'required|email|max:100',
            'telefono'           => 'nullable|string|max:50',
            'direccion'          => 'nullable|string',
            'sitio_web'          => 'nullable|string|max:200',
            'logo'               => 'nullable|string|max:255',
            'codigo_barras'      => 'nullable|string|max:100',
            'notas'              => 'nullable|string',

            // Cuenta de acceso
            'password'           => 'required|string|min:8|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->email));

        // Validaciones de duplicados previas
        if ($request->filled('ruc')) {
            $rucExiste = DB::table('socios_comerciales')
                ->where('ruc', $request->ruc)
                ->exists();

            if ($rucExiste) {
                return response()->json([
                    'success' => false,
                    'message' => 'El RUC ya está registrado.',
                ], 409);
            }
        }

        if ($request->filled('codigo_barras')) {
            $codigoExiste = DB::table('socios_comerciales')
                ->where('codigo_barras', $request->codigo_barras)
                ->exists();

            if ($codigoExiste) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de barras ya está registrado.',
                ], 409);
            }
        }

        $emailExiste = DB::table('socio_accesos')
            ->where('email', $email)
            ->exists();

        if ($emailExiste) {
            return response()->json([
                'success' => false,
                'message' => 'El email ya está registrado.',
            ], 409);
        }

        try {
            DB::beginTransaction();

            // Insertar en socios_comerciales
            $socioId = DB::table('socios_comerciales')->insertGetId([
                'categoria_id'       => $request->categoria_id,
                'nombre'             => $request->nombre,
                'razon_social'       => $request->razon_social,
                'ruc'                => $request->ruc,
                'contacto_principal' => $request->contacto_principal,
                'email'              => $email,
                'telefono'           => $request->telefono,
                'direccion'          => $request->direccion,
                'sitio_web'          => $request->sitio_web,
                'logo'               => $request->logo,
                'codigo_barras'      => $request->codigo_barras,
                'notas'              => $request->notas,
                'estado'             => 'activo',
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);

            // Insertar en socio_accesos
            $cuentaId = DB::table('socio_accesos')->insertGetId([
                'socio_id'         => $socioId,
                'email'            => $email,
                'password'         => Hash::make($request->password),
                'api_token'        => null,
                'token_expires_at' => null,
                'ultimo_acceso'    => null,
                'estado'           => 'activo',
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);

            DB::commit();

            // Consultar datos resultantes
            $socio = DB::table('socios_comerciales')
                ->where('id', $socioId)
                ->first();

            $cuenta = DB::table('socio_accesos')
                ->where('id', $cuentaId)
                ->select([
                    'id',
                    'socio_id',
                    'email',
                    'estado',
                    'ultimo_acceso',
                    'created_at',
                    'updated_at',
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Socio comercial y cuenta creados correctamente.',
                'data'    => [
                    'socio'  => $socio,
                    'cuenta' => $cuenta,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'No fue posible crear el socio comercial y su cuenta.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
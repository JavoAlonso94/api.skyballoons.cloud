<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login()
    {
        $email = request()->input('email');
        $password = request()->input('password');

        if (!$email || !$password) {
            return response()->json([
                'success' => false,
                'message' => 'Email y password son requeridos',
            ], 422);
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Autenticación exitosa',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 200);
    }

    public function socioLogin()
    {
        $email = request()->input('email');
        $password = request()->input('password');

        if (!$email || !$password) {
            return response()->json([
                'success' => false,
                'message' => 'Email y password son requeridos',
            ], 422);
        }

        // Consulta segura utilizando JOIN y select explícito (Previene Schema Leak)
        $acceso = DB::table('socio_accesos as sa')
            ->join('socios_comerciales as sc', 'sa.socio_id', '=', 'sc.id')
            ->where('sa.email', strtolower(trim($email)))
            ->select(
                'sa.id as acceso_id',
                'sa.socio_id',
                'sa.email',
                'sa.password',
                'sa.estado as cuenta_estado',
                'sc.nombre',
                'sc.estado as socio_estado'
            )
            ->first();

        if (!$acceso) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        // Validar contraseña
        if (!Hash::check($password, $acceso->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        // Validar estado de la cuenta
        if ($acceso->cuenta_estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'El acceso del socio no está activo',
            ], 403);
        }

        // 1. Generar token en texto plano para el cliente
        $token = bin2hex(random_bytes(40));

        // 2. Hashear el token para almacenarlo de forma segura en la base de datos
        $hashedToken = hash('sha256', $token);

        // 3. Definir expiración de 12 horas
        $expiresAt = Carbon::now()->addHours(12);

        // Actualizar registro en la base de datos de manera segura
        DB::table('socio_accesos')
            ->where('id', $acceso->acceso_id)
            ->update([
                'api_token'        => $hashedToken,
                'token_expires_at' => $expiresAt,
                'ultimo_acceso'    => Carbon::now(),
                'updated_at'       => Carbon::now(),
            ]);

        // Respuesta limpia y estructurada sin exponer esquemas
        return response()->json([
            'success'    => true,
            'message'    => 'Autenticación exitosa',
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
            'socio'      => [
                'id'     => $acceso->socio_id,
                'nombre' => $acceso->nombre,
                'email'  => $acceso->email,
            ],
        ], 200);
    }
}

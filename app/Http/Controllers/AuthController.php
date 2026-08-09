<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SocioAcceso;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login()
    {
        $email = request()->input('email');
        $password = request()->input('password');

        if (!$email || !$password) {
            return response()->json(
                [
                    'message' => 'Email y password son requeridos',
                ],
                422,
            );
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(
                [
                    'message' => 'Credenciales incorrectas',
                ],
                401,
            );
        }

        return response()->json([
            'message' => 'Autenticación exitosa',
            'user' => $user,
        ]);
    }

    public function socioLogin()
    {
        $email = request()->input('email');
        $password = request()->input('password');

        if (!$email || !$password) {
            return response()->json(
                [
                    'message' => 'Email y password son requeridos',
                ],
                422,
            );
        }

        $acceso = SocioAcceso::where('email', $email)->first();

        if (!$acceso) {
            return response()->json(
                [
                    'message' => 'Credenciales incorrectas',
                ],
                401,
            );
        }

        if ($acceso->estado !== 'activo') {
            return response()->json(
                [
                    'message' => 'El acceso del socio no está activo',
                    'estado' => $acceso->estado,
                ],
                403,
            );
        }

        if (!Hash::check($password, $acceso->password)) {
            return response()->json(
                [
                    'message' => 'Credenciales incorrectas',
                ],
                401,
            );
        }

        // Generar token
        $token = bin2hex(random_bytes(40));

        // Guardar hash del token
        $acceso->api_token = hash('sha256', $token);

        // Expira en 24 horas
        $acceso->token_expires_at = Carbon::now()->addHours(24);

        // Registrar último acceso
        $acceso->ultimo_acceso = Carbon::now();

        $acceso->save();

        return response()->json([
            'message' => 'Autenticación exitosa',
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $acceso->token_expires_at,
            'socio' => [
                'id' => $acceso->socio_id,
                'email' => $acceso->email,
            ],
        ]);
    }
}

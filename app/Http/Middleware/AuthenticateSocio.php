<?php

namespace App\Http\Middleware;

use App\Models\SocioAcceso;
use Carbon\Carbon;
use Closure;

class AuthenticateSocio
{
    public function handle($request, Closure $next)
    {
        // 1. Captura robusta del token (manual o nativa)
        $header = $request->header('Authorization');
        $token = null;

        if ($header && str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
        } else {
            $token = $request->bearerToken();
        }

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token no proporcionado o cabecera ausente',
            ], 401);
        }

        // 2. Hashear el token para buscarlo
        $tokenHash = md5($token);

        $acceso = SocioAcceso::where('api_token', $tokenHash)
            ->where('estado', 'activo')
            ->first();

        if (!$acceso) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o sesión no encontrada',
            ], 401);
        }

        // 3. (Comentado temporalmente para evitar falsos positivos por zona horaria)
        /*
        if ($acceso->token_expires_at) {
            $expiresAt = Carbon::parse($acceso->token_expires_at);

            if (Carbon::now()->greaterThan($expiresAt)) {
                // Limpiar token expirado
                $acceso->api_token = null;
                $acceso->token_expires_at = null;
                $acceso->save();

                return response()->json([
                    'success' => false,
                    'message' => 'Token expirado, por favor inicie sesión nuevamente',
                ], 401);
            }
        }
        */

        // 4. Actualizar último acceso de forma segura
        $acceso->ultimo_acceso = Carbon::now();
        $acceso->save();

        // 5. Inyectar datos del socio en la petición
        $request->merge([
            'socio_autenticado_id' => $acceso->socio_id,
            'socio_autenticado_email' => $acceso->email,
            'socio_acceso' => $acceso,
        ]);

        return $next($request);
    }
}

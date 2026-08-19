<?php

namespace App\Http\Middleware;

use App\Models\SocioAcceso;
use Carbon\Carbon;
use Closure;

class AuthenticateSocio
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token no proporcionado',
            ], 401);
        }

        $tokenHash = hash('sha256', $token);

        $acceso = SocioAcceso::where('api_token', $tokenHash)
            ->where('estado', 'activo')
            ->first();

        if (!$acceso) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o sesión expirada',
            ], 401);
        }

        if ($acceso->token_expires_at && Carbon::now()->gt($acceso->token_expires_at)) {
            $acceso->api_token = null;
            $acceso->token_expires_at = null;
            $acceso->save();

            return response()->json([
                'success' => false,
                'message' => 'Token expirado, por favor inicie sesión nuevamente',
            ], 401);
        }

        $acceso->ultimo_acceso = Carbon::now();
        $acceso->save();

        $request->merge([
            'socio_autenticado_id' => $acceso->socio_id,
            'socio_autenticado_email' => $acceso->email,
            'socio_acceso' => $acceso,
        ]);

        return $next($request);
    }
}
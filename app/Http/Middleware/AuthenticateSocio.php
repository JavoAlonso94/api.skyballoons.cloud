<?php

namespace App\Http\Middleware;

use App\Models\SocioAcceso;
use Carbon\Carbon;
use Closure;

class AuthenticateSocio
{
    public function handle($request, Closure $next)
    {
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
                'message' => 'Token no proporcionado',
            ], 401);
        }

        $tokenClean = trim($token);
        $tokenMd5 = md5($tokenClean);

        // Búsqueda blindada: compatible con md5 o texto plano previo
        $acceso = SocioAcceso::where('estado', 'activo')
            ->where(function($query) use ($tokenClean, $tokenMd5) {
                $query->where('api_token', $tokenMd5)
                      ->orWhere('api_token', $tokenClean);
            })
            ->first();

        if (!$acceso) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no válida o expirada',
            ], 401);
        }

        // Actualizar último acceso de forma segura
        try {
            $acceso->ultimo_acceso = Carbon::now();
            $acceso->save();
        } catch (\Exception $e) {
            // Ignorar error menor de timestamp para no bloquear la petición
        }

        // Inyectar datos del socio en la petición
        $request->merge([
            'socio_autenticado_id' => $acceso->socio_id,
            'socio_autenticado_email' => $acceso->email,
            'socio_acceso' => $acceso,
        ]);

        return $next($request);
    }
}

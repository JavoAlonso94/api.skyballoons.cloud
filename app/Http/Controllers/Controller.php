<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;
use Throwable;

class Controller extends BaseController
{
    /**
     * Respuesta 500 genérica.
     *
     * El detalle técnico (mensaje SQL, nombres de tablas/columnas, rutas de
     * archivos) SOLO se guarda en el log del servidor. Al cliente nunca se le
     * envía $e->getMessage(): eso expone el esquema de la base de datos.
     */
    protected function errorInterno(Throwable $e, string $mensaje, string $clave = 'message')
    {
        Log::error($mensaje . ' :: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'archivo'   => $e->getFile() . ':' . $e->getLine(),
        ]);

        return response()->json([
            'status'  => 'error',
            'success' => false,
            $clave    => $mensaje,
        ], 500);
    }

    /**
     * ID del socio autenticado. Sale SIEMPRE del token (middleware auth.socio),
     * nunca de un parámetro que mande el cliente.
     */
    protected function socioAutenticadoId($request): int
    {
        return (int) $request->input('socio_autenticado_id');
    }
}

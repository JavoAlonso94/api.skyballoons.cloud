<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Importación obligatoria de la clase DB

class ServicioAdicionalController extends Controller
{
    /**
     * Obtiene la lista de servicios adicionales activos usando DB Query Builder
     */
    public function index()
    {
        // Se utiliza DB::table para evitar que el código se rompa si se agregan campos.
        // Se incluye una estructura de INNER JOIN comentada por si a futuro necesitas vincularlo con categorías de la base de datos.
        $adicionales = DB::table('servicios_adicionales as sa')
            /*
             * Ejemplo de INNER JOIN listo para usarse si tu BD lo requiere:
             * ->join('categorias_adicionales as ca', 'sa.categoria_id', '=', 'ca.id')
             */
            ->select(
                'sa.id',
                'sa.nombre',
                'sa.descripcion',
                'sa.imagen',
                'sa.precio',
                'sa.estado'
            )
            ->where('sa.estado', '=', 'Activo') // Filtramos solo los que están en estado "Activo"
            ->get();

        return response()->json([
            'success' => true,
            'data' => $adicionales
        ], 200);
    }

    /**
     * Endpoint para obtener el esquema de la tabla usando DESCRIBE.
     * Esto permite a los desarrolladores o a la app saber qué columnas existen dinámicamente.
     */
    public function esquema()
    {
        // Ejecuta el comando SQL nativo DESCRIBE
        $esquema = DB::select('DESCRIBE servicios_adicionales');

        return response()->json([
            'success' => true,
            'esquema' => $esquema
        ], 200);
    }
}

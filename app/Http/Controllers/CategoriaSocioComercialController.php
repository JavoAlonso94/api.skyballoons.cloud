<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaSocioComercialController extends Controller
{
    private $table = 'categorias_socios_comerciales';

    /**
     * Categorías activas (para el formulario de registro de socios).
     * GET /api/categorias-socios-comerciales
     */
    public function index()
    {
        try {
            // LEFT JOIN: una categoría puede no tener empresa asignada
            $categorias = DB::table($this->table . ' as c')
                ->leftJoin('empresas as e', 'c.empresa_id', '=', 'e.id')
                ->select([
                    'c.id',
                    'c.empresa_id',
                    'e.nombre as empresa',
                    'c.nombre',
                    'c.descripcion',
                    'c.estado',
                ])
                ->where('c.estado', 'activo')
                ->orderBy('c.nombre')
                ->get();

            return response()->json($categorias, 200);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Error al obtener las categorías', 'error');
        }
    }

    /**
     * Crear un nuevo registro
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'empresa_id' => 'required|integer',
            'nombre'     => 'required|string|max:100',
            'descripcion'=> 'nullable|string',
            'estado'     => 'nullable|in:activo,inactivo'
        ]);

        $now = date('Y-m-d H:i:s');

        $id = DB::table($this->table)->insertGetId([
            'empresa_id'  => $request->input('empresa_id'),
            'nombre'      => $request->input('nombre'),
            'descripcion' => $request->input('descripcion', null),
            'estado'      => $request->input('estado', 'activo'),
            'created_at'  => $now,
            'updated_at'  => $now
        ]);

        $categoria = DB::table($this->table)->where('id', $id)->first();

        return response()->json([
            'message' => 'Categoría creada con éxito',
            'data'    => $categoria
        ], 201);
    }

    /**
     * Una categoría por ID.
     * GET /api/categorias-socios-comerciales/{id}
     */
    public function show($id)
    {
        try {
            $categoria = DB::table($this->table . ' as c')
                ->leftJoin('empresas as e', 'c.empresa_id', '=', 'e.id')
                ->select([
                    'c.id',
                    'c.empresa_id',
                    'e.nombre as empresa',
                    'c.nombre',
                    'c.descripcion',
                    'c.estado',
                ])
                ->where('c.id', $id)
                ->where('c.estado', 'activo')
                ->first();

            if (!$categoria) {
                return response()->json(['error' => 'Categoría no encontrada'], 404);
            }

            return response()->json($categoria, 200);

        } catch (\Throwable $e) {
            return $this->errorInterno($e, 'Error al obtener la categoría', 'error');
        }
    }

    /**
     * Actualizar un registro por ID
     */
    public function update(Request $request, $id)
    {
        $categoria = DB::table($this->table)->where('id', $id)->first();

        if (!$categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }

        $this->validate($request, [
            'empresa_id' => 'integer',
            'nombre'     => 'string|max:100',
            'descripcion'=> 'nullable|string',
            'estado'     => 'in:activo,inactivo'
        ]);

        $dataToUpdate = [];

        if ($request->has('empresa_id'))  $dataToUpdate['empresa_id']  = $request->input('empresa_id');
        if ($request->has('nombre'))      $dataToUpdate['nombre']      = $request->input('nombre');
        if ($request->has('descripcion')) $dataToUpdate['descripcion'] = $request->input('descripcion');
        if ($request->has('estado'))      $dataToUpdate['estado']      = $request->input('estado');

        $dataToUpdate['updated_at'] = date('Y-m-d H:i:s');

        DB::table($this->table)->where('id', $id)->update($dataToUpdate);

        $updatedCategoria = DB::table($this->table)->where('id', $id)->first();

        return response()->json([
            'message' => 'Categoría actualizada con éxito',
            'data'    => $updatedCategoria
        ], 200);
    }

    /**
     * Eliminar un registro por ID
     */
    public function destroy($id)
    {
        $categoria = DB::table($this->table)->where('id', $id)->first();

        if (!$categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }

        DB::table($this->table)->where('id', $id)->delete();

        return response()->json(['message' => 'Categoría eliminada con éxito'], 200);
    }
}

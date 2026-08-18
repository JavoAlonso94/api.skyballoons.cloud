<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaSocioComercialController extends Controller
{
    private $table = 'categorias_socios_comerciales';

    /**
     * Obtener todos los registros
     */
    public function index()
    {
        $categorias = DB::table($this->table)->get();
        return response()->json($categorias, 200);
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
     * Mostrar un registro específico por ID
     */
    public function show($id)
    {
        $categoria = DB::table($this->table)->where('id', $id)->first();

        if (!$categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }

        return response()->json($categoria, 200);
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
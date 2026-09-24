<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vuelo extends Model
{
    protected $table = 'vuelos_globo_para_socios_comerciales';

    protected $fillable = [
        'categoria_vuelo_id',
        'empresa_id',
        'nombre',
        'descripcion',
        'duracion_minutos',
        'capacidad_maxima',
        'precio_base',
        'estado'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descuento extends Model
{
    protected $table = 'descuentos';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'tipo',
        'valor',
        'aplica_a',
        'vigencia_inicio',
        'vigencia_fin',
        'estado'
    ];
}

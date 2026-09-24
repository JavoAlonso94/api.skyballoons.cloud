<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservacion extends Model
{
    protected $table = 'reservaciones_socios_comerciales';

    protected $fillable = [
        'socio_id',
        'vuelo_id',
        'empresa_id',
        'fecha_reserva',
        'fecha_vuelo',
        'hora_salida',
        'numero_personas',
        'json_tarifas',
        'subtotal',
        'impuesto',
        'descuento',
        'total',
        'moneda',
        'estado',
        'observaciones'
    ];

    protected $casts = [
        'json_tarifas' => 'array', // Para que Lumen maneje automáticamente el JSON
    ];
}

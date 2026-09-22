<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    // Nombre exacto de tu tabla en MariaDB
    protected $table = 'comisiones';

    // Campos extraídos de la base de datos que Lumen puede manipular
    protected $fillable = [
        'pedido_venta_id', //[cite: 17]
        'socio_comercial_id', //[cite: 17]
        'configuracion_id', //[cite: 17]
        'tipo', //[cite: 17]
        'valor_configurado', //[cite: 17]
        'base_calculo', //[cite: 17]
        'monto_comision', //[cite: 17]
        'estado', //[cite: 17]
        'fecha_generacion', //[cite: 17]
        'fecha_pago', //[cite: 17]
        'usuario_pago', //[cite: 17]
        'observaciones' //[cite: 17]
    ];
}

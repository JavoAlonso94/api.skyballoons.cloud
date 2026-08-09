<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocioAcceso extends Model
{
    protected $table = 'socio_accesos';

    protected $primaryKey = 'id';

    protected $fillable = [
        'socio_id',
        'email',
        'password',
        'api_token',
        'token_expires_at',
        'ultimo_acceso',
        'estado',
    ];

    protected $hidden = [
        'password',
        'api_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'ultimo_acceso' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
<?php

return [

    'defaults' => [
        'guard' => 'api',
    ],

    'guards' => [
        'api' => [
            'driver' => 'token',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

];

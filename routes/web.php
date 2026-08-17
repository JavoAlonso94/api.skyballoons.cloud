<?php

$router->get('/', function () use ($router) {
    return response()->json([
        'app' => 'Sky Balloons API',
        'version' => $router->app->version(),
    ]);
});

/*
|--------------------------------------------------------------------------
| Usuarios administrativos
|--------------------------------------------------------------------------
*/

$router->post('/api/login', 'AuthController@login');

/*
|--------------------------------------------------------------------------
| Socios
|--------------------------------------------------------------------------
*/

$router->post('/api/socios/login', 'AuthController@socioLogin');

$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->put('/api/socios/{id}', 'SocioController@update');
});


// Correcto si no usas grupos con prefijo
$router->post('/api/socios-comerciales-crea-cuenta', 'SocioController@crearCuenta');
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
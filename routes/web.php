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
    $router->get('/api/comisiones', 'ComisionController@index');
    $router->get('/api/comisiones/paginado', 'ComisionController@indexPaginado');
});


// Correcto si no usas grupos con prefijo
$router->post('/api/socios-comerciales-crea-cuenta', 'SocioController@crearCuenta');

/*
|--------------------------------------------------------------------------
| Categorías de Socios Comerciales
|--------------------------------------------------------------------------
*/

$router->get('/api/categorias-socios-comerciales', 'CategoriaSocioComercialController@index');
$router->post('/api/categorias-socios-comerciales', 'CategoriaSocioComercialController@store');
$router->get('/api/categorias-socios-comerciales/{id}', 'CategoriaSocioComercialController@show');
$router->put('/api/categorias-socios-comerciales/{id}', 'CategoriaSocioComercialController@update');
$router->delete('/api/categorias-socios-comerciales/{id}', 'CategoriaSocioComercialController@destroy');

$router->post('/api/socios-comerciales-crea-cuenta', 'AltaSocioComercialController@crearCuenta');


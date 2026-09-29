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

$router->group(['middleware' => 'auth.socio'], function () use ($router) {
    // Comisiones
    $router->get('/api/comisiones', 'ComisionController@index');
    $router->get('/api/comisiones/paginado', 'ComisionController@indexPaginado');

    // Catálogo
    $router->get('/api/vuelos', 'VueloController@index');
    $router->get('/api/promociones', 'DescuentoController@index');
    $router->get('/api/servicios-adicionales', 'ServicioAdicionalController@index');

    // Reservaciones
    $router->get('/api/reservaciones', 'ReservacionController@index');
    $router->post('/api/reservaciones', 'ReservacionController@store');

    // Pasajeros de reservaciones
    $router->get('/api/pasajeros', 'PasajeroController@index');
    $router->post('/api/pasajeros', 'PasajeroController@store');

    // Perfil del socio y actualización de foto
    $router->get('/api/socio/perfil', 'SocioController@show');
    $router->post('/api/socio/perfil', 'SocioController@updatePerfil');
});

/*
|--------------------------------------------------------------------------
| Registro de socios (público)
|--------------------------------------------------------------------------
*/

$router->post('/api/socios-comerciales-crea-cuenta', 'AltaSocioComercialController@crearCuenta');

/*
|--------------------------------------------------------------------------
| Categorías de Socios Comerciales (solo lectura, para el formulario de registro)
|--------------------------------------------------------------------------
| POST / PUT / DELETE se quitaron de la API pública: cualquiera podía crear,
| editar o borrar categorías sin autenticarse. Si se necesitan, deben ir
| detrás de un middleware de administrador.
*/

$router->get('/api/categorias-socios-comerciales', 'CategoriaSocioComercialController@index');
$router->get('/api/categorias-socios-comerciales/{id}', 'CategoriaSocioComercialController@show');

/*
| Se eliminaron las rutas de diagnóstico/esquema:
|   GET /api/db-test
|   GET /api/pasajeros/esquema
|   GET /api/servicios-adicionales/esquema
| Exponían la estructura y la versión de la base de datos sin autenticación.
*/

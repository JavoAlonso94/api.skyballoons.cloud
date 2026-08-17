<?php

require_once __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Define Missing Laravel Helpers for Lumen
|--------------------------------------------------------------------------
|
| Lumen does not include app_path() or public_path() by default.
| Defining them here resolves "Call to undefined function app_path()".
|
*/

if (!function_exists('app_path')) {
    function app_path($path = '') {
        return app()->basePath('app') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '') {
        return app()->basePath('public') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Enable Facades & Eloquent
|--------------------------------------------------------------------------
*/

$app->withFacades();
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
*/

$app->configure('app');
$app->configure('auth');
$app->configure('view'); // Added to resolve view & compiler dependencies

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
*/

// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

// Fixes: BindingResolutionException [$cachePath] in class Illuminate\View\Compilers\Compiler
$app->register(Illuminate\View\ViewServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
*/
$app->middleware([
    App\Http\Middleware\CorsMiddleware::class,
]);


/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
<?php

require_once __DIR__.'/../vendor/autoload.php';

Dotenv::load(__DIR__.'/../');

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
	realpath(__DIR__.'/../')
);

$app->withFacades();

// $app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    'Illuminate\Contracts\Debug\ExceptionHandler',
    'App\Exceptions\Handler'
);

$app->singleton(
    'Illuminate\Contracts\Console\Kernel',
    'App\Console\Kernel'
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    'Illuminate\Cookie\Middleware\EncryptCookies',
    'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
    'Illuminate\Session\Middleware\StartSession',
    'Illuminate\View\Middleware\ShareErrorsFromSession',
    //'Laravel\Lumen\Http\Middleware\VerifyCsrfToken',
    //'App\Http\Middleware\AuthMiddleware',
]);

//$app->routeMiddleware([
//    'authAdmin' => App\Http\Middleware\AuthMiddleware::class,
//]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register('FintechFab\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider');

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

require __DIR__.'/../app/Http/routes.php';

if (! function_exists('START')) {
    $timers = [];

    define('STATSD_PORT', '8125');
    define('STATSD_HOST', '127.0.0.1');

    function START($operation) {
        global $timers;
        array_push($timers, [
            'time' => microtime(true),
            'operation' => $operation,
        ]);
    }

    function FINISH() {
        global $timers;
        $timer = array_pop($timers);
        if (! $timer) {
            throw new \Exception('Не парный таймер.');
        }
        $diff = sprintf("%.5f", microtime(true) - $timer['time']);
        TIMER($timer['operation'], $diff);
    }

    function STATSD_SOCKET() {
        return socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    function TIMER($operation, $value) {
        if ($sock = STATSD_SOCKET()) {
            \Log::info('Timer '  . $operation . ' = ' . $value . 's');
            $msg = $operation . ":$value|ms|@0.1";
            $len = strlen($msg);
            socket_sendto($sock, $msg, $len, 0, STATSD_HOST, STATSD_PORT);
            socket_close($sock);
        }
    }

    function GAUGE($name, $value) {
        if ($sock = STATSD_SOCKET()) {
            $msg = $name . ":$value|g";
            $len = strlen($msg);
            socket_sendto($sock, $msg, $len, 0, STATSD_HOST, STATSD_PORT);
            socket_close($sock);
        }
    }
}

return $app;

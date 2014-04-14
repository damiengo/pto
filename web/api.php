<?php
require_once __DIR__.'/../vendor/autoload.php';

use JDesrosiers\Silex\Provider\CorsServiceProvider;

// App
$app = new Silex\Application();
$app['debug'] = true;

// Initialization
$app->register(new CorsServiceProvider(), array(
    "cors.allowOrigin" => "http://localhost",
));

// Routes
$app->get('/admin/upload', function() use ($app) {
    $request = $app['request'];

    return print_r($request->files, true);
});

$app->after($app["cors"]);

$app->run();


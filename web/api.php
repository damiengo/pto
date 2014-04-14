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

    if (\Flow\Basic::save('/tmp/final_file_destination', '/tmp/chunks_temp_folder')) {
        echo "\nSaved!";
    }
    else {
        // This is not a final chunk or request is invalid, continue to upload.
        echo "\nNot final or invalid..";
    }

    return 1;
});

$app->after($app["cors"]);

$app->run();


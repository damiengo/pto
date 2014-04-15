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
$app->match('/admin/upload', function() use ($app) {
    //if (\Flow\Basic::save('/tmp/final_file_destination', '/tmp/chunks_temp_folder')) {
    //    echo "\nSaved!";
    //}
    //else {
        // This is not a final chunk or request is invalid, continue to upload.
    //    echo "\nNot final or invalid..";
    //}



    $config = new \Flow\Config();
    $config->setTempDir('/tmp/chunks_temp_folder');
    $file = new \Flow\File($config);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        print_r($_FILES);
        if ($file->checkChunk()) {
            header("HTTP/1.1 200 Ok");
            echo "check ok";
        }
        else {
            header("HTTP/1.1 404 Not Found");
            print_r($file);
            echo "check ko";
            return 0;
        }
    }
    else {
        if ($file->validateChunk()) {
            echo "chunk validated";
            $file->saveChunk();
        }
        else {
            // error, invalid chunk upload request, retry
            header("HTTP/1.1 400 Bad Request");
            echo "chunk unvalidated";
            return 0;
        }
    }
    if ($file->validateFile() && $file->save('/tmp/final_file_name')) {
        // File upload was completed
        echo "upload completed";
    }
    else {
        // This is not a final chunk, continue to upload
        echo "not final chunk";
    }

    return 1;
});

$app->after($app["cors"]);

$app->run();


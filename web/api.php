<?php
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** App **/
$app = new Silex\Application();
$app['debug'] = true;

/** After app **/
$app->after(function (Request $request, Response $response) {
    $response->headers->set("Access-Control-Allow-Origin", "http://localhost");
});

/** Routes **/

// Admin authetication
$app->match('/admin/authenticate', function(Request $request) use ($app) {


    return $app->json(['connected' => 'yes'], 200);

});

// Images uploading
$app->match('/admin/upload', function() use ($app) {

    $tmpDir   = '/tmp/pto/';
    $finalDir = '/tmp/pto/';

    $config = new \Flow\Config();
    $config->setTempDir($tmpDir);
    $file = new \Flow\File($config);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($file->checkChunk()) {
            // Nothing
        }
        else {
            return $app->json(['error' => 'not found'], 404);
        }
    }
    else {
        if ($file->validateChunk()) {
            $file->saveChunk();
        }
        else {
            // error, invalid chunk upload request, retry
            return $app->json(['error' => 'bad request'], 400);
        }
    }
    $success = false;
    if ($file->validateFile() && $file->save($finalDir.$_POST['flowFilename'])) {
      // File upload was completed
      $success = true;
    }
    else {
        // This is not a final chunk, continue to upload
    }

    // Just imitate that the file was uploaded and stored.
    return $app->json([
        'success' => $success,
        'files' => $_FILES,
        'get' => $_GET,
        'post' => $_POST,
        //optional
        'flowTotalSize' => isset($_FILES['file']) ? $_FILES['file']['size'] : $_GET['flowTotalSize'],
        'flowIdentifier' => isset($_FILES['file']) ? $_FILES['file']['name'] . '-' . $_FILES['file']['size']
            : $_GET['flowIdentifier'],
        'flowFilename' => isset($_FILES['file']) ? $_FILES['file']['name'] : $_GET['flowFilename'],
        'flowRelativePath' => isset($_FILES['file']) ? $_FILES['file']['tmp_name'] : $_GET['flowRelativePath']
    ], 200);
});

$app->run();


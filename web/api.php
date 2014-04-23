<?php
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

/** App **/
$app = new Silex\Application();
$app['debug'] = true;

/** Database access **/
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/app.db',
    ),
));

/** Init database **/
$schema = new \Doctrine\DBAL\Schema\Schema();
$galleriesTable = $schema->createTable("galleries");
$galleriesTable->addColumn("id", "integer", array("unsigned" => true));
$galleriesTable->addColumn("title", "string", array("length" => 32));
$galleriesTable->setPrimaryKey(array("id"));
$galleriesTable->addUniqueIndex(array("title"));

$sqls = $schema->toSql($app["db"]->getDatabasePlatform());

foreach($sqls as $sql) {
    try {
        $app["db"]->query($sql);
    }
    catch(\Exception $e) {

    }
}

/** Before App **/
$app->before(function (Request $request) {
    // JSON in http request content
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request = new ParameterBag(is_array($data) ? $data : array());
    }
});

/** After App **/
$app->after(function (Request $request, Response $response) {
    $response->headers->set("Access-Control-Allow-Origin", "http://localhost");
});

/** Routes **/
// Admin authetication
$app->match('/admin/authenticate', function(Request $request) use ($app) {
    $username = (string) $request->get("username");
    $password = (string) $request->get("password");

    if($username === "saby" && $password === "da") {
        return $app->json(['connected' => true, 'username' => $username], 200);
    }
    else {
        return $app->json(['connected' => false, 'username' => $username], 403);
    }
});

// Adding a gallery
$app->post('/admin/gallery', function(Request $request) use ($app) {
    $title = $request->get("title", "");
    if($title !== "") {
        $app["db"]->insert("galleries", array("title" => $title));
    }

    return $app->json(['added' => true, 'title' => $title], 200);
});

// List galleries
$app->get('admin/galleries', function(Request $request) use ($app) {
    $statement = $app["db"]->prepare("SELECT id, title FROM galleries");
    $statement->execute();
    $galleries = $statement->fetchAll();

    return $app->json($galleries, 200);
});

// Images uploading
$app->match('/admin/upload', function(Request $request) use ($app) {

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


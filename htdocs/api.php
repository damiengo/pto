<?php
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Imagine\Image\Box;
use Neutron\Silex\Provider\ImagineServiceProvider;
use Cocur\Slugify\Slugify;

/** App **/
$app = new Silex\Application();
$app['debug'] = true;

/** Config **/
$app->register(new DerAlex\Silex\YamlConfigServiceProvider("config.yml"));

/** Database access **/
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => $app["config"]["database"]["driver"],
        'host'     => $app["config"]["database"]["host"],
        'dbname'   => $app["config"]["database"]["dbname"],
        'user'     => $app["config"]["database"]["user"],
        'password' => $app["config"]["database"]["password"]
    ),
));

/** Image resizing **/
$app->register(new ImagineServiceProvider());

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

    if($username === $app["config"]["admin"]["username"] && $password === $app["config"]["admin"]["password"]) {
        return $app->json(['connected' => true, 'username' => $username], 200);
    }
    else {
        return $app->json(['connected' => false, 'username' => $username], 403);
    }
});

// Adding a gallery
$app->post('/admin/gallery', function(Request $request) use ($app) {
    $title = $request->get("title", "");
    $slugify = new Slugify();
    $slug = $slugify->slugify($title);
    if($title !== "") {
        $app["db"]->insert("galleries", array("title" => $title, "slug" => $slug));
    }

    return $app->json(['added' => true, 'id' => $app["db"]->lastInsertId(), 'title' => $title, 'slug' => $slug], 200);
});

// Deleting a gallery
$app->post('/admin/delete_gallery', function(Request $request) use ($app) {
    $id = $request->get("id", "");
    if($id !== "") {
        $finalDir     = $app["config"]["upload"]["final_dir"];
        $originalDir  = $app["config"]["upload"]["original_dir"].$id.DIRECTORY_SEPARATOR;
        $thumbnailDir = $app["config"]["upload"]["thumbnail_dir"].$id.DIRECTORY_SEPARATOR;
        // Cleaning images
        $statement = $app["db"]->prepare("SELECT * FROM images WHERE gallery_id = ?");
        $statement->bindValue(1, $id);
        $statement->execute();
        $images = $statement->fetchAll();
        foreach($images as $image) {
            if(file_exists($finalDir.$originalDir.$image["name"])) {
                unlink($finalDir.$originalDir.$image["name"]);
            }
            if(file_exists($finalDir.$thumbnailDir.$image["name"])) {
                unlink($finalDir.$thumbnailDir.$image["name"]);
            }
        }
        // Cleaning folder
        if(file_exists($finalDir.$originalDir)) {
            rmdir($finalDir.$originalDir);
        }
        if(file_exists($finalDir.$thumbnailDir)) {
            rmdir($finalDir.$thumbnailDir);
        }
        $app["db"]->delete("images", array("gallery_id" => $id));

        // Deleting gallery
        $app["db"]->delete("galleries", array("id" => $id));

        return $app->json(array("success" => "Gallery deleted"), 200);
    }
    return $app->json(array("error" => "No gallery id given"), 418);
});

// List galleries
$app->get('admin/galleries', function(Request $request) use ($app) {
    $statement = $app["db"]->prepare("SELECT id, title, password, slug FROM galleries ORDER BY created_at DESC");
    $statement->execute();
    $galleries = $statement->fetchAll();

    return $app->json($galleries, 200);
});

// List pictures
$app->get('admin/images/{galleryId}', function(Request $request, $galleryId) use ($app) {
    $statement = $app["db"]->prepare("SELECT id, name FROM images WHERE gallery_id = ? ORDER BY created_at DESC");
    $statement->bindValue(1, $galleryId);
    $statement->execute();
    $galleries = $statement->fetchAll();

    return $app->json($galleries, 200);
});

// Reset gallery password
$app->post('admin/gallery_password', function(Request $request) use ($app) {
    $id       = $request->get("id", "");
    $password = $request->get("password", "");
    $app["db"]->update("galleries", array("password" => $password), array("id" => $id));

    return $app->json(array(), 200);
});

// Images uploading
$app->match('/admin/upload', function(Request $request) use ($app) {

    $tmpDir       = $app["config"]["upload"]["tmp_dir"];
    $finalDir     = $app["config"]["upload"]["final_dir"];
    $originalDir  = $app["config"]["upload"]["original_dir"];
    $thumbnailDir = $app["config"]["upload"]["thumbnail_dir"];

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
    // Images dir
    $originalPath  = $finalDir.$originalDir;
    $thumbnailPath = $finalDir.$thumbnailDir;
    if( ! file_exists($originalPath)) {
        mkdir($originalPath);
    }
    if( ! file_exists($thumbnailPath)) {
        mkdir($thumbnailPath);
    }
    // Gallery dir
    $galleryId = $request->get("galleryId");
    $fileName  = $_POST["flowFilename"];;
    $originalGalleryPath  = $originalPath.$galleryId.DIRECTORY_SEPARATOR;
    $thumbnailGalleryPath = $thumbnailPath.$galleryId.DIRECTORY_SEPARATOR;
    if( ! file_exists($originalGalleryPath)) {
      mkdir($originalGalleryPath);
    }
    if( ! file_exists($thumbnailGalleryPath)) {
      mkdir($thumbnailGalleryPath);
    }
    // Saving
    if ($file->validateFile() && $file->save($originalGalleryPath.$fileName)) {
      // Resizing
      $image  = $app["imagine"]->open($originalGalleryPath.$fileName);
      $srcBox = $image->getSize();
      // Scale on the smaller dimension
      $maxWidth  = 320;
      $maxHeight = 320;
      if ($srcBox->getWidth() > $srcBox->getHeight()) {
          $width  = $maxWidth;
          $height = $srcBox->getHeight()*($maxWidth/$srcBox->getWidth());
      }
      else {
          $width  = $srcBox->getWidth()*($maxHeight/$srcBox->getHeight());
          $height = $maxHeight;
      }
      $image->resize(new Box($width, $height))
            ->save($thumbnailGalleryPath.$fileName);
      // File upload was completed
      $app["db"]->insert("images", array("name" => $fileName, "gallery_id" => $galleryId));
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

// Check gallery password
$app->get('gallery/check_password/{gallery_slug}/{password}', function(Request $request, $gallery_slug, $password) use ($app) {
  $statement = $app["db"]->prepare("SELECT id, title, password, slug FROM galleries WHERE slug = ? AND password = ?");
  $statement->bindValue(1, $gallery_slug);
  $statement->bindValue(2, $password);
  $statement->execute();
  $galleries = $statement->fetchAll();

  if(count($galleries) == 0) {
    return $app->json(array("error" => "Authentication failed"), 403);
  }

  return $app->json(array("success" => true, "gallery" => $galleries[0]), 200);
});

// Get the gallery images
$app->get('gallery/images/{id}', function(Request $request, $id) use ($app) {
  $statement = $app["db"]->prepare("SELECT name FROM images WHERE gallery_id = ? ORDER BY created_at DESC");
  $statement->bindValue(1, $id);
  $statement->execute();
  $images = $statement->fetchAll();

  return $app->json(array("success" => true, "images" => $images), 200);
});

$app->run();


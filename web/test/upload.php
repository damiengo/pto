<?php
$tempDir = __DIR__ . DIRECTORY_SEPARATOR . 'temp';
if (!file_exists($tempDir)) {
	mkdir($tempDir);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $number     = $_POST['flowChunkNumber'];
  $id         = $_POST['flowIdentifier'];
  $final_dir  = $tempDir . DIRECTORY_SEPARATOR . $id;
  mkdir($final_dir);
  $final_path = $final_dir . '/chunck.part' . $number;
  $origi_path = $_FILES['file']['tmp_name'];
  move_uploaded_file($origi_path, $final_path);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $chunkDir = $tempDir . DIRECTORY_SEPARATOR . $_GET['flowIdentifier'];
  echo $chunkDir;
  echo "====";
  $chunkFile = $chunkDir.'/chunck.part'.$_GET['flowChunkNumber'];
  echo $chunkFile;
  echo "****";
	if (file_exists($chunkFile)) {
		header("HTTP/1.0 200 Ok");
	} else {
		header("HTTP/1.0 404 Not Found");
	}
}
// Just imitate that the file was uploaded and stored.
sleep(2);
echo json_encode([
    'success' => true,
    'files' => $_FILES,
    'get' => $_GET,
    'post' => $_POST,
    //optional
    'flowTotalSize' => isset($_FILES['file']) ? $_FILES['file']['size'] : $_GET['flowTotalSize'],
    'flowIdentifier' => isset($_FILES['file']) ? $_FILES['file']['name'] . '-' . $_FILES['file']['size']
        : $_GET['flowIdentifier'],
    'flowFilename' => isset($_FILES['file']) ? $_FILES['file']['name'] : $_GET['flowFilename'],
    'flowRelativePath' => isset($_FILES['file']) ? $_FILES['file']['tmp_name'] : $_GET['flowRelativePath']
]);

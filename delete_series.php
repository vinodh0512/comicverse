<?php
// delete_series.php
header('Content-Type: application/json');
$baseDir = __DIR__ . '/Book_data/';
$input = json_decode(file_get_contents('php://input'), true);
$folder = basename($input['folder'] ?? ''); 
$type = basename($input['type'] ?? '');

if (!$folder || !$type) { echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']); exit; }

$targetDir = $baseDir . $type . '/' . $folder;

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

if (is_dir($targetDir)) {
    if (deleteDirectory($targetDir)) echo json_encode(['status' => 'success']);
    else echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Series not found']);
}
?>
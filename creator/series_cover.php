<?php
header('Content-Type: application/json');
error_reporting(0);

function sanitizeName($string) { return preg_replace('/[^a-zA-Z0-9\s_-]/', '', str_replace(' ', '_', $string)); }

$baseDir = dirname(__DIR__) . '/Book_data/';
$seriesName = $_POST['seriesName'] ?? '';
$type = $_POST['type'] ?? '';
if ($seriesName === '' || $type === '' || !isset($_FILES['cover'])) { echo json_encode(['status' => 'error', 'message' => 'Missing parameters']); exit; }

$safeSeries = sanitizeName($seriesName);
$safeType = sanitizeName($type);
$seriesDir = $baseDir . $safeType . '/' . $safeSeries . '/';
if (!is_dir($seriesDir) && !mkdir($seriesDir, 0777, true)) { echo json_encode(['status' => 'error', 'message' => 'Failed to create series dir']); exit; }

$file = $_FILES['cover'];
if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) { echo json_encode(['status' => 'error', 'message' => 'Invalid file']); exit; }

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
if (!isset($extMap[$mime])) { echo json_encode(['status' => 'error', 'message' => 'Unsupported type']); exit; }
$ext = $extMap[$mime];
$dest = $seriesDir . 'cover.' . $ext;

function resizeSave($srcPath, $mime, $dest, $maxWidth = 800, $quality = 85) {
    if (!extension_loaded('gd')) { return copy($srcPath, $dest); }
    if ($mime === 'image/jpeg') { $img = imagecreatefromjpeg($srcPath); }
    elseif ($mime === 'image/png') { $img = imagecreatefrompng($srcPath); imagealphablending($img, false); imagesavealpha($img, true); }
    elseif ($mime === 'image/gif') { $img = imagecreatefromgif($srcPath); }
    elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) { $img = imagecreatefromwebp($srcPath); }
    else { return copy($srcPath, $dest); }
    if (!$img) { return false; }
    $w = imagesx($img); $h = imagesy($img);
    if ($w > $maxWidth) { $ratio = $h / $w; $newW = $maxWidth; $newH = (int)round($newW * $ratio); $res = imagecreatetruecolor($newW, $newH); if ($mime !== 'image/jpeg') { imagealphablending($res, false); imagesavealpha($res, true); } imagecopyresampled($res, $img, 0,0,0,0, $newW,$newH,$w,$h); imagedestroy($img); $img = $res; }
    $ok = false;
    if ($mime === 'image/jpeg') { $ok = imagejpeg($img, $dest, $quality); }
    elseif ($mime === 'image/png') { $pngc = round((100 - $quality) / 100 * 9); $ok = imagepng($img, $dest, $pngc); }
    elseif ($mime === 'image/gif') { $ok = imagegif($img, $dest); }
    elseif ($mime === 'image/webp' && function_exists('imagewebp')) { $ok = imagewebp($img, $dest, $quality); }
    imagedestroy($img);
    return $ok;
}

foreach (['cover.jpg','cover.png','cover.gif','cover.webp'] as $old) { $p = $seriesDir . $old; if (is_file($p) && $p !== $dest) { @unlink($p); } }

$ok = resizeSave($file['tmp_name'], $mime, $dest);
if (!$ok) { echo json_encode(['status' => 'error', 'message' => 'Save failed']); exit; }

$url = 'Book_data/' . $safeType . '/' . $safeSeries . '/' . basename($dest);
echo json_encode(['status' => 'success', 'cover' => $url]);
?>
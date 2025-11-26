<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'creator') { echo json_encode(['status'=>'error','message'=>'Forbidden']); exit; }
$baseDir = dirname(__DIR__) . '/Book_data/';
$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
$series = $_POST['series'] ?? '';
$type = $_POST['type'] ?? '';
$chapterNum = $_POST['chapterNum'] ?? '';
function sanitize($s){ return preg_replace('/[^a-zA-Z0-9\s_-]/','',str_replace(' ','_',$s)); }
$safeType = sanitize($type);
$safeSeries = sanitize($series);
$safeChapter = 'Chapter_' . sanitize($chapterNum);
$chapterDir = $baseDir . $safeType . '/' . $safeSeries . '/' . $safeChapter . '/';
if (!is_dir($chapterDir)) { echo json_encode(['status'=>'error','message'=>'Chapter not found']); exit; }
if (!isset($_FILES['file'])) { echo json_encode(['status'=>'error','message'=>'No file']); exit; }
$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) { echo json_encode(['status'=>'error','message'=>'Upload error']); exit; }
$fi = new finfo(FILEINFO_MIME_TYPE);
$mime = $fi->file($f['tmp_name']);
if (!isset($allowed[$mime])) { echo json_encode(['status'=>'error','message'=>'Invalid image']); exit; }
$ext = $allowed[$mime];
$dest = $chapterDir . 'cover.' . $ext;
@move_uploaded_file($f['tmp_name'], $dest);
@chmod($dest, 0644);
$metaPath = $chapterDir . 'metadata.json';
$meta = [];
if (file_exists($metaPath)) { $meta = json_decode(file_get_contents($metaPath), true) ?: []; }
if (!isset($meta['assets'])) $meta['assets'] = [];
$meta['assets']['thumbnail'] = $safeType . '/' . $safeSeries . '/' . $safeChapter . '/cover.' . $ext;
$meta['assets']['thumbnail_base64'] = null;
file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
$thumbUrl = dirname($_SERVER['SCRIPT_NAME']);
$thumbUrl = rtrim($thumbUrl, '/');
$thumbUrl .= '/Book_data/' . $meta['assets']['thumbnail'];
if (file_exists(__DIR__ . '/db/sqlite.php')) { require_once __DIR__ . '/db/sqlite.php'; }
try {
    if (function_exists('db')) {
        $pdo = db();
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT id FROM manga_metadata WHERE creator_id = ? AND title = ?');
            $stmt->execute([$_SESSION['user_id'] ?? ($_SESSION['username'] ?? ''), $series]);
            $mangaId = (int)($stmt->fetchColumn() ?: 0);
            if ($mangaId) {
                $stmtC = $pdo->prepare('SELECT id FROM chapters WHERE manga_id = ? AND chapter_number = ?');
                $stmtC->execute([$mangaId, (int)$chapterNum]);
                $cid = (int)($stmtC->fetchColumn() ?: 0);
                if ($cid) {
                    $pdo->prepare('UPDATE chapters SET thumbnail_path = ? WHERE id = ?')->execute([$meta['assets']['thumbnail'], $cid]);
                }
            }
        }
    }
} catch (Throwable $e) {}
echo json_encode(['status'=>'success','thumbnail'=>$thumbUrl]);
?>

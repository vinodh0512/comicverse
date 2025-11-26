<?php
// uploader_update_cover.php (Dedicated AJAX endpoint for updating Chapter Cover)
session_start();
ini_set('display_errors', 0); // Suppress errors for clean JSON response
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// --- 1. CONFIGURATION & SECURITY CHECK ---

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'creator') { 
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Forbidden: Creator login required.']); 
    exit; 
}

$baseDir = dirname(__DIR__) . '/Book_data/';
$allowedMimes = [
    'image/jpeg' => 'jpg', 
    'image/png' => 'png', 
    'image/webp' => 'webp', 
    'image/gif' => 'gif'
];

// --- 2. HELPER FUNCTIONS (CORRECTED) ---

function sanitizeName($s) { return preg_replace('/[^a-zA-Z0-9\s_-]/','',str_replace(' ','_',$s)); }

function logError($msg) {
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/cover_update_errors.log';
    if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
    $u = $_SESSION['username'] ?? 'unknown';
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] [$u] " . $msg . "\n", FILE_APPEND);
}

/**
 * Calculates the public, absolute URL path for an image file.
 * @param string $relativePath The path relative to the Book_data directory (e.g., manga/series/Chapter_1/cover.jpg).
 * @return string The full public URL.
 */
function getWebUrl($relativePath) {
    // Determine the base web directory path (e.g., /app/folder or just /)
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = rtrim($scriptDir, '/');
    
    // Construct the public URL: (Base URL + /Book_data/ + relative path)
    return $scriptDir . '/Book_data/' . $relativePath;
}

function resizeSave($srcPath, $mime, $dest, $maxWidth = 800, $quality = 85) {
    // Requires PHP GD extension
    if (!extension_loaded('gd')) { return copy($srcPath, $dest); }
    
    // Create image resource from source file
    if ($mime === 'image/jpeg') { $img = imagecreatefromjpeg($srcPath); }
    elseif ($mime === 'image/png') { $img = imagecreatefrompng($srcPath); imagealphablending($img, false); imagesavealpha($img, true); }
    elseif ($mime === 'image/gif') { $img = imagecreatefromgif($srcPath); }
    elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) { $img = imagecreatefromwebp($srcPath); }
    else { return copy($srcPath, $dest); } 
    
    if (!$img) { return false; }
    
    $w = imagesx($img); $h = imagesy($img);
    
    // Resize if width exceeds maxWidth
    if ($w > $maxWidth) { 
        $ratio = $h / $w; 
        $newW = $maxWidth; 
        $newH = (int)round($newW * $ratio); 
        $res = imagecreatetruecolor($newW, $newH); 
        
        if ($mime !== 'image/jpeg') { imagealphablending($res, false); imagesavealpha($res, true); } 
        
        imagecopyresampled($res, $img, 0,0,0,0, $newW,$newH,$w,$h); 
        imagedestroy($img); 
        $img = $res; 
    }
    
    // Save image to destination path
    $ok = false;
    if ($mime === 'image/jpeg') { $ok = imagejpeg($img, $dest, $quality); }
    elseif ($mime === 'image/png') { $pngc = round((100 - $quality) / 100 * 9); $ok = imagepng($img, $dest, $pngc); }
    elseif ($mime === 'image/gif') { $ok = imagegif($img, $dest); }
    elseif ($mime === 'image/webp' && function_exists('imagewebp')) { $ok = imagewebp($img, $dest, $quality); }
    
    imagedestroy($img);
    return $ok;
}

// --- 3. INPUT VALIDATION & PATH CONSTRUCTION ---

$series = $_POST['series'] ?? '';
$type = $_POST['type'] ?? '';
$chapterNum = $_POST['chapterNum'] ?? '';

$safeType = sanitizeName($type);
$safeSeries = sanitizeName($series);
$safeChapter = 'Chapter_' . sanitizeName($chapterNum);

$chapterDir = $baseDir . $safeType . '/' . $safeSeries . '/' . $safeChapter . '/';
$metaPath = $chapterDir . 'metadata.json';

// Ensure target chapter directory exists
if (!is_dir($chapterDir)) { 
    logError("Chapter directory not found: " . $chapterDir);
    echo json_encode(['status'=>'error','message'=>'Chapter directory not found.']); 
    exit; 
}

// Check file integrity
if (!isset($_FILES['file'])) { echo json_encode(['status'=>'error','message'=>'No file data received.']); exit; }
$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) { 
    logError("File upload error code: " . $f['error']);
    echo json_encode(['status'=>'error','message'=>'Upload failed (code: ' . $f['error'] . ').']); 
    exit; 
}

// Check MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($f['tmp_name']);
if (!isset($allowedMimes[$mime])) { 
    logError("Invalid MIME type received: " . $mime);
    echo json_encode(['status'=>'error','message'=>'Invalid image type.']); 
    exit; 
}
$ext = $allowedMimes[$mime];
$dest = $chapterDir . 'cover.' . $ext;
$finalRelativePath = $safeType . '/' . $safeSeries . '/' . $safeChapter . '/cover.' . $ext;

// --- 4. FILE SYSTEM OPERATIONS (Delete old covers & Save new) ---

// 4a. Delete old cover files (to prevent accumulation and path confusion)
foreach (array_values($allowedMimes) as $oldExt) { 
    $p = $chapterDir . 'cover.' . $oldExt; 
    if (is_file($p) && $p !== $dest) { @unlink($p); } 
}

// 4b. Resize and save the new file
$ok = resizeSave($f['tmp_name'], $mime, $dest, 800, 85);

if (!$ok) { 
    logError("Resizing/Save failed for destination: " . $dest);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save processed image.']); 
    exit; 
}

// Set permissions after successful save
@chmod($dest, 0644);

// --- 5. METADATA & DB UPDATE ---

// 5a. Update metadata.json
$meta = [];
if (file_exists($metaPath)) { 
    $metaContent = @file_get_contents($metaPath);
    $meta = json_decode($metaContent, true) ?: []; 
}

if (!isset($meta['assets'])) $meta['assets'] = [];
$meta['assets']['thumbnail'] = $finalRelativePath;
$meta['assets']['thumbnail_base64'] = null; // Clear any old Base64 reference

if (!@file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT))) {
    logError("Failed to write metadata to: " . $metaPath);
}

// 5b. Update Database
$thumbUrl = getWebUrl($finalRelativePath); // CORRECTED: getWebUrl function is now defined

if (file_exists(__DIR__ . '/db/sqlite.php')) { require_once __DIR__ . '/db/sqlite.php'; }

try {
    if (function_exists('db')) {
        $pdo = db();
        if ($pdo) {
            $creatorId = $_SESSION['user_id'] ?? ($_SESSION['username'] ?? '');
            
            // Find Manga ID
            $stmt = $pdo->prepare('SELECT id FROM manga_metadata WHERE creator_id = ? AND title = ?');
            $stmt->execute([$creatorId, $series]);
            $mangaId = (int)($stmt->fetchColumn() ?: 0);
            
            if ($mangaId) {
                // Find Chapter ID
                $stmtC = $pdo->prepare('SELECT id FROM chapters WHERE manga_id = ? AND chapter_number = ?');
                $stmtC->execute([$mangaId, (int)$chapterNum]);
                $cid = (int)($stmtC->fetchColumn() ?: 0);
                
                if ($cid) {
                    // Update the chapter's thumbnail path
                    $pdo->prepare('UPDATE chapters SET thumbnail_path = ? WHERE id = ?')
                        ->execute([$finalRelativePath, $cid]);
                }
            }
        }
    }
} catch (Throwable $e) { 
    logError("DB Update Failed: " . $e->getMessage()); 
}

// --- 6. FINAL SUCCESS RESPONSE ---
echo json_encode(['status'=>'success','thumbnail'=>$thumbUrl]);
?>
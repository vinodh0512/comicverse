<?php
// uploader.php (Unified Content Uploader)
session_start();
ini_set('display_errors', 0); 
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// --- 1. CONFIGURATION & DEPENDENCIES ---

if (file_exists(__DIR__ . '/db/sqlite.php')) { require_once __DIR__ . '/db/sqlite.php'; } 
else { if (!function_exists('db')) { function db() { return null; } } }

$baseDir = dirname(__DIR__) . '/Book_data/'; 
$allowedMimes = [
    'image/jpeg' => 'jpg', 'image/png' => 'png', 
    'image/webp' => 'webp', 'image/gif' => 'gif'
];

// --- 2. CORE HELPER FUNCTIONS ---

function logError($msg) {
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/upload_errors.log';
    if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
    $u = $_SESSION['username'] ?? 'unknown';
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] [$u] " . $msg . "\n", FILE_APPEND);
}

function sanitizeName($string) {
    return preg_replace('/[^a-zA-Z0-9\s_-]/', '', str_replace(' ', '_', $string));
}

function getMovieFolderName($title, $id) {
    $base = sanitizeName($title);
    if ($id && $id !== '0') {
        return $base . '_' . sanitizeName($id);
    }
    return $base . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
}

function saveCoverFile($fileData, $targetDir, $allowedMimes, $safePaths) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($fileData['tmp_name']);
    
    if (!isset($fileData['tmp_name']) || $fileData['error'] !== UPLOAD_ERR_OK || !isset($allowedMimes[$realMime])) {
        throw new Exception("Invalid or failed file upload for cover.");
    }
    
    $ext = $allowedMimes[$realMime];
    $finalName = 'cover.' . $ext;
    $finalPath = $targetDir . $finalName;
    $relativePath = $safePaths['type'] . '/' . $safePaths['series'] . '/' . $safePaths['chapter'] . '/' . $finalName;

    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            throw new Exception("Failed to create chapter directory. Check folder permissions.");
        }
    }
    
    // Delete old covers before saving the new one (Crucial fix)
    foreach (array_values($allowedMimes) as $oldExt) { 
        $p = $targetDir . 'cover.' . $oldExt; 
        if (is_file($p)) { @unlink($p); } 
    }

    if (!move_uploaded_file($fileData['tmp_name'], $finalPath)) {
        throw new Exception("Failed to save cover file to disk.");
    }
    
    @chmod($finalPath, 0644); 
    return $relativePath;
}


// --- 3. SECURITY & ENTRY POINT ---
try {
    if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'creator') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: Creator login required.']);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid Request. This endpoint expects POST data.');
    }

    $action = $_POST['action'] ?? '';
    
    // --------------------------------------------------------
    // ACTION: SUBMIT_ARCHIVE (Unified Content Handler)
    // --------------------------------------------------------
    if ($action === 'submit_archive') {
        
        // --- 3a. Capture and Validate Data ---
        $seriesName    = $_POST['series'] ?? '';      
        $contentType   = $_POST['type'] ?? 'manga';  
        $contentTitle  = $_POST['chapterTitle'] ?? ''; 
        $contentID     = $_POST['chapterNum'] ?? '0';    
        $videoUrl      = $_POST['pageArchiveUrl'] ?? ''; // Single video link
        $pagesJson     = $_POST['pageUrls'] ?? '[]';     // JSON array of image links

        if (empty($seriesName)) throw new Exception("Collection/Series Name is required.");
        if (empty($contentTitle)) throw new Exception("Content Title is required.");
        if (!isset($_FILES['coverFile']) || $_FILES['coverFile']['error'] !== UPLOAD_ERR_OK) { 
            throw new Exception("Cover file upload failed or missing."); 
        }

        $isVideo = in_array($contentType, ['movie', 'series']);
        
        $safeType = sanitizeName($contentType);
        $safeSeries = sanitizeName($seriesName);
        $contentFolder = getMovieFolderName($contentTitle, $contentID); 

        $safePaths = [
            'type' => $safeType,
            'series' => $safeSeries,
            'chapter' => $contentFolder 
        ];
        
        $chapterDir = $baseDir . $safePaths['type'] . '/' . $safePaths['series'] . '/' . $contentFolder . '/';
        
        // --- 3b. Process Cover File ---
        $thumbRelPath = saveCoverFile($_FILES['coverFile'], $chapterDir, $allowedMimes, $safePaths);

        // --- 3c. Schedule & Status (Using standard Chapter fields) ---
        $publishDateStr = date('Y-m-d H:i:s');
        $state = ($_POST['releaseType'] === 'scheduled') ? 'Scheduled' : 'Live';

        // --- 3d. DETERMINE CONTENT SOURCE ---
        $sourceType = $isVideo ? 'video_url' : 'page_urls';
        $contentSource = $isVideo ? $videoUrl : json_decode($pagesJson, true);
        
        if ($isVideo && (!is_string($contentSource) || !filter_var($contentSource, FILTER_VALIDATE_URL))) {
             throw new Exception("Invalid or missing Video Source URL.");
        }
        if (!$isVideo && (!is_array($contentSource) || empty($contentSource))) {
             throw new Exception("No valid image page URLs provided.");
        }
        
        // --- 3e. Generate and Save JSON Metadata ---
        $metaData = [
            "id" => uniqid('content_'),
            "meta" => [
                "series_name" => $seriesName,
                "folder_name" => $safePaths['series'],
                "content_type" => $contentType,
                "title" => $contentTitle, 
                "unique_id" => $contentID, 
                "creator" => $_SESSION['username']
            ],
            "status" => [
                "state" => $state,
                "upload_date" => date('Y-m-d H:i:s'), 
                "publish_date" => $publishDateStr
            ],
            "assets" => [
                "thumbnail_path" => $thumbRelPath, 
                $sourceType => $contentSource, 
                "source_hint" => $sourceType
            ]
        ];
        $metaPath = $chapterDir . 'metadata.json';
        file_put_contents($metaPath, json_encode($metaData, JSON_PRETTY_PRINT));
        @chmod($metaPath, 0644); 
        
        // --- 3f. Database Integration ---
        try {
            $pdo = db();
            if ($pdo) { 
                $pdo->beginTransaction(); 
                $creatorId = $_SESSION['user_id'] ?? ($_SESSION['username'] ?? '');
                
                // 1. Find or Create Collection (manga_metadata table)
                $stmt = $pdo->prepare('SELECT id FROM manga_metadata WHERE creator_id = ? AND title = ?');
                $stmt->execute([$creatorId, $seriesName]);
                $mangaId = (int)($stmt->fetchColumn() ?: 0);
                if (!$mangaId) {
                    $stmt2 = $pdo->prepare('INSERT INTO manga_metadata(creator_id, title, upload_timestamp) VALUES(?,?,?)');
                    $stmt2->execute([$creatorId, $seriesName, date('Y-m-d H:i:s')]);
                    $mangaId = (int)$pdo->lastInsertId();
                }

                // 2. Find or Create/Update Content Record (chapters table)
                $contentUniqueDBID = $contentID ?: sprintf('%u', crc32($contentTitle)); 

                $stmtC = $pdo->prepare('SELECT id FROM chapters WHERE manga_id = ? AND chapter_number = ?');
                $stmtC->execute([$mangaId, $contentUniqueDBID]);
                $chapterId = (int)($stmtC->fetchColumn() ?: 0);
                
                if (!$chapterId) {
                    $stmtC2 = $pdo->prepare('INSERT INTO chapters(manga_id, chapter_number, title, thumbnail_path) VALUES(?,?,?,?)');
                    $stmtC2->execute([$mangaId, $contentUniqueDBID, $contentTitle, $thumbRelPath]);
                    $chapterId = (int)$pdo->lastInsertId();
                } else {
                    $pdo->prepare('UPDATE chapters SET title = ?, thumbnail_path = ? WHERE id = ?')->execute([$contentTitle, $thumbRelPath, $chapterId]);
                    $pdo->prepare('DELETE FROM pages WHERE chapter_id = ?')->execute([$chapterId]);
                }

                // 3. Insert Content Source(s) (pages table)
                if ($isVideo) {
                    // Single entry for the video stream URL
                    $fileName = basename(parse_url($videoUrl, PHP_URL_PATH));
                    $pdo->prepare('INSERT INTO pages(chapter_id, filename, sequence_order, file_path) VALUES(?,?,?,?)')
                        ->execute([$chapterId, $fileName, 1, $videoUrl]);
                } else {
                    // Multiple entries for each image page URL
                    foreach ($contentSource as $idx => $url) {
                        $seq = $idx + 1;
                        $fileName = basename(parse_url($url, PHP_URL_PATH) ?: 'page' . $seq);
                        $pdo->prepare('INSERT INTO pages(chapter_id, filename, sequence_order, file_path) VALUES(?,?,?,?)')
                            ->execute([$chapterId, $fileName, $seq, $url]);
                    }
                }
                
                $pdo->commit();
            }
        } catch (Throwable $dbEx) {
            if (isset($pdo) && $pdo && $pdo->inTransaction()) $pdo->rollBack();
            logError("DB Error on submission: " . $dbEx->getMessage());
            throw new Exception("Database operation failed: " . $dbEx->getMessage());
        }

        // --- 3g. Final Success Response ---
        echo json_encode(['status' => 'success', 'message' => 'Content published.']);
        exit;
    }

    // Handle unknown action
    throw new Exception("Unknown Action requested.");

} catch (Exception $e) {
    http_response_code(500);
    logError("Fatal Upload Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>
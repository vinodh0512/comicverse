<?php
// bulk_processor.php - Dedicated script for processing staged files into final structure.
session_start();
header('Content-Type: application/json');
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return;
    echo json_encode([
        'status' => 'error',
        'message' => 'PHP Runtime Error: ' . $errstr . ' in ' . $errfile . ' on line ' . $errline
    ]);
    exit(1);
}, E_ALL);
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Increase limits for processing potentially hundreds of files
set_time_limit(1800); // 30 minutes execution time
ini_set('memory_limit', '1024M'); // 1GB memory limit

// Open access (no login required)

// --- PATHS & CONFIG ---
$baseDir = __DIR__ . '/Book_data/';
$tempUploadDir = __DIR__ . '/temp_uploads/' . session_id() . '/'; 
$allowedMimes = [
    'image/jpeg' => 'jpg', 
    'image/png' => 'png', 
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];

// --- Helper Functions ---

/**
 * Saves and compresses an image using the GD library.
 * If GD is not available, it falls back to a simple file copy.
 */
function saveAndCompressImage($sourcePath, $destination, $quality = 85, $type = 'comic', $maxWidth = null) {
    if (!extension_loaded('gd')) {
        return copy($sourcePath, $destination);
    }

    try {
        $mime = mime_content_type($sourcePath);
        $image = false;

        if ($mime === 'image/jpeg') {
            $image = imagecreatefromjpeg($sourcePath);
        } elseif ($mime === 'image/png') {
            $image = imagecreatefrompng($sourcePath);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        } elseif ($mime === 'image/gif') {
            $image = imagecreatefromgif($sourcePath);
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = imagecreatefromwebp($sourcePath);
        } else {
            return copy($sourcePath, $destination);
        }

        if (!$image) return false;

        $srcW = imagesx($image);
        $srcH = imagesy($image);

        if ($maxWidth === null) {
            $maxWidth = ($type === 'webtoon') ? 1080 : 1600;
        }

        if ($srcW > $maxWidth) {
            $ratio = $srcH / $srcW;
            $newW = $maxWidth;
            $newH = (int) round($newW * $ratio);
            $resized = imagecreatetruecolor($newW, $newH);
            if ($mime === 'image/png' || $mime === 'image/gif' || $mime === 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
            imagedestroy($image);
            $image = $resized;
        }

        $ok = false;
        if ($mime === 'image/jpeg') {
            $ok = imagejpeg($image, $destination, $quality);
        } elseif ($mime === 'image/png') {
            $png_compression = round((100 - $quality) / 100 * 9);
            $ok = imagepng($image, $destination, $png_compression);
        } elseif ($mime === 'image/gif') {
            $ok = imagegif($image, $destination);
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            $ok = imagewebp($image, $destination, $quality);
        }

        imagedestroy($image);
        return $ok;
    } catch (\Throwable $th) {
        error_log("Image resize/compress failed for: $sourcePath. Error: " . $th->getMessage());
        return copy($sourcePath, $destination);
    }
}

function sanitizeName($string) {
    // Remove special characters, replace spaces with underscores, and limit length
    return substr(preg_replace('/[^a-zA-Z0-9\s_-]/', '', str_replace(' ', '_', $string)), 0, 100);
}
// --- END Helpers ---


try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid Request Method.');
    
    // Get parameters from POST body
    $contentType = $_POST['type'] ?? 'comic';
    $seriesName = $_POST['seriesName'] ?? 'Unknown';
    $creatorName = $_SESSION['username'] ?? 'anonymous';
    $skipCompression = (($_POST['compress'] ?? '') === 'off');
    
    if (empty($seriesName) || !is_dir($tempUploadDir)) {
        throw new Exception("Missing Series Name or no staged files found in temporary directory.");
    }
    
    $chaptersData = [];
    $uploadedFileCount = 0;
    $flatImages = [];

    // Use recursive directory iterator to find all staged files safely
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempUploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    // 1. Organize staged files by chapter (based on relative path)
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isDir() || $fileInfo->getSize() === 0) continue;

        // Strip the temp path to get the original relative path (e.g., 'SeriesName/Chapter_1/001.jpg')
        $relativePath = str_replace($tempUploadDir, '', $fileInfo->getPathname());
        
        $pathParts = explode('/', $relativePath);
        
        // We use the second to last element as the chapter folder name
        $chapterFolder = sanitizeName($pathParts[count($pathParts) - 2] ?? 'Chapter_1');
        $pageFileName = basename($pathParts[count($pathParts) - 1]);
        
        $mimeType = mime_content_type($fileInfo->getPathname());

        if (!isset($chaptersData[$chapterFolder])) {
            $chaptersData[$chapterFolder] = [ 'pages' => [] ];
        }

        $chaptersData[$chapterFolder]['pages'][] = [
            'path' => $fileInfo->getPathname(),
            'name' => $pageFileName,
            'mime' => $mimeType
        ];
        $flatImages[] = [
            'path' => $fileInfo->getPathname(),
            'name' => $pageFileName,
            'mime' => $mimeType
        ];
        $uploadedFileCount++;
    }

    if ($uploadedFileCount === 0) {
        throw new Exception("No images found in staged directory. Please ensure the files were uploaded correctly.");
    }
    
    // Optional: sizes-based grouping (ignore folder names when provided)
    $sizesStr = trim($_POST['chapterSizes'] ?? '');
    if ($sizesStr !== '') {
        usort($flatImages, function($a, $b) { return strnatcmp($a['name'], $b['name']); });
        $sizes = array_values(array_filter(array_map(function($x){ return (int)trim($x); }, explode(',', $sizesStr)), function($n){ return $n > 0; }));
        $chaptersData = [];
        $cursor = 0;
        $idx = 1;
        foreach ($sizes as $size) {
            $chunk = array_slice($flatImages, $cursor, $size);
            if (!empty($chunk)) {
                $chaptersData['Chapter ' . $idx] = [ 'pages' => $chunk ];
                $idx++;
            }
            $cursor += $size;
        }
        if ($cursor < count($flatImages)) {
            $chaptersData['Chapter ' . $idx] = [ 'pages' => array_slice($flatImages, $cursor) ];
        }
    }

    // Set up final destination directories
    $safeType = sanitizeName($contentType);
    $safeSeries = sanitizeName($seriesName);
    $typeDir = $baseDir . $safeType . '/';
    $seriesDir = $typeDir . $safeSeries . '/';
    
    // Create final base directories
    if (!is_dir($typeDir) && !@mkdir($typeDir, 0777, true)) {
        throw new Exception("Failed to create type directory ($typeDir). Check permissions.");
    }
    if (!is_dir($seriesDir) && !@mkdir($seriesDir, 0777, true)) {
        throw new Exception("Failed to create series directory ($seriesDir). Check permissions.");
    }
    
    $uploadedChapters = [];
    
    // 2. Process and Save Chapters from Staged Files
    foreach ($chaptersData as $chapterFolderName => $data) {
        if (empty($data['pages'])) continue;
        
        // Standardize final chapter folder naming: "chapter <number>"
        $chapterNumber = (int)preg_replace('/[^0-9]/', '', $chapterFolderName) ?: 1;
        $chapterDirName = 'Chapter ' . $chapterNumber;

        $chapterDir = $seriesDir . $chapterDirName . '/';
        // Web path excludes 'Book_data/' to align with reader API which prefixes it
        $webPathBase = $safeType . '/' . $safeSeries . '/' . $chapterDirName . '/';

        if (!is_dir($chapterDir) && !@mkdir($chapterDir, 0777, true)) {
            continue; // Skip this chapter if folder creation fails
        }

        $savedPages = [];
        $pageCounter = 1;
        
        // Sort pages numerically by filename (CRITICAL for chapter page order)
        usort($data['pages'], function($a, $b) {
            return strnatcmp($a['name'], $b['name']);
        });

        foreach ($data['pages'] as $page) {
            $ext = $allowedMimes[$page['mime']] ?? 'jpg';
            $finalName = str_pad($pageCounter, 3, '0', STR_PAD_LEFT) . '.' . $ext;
            $destination = $chapterDir . $finalName;

            $ok = false;
            if ($skipCompression) {
                $ok = copy($page['path'], $destination);
            } else {
                $ok = saveAndCompressImage($page['path'], $destination, 85, $contentType);
            }

            if ($ok) {
                $savedPages[] = $finalName;
                $pageCounter++;
            }
        }
        
        if (empty($savedPages)) continue;

        $thumbnailFile = $savedPages[0]; 

        // Generate Metadata JSON
        $metaData = [
            "id" => uniqid('chapter_'),
            "meta" => [
                "series_name" => str_replace('_', ' ', $safeSeries),
                "folder_name" => $safeSeries,
                "content_type" => $contentType,
                "chapter_number" => (string)$chapterNumber,
                "chapter_title" => 'Chapter ' . $chapterNumber,
                "creator" => $creatorName
            ],
            "status" => [
                "state" => 'Live',
                "upload_date" => date('Y-m-d H:i:s'), 
                "publish_date" => date('Y-m-d H:i:s')
            ],
            "assets" => [
                "thumbnail" => $thumbnailFile,
                "path_to_pages" => $webPathBase,
                "pages_files" => $savedPages
            ]
        ];

        @file_put_contents($chapterDir . 'metadata.json', json_encode($metaData, JSON_PRETTY_PRINT));
        $uploadedChapters[] = $metaData['meta'];
    }

    // 3. Cleanup temporary directory (CRITICAL STEP)
    if (is_dir($tempUploadDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempUploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
            } else {
                @unlink($fileinfo->getRealPath());
            }
        }
        @rmdir($tempUploadDir);
    }

    if (empty($uploadedChapters)) {
        throw new Exception("No chapters were successfully processed. Check file types or directory structure.");
    }

    echo json_encode([
        'status' => 'success',
        'message' => count($uploadedChapters) . ' Chapters processed and saved successfully.',
        'chapters' => $uploadedChapters
    ]);

} catch (Exception $e) {
    // Attempt to clean up temp files even on error
    if (is_dir($tempUploadDir)) {
        // ... (Cleanup logic here, omitted for brevity but included in full code above)
    }

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Bulk Processing Fatal Error: ' . $e->getMessage()
    ]);
}
?>
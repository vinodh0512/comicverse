<?php
// sequential_uploader.php - Handles single file uploads to a temporary staging area.
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0); // Suppress errors in JSON output

$tempUploadDir = __DIR__ . '/temp_uploads/' . session_id() . '/';
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method.');
    if (!isset($_FILES['file'])) throw new Exception('No file received.');

    // Ensure the temporary directory exists
    if (!is_dir($tempUploadDir)) {
        // Use recursive creation with suppressed error reporting for cleaner output
        if (!@mkdir($tempUploadDir, 0777, true)) {
            throw new Exception("Failed to create temp upload directory. Check permissions for /temp_uploads/.");
        }
    }

    $file = $_FILES['file'];
    // Use the relative path sent from the client (e.g., SeriesName/Chapter_1/001.jpg)
    $relativePath = $_POST['relativePath'] ?? basename($file['name']);

    // Validate MIME type
    if (!in_array($file['type'], $allowedMimes)) {
        throw new Exception("Invalid file type: " . $file['type']);
    }

    // Determine the full target path based on the relative folder structure
    $targetPath = $tempUploadDir . $relativePath;
    $targetDir = dirname($targetPath);

    // Create necessary subdirectories within the temporary staging area
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }
    
    // Move the uploaded file from PHP's system temp location to our session temp location
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to save file temporarily. Upload error code: " . $file['error']);
    }
    
    echo json_encode(['status' => 'success', 'path' => $relativePath]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Upload error: ' . $e->getMessage()]);
}
?>
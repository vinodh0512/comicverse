<?php
require_once __DIR__ . '/db/sqlite.php';
session_start();
$series = trim($_POST['series'] ?? '');
$type = trim($_POST['type'] ?? '');
$chapter = intval($_POST['chapter'] ?? 0);
$uid = $_SESSION['user_id'] ?? null;
try {
    $pdo = db();
    if ($pdo && $series && $type && $chapter > 0) {
        $stmt = $pdo->prepare('INSERT INTO views(user_id, series, type, chapter, read_at) VALUES(?,?,?,?,?)');
        $stmt->execute([$uid, $series, $type, $chapter, date('Y-m-d H:i:s')]);
        echo json_encode(['status'=>'success']);
        exit;
    }
} catch (Throwable $e) {}
echo json_encode(['status'=>'error']);
?>

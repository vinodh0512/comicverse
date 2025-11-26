<?php
$dataDir = dirname(__DIR__) . '/data';
if (!is_dir($dataDir)) { mkdir($dataDir, 0777, true); }
$dbPath = $dataDir . '/comicverse.sqlite';
function db() {
    global $dbPath;
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('CREATE TABLE IF NOT EXISTS manga_metadata (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        creator_id TEXT NOT NULL,
        title TEXT NOT NULL,
        upload_timestamp TEXT NOT NULL,
        UNIQUE(creator_id, title)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS chapters (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        manga_id INTEGER NOT NULL,
        chapter_number INTEGER NOT NULL,
        title TEXT,
        thumbnail_path TEXT,
        FOREIGN KEY(manga_id) REFERENCES manga_metadata(id) ON DELETE CASCADE,
        UNIQUE(manga_id, chapter_number)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chapter_id INTEGER NOT NULL,
        filename TEXT NOT NULL,
        sequence_order INTEGER NOT NULL,
        file_path TEXT NOT NULL,
        FOREIGN KEY(chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
        UNIQUE(chapter_id, sequence_order)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS views (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT,
        series TEXT,
        type TEXT,
        chapter INTEGER,
        read_at TEXT
    )');
    // Ensure chapters has thumbnail_path (for existing DBs)
    try {
        $cols = $pdo->query('PRAGMA table_info(chapters)')->fetchAll(PDO::FETCH_ASSOC);
        $hasThumb = false;
        foreach ($cols as $c) { if (($c['name'] ?? '') === 'thumbnail_path') { $hasThumb = true; break; } }
        if (!$hasThumb) { $pdo->exec('ALTER TABLE chapters ADD COLUMN thumbnail_path TEXT'); }
    } catch (Throwable $e) {}
    return $pdo;
}
function getCreatorEmail() {
    $dataDir = dirname(__DIR__) . '/user_data/';
    $jsonFile = $dataDir . 'creators.json';
    if (!file_exists($jsonFile)) return null;
    $arr = json_decode(file_get_contents($jsonFile), true) ?: [];
    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid) return null;
    foreach ($arr as $c) { if (($c['id'] ?? '') === $uid) return $c['email'] ?? null; }
    return null;
}
?>

<?php
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

$dataDir = __DIR__ . '/user_data/';
$jsonFile = $dataDir . 'lists.json';

if (!is_dir($dataDir)) { mkdir($dataDir, 0777, true); }
if (!file_exists($jsonFile)) { file_put_contents($jsonFile, json_encode([])); }

$username = $_SESSION['username'];

function load_lists($file) {
    try { $raw = file_get_contents($file); $json = json_decode($raw, true); return is_array($json) ? $json : []; } catch (Throwable $e) { return []; }
}

function save_lists($file, $data) {
    try { file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)); return true; } catch (Throwable $e) { return false; }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

$lists = load_lists($jsonFile);
if (!isset($lists[$username])) { $lists[$username] = []; }

if ($method === 'POST' && $action === 'add') {
    $series = basename($_POST['series'] ?? '');
    $type = basename($_POST['type'] ?? '');
    $title = trim($_POST['title'] ?? '');
    if (!$series || !$type) { echo json_encode(['status' => 'error', 'message' => 'Missing fields']); exit; }

    foreach ($lists[$username] as $item) { if ($item['series'] === $series && $item['type'] === $type) { echo json_encode(['status' => 'success', 'message' => 'Already added']); exit; } }

    $lists[$username][] = [
        'series' => $series,
        'type' => $type,
        'title' => $title ?: str_replace('_', ' ', $series),
        'added_at' => date('Y-m-d H:i:s')
    ];

    if (save_lists($jsonFile, $lists)) { echo json_encode(['status' => 'success']); } else { echo json_encode(['status' => 'error', 'message' => 'Save failed']); }
    exit;
}

if ($method === 'POST' && $action === 'remove') {
    $series = basename($_POST['series'] ?? '');
    $type = basename($_POST['type'] ?? '');
    $lists[$username] = array_values(array_filter($lists[$username], function($item) use ($series, $type) { return !($item['series'] === $series && $item['type'] === $type); }));
    if (save_lists($jsonFile, $lists)) { echo json_encode(['status' => 'success']); } else { echo json_encode(['status' => 'error', 'message' => 'Save failed']); }
    exit;
}

if ($method === 'GET' && $action === 'get') {
    echo json_encode(['status' => 'success', 'list' => $lists[$username]]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>

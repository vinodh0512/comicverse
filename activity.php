<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
if (!isset($_SESSION['username'])) { echo json_encode(['status'=>'error','message'=>'Login required']); exit; }
$dataDir = __DIR__ . '/user_data/';
$jsonFile = $dataDir . 'activity.json';
if (!is_dir($dataDir)) { mkdir($dataDir, 0777, true); }
if (!file_exists($jsonFile)) { file_put_contents($jsonFile, json_encode([])); }
$u = $_SESSION['username'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';
$all = json_decode(file_get_contents($jsonFile), true);
if (!is_array($all)) { $all = []; }
if (!isset($all[$u])) { $all[$u] = []; }
if ($method === 'POST' && $action === 'log') {
    $type = trim($_POST['type'] ?? '');
    $series = basename($_POST['series'] ?? '');
    $contentType = basename($_POST['content_type'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $chapter = trim($_POST['chapter'] ?? '');
    if (!$type) { echo json_encode(['status'=>'error','message'=>'Missing type']); exit; }
    $all[$u][] = ['type'=>$type,'series'=>$series,'content_type'=>$contentType,'title'=>$title,'chapter'=>$chapter,'time'=>date('Y-m-d H:i:s')];
    file_put_contents($jsonFile, json_encode($all, JSON_PRETTY_PRINT));
    echo json_encode(['status'=>'success']);
    exit;
}
if ($method === 'GET' && $action === 'get') {
    $list = $all[$u];
    usort($list, function($a,$b){ return strtotime($b['time']??'') - strtotime($a['time']??''); });
    echo json_encode(['status'=>'success','activities'=>$list]);
    exit;
}
echo json_encode(['status'=>'error','message'=>'Invalid request']);
?>

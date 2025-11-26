<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') { echo json_encode(['status'=>'error','message'=>'Invalid method']); exit; }
$userId = $_POST['user_id'] ?? '';
$email = $_POST['email'] ?? '';
$reason = trim($_POST['reason'] ?? '');

$usersPath = __DIR__ . '/../../user_data/users.json';
$users = [];
if (file_exists($usersPath)) { $users = json_decode(file_get_contents($usersPath), true) ?: []; }
$found = false;
foreach ($users as &$u) {
    $match = (($u['id'] ?? '') === $userId) || (($u['email'] ?? '') === $email && $email !== '');
    if ($match) {
        $u['role'] = 'reader';
        $found = true;
        break;
    }
}
if (!$found) { echo json_encode(['status'=>'error','message'=>'User not found']); exit; }
file_put_contents($usersPath, json_encode($users, JSON_PRETTY_PRINT));

$cdir = __DIR__ . '/../../creator/user_data/';
if (!is_dir($cdir)) { mkdir($cdir, 0777, true); }
$cfile = $cdir . 'creators.json';
if (file_exists($cfile)) {
    $creators = json_decode(file_get_contents($cfile), true) ?: [];
    $changed = false;
    foreach ($creators as $i => $c) {
        if (($c['id'] ?? '') === $userId || (($c['email'] ?? '') === $email && $email !== '')) { unset($creators[$i]); $changed = true; }
    }
    if ($changed) { file_put_contents($cfile, json_encode(array_values($creators), JSON_PRETTY_PRINT)); }
}

$appFile = $cdir . 'applications.json';
if (file_exists($appFile)) {
    $apps = json_decode(file_get_contents($appFile), true) ?: [];
    $changed = false;
    foreach ($apps as &$a) {
        if (($a['user_id'] ?? '') === $userId || (($a['email'] ?? '') === $email && $email !== '')) {
            $a['status'] = 'rejected';
            $a['rejected_at'] = date('Y-m-d H:i:s');
            if ($reason !== '') { $a['reason'] = $reason; }
            $changed = true;
        }
    }
    if ($changed) { file_put_contents($appFile, json_encode($apps, JSON_PRETTY_PRINT)); }
}

echo json_encode(['status'=>'success']);
?>

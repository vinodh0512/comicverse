<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') { echo json_encode(['status'=>'error','message'=>'Invalid method']); exit; }
$userId = $_POST['user_id'] ?? '';
$email = $_POST['email'] ?? '';
$path = __DIR__ . '/../../user_data/users.json';
$users = [];
if (file_exists($path)) { $users = json_decode(file_get_contents($path), true) ?: []; }
$found = false;
foreach ($users as &$u) {
    if (($u['id'] ?? '') === $userId || (($u['email'] ?? '') === $email && $email !== '')) {
        $u['role'] = 'creator';
        $found = true;
        break;
    }
}
if (!$found) { echo json_encode(['status'=>'error','message'=>'User not found']); exit; }
file_put_contents($path, json_encode($users, JSON_PRETTY_PRINT));
$cdir = __DIR__ . '/../../creator/user_data/';
if (!is_dir($cdir)) { mkdir($cdir, 0777, true); }
$cfile = $cdir . 'creators.json';
$creators = [];
if (file_exists($cfile)) { $creators = json_decode(file_get_contents($cfile), true) ?: []; }
$exists = false;
foreach ($creators as $c) { if (($c['email'] ?? '') === ($email ?: '')) { $exists = true; break; } }
if (!$exists) {
    $target = null;
    foreach ($users as $u) { if (($u['id'] ?? '') === $userId || (($u['email'] ?? '') === $email && $email !== '')) { $target = $u; break; } }
    if ($target) {
        $creators[] = [
            'id' => $target['id'],
            'username' => $target['username'],
            'email' => $target['email'],
            'password' => $target['password'],
            'joined' => date('Y-m-d H:i:s')
        ];
        file_put_contents($cfile, json_encode($creators, JSON_PRETTY_PRINT));
    }
}
// Update application status to approved
$appFile = __DIR__ . '/../../creator/user_data/applications.json';
if (file_exists($appFile)) {
    $apps = json_decode(file_get_contents($appFile), true) ?: [];
    $changed = false;
    foreach ($apps as &$a) {
        if (($a['user_id'] ?? '') === $userId || (($a['email'] ?? '') === $email && $email !== '')) {
            $a['status'] = 'approved';
            $a['approved_at'] = date('Y-m-d H:i:s');
            $changed = true;
        }
    }
    if ($changed) { file_put_contents($appFile, json_encode($apps, JSON_PRETTY_PRINT)); }
}
echo json_encode(['status'=>'success']);
?>

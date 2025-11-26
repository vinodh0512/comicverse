<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
$uid = $_POST['user_id'] ?? $_GET['user_id'] ?? '';
$email = $_POST['email'] ?? $_GET['email'] ?? '';
$usersPath = __DIR__ . '/../../user_data/users.json';
$appsPath = __DIR__ . '/../../creator/user_data/applications.json';
$user = null; $app = null;
try {
    if (file_exists($usersPath)) {
        $users = json_decode(file_get_contents($usersPath), true) ?: [];
        foreach ($users as $u) { if (($u['id'] ?? '') === $uid || (($u['email'] ?? '') === $email && $email !== '')) { $user = $u; break; } }
    }
    if (file_exists($appsPath)) {
        $apps = json_decode(file_get_contents($appsPath), true) ?: [];
        foreach ($apps as $a) { if (($a['user_id'] ?? '') === ($user['id'] ?? $uid) || (($a['email'] ?? '') === ($user['email'] ?? $email))) { $app = $a; break; } }
    }
} catch (Throwable $e) {}
if (!$user && !$app) { echo json_encode(['status'=>'error','message'=>'Application not found']); exit; }
echo json_encode(['status'=>'success','user'=>$user,'application'=>$app]);
?>

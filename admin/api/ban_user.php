<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') { echo json_encode(['status'=>'error','message'=>'Invalid method']); exit; }
$userId = $_POST['user_id'] ?? '';
$email = $_POST['email'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$duration = intval($_POST['duration_minutes'] ?? 0);
if ($duration <= 0) { $duration = 60; }
$until = date('Y-m-d H:i:s', time() + ($duration * 60));
$path = __DIR__ . '/../../user_data/users.json';
$users = [];
if (file_exists($path)) { $users = json_decode(file_get_contents($path), true) ?: []; }
$found = false;
foreach ($users as &$u) {
    $match = (($u['id'] ?? '') === $userId) || (($u['email'] ?? '') === $email && $email !== '');
    if ($match) {
        $u['status'] = 'banned';
        $u['ban_reason'] = $reason ?: 'Policy violation';
        $u['ban_until'] = $until;
        $found = true;
        break;
    }
}
if (!$found) { echo json_encode(['status'=>'error','message'=>'User not found']); exit; }
file_put_contents($path, json_encode($users, JSON_PRETTY_PRINT));
echo json_encode(['status'=>'success']);
?>

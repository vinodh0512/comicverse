<?php
require_once __DIR__ . '/includes/session.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$dataDir = __DIR__ . '/user_data/';
$usersFile = $dataDir . 'users.json';
if (!file_exists($usersFile)) file_put_contents($usersFile, json_encode([]));
$users = json_decode(file_get_contents($usersFile), true) ?: [];

$email = trim($_POST['email'] ?? $_GET['email'] ?? '');
if (!$email) { echo json_encode(['status'=>'error','message'=>'Email required']); exit; }

$user = null;
foreach ($users as $u) { if (($u['email'] ?? '') === $email) { $user = $u; break; } }
if (!$user) { echo json_encode(['status'=>'error','message'=>'Account not found']); exit; }

$resetDir = __DIR__ . '/user_data/reset';
if (!is_dir($resetDir)) { mkdir($resetDir, 0777, true); }
$path = $resetDir . '/' . ($user['id'] ?? '') . '.json';
$existing = file_exists($path) ? json_decode(file_get_contents($path), true) : null;
$now = time();
if ($existing) {
    $last = strtotime($existing['created_at'] ?? date('Y-m-d H:i:s', 0));
    if (($now - $last) < 60) { echo json_encode(['status'=>'wait','seconds'=> (60 - ($now - $last))]); exit; }
}

$token = bin2hex(random_bytes(16));
$payload = [
    'user_id' => $user['id'],
    'email' => $email,
    'token' => $token,
    'created_at' => date('Y-m-d H:i:s', $now),
    'expires_at' => date('Y-m-d H:i:s', $now + 1800)
];
file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT));

require_once __DIR__ . '/includes/mailer_config.php';
$subject = 'ComicVerse Password Reset';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/comicverse'), '/\\');
$link = $scheme . '://' . $host . $base . '/reset_password.php?token=' . urlencode($token) . '&email=' . urlencode($email);
$html = '<h2>Password Reset</h2><p>Click the link to reset your password:</p><p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p><p>This link expires in 30 minutes.</p>';
$res = send_mail($email, $subject, $html);
if (!($res['ok'] ?? false)) { echo json_encode(['status'=>'error','message'=>$res['error'] ?? 'send failed']); exit; }
echo json_encode(['status'=>'success']);
?>

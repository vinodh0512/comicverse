<?php
require_once __DIR__ . '/includes/session.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$dataDir = __DIR__ . '/user_data/';
$usersFile = $dataDir . 'users.json';
if (!file_exists($usersFile)) file_put_contents($usersFile, json_encode([]));
$users = json_decode(file_get_contents($usersFile), true) ?: [];

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$email = trim($_POST['email'] ?? $_GET['email'] ?? '');
if (!$email) { echo json_encode(['status'=>'error','message'=>'Email required']); exit; }

$user = null;
foreach ($users as $u) { if (($u['email'] ?? '') === $email) { $user = $u; break; } }
if (!$user) { echo json_encode(['status'=>'error','message'=>'Account not found']); exit; }

$otpDir = __DIR__ . '/user_data/otp';
if (!is_dir($otpDir)) { mkdir($otpDir, 0777, true); }
$otpPath = $otpDir . '/' . ($user['id'] ?? '') . '.json';
$rec = file_exists($otpPath) ? json_decode(file_get_contents($otpPath), true) : null;

if ($action === 'send') {
    $now = time();
    if ($rec) {
        $last = strtotime($rec['created_at'] ?? $rec['expires_at'] ?? date('Y-m-d H:i:s', 0));
        if (($now - $last) < 30) { echo json_encode(['status'=>'wait','seconds'=> (30 - ($now - $last))]); exit; }
    }
    $code = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
    $payload = [
        'user_id' => $user['id'],
        'email' => $email,
        'code' => $code,
        'created_at' => date('Y-m-d H:i:s', $now),
        'expires_at' => date('Y-m-d H:i:s', $now + 600)
    ];
    file_put_contents($otpPath, json_encode($payload, JSON_PRETTY_PRINT));
    require_once __DIR__ . '/includes/mailer_config.php';
    $subject = 'ComicVerse Email Verification Code';
    $html = '<h2>Your verification code</h2><p>Use this code to verify your email: <strong style="font-size:20px;">' . htmlspecialchars($code) . '</strong></p><p>This code expires in 10 minutes.</p>';
    $res = send_mail($email, $subject, $html);
    if (!($res['ok'] ?? false)) { echo json_encode(['status'=>'error','message'=>$res['error'] ?? 'send failed']); exit; }
    echo json_encode(['status'=>'success']);
    exit;
}

if ($action === 'verify') {
    $code = trim($_POST['code'] ?? $_GET['code'] ?? '');
    if (!$rec) { echo json_encode(['status'=>'error','message'=>'No active code']); exit; }
    if (($rec['code'] ?? '') !== $code) { echo json_encode(['status'=>'error','message'=>'Invalid code']); exit; }
    if (strtotime($rec['expires_at'] ?? '') < time()) { echo json_encode(['status'=>'error','message'=>'Code expired']); exit; }
    foreach ($users as &$uu) { if (($uu['id'] ?? '') === ($user['id'] ?? '')) { $uu['verified'] = true; break; } }
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    @unlink($otpPath);
    echo json_encode(['status'=>'success']);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Unknown action']);
?>

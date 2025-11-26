<?php
if (session_status() === PHP_SESSION_NONE) {
    $params = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    if (function_exists('session_set_cookie_params')) { session_set_cookie_params($params); }
    session_start();
}
if (!isset($_SESSION['last_activity'])) { $_SESSION['last_activity'] = time(); }
else { if (time() - $_SESSION['last_activity'] > 1800) { $_SESSION = []; if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']); } session_destroy(); header('Location: login.php'); exit; } $_SESSION['last_activity'] = time(); }
if (isset($_SESSION['user_id']) && !isset($_SESSION['role'])) { $_SESSION['role'] = 'reader'; }
// Sync role with users.json if it changed externally (e.g., admin approval)
if (isset($_SESSION['user_id'])) {
    try {
        $usersFile = __DIR__ . '/../user_data/users.json';
        if (file_exists($usersFile)) {
            $list = json_decode(file_get_contents($usersFile), true) ?: [];
            foreach ($list as $idx => $uu) {
                if (($uu['id'] ?? null) === ($_SESSION['user_id'] ?? null)) {
                    $newRole = $uu['role'] ?? 'reader';
                    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $newRole) { $_SESSION['role'] = $newRole; }
                    $status = $uu['status'] ?? 'active';
                    $until = $uu['ban_until'] ?? null;
                    $reason = $uu['ban_reason'] ?? '';
                    $now = time();
                    $banActive = ($status === 'banned') && ($until && strtotime($until) > $now);
                    if ($status === 'banned' && $until && strtotime($until) <= $now) {
                        $list[$idx]['status'] = 'active';
                        $list[$idx]['ban_until'] = null;
                        file_put_contents($usersFile, json_encode($list, JSON_PRETTY_PRINT));
                        $banActive = false;
                    }
                    if ($banActive) {
                        $_SESSION = [];
                        if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
                        session_destroy();
                        header('Location: ../login.php?error=' . urlencode('Account banned until ' . $until . '. Reason: ' . $reason));
                        exit;
                    }
                    break;
                }
            }
        }
    } catch (Throwable $e) { }
}
function is_logged_in() { return isset($_SESSION['user_id']) && isset($_SESSION['username']); }
function is_creator() { return (isset($_SESSION['role']) && $_SESSION['role'] === 'creator'); }
function require_login() { if (!is_logged_in()) { header('Location: login.php'); exit; } }
?>

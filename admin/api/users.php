<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/log.php';
$users = [];
$pdo = db_connect();
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT id, username, email, role, plan, status, joined_at FROM users ORDER BY joined_at DESC LIMIT 200');
        $stmt->execute();
        while ($row = $stmt->fetch()) { $users[] = $row; }
    } catch (Throwable $e) {
        admin_log('users_db_error');
    }
}
if (!$users) {
    // Fallback: read users from flat-file store
    $usersPath = __DIR__ . '/../../user_data/users.json';
    if (file_exists($usersPath)) {
        $list = json_decode(file_get_contents($usersPath), true) ?: [];
        foreach ($list as $u) {
            $users[] = [
                'id' => $u['id'] ?? null,
                'username' => $u['username'] ?? '',
                'email' => $u['email'] ?? '',
                'role' => $u['role'] ?? 'reader',
                'plan' => $u['plan'] ?? 'free',
                'status' => $u['status'] ?? 'active',
                'joined_at' => $u['joined'] ?? date('Y-m-d'),
                'ban_until' => $u['ban_until'] ?? null,
                'ban_reason' => $u['ban_reason'] ?? null
            ];
        }
    }
    // Also include any creators from legacy store
    $path = __DIR__ . '/../../creator/data_creator/creators.json';
    $list = [];
    if (file_exists($path)) { $list = json_decode(file_get_contents($path), true) ?: []; }
    foreach ($list as $c) {
        $users[] = [
            'id' => $c['id'] ?? null,
            'username' => $c['username'] ?? ($c['email'] ?? ''),
            'email' => $c['email'] ?? ($c['username'] ?? ''),
            'role' => 'creator',
            'plan' => 'free',
            'status' => 'active',
            'joined_at' => $c['joined_at'] ?? date('Y-m-d')
        ];
    }
    if (isset($_SESSION['username'])) {
        $users[] = [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'],
            'email' => $_SESSION['username'],
            'role' => $_SESSION['role'] ?? 'reader',
            'plan' => 'pro',
            'status' => 'active',
            'joined_at' => date('Y-m-d')
        ];
    }
}
echo json_encode(['status' => 'success', 'users' => $users]);
?>

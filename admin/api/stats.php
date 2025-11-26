<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/log.php';
// Removed legacy DB views; using JSON views store
$json = @file_get_contents(__DIR__ . '/../../get_stories_api.php?t=' . time());
if (!$json) {
    admin_log('stats_fetch_failed');
    echo json_encode(['status' => 'error']);
    exit;
}
$data = json_decode($json, true);
if (!is_array($data)) {
    admin_log('stats_decode_failed');
    echo json_encode(['status' => 'error']);
    exit;
}
$stories = $data['stories'] ?? [];
$stats = $data['stats'] ?? [];
$dist = ['comic' => 0, 'manga' => 0, 'webtoon' => 0];
foreach ($stories as $s) {
    $t = strtolower($s['type'] ?? '');
    if (isset($dist[$t])) { $dist[$t]++; }
}
$usersCount = 0;
$viewsFile = __DIR__ . '/../../views/views.json';
$viewsCount = 0;
$activeUsers = 0;
$storiesCount = $stats['stories'] ?? 0;
$chaptersCount = $stats['chapters'] ?? 0;
try {
    $j = @file_get_contents($viewsFile);
    $d = json_decode($j, true);
    if (is_array($d)) {
        foreach ($d as $type => $seriesMap) {
            foreach ($seriesMap as $series => $vals) {
                $viewsCount += (int)($vals['total'] ?? 0);
            }
        }
    }
} catch (Throwable $e) {}
try {
    $usersPath = __DIR__ . '/../../user_data/users.json';
    if (file_exists($usersPath)) { $usersList = json_decode(file_get_contents($usersPath), true) ?: []; $usersCount = is_array($usersList) ? count($usersList) : 0; }
} catch (Throwable $e) {}
echo json_encode([
    'status' => 'success',
    'views' => $viewsCount,
    'stories' => $storiesCount,
    'chapters' => $chaptersCount,
    'earnings' => $stats['earnings'] ?? 0,
    'distribution' => $dist,
    'users' => $usersCount,
    'active_users' => $activeUsers
]);
?>

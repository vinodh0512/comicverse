<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);

$baseDir = __DIR__ . '/Book_data/';

function sanitize($v) { return basename($v); }

function loadReviews($seriesPath) {
    $file = $seriesPath . '/reviews.json';
    if (!file_exists($file)) return ['ratings' => [], 'counts' => ['1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0]];
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) $data = ['ratings' => [], 'counts' => ['1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0]];
    return $data;
}

function saveReviews($seriesPath, $data) {
    $file = $seriesPath . '/reviews.json';
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function summarize($data) {
    $counts = ['1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0];
    $sum = 0; $total = 0;
    foreach ($data['ratings'] as $r) {
        $rating = intval($r['rating'] ?? 0);
        if ($rating >=1 && $rating <=5) {
            $counts[(string)$rating]++;
            $sum += $rating;
            $total++;
        }
    }
    $avg = $total > 0 ? ($sum / $total) : 0;
    $percent = $avg > 0 ? round(($avg / 5) * 100) : 0;
    $dist = [];
    foreach ($counts as $k=>$v) { $dist[$k] = $total > 0 ? round(($v / $total) * 100) : 0; }
    return ['average' => $avg, 'overall_percent' => $percent, 'counts' => $counts, 'distribution' => $dist, 'total' => $total];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $series = sanitize($_POST['series'] ?? '');
        $type = sanitize($_POST['type'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $review = trim($_POST['review'] ?? '');

        if (!$series || !$type) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Missing series/type']); exit; }
        if ($rating < 1 || $rating > 5) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Invalid rating']); exit; }

        $seriesPath = $baseDir . $type . '/' . $series;
        if (!is_dir($seriesPath)) { http_response_code(404); echo json_encode(['status'=>'error','message'=>'Series not found']); exit; }

        $data = loadReviews($seriesPath);

        $entry = [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? 'Guest',
            'rating' => $rating,
            'review' => $review,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $updated = false;
        if ($entry['user_id']) {
            foreach ($data['ratings'] as &$r) {
                if (($r['user_id'] ?? null) === $entry['user_id']) { $r = $entry; $updated = true; break; }
            }
        } elseif (!empty($entry['username'])) {
            foreach ($data['ratings'] as &$r) {
                if (($r['username'] ?? '') === $entry['username'] && empty($r['user_id'])) { $r = $entry; $updated = true; break; }
            }
        }
        if (!$updated) { $data['ratings'][] = $entry; }

        $summary = summarize($data);
        saveReviews($seriesPath, $data);
        echo json_encode(['status'=>'success','summary'=>$summary]);
        exit;
    }

    // GET summary
    $series = sanitize($_GET['series'] ?? '');
    $type = sanitize($_GET['type'] ?? '');
    if (!$series || !$type) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Missing series/type']); exit; }
    $seriesPath = $baseDir . $type . '/' . $series;
    if (!is_dir($seriesPath)) { http_response_code(404); echo json_encode(['status'=>'error','message'=>'Series not found']); exit; }
    $data = loadReviews($seriesPath);
    $summary = summarize($data);
    $list = [];
    foreach ($data['ratings'] as $r) {
        $list[] = [
            'username' => $r['username'] ?? 'Guest',
            'rating' => intval($r['rating'] ?? 0),
            'review' => trim($r['review'] ?? ''),
            'timestamp' => $r['timestamp'] ?? ''
        ];
    }
    usort($list, function($a,$b){ return strcmp($b['timestamp'],$a['timestamp']); });
    $list = array_slice($list, 0, 30);
    echo json_encode(['status'=>'success','summary'=>$summary,'reviews'=>$list]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>

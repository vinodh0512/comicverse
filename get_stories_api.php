<?php
// get_stories_api.php
session_start();
$scope = $_GET['scope'] ?? $_POST['scope'] ?? 'all';
header('Content-Type: application/json');
// --- FIX: FORCE NO CACHE ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// ---------------------------
error_reporting(0);

$baseDir = 'Book_data/';
// Build site base path for absolute URLs like /comicverse/Book_data/...
$siteBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
function toWebUrl($p, $siteBase) {
    if (!$p) return $p;
    if (preg_match('/^(https?:\/\/|data:)/i', $p)) return $p;
    $p = ltrim($p, '/');
    return ($siteBase ? $siteBase : '') . '/' . $p;
}
$stories = []; 
$viewsFile = __DIR__ . '/views/views.json';
function views_count_series($file, $type, $series){ $j=@file_get_contents($file); $d=json_decode($j,true); if(!is_array($d)) return 0; return (int)($d[$type][$series]['total'] ?? 0); }
$currentCreator = ($scope === 'mine' && isset($_SESSION['role']) && $_SESSION['role'] === 'creator') ? ($_SESSION['username'] ?? null) : null;

// Helper: Time Ago
function time_elapsed_string($datetime) {
    try {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $weeks = floor($diff->d / 7);
        $days = $diff->d - ($weeks * 7);
        $string = array('y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
        foreach ($string as $k => &$v) {
            if ($k === 'w') $value = $weeks; elseif ($k === 'd') $value = $days; else $value = $diff->$k;
            if ($value) $v = $value . ' ' . $v . ($value > 1 ? 's' : ''); else unset($string[$k]);
        }
        $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    } catch (Exception $e) { return 'Just now'; }
}

function getReviewSummary($seriesPath) {
    $file = $seriesPath . '/reviews.json';
    if (!file_exists($file)) return ['average'=>0,'overall_percent'=>0];
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data) || !isset($data['ratings'])) return ['average'=>0,'overall_percent'=>0];
    $sum = 0; $total = 0;
    foreach ($data['ratings'] as $r) { $val = intval($r['rating'] ?? 0); if ($val>=1 && $val<=5) { $sum += $val; $total++; } }
    $avg = $total>0 ? ($sum/$total) : 0;
    $pct = $avg>0 ? round(($avg/5)*100) : 0;
    return ['average'=>$avg,'overall_percent'=>$pct];
}

// Scan Directory
if (is_dir($baseDir)) {
    $types = scandir($baseDir);
    foreach ($types as $type) {
        if ($type === '.' || $type === '..') continue;
        $typePath = $baseDir . $type;
        if (is_dir($typePath)) {
            $seriesList = scandir($typePath);
            foreach ($seriesList as $seriesName) {
                if ($seriesName === '.' || $seriesName === '..') continue;
                $seriesPath = $typePath . '/' . $seriesName;
                if (is_dir($seriesPath)) {
                    $chapters = scandir($seriesPath);
                    $chapterCount = 0;
                    $latestUploadTime = 0;
                    $seriesDisplayData = null;
                    $ownedByCreator = false;

                    // Prefer series root cover first
                    $seriesCover = '';
                    foreach (['cover.jpg','cover.png','cover.webp','cover.gif'] as $cfile) {
                        if (is_file($seriesPath . '/' . $cfile)) {
                            $seriesCover = toWebUrl($baseDir . $type . '/' . $seriesName . '/' . $cfile, $siteBase);
                            break;
                        }
                    }

                    foreach ($chapters as $chapter) {
                        if ($chapter === '.' || $chapter === '..') continue;
                        $jsonPath = $seriesPath . '/' . $chapter . '/metadata.json';
                        if (file_exists($jsonPath)) {
                            $chapterCount++;
                            $jsonContent = file_get_contents($jsonPath);
                            $data = json_decode($jsonContent, true);
                            if ($data) {
                                $creatorName = $data['meta']['creator'] ?? null;
                                if ($currentCreator && $creatorName && $creatorName === $currentCreator) { $ownedByCreator = true; }
                                $dateStr = $data['status']['upload_date'] ?? $data['status']['publish_date'] ?? 'now';
                                $uploadTime = strtotime($dateStr);

                                if ($uploadTime >= $latestUploadTime) {
                                    $latestUploadTime = $uploadTime;
                                    $thumbSrc = $seriesCover;
                                    if (empty($thumbSrc)) {
                                        if (!empty($data['assets']['thumbnail_base64'])) {
                                            $thumbSrc = $data['assets']['thumbnail_base64'];
                                        } elseif (!empty($data['assets']['thumbnail'])) {
                                            $tn = trim($data['assets']['thumbnail'], '/');
                                            if (preg_match('/^Book_data\//i', $tn)) { $thumbSrc = toWebUrl($tn, $siteBase); }
                                            else { $thumbSrc = toWebUrl($baseDir . $tn, $siteBase); }
                                        } elseif (!empty($data['assets']['thumbnail_path'])) {
                                            $tn = trim($data['assets']['thumbnail_path'], '/');
                                            if (preg_match('/^Book_data\//i', $tn)) { $thumbSrc = toWebUrl($tn, $siteBase); }
                                            else { $thumbSrc = toWebUrl($baseDir . $tn, $siteBase); }
                                        } else {
                                            $pages = $data['assets']['pages_files'] ?? [];
                                            $path = $data['assets']['path_to_pages'] ?? '';
                                            if (is_array($pages) && count($pages) > 0) {
                                                $pp = trim($path, '/');
                                                if (preg_match('/^Book_data\//i', $pp)) { $thumbSrc = toWebUrl($pp . '/' . $pages[0], $siteBase); }
                                                else { $thumbSrc = toWebUrl($baseDir . $pp . '/' . $pages[0], $siteBase); }
                                            } else {
                                                $thumbSrc = 'https://via.placeholder.com/400x600?text=No+Cover';
                                            }
                                        }
                                    }

                                    $seriesDisplayData = [
                                        'id' => md5($seriesName . $type), // Unique ID for JS removal
                                        'title' => $data['meta']['series_name'] ?? str_replace('_', ' ', $seriesName),
                                        'folder' => $seriesName, 
                                        'type' => $data['meta']['content_type'] ?? $type,
                                        'status' => $data['status']['state'] ?? 'Live',
                                        'last_updated' => $dateStr,
                                        'time_ago' => time_elapsed_string($dateStr),
                                        'publish_date' => $data['status']['publish_date'] ?? 'Unknown',
                                        'thumbnail' => $thumbSrc,
                                        'latest_chapter' => $data['meta']['chapter_number'] ?? $chapterCount,
                                        'views' => views_count_series($viewsFile, $type, $seriesName),
                                        'rating' => (function($sp){ $s=getReviewSummary($sp); return number_format($s['average'],1) . ' (' . $s['overall_percent'] . '%)'; })($seriesPath)
                                    ];
                                }
                            }
                        }
                    }
                    if ($seriesDisplayData) {
                        if ($scope === 'mine' && $currentCreator && !$ownedByCreator) { continue; }
                        $seriesDisplayData['total_chapters'] = $chapterCount;
                        $stories[] = $seriesDisplayData;
                    }
                }
            }
        }
    }
}

usort($stories, function($a, $b){
    $ta = strtotime($a['last_updated'] ?? '1970-01-01 00:00:00');
    $tb = strtotime($b['last_updated'] ?? '1970-01-01 00:00:00');
    return $tb <=> $ta;
});

// Stats
$totalStories = count($stories);
$totalChaptersAll = 0;
$totalViewsAll = 0;
foreach ($stories as $story) { $totalChaptersAll += $story['total_chapters']; $totalViewsAll += $story['views']; }

function format_views($n) { if ($n > 1000000) return round($n/1000000, 1).'M'; if ($n > 1000) return round($n/1000, 1).'k'; return $n; }

echo json_encode([
    'status' => 'success',
    'stats' => [
        'stories' => $totalStories,
        'chapters' => $totalChaptersAll,
        'views' => format_views($totalViewsAll),
        'earnings' => number_format($totalViewsAll * 0.005, 2)
    ],
    'stories' => $stories
]);
?>

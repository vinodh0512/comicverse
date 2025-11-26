<?php
// get_series.php
header('Content-Type: application/json');
error_reporting(0);

$baseDir = dirname(__DIR__) . '/Book_data/';
$seriesList = [];

if (is_dir($baseDir)) {
    // Scan types (Comic, Manga, Webtoon)
    $types = scandir($baseDir);
    
    foreach ($types as $type) {
        if ($type === '.' || $type === '..') continue;
        
        $typePath = $baseDir . $type;
        if (is_dir($typePath)) {
            // Scan Series inside the type folder
            $seriesFolders = scandir($typePath);
            
            foreach ($seriesFolders as $series) {
                if ($series === '.' || $series === '..') continue;
                
                $readableName = str_replace('_', ' ', $series);
                $seriesPath = $typePath . '/' . $series;
                $latestTime = 0;
                $latestChap = 0;
                $thumb = '';
                foreach (['cover.jpg','cover.png','cover.webp','cover.gif'] as $cfile) {
                    $cp = $seriesPath . '/' . $cfile;
                    if (is_file($cp)) { $thumb = 'Book_data/' . $type . '/' . $series . '/' . $cfile; break; }
                }
                $chs = scandir($seriesPath);
                foreach ($chs as $c) {
                    if ($c === '.' || $c === '..') continue;
                    $meta = $seriesPath . '/' . $c . '/metadata.json';
                    if (!is_file($meta)) continue;
                    $raw = file_get_contents($meta);
                    $data = json_decode($raw, true);
                    if (!is_array($data)) continue;
                    $dateStr = $data['status']['upload_date'] ?? ($data['status']['publish_date'] ?? 'now');
                    $t = strtotime($dateStr);
                    if ($t >= $latestTime) {
                        $latestTime = $t;
                        $latestChap = (int)($data['meta']['chapter_number'] ?? 0);
                        $tb64 = $data['assets']['thumbnail_base64'] ?? '';
                        $tfile = $data['assets']['thumbnail'] ?? '';
                        $pages = $data['assets']['pages_files'] ?? [];
                        $path = $data['assets']['path_to_pages'] ?? '';
                        if ($tb64) { $thumb = $tb64; }
                        elseif ($tfile && $thumb === '') { $thumb = 'Book_data/' . $type . '/' . $series . '/' . $c . '/' . $tfile; }
                        elseif (is_array($pages) && count($pages) > 0) {
                            if ($thumb === '') {
                                $pp = trim($path, '/');
                                if (preg_match('/^Book_data\//i', $pp)) { $thumb = $pp . '/' . $pages[0]; }
                                else { $thumb = 'Book_data/' . $pp . '/' . $pages[0]; }
                            }
                        } else {
                            if ($thumb === '') {
                                $dir = $seriesPath . '/' . $c;
                                $imgs = array_values(array_filter(scandir($dir), function($f){ return preg_match('/\.(jpg|jpeg|png|webp)$/i', $f); }));
                                if (count($imgs) > 0) { $thumb = 'Book_data/' . $type . '/' . $series . '/' . $c . '/' . $imgs[0]; }
                            }
                        }
                    }
                }
                $seriesList[] = [
                    'name' => $readableName,
                    'value' => $series,
                    'type' => $type,
                    'thumbnail' => $thumb,
                    'latest' => $latestChap
                ];
            }
        }
    }
}

// Return JSON
echo json_encode($seriesList);
?>
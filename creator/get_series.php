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
                $seriesPath = $typePath . '/' . $series;
                if (!is_dir($seriesPath)) continue;
                // Only include series that have at least one chapter with metadata.json
                $hasContent = false;
                $entries = scandir($seriesPath);
                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..') continue;
                    $chapterPath = $seriesPath . '/' . $entry;
                    if (is_dir($chapterPath) && file_exists($chapterPath . '/metadata.json')) { $hasContent = true; break; }
                }
                if (!$hasContent) continue;
                $readableName = str_replace('_', ' ', $series);
                $seriesList[] = [
                    'name' => $readableName,
                    'value' => $series,
                    'type' => $type
                ];
            }
        }
    }
}

// Return JSON
echo json_encode($seriesList);
?>

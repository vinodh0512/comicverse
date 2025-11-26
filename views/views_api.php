<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
// Disable error reporting for production output, but log errors
error_reporting(0); 

// --- CONFIGURATION ---
$viewsDir = __DIR__;
$viewsFile = $viewsDir . '/views.json';
$lockFile  = $viewsDir . '/views.lock'; // Helper file for exclusive locking

// 1. Security: Protect JSON file from direct web access
if (!file_exists($viewsDir . '/.htaccess')) {
    @file_put_contents($viewsDir . '/.htaccess', "Deny from all");
}

if (!is_dir($viewsDir)) { @mkdir($viewsDir, 0777, true); }
if (!file_exists($viewsFile)) { @file_put_contents($viewsFile, json_encode(new stdClass())); }

// --- HELPERS ---
function clean($s){ 
    // FIXED: Allow spaces ( ) so "Iron Man" doesn't become "IronMan"
    return preg_replace('/[^A-Za-z0-9_\- ]/', '', $s ?? ''); 
}

function client_ip(){ 
    $h = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''); 
    if (!is_string($h)) return 'unknown'; 
    $p = trim(explode(',', $h)[0]); 
    return $p; 
}

// --- MAIN LOGIC ---
$action = $_REQUEST['action'] ?? 'get_series';
$type = clean($_REQUEST['type'] ?? '');
$series = clean($_REQUEST['series'] ?? '');
$chapter = clean($_REQUEST['chapter'] ?? '0'); // Keep chapter as string for array keys

try {
    // Use a file lock to prevent race conditions (users overwriting each other)
    $fp = fopen($viewsFile, 'c+');
    if (!flock($fp, LOCK_EX)) {
        throw new Exception("Could not lock file");
    }

    // Read current data
    $json = "";
    while (!feof($fp)) $json .= fread($fp, 8192);
    $data = json_decode($json, true) ?: [];

    // Initialize structure if missing
    if (!isset($data[$type])) $data[$type] = [];
    if (!isset($data[$type][$series])) $data[$type][$series] = ['chapters'=>[], 'total'=>0];

    if ($action === 'log') {
        if (!$type || !$series) { 
            echo json_encode(['status'=>'error','message'=>'Missing params']); 
            exit;
        }

        $ip = client_ip();
        $seriesRef = &$data[$type][$series];
        
        // Initialize chapter
        if (!isset($seriesRef['chapters'][$chapter])) {
            $seriesRef['chapters'][$chapter] = ['count'=>0, 'ips'=>[]];
        }
        $entry = &$seriesRef['chapters'][$chapter];
        
        // --- LOGIC FIX: CHECK TIMESTAMP ---
        $now = time();
        $shouldCount = true;

        // Check if IP exists and when it was last logged
        if (isset($entry['ips'][$ip])) {
            $lastViewTime = strtotime($entry['ips'][$ip]);
            // Only count if last view was more than 1 hour ago (3600 seconds)
            if (($now - $lastViewTime) < 3600) {
                $shouldCount = false; 
            }
        }

        if ($shouldCount) {
            $entry['ips'][$ip] = date('c'); // Update timestamp
            $entry['count'] = intval($entry['count']) + 1;
            
            // Recalculate total
            $total = 0; 
            foreach ($seriesRef['chapters'] as $c) { 
                $total += intval($c['count'] ?? 0); 
            }
            $seriesRef['total'] = $total;

            // Clean up IPs to prevent file from getting too huge (Auto-maintenance)
            // Remove IPs older than 30 days
            foreach ($entry['ips'] as $userIp => $timestamp) {
                if ($now - strtotime($timestamp) > 2592000) {
                    unset($entry['ips'][$userIp]);
                }
            }
            
            // Write back to file
            ftruncate($fp, 0); // Clear file
            rewind($fp);       // Reset pointer
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        
        echo json_encode([
            'status'=>'success',
            'counted' => $shouldCount,
            'series_total'=>$seriesRef['total'],
            'chapter_count'=>$entry['count']
        ]);
    }
    
    elseif ($action === 'get_series') {
        $seriesRef = $data[$type][$series] ?? ['chapters'=>[], 'total'=>0];
        // Remove 'ips' from output to save bandwidth and protect privacy
        $publicData = $seriesRef;
        foreach($publicData['chapters'] as &$ch) { unset($ch['ips']); }
        
        echo json_encode([
            'status'=>'success',
            'total'=>$publicData['total'],
            'chapters'=>$publicData['chapters']
        ]);
    }

    // Release lock
    flock($fp, LOCK_UN);
    fclose($fp);

} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
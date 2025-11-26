<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
error_reporting(0);

$viewsDir = __DIR__;
$viewsFile = $viewsDir . '/views.json';
if (!is_dir($viewsDir)) { @mkdir($viewsDir, 0777, true); }
if (!file_exists($viewsFile)) { @file_put_contents($viewsFile, json_encode(new stdClass())); }

function read_json($file){ $j = @file_get_contents($file); $d = json_decode($j, true); return is_array($d) ? $d : []; }
function write_json($file, $data){ @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX); }
function client_ip(){ $h = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''); if (!is_string($h)) return ''; $p = trim(explode(',', $h)[0]); return $p; }
function clean($s){ return preg_replace('/[^A-Za-z0-9_\-]/', '', $s ?? ''); }

$action = $_REQUEST['action'] ?? 'get_series';
$type = clean($_REQUEST['type'] ?? '');
$series = clean($_REQUEST['series'] ?? '');
$chapter = intval($_REQUEST['chapter'] ?? 0);

$data = read_json($viewsFile);
if (!isset($data[$type])) $data[$type] = [];
if (!isset($data[$type][$series])) $data[$type][$series] = ['chapters'=>[], 'total'=>0];

if ($action === 'log') {
  if (!$type || !$series || $chapter <= 0) { echo json_encode(['status'=>'error','message'=>'invalid']); exit; }
  $ip = client_ip();
  $seriesRef = &$data[$type][$series];
  if (!isset($seriesRef['chapters'][$chapter])) $seriesRef['chapters'][$chapter] = ['count'=>0,'ips'=>[]];
  $entry = &$seriesRef['chapters'][$chapter];
  if (!isset($entry['ips'][$ip])) { $entry['ips'][$ip] = date('c'); $entry['count']++; }
  $total = 0; foreach ($seriesRef['chapters'] as $c) { $total += intval($c['count'] ?? 0); }
  $seriesRef['total'] = $total;
  write_json($viewsFile, $data);
  echo json_encode(['status'=>'success','series_total'=>$seriesRef['total'],'chapter_count'=>$entry['count']]);
  exit;
}

if ($action === 'get_series') {
  if (!$type || !$series) { echo json_encode(['status'=>'error']); exit; }
  $seriesRef = $data[$type][$series] ?? ['chapters'=>[], 'total'=>0];
  echo json_encode(['status'=>'success','total'=>$seriesRef['total'],'chapters'=>$seriesRef['chapters']]);
  exit;
}

if ($action === 'get_chapter') {
  if (!$type || !$series || $chapter <= 0) { echo json_encode(['status'=>'error']); exit; }
  $seriesRef = $data[$type][$series] ?? ['chapters'=>[], 'total'=>0];
  $cc = $seriesRef['chapters'][$chapter]['count'] ?? 0;
  echo json_encode(['status'=>'success','count'=>$cc]);
  exit;
}

echo json_encode(['status'=>'error']);
?>


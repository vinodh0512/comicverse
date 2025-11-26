<?php
function admin_log($message) {
    $dir = __DIR__ . '/../logs';
    $file = $dir . '/admin.log';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}
?>
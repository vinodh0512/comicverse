<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Forbidden</title><style>*{margin:0;padding:0;box-sizing:border-box;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif}body{background:#fff;color:#222;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center}.box{max-width:520px;padding:40px}.title{font-size:24px;font-weight:800;color:#ec1d24;margin-bottom:10px}.msg{font-size:14px;color:#555;margin-bottom:20px}.btn{background:#ec1d24;color:#fff;padding:12px 25px;border-radius:4px;text-decoration:none;font-weight:700}</style></head><body><div class="box"><div class="title">Restricted Area</div><div class="msg">Access denied. Redirecting to home.</div><a class="btn" href="../index.php">Go to Home</a></div><script>setTimeout(function(){window.location.href="../index.php"},5000)</script></body></html>';
    exit;
}
?>
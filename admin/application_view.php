<?php
require_once __DIR__ . '/includes/session.php';
$uid = $_GET['user_id'] ?? '';
$email = $_GET['email'] ?? '';
$usersPath = __DIR__ . '/../user_data/users.json';
$appsPath = __DIR__ . '/../creator/user_data/applications.json';
$user = null; $app = null;
try {
    if (file_exists($usersPath)) {
        $users = json_decode(file_get_contents($usersPath), true) ?: [];
        foreach ($users as $u) { if (($u['id'] ?? '') === $uid || (($u['email'] ?? '') === $email && $email !== '')) { $user = $u; break; } }
    }
    if (file_exists($appsPath)) {
        $apps = json_decode(file_get_contents($appsPath), true) ?: [];
        foreach ($apps as $a) { if (($a['user_id'] ?? '') === ($user['id'] ?? $uid) || (($a['email'] ?? '') === ($user['email'] ?? $email))) { $app = $a; break; } }
    }
} catch (Throwable $e) {}
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application View | Admin</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body{background:#151515;color:#fff;font-family:'Segoe UI',sans-serif;margin:0}
    .wrap{max-width:900px;margin:40px auto;padding:0 20px}
    .head{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
    .btn{background:#ec1d24;color:#fff;border:none;border-radius:6px;padding:10px 14px;font-weight:800;cursor:pointer}
    .card{background:#202020;border:1px solid #333;border-radius:8px;padding:20px;margin-bottom:16px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .full{grid-column:1/-1}
    .label{font-size:12px;color:#bbb;margin-bottom:6px}
    .val{font-size:14px}
    a.link{color:#00bcd4;text-decoration:none}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="head">
      <h2 style="margin:0">Creator Application</h2>
      <button class="btn" onclick="history.back()"><i class="fas fa-arrow-left"></i> Back</button>
    </div>
    <div class="card">
      <div class="grid">
        <div>
          <div class="label">Username</div>
          <div class="val"><?php echo esc($user['username'] ?? ''); ?></div>
        </div>
        <div>
          <div class="label">Email</div>
          <div class="val"><?php echo esc($user['email'] ?? ''); ?></div>
        </div>
        <div>
          <div class="label">Status</div>
          <div class="val"><?php echo esc($app['status'] ?? 'pending'); ?></div>
        </div>
        <div>
          <div class="label">Requested At</div>
          <div class="val"><?php echo esc($app['requested_at'] ?? ''); ?></div>
        </div>
        <div class="full">
          <div class="label">Display Name</div>
          <div class="val"><?php echo esc($app['display_name'] ?? ($user['username'] ?? '')); ?></div>
        </div>
        <div class="full">
          <div class="label">Portfolio</div>
          <?php $p = esc($app['portfolio'] ?? ''); ?>
          <div class="val"><?php echo $p ? ('<a class="link" target="_blank" href="'.$p.'">'.$p.'</a>') : ''; ?></div>
        </div>
        <div class="full">
          <div class="label">Genres</div>
          <div class="val"><?php echo esc($app['genres'] ?? ''); ?></div>
        </div>
        <div class="full">
          <div class="label">Samples</div>
          <div class="val"><?php echo esc($app['samples'] ?? ''); ?></div>
        </div>
        <div class="full">
          <div class="label">Bio</div>
          <div class="val"><?php echo esc($app['bio'] ?? ''); ?></div>
        </div>
        <div class="full">
          <div class="label">Social / Contact</div>
          <div class="val"><?php echo esc($app['social'] ?? ''); ?></div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

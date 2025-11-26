<?php
session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    if ($u === 'admin' && $p === 'admin123') {
        session_regenerate_id(true);
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ComicVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { background-color: #0f0f0f; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #141414; border: 1px solid #333; border-radius: 8px; width: 100%; max-width: 380px; padding: 30px; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .logo { width: 36px; height: 36px; border-radius: 6px; background: #ec1d24; display: flex; align-items: center; justify-content: center; font-weight: 900; }
        .title { font-size: 20px; font-weight: 800; }
        .field { display: flex; flex-direction: column; gap: 6px; margin-top: 15px; }
        .label { font-size: 12px; color: #888; text-transform: uppercase; font-weight: 700; }
        .input { background: #1f1f1f; border: 1px solid #444; color: #fff; padding: 12px 14px; border-radius: 6px; outline: none; }
        .input:focus { border-color: #ec1d24; }
        .btn { margin-top: 18px; background: #ec1d24; color: #fff; border: none; padding: 12px 14px; border-radius: 6px; font-weight: 800; cursor: pointer; width: 100%; }
        .btn:hover { background: #ff333b; }
        .error { margin-top: 12px; color: #ec1d24; font-size: 13px; font-weight: 700; }
        .hint { margin-top: 16px; font-size: 12px; color: #888; text-align: center; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var u = document.getElementById('username');
            if (u) u.focus();
        });
    </script>
    </head>
<body>
    <div class="card">
        <div class="brand"><div class="logo">CV</div><div class="title">Admin Login</div></div>
        <?php if ($error !== ''): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="field">
                <label class="label" for="username">Username</label>
                <input id="username" name="username" type="text" class="input" placeholder="admin" required>
            </div>
            <div class="field">
                <label class="label" for="password">Password</label>
                <input id="password" name="password" type="password" class="input" placeholder="admin123" required>
            </div>
            <button type="submit" class="btn"><i class="fas fa-lock"></i> Log In</button>
        </form>
        <div class="hint">Use admin / admin123</div>
    </div>
</body>
</html>
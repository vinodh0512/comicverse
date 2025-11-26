<?php
require_once __DIR__ . '/includes/session.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// --- Backend Logic Start (Unchanged) ---
$dataDir = __DIR__ . '/user_data/';
$usersFile = $dataDir . 'users.json';
if (!file_exists($usersFile)) file_put_contents($usersFile, json_encode([]));
$users = json_decode(file_get_contents($usersFile), true) ?: [];
$user = null;
foreach ($users as $u) { if (($u['email'] ?? '') === $email) { $user = $u; break; } }
$ok = '';
$err = '';

$valid = false;
if ($user) {
    $path = __DIR__ . '/user_data/reset/' . ($user['id'] ?? '') . '.json';
    $rec = file_exists($path) ? json_decode(file_get_contents($path), true) : null;
    if ($rec && ($rec['token'] ?? '') === $token && strtotime($rec['expires_at'] ?? '') >= time()) { $valid = true; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $token = $_POST['token'] ?? '';
    $pass1 = $_POST['pass1'] ?? '';
    $pass2 = $_POST['pass2'] ?? '';
    
    if ($pass1 !== $pass2 || strlen($pass1) < 6) { $err = 'Passwords must match and be at least 6 characters.'; }
    else {
        $users = json_decode(file_get_contents($usersFile), true) ?: [];
        foreach ($users as &$uu) { 
            if (($uu['email'] ?? '') === $email) { 
                $uu['password'] = password_hash($pass1, PASSWORD_DEFAULT); 
                $user = $uu; 
                break; 
            } 
        }
        
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        
        $path = __DIR__ . '/user_data/reset/' . ($user['id'] ?? '') . '.json';
        @unlink($path);
        
        $ok = 'Password changed successfully. Please log in.';
        $valid = false;
    }
}
// --- Backend Logic End ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | ComicVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Mixed Red & Blue Palette */
            --bg-color: #0b0f15; /* Deeper Dark Blue */
            --card-color: #141822; 
            --border-color: #2c3340; /* Subtler Border */
            --input-color: #1c212c;
            
            --brand-red: #ec1d24; /* ComicVerse Red */
            --brand-red-light: #ff333b; 
            --accent-blue: #00bcd4; /* Electric Blue Accent */

            --text-color: #e0e6f1;
            --subtext-color: #9ab4cc;
            --error-color: #ff6666;
            --success-color: #00a652;
        }

        body {
            background: var(--bg-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            padding: 16px;
            overflow: hidden;
        }
        
        /* Red and Blue Lighting Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Two radial gradients for the red and blue glow */
            background: radial-gradient(circle at top right, rgba(236, 29, 36, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at bottom left, rgba(0, 188, 212, 0.1) 0%, transparent 40%);
            z-index: -1;
            animation: color-shift 20s infinite alternate ease-in-out;
        }

        @keyframes color-shift {
            0% { background-position: 0% 0%; }
            100% { background-position: 100% 100%; }
        }

        .card {
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 30px; 
            width: 95%;
            max-width: 520px;
            /* Box shadow uses a subtle blend of the two main colors */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.6), 0 0 15px rgba(236, 29, 36, 0.15), 0 0 15px rgba(0, 188, 212, 0.15);
            position: relative;
            z-index: 1;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .logo {
            background: var(--brand-red);
            color: #fff;
            font-weight: 900;
            padding: 6px 12px;
            border-radius: 6px;
            box-shadow: 0 0 8px rgba(236, 29, 36, 0.6); /* Red logo glow */
        }

        .title {
            font-weight: 900;
            font-size: 26px;
            margin-bottom: 8px;
            color: var(--text-color);
        }

        .subtitle {
            color: var(--subtext-color);
            margin-bottom: 16px; 
            font-size: 15px;
        }

        .input {
            width: 100%;
            padding: 16px;
            margin-top: 10px;
            background: var(--input-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }

        .input:focus {
            border-color: var(--accent-blue); /* Focus uses Electric Blue */
            box-shadow: 0 0 10px rgba(0, 188, 212, 0.8); /* Stronger Blue Glow */
        }

        .btn {
            width: 100%;
            margin-top: 20px;
            padding: 14px;
            background: var(--brand-red); /* Button remains Red for primary action */
            border: none;
            color: #fff;
            font-weight: 800;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(236, 29, 36, 0.4);
            transition: background 0.3s, box-shadow 0.3s;
        }

        .btn:hover:not(:disabled) {
            background: var(--brand-red-light);
            box-shadow: 0 6px 15px rgba(236, 29, 36, 0.8);
        }

        .btn:disabled {
            background: #444;
            cursor: not-allowed;
            box-shadow: none;
            opacity: 0.6;
        }

        .msg {
            margin: 15px 0 10px 0;
            font-size: 14px;
            padding: 10px 15px;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
        }

        .error {
            color: var(--error-color);
            background: rgba(255, 102, 102, 0.1);
            border: 1px solid var(--error-color);
        }

        .ok {
            color: var(--success-color);
            background: rgba(0, 166, 82, 0.1);
            border: 1px solid var(--success-color);
        }

        .footer {
            margin-top: 20px;
            text-align: center;
        }

        .link {
            color: var(--subtext-color);
            text-decoration: none;
            transition: color 0.3s;
        }

        .link:hover {
            color: var(--accent-blue); /* Footer link hover uses Electric Blue */
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand"><span class="logo">CV</span><strong>Reset your password</strong></div>
        <div class="title">Reset Password</div>
        
        <?php 
        // Display messages based on backend status
        if ($ok) {
            echo '<div class="msg ok">' . htmlspecialchars($ok) . '</div>';
        } else if ($err) {
            echo '<div class="msg error">' . htmlspecialchars($err) . '</div>';
        } else if (!$user) {
            echo '<div class="msg error">Account not found.</div>';
        } else if (!$valid) {
            echo '<div class="msg error">Invalid or expired reset link.</div>';
        }
        ?>

        <?php if ($valid): ?>
        <form method="POST">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="subtitle">Enter your new password (min. 6 characters)</div>
            <input type="password" name="pass1" class="input" placeholder="New password" required autofocus>
            <input type="password" name="pass2" class="input" placeholder="Confirm password" required>
            <button type="submit" class="btn">Change Password</button>
        </form>
        <?php endif; ?>
        
        <div class="footer"><a href="login.php" class="link">Back to Login</a></div>
    </div>
</body>
</html>
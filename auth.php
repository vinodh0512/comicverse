<?php
// auth.php
session_start();

// --- CONFIGURATION ---
$dataDir = __DIR__ . '/user_data/';
$jsonFile = $dataDir . 'users.json';

// --- 1. INITIALIZATION (Create DB if missing) ---
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode([]));
}

// --- 2. HELPER FUNCTIONS ---
function getUsers() {
    global $jsonFile;
    $content = file_get_contents($jsonFile);
    return json_decode($content, true) ?? [];
}

function saveUsers($users) {
    global $jsonFile;
    file_put_contents($jsonFile, json_encode($users, JSON_PRETTY_PRINT));
}

function getCreators() {
    $dataDir = __DIR__ . '/creator/user_data/';
    $jsonFile = $dataDir . 'creators.json';
    if (!is_dir($dataDir)) { mkdir($dataDir, 0777, true); }
    if (!file_exists($jsonFile)) { file_put_contents($jsonFile, json_encode([])); }
    $content = file_get_contents($jsonFile);
    return json_decode($content, true) ?? [];
}

function saveCreators($creators) {
    $dataDir = __DIR__ . '/creator/user_data/';
    $jsonFile = $dataDir . 'creators.json';
    if (!is_dir($dataDir)) { mkdir($dataDir, 0777, true); }
    file_put_contents($jsonFile, json_encode($creators, JSON_PRETTY_PRINT));
}

// --- DEFAULT CREATOR SEED ---
$users = getUsers();
$needSeed = true;
foreach ($users as $u) { if (($u['email'] ?? '') === 'creator@comicverse.com') { $needSeed = false; break; } }
if ($needSeed) {
    $users[] = [
        'id' => 'user_creator_1',
        'username' => 'creator',
        'email' => 'creator@comicverse.com',
        'password' => password_hash('creator123', PASSWORD_DEFAULT),
        'joined' => date('Y-m-d H:i:s'),
        'role' => 'creator'
    ];
    saveUsers($users);
    $creators = getCreators();
    $exists = false;
    foreach ($creators as $c) { if (($c['email'] ?? '') === 'creator@comicverse.com') { $exists = true; break; } }
    if (!$exists) {
        $creators[] = [
            'id' => 'user_creator_1',
            'username' => 'creator',
            'email' => 'creator@comicverse.com',
            'password' => $users[array_key_last($users)]['password'],
            'joined' => date('Y-m-d H:i:s'),
            'role' => 'creator'
        ];
        saveCreators($creators);
    }
}

// --- 3. REQUEST HANDLING ---

// HANDLE LOGOUT
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_write_close();
    session_regenerate_id(true);
    header("Location: login.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'logout_creator') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_write_close();
    session_regenerate_id(true);
    header("Location: login.php?role=creator");
    exit;
}

// HANDLE FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'] ?? '';

    // --- A. SIGN UP LOGIC ---
    if ($action === 'signup') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        // Validations
        if ($password !== $confirm) {
            header("Location: signup.php?error=" . urlencode("Passwords do not match"));
            exit;
        }

        $users = getUsers();

        // Check if email exists
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                header("Location: signup.php?error=" . urlencode("Email already registered"));
                exit;
            }
        }

        // Create User
        $newUser = [
            'id' => uniqid('user_'),
            'username' => htmlspecialchars($username),
            'email' => filter_var($email, FILTER_SANITIZE_EMAIL),
            'password' => password_hash($password, PASSWORD_DEFAULT), // SECURE HASHING
            'joined' => date('Y-m-d H:i:s'),
            'role' => 'reader',
            'verified' => false
        ];

        $users[] = $newUser;
        saveUsers($users);

        // Generate OTP and send verification email
        $otpDir = __DIR__ . '/user_data/otp';
        if (!is_dir($otpDir)) { mkdir($otpDir, 0777, true); }
        $otpCode = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
        $otpData = [ 'user_id' => $newUser['id'], 'email' => $newUser['email'], 'code' => $otpCode, 'expires_at' => date('Y-m-d H:i:s', time()+600) ];
        file_put_contents($otpDir . '/' . $newUser['id'] . '.json', json_encode($otpData, JSON_PRETTY_PRINT));
        require_once __DIR__ . '/includes/mailer_config.php';
        $subject = 'ComicVerse Email Verification Code';
        $html = '<h2>Your verification code</h2><p>Use this code to verify your email: <strong style="font-size:20px;">' . htmlspecialchars($otpCode) . '</strong></p><p>This code expires in 10 minutes.</p>';
        send_mail($newUser['email'], $subject, $html);

        // Set session and redirect to verification prompt
        $_SESSION['user_id'] = $newUser['id'];
        $_SESSION['username'] = $newUser['username'];
        $_SESSION['role'] = 'reader';
        header('Location: otp_verify.php');
        exit;
    }

    // --- B. LOGIN LOGIC ---
    if ($action === 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $users = getUsers();
        $foundUser = null;

        foreach ($users as $user) {
            if ($user['email'] === $email) {
                $foundUser = $user;
                break;
            }
        }

        // Verify User and Password
        if ($foundUser && password_verify($password, $foundUser['password'])) {
            $status = $foundUser['status'] ?? 'active';
            $until = $foundUser['ban_until'] ?? null;
            $reason = $foundUser['ban_reason'] ?? '';
            if ($status === 'banned' && $until && strtotime($until) > time()) {
                header("Location: login.php?error=" . urlencode('Account banned until ' . $until . '. Reason: ' . $reason));
                exit;
            }
            $_SESSION['user_id'] = $foundUser['id'];
            $_SESSION['username'] = $foundUser['username'];
            $_SESSION['role'] = $foundUser['role'] ?? 'reader';
            if (!(bool)($foundUser['verified'] ?? false)) {
                header('Location: otp_verify.php');
                exit;
            }
            header("Location: index.php");
            exit;
        } else {
            header("Location: login.php?error=" . urlencode("Invalid email or password"));
            exit;
        }
    }

    if ($action === 'creator_login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $users = getUsers();
        $foundUser = null;

        foreach ($users as $user) {
            if ($user['email'] === $email) {
                $foundUser = $user;
                break;
            }
        }

        if ($foundUser && password_verify($password, $foundUser['password'])) {
            $status = $foundUser['status'] ?? 'active';
            $until = $foundUser['ban_until'] ?? null;
            $reason = $foundUser['ban_reason'] ?? '';
            if ($status === 'banned' && $until && strtotime($until) > time()) {
                header("Location: login.php?role=creator&error=" . urlencode('Account banned until ' . $until . '. Reason: ' . $reason));
                exit;
            }
            $creators = getCreators();
            $exists = false;
            foreach ($creators as $c) {
                if (($c['email'] ?? '') === $foundUser['email']) { $exists = true; break; }
            }
            if (!$exists) {
                $_SESSION['user_id'] = $foundUser['id'];
                $_SESSION['username'] = $foundUser['username'];
                $_SESSION['role'] = $foundUser['role'] ?? 'reader';
                header("Location: profile.php?prompt=creator_apply");
                exit;
            }

            $_SESSION['user_id'] = $foundUser['id'];
            $_SESSION['username'] = $foundUser['username'];
            $_SESSION['role'] = 'creator';
            header("Location: index.php");
            exit;
        } else {
            header("Location: login.php?role=creator&error=" . urlencode("Invalid email or password"));
            exit;
        }
    }

    if ($action === 'apply_creator') {
        $users = getUsers();
        $uid = $_SESSION['user_id'] ?? null;
        $updated = false;
        $emailForApp = '';
        foreach ($users as &$u) {
            if (($u['id'] ?? null) === $uid) {
                $u['role'] = 'creator_pending';
                $emailForApp = $u['email'] ?? '';
                $updated = true;
                break;
            }
        }
        if ($updated) {
            saveUsers($users);
            $appDir = __DIR__ . '/creator/user_data/';
            $appFile = $appDir . 'applications.json';
            if (!is_dir($appDir)) { mkdir($appDir, 0777, true); }
            $apps = [];
            if (file_exists($appFile)) { $apps = json_decode(file_get_contents($appFile), true) ?: []; }
            $exists = false;
            foreach ($apps as $a) { if (($a['user_id'] ?? '') === $uid) { $exists = true; break; } }
            $payload = [
                'user_id' => $uid,
                'username' => $_SESSION['username'] ?? '',
                'email' => $emailForApp,
                'status' => 'pending',
                'requested_at' => date('Y-m-d H:i:s'),
                'display_name' => trim($_POST['display_name'] ?? ($_SESSION['username'] ?? '')),
                'portfolio' => trim($_POST['portfolio'] ?? ''),
                'bio' => trim($_POST['bio'] ?? ''),
                'genres' => trim($_POST['genres'] ?? ''),
                'samples' => trim($_POST['samples'] ?? ''),
                'social' => trim($_POST['social'] ?? '')
            ];
            if ($exists) {
                foreach ($apps as &$a) { if (($a['user_id'] ?? '') === $uid) { $a = array_merge($a, $payload); break; } }
            } else {
                $apps[] = $payload;
            }
            file_put_contents($appFile, json_encode($apps, JSON_PRETTY_PRINT));
            header("Location: profile.php?success=" . urlencode("Application submitted. Awaiting admin approval."));
            exit;
        }
        header("Location: profile.php?error=" . urlencode("Unable to submit application."));
        exit;
    }
}

// If accessed directly without POST, redirect home
header("Location: index.php");
?>

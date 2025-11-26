<?php
require_once __DIR__ . '/includes/session.php';
require_login();

// --- PHP HELPER FUNCTION (SIMULATED DB FETCH) ---
function getFullUserProfile($username) {
    // SIMULATE DELAY for the loader to be visible (100ms)
    // usleep(100000); 

    $role = $_SESSION['role'] ?? 'reader';
    $isCreator = ($role === 'creator');
    $userId = $_SESSION['user_id'] ?? uniqid();

    // Determine initial based on username
    $initial = strtoupper(substr($username, 0, 1));

    $email = $_SESSION['email'] ?? ($username . ($isCreator ? '@creator.com' : '@reader.com'));
    if ($isCreator) {
        return [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'joined_at' => '2024-05-15',
            'bio' => 'Official verified account for uploading stories to ComicVerse. Focused on high-quality action manga.',
            'followers' => rand(1000, 5000),
            'stories_published' => rand(5, 20),
            'initial' => $initial
        ];
    }

    return [
        'id' => $userId,
        'username' => $username,
        'email' => $email,
        'role' => $role,
        'joined_at' => '2025-01-01',
        'bio' => 'A passionate reader who enjoys action and fantasy manga.',
        'liked_items' => rand(50, 200),
        'initial' => $initial
    ];
}

$user = getFullUserProfile($_SESSION['username']);
$isCreator = ($user['role'] === 'creator');
$userId = $_SESSION['user_id'] ?? '';
$appFile = __DIR__ . '/creator/user_data/applications.json';
$myApp = null; if (file_exists($appFile)) { $aa = json_decode(file_get_contents($appFile), true) ?: []; foreach ($aa as $a) { if (($a['user_id'] ?? '') === $userId) { $myApp = $a; break; } } }

// Load user's reading list (Realtime source used by JS; server-side for initial render)
$listsPath = __DIR__ . '/user_data/lists.json';
$userList = [];
if (file_exists($listsPath)) {
    $all = json_decode(file_get_contents($listsPath), true);
    if (is_array($all) && isset($all[$_SESSION['username']])) { $userList = $all[$_SESSION['username']]; }
}

$activityPath = __DIR__ . '/user_data/activity.json';
$userActivity = [];
if (file_exists($activityPath)) {
    $aa = json_decode(file_get_contents($activityPath), true);
    if (is_array($aa) && isset($aa[$_SESSION['username']])) { $userActivity = $aa[$_SESSION['username']]; }
}
usort($userActivity, function($a,$b){ return strtotime(($b['time']??'')) - strtotime(($a['time']??'')); });
function latest_read_title($acts) {
    foreach ($acts as $a) { if (($a['type']??'') === 'read_chapter') { $s = $a['series']??''; $c = $a['chapter']??''; return ($s && $c) ? (ucwords(str_replace('-', ' ', $s)) . ", Ch. " . $c) : ''; } }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> | Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* --- VARIABLES --- */
        :root {
            --primary-red: #E53935; /* Brighter, more defined red */
            --primary-green: #00a652;
            --bg-body: #0a0a0a;
            --bg-card: #1C1C1E; /* Slightly lighter card background */
            --bg-nav: #252525;
            --border-dark: #3A3A3C;
            --text-light: #ffffff;
            --text-muted: #B0B0B0; /* Better contrast */
            --radius-default: 8px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { 
            background-color: var(--bg-body); 
            color: var(--text-light); 
            min-height: 100vh; 
            overflow-x: hidden; /* Prevent horizontal scroll for animations */
        }
        a { text-decoration: none; color: var(--primary-red); transition: color 0.3s; }
        a:hover { color: var(--text-light); }

        /* --- LOADER (Performance Enhancement) --- */
        .loader-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: transparent;
            z-index: 1000;
            /* Simulates a fast, indeterminate loading state on navigation */
            animation: pulse-loader 1s ease-out;
        }
        .loader-bar::before {
            content: '';
            display: block;
            height: 100%;
            background-color: var(--primary-red);
            width: 30%;
            animation: slide-loader 1s infinite linear;
        }
        @keyframes slide-loader {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(333.33%); }
        }
        @keyframes pulse-loader {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* --- NAVBAR --- */
        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0 5%; 
            height: 65px; /* Slightly taller */
            background-color: var(--bg-nav); 
            border-bottom: 1px solid var(--border-dark); 
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .logo { font-size: 26px; font-weight: 900; color: var(--primary-red); }
        .nav-links { display:flex; gap:24px; margin-left: 24px; }
        .nav-links a { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #ccc; }
        .nav-links a:hover { color: var(--primary-red); }
        .mobile-menu-btn { display:none; background:none; border:none; color:#fff; font-size:22px; width:44px; height:44px; cursor:pointer; margin-left: 10px; }
        .drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); display:none; z-index:999; }
        .drawer-overlay.open { display:block; }
        .mobile-drawer { position:fixed; top:0; right:0; width:80%; max-width: 300px; height:100%; background:#1a1a1a; border-left:1px solid var(--border-dark); z-index:1000; transform: translateX(100%); transition: transform .2s ease-out; padding:20px; display:flex; flex-direction:column; gap:16px; }
        .mobile-drawer.open { transform: translateX(0); }
        .mobile-drawer a { color:#ccc; font-weight:700; text-transform:uppercase; padding:10px; border-bottom:1px solid #2a2a2a; }
        .drawer-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-dark); margin-bottom: 10px; }
        .drawer-close-btn { color: #fff; font-size: 24px; cursor: pointer; background:none; border:none; }
        
        .nav-button {
            background: none;
            border: 1px solid var(--border-dark);
            color: var(--text-muted);
            padding: 8px 12px;
            border-radius: var(--radius-default);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .nav-button:hover { 
            background: var(--border-dark); 
            color: var(--text-light); 
        }

        .user-menu a { 
            color: white; 
            font-weight: 600; 
            font-size: 13px; 
            text-transform: uppercase; 
            background: var(--primary-red); 
            padding: 8px 15px; 
            border-radius: var(--radius-default);
            border: 1px solid var(--primary-red);
        }
        .user-menu a:hover { 
            background: transparent; 
            color: var(--primary-red); 
        }

        /* --- LAYOUT --- */
        .container { max-width: 960px; margin: 40px auto; padding: 0 20px; }

        /* --- PROFILE HEADER CARD (Modernized) --- */
        .profile-header {
            background: var(--bg-card);
            border-radius: 12px; /* More rounded corners */
            padding: 30px;
            display: flex;
            align-items: flex-start; /* Align to the top */
            gap: 25px;
            border: none; /* Remove border, rely on shadow */
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
        }
        .profile-avatar {
            min-width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #252525;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 36px;
            color: var(--primary-red);
            border: 3px solid var(--primary-red);
            font-weight: 700;
        }
        .profile-info h1 {
            font-size: 32px; /* Larger username */
            font-weight: 900;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .role-badge {
            font-size: 11px;
            background: var(--text-muted);
            color: var(--bg-card);
            padding: 4px 10px;
            border-radius: 12px; /* Pill shape */
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .creator-badge { background: var(--primary-green); color: var(--bg-body); }
        .info-detail {
            font-size: 15px;
            color: var(--text-muted);
            margin-top: 5px;
            display: flex;
            align-items: center;
        }
        .info-detail i { margin-right: 10px; color: var(--primary-red); }
        .email-link { color: var(--text-light); }
        .email-link:hover { color: var(--primary-red); }
        .profile-info p:last-of-type { 
            margin-top: 20px; 
            font-size: 15px;
            font-style: italic;
            color: var(--text-light);
            border-left: 3px solid var(--primary-red);
            padding-left: 15px;
        }

        /* --- CREATOR CTA --- */
        .creator-cta {
            margin-top: 30px;
            padding: 20px;
            background: var(--bg-card);
            border: 1px solid var(--primary-red);
            border-radius: var(--radius-default);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .creator-cta p { font-weight: 600; font-size: 16px; margin: 0; }
        .creator-cta p i { color: var(--primary-green); margin-right: 10px; }
        .btn-studio {
            background: var(--primary-green);
            padding: 10px 20px;
            border-radius: var(--radius-default);
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: white;
            transition: background 0.2s;
        }
        .btn-studio:hover { background: #009241; }

        /* --- TAB NAVIGATION --- */
        .tab-nav {
            margin-top: 40px;
            display: flex;
            border-bottom: 1px solid var(--border-dark);
            padding-bottom: 5px;
            gap: 5px;
        }
        .tab-nav button {
            background: none;
            border: none;
            padding: 12px 18px;
            color: var(--text-muted);
            font-size: 15px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            border-radius: 4px 4px 0 0;
            font-weight: 500;
        }
        .tab-nav button:hover {
            color: var(--text-light);
            background: #252525;
        }
        .tab-nav button.active {
            color: var(--text-light);
            border-bottom-color: var(--primary-red);
            font-weight: 700;
            background: #101010;
        }

        /* --- TAB CONTENT --- */
        .tab-content-section {
            padding: 30px 0;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
        }
        .tab-pane h3 {
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 700;
            border-left: 4px solid var(--primary-red);
            padding-left: 10px;
        }
        .list-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-dark);
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .list-item:last-child { border-bottom: none; }
        .list-item i { color: var(--primary-red); margin-right: 15px; font-size: 18px; width: 20px; }
        .list-item-content { display: flex; align-items: center; }
        .list-item span { color: var(--text-muted); font-size: 13px; }

        /* --- STATS GRID (Professional Dashboard Look) --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: var(--radius-default);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            border-top: 4px solid var(--primary-red);
        }
        .stat-card .stat-icon {
            font-size: 24px;
            color: var(--primary-red);
            margin-bottom: 10px;
        }
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 5px;
        }
        .stat-card .stat-label {
            font-size: 14px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* --- MEDIA QUERIES (Responsiveness) --- */
        @media (max-width: 650px) {
            .profile-header { flex-direction: column; text-align: center; align-items: center; }
            .profile-avatar { margin-bottom: 10px; }
            .profile-info h1 { justify-content: center; }
            .info-detail { justify-content: center; }
            .email-link { word-break: break-all; }
            .creator-cta { flex-direction: column; gap: 15px; align-items: stretch; }
            .tab-nav { justify-content: space-around; }
            .tab-nav button { padding: 10px; font-size: 14px; }
            .nav-links { display:none; }
            .mobile-menu-btn { display:block; }
            .user-menu { display:none; }
        }
    </style>
</head>
<body>
    <div class="loader-bar"></div>

    <nav class="navbar">
        <div class="nav-left">
            <button class="nav-button" onclick="goBackSafely()"><i class="fas fa-arrow-left"></i> Back</button>
            <a href="index.php" class="logo">ComicVerse</a> 
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="comic.php">Comics</a>
            <a href="manga.php">Manga</a>
            <a href="webtoon.php">Movies</a>
        </div>
        <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
        <div class="user-menu">
            <?php if ($isCreator): ?>
                <a href="mystories.php" style="margin-right:10px;">Creator Studio</a>
                <a href="auth.php?action=logout_creator">LOGOUT <i class="fas fa-sign-out-alt"></i></a>
            <?php else: ?>
                <a href="auth.php?action=logout">LOGOUT <i class="fas fa-sign-out-alt"></i></a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header">
            <span style="font-size: 20px; font-weight: 900; color: var(--primary-red);">Menu</span>
            <button class="drawer-close-btn" id="drawerCloseBtn">&times;</button>
        </div>
        <a href="index.php">Home</a>
        <a href="comic.php">Comics</a>
        <a href="manga.php">Manga</a>
        <a href="webtoon.php">Movies</a>
        <a href="profile.php">Profile</a>
        <?php if ($isCreator): ?>
            <a href="mystories.php">Dashboard</a>
            <a href="auth.php?action=logout_creator">Logout</a>
        <?php else: ?>
            <a href="auth.php?action=logout">Logout</a>
        <?php endif; ?>
    </div>

<div class="container">
        <?php if (($myApp['status'] ?? '') === 'pending'): ?>
            <div style="background:#1e1e1e; border:1px solid #00a652; color:#fff; padding:12px 16px; border-radius:8px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:10px;"><i class="fas fa-check-circle" style="color:#00a652;"></i><strong>Application submitted</strong></div>
                <div style="font-size:13px; color:#9ab4cc;">We will respond within 5 hours.</div>
            </div>
        <?php elseif (($myApp['status'] ?? '') === 'rejected'): ?>
            <div id="rejectBanner" style="background:#1e1e1e; border:1px solid #ec1d24; color:#fff; padding:12px 16px; border-radius:8px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="display:flex; align-items:center; gap:10px;"><i class="fas fa-exclamation-triangle" style="color:#ec1d24;"></i><strong>Application rejected</strong></div>
                    <div style="margin-top:6px; font-size:13px; color:#ff6666;">Reason: <?php echo htmlspecialchars($myApp['reason'] ?? ''); ?></div>
                </div>
                <button id="rejectHideBtn" style="background:#333; color:#fff; border:1px solid #444; padding:8px 12px; border-radius:6px; cursor:pointer;">Hide</button>
            </div>
            <script>
                (function(){
                    var uid = <?php echo json_encode($userId); ?>;
                    var ts = <?php echo json_encode($myApp['rejected_at'] ?? ''); ?>;
                    var key = 'cv_reject_seen_' + uid;
                    try {
                        var seen = localStorage.getItem(key);
                        if (seen && ts && seen === ts) {
                            var b = document.getElementById('rejectBanner'); if (b) b.style.display='none';
                        }
                    } catch(e){}
                    var btn = document.getElementById('rejectHideBtn');
                    if (btn) btn.addEventListener('click', function(){
                        try { if (ts) localStorage.setItem(key, ts); } catch(e){}
                        var b = document.getElementById('rejectBanner'); if (b) b.style.display='none';
                    });
                })();
            </script>
        <?php endif; ?>
        <?php if (!($isCreator)):
            $prompt = isset($_GET['prompt']) && $_GET['prompt'] === 'creator_apply';
            if ($prompt): ?>
            <div style="background:#1e1e1e; border:1px solid var(--primary-red); color:#fff; padding:12px 16px; border-radius:8px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
                <div><strong>Start Creating:</strong> Apply for a creator account to access the studio.</div>
                <a href="creator_apply.php" class="nav-button" style="background:var(--primary-red); color:#fff; border-color:var(--primary-red); text-decoration:none; display:inline-block;">Apply Now</a>
            </div>
            <?php endif; endif; ?>
        
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo htmlspecialchars($user['initial']); ?>
            </div>
            <div class="profile-info">
                <h1>
                    <?php echo htmlspecialchars($user['username']); ?>
                    <?php if ($isCreator): ?>
                        <span class="role-badge creator-badge">CREATOR</span>
                    <?php else: ?>
                        <span class="role-badge">READER</span>
                    <?php endif; ?>
                </h1>
                <p class="info-detail"><i class="fas fa-envelope"></i> <a class="email-link" href="mailto:<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></a></p>
                <p class="info-detail"><i class="fas fa-calendar-alt"></i> Joined: <?php echo htmlspecialchars($user['joined_at']); ?></p>
                <p class="info-detail"><?php echo htmlspecialchars($user['bio']); ?></p>
            </div>
        </div>

        <?php if ($isCreator): ?>
        <div class="creator-cta">
            <p><i class="fas fa-chart-line"></i> Manage your publications and track performance.</p>
            <a href="mystories.php" class="btn-studio">Go to Studio <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php else: ?>
        <?php if (($myApp['status'] ?? '') !== 'pending'): ?>
            <div class="creator-cta">
                <p><i class="fas fa-user-plus" style="color:var(--primary-red);"></i> Want to publish? Apply to become a creator.</p>
                <a href="creator_apply.php" class="btn-studio" style="background:var(--primary-red); text-decoration:none;">Apply Now</a>
            </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="tab-nav">
            <button class="tab-link active" onclick="openTab(event, 'Activity')"><i class="fas fa-chart-bar"></i> Activity</button>
            <button class="tab-link" id="myListTabLabel" onclick="openTab(event, 'MyList')"><i class="fas fa-bookmark"></i> My List (<?php echo count($userList); ?>)</button>
            <?php if ($isCreator): ?>
                <button class="tab-link" onclick="openTab(event, 'CreatorStats')"><i class="fas fa-medal"></i> Stats Overview</button>
            <?php endif; ?>
            <button class="tab-link" onclick="openTab(event, 'Settings')"><i class="fas fa-cog"></i> Settings</button>
        </div>

        <div class="tab-content-section">
            
            <div id="Activity" class="tab-pane active">
                <h3>Recent Activity & Metrics</h3>
                <div class="list-item">
                    <div class="list-item-content">
                        <i class="fas fa-heart"></i>
                        <span><?php echo $isCreator ? 'Total Followers' : 'Total Liked Items'; ?></span>
                    </div>
                    <strong><?php echo $isCreator ? number_format($user['followers']) : number_format($user['liked_items']); ?></strong>
                </div>
                <div class="list-item">
                    <div class="list-item-content">
                        <i class="fas fa-eye"></i>
                        <span>Last Read Item</span>
                    </div>
                    <strong><?php echo htmlspecialchars(latest_read_title($userActivity) ?: ''); ?></strong>
                </div>
                <div class="list-item">
                    <div class="list-item-content">
                        <i class="fas fa-list-ul"></i>
                        <span>Items on Reading List</span>
                    </div>
                    <strong id="activityListCount"><?php echo count($userList); ?></strong>
                </div>
                <div class="list-item" style="flex-direction: column; align-items: flex-start; gap: 10px;">
                    <div style="display:flex; align-items:center; gap:10px;"><i class="fas fa-history"></i><span>Recent Activity</span></div>
                    <div id="activityFeed" style="width:100%">
                        <?php if (empty($userActivity)): ?>
                            <div class="list-item" style="justify-content: center; color: var(--text-muted);">
                                <i class="fas fa-info-circle"></i>
                                No recent activity.
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($userActivity, 0, 10) as $a): ?>
                                <div class="list-item" style="border:none; padding:8px 0;">
                                    <div class="list-item-content">
                                        <i class="fas <?php echo ($a['type']==='add_list'?'fa-bookmark':($a['type']==='read_chapter'?'fa-book-open':'fa-bolt')); ?>"></i>
                                        <span>
                                            <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $a['series'] ?? ''))); ?>
                                            <?php if (!empty($a['chapter'])) echo ' • Ch. ' . htmlspecialchars($a['chapter']); ?>
                                        </span>
                                    </div>
                                    <span style="color:var(--text-muted); font-size:12px;"><?php echo htmlspecialchars($a['time'] ?? ''); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="MyList" class="tab-pane">
                <h3>My Reading List</h3>
                <div id="myListContainer">
                    <?php if (empty($userList)): ?>
                        <div class="list-item" style="justify-content: center; color: var(--text-muted);">
                            <i class="fas fa-info-circle"></i>
                            Your list is currently empty. Start reading now!
                        </div>
                    <?php else: ?>
                        <?php foreach ($userList as $item): ?>
                            <div class="list-item">
                                <div class="list-item-content">
                                    <i class="fas fa-book"></i>
                                    <a href="preview.php?series=<?php echo urlencode($item['series']); ?>&type=<?php echo urlencode($item['type']); ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                    <span style="margin-left: 10px;">(<?php echo htmlspecialchars(ucfirst($item['type'])); ?>)</span>
                                </div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="color:var(--primary-red); font-size:12px;">Added: <?php echo htmlspecialchars($item['added_at']); ?></span>
                                    <button class="nav-button btn-remove" data-series="<?php echo htmlspecialchars($item['series']); ?>" data-type="<?php echo htmlspecialchars($item['type']); ?>" style="padding:6px 10px;">Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isCreator): ?>
            <div id="CreatorStats" class="tab-pane">
                <h3>Creator Stats Dashboard</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                        <div class="stat-value"><?php echo $user['stories_published']; ?></div>
                        <div class="stat-label">Stories Published</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-value"><?php echo number_format($user['followers']); ?></div>
                        <div class="stat-label">Total Followers</div>
                    </div>
                    <div class="stat-card" style="border-top-color: var(--primary-green);">
                        <div class="stat-icon" style="color:var(--primary-green);"><i class="fas fa-dollar-sign"></i></div>
                        <div class="stat-value">$<?php echo number_format(rand(100, 9999) / 10, 2); ?></div>
                        <div class="stat-label">Est. Monthly Earnings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-value"><?php echo number_format(rand(10000, 50000)); ?></div>
                        <div class="stat-label">Total Views</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div id="Settings" class="tab-pane">
                <h3>Account Settings</h3>
                <div class="list-item">
                    <div class="list-item-content"><i class="fas fa-lock"></i> Change Password</div>
                    <i class="fas fa-chevron-right" style="color:var(--text-muted); margin-right: 0;"></i>
                </div>
                <div class="list-item">
                    <div class="list-item-content"><i class="fas fa-bell"></i> Notification Preferences</div>
                    <i class="fas fa-chevron-right" style="color:var(--text-muted); margin-right: 0;"></i>
                </div>
                <div class="list-item">
                    <div class="list-item-content"><i class="fas fa-user-shield"></i> Two-Factor Authentication</div>
                    <i class="fas fa-chevron-right" style="color:var(--text-muted); margin-right: 0;"></i>
                </div>
                <?php if ($isCreator): ?>
                <div class="list-item">
                    <div class="list-item-content"><i class="fas fa-credit-card" style="color:var(--primary-green);"></i> Payout Information</div>
                    <i class="fas fa-chevron-right" style="color:var(--text-muted); margin-right: 0;"></i>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        // --- JAVASCRIPT TAB FUNCTIONALITY ---
        function openTab(evt, tabName) {
            let i, tabcontent, tablinks;
            
            // Get all elements with class="tab-pane" and hide them
            tabcontent = document.getElementsByClassName("tab-pane");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove('active');
            }
            
            // Get all elements with class="tab-link" and remove the active class
            tablinks = document.getElementsByClassName("tab-link");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove('active');
            }
            
            // Show the current tab, and add an "active" class to the button that opened the tab
            document.getElementById(tabName).classList.add('active');
            evt.currentTarget.classList.add('active');
        }

        // Initialize: Ensure the default tab is active on load (redundant but safe)
        function goBackSafely(){
            try {
                if (document.referrer && window.history.length > 1) { window.history.back(); }
                else { window.location.href = 'index.php'; }
            } catch(e) { window.location.href = 'index.php'; }
        }

        async function fetchUserList(){
            try {
                const res = await fetch('list.php?action=get&t='+Date.now());
                const data = await res.json();
                if (data && data.status === 'success') {
                    const items = Array.isArray(data.list) ? data.list : [];
                    const tab = document.getElementById('myListTabLabel');
                    if (tab) tab.innerHTML = `<i class="fas fa-bookmark"></i> My List (${items.length})`;
                    const activityCount = document.getElementById('activityListCount');
                    if (activityCount) activityCount.textContent = items.length;
                    const container = document.getElementById('myListContainer');
                    if (container) {
                        if (!items.length) {
                            container.innerHTML = '<div class="list-item" style="justify-content: center; color: var(--text-muted);"><i class="fas fa-info-circle"></i> Your list is currently empty. Start reading now!</div>';
                        } else {
                            container.innerHTML = items.map(item => {
                                const series = encodeURIComponent(item.series||'');
                                const type = encodeURIComponent(item.type||'');
                                const title = (item.title||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                                const added = (item.added_at||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                                return `<div class="list-item">
                                    <div class="list-item-content">
                                        <i class="fas fa-book"></i>
                                        <a href="preview.php?series=${series}&type=${type}">${title}</a>
                                        <span style="margin-left: 10px;">(${(item.type||'').charAt(0).toUpperCase()+(item.type||'').slice(1)})</span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span style="color:var(--primary-red); font-size:12px;">Added: ${added}</span>
                                        <button class="nav-button btn-remove" data-series="${(item.series||'').replace(/"/g,'')}" data-type="${(item.type||'').replace(/"/g,'')}" style="padding:6px 10px;">Remove</button>
                                    </div>
                                </div>`;
                            }).join('');
                        }
                    }
                }
            } catch (e) {}
        }

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-remove');
            if (!btn) return;
            btn.disabled = true;
            try {
                const series = btn.getAttribute('data-series');
                const type = btn.getAttribute('data-type');
                const fd = new FormData(); fd.append('action','remove'); fd.append('series', series); fd.append('type', type);
                const res = await fetch('list.php', { method:'POST', body: fd });
                const json = await res.json();
                if (json && json.status === 'success') {
                    const afd = new FormData(); afd.append('action','log'); afd.append('type','remove_list'); afd.append('series', series); afd.append('content_type', type);
                    fetch('activity.php', { method:'POST', body: afd }).catch(()=>{});
                    fetchUserList();
                    fetchUserActivity();
                }
            } catch (err) {}
            finally { btn.disabled = false; }
        });

        async function fetchUserActivity(){
            try {
                const res = await fetch('activity.php?action=get&t='+Date.now());
                const data = await res.json();
                if (data && data.status === 'success') {
                    const acts = Array.isArray(data.activities) ? data.activities : [];
                    const feed = document.getElementById('activityFeed');
                    if (feed) {
                        if (!acts.length) {
                            feed.innerHTML = '<div class="list-item" style="justify-content: center; color: var(--text-muted);"><i class="fas fa-info-circle"></i> No recent activity.</div>';
                        } else {
                            feed.innerHTML = acts.slice(0,10).map(a => {
                                const icon = a.type==='add_list'?'fa-bookmark':(a.type==='read_chapter'?'fa-book-open':'fa-bolt');
                                const seriesName = (a.series||'').replace(/-/g,' ');
                                const chapter = a.chapter?` • Ch. ${a.chapter}`:'';
                                const time = (a.time||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                                return `<div class="list-item" style="border:none; padding:8px 0;">
                                    <div class="list-item-content">
                                        <i class="fas ${icon}"></i>
                                        <span>${seriesName}${chapter}</span>
                                    </div>
                                    <span style="color:var(--text-muted); font-size:12px;">${time}</span>
                                </div>`;
                            }).join('');
                        }
                    }
                }
            } catch(e) {}
        }

        document.addEventListener('DOMContentLoaded', () => {
             // Check URL hash for initial tab (e.g., profile.php#CreatorStats)
            const hash = window.location.hash.substring(1);
            const initialTab = hash || 'Activity';
            
            const defaultLink = document.querySelector(`.tab-link[onclick*="'${initialTab}'"]`);
            const defaultPane = document.getElementById(initialTab);

            if (defaultLink && defaultPane) {
                // Clear all active states first
                document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

                // Set the correct one active
                defaultLink.classList.add('active');
                defaultPane.classList.add('active');
            } else {
                // Fallback to 'Activity' if hash is invalid or missing
                document.getElementById("Activity").classList.add('active');
                document.querySelector(".tab-link").classList.add('active');
            }

            setTimeout(() => {
                const loaderBar = document.querySelector('.loader-bar');
                if (loaderBar) {
                    loaderBar.style.opacity = '0';
                    setTimeout(() => loaderBar.style.display = 'none', 300);
                }
            }, 300);

            fetchUserList();
            fetchUserActivity();
        });

        (function(){
            const btn = document.getElementById('mobileMenuBtn');
            const drawer = document.getElementById('mobileDrawer');
            const overlay = document.getElementById('drawerOverlay');
            const closeBtn = document.getElementById('drawerCloseBtn');
            function toggle(e){ if(e) e.preventDefault(); drawer.classList.toggle('open'); overlay.classList.toggle('open'); }
            if (btn) { btn.addEventListener('click', toggle); btn.addEventListener('touchstart', toggle, {passive:true}); }
            if (overlay) { overlay.addEventListener('click', toggle); overlay.addEventListener('touchstart', toggle, {passive:true}); }
            if (closeBtn) { closeBtn.addEventListener('click', toggle); closeBtn.addEventListener('touchstart', toggle, {passive:true}); }
            function ensureClosed(){ if (window.innerWidth >= 650) { if (drawer) drawer.classList.remove('open'); if (overlay) overlay.classList.remove('open'); } }
            ensureClosed();
            window.addEventListener('resize', ensureClosed);
        })();
    </script>
</body>
</html>

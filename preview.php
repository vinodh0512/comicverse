<?php
require_once __DIR__ . '/includes/session.php'; 

// --- 1. GET PARAMS & SECURITY ---
$baseDir = 'Book_data/';
$series = $_REQUEST['series'] ?? '';
$type = $_REQUEST['type'] ?? '';

// Sanitize inputs
$series = basename($series);
$type = basename($type);

// --- ROBUST CONTENT FINDER ---
// The URL type might differ from the folder type (e.g. URL says 'movie' but folder is 'webtoon')
// We check the requested type first, then fallback to others.
$possible_types = [$type, 'webtoon', 'movie', 'series', 'manga', 'comic'];
$seriesPath = '';
$realType = $type; // Default to requested type

foreach ($possible_types as $checkType) {
    if (empty($checkType)) continue;
    $testPath = $baseDir . $checkType . '/' . $series;
    if (is_dir($testPath)) {
        $seriesPath = $testPath;
        $realType = $checkType; // Update to the actual physical folder name
        break;
    }
}

// If still not found, redirect home
if (empty($seriesPath)) {
    header("Location: index.php");
    exit;
}

// Generate Comic ID based on the FOUND path to ensure consistency with Likes system
$comicId = sprintf('%u', crc32(strtolower($realType . '/' . $series)));
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['username'] ?? null;

// --- LIKES STORAGE ---
$likesDir = __DIR__ . '/user_data/likes';
$likesFile = $likesDir . '/' . $comicId . '.json';

$pdo = $pdo ?? null;

// Helper Functions
function load_likes($file){ if (!file_exists($file)) return ['users'=>[]]; $d = json_decode(@file_get_contents($file), true) ?: []; if (!isset($d['users']) || !is_array($d['users'])) $d = ['users'=>[]]; return $d; }
function save_likes($file, $data){ $dir = dirname($file); if (!is_dir($dir)) { @mkdir($dir, 0777, true); } @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX); }
function likes_count_fs($file){ $d = load_likes($file); return count($d['users']); }
function liked_by_me_fs($file, $userName){ if(!$userName) return false; $d = load_likes($file); return array_key_exists($userName, $d['users']); }

// --- AJAX LIKES HANDLER ---
if ((isset($_GET['ajax']) && $_GET['ajax'] === 'likes') || (isset($_POST['ajax']) && $_POST['ajax'] === 'likes')) {
    header('Content-Type: application/json');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    $action = $_REQUEST['action'] ?? 'status';
    $liked = liked_by_me_fs($likesFile, $userName);
    try {
        if ($action === 'toggle') {
            if (!$userName) { echo json_encode(['status'=>'login_required','liked'=>$liked,'count'=>likes_count_fs($likesFile)]); exit; }
            $data = load_likes($likesFile);
            if ($liked) { unset($data['users'][$userName]); $liked = false; }
            else { $data['users'][$userName] = date('Y-m-d H:i:s'); $liked = true; }
            save_likes($likesFile, $data);
        }
    } catch(Throwable $e){ }
    $count = likes_count_fs($likesFile);
    echo json_encode(['status'=>'success','liked'=>$liked,'count'=>$count]);
    exit;
}

$viewsFile = __DIR__ . '/views/views.json';
$j = @file_get_contents($viewsFile); $d = json_decode($j, true);
// Use $realType for stats lookup to match file structure
$totalViews = (int)($d[$realType][$series]['total'] ?? 0);
$likesCount = likes_count_fs($likesFile);
$likedInitial = liked_by_me_fs($likesFile, $userName);
$siteBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

// --- URL Helper ---
function toWebUrl($p, $siteBase) {
    if (!$p) return $p;
    if (preg_match('/^(https?:\/\/|data:)/i', $p)) return $p;
    // Ensure path starts with Book_data prefix if relative
    if (!preg_match('/^Book_data\//i', $p)) {
        $p = 'Book_data/' . ltrim($p, '/');
    }
    return rtrim($siteBase, '/') . '/' . ltrim($p, '/');
}

// --- 2. DEFAULT META ---
$meta = [
    'title' => str_replace('_', ' ', $series),
    'description' => 'Experience the latest content of this amazing series. Read now on ComicVerse.',
    'published' => 'Unknown',
    'cover' => 'https://via.placeholder.com/400x600?text=No+Cover', 
    'latest_chapter_num' => 1,
    'latest_chapter_title' => 'Chapter 1',
    'writer' => 'ComicVerse Creator'
];
$latestExternalUrl = '';

// --- 3. SCAN FOLDER FOR REAL DATA ---
if (is_dir($seriesPath)) {
    $chapters = scandir($seriesPath);
    $latestTime = 0;
    $foundContent = false;

    foreach ($chapters as $chapter) {
        if ($chapter === '.' || $chapter === '..') continue;
        
        $jsonPath = $seriesPath . '/' . $chapter . '/metadata.json';

        if (file_exists($jsonPath)) {
            $content = @file_get_contents($jsonPath);
            $data = json_decode($content, true);

            if ($data) {
                $foundContent = true;
                $dateStr = $data['status']['upload_date'] ?? 'now';
                $uploadTime = strtotime($dateStr);
                
                // Logic: Find the NEWEST upload
                if ($uploadTime >= $latestTime) {
                    $latestTime = $uploadTime;
                    
                    $meta['title'] = $data['meta']['series_name'] ?? $meta['title'];
                    $meta['latest_chapter_num'] = $data['meta']['chapter_number'] ?? $meta['latest_chapter_num'];
                    $meta['published'] = date('M d, Y', $uploadTime);
                    $meta['writer'] = $data['meta']['creator'] ?? $meta['writer'];
                    
                    // Handle Content Title
                    if (!empty($data['meta']['movie_title'])) {
                        $meta['latest_chapter_title'] = $data['meta']['movie_title'];
                    } elseif (!empty($data['meta']['chapter_title'])) {
                        $meta['latest_chapter_title'] = $data['meta']['chapter_title'];
                    } elseif (!empty($data['meta']['title'])) {
                         $meta['latest_chapter_title'] = $data['meta']['title'];
                    } else {
                        $meta['latest_chapter_title'] = 'Chapter ' . $meta['latest_chapter_num'];
                    }

                    // --- Capture External URL ---
                    $latestExternalUrl = $data['assets']['video_url'] ?? $data['assets']['archive_url'] ?? ''; 
                    
                    // --- Handle Cover Image ---
                    $coverPath = $data['assets']['thumbnail_path'] ?? $data['assets']['thumbnail'] ?? ''; 
                    if ($coverPath) {
                        $meta['cover'] = toWebUrl($coverPath, $siteBase);
                    }
                }
            }
        }
    }
    
    if (!$foundContent) { header("Location: index.php"); exit; }

} else {
    header("Location: index.php");
    exit;
}

// --- 4. GENERATE READ LINK & TARGET ---
$readLinkTarget = '_self'; 
$btnIcon = 'book-open';
$btnText = 'Read Now';

// Decide button style and link based on content
if (!empty($latestExternalUrl) && filter_var($latestExternalUrl, FILTER_VALIDATE_URL)) {
    // External Video/Link
    $readLink = $latestExternalUrl;
    $readLinkTarget = '_blank';
    $btnIcon = 'play';
    $btnText = 'Watch Now';
} else {
    // Internal Reader
    // Use $realType here to ensure the reader looks in the correct folder
    $readLink = "read.php?series=" . urlencode($series) . "&type=" . urlencode($realType) . "&chapter=" . urlencode($meta['latest_chapter_num']); 
}

// Logic check for explicit Movie types to set button text even if internal
if (strtolower($realType) === 'webtoon' || strtolower($realType) === 'movie' || strtolower($realType) === 'series') {
    $btnIcon = 'play';
    $btnText = 'Watch Now';
}

$isLoggedIn = is_logged_in();
$username = $_SESSION['username'] ?? 'Guest';

// --- Series Navigation Links (Using $realType) ---
$prevLink = null;
$nextLink = null;
$typePath = $baseDir . $realType;

if (is_dir($typePath)) {
    $seriesFolders = [];
    $entries = scandir($typePath);
    foreach ($entries as $s) {
        if ($s === '.' || $s === '..') continue;
        if (is_dir($typePath . '/' . $s)) $seriesFolders[] = $s;
    }
    if (!empty($seriesFolders)) {
        usort($seriesFolders, function($a, $b) { return strcasecmp($a, $b); });
        $idx = array_search($series, $seriesFolders);
        if ($idx !== false) {
            if ($idx > 0) $prevLink = "preview.php?series=" . urlencode($seriesFolders[$idx - 1]) . "&type=" . urlencode($realType);
            if ($idx < count($seriesFolders) - 1) $nextLink = "preview.php?series=" . urlencode($seriesFolders[$idx + 1]) . "&type=" . urlencode($realType);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($meta['title']); ?> | ComicVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* --- VARIABLES --- */
        :root {
            --primary: #ec1d24;
            --primary-red: #ec1d24;
            --bg-dark: #121212;
            --bg-card: #1a1a1a;
            --border-dark: #333;
            --text-light: #ffffff;
            --text-main: #ffffff;
            --text-muted: #aaa;
            --star-gold: #ffcc00;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { background-color: var(--bg-dark); color: var(--text-light); overflow-x: hidden; background-image: radial-gradient(circle at 10% 20%, rgba(236, 29, 36, 0.05) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(0, 120, 255, 0.05) 0%, transparent 40%); }
        a { text-decoration: none; color: white; transition: 0.3s; }

        /* --- NAVBAR --- */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 0 5%; height: 60px; background-color: #202020; border-bottom: 1px solid var(--border-dark); z-index: 1000; position: sticky; top: 0; }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: 1px; color: #fff; background-color: var(--primary); padding: 0 10px; height: 100%; display: flex; align-items: center; }

        .nav-links { display: flex; gap: 30px; margin-right: auto; margin-left: 30px; }
        .nav-links a { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #ccc; }
        .nav-links a:hover { color: var(--primary); }
        .mobile-menu-btn { display:none; background:none; border:none; color:#fff; font-size:22px; width:44px; height:44px; cursor:pointer; margin-left: 10px; }
        .drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); display:none; z-index:999; }
        .drawer-overlay.open { display:block; }
        .mobile-drawer { position:fixed; top:0; right:0; width:80%; max-width: 300px; height:100%; background:#1a1a1a; border-left:1px solid var(--border-dark); z-index:1000; transform: translateX(100%); transition: transform .2s ease-out; padding:20px; display:flex; flex-direction:column; gap:16px; }
        .mobile-drawer.open { transform: translateX(0); }
        .mobile-drawer a { color:#ccc; font-weight:700; text-transform:uppercase; padding:10px; border-bottom:1px solid #2a2a2a; }
        .drawer-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-dark); margin-bottom: 10px; }
        .drawer-close-btn { color: #fff; font-size: 24px; cursor: pointer; background:none; border:none; }

        .user-menu { display: flex; align-items: center; gap: 15px; }
        .user-text { font-size: 14px; font-weight: 700; }
        .user-menu a { color: var(--primary); font-weight: bold; font-size: 12px; cursor: pointer; text-transform: uppercase; border: 1px solid var(--primary); padding: 5px 10px; border-radius: 4px;}
        .user-menu a:hover { background: var(--primary); color: white; }

        /* --- SUB-HEADER (Breadcrumbs) --- */
        .sub-header { background-color: #111; height: 50px; display: flex; align-items: center; justify-content: space-between; padding: 0 5%; border-bottom: 1px solid var(--border-dark); text-transform: uppercase; font-size: 12px; font-weight: 700; letter-spacing: 1px; z-index: 50; position: relative; }
        .back-link i { margin-right: 8px; color: var(--primary); }
        .nav-controls { display: flex; gap: 16px; align-items: center; }
        .nav-controls a { color: #ccc; font-weight: 700; }
        .nav-controls a:hover { color: var(--primary); }
        .nav-controls .disabled { color: #555; font-weight: 700; }

        /* --- MAIN PREVIEW SECTION --- */
        .preview-container { position: relative; min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 60px 5%; overflow: hidden; }
        .bg-blur { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-size: cover; background-position: center; filter: blur(60px) brightness(0.3); z-index: 0; transform: scale(1.1); }
        .content-wrapper { position: relative; z-index: 10; display: flex; max-width: 1100px; width: 100%; gap: 60px; align-items: flex-start; opacity: 0; animation: fadeIn 0.8s ease-out forwards; }
        
        /* Cover Display */
        .cover-display { flex: 1; max-width: 350px; box-shadow: 0 20px 50px rgba(0,0,0,0.8); border: 1px solid rgba(255,255,255,0.1); }
        .cover-display img { width: 100%; display: block; }
        
        /* Info Display */
        .info-display { flex: 2; padding-top: 20px; }
        .comic-title { font-size: 42px; font-weight: 900; text-transform: uppercase; line-height: 1; margin-bottom: 30px; text-shadow: 2px 2px 10px rgba(0,0,0,0.5); }
        
        .meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin-bottom: 40px; border-top: 1px solid rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.1); padding: 20px 0; }
        .meta-item h4 { color: var(--text-muted); font-size: 11px; text-transform: uppercase; margin-bottom: 5px; font-weight: 700; letter-spacing: 0.5px; }
        
        /* Buttons & Actions */
        .action-buttons { display: flex; gap: 20px; align-items: center; margin-top: 10px; }
        .btn-read { 
            background-color: var(--primary); color: white; padding: 16px 45px; text-transform: uppercase; 
            font-weight: 800; font-size: 14px; letter-spacing: 1px; clip-path: polygon(10% 0, 100% 0, 100% 70%, 90% 100%, 0 100%, 0 30%); 
            transition: 0.3s; border: none; cursor: pointer; display: inline-block; 
            box-shadow: 0 0 15px rgba(236, 29, 36, 0.4); 
        }
        .btn-read:hover { background-color: #ff333b; transform: scale(1.05); }
        
        .btn-save { 
            background: transparent; border: 1px solid #555; color: white; padding: 10px 20px; 
            text-transform: uppercase; font-weight: 700; font-size: 12px; transition: 0.3s; 
            cursor: pointer; border-radius: 4px; display: flex; align-items: center; gap: 8px;
        }
        .btn-save:hover { border-color: var(--primary); color: var(--primary); background: rgba(236, 29, 36, 0.1); }
        
        .social-share { 
            font-size: 18px; color: #777; margin-left: auto; cursor: pointer; 
            width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; 
            border: 1px solid #333;
        }
        .social-share:hover { color: white; border-color: white; background: rgba(255,255,255,0.1); }
        .like-btn { background: transparent; border: 1px solid #444; color: #ff4d5a; padding: 10px 16px; text-transform: uppercase; font-weight: 700; font-size: 12px; border-radius: 4px; display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .like-btn.liked { background: rgba(255,77,90,0.1); border-color: #ff4d5a; }
        .like-count { color: #ccc; font-weight: 700; }

        /* --- REVIEW BLOCK --- */
        .review-section { 
            position: relative; z-index: 10; width: 100%; max-width: 1100px; 
            margin: 40px auto 60px; padding: 40px; 
            background: var(--bg-card); border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
            border: 1px solid var(--border-dark);
        }
        #ratingSummary { margin-bottom: 20px; padding: 10px 0 20px; border-bottom: 1px solid #292929; }
        .summary-header { display: flex; align-items: flex-end; gap: 15px; margin-bottom: 25px; padding-top: 5px; }
        .summary-average { color: var(--star-gold); font-weight: 900; font-size: 36px; display: flex; align-items: center; gap: 8px; text-shadow: 0 0 10px rgba(255, 204, 0, 0.4); }
        .summary-average .fas { font-size: 20px; }
        .summary-total { color: var(--text-muted); font-size: 14px; font-weight: 600; }
        .summary-row { display: flex; align-items: center; gap: 10px; margin: 8px 0; }
        .summary-star { width: 80px; color: var(--text-muted); font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 6px; }
        .summary-star .fas { color: var(--star-gold); font-size: 14px; }
        .summary-bar { flex: 1; height: 10px; background: #252525; border: 1px solid #3a3a3a; border-radius: 8px; overflow: hidden; }
        .summary-fill { height: 100%; background: linear-gradient(90deg, var(--primary), #ff6666); box-shadow: 0 0 5px rgba(236, 29, 36, 0.6); transition: width 0.5s ease-out; }
        .summary-pct { width: 80px; text-align: right; color: #bbb; font-size: 13px; font-weight: 500; }
        
        .rating-stars { font-size: 24px; color: var(--star-gold); margin-bottom: 15px; }
        .rating-stars .far { color: #555; cursor: pointer; transition: 0.2s; }
        .rating-stars .fas { text-shadow: 0 0 5px rgba(255, 204, 0, 0.5); }
        
        .review-form-group { display: flex; flex-direction: column; gap: 10px; }
        .review-textarea { background: #222; border: 1px solid #444; padding: 10px; color: #fff; border-radius: 4px; resize: vertical; min-height: 100px; outline: none; font-size: 14px; }
        .review-textarea:focus { border-color: var(--primary); }

        .btn-submit-review { align-self: flex-start; background: #00a652; color: white; padding: 10px 25px; border: none; border-radius: 4px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .login-prompt { color: #aaa; text-align: center; padding: 20px; border: 1px dashed #444; border-radius: 4px; }

        /* Reviews list */
        #reviewsList { margin-top: 25px; display: grid; gap: 14px; }
        .review-item { background:#191919; border:1px solid #2a2a2a; border-radius:8px; padding:14px; display:flex; gap:12px; align-items:flex-start; }
        .review-avatar { width:36px; height:36px; border-radius:50%; background:#252525; color:#fff; font-weight:800; display:flex; align-items:center; justify-content:center; }
        .review-content { flex:1; }
        .review-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
        .review-user { font-weight:800; color:#eee; }
        .review-time { color:#777; font-size:12px; }
        .review-text { color:#ccc; font-size:14px; line-height:1.5; }

        /* FOOTER */
        footer { background-color: #111; padding: 40px 5%; border-top: 1px solid var(--border-dark); text-align: center; font-size: 12px; color: #555; }
        @media (max-width: 768px) { 
            .nav-links { display:none; } 
            .mobile-menu-btn { display:block; } 
            .user-menu { display: none; }
            .btn-login { display: none; }
            .navbar { padding: 0 15px; }
            .content-wrapper { flex-direction: column; align-items: center; gap: 30px; } 
            .cover-display { max-width: 80%; }
            .info-display { padding-top: 0; text-align: center; }
            .comic-title { font-size: 32px; } 
            .meta-grid { grid-template-columns: 1fr; text-align: center; } 
            .action-buttons { justify-content: center; width: 100%; }
            .btn-read { flex: 1; padding: 14px 20px; }
            .btn-save { padding: 14px 20px; }
            .social-share { display: flex; margin-left: 0; }
            .login-prompt { display: none; }
        }
        @keyframes fadeIn { to { opacity: 1; } }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">CV</a> 
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="comic.php">Comics</a>
            <a href="manga.php">Manga</a>
            <a href="webtoon.php">Movies</a>
        </div>
        
        <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
        
        <?php if($isLoggedIn): ?>
            <div class="user-menu user-dropdown">
                <span class="user-name" style="cursor:pointer;">Hi, <?php echo htmlspecialchars($username); ?> <i class="fas fa-caret-down"></i></span>
                <div class="dropdown" style="display:none; position:absolute; top:60px; right:5%; background:#1a1a1a; border:1px solid var(--border-dark); border-radius:6px; min-width:160px; box-shadow:0 6px 20px rgba(0,0,0,0.5);">
                    <a href="profile.php" style="display:block; padding:10px 12px; color:#ccc;">Profile</a>
                    <a href="auth.php?action=logout" style="display:block; padding:10px 12px; color:#ccc;">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="auth-btn btn-login">LOG IN</a>
        <?php endif; ?>
    </nav>
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header">
            <span style="font-size: 20px; font-weight: 900; color: var(--primary);">Menu</span>
            <button class="drawer-close-btn" id="drawerCloseBtn">&times;</button>
        </div>
        <a href="index.php">Home</a>
        <a href="comic.php">Comics</a>
        <a href="manga.php">Manga</a>
        <a href="webtoon.php">Movies</a>
        <?php if($isLoggedIn): ?>
            <a href="profile.php">Profile</a>
            <?php if(($_SESSION['role'] ?? '') === 'creator'): ?>
                <a href="mystories.php">Dashboard</a>
            <?php endif; ?>
            <a href="auth.php?action=logout">Logout</a>
        <?php else: ?>
            <a href="login.php">Log In</a>
        <?php endif; ?>
        <div id="reviewsList"></div>
    </div>

    <div class="sub-header">
        <a href="index.php" class="back-link"><i class="fas fa-chevron-left"></i> Back to Series</a>
        <div class="nav-controls">
            <?php if ($prevLink): ?>
                <a href="<?php echo $prevLink; ?>"><i class="fas fa-chevron-left"></i> Prev</a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-left"></i> Prev</span>
            <?php endif; ?>
            <?php if ($nextLink): ?>
                <a href="<?php echo $nextLink; ?>">Next <i class="fas fa-chevron-right"></i></a>
            <?php else: ?>
                <span class="disabled">Next <i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="preview-container">
        <div class="bg-blur" style="background-image: url('<?php echo $meta['cover']; ?>');"></div>

        <div class="content-wrapper">
            <div class="cover-display">
                <img src="<?php echo $meta['cover']; ?>" alt="Cover Art">
            </div>

            <div class="info-display">
                <h1 class="comic-title"><?php echo htmlspecialchars($meta['title']); ?></h1>

                <div class="meta-grid">
                    <div class="meta-item">
                        <h4>Last Updated</h4>
                        <p><?php echo $meta['published']; ?></p>
                    </div>
                    <div class="meta-item">
                        <h4>Uploaded by</h4>
                        <p><?php echo $meta['writer']; ?></p>
                    </div>
                    <div class="meta-item">
                        <h4>Latest Content</h4>
                        <p><?php echo htmlspecialchars($meta['latest_chapter_title']); ?></p>
                    </div>
                    <div class="meta-item">
                        <h4>Format</h4>
                        <p><?php echo ucfirst($realType); ?></p>
                    </div>
                    <div class="meta-item">
                        <h4>Total Views</h4>
                        <p id="totalViewsText"><?php echo number_format($totalViews); ?></p>
                    </div>
                </div>

                <div class="description">
                    <p><?php echo $meta['description']; ?></p>
                </div>

                <div class="action-buttons">
                    <a href="<?php echo $readLink; ?>" class="btn-read" id="btnReadNow" target="<?php echo $readLinkTarget; ?>">
                        <i class="fas fa-<?php echo $btnIcon; ?>"></i>
                        <?php echo $btnText; ?>
                    </a>
                    
                    <button class="btn-save" id="addToListBtn"><i class="fas fa-plus"></i> Add to List</button>
                    <button class="like-btn" id="likeBtn">
                        <i class="<?php echo $likedInitial ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span class="like-count" id="likeCount"><?php echo number_format($likesCount); ?></span>
                    </button>
                    <div class="social-share" id="shareBtn"><i class="fas fa-share-alt"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="review-section">
        <h3><?php echo $isLoggedIn ? 'Write a Review' : 'Join the Discussion'; ?></h3>
        <div id="ratingSummary"></div>
        
        <?php if ($isLoggedIn): ?>
            <p style="color:#aaa; margin-bottom: 10px;">Posting as **<?php echo htmlspecialchars($username); ?>**</p>
            <form>
                <div class="rating-stars" id="ratingContainer">
                    </div>
                <div class="review-form-group">
                    <textarea class="review-textarea" placeholder="Share your thoughts on the series..."></textarea>
                    <button type="submit" class="btn-submit-review">Submit Review</button>
                </div>
            </form>
        <?php else: ?>
            <div class="login-prompt">
                Please <a href="login.php" style="color: var(--primary); font-weight: bold;">Log In</a> to leave a review and join the discussion!
            </div>
        <?php endif; ?>
        <div id="reviewsList"></div>
    </div>

    <footer>&copy; 2025 ComicVerse - <a href="termofservice.php">Terms of Service</a> | <a href="privacy_policy.php">Privacy Policy</a></footer>

    <script>
        // Data needed for JS logic
        const CURRENT_TYPE = <?php echo json_encode($realType); ?>; // Use real found type
        const CURRENT_SERIES = <?php echo json_encode($series); ?>;
        const LATEST_CHAPTER = <?php echo json_encode($meta['latest_chapter_num']); ?>;

        document.addEventListener('DOMContentLoaded', initializeRating);
        document.addEventListener('DOMContentLoaded', () => { 
            fetchSummary(); 
            setInterval(fetchSummary, 10000); 
            fetchViews(); 
            setInterval(fetchViews, 10000); 
            
            // View log on "Read/Watch Now" button click
            const btn = document.getElementById('btnReadNow');
            if (btn) {
                btn.addEventListener('click', async function(e){
                    // Log view for both internal and external content
                    try {
                        const url = `views/views_api.php?action=log&type=${encodeURIComponent(CURRENT_TYPE)}&series=${encodeURIComponent(CURRENT_SERIES)}&chapter=${encodeURIComponent(LATEST_CHAPTER)}`;
                        await fetch(url, { method:'POST' });
                    } catch(_){}
                });
            }
        });

        // Function to fetch and update view count
        async function fetchViews(){ 
            try { 
                const res = await fetch(`views/views_api.php?action=get_series&series=${encodeURIComponent(CURRENT_SERIES)}&type=${encodeURIComponent(CURRENT_TYPE)}`); 
                const j = await res.json(); 
                if (j && j.status==='success') { 
                    const el = document.getElementById('totalViewsText'); 
                    if (el) el.textContent = new Intl.NumberFormat().format(parseInt(j.total||0)); 
                } 
            } catch(e){} 
        }
        
        // --- MOBILE MENU TOGGLE ---
        function toggleMobileMenu() {
            const drawer = document.getElementById('mobileDrawer');
            const overlay = document.getElementById('drawerOverlay');
            drawer.classList.toggle('open');
            overlay.classList.toggle('open');
        }
        (function(){
            const menuBtn = document.getElementById('mobileMenuBtn');
            const overlay = document.getElementById('drawerOverlay');
            const closeBtn = document.querySelector('.drawer-close-btn');
            function onToggle(e){ if(e) e.preventDefault(); toggleMobileMenu(); }
            if (menuBtn) { menuBtn.addEventListener('click', onToggle); menuBtn.addEventListener('touchstart', onToggle, {passive:true}); }
            if (overlay) { overlay.addEventListener('click', onToggle); overlay.addEventListener('touchstart', toggle, {passive:true}); }
            if (closeBtn) { closeBtn.addEventListener('click', onToggle); closeBtn.addEventListener('touchstart', toggle, {passive:true}); }
            function ensureClosed(){ 
                if (window.innerWidth >= 768) { 
                    const drawer = document.getElementById('mobileDrawer'); 
                    const ov = document.getElementById('drawerOverlay'); 
                    if (drawer) drawer.classList.remove('open'); 
                    if (ov) ov.classList.remove('open'); 
                } 
            }
            ensureClosed();
            window.addEventListener('resize', ensureClosed);
        })();

        // User dropdown menu logic
        (function(){
            const dd = document.querySelector('.user-dropdown');
            if (!dd) return;
            const trigger = dd.querySelector('.user-name');
            const menu = dd.querySelector('.dropdown');
            function toggle(e){ if(e) e.preventDefault(); menu.style.display = (menu.style.display === 'block') ? 'none' : 'block'; }
            trigger.addEventListener('click', toggle);
            document.addEventListener('click', (e) => { if (!dd.contains(e.target)) { menu.style.display = 'none'; } });
        })();

        // Add/Remove from List Logic
        (function(){
            const btn = document.getElementById('addToListBtn');
            if (!btn) return;
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
            const series = CURRENT_SERIES;
            const type = CURRENT_TYPE;
            const title = <?php echo json_encode($meta['title']); ?>;
            let isAdded = false;
            async function refreshAddButton(){
                if (!isLoggedIn) return;
                try {
                    const res = await fetch('list.php?action=get&t='+Date.now());
                    const data = await res.json();
                    if (data && data.status === 'success' && Array.isArray(data.list)) {
                        const exists = data.list.some(i => (i.series===series && i.type===type));
                        isAdded = !!exists;
                        btn.innerHTML = exists ? '<i class="fas fa-check"></i> Added' : '<i class="fas fa-plus"></i> Add to List';
                        btn.disabled = false;
                    }
                } catch(e) {}
            }
            refreshAddButton();
            btn.addEventListener('click', async function(){
                if (!isLoggedIn) { window.location.href = 'login.php'; return; }
                btn.disabled = true; const prev = btn.innerHTML; btn.innerHTML = '<i class=\"fas fa-circle-notch fa-spin\"></i>'+(isAdded?' Removing':' Adding');
                try {
                    if (!isAdded) {
                        const fd = new FormData(); fd.append('action','add'); fd.append('series', series); fd.append('type', type); fd.append('title', title);
                        const res = await fetch('list.php', { method:'POST', body: fd });
                        const json = await res.json();
                        if (json.status === 'success') {
                            isAdded = true;
                            btn.innerHTML = '<i class="fas fa-check"></i> Added';
                            const afd = new FormData(); afd.append('action','log'); afd.append('type','add_list'); afd.append('series', series); afd.append('content_type', type); afd.append('title', title);
                            fetch('activity.php', { method:'POST', body: afd }).catch(()=>{});
                        } else { btn.innerHTML = prev; btn.disabled = false; alert(json.message || 'Failed to add'); }
                    } else {
                        const fd = new FormData(); fd.append('action','remove'); fd.append('series', series); fd.append('type', type);
                        const res = await fetch('list.php', { method:'POST', body: fd });
                        const json = await res.json();
                        if (json.status === 'success') {
                            isAdded = false;
                            btn.innerHTML = '<i class="fas fa-plus"></i> Add to List';
                            const afd = new FormData(); afd.append('action','log'); afd.append('type','remove_list'); afd.append('series', series); afd.append('content_type', type); afd.append('title', title);
                            fetch('activity.php', { method:'POST', body: afd }).catch(()=>{});
                        } else { btn.innerHTML = prev; btn.disabled = false; alert(json.message || 'Failed to remove'); }
                    }
                } catch(e) { btn.innerHTML = prev; btn.disabled = false; }
                finally { btn.disabled = false; }
            });
        })();

        // --- RATING SCRIPT ---
        function initializeRating() {
            const container = document.getElementById('ratingContainer');
            if (!container) return; 

            for (let i = 1; i <= 5; i++) {
                const star = document.createElement('i');
                star.className = 'far fa-star'; // Outline star initially
                star.dataset.value = i;
                star.addEventListener('mouseover', highlightStars);
                star.addEventListener('mouseout', resetStars);
                star.addEventListener('click', setRating);
                container.appendChild(star);
            }
        }

        let currentRating = 0;

        function highlightStars(e) {
            const value = parseInt(e.target.dataset.value);
            const stars = e.target.parentNode.children;
            for (let i = 0; i < 5; i++) {
                stars[i].className = (i < value) ? 'fas fa-star' : 'far fa-star';
            }
        }

        function resetStars(e) {
            setRatingVisuals(currentRating, e.target.parentNode.children);
        }
        
        function setRating(e) {
            const value = parseInt(e.target.dataset.value);
            currentRating = value;
            setRatingVisuals(value, e.target.parentNode.children);
        }

        function setRatingVisuals(rating, stars) {
             for (let i = 0; i < 5; i++) {
                stars[i].className = (i < rating) ? 'fas fa-star' : 'far fa-star';
            }
        }

        // --- REVIEW SUMMARY FETCH ---
        async function fetchSummary() {
            try {
                const res = await fetch(`reviews.php?series=<?php echo urlencode($series); ?>&type=<?php echo urlencode($type); ?>`);
                const json = await res.json();
                if (json.status !== 'success') return;
                renderSummary(json.summary);
                if (Array.isArray(json.reviews)) renderReviews(json.reviews);
            } catch(e) {
                console.error("Error fetching review summary:", e);
            }
        }

        function renderSummary(summary) {
            const counts = summary.counts || { '5':0,'4':0,'3':0,'2':0,'1':0 };
            const totalReviews = summary.total || 0;
            const avg = totalReviews > 0 ? Number(summary.average || 0).toFixed(1) : 'N/A';
            const container = document.getElementById('ratingSummary');

            const header = `
                <div class="summary-header">
                    <div class="summary-average"><i class="fas fa-star"></i> ${avg}</div>
                    <div class="summary-total">${totalReviews} reviews</div>
                </div>`;

            const rows = [5,4,3,2,1].map(star => {
                const pct = totalReviews > 0 ? (Math.round(((counts[String(star)] || 0) / totalReviews) * 100)) : 0;
                const cnt = counts[String(star)] || 0;
                return `
                    <div class="summary-row">
                        <div class="summary-star"><i class="fas fa-star"></i> ${star} Star</div>
                        <div class="summary-bar"><div class="summary-fill" style="width:${pct}%"></div></div>
                        <div class="summary-pct">${cnt} (${pct}%)</div>
                    </div>`;
            }).join('');

            container.innerHTML = header + rows;
        }

        function renderReviews(items){
            const wrap = document.getElementById('reviewsList');
            if (!wrap) return;
            if (!Array.isArray(items) || items.length === 0) { wrap.innerHTML = '<div class="no-content">No reviews yet.</div>'; return; }
            let html = '';
            for (const it of items){
                const name = (it.username || 'Guest');
                const av = name.charAt(0).toUpperCase();
                const stars = '<i class="fas fa-star" style="color:var(--star-gold);"></i>'.repeat(Math.max(0, Math.min(5, parseInt(it.rating||0))));
                const time = (it.timestamp || '');
                const text = (it.review || '').replace(/</g,'&lt;');
                html += `
                <div class="review-item">
                    <div class="review-avatar">${av}</div>
                    <div class="review-content">
                        <div class="review-header">
                            <div class="review-user">${name}</div>
                            <div class="review-time">${time}</div>
                        </div>
                        <div class="review-stars">${stars}</div>
                        ${text ? `<div class="review-text">${text}</div>` : ''}
                    </div>
                </div>`;
            }
            wrap.innerHTML = html;
        }

        // --- REVIEW SUBMISSION LOGIC ---
        const submitBtnEl = document.querySelector('.btn-submit-review');
        if (submitBtnEl) submitBtnEl.addEventListener('click', async function(e) {
            e.preventDefault();
            const reviewTextarea = document.querySelector('.review-textarea');
            const reviewText = reviewTextarea.value;
            const submitButton = this;
            
            if (currentRating === 0) {
                alert("Please select a rating before submitting.");
                return;
            }
            if (reviewText.trim().length < 10) {
                alert("Please write a review longer than 10 characters.");
                return;
            }
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Submitting...';
            
            // 1. Prepare data for the API
            const formData = new FormData();
            formData.append('series', '<?php echo $series; ?>');
            formData.append('type', '<?php echo $type; ?>');
            formData.append('rating', currentRating);
            formData.append('review', reviewText);

            try {
                const response = await fetch('reviews.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    // Update summary instantly after successful submission
                    fetchSummary(); 
                    submitButton.innerHTML = '<i class="fas fa-check"></i> Submitted';
                    submitButton.style.backgroundColor = '#00a652';
                    setTimeout(() => { 
                        submitButton.innerHTML = 'Review Submitted'; 
                        submitButton.style.backgroundColor = ''; 
                        submitButton.disabled = true; 
                        reviewTextarea.disabled = true; 
                    }, 1500);
                } else {
                    alert("Submission Failed: " + result.message);
                    submitButton.innerHTML = 'Submit Review';
                    submitButton.disabled = false;
                }

            } catch (error) {
                console.error("Network Error:", error);
                alert("An error occurred during submission.");
                submitButton.innerHTML = 'Submit Review';
                submitButton.disabled = false;
            }
        });
        
        // --- LIKE BUTTON LOGIC ---
        (function(){
            const btn = document.getElementById('likeBtn');
            if (!btn) return;
            const icon = btn.querySelector('i');
            const countEl = document.getElementById('likeCount');
            let pending = false;
            
            function update(liked, count){
                btn.classList.toggle('liked', !!liked);
                btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
                icon.className = (liked ? 'fas' : 'far') + ' fa-heart';
                countEl.textContent = (count||0).toLocaleString();
            }
            async function fetchStatus(){
                try {
                    const url = `preview.php?ajax=likes&action=status&series=<?php echo urlencode($series); ?>&type=<?php echo urlencode($type); ?>&t=${Date.now()}`;
                    const res = await fetch(url, { credentials: 'same-origin' });
                    const j = await res.json();
                    if (j && j.status === 'success') { update(j.liked, j.count); }
                } catch(e) {}
            }
            document.addEventListener('DOMContentLoaded', fetchStatus);
            setInterval(fetchStatus, 10000); // Polling for count updates
            
            btn.addEventListener('click', async function(){
                if (pending) return;
                pending = true; btn.disabled = true;
                try {
                    const fd = new FormData();
                    fd.append('ajax','likes');
                    fd.append('action','toggle');
                    fd.append('series','<?php echo htmlspecialchars($series, ENT_QUOTES); ?>');
                    fd.append('type','<?php echo htmlspecialchars($type, ENT_QUOTES); ?>');
                    const res = await fetch('preview.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                    const j = await res.json();
                    if (j && j.status === 'login_required') { window.location.href = 'login.php'; }
                    else if (j && j.status === 'success') { update(j.liked, j.count); }
                } catch(e) {}
                finally { pending = false; btn.disabled = false; }
            });
        })();
        
        // --- SHARE BUTTON LOGIC ---
        document.addEventListener('DOMContentLoaded', () => {
            const shareEl = document.getElementById('shareBtn');
            if (!shareEl) return;
            const shareUrl = window.location.href;
            const shareTitle = <?php echo json_encode($meta['title']); ?>;
            shareEl.addEventListener('click', async () => {
                try {
                    if (navigator.share) {
                        await navigator.share({ title: shareTitle, url: shareUrl });
                    } else {
                        await navigator.clipboard.writeText(shareUrl);
                        const prev = shareEl.innerHTML;
                        shareEl.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(() => { shareEl.innerHTML = prev; }, 1500);
                    }
                } catch(e) {}
            });
        });
    </script>
</body>
</html>
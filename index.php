<?php
require_once __DIR__ . '/includes/session.php'; // Assuming this file includes session_start() and is_logged_in()

// --- BACKEND ENGINE (AJAX RESPONDER) ---
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    error_reporting(0);
    $cacheDir = __DIR__ . '/cache';
    $cacheFile = $cacheDir . '/index_ajax.json';
    $ttl = 20;
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $ttl)) { readfile($cacheFile); exit; }
    
    // Using JSON views store
    $viewsFile = __DIR__ . '/views/views.json';
    function views_count_series($file, $type, $series){
        $j = @file_get_contents($file); $d = json_decode($j, true);
        if (!is_array($d)) return 0; $t = $d[$type][$series]['total'] ?? 0; return (int)$t;
    }

    $baseDir = 'Book_data/';
    $library = [];
    $siteBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    
    // Corrected and robust URL conversion function for thumbnails
    function toWebUrl($p, $siteBase) {
        if (!$p) return $p;
        if (preg_match('/^(https?:\/\/|data:)/i', $p)) return $p;
        
        // FIX: Ensure path starts with the Book_data/ prefix if it's relative
        if (!preg_match('/^Book_data\//i', $p)) {
            $p = 'Book_data/' . ltrim($p, '/');
        }
        
        return rtrim($siteBase, '/') . '/' . ltrim($p, '/');
    }

    function time_elapsed_string($datetime) {
        try {
            $now = new DateTime;
            $ago = new DateTime($datetime);
            $diff = $now->diff($ago);
            $string = array('y' => 'y', 'm' => 'mo', 'w' => 'w', 'd' => 'd', 'h' => 'h', 'i' => 'm');
            foreach ($string as $k => &$v) {
                if ($diff->$k) { $v = $diff->$k . $v; break; }
            }
            return is_string($v) ? $v . ' ago' : 'just now';
        } catch (Exception $e) { return 'just now'; }
    }

    function getReviewSummary($seriesPath) {
        $file = $seriesPath . '/reviews.json';
        if (!file_exists($file)) return ['average'=>0,'overall_percent'=>0];
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data) || !isset($data['ratings'])) return ['average'=>0,'overall_percent'=>0];
        $sum = 0; $total = 0;
        foreach ($data['ratings'] as $r) { $val = intval($r['rating'] ?? 0); if ($val>=1 && $val<=5) { $sum += $val; $total++; } }
        $avg = $total>0 ? ($sum/$total) : 0;
        $pct = $avg>0 ? round(($avg/5)*100) : 0;
        return ['average'=>$avg,'overall_percent'=>$pct];
    }

    if (is_dir($baseDir)) {
        $types = scandir($baseDir);
        foreach ($types as $type) { // $type = folder name (e.g., 'manga', 'movie', 'webtoon')
            if ($type === '.' || $type === '..') continue;
            $typePath = $baseDir . $type;
            if (is_dir($typePath)) {
                
                // FIX 2: Normalize Movie/Series types to 'webtoon' for frontend display grouping
                $displayType = $type;
                $typeLower = strtolower($type);
                if ($typeLower === 'movie' || $typeLower === 'series') {
                    $displayType = 'webtoon';
                }
                
                $seriesList = scandir($typePath);
                foreach ($seriesList as $seriesFolder) {
                    if ($seriesFolder === '.' || $seriesFolder === '..') continue;
                    $seriesPath = $typePath . '/' . $seriesFolder;
                    
                    if (is_dir($seriesPath)) {
                        $chapters = scandir($seriesPath);
                        foreach ($chapters as $chapter) {
                            if ($chapter === '.' || $chapter === '..') continue;
                            $jsonPath = $seriesPath . '/' . $chapter . '/metadata.json';
                            
                            if (file_exists($jsonPath)) {
                                $content = file_get_contents($jsonPath);
                                $data = json_decode($content, true);
                                
                                if ($data) {
                                    $dateStr = $data['status']['upload_date'] ?? 'now';
                                    $uploadTime = strtotime($dateStr);
                                    
                                    // Use the normalized type for the library key
                                    $seriesKey = $displayType . '_' . $seriesFolder; 

                                    if (!isset($library[$seriesKey]) || $uploadTime > $library[$seriesKey]['timestamp']) {
                                        $thumbSrc = 'https://via.placeholder.com/400x600?text=No+Cover';
                                        
                                        // Retrieve the single thumbnail path
                                        $coverPath = $data['assets']['thumbnail'] ?? $data['assets']['thumbnail_path'] ?? $data['assets']['thumbnail_base64'] ?? '';
                                        
                                        if (!empty($coverPath)) {
                                            $thumbSrc = toWebUrl($coverPath, $siteBase);
                                        } 

                                        $summary = getReviewSummary($seriesPath);
                                        $vcount = views_count_series($viewsFile, $type, $seriesFolder);
                                        
                                        $chapterDisplay = $data['meta']['chapter_number'] ?? 1;
                                        if (!empty($data['meta']['title'])) {
                                            $chapterDisplay = $data['meta']['title'];
                                        }

                                        $library[$seriesKey] = [
                                            'title' => $data['meta']['series_name'],
                                            'folder' => $seriesFolder,
                                            'type' => $displayType, // Uses 'webtoon' for movies/series
                                            'chapter_num' => $chapterDisplay,
                                            'timestamp' => $uploadTime,
                                            'time_ago' => time_elapsed_string($dateStr),
                                            'thumbnail' => $thumbSrc,
                                            'rating' => number_format($summary['average'], 1) . ' (' . $summary['overall_percent'] . '%)',
                                            'views' => $vcount,
                                            'badge' => (time() - $uploadTime < 172800) ? 'NEW' : 'UPDATED'
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $allSeries = array_values($library);
    usort($allSeries, function($a, $b) { return $b['timestamp'] - $a['timestamp']; });

    $payload = json_encode(['status' => 'success', 'data' => $allSeries]);
    echo $payload;
    if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0777, true); }
    @file_put_contents($cacheFile, $payload);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Read comics, manga, and webtoons on ComicVerse. Discover trending stories and latest chapters with fast performance and accessible design.">
    <link rel="canonical" href="http://localhost/comicverse/">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
    <title>ComicVerse | Read Comics, Manga, Movies</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></noscript>
    <link rel="preload" as="image" href="https://images.unsplash.com/photo-1612036782180-6f0b6cd846fe?q=80&w=1600&auto=format&fit=crop&fm=webp" fetchpriority="high">
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite","name":"ComicVerse","url":"http://localhost/comicverse/","potentialAction":{"@type":"SearchAction","target":"http://localhost/comicverse/searching.php?q={query}","query-input":"required name=query"}}</script>
    <style>
        /* --- VARIABLES --- */
        :root {
            --primary: #ec1d24;
            --primary-glow: rgba(236, 29, 36, 0.6);
            --bg-dark: #121212;
            --bg-card: #1e1e1e;
            --text-main: #ffffff;
            --text-dim: #aaaaaa;
        }

        /* --- BASE STYLES --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, sans-serif; }
        
        body { 
            background-color: var(--bg-dark); 
            color: var(--text-main); 
            overflow-x: hidden; 
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(236, 29, 36, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(0, 120, 255, 0.05) 0%, transparent 40%);
        }
        
        a { text-decoration: none; color: white; transition: 0.3s; }
        ul { list-style: none; }

        /* --- NAVBAR (Match preview.php) --- */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 0 5%; height: 60px; background-color: #202020; border-bottom: 1px solid #333; z-index: 100; position: relative; }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: 1px; color: #fff; background-color: var(--primary); padding: 0 10px; height: 100%; display: flex; align-items: center; }
        .nav-links { display: flex; gap: 30px; margin-right: auto; margin-left: 30px; }
        .nav-links a { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #ccc; }
        .nav-links a:hover { color: var(--primary); }

        /* --- BUTTONS & AUTH --- */
        .auth-btn { font-weight: 800; font-size: 13px; cursor: pointer; text-transform: uppercase; }
        .btn-login { 
            color: var(--primary); border: 1px solid var(--primary); padding: 8px 20px; border-radius: 4px; 
            transition: all 0.3s ease;
        }
        .btn-login:hover { 
            background: var(--primary); color: white; 
            box-shadow: 0 0 15px var(--primary-glow); 
        }
        .user-menu { display: flex; align-items: center; gap: 15px; font-size: 13px; }
        .user-dropdown { position: relative; }

        .mobile-menu-btn { display:none; background:none; border:none; color:#fff; font-size:22px; width:44px; height:44px; cursor:pointer; }
        .drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:none; display:none; z-index:999; }
        .mobile-drawer { position:fixed; top:0; right:0; width:80%; height:100%; background:#1a1a1a; border-left:1px solid #333; z-index:1000; transform: translateX(100%); transition: transform .2s ease-out; padding:20px; display:flex; flex-direction:column; gap:16px; will-change: transform; -webkit-overflow-scrolling: touch; }
        .mobile-drawer.open { transform: translateX(0); }
        .drawer-overlay.open { display:block; }
        .mobile-drawer a { color:#ccc; font-weight:700; text-transform:uppercase; padding:10px; border-bottom:1px solid #2a2a2a; }

        /* --- HERO --- */
        .hero { position: relative; height: 550px; display: flex; flex-direction: column; justify-content: center; padding: 0 5%; }
        .hero-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .hero-overlay { position: absolute; inset: 0; background: linear-gradient(to right, #121212 30%, rgba(18,18,18,0.7) 60%, rgba(18,18,18,0.1)); }
        /* Top and Bottom Fade for Hero */
        .hero::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 100px;
            background: linear-gradient(to top, var(--bg-dark), transparent);
        }

        .hero-content { max-width: 650px; animation: fadeIn 1s ease-in; position: relative; z-index: 2; }
        .hero h1 { font-size: 48px; font-weight: 900; text-transform: uppercase; margin-bottom: 12px; line-height: 1.1; }
        .hero p { font-size: 16px; color: #ddd; margin-bottom: 24px; }
        
        .hero-btn { 
            background-color: var(--primary); color: white; padding: 14px 34px; 
            font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            clip-path: polygon(10% 0, 100% 0, 100% 70%, 90% 100%, 0 100%, 0 30%); 
            display: inline-block; border: none; outline: none; transition: all 0.3s;
        }
        .hero-btn:hover { 
            background-color: #ff333b; transform: scale(1.05); 
            box-shadow: 0 0 25px var(--primary-glow); 
        }

        /* --- SEARCH --- */
        .search-container { margin-top: 20px; display: flex; gap: 10px; max-width: 500px; }
        .search-input { 
            flex: 1; padding: 12px 15px; background: rgba(0, 0, 0, 0.6); 
            border: 1px solid #444; color: white; font-size: 16px; outline: none; 
            backdrop-filter: blur(5px); transition: 0.3s;
        }
        .search-input:focus { 
            background: rgba(0, 0, 0, 0.8); border-color: var(--primary); 
            box-shadow: 0 0 10px rgba(236,29,36,0.2);
        }

        /* --- SECTIONS --- */
        .section-container { padding: 0 5%; margin-bottom: 50px; position: relative; z-index: 2; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-top: 20px; }
        .section-header h2 { 
            font-size: 26px; font-weight: 800; text-transform: uppercase; position: relative; padding-left: 15px; letter-spacing: 0.5px;
        }
        .section-header h2::before { 
            content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; 
            background-color: var(--primary); 
            box-shadow: 0 0 10px var(--primary); 
        }
        .see-all { font-size: 12px; color: var(--primary); cursor: pointer; text-transform: uppercase; font-weight: 700; transition: 0.3s; }
        .see-all:hover { color: #fff; text-shadow: 0 0 8px var(--primary); }

        /* --- GRID & CARDS (VISUAL UPGRADE) --- */
        .enhanced-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 25px; min-height: 200px; }
        
        .enhanced-card { 
            cursor: pointer;
            position: relative; border-radius: 6px; overflow: hidden; 
            background-color: var(--bg-card);
            border: 1px solid transparent; 
        }

        /* HOVER EFFECT - The "Lighting" Logic */
        .enhanced-card:hover { 
            border-color: rgba(236, 29, 36, 0.5); 
            box-shadow: 0 10px 40px -10px rgba(236, 29, 36, 0.3); 
        }
        
        .enhanced-card-image { 
            width: 100%; aspect-ratio: 2/3; background-color: #333; overflow: hidden; position: relative; 
        }
        /* Gradient Overlay on Image for better text contrast if we put text over it later */
        .enhanced-card-image::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 30%;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            opacity: 0.6;
        }

        .enhanced-card-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .enhanced-card-details { padding: 15px 12px; background: #1a1a1a; position: relative; z-index: 2; }
        .enhanced-card-title { 
            font-size: 15px; font-weight: 700; color: #fff; text-transform: uppercase; margin-bottom: 5px; 
            display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; line-clamp: 1; box-orient: vertical;
        }
        .enhanced-card:hover .enhanced-card-title { color: var(--primary); }
        
        .enhanced-card-sub { font-size: 12px; color: #888; margin-bottom: 10px; }
        .enhanced-card-meta { display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #777; }
        .rating { color: #ffcc00; text-shadow: 0 0 5px rgba(255, 204, 0, 0.4); }

        /* --- BADGES --- */
        .free-badge { 
            background: rgba(0, 166, 82, 0.1); color: #00a652; border: 1px solid #00a652;
            font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 3px; 
        }
        .views-badge { background: #222; color: #ccc; border: 1px solid #444; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 3px; }
        .badge-new, .badge-update { 
            position: absolute; top: 10px; left: 0; 
            font-size: 10px; font-weight: 800; padding: 4px 10px; 
            text-transform: uppercase; z-index: 5; 
            box-shadow: 2px 2px 10px rgba(0,0,0,0.7);
            clip-path: polygon(0 0, 100% 0, 90% 100%, 0% 100%);
        }
        .badge-new { background-color: var(--primary); color: white; }
        .badge-update { background-color: #0078ff; color: white; }

        /* --- FOOTER (FIXED & ENHANCED) --- */
        footer { 
            background-color: #050505; 
            padding: 70px 5% 30px; 
            margin-top: 80px; 
            position: relative; 
            border-top: 1px solid #222;
        }

        /* glowing top border line */
        footer::before {
            content: ''; position: absolute; top: -1px; left: 0; width: 100%; height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            box-shadow: 0 0 10px var(--primary);
        }

        .footer-content { 
            display: flex; 
            flex-wrap: wrap; 
            justify-content: space-between; 
            align-items: flex-start;
            gap: 40px; 
            margin-bottom: 50px;
        }

        /* Logo Column */
        .footer-brand { flex: 1; min-width: 250px; }
        .footer-logo { 
            font-size: 32px; font-weight: 900; color: #fff; 
            background: var(--primary); width: 60px; height: 60px; 
            display: flex; justify-content: center; align-items: center; 
            margin-bottom: 20px;
            box-shadow: 0 0 15px var(--primary-glow);
            clip-path: polygon(0 0, 100% 0, 90% 100%, 0% 100%);
        }
        .footer-desc { color: #888; font-size: 14px; line-height: 1.6; max-width: 300px; }

        /* Links Columns */
        .footer-links-group { display: flex; gap: 60px; flex-wrap: wrap; }
        .footer-column h4 { 
            font-size: 14px; font-weight: 800; color: #fff; 
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; 
            position: relative; display: inline-block;
        }
        /* Red dot next to headers */
        .footer-column h4::after {
            content: ''; position: absolute; right: -10px; bottom: 5px; 
            width: 4px; height: 4px; background: var(--primary); box-shadow: 0 0 5px var(--primary);
        }

        .footer-column ul li { margin-bottom: 12px; }
        .footer-column ul li a { 
            color: #999; font-size: 14px; transition: 0.3s; position: relative; left: 0;
        }
        .footer-column ul li a:hover { 
            color: #fff; left: 5px; text-shadow: 0 0 10px rgba(255,255,255,0.3);
        }

        /* Social Icons */
        .social-icons { display: flex; gap: 15px; margin-top: 20px; }
        .social-btn {
            width: 40px; height: 40px; border-radius: 50%; background: #222;
            display: flex; justify-content: center; align-items: center;
            color: #fff; transition: 0.3s; border: 1px solid #333;
        }
        .social-btn:hover { 
            background: var(--primary); border-color: var(--primary); 
            transform: translateY(-3px); box-shadow: 0 0 15px var(--primary-glow);
        }

        /* Copyright */
        .copyright { 
            text-align: center; color: #444; font-size: 12px; 
            padding-top: 30px; border-top: 1px solid #1a1a1a; 
        }
        .copyright a { color: #666; font-weight: 700; }
        .copyright a:hover { color: var(--primary); }

        /* --- UTILS --- */
        .loading-spinner { 
            grid-column: 1/-1; text-align: center; padding: 50px; color: var(--primary); font-size: 24px; 
            text-shadow: 0 0 10px var(--primary);
        }
        .no-content { grid-column: 1/-1; text-align: center; padding: 50px; color: #555; border: 1px dashed #333; border-radius: 8px; }

        .drawer-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid #2a2a2a; margin-bottom: 10px; }
        .drawer-close-btn { color: #fff; font-size: 24px; cursor: pointer; background: none; border: none; }

        .skeleton-card { position: relative; border-radius: 6px; overflow: hidden; background: #1a1a1a; }
        .skeleton-thumb { width: 100%; aspect-ratio: 2/3; background: #262626; }
        .skeleton-details { padding: 12px; }
        .skeleton-line { height: 12px; background: #2a2a2a; border-radius: 4px; margin-top: 8px; position: relative; overflow: hidden; }
        .skeleton-line::after { content: ''; position: absolute; left: -40%; top: 0; height: 100%; width: 40%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent); animation: shimmer 1.2s infinite; }
        @keyframes shimmer { 0% { left: -40%; } 100% { left: 100%; } }

        @media (max-width: 768px) { 
            .nav-links { display: none; } 
            .mobile-menu-btn { display:block; } 
            .btn-logout { display: none; } 
            .btn-login { display: none; } 
            .user-menu { margin-left: 10px; display:flex; gap:10px; } 
            .user-menu a { display:none; }
            .user-name { color:#fff; font-weight:700; } 
            .hero { height: 420px; }
            .hero h1 { font-size: 32px; } 
            .hero p { font-size: 14px; }
            .hero-btn { padding: 12px 28px; }
            .enhanced-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; } 
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo" aria-label="ComicVerse Home">CV</a> 
        <div class="nav-links" role="navigation" aria-label="Primary">
            <a href="index.php">Home</a>
            <a href="comic.php">Comics</a>
            <a href="manga.php">Manga</a>
            <a href="webtoon.php">Movies</a>
        </div>
        
        <?php if(isset($_SESSION['username'])): ?>
            <div class="user-menu user-dropdown">
                <span class="user-name" style="cursor:pointer;">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="fas fa-caret-down"></i></span>
                <div class="dropdown" style="display:none; position:absolute; top:calc(100% + 8px); right:0; background:#1a1a1a; border:1px solid #333; border-radius:6px; min-width:160px; box-shadow:0 6px 20px rgba(0,0,0,0.5); z-index:2001;">
                    <a href="profile.php" style="display:block; padding:10px 12px; color:#ccc;">Profile</a>
                    <?php if(($_SESSION['role'] ?? '') === 'creator'): ?>
                        <a href="mystories.php" style="display:block; padding:10px 12px; color:#ccc;">Dashboard</a>
                    <?php endif; ?>
                    <a href="auth.php?action=logout" style="display:block; padding:10px 12px; color:#ccc;">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="auth-btn btn-login">LOG IN</a>
        <?php endif; ?>

        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu"><i class="fas fa-bars" aria-hidden="true"></i></button>
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
        <?php if(isset($_SESSION['username'])): ?>
            <a href="profile.php">Profile</a>
            <?php if(($_SESSION['role'] ?? '') === 'creator'): ?>
                <a href="mystories.php">Dashboard</a>
            <?php endif; ?>
            <a href="auth.php?action=logout">Logout</a>
        <?php else: ?>
            <a href="login.php">Log In</a>
        <?php endif; ?>
    </div>
    <main>
    <section class="hero">
        <img class="hero-img" src="https://images.unsplash.com/photo-1612036782180-6f0b6cd846fe?q=80&w=1600&auto=format&fit=crop&fm=webp" alt="Comics, manga, and webtoons" decoding="async" fetchpriority="high">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Comics, Manga, Movies</h1>
            <p>Discover trending stories and read the latest chapters with a fast, accessible experience.</p>
            <a href="searching.php?q=popular" class="hero-btn" aria-label="Start reading popular titles">Start Reading</a>
            <div class="search-container">
                <input type="text" id="mainSearchInput" class="search-input" placeholder="Search comics, manga, movies..." aria-label="Search">
                <a href="#" class="hero-btn" style="padding: 12px 20px; box-shadow:none;" onclick="performSearch()" aria-label="Search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>

    <div class="section-container" style="margin-top: 40px;">
        <div class="section-header">
            <h2>Latest Updates</h2>
            <span class="see-all" onclick="window.location.href='latestupdates.php'">See All <i class="fas fa-chevron-right"></i></span>
        </div>
        <div class="enhanced-grid" id="grid-latest">
            <div class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading Library...</div>
        </div>
    </div>

    <div class="section-container" id="comics">
        <div class="section-header">
            <h2>Fresh Comics</h2>
            <span class="see-all" onclick="window.location.href='comic.php'">See All <i class="fas fa-chevron-right"></i></span>
        </div>
        <div class="enhanced-grid" id="grid-comic"></div>
    </div>

    <div class="section-container" id="manga">
        <div class="section-header">
            <h2>Popular Manga</h2>
            <span class="see-all" onclick="window.location.href='manga.php'">See All <i class="fas fa-chevron-right"></i></span>
        </div>
        <div class="enhanced-grid" id="grid-manga"></div>
    </div>

    <div class="section-container" id="webtoon">
        <div class="section-header">
            <h2>New Movies</h2>
            <span class="see-all" onclick="window.location.href='webtoon.php'">See All <i class="fas fa-chevron-right"></i></span>
        </div>
        <div class="enhanced-grid" id="grid-webtoon"></div>
    </div>
    </main>
    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <div class="footer-logo">CV</div>
                <p class="footer-desc">ComicVerse is your ultimate destination for unlimited comics, manga, and webtoons. Dive into a universe of stories and start creating today!</p>
            </div>
            <div class="footer-links-group">
                <div class="footer-column">
                    <h4>Explore</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="comic.php">Comics</a></li>
                        <li><a href="manga.php">Manga</a></li>
                        <li><a href="webtoon.php">Movies</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="help_center.php">Help Center</a></li>
                        <li><a href="termofservice.php">Terms of Service</a></li>
                        <li><a href="privacy_policy.php">Privacy Policy</a></li>
                        <li><a href="contact_us.php">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <div class="social-icons">
                <a href="#" class="social-btn" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                <a href="#" class="social-btn" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                <a href="#" class="social-btn" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                <a href="#" class="social-btn" aria-label="Discord"><i class="fab fa-discord" aria-hidden="true"></i></a>
            </div>
        </div>
        <div class="copyright">
            &copy; 2024 ComicVerse. All rights reserved. | Developed by <a href="uc.html">Unknown Creators</a>
        </div>
    </footer>

    <script>
        // --- FAST LIVE LOADER ---
        document.addEventListener('DOMContentLoaded', () => { renderSkeleton('grid-latest', 8); renderSkeleton('grid-comic', 8); renderSkeleton('grid-manga', 8); renderSkeleton('grid-webtoon', 8); fastBootstrap(); });

        function fastBootstrap(){
            const cacheKey = 'cv_index_cache';
            try {
                const cached = sessionStorage.getItem(cacheKey);
                if (cached) {
                    const obj = JSON.parse(cached);
                    if (obj && (Date.now() - obj.time) < 30000 && Array.isArray(obj.data)) {
                        renderGrid('grid-latest', obj.data.slice(0, 8));
                        const comics = obj.data.filter(s => s.type.toLowerCase()==='comic');
                        renderGrid('grid-comic', comics.slice(0, 8));
                        const manga = obj.data.filter(s => s.type.toLowerCase()==='manga');
                        renderGrid('grid-manga', manga.slice(0, 8));
                        const webtoons = obj.data.filter(s => s.type.toLowerCase()==='webtoon');
                        renderGrid('grid-webtoon', webtoons.slice(0, 8));
                    }
                }
            } catch(e) {}
            fetchContent();
            setInterval(fetchContent, 20000);
        }

        function renderSkeleton(targetId, count) {
            const container = document.getElementById(targetId);
            if (!container) return;
            const items = [];
            for (let i = 0; i < count; i++) {
                items.push('<div class="skeleton-card"><div class="skeleton-thumb"></div><div class="skeleton-details"><div class="skeleton-line" style="width:80%"></div><div class="skeleton-line" style="width:60%"></div></div></div>');
            }
            container.innerHTML = items.join('');
        }

        let fetchCtl;
        async function fetchContent() {
            try {
                if (fetchCtl) fetchCtl.abort();
                fetchCtl = new AbortController();
                const response = await fetch('index.php?ajax=1&t=' + Date.now(), { signal: fetchCtl.signal });
                const result = await response.json();

                if (result.status === 'success') {
                    const stories = result.data;
                    
                    // 1. Latest (First 8 of all)
                    renderGrid('grid-latest', stories.slice(0, 8));

                    // 2. Comics
                    const comics = stories.filter(s => s.type.toLowerCase() === 'comic');
                    renderGrid('grid-comic', comics.slice(0, 8));

                    // 3. Manga
                    const manga = stories.filter(s => s.type.toLowerCase() === 'manga');
                    renderGrid('grid-manga', manga.slice(0, 8));

                    // 4. Movies (Note: PHP backend must normalize 'movie' and 'series' types to 'webtoon')
                    const webtoons = stories.filter(s => s.type.toLowerCase() === 'webtoon');
                    renderGrid('grid-webtoon', webtoons.slice(0, 8));
                    
                    try { sessionStorage.setItem('cv_index_cache', JSON.stringify({ time: Date.now(), data: stories })); } catch(e) {}
                }
            } catch (error) {
                console.error(error);
                document.getElementById('grid-latest').innerHTML = '<div class="no-content">Library Empty or Error.</div>';
            }
        }

        function renderGrid(targetId, items) {
            const container = document.getElementById(targetId);
            if (!container) return;
            const skeletons = container.querySelectorAll('.skeleton-card');
            if (skeletons.length) skeletons.forEach(s => s.remove());

            if (items.length === 0) {
                if (!container.querySelector('.no-content')) {
                    container.innerHTML = '<div class="no-content">Coming Soon.</div>';
                }
                return;
            }

            const existing = new Map(Array.from(container.children).map(el => [el.getAttribute('data-key'), el]));
            const orderKeys = [];

            items.forEach(story => {
                const key = `${story.type}/${story.folder}`;
                orderKeys.push(key);
                const link = `preview.php?series=${encodeURIComponent(story.folder)}&type=${encodeURIComponent(story.type)}`;
                const badgeClass = story.badge === 'NEW' ? 'badge-new' : 'badge-update';

                let card = existing.get(key);
                if (!card) {
                    card = document.createElement('div');
                    card.className = 'enhanced-card';
                    card.setAttribute('data-key', key);
                    card.onclick = () => { window.location.href = link; };
                    const badge = document.createElement('div');
                    badge.className = badgeClass;
                    badge.textContent = story.badge;
                    const imgWrap = document.createElement('div');
                    imgWrap.className = 'enhanced-card-image';
                    const img = document.createElement('img');
                    img.className = 'lazy-img';
                    img.setAttribute('data-src', story.thumbnail);
                    img.src = 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
                    img.alt = 'Cover';
                    imgWrap.appendChild(img);
                    const details = document.createElement('div');
                    details.className = 'enhanced-card-details';
                    const titleEl = document.createElement('div');
                    titleEl.className = 'enhanced-card-title';
                    titleEl.textContent = escapeHtml(story.title);
                    const subEl = document.createElement('div');
                    subEl.className = 'enhanced-card-sub';
                    // Note: If story.chapter_num is a title (like for a movie), update this display logic:
                    subEl.textContent = `${typeof story.chapter_num === 'string' ? story.chapter_num : 'Ch. ' + story.chapter_num} • ${story.time_ago}`;
                    const metaEl = document.createElement('div');
                    metaEl.className = 'enhanced-card-meta';
                    const ratingEl = document.createElement('span');
                    ratingEl.className = 'rating';
                    ratingEl.innerHTML = `<i class=\"fas fa-star\"></i> ${story.rating}`;
                    const viewsEl = document.createElement('span');
                    viewsEl.className = 'views-badge';
                    viewsEl.innerHTML = `<i class=\"fas fa-eye\"></i> ${story.views}`;
                    metaEl.appendChild(ratingEl);
                    metaEl.appendChild(viewsEl);
                    details.appendChild(titleEl);
                    details.appendChild(subEl);
                    details.appendChild(metaEl);

                    card.appendChild(badge);
                    card.appendChild(imgWrap);
                    card.appendChild(details);
                    container.appendChild(card);
                } else {
                    card.onclick = () => { window.location.href = link; };
                    let badge = card.querySelector('.badge-new, .badge-update');
                    if (!badge) { badge = document.createElement('div'); card.insertBefore(badge, card.firstChild); }
                    badge.className = badgeClass;
                    badge.textContent = story.badge;
                    const img = card.querySelector('.enhanced-card-image img');
                    if (img) {
                        const current = img.getAttribute('data-src') || img.getAttribute('src');
                        if (current !== story.thumbnail) {
                            img.setAttribute('data-src', story.thumbnail);
                            img.src = 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
                        }
                    }
                    const titleEl = card.querySelector('.enhanced-card-title');
                    if (titleEl) titleEl.textContent = escapeHtml(story.title);
                    const subEl = card.querySelector('.enhanced-card-sub');
                    if (subEl) subEl.textContent = `${typeof story.chapter_num === 'string' ? story.chapter_num : 'Ch. ' + story.chapter_num} • ${story.time_ago}`;
                    const ratingEl = card.querySelector('.rating');
                    if (ratingEl) ratingEl.innerHTML = `<i class=\"fas fa-star\"></i> ${story.rating}`;
                    const viewsEl = card.querySelector('.views-badge');
                    if (viewsEl) viewsEl.innerHTML = `<i class=\"fas fa-eye\"></i> ${story.views}`;
                }
            });

            Array.from(container.children).forEach(child => {
                const key = child.getAttribute('data-key');
                if (key && !orderKeys.includes(key)) child.remove();
            });

            orderKeys.forEach(k => {
                const el = container.querySelector(`[data-key="${CSS.escape(k)}"]`);
                if (el) container.appendChild(el);
            });

            initLazyImages(container);
        }

        // Search Logic
        function performSearch() {
            const query = document.getElementById('mainSearchInput').value.trim();
            if (query) {
                window.location.href = `searching.php?q=${encodeURIComponent(query)}`;
            }
        }

        document.getElementById('mainSearchInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        (function(){
            const btn = document.getElementById('mobileMenuBtn');
            const drawer = document.getElementById('mobileDrawer');
            const overlay = document.getElementById('drawerOverlay');
            const closeBtn = document.getElementById('drawerCloseBtn');
            function toggle(e){ if(e) e.preventDefault(); drawer.classList.toggle('open'); overlay.classList.toggle('open'); }
            if (btn) { btn.addEventListener('click', toggle); btn.addEventListener('touchstart', toggle, {passive:true}); }
            if (overlay) { overlay.addEventListener('click', toggle); overlay.addEventListener('touchstart', toggle, {passive:true}); }
            if (closeBtn) { closeBtn.addEventListener('click', toggle); closeBtn.addEventListener('touchstart', toggle, {passive:true}); }
            function ensureClosed(){ if (window.innerWidth >= 768) { if (drawer) drawer.classList.remove('open'); if (overlay) overlay.classList.remove('open'); } }
            ensureClosed();
            window.addEventListener('resize', ensureClosed);
        })();

        (function(){
            const dd = document.querySelector('.user-dropdown');
            if (!dd) return;
            const trigger = dd.querySelector('.user-name');
            const menu = dd.querySelector('.dropdown');
            function toggle(e){ if(e) e.preventDefault(); menu.style.display = (menu.style.display === 'block') ? 'none' : 'block'; }
            trigger.addEventListener('click', toggle);
            trigger.addEventListener('touchstart', toggle, {passive:true});
            document.addEventListener('click', (e) => { if (!dd.contains(e.target)) { menu.style.display = 'none'; } });
        })();

        let io;
        function initLazyImages(root){
            const images = root.querySelectorAll('img.lazy-img[data-src]');
            if (!('IntersectionObserver' in window)) {
                images.forEach(img => { img.src = img.getAttribute('data-src'); });
                return;
            }
            if (!io) io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.getAttribute('data-src');
                        if (src) { img.src = src; img.removeAttribute('data-src'); io.unobserve(img); }
                    }
                });
            }, { rootMargin: '200px 0px' });
            images.forEach(img => io.observe(img));
        }
    </script>
</body>
</html>
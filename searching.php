<?php
require_once __DIR__ . '/includes/session.php';
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    error_reporting(0);

    $baseDir = 'Book_data/';
    $library = [];
    $siteBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    function toWebUrl($p, $siteBase) {
        if (!$p) return $p;
        if (preg_match('/^(https?:\/\/|data:)/i', $p)) return $p;
        $p = ltrim($p, '/');
        return ($siteBase ? $siteBase : '') . '/' . $p;
    }

    $rawQuery = $_GET['q'] ?? '';
    $searchQuery = strtolower(trim($rawQuery));
    $searchTerms = array_filter(explode(' ', $searchQuery));

    function time_elapsed_string($datetime) {
        try { $now = new DateTime; $ago = new DateTime($datetime); $diff = $now->diff($ago); $map = ['y'=>'y','m'=>'mo','w'=>'w','d'=>'d','h'=>'h','i'=>'m']; foreach ($map as $k=>&$v) { if ($diff->$k) { $v = $diff->$k . $v; break; } } return is_string($v) ? $v . ' ago' : 'just now'; } catch (Exception $e) { return 'just now'; }
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

    $viewsFile = __DIR__ . '/views/views.json';
    function views_count_series($file, $type, $series){ $j=@file_get_contents($file); $d=json_decode($j,true); if(!is_array($d)) return 0; return (int)($d[$type][$series]['total'] ?? 0); }

    if (is_dir($baseDir)) {
        $types = scandir($baseDir);
        foreach ($types as $type) {
            if ($type === '.' || $type === '..') continue;
            $typePath = $baseDir . $type;
            if (!is_dir($typePath)) continue;
            $seriesList = scandir($typePath);
            foreach ($seriesList as $seriesFolder) {
                if ($seriesFolder === '.' || $seriesFolder === '..') continue;

                $cleanTitle = str_replace('_', ' ', $seriesFolder);
                $lowerTitle = strtolower($cleanTitle);
                $score = 0;
                if ($searchQuery !== '' && strpos($lowerTitle, $searchQuery) !== false) { $score += 50; }
                foreach ($searchTerms as $term) { if (strpos($lowerTitle, $term) !== false) { $score += 10; } }
                if ($score === 0) continue;

                $seriesPath = $typePath . '/' . $seriesFolder;
                if (!is_dir($seriesPath)) continue;
                $chapters = scandir($seriesPath);
                foreach ($chapters as $chapter) {
                    if ($chapter === '.' || $chapter === '..') continue;
                    $jsonPath = $seriesPath . '/' . $chapter . '/metadata.json';
                    if (!file_exists($jsonPath)) continue;
                    $content = file_get_contents($jsonPath);
                    $data = json_decode($content, true);
                    if (!$data) continue;

                    $dateStr = $data['status']['upload_date'] ?? 'now';
                    $uploadTime = strtotime($dateStr);
                    $seriesKey = $type . '_' . $seriesFolder;

                    if (!isset($library[$seriesKey]) || $uploadTime > $library[$seriesKey]['timestamp']) {
                        $thumbSrc = 'https://via.placeholder.com/400x600?text=No+Cover';
                        if (!empty($data['assets']['thumbnail_base64'])) {
                            $thumbSrc = $data['assets']['thumbnail_base64'];
                        } elseif (!empty($data['assets']['thumbnail'])) {
                            $tn = trim($data['assets']['thumbnail'],'/');
                            if (preg_match('/^Book_data\//i', $tn)) { $thumbSrc = toWebUrl($tn, $siteBase); }
                            else { $thumbSrc = toWebUrl($baseDir . $tn, $siteBase); }
                        } elseif (!empty($data['assets']['thumbnail_path'])) {
                            $tn = trim($data['assets']['thumbnail_path'],'/');
                            if (preg_match('/^Book_data\//i', $tn)) { $thumbSrc = toWebUrl($tn, $siteBase); }
                            else { $thumbSrc = toWebUrl($baseDir . $tn, $siteBase); }
                        } else {
                            $pages = $data['assets']['pages_files'] ?? [];
                            $pp = $data['assets']['path_to_pages'] ?? '';
                            if (is_array($pages) && count($pages) > 0) {
                                $ppTrim = trim($pp, '/');
                                if ($ppTrim) {
                                    if (preg_match('/^Book_data\//i', $ppTrim)) { $thumbSrc = toWebUrl($ppTrim . '/' . $pages[0], $siteBase); }
                                    else { $thumbSrc = toWebUrl($baseDir . $ppTrim . '/' . $pages[0], $siteBase); }
                                } else {
                                    $thumbSrc = toWebUrl($baseDir . $type . '/' . $seriesFolder . '/' . $chapter . '/' . $pages[0], $siteBase);
                                }
                            }
                        }

                        $summary = getReviewSummary($seriesPath);
                        $vcount = views_count_series($viewsFile, $type, $seriesFolder);
                        $library[$seriesKey] = [
                            'title' => $data['meta']['series_name'],
                            'folder' => $seriesFolder,
                            'type' => $type,
                            'chapter_num' => $data['meta']['chapter_number'],
                            'timestamp' => $uploadTime,
                            'time_ago' => time_elapsed_string($dateStr),
                            'thumbnail' => $thumbSrc,
                            'rating' => number_format($summary['average'], 1) . ' (' . $summary['overall_percent'] . '%)',
                            'views' => $vcount,
                            'score' => $score
                        ];
                    }
                }
            }
        }
    }

    $allSeries = array_values($library);
    usort($allSeries, function($a, $b) { if ($a['score'] === $b['score']) { return $b['timestamp'] - $a['timestamp']; } return $b['score'] - $a['score']; });
    echo json_encode(['status' => 'success', 'data' => $allSeries]);
    exit;
}

$query = $_GET['q'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?php echo htmlspecialchars($query); ?> | ComicVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        body { background-color: #151515; color: #ffffff; overflow-x: hidden; min-height: 100vh; display: flex; flex-direction: column; }
        a { text-decoration: none; color: white; transition: 0.3s; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 0 5%; height: 60px; background-color: #202020; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 1000; }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: 1px; color: #fff; background-color: #ec1d24; padding: 0 10px; height: 100%; display: flex; align-items: center; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #ccc; }
        .nav-links a:hover { color: #ec1d24; }
        .mobile-menu-btn { display:none; background:none; border:none; color:#fff; font-size:22px; width:44px; height:44px; cursor:pointer; margin-left: 10px; }
        .drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); display:none; z-index:999; }
        .drawer-overlay.open { display:block; }
        .mobile-drawer { position:fixed; top:0; right:0; width:80%; max-width: 300px; height:100%; background:#1a1a1a; border-left:1px solid #333; z-index:1000; transform: translateX(100%); transition: transform .2s ease-out; padding:20px; display:flex; flex-direction:column; gap:16px; }
        .mobile-drawer.open { transform: translateX(0); }
        .mobile-drawer a { color:#ccc; font-weight:700; text-transform:uppercase; padding:10px; border-bottom:1px solid #2a2a2a; }
        .mobile-drawer a:hover { color:#ec1d24; }
        .drawer-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid #333; margin-bottom: 10px; }
        .drawer-close-btn { color: #fff; font-size: 24px; cursor: pointer; background:none; border:none; }
        .btn-login { color: #ec1d24; font-weight: bold; font-size: 12px; cursor: pointer; text-transform: uppercase; }
        .btn-login:hover { text-decoration: underline; }
        .user-menu { display: flex; align-items: center; gap: 15px; font-size: 13px; font-weight: bold; position: relative; }
        .user-name { color: #fff; text-transform: uppercase; cursor: pointer; }
        .dropdown { display:none; position:absolute; top:60px; right:5%; background:#1a1a1a; border:1px solid #333; border-radius:6px; min-width:160px; box-shadow:0 6px 20px rgba(0,0,0,0.5); }
        .dropdown a { display:block; padding:10px 12px; color:#ccc; }
        .dropdown a:hover { color:#fff; background:#222; }
        .page-header { padding: 40px 5%; background: linear-gradient(to bottom, #202020, #151515); border-bottom: 1px solid #333; margin-bottom: 30px; }
        .page-header h1 { font-size: 32px; font-weight: 800; text-transform: uppercase; }
        .page-header p { color: #888; margin-top: 5px; }
        .header-search { margin-top: 20px; display: flex; gap: 10px; max-width: 500px; }
        .search-input { flex: 1; padding: 12px 15px; background: rgba(255, 255, 255, 0.1); border: 1px solid #444; color: white; font-size: 16px; outline: none; border-radius: 4px; }
        .search-input:focus { background: rgba(255, 255, 255, 0.2); border-color: #ec1d24; }
        .search-btn { background-color: #ec1d24; color: white; padding: 12px 25px; font-weight: 700; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
        .search-btn:hover { background-color: #ff333b; }
        .container { padding: 0 5%; flex: 1; }
        .enhanced-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 25px; min-height: 300px; }
        .enhanced-card { cursor: pointer; transition: transform 0.3s ease; position: relative; border-radius: 4px; overflow: hidden; animation: fadeIn 0.5s ease; }
        .enhanced-card:hover { transform: translateY(-10px); }
        .enhanced-card-image { width: 100%; aspect-ratio: 2/3; background-color: #333; overflow: hidden; position: relative; }
        .enhanced-card-image img { width: 100%; height: 100%; object-fit: cover; }
        .enhanced-card-details { padding: 12px 8px 8px; background: rgba(32, 32, 32, 0.8); }
        .enhanced-card-title { font-size: 14px; font-weight: 700; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
        .enhanced-card-sub { font-size: 12px; color: #999; margin-bottom: 8px; }
        .enhanced-card-meta { display: flex; justify-content: space-between; font-size: 11px; color: #777; }
        .rating { color: #ffcc00; }
        .views-badge { background-color: #222; color: #ccc; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 3px; border: 1px solid #444; }
        .loading-spinner { grid-column: 1/-1; text-align: center; padding: 50px; color: #ec1d24; font-size: 24px; }
        .no-content { grid-column: 1/-1; text-align: center; padding: 50px; color: #666; border: 2px dashed #333; border-radius: 8px; }
        footer { background-color: #111; padding: 40px 5%; border-top: 1px solid #333; text-align: center; font-size: 12px; color: #555; margin-top: auto; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .mobile-menu-btn { display:block; }
            .user-menu, .btn-login { display:none; }
            .page-header { padding: 20px 5%; }
            .header-search { max-width: 100%; }
            .enhanced-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; }
        }
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
        <?php if(isset($_SESSION['username'])): ?>
            <div class="user-menu user-dropdown">
                <span class="user-name">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="fas fa-caret-down"></i></span>
                <div class="dropdown">
                    <a href="profile.php">Profile</a>
                    <?php if(($_SESSION['role'] ?? '') === 'creator'): ?>
                        <a href="mystories.php">Dashboard</a>
                    <?php endif; ?>
                    <a href="auth.php?action=logout">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-login">LOG IN</a>
        <?php endif; ?>
    </nav>

    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header">
            <span style="font-size: 20px; font-weight: 900; color: #ec1d24;">Menu</span>
            <button class="drawer-close-btn" id="drawerCloseBtn">&times;</button>
        </div>
        <a href="index.php">Home</a>
        <a href="comic.php">Comics</a>
        <a href="manga.php">Manga</a>
        <a href="webtoon.php">Movies</a>
        <?php if(isset($_SESSION['username'])): ?>
            <a href="profile.php">Profile</a>
            <a href="auth.php?action=logout">Logout</a>
        <?php else: ?>
            <a href="login.php">Log In</a>
        <?php endif; ?>
    </div>

    <div class="page-header">
        <h1>Search Results</h1>
        <p>Results for "<strong><?php echo htmlspecialchars($query); ?></strong>"</p>
        <div class="header-search">
            <input type="text" id="searchInput" class="search-input" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search again...">
            <a href="#" class="search-btn" onclick="performSearch()"><i class="fas fa-search"></i></a>
        </div>
    </div>

    <div class="container">
        <div class="enhanced-grid" id="resultsGrid">
            <div class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Searching...</div>
        </div>
    </div>

    <footer>&copy; 2025 ComicVerse</footer>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('q') || '';
        function toggleMobileMenu(){
            document.getElementById('mobileDrawer').classList.toggle('open');
            document.getElementById('drawerOverlay').classList.toggle('open');
        }
        document.addEventListener('DOMContentLoaded', () => {
            if(searchQuery) fetchResults(searchQuery);
            else document.getElementById('resultsGrid').innerHTML = '<div class="no-content">Type something to search.</div>';
            const btn = document.getElementById('mobileMenuBtn');
            const overlay = document.getElementById('drawerOverlay');
            const closeBtn = document.getElementById('drawerCloseBtn');
            if (btn) { btn.addEventListener('click', toggleMobileMenu); btn.addEventListener('touchstart', toggleMobileMenu, {passive:true}); }
            if (overlay) { overlay.addEventListener('click', toggleMobileMenu); overlay.addEventListener('touchstart', toggleMobileMenu, {passive:true}); }
            if (closeBtn) { closeBtn.addEventListener('click', toggleMobileMenu); closeBtn.addEventListener('touchstart', toggleMobileMenu, {passive:true}); }
            const dd = document.querySelector('.user-dropdown');
            if (dd) {
                const trigger = dd.querySelector('.user-name');
                const menu = dd.querySelector('.dropdown');
                function toggle(e){ if(e) e.preventDefault(); menu.style.display = (menu.style.display === 'block') ? 'none' : 'block'; }
                trigger.addEventListener('click', toggle);
                document.addEventListener('click', (e) => { if (!dd.contains(e.target)) { menu.style.display = 'none'; } });
            }
            function ensureClosed(){ if (window.innerWidth >= 768) { const drawer = document.getElementById('mobileDrawer'); const ov = document.getElementById('drawerOverlay'); if (drawer) drawer.classList.remove('open'); if (ov) ov.classList.remove('open'); } }
            ensureClosed();
            window.addEventListener('resize', ensureClosed);
        });
        async function fetchResults(query) {
            try {
                const response = await fetch(`searching.php?ajax=1&q=${encodeURIComponent(query)}&t=${Date.now()}`);
                const data = await safeJson(response);
                if (data && data.status === 'success' && Array.isArray(data.data)) { renderGrid(data.data); }
                else { document.getElementById('resultsGrid').innerHTML = '<div class="no-content">No matches found.</div>'; }
            } catch (error) {
                console.error(error);
                document.getElementById('resultsGrid').innerHTML = '<div class="no-content">Error searching library.</div>';
            }
        }
        function renderGrid(items) {
            const container = document.getElementById('resultsGrid');
            container.innerHTML = '';
            if (items.length === 0) { container.innerHTML = '<div class="no-content">No matches found.</div>'; return; }
            items.forEach(story => {
                const link = `preview.php?series=${encodeURIComponent(story.folder)}&type=${encodeURIComponent(story.type)}`;
                const html = `
                <div class="enhanced-card" onclick="window.location.href='${link}'">
                    <div class="enhanced-card-image">
                        <img src="${story.thumbnail}" alt="Cover">
                    </div>
                    <div class="enhanced-card-details">
                        <div class="enhanced-card-title">${story.title}</div>
                        <div class="enhanced-card-sub">Ch. ${story.chapter_num} &bull; ${story.type.toUpperCase()}</div>
                        <div class="enhanced-card-meta">
                            <span class="rating"><i class="fas fa-star"></i> ${story.rating}</span>
                            <span class="views-badge"><i class="fas fa-eye"></i> ${story.views || 0}</span>
                        </div>
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
            });
        }
        function performSearch() { const query = document.getElementById('searchInput').value.trim(); if (query) window.location.href = `searching.php?q=${encodeURIComponent(query)}`; }
        document.getElementById('searchInput').addEventListener('keypress', function (e) { if (e.key === 'Enter') performSearch(); });
        async function safeJson(res){ const text = await res.text(); try { return JSON.parse(text); } catch(e){ return null; } }
    </script>
</body>
</html>

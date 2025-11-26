<?php
require_once __DIR__ . '/includes/session.php'; 

$baseDir = 'Book_data/';
$series = $_REQUEST['series'] ?? '';
$type = $_REQUEST['type'] ?? '';

// Sanitize inputs
$series = basename($series);
$type = basename($type);

// --- 1. ROBUST CONTENT FINDER ---
$possible_types = [$type, 'webtoon', 'movie', 'series', 'manga', 'comic'];
$seriesPath = '';
$realType = $type; 

foreach ($possible_types as $checkType) {
    if (empty($checkType)) continue;
    $testPath = $baseDir . $checkType . '/' . $series;
    if (is_dir($testPath)) {
        $seriesPath = $testPath;
        $realType = $checkType;
        break;
    }
}

if (empty($seriesPath)) {
    header("Location: index.php");
    exit;
}

// Generate Consistent ID
$comicId = sprintf('%u', crc32(strtolower($realType . '/' . $series)));
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['username'] ?? null;

// --- 2. LIKES STORAGE ---
$likesDir = __DIR__ . '/user_data/likes';
if (!is_dir($likesDir)) { @mkdir($likesDir, 0777, true); }
$likesFile = $likesDir . '/' . $comicId . '.json';

function load_likes($file){ 
    if (!file_exists($file)) return ['users'=>[]]; 
    $d = json_decode(@file_get_contents($file), true); 
    return (is_array($d) && isset($d['users'])) ? $d : ['users'=>[]]; 
}

function save_likes($file, $data){ 
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    return @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX); 
}

function likes_count_fs($file){ $d = load_likes($file); return count($d['users']); }
function liked_by_me_fs($file, $userName){ if(!$userName) return false; $d = load_likes($file); return array_key_exists($userName, $d['users']); }

// --- AJAX LIKES HANDLER ---
if ((isset($_GET['ajax']) && $_GET['ajax'] === 'likes') || (isset($_POST['ajax']) && $_POST['ajax'] === 'likes')) {
    header('Content-Type: application/json');
    $action = $_REQUEST['action'] ?? 'status';
    
    if (!$userName && $action === 'toggle') {
        echo json_encode(['status'=>'login_required']); exit;
    }

    if ($action === 'toggle') {
        $data = load_likes($likesFile);
        $liked = liked_by_me_fs($likesFile, $userName);
        
        if ($liked) { unset($data['users'][$userName]); $liked = false; } 
        else { $data['users'][$userName] = date('Y-m-d H:i:s'); $liked = true; }
        save_likes($likesFile, $data);
    }

    echo json_encode([
        'status'=>'success', 
        'liked'=>liked_by_me_fs($likesFile, $userName), 
        'count'=>likes_count_fs($likesFile)
    ]);
    exit;
}

// --- 3. METADATA LOADING ---
// Fetch Views directly from API file logic to ensure sync
$viewsFile = __DIR__ . '/views/views.json';
$d_views = json_decode(@file_get_contents($viewsFile), true) ?: [];
$totalViews = (int)($d_views[$realType][$series]['total'] ?? 0);

$likesCount = likes_count_fs($likesFile);
$likedInitial = liked_by_me_fs($likesFile, $userName);
$siteBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

function toWebUrl($p, $siteBase) {
    if (!$p) return $p;
    if (preg_match('/^(https?:\/\/|data:)/i', $p)) return $p;
    if (!preg_match('/^Book_data\//i', $p)) $p = 'Book_data/' . ltrim($p, '/');
    return rtrim($siteBase, '/') . '/' . ltrim($p, '/');
}

$meta = [
    'title' => str_replace('_', ' ', $series),
    'description' => 'Experience the latest content of this amazing series.',
    'published' => 'Unknown',
    'cover' => 'https://via.placeholder.com/400x600?text=No+Cover', 
    'latest_chapter_num' => 1,
    'latest_chapter_title' => 'Chapter 1',
    'writer' => 'Creator'
];
$latestExternalUrl = '';

if (is_dir($seriesPath)) {
    $chapters = scandir($seriesPath);
    $latestTime = 0;
    
    foreach ($chapters as $chapter) {
        if ($chapter === '.' || $chapter === '..') continue;
        $jsonPath = $seriesPath . '/' . $chapter . '/metadata.json';
        
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if ($data) {
                $uploadTime = strtotime($data['status']['upload_date'] ?? 'now');
                if ($uploadTime >= $latestTime) {
                    $latestTime = $uploadTime;
                    $meta['title'] = $data['meta']['series_name'] ?? $meta['title'];
                    $meta['latest_chapter_num'] = $data['meta']['chapter_number'] ?? $meta['latest_chapter_num'];
                    $meta['published'] = date('M d, Y', $uploadTime);
                    $meta['writer'] = $data['meta']['creator'] ?? $meta['writer'];
                    $meta['description'] = $data['meta']['description'] ?? $meta['description'];
                    
                    if (!empty($data['meta']['movie_title'])) $meta['latest_chapter_title'] = $data['meta']['movie_title'];
                    elseif (!empty($data['meta']['chapter_title'])) $meta['latest_chapter_title'] = $data['meta']['chapter_title'];
                    
                    $latestExternalUrl = $data['assets']['video_url'] ?? $data['assets']['archive_url'] ?? ''; 
                    $coverPath = $data['assets']['thumbnail_path'] ?? $data['assets']['thumbnail'] ?? ''; 
                    if ($coverPath) $meta['cover'] = toWebUrl($coverPath, $siteBase);
                }
            }
        }
    }
}

// Button Logic
$readLinkTarget = '_self'; 
$btnIcon = 'book-open';
$btnText = 'Read Now';

if (!empty($latestExternalUrl) && filter_var($latestExternalUrl, FILTER_VALIDATE_URL)) {
    $readLink = $latestExternalUrl;
    $readLinkTarget = '_blank';
    $btnIcon = 'play';
    $btnText = 'Watch Now';
} else {
    $readLink = "read.php?series=" . urlencode($series) . "&type=" . urlencode($realType) . "&chapter=" . urlencode($meta['latest_chapter_num']); 
}

if (in_array(strtolower($realType), ['webtoon', 'movie', 'series'])) {
    $btnIcon = 'play';
    $btnText = 'Watch Now';
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
        :root { --primary: #ec1d24; --bg-dark: #121212; --bg-card: #1e1e1e; --text-main: #ffffff; --text-muted: #aaaaaa; --border-color: #333333; }
        body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; padding-top: 70px; }
        a { text-decoration: none; color: inherit; transition: 0.2s; }

        /* NAVBAR */
        .navbar { position: fixed; top: 0; left: 0; right: 0; height: 70px; background: rgba(18, 18, 18, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; z-index: 1000; }
        .logo { font-size: 28px; font-weight: 900; color: var(--primary); letter-spacing: -1px; }
        .nav-center { display: flex; gap: 30px; }
        .nav-link { font-weight: 600; font-size: 15px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .nav-link:hover, .nav-link.active { color: var(--text-main); }
        
        .user-section { position: relative; display: flex; align-items: center; gap: 20px; }
        .btn-login { background: var(--primary); color: white; padding: 8px 24px; border-radius: 20px; font-weight: 700; font-size: 14px; }
        .user-toggle { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 5px; border-radius: 30px; transition: 0.2s; }
        .user-toggle:hover { background: rgba(255,255,255,0.05); }
        .user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary), #800000); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; }
        .dropdown-menu { position: absolute; top: 60px; right: 0; width: 200px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: none; flex-direction: column; overflow: hidden; }
        .dropdown-menu.show { display: flex; }
        .dropdown-item { padding: 12px 20px; color: var(--text-muted); font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .dropdown-item:hover { background: rgba(255,255,255,0.05); color: var(--text-main); }
        
        /* CONTENT */
        .preview-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: flex; gap: 60px; }
        .cover-box { flex: 0 0 350px; }
        .cover-box img { width: 100%; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
        .info-box { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .series-title { font-size: 48px; font-weight: 800; line-height: 1.1; margin-bottom: 20px; }
        .meta-tags { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; }
        .tag { background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 4px; font-size: 13px; color: #ddd; font-weight: 600; }
        .tag i { color: var(--primary); margin-right: 6px; }
        .desc-text { color: var(--text-muted); line-height: 1.6; font-size: 16px; margin-bottom: 30px; }
        
        .actions { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn-main { background: var(--primary); color: white; border: none; padding: 14px 40px; border-radius: 6px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; transition: 0.2s; box-shadow: 0 4px 15px rgba(236,29,36,0.4); }
        .btn-main:hover { background: #ff333b; transform: translateY(-2px); }
        .btn-sec { background: transparent; border: 1px solid #555; color: white; padding: 14px 25px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-sec:hover { border-color: var(--primary); color: var(--primary); }
        .btn-sec.active { border-color: var(--primary); background: var(--primary); color: white; }

        /* REVIEWS */
        .reviews-container { max-width: 1200px; margin: 60px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 2fr; gap: 40px; }
        .review-form-box { background: var(--bg-card); padding: 30px; border-radius: 12px; border: 1px solid var(--border-color); height: fit-content; }
        .star-input { font-size: 24px; color: #555; cursor: pointer; transition: 0.2s; margin-right: 5px; }
        .star-input:hover, .star-input.active { color: #ffcc00; }
        .review-input { width: 100%; background: #2a2a2a; border: 1px solid #444; color: white; padding: 15px; border-radius: 6px; margin: 20px 0; resize: vertical; min-height: 120px; font-family: inherit; }
        
        .review-card { background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 15px; }
        .r-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .r-user { font-weight: 700; color: white; }
        .r-stars { color: #ffcc00; font-size: 12px; }
        .r-text { color: #ccc; line-height: 1.5; font-size: 14px; }
        .r-date { color: #666; font-size: 12px; }

        @media (max-width: 900px) {
            .navbar { padding: 0 20px; } .nav-center { display: none; }
            .preview-container { flex-direction: column; align-items: center; text-align: center; }
            .meta-tags { justify-content: center; } .reviews-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">CV</a>
        <div class="nav-center">
            <a href="index.php" class="nav-link">Home</a>
            <a href="comic.php" class="nav-link">Comics</a>
            <a href="manga.php" class="nav-link">Manga</a>
            <a href="webtoon.php" class="nav-link">Movies</a>
        </div>
        <div class="user-section">
            <?php if($userName): ?>
                <div class="user-toggle" onclick="document.querySelector('.dropdown-menu').classList.toggle('show')">
                    <div class="user-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
                    <span style="font-size:14px; font-weight:600"><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down" style="font-size:12px"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> Profile</a>
                    <a href="mystories.php" class="dropdown-item"><i class="fas fa-layer-group"></i> My Stories</a>
                    <a href="auth.php?action=logout" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-login">Log In</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="preview-container">
        <div class="cover-box"><img src="<?php echo $meta['cover']; ?>" alt="cover"></div>
        <div class="info-box">
            <div class="meta-tags">
                <span class="tag"><i class="fas fa-film"></i> <?php echo ucfirst($realType); ?></span>
                <span class="tag"><i class="fas fa-user"></i> <?php echo htmlspecialchars($meta['writer']); ?></span>
                <span class="tag"><i class="fas fa-eye"></i> <span id="viewCount"><?php echo number_format($totalViews); ?></span></span>
                <span class="tag"><i class="fas fa-calendar"></i> <?php echo $meta['published']; ?></span>
            </div>

            <h1 class="series-title"><?php echo htmlspecialchars($meta['title']); ?></h1>
            <p class="desc-text"><?php echo nl2br(htmlspecialchars($meta['description'])); ?></p>

            <div class="actions">
                <a href="<?php echo $readLink; ?>" target="<?php echo $readLinkTarget; ?>" class="btn-main" id="btnReadNow">
                    <i class="fas fa-<?php echo $btnIcon; ?>"></i> <?php echo $btnText; ?>
                </a>
                <button class="btn-sec" id="btnList"><i class="fas fa-plus"></i> My List</button>
                <button class="btn-sec <?php echo $likedInitial ? 'active' : ''; ?>" id="btnLike">
                    <i class="<?php echo $likedInitial ? 'fas' : 'far'; ?> fa-heart"></i>
                    <span id="likeText"><?php echo $likedInitial ? 'Liked' : 'Like'; ?></span>
                    <span id="likeNum">(<?php echo $likesCount; ?>)</span>
                </button>
                <button class="btn-sec" onclick="navigator.share({title:document.title, url:window.location.href}).catch(()=>{})">
                    <i class="fas fa-share-alt"></i> Share
                </button>
            </div>
        </div>
    </div>

    <div class="reviews-container">
        <div class="review-form-box">
            <h3>Write a Review</h3>
            <?php if($userName): ?>
                <div style="margin-bottom:15px;" id="starContainer">
                    <i class="fas fa-star star-input" data-val="1"></i><i class="fas fa-star star-input" data-val="2"></i>
                    <i class="fas fa-star star-input" data-val="3"></i><i class="fas fa-star star-input" data-val="4"></i>
                    <i class="fas fa-star star-input" data-val="5"></i>
                </div>
                <textarea id="reviewText" class="review-input" placeholder="What did you think?"></textarea>
                <button id="submitReview" class="btn-main" style="width:100%">Post Review</button>
            <?php else: ?>
                <p style="color:#aaa;">Please <a href="login.php" style="color:var(--primary)">login</a> to review.</p>
            <?php endif; ?>
        </div>
        <div>
            <h3 style="margin-bottom:20px;">Reviews <span id="avgRating" style="font-size:14px; color:#ffcc00; margin-left:10px;"></span></h3>
            <div id="reviewList"><div style="text-align:center; padding:20px; color:#555;">Loading...</div></div>
        </div>
    </div>

    <script>
        const CFG = {
            series: <?php echo json_encode($series); ?>,
            type: <?php echo json_encode($realType); ?>,
            chapter: <?php echo json_encode($meta['latest_chapter_num']); ?>,
            user: <?php echo json_encode($userName); ?>
        };

        // --- 1. VIEW COUNTING LOGIC (ADDED) ---
        document.getElementById('btnReadNow').addEventListener('click', function() {
            const url = `views/views_api.php?action=log&type=${encodeURIComponent(CFG.type)}&series=${encodeURIComponent(CFG.series)}&chapter=${encodeURIComponent(CFG.chapter)}`;
            fetch(url, { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success' && data.series_total) {
                        document.getElementById('viewCount').textContent = new Intl.NumberFormat().format(data.series_total);
                    }
                })
                .catch(err => console.error("View log error:", err));
        });

        // --- 2. LIKE LOGIC ---
        document.getElementById('btnLike').addEventListener('click', async function() {
            if (!CFG.user) return window.location.href = 'login.php';
            this.disabled = true;
            try {
                const fd = new FormData(); fd.append('ajax', 'likes'); fd.append('action', 'toggle');
                const res = await fetch(window.location.href, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    this.classList.toggle('active', data.liked);
                    this.querySelector('i').className = data.liked ? 'fas fa-heart' : 'far fa-heart';
                    document.getElementById('likeText').textContent = data.liked ? 'Liked' : 'Like';
                    document.getElementById('likeNum').textContent = `(${data.count})`;
                }
            } catch(e) {}
            this.disabled = false;
        });

        // --- 3. LIST LOGIC ---
        async function initList() {
            const btn = document.getElementById('btnList');
            if(!CFG.user) return btn.onclick = () => window.location.href = 'login.php';
            try {
                const res = await fetch(`list.php?action=get&ts=${Date.now()}`);
                const data = await res.json();
                const inList = data.list && data.list.some(i => i.series === CFG.series && i.type === CFG.type);
                updateListBtn(inList);
                btn.onclick = async () => {
                    const isAdding = !btn.classList.contains('active');
                    const fd = new FormData(); fd.append('action', isAdding ? 'add' : 'remove');
                    fd.append('series', CFG.series); fd.append('type', CFG.type);
                    if(isAdding) fd.append('title', document.title);
                    const actionRes = await fetch('list.php', { method: 'POST', body: fd });
                    const j = await actionRes.json();
                    if(j.status === 'success') updateListBtn(isAdding);
                };
            } catch(e) { btn.style.display = 'none'; }
        }
        function updateListBtn(active) {
            const btn = document.getElementById('btnList');
            btn.classList.toggle('active', active);
            btn.innerHTML = active ? '<i class="fas fa-check"></i> In List' : '<i class="fas fa-plus"></i> My List';
        }
        initList();

        // --- 4. REVIEWS LOGIC ---
        let currentRating = 0;
        document.querySelectorAll('.star-input').forEach(star => {
            star.addEventListener('click', function() {
                currentRating = this.dataset.val;
                document.querySelectorAll('.star-input').forEach(s => s.classList.toggle('active', s.dataset.val <= currentRating));
            });
        });

        async function loadReviews() {
            try {
                const res = await fetch(`reviews.php?series=${encodeURIComponent(CFG.series)}&type=${encodeURIComponent(CFG.type)}`);
                const data = await res.json();
                if (data.status === 'success') {
                    if(data.summary.total > 0) document.getElementById('avgRating').innerHTML = `<i class="fas fa-star"></i> ${data.summary.average} (${data.summary.total})`;
                    const list = document.getElementById('reviewList');
                    if (data.reviews.length === 0) { list.innerHTML = '<div style="text-align:center; padding:20px; color:#777;">Be the first to review!</div>'; return; }
                    list.innerHTML = data.reviews.map(r => `
                        <div class="review-card">
                            <div class="r-header"><span class="r-user">${escapeHtml(r.username)}</span><span class="r-date">${r.timestamp.split(' ')[0]}</span></div>
                            <div class="r-stars">${'<i class="fas fa-star"></i>'.repeat(r.rating)}</div>
                            <p class="r-text">${escapeHtml(r.review)}</p>
                        </div>`).join('');
                }
            } catch(e) {}
        }
        const subBtn = document.getElementById('submitReview');
        if(subBtn) {
            subBtn.addEventListener('click', async () => {
                if(currentRating === 0) return alert('Please select a star rating');
                const text = document.getElementById('reviewText').value;
                if(!text.trim()) return alert('Please write a review');
                subBtn.disabled = true; subBtn.textContent = 'Posting...';
                const fd = new FormData(); fd.append('series', CFG.series); fd.append('type', CFG.type);
                fd.append('rating', currentRating); fd.append('review', text);
                try {
                    const res = await fetch('reviews.php', { method:'POST', body:fd });
                    const j = await res.json();
                    if(j.status === 'success') { document.getElementById('reviewText').value = ''; loadReviews(); subBtn.textContent = 'Posted!'; } 
                    else { alert(j.message); subBtn.textContent = 'Post Review'; subBtn.disabled = false; }
                } catch(e) { alert('Error posting review'); subBtn.disabled = false; }
            });
        }
        function escapeHtml(text) { return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"); }
        loadReviews();
        document.addEventListener('click', (e) => { if (!e.target.closest('.user-section')) document.querySelector('.dropdown-menu')?.classList.remove('show'); });
    </script>
</body>
</html>
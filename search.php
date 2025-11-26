<?php
// index.php
session_start(); // <--- CRITICAL: Start Session to handle login state

// --- BACKEND ENGINE (AJAX RESPONDER) ---
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    error_reporting(0);

    $baseDir = 'Book_data/';
    $library = [];
    
    // Sanitize and split query
    $rawQuery = $_GET['q'] ?? '';
    $searchQuery = strtolower(trim($rawQuery));
    $searchTerms = array_filter(explode(' ', $searchQuery)); 

    // Helper: Time Ago
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

    if (is_dir($baseDir)) {
        $types = scandir($baseDir);
        foreach ($types as $type) {
            if ($type === '.' || $type === '..') continue;
            $typePath = $baseDir . $type;
            if (is_dir($typePath)) {
                $seriesList = scandir($typePath);
                foreach ($seriesList as $seriesFolder) {
                    if ($seriesFolder === '.' || $seriesFolder === '..') continue;

                    // --- ENHANCED SEARCH LOGIC ---
                    $cleanTitle = str_replace('_', ' ', $seriesFolder);
                    $lowerTitle = strtolower($cleanTitle);
                    $relevanceScore = 0;

                    if ($searchQuery !== '' && strpos($lowerTitle, $searchQuery) !== false) {
                        $relevanceScore += 50;
                    }

                    foreach ($searchTerms as $term) {
                        if (strpos($lowerTitle, $term) !== false) {
                            $relevanceScore += 10;
                        }
                    }

                    if ($relevanceScore === 0) continue;

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
                                    $seriesKey = $type . '_' . $seriesFolder;

                                    if (!isset($library[$seriesKey]) || $uploadTime > $library[$seriesKey]['timestamp']) {
                                        $thumbSrc = 'https://via.placeholder.com/400x600?text=No+Cover';
                                        if (!empty($data['assets']['thumbnail_base64'])) {
                                            $thumbSrc = $data['assets']['thumbnail_base64'];
                                        } elseif (!empty($data['assets']['thumbnail'])) {
                                            $thumbSrc = $seriesPath . '/' . $chapter . '/' . $data['assets']['thumbnail'];
                                        }

                                        $library[$seriesKey] = [
                                            'title' => $data['meta']['series_name'],
                                            'folder' => $seriesFolder,
                                            'type' => $type,
                                            'chapter_num' => $data['meta']['chapter_number'],
                                            'timestamp' => $uploadTime,
                                            'time_ago' => time_elapsed_string($dateStr),
                                            'thumbnail' => $thumbSrc,
                                            'rating' => number_format(rand(40, 50) / 10, 1),
                                            'score' => $relevanceScore 
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

    usort($allSeries, function($a, $b) {
        if ($a['score'] === $b['score']) {
            return $b['timestamp'] - $a['timestamp']; 
        }
        return $b['score'] - $a['score']; 
    });

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
        /* --- THEME PRESERVED --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        body { background-color: #151515; color: #ffffff; overflow-x: hidden; min-height: 100vh; display: flex; flex-direction: column; }
        a { text-decoration: none; color: white; transition: 0.3s; }
        
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 0 5%; height: 60px; background-color: #202020; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 1000; }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: 1px; color: #fff; background-color: #ec1d24; padding: 0 10px; height: 100%; display: flex; align-items: center; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #ccc; }
        .nav-links a:hover { color: #ec1d24; }

        /* AUTH BUTTONS */
        .btn-login { color: #ec1d24; font-weight: bold; font-size: 12px; cursor: pointer; text-transform: uppercase; }
        .btn-login:hover { text-decoration: underline; }
        
        .user-menu { display: flex; align-items: center; gap: 15px; font-size: 13px; font-weight: bold; }
        .user-name { color: #fff; text-transform: uppercase; }
        .btn-logout { color: #777; font-size: 14px; }
        .btn-logout:hover { color: #ec1d24; }

        /* HEADER */
        .page-header { padding: 40px 5%; background: linear-gradient(to bottom, #202020, #151515); border-bottom: 1px solid #333; margin-bottom: 30px; }
        .page-header h1 { font-size: 32px; font-weight: 800; text-transform: uppercase; }
        .page-header p { color: #888; margin-top: 5px; }

        /* SEARCH BAR */
        .header-search { margin-top: 20px; display: flex; gap: 10px; max-width: 500px; }
        .search-input { flex: 1; padding: 12px 15px; background: rgba(255, 255, 255, 0.1); border: 1px solid #444; color: white; font-size: 16px; outline: none; border-radius: 4px; }
        .search-input:focus { background: rgba(255, 255, 255, 0.2); border-color: #ec1d24; }
        .search-btn { background-color: #ec1d24; color: white; padding: 12px 25px; font-weight: 700; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
        .search-btn:hover { background-color: #ff333b; }

        /* GRID */
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
        .free-badge { background-color: #00a652; color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 3px; }

        .loading-spinner { grid-column: 1/-1; text-align: center; padding: 50px; color: #ec1d24; font-size: 24px; }
        .no-content { grid-column: 1/-1; text-align: center; padding: 50px; color: #666; border: 2px dashed #333; border-radius: 8px; }

        footer { background-color: #111; padding: 40px 5%; border-top: 1px solid #333; text-align: center; font-size: 12px; color: #555; margin-top: auto; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
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
            <a href="mystories.php">Creator Studio</a>
        </div>

        <?php if(isset($_SESSION['username'])): ?>
            <div class="user-menu">
                <span class="user-name">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="auth.php?action=logout" class="btn-logout" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-login">LOG IN</a>
        <?php endif; ?>
    </nav>

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
        // Get Query from URL
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('q') || '';

        document.addEventListener('DOMContentLoaded', () => {
            if(searchQuery) fetchResults(searchQuery);
            else document.getElementById('resultsGrid').innerHTML = '<div class="no-content">Type something to search.</div>';
        });

        async function fetchResults(query) {
            try {
                const response = await fetch(`search.php?ajax=1&q=${encodeURIComponent(query)}&t=${Date.now()}`);
                const data = await response.json();

                if (data.status === 'success') {
                    renderGrid(data.data);
                }
            } catch (error) {
                console.error(error);
                document.getElementById('resultsGrid').innerHTML = '<div class="no-content">Error searching library.</div>';
            }
        }

        function renderGrid(items) {
            const container = document.getElementById('resultsGrid');
            container.innerHTML = '';

            if (items.length === 0) {
                container.innerHTML = '<div class="no-content">No matches found.</div>';
                return;
            }

            items.forEach(story => {
                const link = `preview.php?series=${encodeURIComponent(story.folder)}&type=${encodeURIComponent(story.type)}`;
                
                const html = `
                <div class="enhanced-card" onclick="window.location.href='${link}'">
                    <div class="enhanced-card-image">
                        <img src="${story.thumbnail}" loading="lazy" alt="Cover">
                    </div>
                    <div class="enhanced-card-details">
                        <div class="enhanced-card-title">${story.title}</div>
                        <div class="enhanced-card-sub">Ch. ${story.chapter_num} &bull; ${story.type.toUpperCase()}</div>
                        <div class="enhanced-card-meta">
                            <span class="rating"><i class="fas fa-star"></i> ${story.rating}</span>
                            <span class="free-badge">FREE</span>
                        </div>
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
            });
        }

        function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) window.location.href = `search.php?q=${encodeURIComponent(query)}`;
        }

        document.getElementById('searchInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') performSearch();
        });
    </script>
</body>
</html>

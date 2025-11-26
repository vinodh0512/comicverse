<?php
require_once __DIR__ . '/includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Read new webtoons on ComicVerse. Vertical-scroll comics optimized for mobile.">
    <link rel="canonical" href="http://localhost/comicverse/webtoon.php">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <title>Browse Movies | ComicVerse</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></noscript>
    <style>
        /* --- BASE STYLES --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { background-color: #151515; color: #ffffff; overflow-x: hidden; min-height: 100vh; display: flex; flex-direction: column; }
        a { text-decoration: none; color: white; transition: 0.3s; }

        /* --- NAVBAR --- */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 0 5%; height: 60px; background-color: #202020; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 1000; }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: 1px; color: #fff; background-color: #ec1d24; padding: 0 10px; height: 100%; display: flex; align-items: center; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #ccc; border-bottom: 2px solid transparent; }
        .nav-links a:hover { color: white; border-bottom: 2px solid #ec1d24; }
        .nav-links a.active { color: white; border-bottom: 2px solid #ec1d24; }
        .mobile-menu-btn { display:none; background:none; border:none; color:#fff; font-size:22px; width:44px; height:44px; cursor:pointer; margin-left: 10px; }
        .drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); display:none; z-index:999; }
        .drawer-overlay.open { display:block; }
        .mobile-drawer { position:fixed; top:0; right:0; width:80%; max-width:300px; height:100%; background:#1a1a1a; border-left:1px solid #333; z-index:1000; transform: translateX(100%); transition: transform .2s ease-out; padding:20px; display:flex; flex-direction:column; gap:16px; }
        .mobile-drawer.open { transform: translateX(0); }
        .mobile-drawer a { color:#ccc; font-weight:700; text-transform:uppercase; padding:10px; border-bottom:1px solid #2a2a2a; }
        .drawer-header { display:flex; justify-content:space-between; align-items:center; padding-bottom:10px; border-bottom:1px solid #333; margin-bottom:10px; }
        .drawer-close-btn { color:#fff; font-size:24px; cursor:pointer; background:none; border:none; }

        /* --- AUTH BUTTONS --- */
        .btn-login { color: #ec1d24; font-weight: bold; font-size: 12px; cursor: pointer; text-transform: uppercase; }
        .user-menu { display: flex; align-items: center; gap: 12px; }
        .user-text { color: #fff; font-size: 14px; font-weight: 700; }
        .user-text span { font-weight: 900; }
        .logout-btn { color: #666; font-size: 16px; transition: 0.3s; }
        .logout-btn:hover { color: #fff; }

        /* --- HEADER & SEARCH --- */
        .page-header {
            padding: 50px 5%;
            background: linear-gradient(to bottom, #202020, #151515);
            border-bottom: 1px solid #333;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-title h1 { font-size: 36px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        .header-title p { color: #888; margin-top: 5px; font-size: 14px; }
        .header-title span { color: #ec1d24; }

        .search-wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
        }
        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #333;
            border-radius: 50px;
            color: white;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }
        .search-input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #ec1d24;
            box-shadow: 0 0 15px rgba(236, 29, 36, 0.1);
        }
        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            pointer-events: none;
        }

        /* --- GRID LAYOUT --- */
        .container { padding: 0 5%; flex: 1; }
        
        .update-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); 
            gap: 30px; 
            min-height: 300px;
        }

        /* --- CARD STYLES --- */
        .enhanced-card { 
            cursor: pointer; transition: transform 0.3s ease; position: relative; 
            border-radius: 6px; overflow: hidden; animation: fadeIn 0.4s ease; 
            background: #1a1a1a;
        }
        .enhanced-card:hover { transform: translateY(-8px); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .enhanced-card-image { 
            width: 100%; aspect-ratio: 2/3; background-color: #333; 
            overflow: hidden; position: relative; 
        }
        .enhanced-card-image img { 
            width: 100%; height: 100%; object-fit: cover; transition: all 0.3s ease; 
        }
        .enhanced-card:hover .enhanced-card-image img { transform: scale(1.05); opacity: 0.8; }
        
        .enhanced-card-details { padding: 15px 12px; }
        .enhanced-card-title { font-size: 15px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.4; }
        .enhanced-card-sub { font-size: 12px; color: #888; margin-bottom: 10px; }
        .enhanced-card-meta { display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #666; border-top: 1px solid #333; padding-top: 8px; }
        
        .rating { color: #ffcc00; font-weight: bold; }
        .read-btn { color: #ec1d24; font-weight: 800; font-size: 10px; text-transform: uppercase; }

        /* --- LOAD MORE BUTTON --- */
        .load-more-container { text-align: center; margin: 60px 0; }
        .btn-load-more {
            background: #222; border: 1px solid #444; color: #ccc;
            padding: 14px 50px; font-weight: 700; text-transform: uppercase;
            cursor: pointer; transition: 0.3s; font-size: 12px; letter-spacing: 1px;
        }
        .btn-load-more:hover { border-color: #ec1d24; color: #fff; background: #ec1d24; }
        .hidden { display: none; }

        /* --- STATES --- */
        .loading-spinner { grid-column: 1/-1; text-align: center; padding: 50px; color: #ec1d24; font-size: 24px; }
        .no-content { grid-column: 1/-1; text-align: center; padding: 50px; color: #666; border: 2px dashed #333; border-radius: 8px; font-size: 14px;}

        /* FOOTER */
        footer { background-color: #111; padding: 40px 5%; border-top: 1px solid #333; text-align: center; font-size: 12px; color: #555; margin-top: auto; }

        @media (max-width: 768px) { 
            .nav-links { display: none; } 
            .mobile-menu-btn { display:block; }
            .user-menu, .btn-login { display:none; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .search-wrapper { max-width: 100%; }
            .enhanced-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; } 
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo" aria-label="ComicVerse Home">CV</a> 
        <div class="nav-links" role="navigation" aria-label="Primary">
            <a href="index.php">Home</a>
            <a href="comic.php">Comics</a>
            <a href="manga.php">Manga</a>
            <a href="webtoon.php" class="active">Movies</a>
        </div>
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu"><i class="fas fa-bars" aria-hidden="true"></i></button>

        <?php if(isset($_SESSION['username'])): ?>
            <div class="user-menu user-dropdown" style="position:relative;">
                <div class="user-text" style="cursor:pointer;">Hi, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span> <i class="fas fa-caret-down"></i></div>
                <div class="dropdown" style="display:none; position:absolute; top:60px; right:5%; background:#1a1a1a; border:1px solid #333; border-radius:6px; min-width:160px; box-shadow:0 6px 20px rgba(0,0,0,0.5);">
                    <a href="profile.php" style="display:block; padding:10px 12px; color:#ccc;">Profile</a>
                    <?php if(($_SESSION['role'] ?? '') === 'creator'): ?>
                        <a href="mystories.php" style="display:block; padding:10px 12px; color:#ccc;">Dashboard</a>
                    <?php endif; ?>
                    <a href="auth.php?action=logout" style="display:block; padding:10px 12px; color:#ccc;">Logout</a>
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
            <?php if(($_SESSION['role'] ?? '') === 'creator'): ?><a href="mystories.php">Dashboard</a><?php endif; ?>
            <a href="auth.php?action=logout">Logout</a>
        <?php else: ?>
            <a href="login.php">Log In</a>
        <?php endif; ?>
    </div>

    <main>
    <div class="page-header">
        <div class="header-title">
            <h1>New <span>Movies</span></h1>
            <p>Vertical scrolling comics optimized for mobile devices.</p>
        </div>
        <div class="search-wrapper">
            <input type="text" id="searchInput" class="search-input" placeholder="Search movies..." onkeyup="filterWebtoons()">
            <i class="fas fa-search search-icon"></i>
        </div>
    </div>

    <div class="container">
        <div class="update-grid" id="webtoonGrid">
            <div class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading Library...</div>
        </div>

        <div class="load-more-container">
            <button class="btn-load-more hidden" id="loadMoreBtn" onclick="loadMore()">Load More</button>
        </div>
    </div>

    </main>
    <footer>&copy; 2025 ComicVerse - <a href="termofservice.php">Terms of Service</a> | <a href="privacy_policy.php">Privacy Policy</a></footer>

    <script>
        // --- STATE ---
        let allWebtoons = [];      // Stores ALL data of type 'webtoon'
        let filteredWebtoons = []; // Stores current search results
        let currentIndex = 0;      // Pagination
        const itemsPerPage = 12;   // Items per click

        // --- INIT ---
        document.addEventListener('DOMContentLoaded', fetchWebtoons);

        // --- 1. FETCH & FILTER DATA ---
        async function fetchWebtoons() {
            const grid = document.getElementById('webtoonGrid');
            try {
                let stories = [];
                const res = await fetch('get_stories_api.php?t=' + Date.now());
                if (res.ok) { const json = await res.json(); if (json && json.status==='success' && Array.isArray(json.stories)) stories = json.stories; }
                if (!stories.length) {
                    const res2 = await fetch('index.php?ajax=1&t=' + Date.now());
                    if (res2.ok) { const j2 = await res2.json(); if (j2 && j2.status==='success' && Array.isArray(j2.data)) stories = j2.data.map(s=>({ title:s.title, folder:s.folder, type:s.type, latest_chapter:s.chapter_num, time_ago:s.time_ago, thumbnail:s.thumbnail, rating:s.rating })); }
                }
                if (!stories.length) { grid.innerHTML = '<div class="no-content" style="color:red">No webtoons found.</div>'; return; }
                const norm = stories.map(story => ({ title: story.title || 'Untitled', folder: story.folder || '', type: (story.type||'').toLowerCase(), latest_chapter: story.latest_chapter || story.chapter_num || 1, time_ago: story.time_ago || 'just now', thumbnail: story.thumbnail || 'https://via.placeholder.com/400x600?text=No+Cover', rating: story.rating || '0.0 (0%)' }));
                allWebtoons = norm.filter(s => s.type === 'webtoon');
                filteredWebtoons = allWebtoons;
                grid.innerHTML = '';
                if (allWebtoons.length === 0) grid.innerHTML = '<div class="no-content">No movies uploaded yet.</div>'; else loadMore();
            } catch (error) {
                console.error(error);
                grid.innerHTML = '<div class="no-content" style="color:red">Error loading webtoons.</div>';
            }
        }

        // --- 2. REAL-TIME SEARCH FILTER ---
        function filterWebtoons() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            
            if (query === "") {
                filteredWebtoons = allWebtoons;
            } else {
                filteredWebtoons = allWebtoons.filter(story => 
                    story.title.toLowerCase().includes(query)
                );
            }

            // Reset for new search results
            currentIndex = 0;
            document.getElementById('webtoonGrid').innerHTML = '';
            document.getElementById('loadMoreBtn').classList.add('hidden');

            if (filteredWebtoons.length === 0) {
                document.getElementById('webtoonGrid').innerHTML = '<div class="no-content">No webtoons matched your search.</div>';
            } else {
                loadMore();
            }
        }

        // --- 3. RENDER & PAGINATION ---
        function loadMore() {
            const container = document.getElementById('webtoonGrid');
            const btn = document.getElementById('loadMoreBtn');
            
            // Slice based on FILTERED list
            const nextBatch = filteredWebtoons.slice(currentIndex, currentIndex + itemsPerPage);
            
            nextBatch.forEach(story => {
                const link = `preview.php?series=${encodeURIComponent(story.folder)}&type=${encodeURIComponent(story.type)}`;

                const html = `
                <div class="enhanced-card" onclick="window.location.href='${link}'">
                    <div class="enhanced-card-image">
                        <img data-src="${story.thumbnail}" src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt="Cover" class="lazy-img">
                    </div>
                    <div class="enhanced-card-details">
                        <div class="enhanced-card-title">${escapeHtml(story.title)}</div>
                        <div class="enhanced-card-sub">Ch. ${story.latest_chapter} &bull; ${story.time_ago}</div>
                        <div class="enhanced-card-meta">
                            <span class="rating"><i class="fas fa-star"></i> ${story.rating}</span>
                            <span class="read-btn">READ NOW <i class="fas fa-arrow-right" style="margin-left:3px;"></i></span>
                        </div>
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
            });

            initLazyImages(container);

            currentIndex += itemsPerPage;

            // Show/Hide Button logic
            if (currentIndex >= filteredWebtoons.length) {
                btn.classList.add('hidden');
            } else {
                btn.classList.remove('hidden');
            }
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
        // Lazy image loader
        let io;
        function toggleMobileMenu(){ document.getElementById('mobileDrawer').classList.toggle('open'); document.getElementById('drawerOverlay').classList.toggle('open'); }
        (function(){
            const dd = document.querySelector('.user-dropdown');
            if (!dd) return;
            const trigger = dd.querySelector('.user-text');
            const menu = dd.querySelector('.dropdown');
            function toggle(e){ if(e) e.preventDefault(); menu.style.display = (menu.style.display === 'block') ? 'none' : 'block'; }
            trigger.addEventListener('click', toggle);
            document.addEventListener('click', (e) => { if (!dd.contains(e.target)) { menu.style.display = 'none'; } });
            const btn = document.getElementById('mobileMenuBtn');
            const overlay = document.getElementById('drawerOverlay');
            const closeBtn = document.getElementById('drawerCloseBtn');
            if (btn) { btn.addEventListener('click', toggleMobileMenu); btn.addEventListener('touchstart', toggleMobileMenu, {passive:true}); }
            if (overlay) { overlay.addEventListener('click', toggleMobileMenu); overlay.addEventListener('touchstart', toggleMobileMenu, {passive:true}); }
            if (closeBtn) { closeBtn.addEventListener('click', toggleMobileMenu); closeBtn.addEventListener('touchstart', toggleMobileMenu, {passive:true}); }
            function ensureClosed(){ if (window.innerWidth >= 768) { const drawer = document.getElementById('mobileDrawer'); const ov = document.getElementById('drawerOverlay'); if (drawer) drawer.classList.remove('open'); if (ov) ov.classList.remove('open'); } }
            ensureClosed();
            window.addEventListener('resize', ensureClosed);
        })();
        function initLazyImages(root){
            const images = root.querySelectorAll('img.lazy-img[data-src]');
            if (!('IntersectionObserver' in window)) { images.forEach(img => { img.src = img.getAttribute('data-src'); }); return; }
            if (!io) io = new IntersectionObserver((entries) => {
                entries.forEach(entry => { if (entry.isIntersecting) { const img = entry.target; const src = img.getAttribute('data-src'); if (src) { img.src = src; img.removeAttribute('data-src'); io.unobserve(img); } } });
            }, { rootMargin: '200px 0px' });
            images.forEach(img => io.observe(img));
        }
    </script>
</body>
</html>

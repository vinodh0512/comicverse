<?php require_once __DIR__ . '/includes/session.php'; $username = $_SESSION['username'] ?? ''; $role = $_SESSION['role'] ?? 'reader'; $loggedIn = isset($_SESSION['user_id']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Latest comic, manga, and webtoon chapters on ComicVerse. Discover freshly uploaded content.">
    <link rel="canonical" href="http://localhost/comicverse/latestupdates.php">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <title>Latest Updates | ComicVerse</title>
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
        .nav-links a { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #ccc; }
        .nav-links a:hover { color: #ec1d24; }
        .auth-btn { font-weight: 800; font-size: 13px; cursor: pointer; text-transform: uppercase; }
        .btn-login { color:#ec1d24; border: 1px solid #ec1d24; padding: 8px 20px; border-radius: 4px; transition: all 0.3s ease; }
        .btn-login:hover { background:#ec1d24; color:#fff; box-shadow:0 0 15px rgba(236,29,36,.6); }
        .user-menu { display:flex; align-items:center; gap:15px; font-size:13px; }
        .user-dropdown { position: relative; }
        .mobile-menu-btn { display:none; background:none; border:none; color:#fff; font-size:22px; width:44px; height:44px; cursor:pointer; }
        .drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.6); display:none; z-index:999; }
        .mobile-drawer { position:fixed; top:0; right:0; width:80%; height:100%; background:#1a1a1a; border-left:1px solid #333; z-index:1000; transform: translateX(100%); transition: transform .2s ease-out; padding:20px; display:flex; flex-direction:column; gap:16px; }
        .mobile-drawer.open { transform: translateX(0); }
        .drawer-overlay.open { display:block; }
        .mobile-drawer a { color:#ccc; font-weight:700; text-transform:uppercase; padding:10px; border-bottom:1px solid #2a2a2a; }
        .dropdown { position:absolute; top:calc(100% + 8px); right:0; background:#1a1a1a; border:1px solid #333; border-radius:6px; min-width:160px; box-shadow:0 6px 20px rgba(0,0,0,0.5); display:none; }

        /* --- HEADER & SEARCH --- */
        .page-header {
            padding: 40px 5%;
            background: linear-gradient(to bottom, #202020, #151515);
            border-bottom: 1px solid #333;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-title h1 { font-size: 32px; font-weight: 800; text-transform: uppercase; }
        .header-title p { color: #888; margin-top: 5px; }

        .search-wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
        }
        .search-input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid #333;
            border-radius: 4px;
            color: white;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }
        .search-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #ec1d24;
            box-shadow: 0 0 10px rgba(236, 29, 36, 0.2);
        }
        .search-icon {
            position: absolute;
            right: 15px;
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
            gap: 25px; 
            min-height: 300px;
        }

        /* --- CARD STYLES --- */
        .enhanced-card { 
            cursor: pointer; transition: transform 0.3s ease; position: relative; 
            border-radius: 4px; overflow: hidden; animation: fadeIn 0.4s ease; 
        }
        .enhanced-card:hover { transform: translateY(-10px); }
        
        .enhanced-card-image { 
            width: 100%; aspect-ratio: 2/3; background-color: #333; 
            overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.5); position: relative; 
        }
        .enhanced-card-image img { 
            width: 100%; height: 100%; object-fit: cover; transition: all 0.3s ease; 
        }
        .enhanced-card:hover .enhanced-card-image img { transform: scale(1.05); opacity: 0.9; }
        
        .enhanced-card-details { padding: 12px 8px 8px; background: rgba(32, 32, 32, 0.8); }
        .enhanced-card-title { font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .enhanced-card-sub { font-size: 12px; color: #999; margin-bottom: 8px; }
        .enhanced-card-meta { display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #777; }
        
        .rating { color: #ffcc00; }
        .badge-new { position: absolute; top: 10px; left: 0; background-color: #ec1d24; color: white; font-size: 10px; font-weight: 800; padding: 4px 8px; z-index: 2; }
        .badge-update { position: absolute; top: 10px; left: 0; background-color: #0078ff; color: white; font-size: 10px; font-weight: 800; padding: 4px 8px; z-index: 2; }

        /* --- LOAD MORE BUTTON --- */
        .load-more-container { text-align: center; margin: 50px 0; }
        .btn-load-more {
            background: transparent; border: 1px solid #444; color: #ccc;
            padding: 12px 40px; font-weight: 700; text-transform: uppercase;
            cursor: pointer; transition: 0.3s; font-size: 13px;
        }
        .btn-load-more:hover { border-color: #ec1d24; color: #ec1d24; }
        .hidden { display: none; }

        /* --- STATES --- */
        .loading-spinner { grid-column: 1/-1; text-align: center; padding: 50px; color: #ec1d24; font-size: 24px; }
        .no-content { grid-column: 1/-1; text-align: center; padding: 50px; color: #666; border: 2px dashed #333; border-radius: 8px; font-size: 14px;}

        /* FOOTER */
        footer { background-color: #111; padding: 40px 5%; border-top: 1px solid #333; text-align: center; font-size: 12px; color: #555; margin-top: auto; }

        @media (max-width: 768px) { 
            .nav-links { display: none; } 
            .mobile-menu-btn { display:block; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .search-wrapper { max-width: 100%; }
            .update-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; } 
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
            <a href="webtoon.php">Movies</a>
        </div>
        <?php if($loggedIn): ?>
            <div class="user-menu user-dropdown">
                <span class="user-name" style="cursor:pointer;">Hi, <?php echo htmlspecialchars($username); ?> <i class="fas fa-caret-down"></i></span>
                <div class="dropdown">
                    <a href="profile.php">Profile</a>
                    <?php if($role === 'creator'): ?><a href="creator/mystories.php">Dashboard</a><?php endif; ?>
                    <a href="auth.php?action=logout">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="auth-btn btn-login">LOG IN</a>
        <?php endif; ?>
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu"><i class="fas fa-bars" aria-hidden="true"></i></button>
    </nav>
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header" style="display:flex; justify-content:space-between; align-items:center; padding-bottom:10px; border-bottom:1px solid #2a2a2a; margin-bottom:10px;">
            <span style="font-size: 20px; font-weight: 900; color: #ec1d24;">Menu</span>
            <button class="drawer-close-btn" id="drawerCloseBtn" style="color:#fff; font-size: 24px; cursor: pointer; background: none; border: none;">&times;</button>
        </div>
        <a href="index.php">Home</a>
        <a href="comic.php">Comics</a>
        <a href="manga.php">Manga</a>
        <a href="webtoon.php">Movies</a>
        <?php if($loggedIn): ?>
            <a href="profile.php">Profile</a>
            <?php if($role === 'creator'): ?><a href="creator/mystories.php">Dashboard</a><?php endif; ?>
            <a href="auth.php?action=logout">Logout</a>
        <?php else: ?>
            <a href="login.php">Log In</a>
        <?php endif; ?>
    </div>

    <main>
    <div class="page-header">
        <div class="header-title">
            <h1>Latest Updates</h1>
            <p>Freshly uploaded chapters from the community</p>
        </div>
        <div class="search-wrapper">
            <input type="text" id="searchInput" class="search-input" placeholder="Filter by title..." onkeyup="filterStories()">
            <i class="fas fa-search search-icon"></i>
        </div>
    </div>

    <div class="container">
        <div class="update-grid" id="updateGrid">
            <div class="loading-spinner"><i class="fas fa-circle-notch fa-spin"></i> Loading...</div>
        </div>

        <div class="load-more-container">
            <button class="btn-load-more hidden" id="loadMoreBtn" onclick="loadMore()">Load More</button>
        </div>
    </div>

    </main>
    <footer>&copy; 2025 ComicVerse - <a href="termofservice.php">Terms of Service</a> | <a href="privacy_policy.php">Privacy Policy</a></footer>

    <script>
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
        // --- STATE ---
        let allStories = [];        // Complete dataset
        let filteredStories = [];   // Search results
        let currentIndex = 0;       // Pagination tracker
        const itemsPerPage = 12;

        // --- INIT ---
        document.addEventListener('DOMContentLoaded', fetchStories);

        // --- 1. FETCH DATA ---
        async function fetchStories() {
            try {
                // Reuse existing API
                const response = await fetch('get_stories_api.php?t=' + Date.now());
                const data = await response.json();

                if (data.status === 'success') {
                    allStories = data.stories;
                    filteredStories = allStories; // Initially, filtered list is everything
                    
                    document.getElementById('updateGrid').innerHTML = ''; // Clear loader
                    
                    if (allStories.length === 0) {
                        document.getElementById('updateGrid').innerHTML = '<div class="no-content">No updates available yet.</div>';
                    } else {
                        loadMore(); // Initial load
                    }
                }
            } catch (error) {
                console.error(error);
                document.getElementById('updateGrid').innerHTML = '<div class="no-content" style="color:red">Error loading updates.</div>';
            }
        }

        // --- 2. REAL-TIME SEARCH FILTER ---
        function filterStories() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            
            if (query === "") {
                filteredStories = allStories;
            } else {
                filteredStories = allStories.filter(story => 
                    story.title.toLowerCase().includes(query)
                );
            }

            // Reset Pagination & Grid
            currentIndex = 0;
            document.getElementById('updateGrid').innerHTML = '';
            document.getElementById('loadMoreBtn').classList.add('hidden');

            if (filteredStories.length === 0) {
                document.getElementById('updateGrid').innerHTML = '<div class="no-content">No matches found.</div>';
            } else {
                loadMore();
            }
        }

        // --- 3. RENDER & PAGINATION ---
        function loadMore() {
            const container = document.getElementById('updateGrid');
            const btn = document.getElementById('loadMoreBtn');
            
            // Slice from Filtered Array, not All Array
            const nextBatch = filteredStories.slice(currentIndex, currentIndex + itemsPerPage);
            
            nextBatch.forEach(story => {
                // Logic: New if uploaded in last 48 hours
                const isNew = story.time_ago.includes('minute') || story.time_ago.includes('hour');
                const badgeClass = isNew ? 'badge-new' : 'badge-update';
                const badgeText = isNew ? 'NEW' : 'UPDATED';
                
                const link = `preview.php?series=${encodeURIComponent(story.folder)}&type=${encodeURIComponent(story.type)}`;

                const html = `
                <div class="enhanced-card" onclick="window.location.href='${link}'">
                    <div class="${badgeClass}">${badgeText}</div>
                    <div class="enhanced-card-image">
                        <img src="${story.thumbnail}" loading="lazy" alt="Cover">
                    </div>
                    <div class="enhanced-card-details">
                        <div class="enhanced-card-title">${escapeHtml(story.title)}</div>
                        <div class="enhanced-card-sub">Ch. ${story.latest_chapter} &bull; ${story.time_ago}</div>
                        <div class="enhanced-card-meta">
                            <span class="rating"><i class="fas fa-star"></i> ${story.rating}</span>
                            <span class="views-badge"><i class="fas fa-eye"></i> ${story.views || 0}</span>
                        </div>
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
            });

            currentIndex += itemsPerPage;

            // Show/Hide Load More based on FILTERED list
            if (currentIndex >= filteredStories.length) {
                btn.classList.add('hidden');
            } else {
                btn.classList.remove('hidden');
            }
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>

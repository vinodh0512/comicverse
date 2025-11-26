<?php
// mystories.php
session_start();

// --- STRICT CREATOR ACCESS CONTROL ---
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'creator') {
    header("Location: ../profile.php?prompt=creator_apply");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stories | Creator Studio</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* --- BASE STYLES --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { background-color: #151515; color: #ffffff; overflow-x: hidden; min-height: 100vh; display: flex; flex-direction: column; }
        a { text-decoration: none; color: white; transition: 0.3s; }
        
        /* NAVBAR */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 0 5%; height: 60px; background-color: #202020; border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 1000; }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: 1px; color: #fff; background-color: #ec1d24; padding: 0 10px; height: 100%; display: flex; align-items: center; }
        .nav-links { display: flex; gap: 30px; height: 100%; }
        .nav-links li { height: 100%; display: flex; align-items: center; }
        .nav-links a { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #ccc; border-bottom: 3px solid transparent; }
        .nav-links a.active, .nav-links a:hover { color: white; border-bottom: 3px solid #ec1d24; }
        
        /* AUTH BUTTONS */
        .btn-login { color: #ec1d24; font-weight: bold; font-size: 12px; cursor: pointer; text-transform: uppercase; }
        .user-menu { display: flex; align-items: center; gap: 12px; }
        .user-text { color: #fff; font-size: 14px; font-weight: 700; }
        .user-text span { font-weight: 900; }
        .logout-btn { color: #666; font-size: 16px; transition: 0.3s; }
        .logout-btn:hover { color: #fff; }

        /* LAYOUT */
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; width: 100%; }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .page-title { font-size: 28px; font-weight: 800; text-transform: uppercase; border-left: 5px solid #ec1d24; padding-left: 15px; }
        .action-btn { background-color: #ec1d24; color: white; padding: 10px 20px; border-radius: 4px; font-weight: 700; text-transform: uppercase; font-size: 13px; display: flex; align-items: center; gap: 8px; border:none; cursor:pointer;}
        .action-btn:hover { background-color: #ff333b; transform: translateY(-2px); }
        
        /* STATS */
        .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #202020; padding: 20px; border-radius: 6px; display: flex; align-items: center; gap: 15px; border: 1px solid #333; }
        .stat-icon { width: 45px; height: 45px; background: rgba(236, 29, 36, 0.1); color: #ec1d24; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-info h4 { font-size: 20px; font-weight: 800; margin: 0; }
        .stat-info p { font-size: 12px; color: #888; text-transform: uppercase; margin: 0; }
        
        /* GRID */
        .story-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 30px; min-height: 200px; }
        .story-card { background-color: #202020; border-radius: 8px; overflow: hidden; position: relative; border: 1px solid #333; display: flex; flex-direction: column; }
        .story-card:hover { box-shadow: 0 10px 25px rgba(0,0,0,0.5); border-color: #444; }
        .card-image { width: 100%; aspect-ratio: 2/3; background-color: #111; position: relative; overflow: hidden; }
        .card-image img { width: 100%; height: 100%; object-fit: cover; }
        .card-actions { position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; opacity: 0; transition: 0.3s; background: rgba(0,0,0,0.7); }
        .story-card:hover .card-actions { opacity: 1; }
        .edit-btn { background: white; color: #151515; padding: 8px 20px; font-weight: 700; border-radius: 20px; font-size: 12px; cursor: pointer; border:none;}
        .edit-btn:hover { background: #ec1d24; color: white; }
        .card-content { padding: 15px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .story-title { font-size: 16px; font-weight: 700; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .story-meta { display: flex; justify-content: space-between; font-size: 12px; color: #888; margin-bottom: 10px; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .status-live { background: rgba(0, 166, 82, 0.2); color: #00a652; border: 1px solid rgba(0, 166, 82, 0.3); }
        .status-scheduled { background: rgba(255, 204, 0, 0.2); color: #ffcc00; border: 1px solid rgba(255, 204, 0, 0.3); }
        .chapter-count { background: #333; color: #ccc; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
        .empty-state { grid-column: 1 / -1; background: #202020; border: 2px dashed #444; padding: 60px; text-align: center; border-radius: 8px; color: #666; }
        .empty-state i { font-size: 50px; margin-bottom: 15px; color: #ec1d24; }
        
        /* MODALS */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); z-index: 2000; display: none; justify-content: center; align-items: center; animation: fadeIn 0.3s; }
        .modal-content { background: #202020; width: 90%; max-width: 600px; border-radius: 8px; border: 1px solid #333; padding: 30px; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.8); display: grid; grid-template-columns: 150px 1fr; gap: 25px; }
        .close-modal { position: absolute; top: 15px; right: 15px; color: #888; font-size: 24px; cursor: pointer; transition: 0.3s; }
        .close-modal:hover { color: #ec1d24; }
        .modal-cover { width: 100%; height: 220px; object-fit: cover; border-radius: 4px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); border: 1px solid #444; }
        .modal-details h2 { font-size: 24px; margin-bottom: 5px; text-transform: uppercase; color: white; }
        .modal-badge { font-size: 10px; padding: 2px 6px; background: #333; border-radius: 3px; color: #ccc; margin-right: 5px; text-transform: uppercase; }
        .detail-row { margin-top: 15px; padding-top: 15px; border-top: 1px solid #333; font-size: 13px; color: #aaa; }
        .detail-row div { margin-bottom: 8px; display: flex; justify-content: space-between; }
        .detail-val { color: white; font-weight: 600; }
        .modal-actions { margin-top: 20px; display: flex; gap: 10px; }

        /* DELETE MODAL SPECIFIC */
        .delete-content { max-width: 400px; display: block; text-align: center; border: 1px solid #ec1d24; }
        .delete-icon { font-size: 50px; color: #ec1d24; margin-bottom: 15px; }
        .delete-actions { display: flex; gap: 15px; justify-content: center; margin-top: 25px; }
        .btn-cancel { background: #333; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-confirm-delete { background: #ec1d24; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-confirm-delete:hover { background: #ff333b; }

        /* LOADER */
        .loading-indicator { grid-column: 1/-1; text-align:center; color:#666; padding: 50px; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @media (max-width: 768px) {
            .navbar { padding: 0 20px; }
            .nav-links { display: none; }
            .story-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .card-content { padding: 10px; }
            .story-title { font-size: 14px; }
            .story-meta { font-size: 11px; }
            .modal-content { grid-template-columns: 1fr; text-align: center; }
            .modal-cover { width: 120px; height: 180px; margin: 0 auto; }
            .modal-actions { justify-content: center; }
            .header-section { flex-direction: column; gap: 15px; align-items: flex-start; }
        }
        @media (max-width: 420px) {
            .story-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .story-title { font-size: 13px; }
            .edit-btn { padding: 6px 14px; font-size: 11px; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="../index.php" class="logo">CV</a> 
        <ul class="nav-links">
            <li><a href="#" class="active">My Stories</a></li>
            <li><a href="upload.php">Upload</a></li>
        </ul>

        <?php if(isset($_SESSION['username'])): ?>
            <div class="user-menu">
                <div class="user-text">Hi, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
                <a href="../profile.php" class="btn-login">PROFILE</a>
                <a href="../auth.php?action=logout_creator" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        <?php else: ?>
            <a href="../login.php?role=creator" class="btn-login">LOG IN</a>
        <?php endif; ?>
    </nav>

    <div class="container">
        <div class="header-section">
            <h1 class="page-title">Dashboard</h1>
            <a href="upload.php" class="action-btn"><i class="fas fa-plus"></i> New Story</a>
        </div>

        <div class="stats-overview">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-book-open"></i></div><div class="stat-info"><h4 id="statStories">0</h4><p>Active Stories</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-layer-group"></i></div><div class="stat-info"><h4 id="statChapters">0</h4><p>Total Chapters</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-eye"></i></div><div class="stat-info"><h4 id="statViews">0</h4><p>Total Views</p></div></div>
            <div class="stat-card"><div class="stat-icon" style="color:#00a652; background:rgba(0,166,82,0.1);"><i class="fas fa-dollar-sign"></i></div><div class="stat-info"><h4 id="statEarnings">$0.00</h4><p>Est. Earnings</p></div></div>
        </div>

        <div style="margin-bottom: 20px; font-weight: 700; font-size: 18px; color: #ddd;">Your Library</div>

        <div class="story-grid" id="storyGridContainer">
            <div class="loading-indicator"><i class="fas fa-circle-notch fa-spin"></i> Loading Library...</div>
        </div>
    </div>

    <div id="storyModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div><img id="modalImg" class="modal-cover" src="" alt="Book Cover"></div>
            <div class="modal-details">
                <h2 id="modalTitle">Title</h2>
                <div style="margin-bottom:15px;"><span id="modalType" class="modal-badge">Manga</span><span id="modalStatus" class="modal-badge" style="color:#00a652">Live</span></div>
                <div class="detail-row">
                    <div><span>Total Views:</span> <span class="detail-val" id="modalViews">0</span></div>
                    <div><span>Total Chapters:</span> <span class="detail-val" id="modalChapters">0</span></div>
                    <div><span>Latest Chapter:</span> <span class="detail-val">Ch. <span id="modalLatest">0</span></span></div>
                    <div><span>Last Updated:</span> <span class="detail-val" id="modalUpdated">Just now</span></div>
                    <div><span>Publish Date:</span> <span class="detail-val" id="modalPubDate">--</span></div>
                </div>
                <div class="modal-actions">
                    <a href="upload.php" class="action-btn" style="flex:1; justify-content:center;">Add Chapter</a>
                    <button id="readChapterBtn" class="action-btn" style="background:#333; flex:1; justify-content:center;">Read Latest</button>
                    <button onclick="showDeleteConfirm()" class="action-btn" style="background:#151515; border:1px solid #333; color:#ec1d24; flex:0.5; justify-content:center;">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button id="openThumbEditor" class="action-btn" style="background:#222; border:1px solid #444; color:#fff; flex:1; justify-content:center;">
                        <i class="fas fa-image"></i> Update Thumbnail
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal-overlay" style="z-index: 2100;">
        <div class="modal-content delete-content">
            <i class="fas fa-exclamation-triangle delete-icon"></i>
            <h3 style="margin-bottom:10px; color:white;">Delete this Series?</h3>
            <p style="color:#aaa; font-size:14px; margin-bottom:5px;">Are you sure you want to delete <strong id="delSeriesName" style="color:white;"></strong>?</p>
            <p style="color:#666; font-size:12px;">This action will permanently delete all chapters and cannot be undone.</p>
            
            <div class="delete-actions">
                <button class="btn-cancel" onclick="closeDeleteConfirm()">Back</button>
                <button class="btn-confirm-delete" onclick="confirmDelete()">Delete Permanently</button>
            </div>
        </div>
    </div>

    <div id="thumbEditorModal" class="modal-overlay" style="z-index: 2200;">
        <div class="modal-content" style="grid-template-columns: 1fr; max-width: 480px;">
            <span class="close-modal" onclick="closeThumbEditor()">&times;</span>
            <div style="display:flex; flex-direction:column; gap:15px;">
                <h2 style="margin:0;">Update Thumbnail</h2>
                <img id="thumbEditorPreview" src="" alt="Preview" style="width:100%; height:260px; object-fit:cover; border:1px solid #444; border-radius:4px; display:none;" />
                <input type="file" id="thumbEditorInput" accept="image/*" />
                <button id="saveThumbBtn" class="action-btn" style="justify-content:center;">Save Thumbnail</button>
            </div>
        </div>
    </div>

    <script>
        // --- GLOBAL STATE ---
        let currentFolder = '';
        let currentType = '';
        let currentLatestChapter = '';
        let currentTitle = '';

        // --- INITIALIZATION ---
        document.addEventListener('DOMContentLoaded', () => {
            loadDashboardData();
            setInterval(loadDashboardData, 10000);
            const openBtn = document.getElementById('openThumbEditor');
            if (openBtn) openBtn.addEventListener('click', openThumbEditor);
            const input = document.getElementById('thumbEditorInput');
            if (input) input.addEventListener('change', previewThumbSelected);
            const saveBtn = document.getElementById('saveThumbBtn');
            if (saveBtn) saveBtn.addEventListener('click', saveThumbnail);
            document.body.addEventListener('click', function(e){
                const btn = e.target.closest('.update-thumb-btn');
                if (!btn) return;
                e.preventDefault();
                currentFolder = btn.getAttribute('data-folder') || '';
                currentType = btn.getAttribute('data-type') || '';
                currentLatestChapter = btn.getAttribute('data-latest') || '1';
                openThumbEditor();
            });
        });

        // --- 1. LOAD DATA AUTOMATICALLY (NO REFRESH) ---
        async function loadDashboardData() {
            const grid = document.getElementById('storyGridContainer');
            
            try {
                const response = await fetch('../get_stories_api.php?scope=mine&t=' + Date.now());
                const data = await response.json();

                if (data.status === 'success') {
                    // Update Stats
                    document.getElementById('statStories').innerText = data.stats.stories;
                    document.getElementById('statChapters').innerText = data.stats.chapters;
                    document.getElementById('statViews').innerText = data.stats.views;
                    document.getElementById('statEarnings').innerText = '$' + data.stats.earnings;

                    // Build Grid
                    if (data.stories.length === 0) {
                        grid.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <h3>No Stories Found</h3>
                                <p>You haven't uploaded any comics or manga yet.</p>
                            </div>`;
                    } else {
                        const loader = grid.querySelector('.loading-indicator');
                        if (loader) loader.remove();
                        const existing = new Map(Array.from(grid.children).map(el => [el.getAttribute('data-key'), el]));
                        const orderKeys = [];
                        data.stories.forEach(story => {
                            const statusClass = story.status === 'Scheduled' ? 'status-scheduled' : 'status-live';
                            const statusText = story.status === 'Scheduled' ? 'Scheduled' : 'Published';
                            const key = `${story.type}/${story.folder}`;
                            orderKeys.push(key);
                            let card = existing.get(key);
                            if (!card) {
                                card = document.createElement('div');
                                card.className = 'story-card';
                                card.setAttribute('data-key', key);
                                const imgWrap = document.createElement('div');
                                imgWrap.className = 'card-image';
                                const img = document.createElement('img');
                                img.src = story.thumbnail;
                                img.alt = 'Cover';
                                imgWrap.appendChild(img);
                                const actions = document.createElement('div');
                                actions.className = 'card-actions';
                                const detailsBtn = document.createElement('button');
                                detailsBtn.className = 'edit-btn view-details-btn';
                                detailsBtn.innerHTML = '<i class="fas fa-info-circle"></i> Details';
                                detailsBtn.setAttribute('data-title', escapeHtml(story.title));
                                detailsBtn.setAttribute('data-folder', escapeHtml(story.folder));
                                detailsBtn.setAttribute('data-type', escapeHtml(story.type));
                                detailsBtn.setAttribute('data-status', escapeHtml(story.status));
                                detailsBtn.setAttribute('data-updated', escapeHtml(story.time_ago));
                                detailsBtn.setAttribute('data-published', escapeHtml(story.publish_date));
                                detailsBtn.setAttribute('data-chapters', story.total_chapters);
                                detailsBtn.setAttribute('data-latest', story.latest_chapter);
                                detailsBtn.setAttribute('data-views', story.views);
                                detailsBtn.setAttribute('data-thumb', escapeHtml(story.thumbnail));
                                const addBtn = document.createElement('a');
                                addBtn.className = 'edit-btn';
                                addBtn.href = 'upload.php';
                                addBtn.innerHTML = '<i class="fas fa-plus"></i> Chapter';
                                const thumbBtn = document.createElement('button');
                                thumbBtn.className = 'edit-btn update-thumb-btn';
                                thumbBtn.innerHTML = '<i class="fas fa-image"></i> Thumbnail';
                                thumbBtn.setAttribute('data-folder', escapeHtml(story.folder));
                                thumbBtn.setAttribute('data-type', escapeHtml(story.type));
                                thumbBtn.setAttribute('data-latest', story.latest_chapter);
                                actions.appendChild(detailsBtn);
                                actions.appendChild(addBtn);
                                actions.appendChild(thumbBtn);
                                imgWrap.appendChild(actions);

                                const content = document.createElement('div');
                                content.className = 'card-content';
                                const top = document.createElement('div');
                                const titleEl = document.createElement('div');
                                titleEl.className = 'story-title';
                                titleEl.textContent = escapeHtml(story.title);
                                const metaEl = document.createElement('div');
                                metaEl.className = 'story-meta';
                                const timeEl = document.createElement('span');
                                timeEl.innerHTML = `<i class="far fa-clock"></i> ${story.time_ago}`;
                                const chEl = document.createElement('span');
                                chEl.className = 'chapter-count';
                                chEl.textContent = `${story.total_chapters} Chs`;
                                metaEl.appendChild(timeEl);
                                metaEl.appendChild(chEl);
                                top.appendChild(titleEl);
                                top.appendChild(metaEl);
                                const bottom = document.createElement('div');
                                bottom.style.cssText = 'display:flex; justify-content:space-between; align-items:center; margin-top:10px;';
                                const statusEl = document.createElement('span');
                                statusEl.className = `status-badge ${statusClass}`;
                                statusEl.textContent = statusText;
                                const viewsEl = document.createElement('span');
                                viewsEl.style.cssText = 'font-size:11px; color:#666;';
                                viewsEl.innerHTML = `<i class="fas fa-eye"></i> ${formatViews(story.views)}`;
                                bottom.appendChild(statusEl);
                                bottom.appendChild(viewsEl);
                                content.appendChild(top);
                                content.appendChild(bottom);

                                card.appendChild(imgWrap);
                                card.appendChild(content);
                                grid.appendChild(card);
                            } else {
                                const img = card.querySelector('.card-image img');
                                if (img && img.src !== story.thumbnail) img.src = story.thumbnail;
                                const detailsBtn = card.querySelector('.view-details-btn');
                                if (detailsBtn) {
                                    detailsBtn.setAttribute('data-title', escapeHtml(story.title));
                                    detailsBtn.setAttribute('data-folder', escapeHtml(story.folder));
                                    detailsBtn.setAttribute('data-type', escapeHtml(story.type));
                                    detailsBtn.setAttribute('data-status', escapeHtml(story.status));
                                    detailsBtn.setAttribute('data-updated', escapeHtml(story.time_ago));
                                    detailsBtn.setAttribute('data-published', escapeHtml(story.publish_date));
                                    detailsBtn.setAttribute('data-chapters', story.total_chapters);
                                    detailsBtn.setAttribute('data-latest', story.latest_chapter);
                                    detailsBtn.setAttribute('data-views', story.views);
                                    detailsBtn.setAttribute('data-thumb', escapeHtml(story.thumbnail));
                                }
                                const titleEl = card.querySelector('.story-title');
                                if (titleEl) titleEl.textContent = escapeHtml(story.title);
                                const timeEl = card.querySelector('.story-meta span:first-child');
                                if (timeEl) timeEl.innerHTML = `<i class="far fa-clock"></i> ${story.time_ago}`;
                                const chEl = card.querySelector('.chapter-count');
                                if (chEl) chEl.textContent = `${story.total_chapters} Chs`;
                                const statusEl = card.querySelector('.status-badge');
                                if (statusEl) { statusEl.className = `status-badge ${statusClass}`; statusEl.textContent = statusText; }
                                const viewsEl = card.querySelector('span[style*="font-size:11px"]');
                                if (viewsEl) viewsEl.innerHTML = `<i class="fas fa-eye"></i> ${formatViews(story.views)}`;
                            }
                        });

                        Array.from(grid.children).forEach(child => {
                            const key = child.getAttribute('data-key');
                            if (key && !orderKeys.includes(key)) child.remove();
                        });
                        orderKeys.forEach(k => {
                            const el = grid.querySelector(`[data-key="${CSS.escape(k)}"]`);
                            if (el) grid.appendChild(el);
                        });

                        attachDetailListeners();
                    }
                }
            } catch (error) {
                console.error(error);
                grid.innerHTML = '<div class="empty-state" style="border-color:red; color:red;">Error loading library.</div>';
            }
        }

        // --- 2. EVENT LISTENERS ---
        function attachDetailListeners() {
            document.querySelectorAll('.view-details-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const thumb = this.getAttribute('data-thumb');
                    currentTitle = this.getAttribute('data-title');
                    currentFolder = this.getAttribute('data-folder');
                    currentType = this.getAttribute('data-type');
                    currentLatestChapter = this.getAttribute('data-latest');
                    const status = this.getAttribute('data-status');

                    document.getElementById('modalImg').src = thumb;
                    document.getElementById('modalTitle').innerText = currentTitle;
                    document.getElementById('modalType').innerText = currentType.charAt(0).toUpperCase() + currentType.slice(1);
                    document.getElementById('modalStatus').innerText = status;
                    
                    const statusEl = document.getElementById('modalStatus');
                    statusEl.style.color = (status === 'Live' || status === 'Published') ? '#00a652' : '#ffcc00';
                    
                    document.getElementById('modalViews').innerText = formatViews(this.getAttribute('data-views'));
                    document.getElementById('modalChapters').innerText = this.getAttribute('data-chapters');
                    document.getElementById('modalLatest').innerText = currentLatestChapter;
                    document.getElementById('modalUpdated').innerText = this.getAttribute('data-updated');
                    document.getElementById('modalPubDate').innerText = this.getAttribute('data-published');
                    
                    document.getElementById('storyModal').style.display = 'flex';
                });
            });
            document.querySelectorAll('.update-thumb-btn').forEach(btn => {
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    currentFolder = this.getAttribute('data-folder');
                    currentType = this.getAttribute('data-type');
                    currentLatestChapter = this.getAttribute('data-latest');
                    openThumbEditor();
                });
            });
        }

        function openThumbEditor(){
            document.getElementById('thumbEditorPreview').style.display = 'none';
            document.getElementById('thumbEditorModal').style.display = 'flex';
        }
        function closeThumbEditor(){ document.getElementById('thumbEditorModal').style.display = 'none'; }
        function previewThumbSelected(e){
            const f = e.target.files[0];
            const img = document.getElementById('thumbEditorPreview');
            if (f) { img.src = URL.createObjectURL(f); img.style.display = 'block'; }
        }
        async function saveThumbnail(){
            const f = document.getElementById('thumbEditorInput').files[0];
            if (!f) { alert('Select an image'); return; }
            const fd = new FormData();
            fd.append('action','update_thumbnail');
            fd.append('series', currentFolder);
            fd.append('type', currentType);
            fd.append('chapterNum', String(currentLatestChapter||'1'));
            fd.append('file', f);
            try {
                const r = await fetch('update_thumbnail.php', { method:'POST', body: fd });
                const j = await r.json();
                if (j.status === 'success') {
                    document.getElementById('modalImg').src = j.thumbnail;
                    closeThumbEditor();
                    loadDashboardData();
                } else { alert('Error: '+ (j.message||'Failed')); }
            } catch(e) { alert('Network Error'); }
        }

        // --- 3. DELETE LOGIC ---
        function showDeleteConfirm() {
            document.getElementById('delSeriesName').innerText = currentTitle;
            document.getElementById('storyModal').style.display = 'none';
            document.getElementById('deleteConfirmModal').style.display = 'flex';
        }

        function closeDeleteConfirm() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
            document.getElementById('storyModal').style.display = 'flex';
        }

        async function confirmDelete() {
            if (!currentFolder || !currentType) return;

            const delBtn = document.querySelector('.btn-confirm-delete');
            delBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Deleting...';
            delBtn.disabled = true;

            try {
                const res = await fetch('delete_series.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ folder: currentFolder, type: currentType.toLowerCase() })
                });
                const data = await res.json();

                if (data.status === 'success') {
                    closeDeleteConfirm();
                    document.getElementById('storyModal').style.display = 'none'; // Close main modal too
                    loadDashboardData(); // REFRESH DATA INSTANTLY WITHOUT PAGE RELOAD
                } else {
                    alert("Error: " + data.message);
                }
            } catch (error) {
                alert("Network Error.");
            } finally {
                delBtn.innerText = 'Delete Permanently';
                delBtn.disabled = false;
            }
        }

        // --- 4. HELPERS ---
        function closeModal() { document.getElementById('storyModal').style.display = 'none'; }
        
        window.onclick = function(event) { 
            if (event.target == document.getElementById('storyModal')) closeModal();
            if (event.target == document.getElementById('deleteConfirmModal')) closeDeleteConfirm();
        }

        document.getElementById('readChapterBtn').addEventListener('click', function() {
            if(currentFolder && currentType && currentLatestChapter) {
                window.location.href = `../read.php?series=${currentFolder}&type=${currentType}&chapter=${currentLatestChapter}`;
            } else {
                alert("Error: Chapter data missing.");
            }
        });

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatViews(n) {
            if (n > 1000000) return (n/1000000).toFixed(1) + 'M';
            if (n > 1000) return (n/1000).toFixed(1) + 'k';
            return n;
        }
    </script>
</body>
</html>

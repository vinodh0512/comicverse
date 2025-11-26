<?php require_once __DIR__ . '/includes/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies | ComicVerse Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* --- RESET & BASE STYLES (Inherited) --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { background-color: #151515; color: #ffffff; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        ul { list-style: none; }

        /* --- LAYOUT STRUCTURE --- */
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            background-color: #111;
            border-right: 1px solid #333;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .logo-container {
            height: 60px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            background-color: #ec1d24;
            color: white;
            font-weight: 900;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .sidebar-menu { padding: 20px 0; flex: 1; }
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: #999;
            font-weight: 600;
            font-size: 14px;
            border-left: 4px solid transparent;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #202020;
            color: white;
            border-left: 4px solid #ec1d24;
        }
        .sidebar-menu i { margin-right: 15px; width: 20px; text-align: center; }

        .sidebar-footer { padding: 20px; border-top: 1px solid #333; }
        .sidebar-footer a { color: #777; font-size: 12px; }
        .sidebar-footer a:hover { color: #ec1d24; }

        /* --- MAIN CONTENT --- */
        .main-content { background-color: #151515; overflow-y: auto; }

        /* --- TOP BAR --- */
        .top-bar {
            height: 60px;
            background-color: #202020;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-profile { display: flex; align-items: center; gap: 15px; }
        .admin-info { text-align: right; font-size: 12px; }
        .admin-avatar { width: 35px; height: 35px; background: #ec1d24; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        /* --- CONTENT AREA --- */
        .content-wrapper { padding: 30px; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-title h2 { font-size: 24px; font-weight: 800; text-transform: uppercase; margin-bottom: 5px; }
        .page-title p { color: #777; font-size: 14px; }

        /* --- CONTROLS BAR --- */
        .controls-bar {
            display: flex;
            gap: 15px;
            background: #202020;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #333;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            background: #151515;
            border: 1px solid #444;
            padding: 10px 15px;
            color: white;
            border-radius: 4px;
            outline: none;
        }
        .search-input:focus { border-color: #ec1d24; }

        .filter-select {
            background: #151515;
            border: 1px solid #444;
            color: #ccc;
            padding: 0 15px;
            border-radius: 4px;
            outline: none;
            cursor: pointer;
        }

        .btn-add {
            background-color: #ec1d24;
            color: white;
            padding: 10px 25px;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-add:hover { background-color: #ff333b; }

        /* --- DATA TABLE --- */
        .table-container {
            background: #202020;
            border-radius: 6px;
            border: 1px solid #333;
            overflow-x: auto;
        }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        
        th {
            text-align: left;
            padding: 15px 20px;
            background-color: #1a1a1a;
            color: #888;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            border-bottom: 1px solid #333;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #333;
            vertical-align: middle;
        }

        tr:hover { background-color: #252525; }
        tr:last-child td { border-bottom: none; }

        /* Table Elements */
        .webtoon-info { display: flex; align-items: center; gap: 15px; }
        /* Webtoon thumbs are often square or longer vertical crops */
        .webtoon-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; background: #333; }
        .webtoon-title { font-weight: 700; display: block; margin-bottom: 3px; color: #fff; }
        .webtoon-creator { font-size: 12px; color: #888; }
        
        /* Specific Badge for Webtoon Types */
        .type-tag { font-size: 10px; padding: 2px 5px; border-radius: 3px; margin-left: 5px; font-weight: 700; }
        .tag-original { background: #00a652; color: white; } /* Green for Originals */
        .tag-canvas { background: #ffcc00; color: #111; } /* Yellow for Canvas/Indie */

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-ongoing { background: rgba(0, 166, 82, 0.15); color: #00a652; border: 1px solid rgba(0, 166, 82, 0.3); }
        .status-completed { background: rgba(0, 120, 255, 0.15); color: #0078ff; border: 1px solid rgba(0, 120, 255, 0.3); }
        .status-hiatus { background: rgba(255, 204, 0, 0.15); color: #ffcc00; border: 1px solid rgba(255, 204, 0, 0.3); }

        .action-buttons { display: flex; gap: 10px; }
        .btn-icon {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 4px;
            border: 1px solid #444;
            color: #ccc;
            cursor: pointer;
            transition: 0.2s;
            background: #151515;
        }
        .btn-icon:hover { border-color: #ec1d24; color: #ec1d24; }
        .btn-icon.delete:hover { border-color: #ff333b; color: #ff333b; }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: flex-end;
            padding: 20px;
            gap: 5px;
        }
        .page-btn {
            background: #151515;
            border: 1px solid #333;
            color: #888;
            padding: 5px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .page-btn.active, .page-btn:hover {
            background: #ec1d24;
            color: white;
            border-color: #ec1d24;
        }

        @media (max-width: 768px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .controls-bar { flex-direction: column; }
            .webtoon-info { flex-direction: column; align-items: flex-start; gap: 5px; }
            .webtoon-thumb { display: none; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <aside class="sidebar">
        <div class="logo-container">CV ADMIN</div>
        <ul class="sidebar-menu">
            <li><a href="admin.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="managemanga.php"><i class="fas fa-book-open"></i> Manage Manga</a></li>
            <li><a href="managecomic.php"><i class="fas fa-mask"></i> Manage Comics</a></li>
            <li><a href="#" class="active"><i class="fas fa-film"></i> Manage Movies</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="#"><i class="fas fa-sign-out-alt"></i> Logout System</a>
        </div>
    </aside>

    <main class="main-content">
        
        <div class="top-bar">
            <div style="color:#888; font-size:14px;">
                Admin / Content / <span style="color:white;">Manage Movies</span>
            </div>
            <div class="admin-profile">
                <div class="admin-info">
                    <div style="font-weight:700;">Admin User</div>
                    <div style="color:#777;">Super Admin</div>
                </div>
                <div class="admin-avatar">AD</div>
            </div>
        </div>

        <div class="content-wrapper">
            
            <div class="page-header">
                <div class="page-title">
                    <h2>Manage Webtoon Library</h2>
                    <p>Oversee vertical-scroll comics, originals, and canvas stories.</p>
                </div>
                <div style="display:flex; gap:20px;">
                    <div style="text-align:right;">
                        <div style="font-size:20px; font-weight:800;">850</div>
                        <div style="font-size:11px; color:#777; text-transform:uppercase;">Total Series</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:20px; font-weight:800; color:#00a652;">215</div>
                        <div style="font-size:11px; color:#777; text-transform:uppercase;">Episodes this Week</div>
                    </div>
                </div>
            </div>

            <div class="controls-bar">
                <input type="text" class="search-input" placeholder="Search by title, creator, or genre...">
                
                <select class="filter-select">
                    <option value="all">All Categories</option>
                    <option value="original">CV Originals</option>
                    <option value="canvas">Canvas / Indie</option>
                </select>

                <select class="filter-select">
                    <option value="all">All Genres</option>
                    <option value="romance">Romance</option>
                    <option value="fantasy">Fantasy</option>
                    <option value="action">Action</option>
                    <option value="drama">Drama</option>
                </select>

                <button class="btn-add" onclick="window.location.href='uploader.html'">
                    <i class="fas fa-plus"></i> Add Webtoon
                </button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Webtoon Details</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Episodes</th>
                            <th>Rating</th>
                            <th>Views</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="webtoonBody">
                        <tr>
                            <td>1</td>
                            <td>
                                <div class="webtoon-info">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/c/cc/Solo_Leveling_Webtoon_01.jpg" class="webtoon-thumb" alt="Thumb">
                                    <div>
                                        <a href="#" class="webtoon-title">Solo Leveling</a>
                                        <span class="webtoon-creator">Chugong</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="type-tag tag-original">ORIGINAL</span></td>
                            <td><span class="status-badge status-completed">Completed</span></td>
                            <td>179</td>
                            <td><i class="fas fa-star" style="color:#ffcc00"></i> 5.0</td>
                            <td>3.2M</td>
                            <td>
                                <div class="action-buttons">
                                    <div class="btn-icon" title="Edit"><i class="fas fa-pen"></i></div>
                                    <div class="btn-icon" title="Upload Episode" onclick="window.location.href='uploader.html'"><i class="fas fa-upload"></i></div>
                                    <div class="btn-icon delete" title="Delete"><i class="fas fa-trash"></i></div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>2</td>
                            <td>
                                <div class="webtoon-info">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/c/c7/Lore_Olympus_Vol_1.jpg" class="webtoon-thumb" alt="Thumb">
                                    <div>
                                        <a href="#" class="webtoon-title">Lore Olympus</a>
                                        <span class="webtoon-creator">Rachel Smythe</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="type-tag tag-original">ORIGINAL</span></td>
                            <td><span class="status-badge status-ongoing">Ongoing</span></td>
                            <td>260</td>
                            <td><i class="fas fa-star" style="color:#ffcc00"></i> 4.8</td>
                            <td>2.8M</td>
                            <td>
                                <div class="action-buttons">
                                    <div class="btn-icon" title="Edit"><i class="fas fa-pen"></i></div>
                                    <div class="btn-icon" title="Upload Episode" onclick="window.location.href='uploader.html'"><i class="fas fa-upload"></i></div>
                                    <div class="btn-icon delete" title="Delete"><i class="fas fa-trash"></i></div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>3</td>
                            <td>
                                <div class="webtoon-info">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/5/59/Tower_of_God_Volume_1_Cover.jpg" class="webtoon-thumb" alt="Thumb">
                                    <div>
                                        <a href="#" class="webtoon-title">Tower of God</a>
                                        <span class="webtoon-creator">SIU</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="type-tag tag-original">ORIGINAL</span></td>
                            <td><span class="status-badge status-ongoing">Ongoing</span></td>
                            <td>580</td>
                            <td><i class="fas fa-star" style="color:#ffcc00"></i> 4.9</td>
                            <td>2.1M</td>
                            <td>
                                <div class="action-buttons">
                                    <div class="btn-icon" title="Edit"><i class="fas fa-pen"></i></div>
                                    <div class="btn-icon" title="Upload Episode" onclick="window.location.href='uploader.html'"><i class="fas fa-upload"></i></div>
                                    <div class="btn-icon delete" title="Delete"><i class="fas fa-trash"></i></div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>4</td>
                            <td>
                                <div class="webtoon-info">
                                    <img src="https://upload.wikimedia.org/wikipedia/en/e/e3/True_Beauty_Vol_1.jpg" class="webtoon-thumb" alt="Thumb">
                                    <div>
                                        <a href="#" class="webtoon-title">True Beauty</a>
                                        <span class="webtoon-creator">Yaongyi</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="type-tag tag-original">ORIGINAL</span></td>
                            <td><span class="status-badge status-completed">Completed</span></td>
                            <td>223</td>
                            <td><i class="fas fa-star" style="color:#ffcc00"></i> 4.7</td>
                            <td>1.9M</td>
                            <td>
                                <div class="action-buttons">
                                    <div class="btn-icon" title="Edit"><i class="fas fa-pen"></i></div>
                                    <div class="btn-icon" title="Upload Episode" onclick="window.location.href='uploader.html'"><i class="fas fa-upload"></i></div>
                                    <div class="btn-icon delete" title="Delete"><i class="fas fa-trash"></i></div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>5</td>
                            <td>
                                <div class="webtoon-info">
                                    <img src="https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?q=80&w=200" class="webtoon-thumb" alt="Thumb">
                                    <div>
                                        <a href="#" class="webtoon-title">Midnight Café</a>
                                        <span class="webtoon-creator">Indie Artist</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="type-tag tag-canvas">CANVAS</span></td>
                            <td><span class="status-badge status-hiatus">Hiatus</span></td>
                            <td>45</td>
                            <td><i class="fas fa-star" style="color:#ffcc00"></i> 4.5</td>
                            <td>120k</td>
                            <td>
                                <div class="action-buttons">
                                    <div class="btn-icon" title="Edit"><i class="fas fa-pen"></i></div>
                                    <div class="btn-icon" title="Upload Episode" onclick="window.location.href='uploader.html'"><i class="fas fa-upload"></i></div>
                                    <div class="btn-icon delete" title="Delete"><i class="fas fa-trash"></i></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="pagination">
                    <button class="page-btn">Prev</button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">Next</button>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var tbody=document.getElementById('webtoonBody');
    if(!tbody) return;
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#888;"><i class="fas fa-circle-notch fa-spin"></i> Loading webtoons...</td></tr>';
    fetch('../get_stories_api.php?t='+Date.now()).then(function(r){return r.json()}).then(function(d){
        if(!d||d.status!=='success'){ tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#888;">Failed to load</td></tr>'; return; }
        var stories=(d.stories||[]).filter(function(s){ return (s.type||'').toLowerCase()==='webtoon'; });
        if(!stories.length){ tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#888;">No webtoons found</td></tr>'; return; }
        tbody.innerHTML='';
        stories.forEach(function(s,idx){
            var tr=document.createElement('tr');
            var statusBadge='<span class="status-badge status-ongoing">Ongoing</span>';
            tr.innerHTML = '<td>'+(idx+1)+'</td>'+
                '<td><div class="webtoon-info"><img src="'+(s.thumbnail||'')+'" class="webtoon-thumb" alt="Thumb"><div><a href="#" class="webtoon-title">'+(s.title||s.folder||'')+'</a><span class="webtoon-creator">Creator</span></div></div></td>'+
                '<td><span class="type-tag tag-original">ORIGINAL</span></td>'+
                '<td>'+statusBadge+'</td>'+
                '<td>'+(s.total_chapters||0)+'</td>'+
                '<td><i class="fas fa-star" style="color:#ffcc00"></i> '+(Math.round((Math.random()*1.0+4.0)*10)/10)+'</td>'+
                '<td>'+(s.views||0)+'</td>'+
                '<td><div class="action-buttons"><div class="btn-icon" title="Edit"><i class="fas fa-pen"></i></div><div class="btn-icon" title="Upload Episode"><i class="fas fa-upload"></i></div><div class="btn-icon delete" title="Delete"><i class="fas fa-trash"></i></div></div></td>';
            tbody.appendChild(tr);
        });
    }).catch(function(){ tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#888;">Error loading data</td></tr>'; });
});
</script>

</body>
</html>
